<?php

namespace src\like\controller;

use InvalidArgumentException;
use src\auth\dto\UserInfoDto;
use src\common\JsonResponse;
use src\like\repository\LikeRepository;
use src\like\service\LikeService;

class LikeController
{
    private const TARGET_TYPE_MAP = [
        'board',
        'comment',
    ];

    public function __construct(
        private readonly LikeRepository $likeRepository = new LikeRepository(),
    ) {
    }


    public function post(UserInfoDto $userInfoDto): bool
    {
        [$targetType, $targetId] = $this->validate();
        $result = $this->likeRepository->addLike($userInfoDto->id, $targetType, $targetId);

        if (!$result) {
            throw new InvalidArgumentException('이미 등록했습니다.', 409);
        }
        JsonResponse::send(
            status: 200,
            message: '등록되었습니다.',
            body: [
                'userId' => $userInfoDto->id,
                'targetType' => $targetType,
                'targetId' => $targetId
            ],
        );
    }

    public function delete(UserInfoDto $userInfoDto): bool
    {
        [$targetType, $targetId] = $this->validate();
        $result = $this->likeRepository->removeLike($userInfoDto->id, $targetType, $targetId);
        if (!$result) {
            throw new InvalidArgumentException('이미 삭제했습니다', 409);
        }
        JsonResponse::send(
            status: 200,
            message: '삭제되었습니다.',
            body: [
                'userId' => $userInfoDto->id,
                'targetType' => $targetType,
                'targetId' => $targetId
            ],
        );
    }

    /**
     * 유효성 검증 메서드
     * SQL 인젝션 방지
     */
    private function validate(): array
    {
        $targetType = strtolower(trim($_GET['target_type'] ?? ''));
        $targetId = (int) ($_GET['target_id'] ?? 0);

        if (!in_array($targetType, self::TARGET_TYPE_MAP, true) || $targetId <= 0) {
            throw new InvalidArgumentException('유효하지 않은 target_type 또는 target_id 입니다.', 400);
        }

        return [
            $targetType,
            $targetId,
        ];
    }
}
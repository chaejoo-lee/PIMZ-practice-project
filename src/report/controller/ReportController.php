<?php

namespace src\report\controller;

use src\auth\dto\UserInfoDto;
use src\common\JsonResponse;
use src\report\repository\ReportRepository;

class ReportController
{

    public function __construct(
        private readonly ReportRepository $reportRepository = new ReportRepository()

    ) {
    }

    // 게시글 신고
    public function post(UserInfoDto $userInfoDto, int $boardId)
    {
        $isCreated = $this->reportRepository->create($boardId, $userInfoDto->id);

        JsonResponse::send(
            status: 200,
            message: $isCreated ? '신고가 접수되었습니다.' : '이미 신고한 게시글입니다.',
            body: [
                'boardId' => $boardId,
                'isReported' => true
            ]
        );
    }

}
?>
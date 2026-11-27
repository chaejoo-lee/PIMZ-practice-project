<?php

namespace src\comment\controller;

use InvalidArgumentException;

use src\common\JsonResponse;
use src\auth\dto\UserInfoDto;
use src\comment\repository\CommentRepository;

class CommentController
{

    public function __construct(
        private readonly CommentRepository $commentRepository = new CommentRepository()
    ) {
    }




    /**
     * 댓글 작성
     */
    public function post(UserInfoDto $userInfoDto, int $boardId, string $content, ?int $commentId = null): void
    {
        $this->commentRepository->save(
            boardId: $boardId,
            userId: $userInfoDto->id,
            content: $content,
            parentId: empty($commentId) ? null : $commentId,
        );

        JsonResponse::send(
            status: 200,
            message: "생성되었습니다.",
        );
    }

    /**
     * DELETE 요청 처리: 
     */
    public function delete(UserInfoDto $userInfoDto, int $commentId): void
    {
        $this->commentRepository->unsetComment(
            id: $commentId
        );

        JsonResponse::send(
            status: 200,
            message: "댓글이 삭제되었습니다.",
        );
    }



}

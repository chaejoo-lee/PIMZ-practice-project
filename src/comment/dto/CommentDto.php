<?php

namespace src\comment\dto;



class CommentDto
{
    public function __construct(
        public readonly int $id,
        public readonly int $boardId,
        public readonly ?int $parentId,
        public readonly int $userId,
        public readonly string $email,
        public readonly int $likeCount,
        public readonly bool $isLiked,
        public readonly ?string $content,
        public readonly string $updatedAt
    ) {
    }

    public static function from(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            boardId: (int) $row['board_id'],
            parentId: (int) $row['parent_id'] ?? null,
            userId: (int) $row['user_id'],
            email: $row['email'],
            likeCount: (int) $row['like_count'],
            isLiked: (bool) $row['is_liked'],
            content: $row['status'] ? $row['content'] : null,
            updatedAt: $row['updated_at']
        );
    }
}

?>
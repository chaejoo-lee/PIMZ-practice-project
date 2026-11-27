<?php
namespace src\excel\dto;


class ExcelCommentDto
{
    public function __construct(
        public readonly int $board_id,
        public readonly ?int $parentId,
        public readonly string $content,
        public readonly string $email,
        public readonly string $createdAt,
        public readonly string $updatedAt
    ) {
    }

    public static function from(array $row): self
    {
        return new self(
            board_id: $row["board_id"],
            parentId: $row["parent_id"] ?? null,
            email: $row['email'],
            content: $row['content'],
            createdAt: $row['created_at'],
            updatedAt: $row['updated_at']
        );
    }

}
?>
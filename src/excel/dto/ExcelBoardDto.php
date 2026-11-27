<?php
namespace src\excel\dto;


class ExcelBoardDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $email,
        public readonly ?string $filePath,
        public readonly string $content,
        public readonly string $createdAt,
        public readonly string $updatedAt
    ) {
    }

    public static function from(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            title: $row['title'],
            email: $row['email'],
            content: $row['content'],
            createdAt: $row['created_at'],
            updatedAt: $row['updated_at'],
            filePath: $row['path'],
        );
    }
}

?>
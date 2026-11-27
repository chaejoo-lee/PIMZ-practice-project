<?php


namespace src\board\dto;

class BoardDetailDto
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $email,
        public readonly string $role,
        public readonly string $title,
        public readonly string $content,
        public readonly int $likeCount,
        public readonly bool $isLiked,
        public readonly int $commentCount,
        public readonly string $type,
        public readonly string $colorCode,
        public readonly bool $isSecret,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly array $images = [],
    ) {
    }

    public static function from(array $row, ?array $images = null): self
    {
        return new self(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            email: $row['email'],
            role: $row['role'],
            title: $row['title'],
            content: $row['content'],
            likeCount: (int) ($row['like_count'] ?? 0),
            isLiked: (bool) $row['is_liked'],
            commentCount: (int) ($row['comment_count'] ?? 0),
            type: $row['type'],
            colorCode: $row['color_code'],
            isSecret: (bool) ($row['is_secret'] ?? false),
            createdAt: $row['created_at'],
            updatedAt: $row['updated_at'],
            images: self::extractImages($images),
        );
    }

    /**
     */
    private static function extractImages(?array $images): array
    {
        $result = [];

        if (!empty($images)) {
            $paths = array_column($images, 'path');
            $result = array_merge($result, !empty($paths) ? $paths : $images);
        }

        // 중복 경로 차단
        return array_values(array_unique($result));
    }
}

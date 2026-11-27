<?php

declare(strict_types=1);

namespace src\board\dto;

class BoardDto
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $email,
        public readonly string $role,
        public readonly string $title,
        public readonly string $content,
        public readonly int $likeCount,
        public readonly int $commentCount,
        public readonly string $community_type,
        public readonly string $colorCode,
        public readonly bool $isSecret,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly array $images = [],
    ) {
    }

    public static function from(array $row, ?array $images = null): self
    {
        $isSecret = (bool) ($row['is_secret'] ?? false);

        return new self(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            email: $row['email'],
            role: $row['role'],
            title: $row['title'],
            content: $row['content'],
            likeCount: (int) ($row['like_count'] ?? 0),
            commentCount: (int) ($row['comment_count'] ?? 0),
            community_type: $row['community_type'],
            colorCode: $row['color_code'],
            isSecret: $isSecret,
            createdAt: $row['created_at'],
            updatedAt: $row['updated_at'],
            images: $isSecret ? [] : self::extractImages($row, $images),
        );
    }

    /**
     */
    private static function extractImages(array $row, ?array $images): array
    {
        $result = [];
        if (!empty($row['path'])) {
            $result[] = $row['path'];
        }

        if (!empty($images)) {
            $paths = array_column($images, 'path');
            $result = array_merge($result, !empty($paths) ? $paths : $images);
        }

        // 중복 경로 차단
        return array_values(array_unique($result));
    }
}

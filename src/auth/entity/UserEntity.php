<?php

namespace src\auth\entity;


class UserEntity
{
    // 생성자
    public function __construct(
        public string $id,
        public string $email,
        public string $password,
        public string $role,
        public string $created_at,
        public string $updated_at,
    ) {
    }

    public static function from(array $row): UserEntity
    {
        return new self(
            id: (string) ($row['id'] ?? ''),
            email: $row['email'] ?? '',
            password: $row['password'] ?? '',
            role: $row['role'] ?? '',
            created_at: $row['created_at'] ?? '',
            updated_at: $row['updated_at'] ?? '',
        );
    }
}
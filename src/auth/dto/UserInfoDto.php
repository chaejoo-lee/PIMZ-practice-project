<?php

namespace src\auth\dto;


class UserInfoDto
{
    // 생성자
    public function __construct(
        public readonly string $email,
        public readonly string $id,
        public readonly string $createdAt,
        public readonly string $role
    ) {
    }
}

?>
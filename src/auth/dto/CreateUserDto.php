<?php

namespace src\auth\dto;


class CreateUserDto
{
    // 생성자
    public function __construct(
        public readonly string $email,
        public readonly string $password,
    ) {
    }
}

?>
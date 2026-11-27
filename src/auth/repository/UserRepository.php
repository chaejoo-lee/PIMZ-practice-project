<?php

namespace src\auth\repository;

use InvalidArgumentException;
use src\auth\dto\CreateUserDto;
use src\auth\entity\UserEntity;
use src\common\MySQL;

class UserRepository
{

    /**
     * 이메일로 유저 찾기
     */
    public function findByEmail(string $email): ?UserEntity
    {
        $query =
            "SELECT id, email, password 
            FROM users 
            WHERE email = ? 
            LIMIT 1
        ";

        $result = MySQL::query($query, [$email]);

        return $result ? UserEntity::from($result) : null;
    }

    /**
     * 유저 등록
     */
    public function create(CreateUserDto $dto): UserEntity
    {
        if (self::findByEmail($dto->email) === null) {
            throw new InvalidArgumentException('이메일이 중복', 409);
        }
        $query = "INSERT INTO users (email, password, role) VALUES (?, ?, ?)";

        $hashedPassword = password_hash($dto->password, PASSWORD_DEFAULT);

        MySQL::query($query, [$dto->email, $hashedPassword, "user"]);

        return $this->findByEmail($dto->email);
    }


    /**
     * id로 유저 찾기
     */
    public function findById(string $id): ?UserEntity
    {
        $query =
            "SELECT id, email, password, created_at, updated_at 
            FROM users 
            WHERE id = ? 
            LIMIT 1
        ";

        $result = MySQL::query($query);

        return $result ? UserEntity::from($result) : null;
    }


    /**
     * email password로 유저 찾기
     */
    public function findByEmailAndPassword(string $email, string $password): ?UserEntity
    {
        $query =
            "SELECT id, email, password, created_at, updated_at 
         FROM users 
         WHERE email = ? 
         LIMIT 1
    ";

        // 조회
        $result = MySQL::query($query, [$email]);

        $user = $result[0] ?? null;

        if ($user && password_verify($password, $user['password'])) {
            return UserEntity::from($user);
        }

        return null;
    }

}
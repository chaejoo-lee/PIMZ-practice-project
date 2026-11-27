<?php

namespace src\common;

use InvalidArgumentException;
use src\auth\dto\UserInfoDto;
use src\auth\entity\UserEntity;

class Session
{
    public function findBySessionId(string $id): UserEntity|null
    {
        $query =
            "SELECT 
                users.id, 
                users.email, 
                users.role,
                users.password, 
                users.created_at, 
                users.updated_at 
            FROM session
            INNER JOIN users ON session.user_id = users.id
            WHERE session.data = ? 
            LIMIT 1
        ";

        $result = MySQL::query($query, [$id]);
        return $result ? UserEntity::from($result[0]) : null;
    }

    // session 등록
    public function create(int $userId, string $session): bool
    {
        $query =
            "INSERT INTO session (user_id, data) 
            VALUES (?, ?)
            ";

        return MySQL::query($query, [$userId, $session]);
    }


    public function getUserBySession(?string $session): ?UserInfoDto
    {
        error_log("session id : " . $session);
        if (empty($session)) {
            return null;
        }
        $user = $this->findBySessionId($session);

        if (!$user) {
            throw new InvalidArgumentException("새션이 틀립니다.", 404);
        }

        return new UserInfoDto(
            id: $user->id,
            email: $user->email,
            createdAt: $user->created_at,
            role: $user->role,
        );
    }

}
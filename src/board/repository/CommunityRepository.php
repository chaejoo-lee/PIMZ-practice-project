<?php

namespace src\board\repository;

use InvalidArgumentException;
use src\auth\dto\UserInfoDto;
use src\common\MySQL;

class CommunityRepository
{
    /**
     * 커뮤니티 단건 조회 (없으면 404 예외)
     */
    public function findByIdOrFail(int $communityId): array
    {
        $rows = MySQL::query(
            "SELECT id, title, color_code, password, is_secret
            FROM community
            WHERE id = ?
            LIMIT 1",
            [$communityId]
        );

        $community = $rows[0] ?? null;
        if ($community === null) {
            throw new InvalidArgumentException('게시판이 존재하지 않습니다.', 404);
        }

        return $community;
    }

    /**
     * 커뮤니티 전체 목록 조회
     */
    public function findAll(): array
    {
        $rows = MySQL::query(
            "SELECT
                c.id,
                c.title,
                c.color_code,
                c.is_secret
            FROM community c"
        );

        return array_map(
            static fn(array $row): array => [
                'id' => (int) $row['id'],
                'title' => $row['title'],
                'color_code' => $row['color_code'],
                'is_secret' => (bool) $row['is_secret'],
            ],
            $rows
        );
    }

    /**
     * 비밀 커뮤니티 비밀번호 검증
     */
    public function verifyPassword(int $communityId, ?string $password, ?UserInfoDto $userInfoDto = null): bool
    {
        $community = $this->findByIdOrFail($communityId);
        $this->assertCanRead($community, $password, $userInfoDto);

        return true;
    }

    /**
     * 커뮤니티 열람 권한 검증 (실패 시 403 예외)
     */
    public function assertCanRead(array $community, ?string $password, ?UserInfoDto $userInfoDto): void
    {
        if (!$this->canRead($community, $password, $userInfoDto)) {
            throw new InvalidArgumentException('비밀 게시판 비밀번호가 올바르지 않습니다.', 403);
        }
    }

    public function canRead(array $community, ?string $password, ?UserInfoDto $userInfoDto): bool
    {
        $isSecret = (bool) ($community['is_secret'] ?? false);

        if ($userInfoDto !== null && strtolower($userInfoDto->role) === 'admin' || !$isSecret) {
            return true;
        }

        if ($userInfoDto === null) {
            return false;
        }

        $passwordHash = $community['community_password'] ?? $community['password'] ?? null;

        return $password !== null
            && $password !== ''
            && is_string($passwordHash)
            && password_verify($password, $passwordHash);
    }

}
<?php

namespace src\board\repository;

use InvalidArgumentException;
use src\auth\dto\UserInfoDto;
use src\board\dto\BoardDetailDto;
use src\board\dto\BoardDto;
use src\common\MySQL;
use src\board\repository\CommunityRepository;

class BoardRepository
{
    public function __construct(
        private readonly BoardImageRepository $boardImageRepository = new BoardImageRepository(),
        private readonly CommunityRepository $communityRepository = new CommunityRepository(),
    ) {
    }

    /**
     * 게시글 목록 조회 (전체 또는 특정 커뮤니티 + 검색)
     */
    public function findAll(
        int $communityId = 0,
        ?string $communityPassword = null,
        ?UserInfoDto $userInfoDto = null,
        string $search = ''
    ): array {
        $canReadSecret = false;

        if ($communityId > 0) {
            $community = $this->communityRepository->findByIdOrFail($communityId);
            $this->communityRepository->assertCanRead($community, $communityPassword, $userInfoDto);
            $canReadSecret = true;
        }

        [$whereSql, $params] = $this->buildFindAllConditions($communityId, $search);

        $rows = MySQL::query(
            "SELECT
                b.id,
                b.user_id,
                u.email,
                u.role,
                b.title,
                b.content,
                b.like_count,
                b.comment_count,
                c.title AS community_type,
                c.color_code,
                c.is_secret,
                bi.path,
                b.created_at,
                b.updated_at
            FROM board b
            INNER JOIN users u ON b.user_id = u.id
            INNER JOIN community c ON b.community_id = c.id
            LEFT JOIN board_images bi
                ON b.id = bi.board_id AND bi.sort_order = 0
            {$whereSql}
            ORDER BY b.id DESC",
            $params
        );

        $boards = [];
        foreach ($rows as $row) {
            if (!$canReadSecret && !empty($row['is_secret'])) {
                continue;
            }
            $boards[] = BoardDto::from($row);
        }

        return $boards;
    }

    /**
     * 게시글 단건 상세 조회
     */
    public function findOneById(
        int $id,
        ?string $communityPassword = null,
        ?UserInfoDto $userInfoDto = null
    ): BoardDetailDto {
        $rows = MySQL::query(
            "SELECT
                b.id,
                b.user_id,
                u.email,
                u.role,
                b.title,
                b.content,
                b.like_count,
                b.comment_count,
                c.id AS community_id,
                c.title AS type,
                c.color_code,
                c.is_secret,
                c.password AS community_password,
                b.created_at,
                b.updated_at,
                CASE WHEN l.id IS NOT NULL THEN 1 ELSE 0 END AS is_liked
            FROM board b
            INNER JOIN users u ON b.user_id = u.id
            INNER JOIN community c ON b.community_id = c.id
            LEFT JOIN likes l
                ON l.target_type = 'board'
               AND l.target_id = b.id
               AND l.user_id = ?
            WHERE b.id = ?",
            [$userInfoDto->id ?? 0, $id]
        );

        $boardRow = $rows[0] ?? null;
        if ($boardRow === null) {
            throw new InvalidArgumentException('게시글이 존재하지 않습니다.', 404);
        }

        $this->communityRepository->assertCanRead($boardRow, $communityPassword, $userInfoDto);
        unset($boardRow['community_password']);

        $images = $this->boardImageRepository->findByBoardId($id);

        return BoardDetailDto::from($boardRow, $images);
    }

    /**
     * 게시글 생성 후 생성된 ID 반환
     */
    public function save(int $userId, string $title, string $content, int $communityId): int
    {
        return MySQL::query(
            "INSERT INTO board (user_id, title, content, community_id)
            VALUES (?, ?, ?, ?)",
            [$userId, $title, $content, $communityId]
        );
    }

    /**
     * 게시글 수정
     */
    public function update(int $boardId, string $title, string $content): bool
    {
        return MySQL::query(
            "UPDATE board
            SET title = ?, content = ?
            WHERE id = ?",
            [$title, $content, $boardId]
        );
    }

    /**
     * 게시글 및 연관 데이터(좋아요, 신고) 삭제
     */
    public function delete(int $userId, int $boardId): bool
    {
        $row = MySQL::query(
            "SELECT id
            FROM board
            WHERE id = ? AND user_id = ?
            FOR UPDATE",
            [$boardId, $userId]
        );

        if (empty($row)) {
            return false;
        }

        MySQL::query(
            "DELETE FROM likes
            WHERE (target_type = 'board' AND target_id = ?)
               OR (target_type = 'comment' AND target_id IN (
                   SELECT id FROM comment WHERE board_id = ?
               ))",
            [$boardId, $boardId]
        );

        MySQL::query("DELETE FROM report WHERE board_id = ?", [$boardId]);

        return MySQL::query(
            "DELETE FROM board WHERE id = ? AND user_id = ?",
            [$boardId, $userId]
        ) > 0;
    }

    private function buildFindAllConditions(int $communityId, string $search): array
    {
        $wheres = [];
        $params = [];

        if ($communityId > 0) {
            $wheres[] = "c.id = ?";
            $params[] = $communityId;
        } else {
            $wheres[] = "c.is_secret = 0";
        }

        $keyword = trim($search);
        if ($keyword !== '') {
            $wheres[] = "(b.title LIKE ? OR b.content LIKE ?)";
            $likeKeyword = '%' . $keyword . '%';
            $params[] = $likeKeyword;
            $params[] = $likeKeyword;
        }

        $whereSql = !empty($wheres) ? 'WHERE ' . implode(' AND ', $wheres) : '';

        return [$whereSql, $params];
    }
}
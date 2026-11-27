<?php

namespace src\report\repository;

use InvalidArgumentException;
use src\common\MySQL;

class ReportRepository
{
    public function __construct()
    {
    }

    /**
     * 신고된 게시글 전체 목록 및 총 신고 횟수 조회
     */
    public function getAll(int $minCount = 0): array
    {
        return MySQL::query(
            "SELECT
                b.id,
                b.user_id,
                u.email AS author_email,
                b.community_id,
                c.title AS community_title,
                b.title,
                b.content,
                b.like_count,
                b.comment_count,
                b.created_at,
                b.updated_at,
                COUNT(r.id) AS report_count,
                MAX(r.created_at) AS last_reported_at
            FROM board b
            INNER JOIN report r ON b.id = r.board_id
            INNER JOIN users u ON b.user_id = u.id
            INNER JOIN community c ON b.community_id = c.id
            GROUP BY b.id
            HAVING report_count > ?
            ORDER BY report_count DESC, last_reported_at DESC",
            [$minCount]
        );
    }

    /**
     * 특정 신고된 게시글 단건 및 신고 횟수 조회
     */
    public function get(int $boardId): ?array
    {
        $rows = MySQL::query(
            "SELECT
                b.id,
                b.user_id,
                u.email AS author_email,
                b.community_id,
                c.title AS community_title,
                b.title,
                b.content,
                b.like_count,
                b.comment_count,
                b.created_at,
                b.updated_at,
                COUNT(r.id) AS report_count,
                MAX(r.created_at) AS last_reported_at
            FROM board b
            INNER JOIN report r ON b.id = r.board_id
            INNER JOIN users u ON b.user_id = u.id
            INNER JOIN community c ON b.community_id = c.id
            WHERE b.id = ?
            GROUP BY
                b.id
            LIMIT 1",
            [$boardId]
        );

        return $rows[0] ?? null;
    }

    /**
     * 게시글 신고 등록
     */
    public function create(int $boardId, int $userId): bool
    {
        $exists = MySQL::query(
            "SELECT id FROM board WHERE id = ? LIMIT 1",
            [$boardId]
        );
        if (empty($exists)) {
            throw new InvalidArgumentException('존재하지 않는 게시글입니다.', 404);
        }

        // 신고 기록 추가
        $result = MySQL::query(
            "INSERT IGNORE INTO report (board_id, user_id)
             VALUES (?, ?)",
            [$boardId, $userId]
        );

        return $result !== 0;
    }

    public function deleteReportsByBoardId(int $boardId): bool
    {
        $exists = MySQL::query(
            "SELECT id FROM report WHERE board_id = ? LIMIT 1",
            [$boardId]
        );
        if (empty($exists)) {
            return false;
        }

        MySQL::query(
            "DELETE FROM report WHERE board_id = ?",
            [$boardId]
        );

        return true;
    }

    /**
     * 신고된 게시글 자체를 삭제 
     */
    public function deleteReportedBoard(int $boardId): bool
    {
        $exists = MySQL::query(
            "SELECT id FROM report WHERE board_id = ? LIMIT 1",
            [$boardId]
        );
        if (empty($exists)) {
            return false;
        }

        MySQL::query(
            "DELETE FROM board WHERE id = ?",
            [$boardId]
        );

        return true;
    }
}
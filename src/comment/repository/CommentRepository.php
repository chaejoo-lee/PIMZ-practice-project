<?php

namespace src\comment\repository;

use src\common\MySQL;
use src\comment\dto\CommentDto;

class CommentRepository
{

    // 댓글 전체 조회
    public function findAllByBoardId(int $boardId, ?int $userId): array
    {
        $query =
            "SELECT
                c.id,
                c.board_id,
                c.parent_id,
                c.content,
                c.user_id,
                u.email,
                c.like_count,
                c.updated_at,
                c.status,
                CASE WHEN l.id IS NOT NULL THEN 1 ELSE 0 END AS is_liked
            FROM comment c
            INNER JOIN users u
                ON c.user_id = u.id
            LEFT JOIN likes l
                ON l.target_type = 'comment'
               AND l.target_id = c.id
               AND l.user_id = ?
            WHERE c.board_id = ?
            ORDER BY c.created_at
        ";

        $result = MySQL::query($query, [$userId ?? 0, $boardId]);

        $val = [];
        foreach ($result as $row) {
            $val[] = CommentDto::from($row);
        }

        return $val;
    }


    public function save(int $boardId, int $userId, string $content, ?int $parentId = null): bool
    {
        $query =
            "INSERT INTO comment(board_id, user_id, content, parent_id, status)
            VALUES (?,?,?,?,?)
        ";



        MySQL::query($query, [$boardId, $userId, $content, $parentId, true]);
        MySQL::query('UPDATE board SET comment_count = comment_count + 1 WHERE id = ?', [$boardId]);
        return true;
    }
    public function unsetComment(int $id): bool
    {

        $row = MySQL::query(
            'SELECT board_id, status 
            FROM comment 
            WHERE id = ? 
            FOR UPDATE',
            [$id]
        )[0] ?? null;

        if (!$row || !(bool) $row['status'])
            return false;

        MySQL::query(
            'UPDATE comment
            SET status = 0 
            WHERE id = ?',
            [$id]
        );
        return true;
    }
}


?>
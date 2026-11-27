<?php

namespace src\like\repository;
use InvalidArgumentException;
use src\common\MySQL;
use src\comment\dto\CommentDto;

class LikeRepository
{

    // 좋아요 전체 조회
    public function addLike(int $userId, string $targetType, int $targetId)
    {
        // 실제 존재하는 게시글/댓글 검증
        $exists = MySQL::query(
            "SELECT id 
            FROM {$targetType} 
            WHERE id = ? 
            LIMIT 1",
            [$targetId]
        );
        if (empty($exists)) {
            throw new InvalidArgumentException('존재하지 않는 대상입니다.');
        }

        // 좋아요 기록 추가 
        $result = MySQL::query(
            "INSERT IGNORE INTO likes (user_id, target_type, target_id) VALUES (?, ?, ?)",
            [$userId, $targetType, $targetId]
        );

        if ($result === 0) {
            return false;
        }

        MySQL::query(
            "UPDATE {$targetType} SET like_count = like_count + 1 
            WHERE id = ?",
            [$targetId]
        );
        return true;
    }

    public function removeLike(int $userId, string $targetType, int $targetId)
    {
        $rows = MySQL::query(
            "SELECT id 
            FROM likes 
            WHERE user_id = ? AND target_type = ? AND target_id = ? 
            LIMIT 1",
            [$userId, $targetType, $targetId]
        );


        // 좋아요 한 기록이 없다면
        if (empty($rows))
            return false;

        MySQL::query(
            "DELETE FROM likes 
            WHERE id = ?",
            [$rows[0]['id']]
        );
        MySQL::query(
            "UPDATE {$targetType} SET like_count = GREATEST(like_count - 1, 0) 
            WHERE id = ?",
            [$targetId]
        );
        return true;


    }
}


?>
<?php


namespace src\admin\repository;

use src\common\MySQL;



class AdminRepository
{

    public function __construct()
    {
    }


    // 게시판 생성
    public function create(string $title, string $colorCode, ?string $password = null): bool
    {

        $hashedPassword = empty($password) ? null : password_hash($password, PASSWORD_DEFAULT);
        $isSecret = empty($password) ? 0 : 1;

        return MySQL::query(
            "INSERT INTO community (title, password, color_code, is_secret) 
            VALUES (?, ?, ?, ?)",
            [$title, $hashedPassword, $colorCode, $isSecret]
        );

    }

    public function delete(int $id)
    {
        return MySQL::query(
            "DELETE FROM community
            WHERE id = ?",
            [$id]
        );
    }

    // 게시판 조회
    public function getAll(): array
    {
        return MySQL::query(
            "SELECT 
                c.id,
                c.title,
                c.color_code,
                c.created_at,
                COUNT(b.id) as board_count,
                c.is_secret
            FROM community c
            LEFT JOIN board b ON b.community_id = c.id
            GROUP BY c.id
            "
        );
    }
    // 신고글 조회
    // 신고글 수정
}



?>
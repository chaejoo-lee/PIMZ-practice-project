<?php

namespace src\excel\repository;

use src\common\MySQL;
use src\excel\dto\ExcelCommentDto;
use src\excel\dto\ExcelBoardDto;

class ExcelRepository
{
    public function __construct()
    {
    }

    // 필요한 자료 가져오기
    /**
     * Summary of getBoards
     * @param string $startDate
     * @param string $endDate
     * @return ExcelBoardDto[]
     */
    public function getBoards(string $startDate, string $endDate): array
    {

        $query =
            "SELECT b.id, u.email, b.title, b.content, bi.path, b.created_at, b.updated_at
            FROM board b
            LEFT JOIN board_images bi ON b.id = bi.board_id
            INNER JOIN users u on b.user_id = u.id
            WHERE b.created_at BETWEEN ? AND ?
            ";


        $queryResult = MySQl::query($query, [$startDate, $endDate]);


        $val = [];
        foreach ($queryResult as $qr) {
            $val[] = ExcelBoardDto::from($qr);
        }

        return $val;
    }



    /**
     * @param array $boards
     * @return ExcelCommentDto[];
     */
    public function getComments(array $boards): array
    {
        // 방어로직
        if (empty($boards)) {
            error_log("board id가 비어있습니다.");
            return [];
        }
        // $boardIds가 여러개면 "?,?" 문자열 생성
        $placeholders = implode(',', array_fill(0, count($boards), '?'));

        $query =
            "SELECT c.id, c.board_id, u.email, c.parent_id ,c.content, c.created_at, c.updated_at
            FROM comment c
            INNER JOIN users u ON c.user_id = u.id
            WHERE c.board_id IN ($placeholders)
            ORDER BY c.id ASC
        ";


        $queryResult = MySQl::query($query, $boards);


        $val = [];
        foreach ($queryResult as $qr) {
            $val[] = ExcelCommentDto::from($qr);
        }


        return $val;
    }
}
?>
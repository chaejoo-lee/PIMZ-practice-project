<?php

namespace src\board\repository;

use src\common\MySQL;

class BoardImageRepository
{
    public function findByBoardId(int $boardId): array
    {
        return MySQL::query(
            "SELECT path, sort_order
            FROM board_images
            WHERE board_id = ?
            ORDER BY sort_order ASC",
            [$boardId]
        );
    }

    public function saveAll(int $boardId, array $paths): array
    {
        $results = [];
        foreach ($paths as $index => $path) {
            $results[] = MySQL::query(
                "INSERT INTO board_images (board_id, path, sort_order)
                VALUES (?, ?, ?)",
                [$boardId, $path, $index]
            );
        }

        return $results;
    }

    public function deleteByBoardId(int $boardId): mixed
    {
        return MySQL::query(
            "DELETE FROM board_images WHERE board_id = ?",
            [$boardId]
        );
    }
}
<?php

namespace src\common;

use mysqli;
class MySQL
{
    // 싱글톤
    private static ?mysqli $connection = null;

    private static function getConnection(): mysqli
    {
        if (self::$connection === null) {

            // 예외 로그 발생
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            error_log("host : " . $_ENV['DB_HOST']);
            $mysqli = mysqli_connect(
                $_ENV['DB_HOST'],
                $_ENV['DB_USERNAME'],
                $_ENV['DB_PASSWORD'],
                $_ENV['DB_NAME'],
                (int) $_ENV['DB_PORT']
            );

            self::$connection = $mysqli;
        }

        return self::$connection;
    }

    public static function query(string $sql, array $params = []): array|int|string
    {

        $mysqli = self::getConnection();


        $queryResult = ($params === [])
            ? mysqli_query($mysqli, $sql)
            : mysqli_execute_query($mysqli, $sql, $params);


        error_log("query result : " . json_encode($queryResult, JSON_UNESCAPED_UNICODE));


        // 결과값이 있음
        if ($queryResult instanceof \mysqli_result) {
            $result = [];
            while ($row = mysqli_fetch_assoc($queryResult)) {
                $result[] = $row;
            }
            return $result;
        }

        // 생성,삭제,수정 성공시 true 반환
        return self::getConnection()->insert_id;
    }
}

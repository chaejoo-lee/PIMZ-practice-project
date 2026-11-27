<?php

namespace src\common;

use RuntimeException;

class FileHandler
{
    //https://www.php.net/manual/en/features.file-upload.multiple.php
    //https://www.php.net/manual/en/features.file-upload.php
    private static function reArrayFiles(array $filePost): array
    {
        if (!isset($filePost['name'])) {
            return [];
        }

        // 단건일경우
        if (!is_array($filePost['name'])) {
            if ($filePost['error'] === UPLOAD_ERR_NO_FILE) {
                return [];
            }
            return [$filePost];
        }

        $arrFiles = [];
        $fileCount = count($filePost['name']);
        $fileKeys = array_keys($filePost);

        for ($i = 0; $i < $fileCount; $i++) {
            if ($filePost['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            foreach ($fileKeys as $key) {
                $arrFiles[$i][$key] = $filePost[$key][$i];
            }
        }

        return array_values($arrFiles);
    }

    public static function getKey(string $key, array $filePost): array
    {
        $result = [];
        foreach (self::reArrayFiles($filePost) as $file) {
            $result[] = self::upload($key, $file);
        }

        error_log("get key [{$key}] : " . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $result;
    }
    private static function upload(string $key, array $file): string
    {
        if (
            !isset($file['error']) ||
            is_array($file['error'])
        ) {
            throw new RuntimeException('Invalid parameters.');
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new RuntimeException('No file sent.');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new RuntimeException('Exceeded filesize limit.');
            default:
                throw new RuntimeException('Unknown errors.');
        }

        if ($file['size'] > 1000000) {
            throw new RuntimeException('Exceeded filesize limit.');
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $randomName = bin2hex(random_bytes(6));
        $newFilename = $extension ? "{$randomName}.{$extension}" : $randomName;

        $uploadDir = "uploads/{$key}/";

        // 저장할 하위 디렉토리가 없으면 자동 생성
        // 권한 있을지?
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destination = $uploadDir . $newFilename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException('Failed to move uploaded file.');
        }

        return $destination;
    }

    // 파일 삭제 기능
    public static function delete(array $path): void
    {
        foreach ($path as $file) {
            if (file_exists($file) && is_file($file)) {
                unlink($file);
                $dirPath = dirname($file);
                //폴더가 비어있으면 지우기
                if (count(scandir($dirPath)) === 2) {
                    rmdir($dirPath);
                }
            }
        }
    }
}

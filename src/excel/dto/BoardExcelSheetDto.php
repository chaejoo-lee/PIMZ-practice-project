<?php

namespace src\excel\dto;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use src\excel\dto\ExcelBoardDto;
use src\excel\dto\ExcelCommentDto;

class BoardExcelSheetDto
{
    private array $rows = [];
    private string $fileName;

    public function __construct(string $name = 'report')
    {
        $this->fileName = $name . date('Y-m-d') . '.xlsx';
        $this->rows[] = ['구분', '작성자', '제목', '내용', '작성일', '수정일'];
    }

    public function addFromBoard(ExcelBoardDto $boardDto): self
    {
        $this->rows[] = [
            '게시글',
            $boardDto->email,
            $boardDto->title,
            $boardDto->content,
            $boardDto->createdAt,
            $boardDto->updatedAt ?? '',
        ];

        return $this;
    }

    public function addFromComment(ExcelCommentDto $commentDto): self
    {
        $this->rows[] = [
            $commentDto->parentId === null ? '댓글' : '답글',
            $commentDto->email,
            '',
            $commentDto->content,
            $commentDto->createdAt,
            $commentDto->updatedAt ?? '',
        ];

        return $this;
    }

    public function toSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 데이터 채우기
        $sheet->fromArray($this->rows, null, 'A1');

        // 자동 너비 설정
        $columnCount = count($this->rows[0] ?? []);
        for ($colIndex = 1; $colIndex <= $columnCount; $colIndex++) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }
}
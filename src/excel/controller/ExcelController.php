<?php

namespace src\excel\controller;


use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use src\auth\dto\UserInfoDto;
use src\excel\dto\BoardExcelSheetDto;
use src\excel\repository\ExcelRepository;


// https://teserre.tistory.com/19
class ExcelController
{
    public function __construct(
        private readonly ExcelRepository $excelRepository = new ExcelRepository()
    ) {
    }

    // 파일 다운로드
    public function boardAllDownload(UserInfoDto $currentUser): void
    {
        $startDate = $_GET['startDate'];
        $endDate = $_GET['endDate'];

        if ($currentUser->role !== 'admin') {
            throw new InvalidArgumentException('접근 권한이 없습니다.', 403);
        }


        $boards = $this->excelRepository->getBoards(
            startDate: $startDate,
            endDate: $endDate
        );

        if (empty($boards)) {
            throw new InvalidArgumentException("게시글이 비어있습니다.", 404);
        }

        $boardIds = [];
        foreach ($boards as $board) {
            $boardIds[] = $board->id;
        }

        $comments = $this->excelRepository->getComments($boardIds);

        // 게시글 ID별로 댓글 묶기 리스트로 달림
        $commentsByBoardId = [];
        foreach ($comments as $comment) {
            $commentsByBoardId[$comment->board_id][] = $comment;
        }

        // error_log(print_r($commentsByBoardId, true));
        // 2행부터 게시글과 댓글을 순차적으로 시트에 입력
        $excelSheet = new BoardExcelSheetDto();
        foreach ($boards as $board) {
            $excelSheet->addFromBoard($board);
            foreach ($commentsByBoardId[$board->id] ?? [] as $comment) {
                $excelSheet->addFromComment($comment);
            }
        }

        $spreadsheet = $excelSheet->toSpreadsheet();

        if (ob_get_length()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . basename($excelSheet->getFileName()) . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

}
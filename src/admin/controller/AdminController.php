<?php

namespace src\admin\controller;

use src\admin\repository\AdminRepository;
use src\auth\dto\UserInfoDto;
use src\common\JsonResponse;
use src\report\repository\ReportRepository;

use InvalidArgumentException;

class AdminController
{
    public function __construct(
        public readonly AdminRepository $adminRepository = new AdminRepository(),
        public readonly ReportRepository $reportRepository = new ReportRepository()
    ) {
    }


    // 커뮤니티 생성
    public function post(UserInfoDto $userInfoDto)
    {
        $title = $_POST['title'];
        $colorType = $_POST['color_type'];
        $password = $_POST['password'] ?? null;
        $this->adminRepository->create($title, $colorType, $password);

        JsonResponse::send(
            status: 200,
            message: '게시글 생성',
            body: [
                'title' => $title,
                'color' => $colorType
            ]
        );
    }

    // 커뮤니티 조회
    public function getAllCommunity()
    {
        $result = $this->adminRepository->getAll();
        $communityList = [];
        foreach ($result as $row) {
            $communityList[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'colorCode' => $row['color_code'],
                'created_at' => $row['created_at'],
                'board_count' => (int) $row['board_count'],
                'is_secret' => $row['is_secret'] === '1' ? true : false,
            ];
        }

        JsonResponse::send(
            status: 200,
            message: '조회 성공',
            body: $communityList
        );
    }
    // 신고된 게시글 목록 및 총 신고 횟수 조회
    public function getReports()
    {
        $minCount = (int) $_GET['minCount'] ?? 0;
        $result = $this->reportRepository->getAll($minCount);
        $reportList = [];

        foreach ($result as $row) {
            $reportList[] = [
                'id' => (int) $row['id'],
                'userId' => (int) $row['user_id'],
                'authorEmail' => $row['author_email'],
                'communityId' => (int) $row['community_id'],
                'communityTitle' => $row['community_title'],
                'title' => $row['title'],
                'content' => $row['content'],
                'updatedAt' => $row['updated_at'],
                'reportCount' => (int) $row['report_count'],
                'lastReportedAt' => $row['last_reported_at'],
            ];
        }
        JsonResponse::send(
            status: 200,
            message: '신고 목록 조회 성공',
            body: $reportList
        );
    }

    // 신고 처리
    public function deleteReport()
    {
        $boardId = (int) $_GET['boardId'] ?? 0;
        $action = $_GET['action'] ?? 'reject';

        if ($boardId <= 0) {
            throw new InvalidArgumentException('유효하지 않은 게시글 ID입니다.', 400);
        }

        if ($action === 'approve') {
            $deleted = $this->reportRepository->deleteReportedBoard($boardId);
            $message = '신고된 게시글이 삭제되었습니다.';
        } else {
            $deleted = $this->reportRepository->deleteReportsByBoardId($boardId);
            $message = '신고 내역이 반려 되었습니다.';
        }

        if (!$deleted) {
            throw new InvalidArgumentException('처리할 신고 대상이 존재하지 않습니다.', 404);
        }

        JsonResponse::send(
            status: 200,
            message: $message,
            body: ['boardId' => $boardId]
        );
    }

    public function deleteCommunity()
    {
        $boardId = (int) $_GET['boardId'] ?? 0;

        if ($boardId <= 0) {
            throw new InvalidArgumentException('유효하지 않은 게시글 ID입니다.', 400);
        }

        $this->adminRepository->delete($boardId);

        JsonResponse::send(
            status: 200,
            message: "삭제 완료"
        );
    }
}

?>
<?php

namespace src\board\controller;

use InvalidArgumentException;

use src\common\JsonResponse;
use src\auth\dto\UserInfoDto;
use src\board\repository\BoardImageRepository;
use src\board\repository\BoardRepository;
use src\board\repository\CommunityRepository;
use src\comment\repository\CommentRepository;
use src\common\FileHandler;

class BoardController
{

    public function __construct(
        private readonly BoardRepository $boardRepository = new BoardRepository(),
        private readonly BoardImageRepository $boardImageRepository = new BoardImageRepository(),
        private readonly CommentRepository $commentRepository = new CommentRepository(),
        private readonly CommunityRepository $communityRepository = new CommunityRepository(),
    ) {
    }



    public function communityPassword(UserInfoDto $currentUser): void
    {
        $communityId = (int) ($_GET['communityId'] ?? 0);
        $password = $_POST['password'] ?? null;

        if ($communityId <= 0) {
            throw new InvalidArgumentException('유효한 communityId를 입력해주세요.', 400);
        }

        $this->communityRepository->verifyPassword(
            communityId: $communityId,
            password: $password,
            userInfoDto: $currentUser,
        );

        JsonResponse::send(
            status: 200,
            message: "커뮤니티 인증 성공",
            body: [
                'communityId' => $communityId,
                'verified' => true,
            ]
        );
    }



    /**
     * GET 요청 처리: 
     */
    public function getAll(?UserInfoDto $userInfoDto): void
    {

        $communityId = (int) ($_GET['communityId'] ?? 0);
        $rawSearch = $_GET['search'] ?? '';
        $search = is_string($rawSearch) ? trim($rawSearch) : '';

        $boards = $this->boardRepository->findAll(
            communityId: $communityId,
            communityPassword: $this->getCommunityPasswordHeader(),
            userInfoDto: $userInfoDto,
            search: $search,
        );

        $community = $this->communityRepository->findAll();

        $res = [
            'user' => [
                'id' => $userInfoDto->id,
                'email' => $userInfoDto->email,
                'role' => $userInfoDto->role,
            ],
            'boards' => $boards,
            'community' => $community,
        ];

        JsonResponse::send(
            status: 200,
            message: "게시글 조회 완료",
            body: $res
        );
    }


    /**
     * GET 요청 처리: 
     */
    public function getOne(?UserInfoDto $userInfoDto): void
    {

        $boardId = (int) ($_GET['id'] ?? 0);
        if ($boardId <= 0) {
            throw new InvalidArgumentException('유효한 게시글 ID를 입력해주세요.', 400);
        }

        $board = $this->boardRepository->findOneById(
            id: $boardId,
            communityPassword: $this->getCommunityPasswordHeader(),
            userInfoDto: $userInfoDto,
        );
        $userId = empty($userInfoDto) ? null : $userInfoDto->id;

        $comments = $this->getComment($boardId, $userId);

        $community = $this->communityRepository->findAll();
        $res = [
            'user' => [
                'id' => $userInfoDto->id,
                'email' => $userInfoDto->email,
                'role' => $userInfoDto->role,
            ],
            'community' => $community,
            'board' => $board,
            'comments' => $comments,
        ];

        JsonResponse::send(
            status: 200,
            message: "게시글 조회 완료",
            body: $res
        );
    }


    /**
     * PATCH 요청 사진 순서 변경, 중간 삽입, 부분/전체 삭제
     */
    public function patch(UserInfoDto $userInfoDto): void
    {
        if (empty($userInfoDto->id)) {
            throw new InvalidArgumentException("세션이 존재하지 않습니다.", 401);
        }

        $boardId = (int) ($_GET['boardId'] ?? 0);
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';

        if ($boardId <= 0) {
            throw new InvalidArgumentException("게시글 ID가 올바르지 않습니다.", 400);
        }

        if ($title === '' || $content === '') {
            throw new InvalidArgumentException("제목과 본문은 필수입니다.", 400);
        }

        $boardEntity = $this->boardRepository->findOneById(
            id: $boardId,
            communityPassword: $this->getCommunityPasswordHeader(),
            userInfoDto: $userInfoDto,
        );

        // 어드민이 아니면서 작성자 ID가 다를경우
        if ($userInfoDto->role !== 'admin' && (int) $boardEntity->userId !== (int) $userInfoDto->id) {
            throw new InvalidArgumentException("수정 권한이 없습니다.", 403);
        }

        $this->syncBoardImages($boardEntity);

        $this->boardRepository->update(
            boardId: $boardId,
            title: $title,
            content: $content,
        );

        JsonResponse::send(
            status: 200,
            message: "게시글 수정 완료",
        );
    }

    /**
     * 게시글 이미지 순서 변경, 중간 삽입, 부분/전체 삭제
     */
    private function syncBoardImages(object $boardEntity): void
    {
        $existingImages = $boardEntity->images ?? [];
        $orders = is_array($_POST['order'] ?? null) ? $_POST['order'] : [];
        $files = $_FILES['file'] ?? null;
        $clearImages = filter_var($_POST['clear_images'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (empty($orders) && empty($files['name']) && !$clearImages) {
            return;
        }

        $uploadedByIndex = $this->uploadIndexedFiles($boardEntity->id, $files);
        $finalPaths = $this->mergeImagePaths($orders, $uploadedByIndex, $existingImages);

        // 기존 이미지 중 빠진 파일만 삭제
        $removedImages = array_diff($existingImages, $finalPaths);
        if (!empty($removedImages)) {
            FileHandler::delete(array_values($removedImages));
        }

        // DB 이미지 목록 갱신
        $this->boardImageRepository->deleteByBoardId($boardEntity->id);
        if (!empty($finalPaths)) {
            $this->boardImageRepository->saveAll($boardEntity->id, $finalPaths);
        }
    }

    /**
     * 새파일들을 업로드하고 반환
     */
    private function uploadIndexedFiles(int $boardId, ?array $files): array
    {
        if (empty($files['name']) || !is_array($files['name'])) {
            return [];
        }

        $uploadedByIndex = [];
        foreach ($files['name'] as $idx => $name) {
            if (($files['error'][$idx] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }

            $singleFile = [
                'name' => [$files['name'][$idx]],
                'type' => [$files['type'][$idx]],
                'tmp_name' => [$files['tmp_name'][$idx]],
                'error' => [$files['error'][$idx]],
                'size' => [$files['size'][$idx]],
            ];

            $saved = (array) FileHandler::getKey($boardId, $singleFile);
            if (!empty($saved[0])) {
                $uploadedByIndex[(int) $idx] = $saved[0];
            }
        }

        return $uploadedByIndex;
    }

    /**
     * 기존 경로와 새 업로드 경로를 인덱스 오름차순으로 병합
     */
    private function mergeImagePaths(array $orders, array $uploadedByIndex, array $existingImages): array
    {
        $merged = [];
        foreach ($orders as $idx => $path) {
            if (in_array($path, $existingImages, true)) {
                $merged[(int) $idx] = $path;
            }
        }

        foreach ($uploadedByIndex as $idx => $path) {
            $merged[$idx] = $path;
        }

        ksort($merged);
        return array_values($merged);
    }
    /**
     * POST 요청 처리: 
     */
    public function post(UserInfoDto $userInfoDto): void
    {
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $images = $_FILES['images'] ?? [];
        $communityId = (int) ($_POST['community_id'] ?? 0);



        if (empty($userInfoDto->id)) {
            throw new InvalidArgumentException("접근 권한이 없습니다.", 401);
        }

        if ($communityId <= 0 || $title === '' || $content === '') {
            throw new InvalidArgumentException('community_id, title, content는 필수입니다.', 400);
        }

        $this->communityRepository->verifyPassword(
            communityId: $communityId,
            password: $this->getCommunityPasswordHeader(),
            userInfoDto: $userInfoDto,
        );

        (int) $createdBoardId = $this->boardRepository->save(
            userId: $userInfoDto->id,
            title: $title,
            content: $content,
            communityId: $communityId,
        );

        // 이미지 업로드
        if (!empty($images)) {
            (array) $uploadPath = FileHandler::getKey($createdBoardId, $images);
            $this->boardImageRepository->saveAll($createdBoardId, $uploadPath);
        }

        JsonResponse::send(
            status: 201,
            message: "게시글 생성 완료",
            body: ['board_id' => $createdBoardId]
        );
    }

    /**
     * DELETE 요청 처리: 
     */
    public function delete(UserInfoDto $userInfoDto, int $boardId): void
    {
        if (empty($userInfoDto->id)) {
            throw new InvalidArgumentException("접근 권한이 없습니다.", 401);
        }

        $boardEntity = $this->boardRepository->findOneById(
            id: $boardId,
            communityPassword: $this->getCommunityPasswordHeader(),
            userInfoDto: $userInfoDto,
        );

        if ($userInfoDto->role !== "admin" && (int) $boardEntity->userId !== (int) $userInfoDto->id) {
            throw new InvalidArgumentException("삭제 권한이 없습니다.", 403);
        }

        $this->boardRepository->delete($userInfoDto->id, $boardId);
        FileHandler::delete($boardEntity->images);



        JsonResponse::send(
            status: 200,
            message: "게시글 삭제 완료"
        );
    }

    /**
     * GET 요청 처리: 
     * 모든 댓글 요청
     */
    private function getComment(int $boardId, ?int $userId): array
    {
        // 부모 댓글 먼저 그다음 id순으로 정렬
        $result = $this->commentRepository->findAllByBoardId($boardId, $userId);

        $temp = [];
        $comments = [];
        foreach ($result as $r) {
            if (empty($r->parentId)) {
                $r->replies = [];
                $temp[$r->id] = $r; // 직접 키값을 입력하는 순간 객체가 되는건지?
                $comments[] = $r;
            }
        }

        // 부모-자식 연결
        foreach ($result as $r) {
            if (!empty($r->parentId) && isset($temp[$r->parentId])) {
                $temp[$r->parentId]->replies[] = $r;
            }
        }
        return $comments;
    }



    private function getCommunityPasswordHeader(): ?string
    {
        $password = $_SERVER['HTTP_COMUNITY_PASSWORD']
            ?? $_SERVER['HTTP_COMMUNITY_PASSWORD']
            ?? null;

        if ($password !== null) {
            return trim((string) $password);
        }

        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                if (in_array(strtolower($name), ['comunity-password', 'community-password'], true)) {
                    return trim((string) $value);
                }
            }
        }

        return null;
    }


}

<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/common/Env.php';

use src\admin\controller\AdminController;
use src\auth\controller\AuthController;
use src\board\controller\BoardController;
use src\comment\controller\CommentController;
use src\excel\controller\ExcelController;
use src\common\{Session, JsonResponse};
use src\like\controller\LikeController;
use src\report\controller\ReportController;

header("Access-Control-Allow-Origin: *"); // CORS 
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Community-Password, Comunity-Password");
header("Access-Control-Expose-Headers: Authorization");

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);


$method = $_SERVER['REQUEST_METHOD'];

// 브라우저의 CORS 요청 즉시 허용 및 종료
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}


// post 제외 다른 http 메서드 적용
if ($method === 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}


$authHeader = $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

// 토큰값 추출
$token = preg_replace('/^Bearer\s+/i', '', trim($authHeader));
$session = new Session();

// 유저 정보 저장
$currentUser = null;

// 로그
error_log("\n=========================================");
error_log("[REQUEST] {$method} {$uri}");
error_log("[QUERY] " . json_encode($_GET, JSON_UNESCAPED_UNICODE));
error_log("[POST / FormData] " . json_encode($_POST, JSON_UNESCAPED_UNICODE));
error_log("\n=========================================");



$baseUrl = "/index.php";



// 로그인 API
if ($uri === $baseUrl . "/signin") {
    $controller = new AuthController();

    // 세션을 통한 유저 정보 발급
    if ($method === 'POST') {
        $controller->login();
    } else {
        not_found();
    }
    exit;
}

// 회원가입 API
if ($uri === $baseUrl . '/signup') {
    $controller = new AuthController();

    if ($method === 'POST') {
        $controller->signup();
    } else {
        not_found();
    }
    exit;
}


// 현재 로그인 유저 조회 API
if ($uri === $baseUrl . '/me') {
    $currentUser = $session->getUserBySession($token);

    if ($method === 'GET') {
        JsonResponse::send(200, '현재 사용자', $currentUser);
    } else {
        not_found();
    }
    exit;
}

// 전체 게시판 조회 API
if ($uri === $baseUrl . '/boards') {
    $currentUser = $session->getUserBySession($token);

    $controller = new BoardController();

    if ($method === 'GET') {
        $controller->getAll($currentUser);
    } else {
        not_found();
    }
    exit;
}

if ($uri === $baseUrl . '/community/password') {
    $currentUser = $session->getUserBySession($token);

    $controller = new BoardController();

    if ($method === 'POST') {
        $controller->communityPassword($currentUser);
    } else {
        not_found();
    }
    exit;
}

// 게시판 CRUD API
if ($uri === $baseUrl . '/board') {
    $currentUser = $session->getUserBySession($token);
    $controller = new BoardController();

    if ($method === 'GET') {
        $controller->getOne($currentUser);

    } elseif ($method === 'POST') {
        $controller->post($currentUser);

    } elseif ($method === 'PATCH') {
        $controller->patch($currentUser);

    } elseif ($method === 'DELETE') {
        $boardId = (int) $_GET['id'];
        $controller->delete($currentUser, $boardId);

    } else {
        not_found();
    }
    exit;
}


// 댓글 CRUD
if ($uri === $baseUrl . '/board/comment') {
    $currentUser = $session->getUserBySession($token);

    $controller = new CommentController();
    $boardId = (int) $_GET['boardId'];

    if ($method === 'POST') {
        $commentId = (int) $_GET['commentId'];
        $content = $_POST["comment"];
        $controller->post($currentUser, $boardId, $content, $commentId);

    } elseif ($method === 'DELETE') {
        $commentId = (int) $_GET['commentId'];
        $controller->delete($currentUser, $commentId);

    } else {
        not_found();
    }
    exit;
}

// 다운로드 CRUD
if ($uri === $baseUrl . '/board/download') {
    // error_log("cookie " . $_COOKIE['SESSION_ID']);
    $currentUser = $session->getUserBySession($token);

    if (empty($currentUser)) {
        throw new InvalidArgumentException('관리자 권한이 필요합니다', 403);
    }

    $controller = new ExcelController();

    if ($method === 'GET') {
        $controller->boardAllDownload(currentUser: $currentUser, );
    } else {
        not_found();
    }
    exit;
}

// 좋아요
if ($uri === $baseUrl . '/like') {
    $currentUser = $session->getUserBySession($token);
    $controller = new LikeController();
    if ($method === 'POST') {
        $controller->post($currentUser);
    } elseif ($method === 'DELETE') {
        $controller->delete($currentUser);
    } else {
        not_found();
    }
    exit;
}


// 게시글 리포트
if ($uri === $baseUrl . '/board/report') {
    $currentUser = $session->getUserBySession($token);
    $controller = new ReportController();
    $boardId = (int) $_GET['boardId'];

    if ($method === 'POST') {
        $controller->post($currentUser, $boardId);
    } else {
        not_found();
    }

}

// 관리자
if (str_starts_with($uri, $baseUrl . '/admin/')) {
    $currentUser = $session->getUserBySession($token);
    if ($currentUser === null || $currentUser->role !== 'admin') {
        throw new Exception('관리자만 접근 가능합니다.', 403);
    }

    $parts = explode('/', trim($uri, '/')); // split()과 동일
    $controller = new AdminController();
    if ($parts[2] === 'community') {
        if ($method === 'GET') {
            $controller->getAllCommunity();
        } elseif ($method === 'POST') {
            $controller->post($currentUser);
        } elseif ($method === 'DELETE') {
            $controller->deleteCommunity($currentUser);
        } else {
            not_found();
        }
    } elseif ($parts[2] === 'report') {
        if ($method === 'GET') {
            $controller->getReports();
        } elseif ($method === 'PATCH') {
            $controller->deleteReport();
        } else {
            not_found();
        }
    }

}

function not_found()
{
    http_response_code(405);
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode(['message' => '허용되지 않는 요청 메서드입니다.'], JSON_UNESCAPED_UNICODE);
}
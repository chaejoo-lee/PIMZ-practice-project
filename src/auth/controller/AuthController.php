<?php

namespace src\auth\controller;

use InvalidArgumentException;
use src\auth\dto\CreateUserDto;
use src\common\JsonResponse;
use src\auth\repository\UserRepository;
use src\common\Session;

class AuthController
{

    public function __construct(
        private readonly UserRepository $userRepository = new UserRepository(),
        private readonly Session $session = new Session(),
    ) {
    }

    public function login(): void
    {
        $request = !empty($_POST) ? $_POST : (json_decode(file_get_contents('php://input'), true) ?? []);

        if (empty($request['email']) || empty($request['password'])) {
            throw new InvalidArgumentException('요청 값이 잘못되었습니다. (email, password 필수)', 400);
        }

        $user = $this->userRepository->findByEmailAndPassword(
            email: $request['email'],
            password: $request['password']
        );


        if (!$user) {
            throw new InvalidArgumentException("회원 정보가 없습니다.", 404);
        }

        // 세션 생성
        $session = bin2hex(random_bytes(32)); // 16진수 랜덤 문자열 반환

        // 세션 저장
        error_log("user id : ", $user->id, "session id : ", $session);
        $this->session->create($user->id, $session);

        JsonResponse::send(
            201,
            '로그인이 완료되었습니다.',
            $session
        );
    }

    public function signup(): void
    {
        $input = !empty($_POST) ? $_POST : (json_decode(file_get_contents('php://input'), true) ?? []);

        if (empty($input['email']) || empty($input['password'])) {
            JsonResponse::send(
                400,
                '요청 값이 잘못되었습니다. (email, password 필수)'
            );
            return;
        }

        $dto = new CreateUserDto(
            email: $input['email'],
            password: $input['password']
        );


        // 유저 생성
        $res = $this->userRepository->create($dto);


        JsonResponse::send(
            201,
            '회원가입이 완료되었습니다.',
            $res
        );
    }
}
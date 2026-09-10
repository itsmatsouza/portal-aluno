<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Controllers;

use Leilabrito\PortalAluno\Core\Request;
use Leilabrito\PortalAluno\Core\Response;
use Leilabrito\PortalAluno\Services\PasswordResetService;
use Throwable;

class PasswordResetController
{
    public function __construct(
        private PasswordResetService $passwordReset
    ) {
    }

    public function forgot(): never
    {
        $request = new Request();

        $email = (string) $request->input(
            'email',
            ''
        );

        try {
            $this->passwordReset->requestReset(
                $email
            );
        } catch (Throwable $e) {
            /*
             * Não revelamos detalhes do processo
             * nem se o e-mail existe.
             */
        }

        Response::json([
            'success' => true,
            'message' =>
                'Se o e-mail estiver cadastrado, '
                . 'você receberá as instruções para '
                . 'redefinir sua senha.'
        ]);
    }

    public function reset(): never
    {
        $request = new Request();

        $token = trim((string) $request->input(
            'token',
            ''
        ));

        $password = (string) $request->input(
            'password',
            ''
        );

        try {
            $this->passwordReset->resetPassword(
                $token,
                $password
            );

            Response::json([
                'success' => true,
                'message' =>
                    'Senha redefinida com sucesso.'
            ]);

        } catch (Throwable $e) {
            Response::json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}

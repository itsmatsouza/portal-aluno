<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Services;

use Leilabrito\PortalAluno\Models\User;
use Leilabrito\PortalAluno\Repositories\AuthTokenRepository;
use Leilabrito\PortalAluno\Repositories\UserRepository;
use DateTimeImmutable;
use RuntimeException;

class PasswordResetService
{
    private const TOKEN_TYPE = 'PASSWORD_RESET';
    private const TOKEN_EXPIRATION_MINUTES = 60;

    public function __construct(
        private UserRepository $users,
        private AuthTokenRepository $tokens,
        private MailService $mail
    ) {
    }

    public function requestReset(string $email): void
    {
        $email = trim(strtolower($email));

        if ($email === '') {
            return;
        }

        $user = $this->users->findByEmail($email);

        /*
         * Não informamos ao solicitante se o e-mail
         * existe ou não.
         */
        if ($user === null || !$user->canLogin()) {
            return;
        }

        $this->tokens->invalidateActiveTokens(
            $user->getId(),
            self::TOKEN_TYPE
        );

        $plainToken = bin2hex(random_bytes(32));

        $tokenHash = hash(
            'sha256',
            $plainToken
        );

        $expiresAt = new DateTimeImmutable(
            '+' . self::TOKEN_EXPIRATION_MINUTES . ' minutes'
        );

        $this->tokens->create(
            $user->getId(),
            $tokenHash,
            self::TOKEN_TYPE,
            $expiresAt
        );

        $resetUrl = $this->buildResetUrl(
            $plainToken
        );

        $html = $this->buildEmail(
            $user,
            $resetUrl
        );

        $this->mail->send(
            $user->getEmail(),
            'Redefinição de senha — Portal do Aluno',
            $html,
            $this->buildTextEmail($resetUrl)
        );
    }

    private function buildResetUrl(string $token): string
    {
        $baseUrl = rtrim(
            $_ENV['APP_URL'] ?? 'http://localhost:8000',
            '/'
        );

        return $baseUrl
            . '/password/reset?token='
            . urlencode($token);
    }

    private function buildEmail(
        User $user,
        string $resetUrl
    ): string {
        $name = htmlspecialchars(
            $user->getName(),
            ENT_QUOTES,
            'UTF-8'
        );

        $url = htmlspecialchars(
            $resetUrl,
            ENT_QUOTES,
            'UTF-8'
        );

        return "
            <!DOCTYPE html>
            <html lang=\"pt-BR\">
            <head>
                <meta charset=\"UTF-8\">
                <title>Redefinição de senha</title>
            </head>
            <body>
                <p>Olá, {$name}.</p>

                <p>
                    Recebemos uma solicitação para
                    redefinir a senha do seu acesso
                    ao Portal do Aluno.
                </p>

                <p>
                    <a href=\"{$url}\">
                        Redefinir minha senha
                    </a>
                </p>

                <p>
                    Este link é válido por
                    " . self::TOKEN_EXPIRATION_MINUTES . "
                    minutos e pode ser utilizado
                    apenas uma vez.
                </p>

                <p>
                    Se você não solicitou a redefinição
                    da senha, ignore este e-mail.
                </p>

                <p>
                    Leila Brito
                </p>
            </body>
            </html>
        ";
    }

    private function buildTextEmail(
        string $resetUrl
    ): string {
        return
            "Redefinição de senha - Portal do Aluno\n\n"
            . "Para redefinir sua senha, acesse:\n"
            . $resetUrl
            . "\n\n"
            . "Este link é válido por "
            . self::TOKEN_EXPIRATION_MINUTES
            . " minutos e pode ser utilizado apenas uma vez.\n\n"
            . "Se você não solicitou a redefinição da senha, "
            . "ignore este e-mail.\n";
    }

    public function validateToken(
        string $plainToken
    ): ?array {
        $plainToken = trim($plainToken);

        if ($plainToken === '') {
            return null;
        }

        $tokenHash = hash(
            'sha256',
            $plainToken
        );

        $token = $this->tokens->findValid(
            $tokenHash,
            self::TOKEN_TYPE
        );

        if ($token === null) {
            return null;
        }

        $user = $this->users->findById(
            (int) $token['user_id']
        );

        if ($user === null || !$user->canLogin()) {
            return null;
        }

        return [
            'token_id' => (int) $token['id'],
            'user' => $user,
            'expires_at' => $token['expires_at'],
        ];
    }

    public function resetPassword(
        string $plainToken,
        string $newPassword
    ): void {
        $result = $this->validateToken(
            $plainToken
        );

        if ($result === null) {
            throw new RuntimeException(
                'Token inválido ou expirado.'
            );
        }

        if (strlen($newPassword) < 8) {
            throw new RuntimeException(
                'A senha deve possuir pelo menos 8 caracteres.'
            );
        }

        $passwordHash = password_hash(
            $newPassword,
            PASSWORD_DEFAULT
        );

        if ($passwordHash === false) {
            throw new RuntimeException(
                'Não foi possível gerar a senha.'
            );
        }

        $this->users->updatePassword(
            $result['user']->getId(),
            $passwordHash
        );

        $this->tokens->markAsUsed(
            $result['token_id']
        );

        /*
        * Qualquer outro token de recuperação
        * existente para o usuário também deixa
        * de ser válido.
        */
        $this->tokens->invalidateActiveTokens(
            $result['user']->getId(),
            self::TOKEN_TYPE
        );
    }
}

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

        $logoUrl = htmlspecialchars(
            rtrim($_ENV['APP_URL'] ?? 'http://localhost:8000', '/')
                . '/assets/images/logo-elo-horizontal.jpg',
            ENT_QUOTES,
            'UTF-8'
        );
        $expirationMinutes = self::TOKEN_EXPIRATION_MINUTES;

        return <<<HTML
            <!DOCTYPE html>
            <html lang="pt-BR">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Redefina sua senha | Portal do Aluno ELO</title>
            </head>
            <body style="margin:0;padding:0;background-color:#ebecf0;color:#18263b;font-family:'Nunito Sans',Arial,sans-serif;-webkit-text-size-adjust:100%;">
                <div style="display:none;max-height:0;overflow:hidden;opacity:0;mso-hide:all;">
                    Defina uma nova senha para o Portal do Aluno ELO. Seu link é válido por {$expirationMinutes} minutos.
                </div>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ebecf0" style="background-color:#ebecf0;">
                    <tr>
                        <td align="center" style="padding:40px 16px;">
                            <!--[if mso]><table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;">
                                <tr>
                                    <td bgcolor="#0a1931" style="padding:32px 28px;background-color:#0a1931;border-radius:24px 24px 0 0;">
                                        <img src="{$logoUrl}" width="220" alt="ELO — Estrategista Legal de Obras" style="display:block;width:220px;max-width:100%;height:auto;border:0;color:#ffffff;font-size:18px;">
                                        <table role="presentation" width="48" cellpadding="0" cellspacing="0" border="0" style="margin-top:28px;">
                                            <tr><td height="2" bgcolor="#8b745a" style="height:2px;font-size:0;line-height:0;background-color:#8b745a;">&nbsp;</td></tr>
                                        </table>
                                        <p style="margin:20px 0 0;color:#94b5de;font-size:11px;line-height:18px;font-weight:700;letter-spacing:2px;">PORTAL DO ALUNO</p>
                                        <p style="margin:8px 0 0;color:#ffffff;font-size:22px;line-height:30px;font-weight:700;">Seu próximo passo começa aqui.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td bgcolor="#ffffff" style="padding:32px 28px;background-color:#ffffff;border-radius:0 0 24px 24px;">
                                        <h1 style="margin:0 0 24px;color:#0a1931;font-size:28px;line-height:36px;font-weight:800;">Redefina sua senha</h1>
                                        <p style="margin:0 0 12px;font-size:16px;line-height:26px;color:#18263b;">Olá, {$name}.</p>
                                        <p style="margin:0 0 28px;font-size:16px;line-height:26px;color:#6c7c90;">Recebemos uma solicitação para redefinir sua senha. Clique no botão abaixo para criar uma nova e voltar ao Portal do Aluno.</p>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td align="center" bgcolor="#0a1931" style="background-color:#0a1931;border-radius:12px;mso-padding-alt:18px 24px;">
                                                    <a href="{$url}" style="display:block;padding:18px 24px;border:1px solid #0a1931;border-radius:12px;color:#ffffff;font-size:16px;line-height:22px;font-weight:700;text-align:center;text-decoration:none;">Redefinir minha senha</a>
                                                </td>
                                            </tr>
                                        </table>
                                        <p style="margin:16px 0 28px;text-align:center;font-size:13px;line-height:21px;color:#6c7c90;">Válido por <strong style="color:#18263b;">{$expirationMinutes} minutos</strong>. Uso único.</p>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td bgcolor="#f4f6f9" style="padding:18px 20px;background-color:#f4f6f9;border-left:3px solid #94b5de;border-radius:0 10px 10px 0;">
                                                    <p style="margin:0 0 6px;font-size:14px;line-height:22px;font-weight:700;color:#18263b;">Não solicitou esta alteração?</p>
                                                    <p style="margin:0;font-size:14px;line-height:22px;color:#6c7c90;">Ignore este e-mail. Sua senha atual permanece a mesma. Não compartilhe este link com outras pessoas.</p>
                                                </td>
                                            </tr>
                                        </table>
                                        <p style="margin:28px 0 8px;font-size:12px;line-height:20px;color:#6c7c90;">Se o botão não funcionar, copie e cole este endereço no navegador:</p>
                                        <p style="margin:0;font-size:12px;line-height:20px;word-break:break-all;overflow-wrap:anywhere;"><a href="{$url}" style="color:#0a1931;text-decoration:underline;word-break:break-all;">{$url}</a></p>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding:24px 16px 0;">
                                        <p style="margin:0 0 4px;font-size:13px;line-height:21px;font-weight:700;color:#18263b;">Leila Brito · Formação ELO</p>
                                        <p style="margin:0;font-size:12px;line-height:20px;color:#6c7c90;">Conhecimento, estratégia e evolução.</p>
                                    </td>
                                </tr>
                            </table>
                            <!--[if mso]></td></tr></table><![endif]-->
                        </td>
                    </tr>
                </table>
            </body>
            </html>
            HTML;
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

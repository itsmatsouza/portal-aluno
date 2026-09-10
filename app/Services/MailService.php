<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Services;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

class MailService
{
    public function send(
        string $to,
        string $subject,
        string $htmlBody,
        ?string $textBody = null
    ): void {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();

            $mail->Host = $_ENV['MAIL_HOST'] ?? '';
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['MAIL_USERNAME'] ?? '';
            $mail->Password = $_ENV['MAIL_PASSWORD'] ?? '';
            $mail->Port = (int) ($_ENV['MAIL_PORT'] ?? 587);

            $encryption = strtolower(
                $_ENV['MAIL_ENCRYPTION'] ?? 'tls'
            );

            if ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->CharSet = 'UTF-8';

            $fromAddress = $_ENV['MAIL_FROM_ADDRESS']
                ?? $_ENV['MAIL_USERNAME']
                ?? '';

            $fromName = $_ENV['MAIL_FROM_NAME']
                ?? 'Leila Brito';

            $mail->setFrom(
                $fromAddress,
                $fromName
            );

            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;

            $mail->AltBody = $textBody
                ?? strip_tags($htmlBody);

            $mail->send();

        } catch (Exception $e) {
            throw new RuntimeException(
                'Não foi possível enviar o e-mail.',
                0,
                $e
            );
        }
    }
}

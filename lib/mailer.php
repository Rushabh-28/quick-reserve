<?php
/** Send an e-mail through SMTP using PHPMailer 6. Returns true on success. */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

require_once __DIR__ . '/../PHPMailer/Exception.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';

function send_mail(string $to, string $subject, string $htmlBody): bool
{
    global $qr_config;
    if ($qr_config['smtp_user'] === '' || $qr_config['smtp_pass'] === '') {
        error_log('Quick Reserve: SMTP is not configured (see config/config.sample.php).');
        return false;
    }
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $qr_config['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $qr_config['smtp_user'];
        $mail->Password   = $qr_config['smtp_pass'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $qr_config['smtp_port'];
        $mail->setFrom($qr_config['smtp_from'] ?: $qr_config['smtp_user'], $qr_config['smtp_from_name']);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);
        $mail->send();
        return true;
    } catch (MailException $e) {
        error_log('Quick Reserve mail error: ' . $mail->ErrorInfo);
        return false;
    }
}

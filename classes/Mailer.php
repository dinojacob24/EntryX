<?php

require_once __DIR__ . '/../config/mail_config.php';

class Mailer
{
    public static function sendResetEmail($to, $link)
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $fullLink = $protocol . "://" . $host . $link;

        $subject = "🔒 Reset Your Password - EntryX";

        $message = "
        <div style='max-width: 600px; margin: 20px auto; font-family: sans-serif; background: #000; color: #fff; padding: 40px; border-radius: 20px; border: 1px solid #ff1f1f;'>
            <h1 style='color: #ff1f1f; margin-bottom: 20px;'>EntryX</h1>
            <p style='color: #ccc; font-size: 1.1rem;'>We received a request to reset your password. If you didn't do this, you can safely ignore this email.</p>
            <div style='margin: 40px 0;'>
                <a href='$fullLink' style='background: #ff1f1f; color: #fff; padding: 15px 35px; text-decoration: none; border-radius: 10px; font-weight: 800; display: inline-block;'>RESET PASSWORD</a>
            </div>
            <p style='font-size: 0.8rem; color: #666;'>This link will expire in 1 hour.<br>&copy; " . date('Y') . " EntryX Systems</p>
        </div>";

        // --- REAL EMAIL LOGIC (PHPMailer) ---
        if (defined('MAIL_ENABLED') && MAIL_ENABLED === true) {
            try {
                // If you use PHPMailer, these files are required
                // require 'path/to/PHPMailer/src/Exception.php';
                // require 'path/to/PHPMailer/src/PHPMailer.php';
                // require 'path/to/PHPMailer/src/SMTP.php';

                // $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                // $mail->isSMTP();
                // $mail->Host = SMTP_HOST;
                // $mail->SMTPAuth = true;
                // $mail->Username = SMTP_USER;
                // $mail->Password = SMTP_PASS;
                // $mail->SMTPSecure = 'tls';
                // $mail->Port = SMTP_PORT;
                // $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
                // $mail->addAddress($to);
                // $mail->isHTML(true);
                // $mail->Subject = $subject;
                // $mail->Body = $message;
                // return $mail->send();
            } catch (Exception $e) {
                error_log("Mail Error: " . $e->getMessage());
                return false;
            }
        }

        // --- DEV FALLBACK (Logging) ---
        // Log the link so the user can test without real SMTP
        $logMessage = "[" . date('Y-m-d H:i:s') . "] RESET LINK for $to: $fullLink\n";
        file_put_contents(__DIR__ . '/../api/auth_error.log', $logMessage, FILE_APPEND);

        return false; // Returns false so the UI shows the 'Check Support' or 'Debug' message
    }
}

<?php

namespace App\Services;
error_reporting(E_ALL & ~E_WARNING);
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host = $_ENV['SMTP_HOST'] ?? 'localhost';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $_ENV['SMTP_USERNAME'] ?? 'user@example.com';
        $this->mailer->Password = $_ENV['SMTP_PASSWORD'] ?? 'password';
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = $_ENV['SMTP_PORT'] ?? 587;
        error_reporting(E_ALL);
    }

    public function sendPurchaseConfirmation($to, $eventTitle, $ticketType, $quantity, $totalPrice)
    {
        try {
            $this->mailer->setFrom($_ENV['FROM_EMAIL'], $_ENV['FROM_NAME']);
            $this->mailer->addAddress($to);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = "Ticket Purchase Confirmation";
            $this->mailer->Body = $this->getPurchaseTemplate($eventTitle, $ticketType, $quantity, $totalPrice);

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("Email could not be sent. Mailer Error: {$this->mailer->ErrorInfo}");
            return false;
        }
    }

    private function getPurchaseTemplate($eventTitle, $ticketType, $quantity, $totalPrice)
    {
        // HTML template for the email
        return "
            <h1>Ticket Purchase Confirmation</h1>
            <p>Thank you for your purchase!</p>
            <p>Event: {$eventTitle}</p>
            <p>Ticket Type: {$ticketType}</p>
            <p>Quantity: {$quantity}</p>
            <p>Total Price: \${$totalPrice}</p>
        ";
    }
}

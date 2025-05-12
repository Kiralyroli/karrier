<?php

namespace App\Model\Email;

use App\Model\Logger\FileLogger;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

readonly class EmailSender
{
    public function __construct(
        private MailerInterface $mailer,
        private FileLogger      $fileLogger,
        private string          $fromEmail,
        private string          $fromName,
        private TemplatedEmail  $templatedEmail = new TemplatedEmail(),
    )
    {
    }

    /**
     * @param array $toEmails
     * @param string $subject
     * @param string $htmlTemplate
     * @param array $context
     * @param array $attachments
     * @param array $secretToEmails
     * @return bool
     */
    public function send(
        array $toEmails,
        string $subject,
        string $htmlTemplate,
        array $context = [],
        array $attachments = [],
        array $secretToEmails = []
    ): bool {
        $toAddresses = array_map(function ($toEmail) {
            return new Address($toEmail);
        }, $toEmails);

        $bccAddresses = array_map(function ($bccEmail) {
            return new Address($bccEmail);
        }, $secretToEmails);

        $email = $this->templatedEmail
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to(...$toAddresses)
            ->bcc(...$bccAddresses)
            ->subject($subject)
            ->htmlTemplate($htmlTemplate)
            ->locale('hu')
            ->context($context);

        foreach ($attachments as $attachment) {
            if (file_exists($attachment)) {
                $email->attachFromPath($attachment);
            } else {
                $this->fileLogger->logError('Attachment not found: ' . $attachment);
            }
        }

        try {
            $this->mailer->send($email);
            return true;
        } catch (TransportExceptionInterface $e) {
            $this->fileLogger->logError('Unsuccessful email transport: ' . $e->getMessage());
            return false;
        }
    }
}
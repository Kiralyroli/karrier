<?php

namespace App\Model\Email;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

readonly class EmailSender
{
    public function __construct(
        private MailerInterface $mailer,
        private string          $fromEmail,
        private TemplatedEmail  $templatedEmail = new TemplatedEmail(),
    ) {
    }

    /**
     * @param array $toEmails
     * @param string $subject
     * @param string $htmlTemplate
     * @param array $context
     * @return void
     */
    public function send(array $toEmails, string $subject, string $htmlTemplate, array $context = []): void {
        $toAddresses = array_map(function ($toEmail) {
            return new Address($toEmail);
        }, $toEmails);
        $email = $this->templatedEmail
            ->from($this->fromEmail)
            ->to(...$toAddresses)
            ->subject($subject)
            ->htmlTemplate($htmlTemplate)
            ->locale('hu')
            ->context($context);
        $this->mailer->send($email);
    }
}
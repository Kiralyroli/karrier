<?php

namespace App\Controller;

use App\Model\Email\EmailSender;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact_page')]
    public function index(EmailSender $emailSender): Response
    {
        $validations = [];
        $successMessage = null;
        $unSuccessMessage = null;
        $values = [
            'name' => '',
            'email' => '',
            'phone' => '',
            'message' => '',
        ];
        $contactEmail = $_ENV['CONTACT_EMAIL'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $message = $_POST['message'] ?? '';
            $isFormValid = true;

            if (empty($name)) {
                $validations['name'] = 'is-invalid';
                $isFormValid = false;
            } else {
                $validations['name'] = 'is-valid';
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $validations['email'] = 'is-invalid';
                $isFormValid = false;
            } else {
                $validations['email'] = 'is-valid';
            }

            if (empty($phone)) {
                $validations['phone'] = 'is-invalid';
                $isFormValid = false;
            } else {
                $validations['phone'] = 'is-valid';
            }

            if (empty($message)) {
                $validations['message'] = 'is-invalid';
                $isFormValid = false;
            } else {
                $validations['message'] = 'is-valid';
            }

            $values = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'message' => $message,
            ];

            if ($isFormValid) {
                $result = $this->sendEmail($emailSender, $contactEmail, $values);
                if ($result) {
                    $successMessage = 'Az üzenet sikeresen elküldve!';
                } else {
                    $unSuccessMessage = 'Üzenet küldés sikertelen, kérjük próbálja újra később!';
                }
            }
        }

        return $this->render('contact/contact.html.twig', [
            'validations' => $validations,
            'successMessage' => $successMessage,
            'unSuccessMessage' => $unSuccessMessage,
            'formData' => $values,
            'contactEmail' => $contactEmail
        ]);
    }

    /**
     * @param EmailSender $emailSender
     * @param string $contactEmail
     * @param array $values
     * @return bool
     */
    private function sendEmail(EmailSender $emailSender, string $contactEmail, array $values): bool
    {
        return $emailSender->send(
            [$values['email']],//TODO: itt majd a $contactEmail-t kell használni. Teszelés idejére van csak $values['email'] használva!
            'Új kapcsolat felvétel',
            'emails/contact.html.twig',
            [
                'name' => $values['name'],
                'userEmail' => $values['email'],
                'phone' => $values['phone'],
                'message' => $values['message']
            ]
        );
    }
}
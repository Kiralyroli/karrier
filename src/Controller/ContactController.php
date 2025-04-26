<?php

namespace App\Controller;

use App\Model\Captcha\ReCaptchaService;
use App\Model\Email\EmailSender;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact_page')]
    public function index(EmailSender $emailSender, ValidatorInterface $validator, ReCaptchaService $reCaptchaService): Response
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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $message = $_POST['message'] ?? '';
            $isFormValid = true;

            $constraints = new Assert\Collection([
                'name' => [new Assert\NotBlank()],
                'email' => [new Assert\NotBlank(), new Assert\Email()],
                'phone' => [],
                'message' => [new Assert\NotBlank()],
                'g-recaptcha-response' => [new Assert\NotBlank()],
            ]);

            $violations = $validator->validate($_POST, $constraints);

            $validations = [
                'name' => 'is-valid',
                'email' => 'is-valid',
                'phone' => 'is-valid',
                'message' => 'is-valid',
            ];

            if (count($violations) > 0) {
                $isFormValid = false;
                foreach ($violations as $violation) {
                    $property = str_replace(['[', ']'], '', $violation->getPropertyPath());
                    $validations[$property] = 'is-invalid';
                }
            }

            if ($reCaptchaService->isSuccessVerify($_POST['g-recaptcha-response'])) {
                $validations['recaptcha'] = 'is-valid';
            } else {
                $validations['recaptcha'] = 'is-invalid';
                $isFormValid = false;
            }

            $values = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'message' => $message,
            ];

            if ($isFormValid) {
                $result = $this->sendEmail($emailSender, $values);
                if ($result) {
                    $successMessage = 'Az üzenet sikeresen elküldve!';
                } else {
                    $unSuccessMessage = 'Üzenet küldés sikertelen, kérjük próbálja újra később!';
                }
            }
        }

        $contactEmails = explode(';', $_ENV['CONTACT_EMAIL']);
        return $this->render('contact/contact.html.twig', [
            'validations' => $validations,
            'successMessage' => $successMessage,
            'unSuccessMessage' => $unSuccessMessage,
            'formData' => $values,
            'contactEmail' => reset($contactEmails),
            'contactPhone' => $_ENV['CONTACT_PHONE']
        ]);
    }

    /**
     * @param EmailSender $emailSender
     * @param array $values
     * @return bool
     */
    private function sendEmail(EmailSender $emailSender, array $values): bool
    {
        $contactEmails = explode(';', $_ENV['CONTACT_EMAIL']);
        return $emailSender->send(
            $contactEmails,
            'Új kapcsolatfelvétel',
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
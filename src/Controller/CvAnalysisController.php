<?php

namespace App\Controller;

use App\Model\Captcha\ReCaptchaService;
use App\Repository\SettingsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class CvAnalysisController extends AbstractController
{
    #[Route('/cv-analysis', name: 'cv_analysis_page')]
    public function index(SettingsRepository $settingsRepository): Response
    {
        $analysisPrice = $settingsRepository->findValueByKey('analysis_price');
        return $this->render('cv_analysis/cv_analysis.html.twig', [
            'analysis_price' => $analysisPrice,
            'faqs' => $this->getJsonData('analysis_faqs.json'),
        ]);
    }

    #[Route('/cv-analysis-data', name: 'cv_analysis_data_page')]
    public function cvAnalysisData(SessionInterface $session): Response
    {
        $sessionFormData = $session->get('analysisFormData');
        $validations = $session->get('analysis_data_validations', []);
        $session->remove('analysis_data_validations');
        return $this->render('cv_analysis/data_sheet.html.twig', [
            'formData' => $sessionFormData,
            'validations' => $validations,
        ]);
    }

    #[Route('/analysis-data-post', name: 'analysis_data_post', methods: ['POST'])]
    public function analysisDataPost(SessionInterface $session, Request $request, ValidatorInterface $validator, ReCaptchaService $reCaptchaService): Response
    {
        $data = $request->request->all();
        $session->set('analysisFormData', $data);
        $isFormValid = true;

        $constraints = new Assert\Collection([
            'firstname' => [new Assert\NotBlank()],
            'lastname' => [new Assert\NotBlank()],
            'email' => [new Assert\NotBlank(), new Assert\Email()],
            'phone' => [],
            'uploaded_cv_file' => [new Assert\NotBlank()],
            'position' => [new Assert\NotBlank()],
            'industry' => [new Assert\NotBlank()],
            'link' => [],
            'g-recaptcha-response' => [new Assert\NotBlank()],
        ]);

        $violations = $validator->validate($data, $constraints);

        $validations = [
            'lastname' => [
                'class' => 'is-valid'
            ],
            'firstname' => [
                'class' => 'is-valid'
            ],
            'email' => [
                'class' => 'is-valid'
            ],
            'phone' => [
                'class' => 'is-valid'
            ],
            'uploaded_cv_file' => [
                'class' => 'is-valid'
            ],
            'position' => [
                'class' => 'is-valid'
            ],
            'industry' => [
                'class' => 'is-valid'
            ],
            'link' => [
                'class' => 'is-valid'
            ],
        ];

        if (count($violations) > 0) {
            $isFormValid = false;
            foreach ($violations as $violation) {
                $property = str_replace(['[', ']'], '', $violation->getPropertyPath());
                $validations[$property] = [
                    'class' => 'is-invalid',
                    'message' => $violation->getMessage()
                ];
            }
        }

        if ($reCaptchaService->isSuccessVerify($_POST['g-recaptcha-response'])) {
            $validations['recaptcha'] = 'is-valid';
        } else {
            $validations['recaptcha'] = 'is-invalid';
            $isFormValid = false;
        }

        if (!$isFormValid) {
            $session->set('analysis_data_validations', $validations);
            return $this->redirectToRoute('cv_analysis_data_page');
        }

        return $this->redirectToRoute('checkout');
    }

    /**
     * @param string $jsonFile
     * @return array
     */
    private function getJsonData(string $jsonFile): array
    {
        $filePath = $this->getParameter('kernel.project_dir') . '/data/' . $jsonFile;
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('A ' . $jsonFile . ' fájl nem található!');
        }
        $jsonData = file_get_contents($filePath);
        return json_decode($jsonData, true);
    }
}
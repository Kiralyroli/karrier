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
    public function analysisDataPost(SessionInterface $session, Request $request, ValidatorInterface $validator, ReCaptchaService $reCaptchaService, SettingsRepository $settingsRepository): Response
    {
        $data = $request->request->all();
        $sessionData = $data;
        if (array_key_exists('g-recaptcha-response', $sessionData)) {
            unset($sessionData['g-recaptcha-response']);
        }
        $session->set('analysisFormData', $sessionData);
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

        return $this->cart($session, $settingsRepository, $data);
    }

    /**
     * @param SessionInterface $session
     * @param SettingsRepository $settingsRepository
     * @param array $formData
     * @return Response
     */
    private function cart(SessionInterface $session, SettingsRepository $settingsRepository, array $formData): Response
    {
        $analysisPrice = $settingsRepository->findValueByKey('analysis_price');
        $product = [
            'sku' => 'cv_analysis',
            'name' => 'Önéletrajz-elemzés',
            'type' => 'analysis',
            'id' => 1,
            'discountPrice' => $analysisPrice,
            'price' => $analysisPrice,
            'features' => [
                'Vezetéknév: ' . $formData['lastname'],
                'Keresztnév: ' . $formData['firstname'],
                'Email cím: ' . $formData['email'],
                'Telefonszám: ' . (empty($formData['phone']) ? '-' : $formData['phone']),
                'Pozíció: ' . (empty($formData['position']) ? '-' : $formData['position']),
                'Iparág: ' . (empty( $formData['industry']) ? '-' : $formData['industry']),
                'Álláshirdetés: ' . (empty($formData['link']) ? '-' : $formData['link']),
                'Feltöltött önéletrajz: ' . $formData['uploaded_cv_file'],
            ]
        ];
        $session->set('product', $product);
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
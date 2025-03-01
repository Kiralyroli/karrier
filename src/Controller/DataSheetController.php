<?php

namespace App\Controller;

use App\Repository\OrdersRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class DataSheetController extends AbstractController
{
    #[Route('/data-sheet/{uniqueId}', name: 'data_sheet', methods: ['GET'])]
    public function index(string $uniqueId, SessionInterface $session): Response
    {
        $sessionFormData = $session->get('formData');
        if (!$sessionFormData) {
            $sessionFormData = [];
        }
        return $this->render('data_sheet/data_sheet.html.twig', [
            'uniqueId' => $uniqueId,
            'step' => 1,
            'lastStep' => $this->getLastStepNumber(),
            'formData' => $sessionFormData,
        ]);
    }

    #[Route('/data-sheet/{uniqueId}/finish', name: 'data_sheet_finish', methods: ['GET'])]
    public function finish(string $uniqueId, SessionInterface $session): Response
    {
        $sessionFormData = $session->get('formData');
        if (!$sessionFormData) {
            $sessionFormData = [];
        }
        return $this->render('data_sheet/success.html.twig');
    }

    #[Route('/load-data-sheet-content', name: 'load_data_sheet_content', methods: ['POST'])]
    public function loadDataSheetContent(Request $request, SessionInterface $session): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['step'])) {
            return new Response('Hibás kérés', Response::HTTP_BAD_REQUEST);
        }

        $step = $data['step'];
        $uniqueId = $data['uniqueId'];

        $sessionFormData = $session->get('formData');
        if (!$sessionFormData) {
            $sessionFormData = [];
        }
        if (empty($data['formData']['uploaded_cv_file'])) {
            unset($data['formData']['uploaded_cv_file']);
        }
        if (empty($data['formData']['uploaded_cv_image'])) {
            unset($data['formData']['uploaded_cv_image']);
        }

        $data = $this->addMultiElementFormData($data, 'jobs', 'job');
        $data = $this->addMultiElementFormData($data, 'experiences', 'experience');
        $data = $this->addMultiElementFormData($data, 'studies', 'study');
        $data = $this->addMultiElementFormData($data, 'professionalSkills', 'professional_skill');
        $data = $this->addMultiElementFormData($data, 'privateSkills', 'private_skill');
        $data = $this->addMultiElementFormData($data, 'languages', 'language');

        $sessionFormData = array_merge($sessionFormData, $data['formData']);
        $session->set('formData', $sessionFormData);

        $lastStepNumber = $this->getLastStepNumber();
        if ($step > $lastStepNumber) {
            return new JsonResponse([
                'redirect' => $this->generateUrl('data_sheet_finish', ['uniqueId' => $uniqueId]),
            ]);
        }

        $templatePath = 'data_sheet/steps/' . $step . '.html.twig';
        $content = $this->renderView($templatePath, [
            'step' => $step,
            'lastStep' => $lastStepNumber,
            'formData' => $sessionFormData,
            'uniqueId' => $uniqueId,
        ]);

        return new JsonResponse([
            'content' => $content
        ]);
    }

    /**
     * @return int
     */
    private function getLastStepNumber(): int
    {
        $files = glob($this->getParameter('kernel.project_dir') . '/templates/data_sheet/steps/*.html.twig');
        $maxNumber = 0;
        foreach ($files as $file) {
            if (preg_match('/(\d+)\.html\.twig$/', basename($file), $matches)) {
                $number = (int)$matches[1];
                if ($number > $maxNumber) {
                    $maxNumber = $number;
                }
            }
        }
        return $maxNumber;
    }

    /**
     * @param array $data
     * @param string $elementsName
     * @param string $elementName
     * @return array
     */
    private function addMultiElementFormData(array $data, string $elementsName, string $elementName): array
    {
        $elements = [];
        foreach ($data['formData'] as $key => $value) {
            if (str_contains($key, $elementName . '_')) {
                preg_match('/\[(\d+)\]/', $key, $matches);
                $elements[$matches[1]][str_replace([$elementName . '_', '[' . $matches[1] . ']'], '', $key)] = $value;
                unset($data['formData'][$key]);
            }
        }
        if (!empty($elements)) {
            foreach ($elements as $key => $element) {
                $isEmptyElement = true;
                foreach ($element as $value) {
                    if (!empty($value)) {
                        $isEmptyElement = false;
                        break;
                    }
                }
                if ($isEmptyElement) {
                    unset($elements[$key]);
                }
            }
            $data['formData'][$elementsName] = $elements;
        }
        return $data;
    }
}
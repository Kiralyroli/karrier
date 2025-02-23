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
            'formData' => $sessionFormData,
        ]);
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
        $jobs = [];
        foreach ($data['formData'] as $key => $value) {
            if (str_contains($key, 'job_')) {
                preg_match('/\[(\d+)\]/', $key, $matches);
                $jobs[$matches[1]][str_replace(['job_', '[' . $matches[1] . ']'], '', $key)] = $value;
                unset($data['formData'][$key]);
            }
        }
        if (!empty($jobs)) {
            $data['formData']['jobs'] = $jobs;
        }

        $sessionFormData = array_merge($sessionFormData, $data['formData']);
        $session->set('formData', $sessionFormData);

        $templatePath = 'data_sheet/steps/' . $step . '.html.twig';
        return $this->render($templatePath, [
            'step' => $step,
            'formData' => $sessionFormData,
        ]);
    }
}
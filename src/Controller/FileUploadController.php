<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FileUploadController extends AbstractController
{
    #[Route('/upload-file', name: 'upload_file', methods: ['POST'])]
    public function uploadFile(Request $request): JsonResponse
    {
        $file = $request->files->get('file');

        if (!$file) {
            return new JsonResponse(['message' => 'Nincs fájl feltöltve'], 400);
        }

        try {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/uploads';
            $fileName = uniqid() . '.' . $file->guessExtension();
            $file->move($uploadDir, $fileName);

            return new JsonResponse(['message' => 'Fájl sikeresen feltöltve', 'file' => $fileName], 200);
        } catch (FileException $e) {
            return new JsonResponse(['message' => 'Feltöltési hiba'], 500);
        }
    }

    #[Route('/download-file/{filename}', name: 'download_file')]
    public function downloadFile(string $filename): BinaryFileResponse
    {
        $filePath = $this->getParameter('kernel.project_dir') . '/uploads/' . $filename;
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('A fájl nem található.');
        }
        return (new BinaryFileResponse($filePath))
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
    }
}

<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home_page')]
    public function index(): Response
    {
        return $this->render('home/home.html.twig', [
            'testimonials' => $this->getJsonData('testimonials.json'),
            'faqs' => $this->getJsonData('home_faqs.json'),
        ]);
    }

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
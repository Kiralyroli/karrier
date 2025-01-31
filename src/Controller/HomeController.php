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
        $filePath = $this->getParameter('kernel.project_dir') . '/data/testimonials.json';
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('A testimonials.json fájl nem található!');
        }
        $jsonData = file_get_contents($filePath);
        $testimonials = json_decode($jsonData, true);

        return $this->render('home/home.html.twig', [
            'testimonials' => $testimonials,
        ]);
    }
}
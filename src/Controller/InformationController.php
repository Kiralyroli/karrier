<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class InformationController extends AbstractController
{
    #[Route('/aszf', name: 'information_aszf')]
    public function aszf(): Response
    {
        return $this->render('information/aszf_page.html.twig');
    }

    #[Route('/adatkezelesi-nyilatkozat', name: 'information_privacy_statement')]
    public function privacyStatement(): Response
    {
        return $this->render('information/privacy_statement_page.html.twig');
    }
}
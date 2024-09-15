<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PricesController extends AbstractController
{
    #[Route('/prices', name: 'prices_page')]
    public function index(): Response
    {
        return $this->render('prices/prices.html.twig');
    }
}
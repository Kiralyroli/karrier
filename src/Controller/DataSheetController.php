<?php

namespace App\Controller;

use App\Repository\OrdersRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class DataSheetController extends AbstractController
{
    #[Route('/data-sheet/{uniqueId}', name: 'data_sheet', methods: ['GET'])]
    public function index(string $uniqueId, Request $request, SessionInterface $session, OrdersRepository $ordersRepository): Response
    {
        $order = $ordersRepository->findByUniqueId($uniqueId);
    }
}
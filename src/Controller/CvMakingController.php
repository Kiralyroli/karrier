<?php

namespace App\Controller;

use App\Repository\PackagesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class CvMakingController extends AbstractController
{
    #[Route('/cv-making', name: 'cv_making_page')]
    public function index(): Response
    {
        return $this->render('cv_making/cv_making.html.twig', [
            'carrier_levels' => $this->getJsonData('carrier_levels/carrier_levels.json'),
            'faqs' => $this->getJsonData('home_faqs.json'),
        ]);
    }

    #[Route('/load-career-content', name: 'load_career_content', methods: ['POST'])]
    public function loadCareerContent(Request $request, PackagesRepository $packagesRepository): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['level'])) {
            return new Response('Hibás kérés', Response::HTTP_BAD_REQUEST);
        }

        $level = $data['level'];

        $templatePath = 'cv_making/carrier_levels/packages.html.twig';

        return $this->render($templatePath, [
            'carrier_levels' => $this->getJsonData('carrier_levels/carrier_levels.json'),
            'level' => $level,
            'packages' => $packagesRepository->findByLevel($level)
        ]);
    }

    #[Route('/package-cart', name: 'package_cart_add', methods: ['POST'])]
    public function cart(Request $request, SessionInterface $session, PackagesRepository $packagesRepository): Response
    {
        $packageId = $request->get('package_id', $request->request->get('package_id'));
        if ($packageId) {
            $package = $packagesRepository->find($packageId);
            match ($package->getLevel()) {
                'beginner' => $packageLevel = 'Pályakezdő',
                'junior' => $packageLevel = 'Junior',
                'medior' => $packageLevel = 'Medior',
                'senior' => $packageLevel = 'Senior',
                'leader' => $packageLevel = 'Vezető',
            };
            $product = [
                'sku' => $package->getLevel() . '_' . $packageId,
                'name' => $package->getTitle() . ' ' . $packageLevel . ' önéletrajz készítés',
                'type' => 'package',
                'id' => $packageId,
                'discountPrice' => $package->getDiscountPrice(),
                'price' => $package->getPrice(),
                'features' => $package->getFeatures()
            ];
            $session->set('product', $product);
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
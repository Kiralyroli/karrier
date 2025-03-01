<?php

namespace App\Controller;

use App\Entity\Orders;
use App\Entity\Packages;
use App\Model\Email\EmailSender;
use App\Repository\PackagesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class CheckoutController extends AbstractController
{
    #[Route('/cart', name: 'cart_add', methods: ['POST'])]
    public function index(Request $request, SessionInterface $session): Response
    {
        $packageId = $request->get('package_id', $request->request->get('package_id'));
        if ($packageId) {
            $session->set('package_id', $packageId);
        }
        return $this->redirectToRoute('checkout');
    }

    #[Route('/checkout', name: 'checkout', methods: ['GET', 'POST'])]
    public function checkout(Request $request, ValidatorInterface $validator, PackagesRepository $packagesRepository, SessionInterface $session): Response
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $request->request->all();

            $constraints = new Assert\Collection([
                'firstname' => [new Assert\NotBlank()],
                'lastname' => [new Assert\NotBlank()],
                'email' => [new Assert\NotBlank(), new Assert\Email()],
                'phone' => [new Assert\NotBlank()],
                'country' => [new Assert\NotBlank()],
                'zipcode' => [new Assert\NotBlank()],
                'city' => [new Assert\NotBlank()],
                'address' => [new Assert\NotBlank()],
            ]);

            $violations = $validator->validate($data, $constraints);

            $validations = [
                'lastname' => 'is-valid',
                'firstname' => 'is-valid',
                'email' => 'is-valid',
                'phone' => 'is-valid',
                'country' => 'is-valid',
                'zipcode' => 'is-valid',
                'city' => 'is-valid',
                'address' => 'is-valid',
            ];
            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $property = str_replace(['[', ']'], '', $violation->getPropertyPath());
                    $validations[$property] = 'is-invalid';
                }
                return $this->getCartTwig($packagesRepository, $session, $data, $validations);
            }

            $session->set('checkout_data', $data);
            return $this->redirectToRoute('checkout_success');
        } else {
            return $this->getCartTwig($packagesRepository, $session);
        }
    }

    #[Route('/checkout/success', name: 'checkout_success', methods: ['GET'])]
    public function success(SessionInterface $session, EntityManagerInterface $entityManager, PackagesRepository $packagesRepository, EmailSender $emailSender)
    {
        $packageId = $session->get('package_id');
        $checkoutData = $session->get('checkout_data');
        $session->remove('package_id');
        $session->remove('checkout_data');

        $package = $packagesRepository->find($packageId);

        $uniqueId = uniqid();
        $order = new Orders(
            $uniqueId,
            $checkoutData['lastname'],
            $checkoutData['firstname'],
            $checkoutData['email'],
            $checkoutData['phone'],
            $checkoutData['country'],
            $checkoutData['zipcode'],
            $checkoutData['city'],
            $checkoutData['address'],
            $package->getLevel(),
            $package->getTitle(),
            $package->getPrice(),
            new \DateTime(),
            new \DateTime()
        );

        $entityManager->persist($order);
        $entityManager->flush();

        $result = $this->sendEmail($emailSender, $checkoutData, $package, $uniqueId);
        return $this->render('cart/success.html.twig', [
            'uniqueId' => $uniqueId
        ]);
    }

    /**
     * @param PackagesRepository $packagesRepository
     * @param SessionInterface $session
     * @param array $data
     * @param array $validations
     * @return Response
     */
    private function getCartTwig(PackagesRepository $packagesRepository, SessionInterface $session, array $data = [], array $validations = []): Response
    {
        $packageId = $session->get('package_id');
        if (empty($packageId)) {
            return $this->redirectToRoute('cv_making_page');
        }
        $package = $packagesRepository->find($packageId);
        match ($package->getLevel()) {
            'beginner' => $package->setLevel('Pályakezdő'),
            'junior' => $package->setLevel('Junior'),
            'medior' => $package->setLevel('Medior'),
            'senior' => $package->setLevel('Senior'),
            'leader' => $package->setLevel('Vezető'),
        };
        return $this->render('cart/cart.html.twig', [
            'package' => $package,
            'data' => $data,
            'validations' => $validations,
        ]);
    }

    /**
     * @param EmailSender $emailSender
     * @param array $values
     * @param Packages $package
     * @param string $uniqueId
     * @return bool
     */
    private function sendEmail(EmailSender $emailSender, array $values, Packages $package, string $uniqueId): bool
    {
        match ($package->getLevel()) {
            'beginner' => $package->setLevel('Pályakezdő'),
            'junior' => $package->setLevel('Junior'),
            'medior' => $package->setLevel('Medior'),
            'senior' => $package->setLevel('Senior'),
            'leader' => $package->setLevel('Vezető'),
        };
        $result = $emailSender->send(
            [$values['email']],
            'CV Maker rendelés',
            'emails/order.html.twig',
            [
                'firstname' => $values['firstname'],
                'packageLevel' => $package->getLevel(),
                'packageTitle' => $package->getTitle(),
                'price' => $package->getPrice(),
                'uniqueId' => $uniqueId
            ]
        );

        $contactEmail = $_ENV['CONTACT_EMAIL'];
        $adminResult = $emailSender->send(
            [$contactEmail],
            'Új CV Maker rendelés érkezett',
            'emails/admin/order.html.twig',
            [
                'values' => $values,
                'createdDate' => new \DateTime(),
                'packageLevel' => $package->getLevel(),
                'packageTitle' => $package->getTitle(),
                'price' => $package->getPrice(),
                'uniqueId' => $uniqueId
            ]
        );

        return $result && $adminResult;
    }
}
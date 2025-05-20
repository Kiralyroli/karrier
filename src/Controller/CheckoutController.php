<?php

namespace App\Controller;

use App\Entity\Orders;
use App\Entity\Packages;
use App\Model\Captcha\ReCaptchaService;
use App\Model\Coupon\CouponProcessor;
use App\Model\Email\EmailSender;
use App\Repository\CouponRepository;
use App\Repository\OrdersRepository;
use App\Repository\PackagesRepository;
use Barion\BarionClient;
use Barion\Enumerations\BarionEnvironment;
use Barion\Enumerations\Currency;
use Barion\Enumerations\FundingSourceType;
use Barion\Enumerations\PaymentType;
use Barion\Enumerations\UILocale;
use Barion\Exceptions\BarionException;
use Barion\Models\Common\ItemModel;
use Barion\Models\Payment\PaymentStateResponseModel;
use Barion\Models\Payment\PaymentTransactionModel;
use Barion\Models\Payment\PreparePaymentRequestModel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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

    /**
     * @throws BarionException
     */
    #[Route('/checkout', name: 'checkout', methods: ['GET', 'POST'])]
    public function checkout(
        Request                $request,
        ValidatorInterface     $validator,
        PackagesRepository     $packagesRepository,
        SessionInterface       $session,
        EntityManagerInterface $entityManager,
        ReCaptchaService       $reCaptchaService,
        CouponRepository       $couponRepository
    ): Response
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $request->request->all();
            $data['agree_aszf'] = isset($data['agree_aszf']) && $data['agree_aszf'] === 'on';
            $data['agree_privacy_statement'] = isset($data['agree_privacy_statement']) && $data['agree_privacy_statement'] === 'on';
            $isFormValid = true;

            $constraints = new Assert\Collection([
                'firstname' => [new Assert\NotBlank()],
                'lastname' => [new Assert\NotBlank()],
                'email' => [new Assert\NotBlank(), new Assert\Email()],
                'phone' => [new Assert\NotBlank()],
                'country' => [new Assert\NotBlank()],
                'zipcode' => [new Assert\NotBlank()],
                'city' => [new Assert\NotBlank()],
                'address' => [new Assert\NotBlank()],
                'agree_aszf' => [new Assert\IsTrue()],
                'agree_privacy_statement' => [new Assert\IsTrue()],
                'g-recaptcha-response' => [new Assert\NotBlank()],
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
                'agree_aszf' => 'is-valid',
                'agree_privacy_statement' => 'is-valid',
            ];
            if (count($violations) > 0) {
                $isFormValid = false;
                foreach ($violations as $violation) {
                    $property = str_replace(['[', ']'], '', $violation->getPropertyPath());
                    $validations[$property] = 'is-invalid';
                }
            }

            if ($reCaptchaService->isSuccessVerify($_POST['g-recaptcha-response'])) {
                $validations['recaptcha'] = 'is-valid';
            } else {
                $validations['recaptcha'] = 'is-invalid';
                $isFormValid = false;
            }

            if (!$isFormValid) {
                return $this->getCartTwig($packagesRepository, $session, $data, $validations);
            }

            $session->set('checkout_data', $data);
            $session->remove('email_sent');
            $order = $this->createOrder($session, $packagesRepository, $entityManager, $couponRepository);
            $gatewayUrl = $this->callBarion($order, $session->get('package_id'));
            if ($gatewayUrl) {
                return $this->redirect($gatewayUrl);
            }
            //todo: sikertelen azonosítás lekezelés, mondjuk sikertelen kártyás fizetés oldalra vigyen
        } else {
            $session->remove('coupon');
            return $this->getCartTwig($packagesRepository, $session);
        }
    }

    #[Route('/checkout/coupon', name: 'checkout_coupon', methods: ['POST'])]
    public function coupon(Request $request, SessionInterface $session, PackagesRepository $packagesRepository, CouponRepository $couponRepository): JsonResponse
    {
        $couponCode = $request->request->get('coupon');
        $packageId = $session->get('package_id');
        if (empty($packageId)) {
            return new JsonResponse([
                'valid' => false,
                'message' => 'Hiányzó csomag.'
            ]);
        }
        $package = $packagesRepository->find($packageId);
        if ($package === null) {
            return new JsonResponse([
                'valid' => false,
                'message' => 'Hiányzó csomag.'
            ]);
        }
        $originalPrice = $package->getPrice();
        if ($package->getDiscountPrice() !== null && $package->getDiscountPrice() < $originalPrice) {
            $originalPrice = $package->getDiscountPrice();
        }
        $processedCoupon = CouponProcessor::calculatePrices($couponCode, $originalPrice, $couponRepository);
        if ($processedCoupon['valid']) {
            $session->set('coupon', $couponCode);
        }
        return new JsonResponse($processedCoupon);
    }

    #[Route('/checkout/success', name: 'checkout_success', methods: ['GET'])]
    public function success(SessionInterface $session, OrdersRepository $ordersRepository, PackagesRepository $packagesRepository, EmailSender $emailSender)
    {
        $packageId = $session->get('package_id');
        $checkoutData = $session->get('checkout_data');
        $uniqueId = $session->get('order_custom_id');
        $barionPaymentId = $session->get('barion_payment_id');
        $barionPaymentPrice = $session->get('barion_payment_price');
        $emailSent = $session->get('email_sent');

        $ordersRepository->setOrderSuccessWith($uniqueId);
        $order = $ordersRepository->findByUniqueId($uniqueId);
        $package = $packagesRepository->find($packageId);

        if (!$emailSent) {
            $result = $this->sendEmail($emailSender, $checkoutData, $package, $order, $uniqueId);
        }
        $session->set('email_sent', true);
        return $this->render('cart/success.html.twig', [
            'uniqueId' => $uniqueId,
            'barion_success' => true,
            'barion_payment_id' => $barionPaymentId,
            'barion_payment_price' => $barionPaymentPrice
        ]);
    }

    #[Route('/checkout/unsuccess', name: 'checkout_unsuccess', methods: ['GET'])]
    public function unsuccess(SessionInterface $session)
    {
        $barionPaymentId = $session->get('barion_payment_id');
        $barionPaymentPrice = $session->get('barion_payment_price');
        return $this->render('cart/unsuccess.html.twig', [
            'barion_success' => false,
            'barion_payment_id' => $barionPaymentId,
            'barion_payment_price' => $barionPaymentPrice
        ]);
    }

    #[Route('/checkout/payment-restart', name: 'checkout_payment_restart', methods: ['GET'])]
    public function paymentRestart(SessionInterface $session, OrdersRepository $ordersRepository)
    {
        $uniqueId = $session->get('order_custom_id');

        $order = $ordersRepository->findByUniqueId($uniqueId);
        $gatewayUrl = $this->callBarion($order, $session->get('package_id'));
        if ($gatewayUrl) {
            return $this->redirect($gatewayUrl);
        }
        //todo: sikertelen azonosítás lekezelés, mondjuk sikertelen kártyás fizetés oldalra vigyen
    }

    #[Route('/checkout/payment-redirect', name: 'checkout_payment_redirect', methods: ['GET'])]
    public function paymentRedirect(Request $request, SessionInterface $session)
    {
        $barionPaymentId = $request->get('paymentId');
        $paymentDetails = $this->getBarionPaymentDetails($barionPaymentId);
        $paymentStatus = $paymentDetails->Status->value;

        $session->set('barion_payment_id', $barionPaymentId);
        $session->set('barion_payment_price', $paymentDetails->Total);

        if ($paymentStatus === 'Succeeded') {
            return $this->redirectToRoute('checkout_success');
        }
        return $this->redirectToRoute('checkout_unsuccess');
    }

    #[Route('/checkout/payment-callback', name: 'checkout_payment_callback', methods: ['POST'])]
    public function paymentCallback(Request $request)
    {
        $paymentStatus = $this->getBarionPaymentDetails($request->get('paymentId'));
        return new Response('OK');
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
     * @param Orders $order
     * @param string $uniqueId
     * @return bool
     */
    private function sendEmail(EmailSender $emailSender, array $values, Packages $package, Orders $order, string $uniqueId): bool
    {
        match ($package->getLevel()) {
            'beginner' => $package->setLevel('Pályakezdő'),
            'junior' => $package->setLevel('Junior'),
            'medior' => $package->setLevel('Medior'),
            'senior' => $package->setLevel('Senior'),
            'leader' => $package->setLevel('Vezető'),
        };
        $contactEmails = explode(';', $_ENV['CONTACT_EMAIL']);
        $secretEmails = explode(';', $_ENV['SECRET_EMAIL']);
        $price = $order->getPrice();
        $result = $emailSender->send(
            [$values['email']],
            'Megrendelés megerősítése',
            'emails/order.html.twig',
            [
                'firstname' => $values['firstname'],
                'packageLevel' => $package->getLevel(),
                'packageTitle' => $package->getTitle(),
                'price' => $price,
                'uniqueId' => $uniqueId,
                'contactEmail' => reset($contactEmails),
                'contactPhone' => $_ENV['CONTACT_PHONE']
            ]
        );

        $adminResult = $emailSender->send(
            $contactEmails,
            'Új primecv.hu rendelés érkezett',
            'emails/admin/order.html.twig',
            [
                'values' => $values,
                'createdDate' => new \DateTime(),
                'packageLevel' => $package->getLevel(),
                'packageTitle' => $package->getTitle(),
                'price' => $price,
                'coupon' => $order->getCoupon(),
                'uniqueId' => $uniqueId
            ],
            [],
            $secretEmails
        );

        return $result && $adminResult;
    }

    /**
     * @param SessionInterface $session
     * @param PackagesRepository $packagesRepository
     * @param EntityManagerInterface $entityManager
     * @param CouponRepository $couponRepository
     * @return Orders
     */
    private function createOrder(SessionInterface $session, PackagesRepository $packagesRepository, EntityManagerInterface $entityManager, CouponRepository $couponRepository): Orders
    {
        $checkoutData = $session->get('checkout_data');
        $packageId = $session->get('package_id');

        $package = $packagesRepository->find($packageId);

        $uniqueId = uniqid();
        $packagePrice = $package->getPrice();
        if ($package->getDiscountPrice() !== null && $package->getDiscountPrice() < $packagePrice) {
            $packagePrice = $package->getDiscountPrice();
        }
        $couponCode = $session->get('coupon');
        $price = $packagePrice;
        $couponText = null;
        if (!empty($couponCode)) {
            $processedCoupon = CouponProcessor::calculatePrices($couponCode, $packagePrice, $couponRepository);
            if ($processedCoupon['valid']) {
                $price = $processedCoupon['newPriceValue'];
                $couponText = $processedCoupon['couponText'];
            }
        }
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
            $packagePrice,
            $price,
            new \DateTime(),
            new \DateTime(),
            false
        );

        if ($couponText) {
            $order->setCoupon($couponText);
        }

        $entityManager->persist($order);
        $entityManager->flush();

        $session->set('order_custom_id', $uniqueId);
        return $order;
    }

    /**
     * @param Orders $order
     * @param int $packageId
     * @return string|null
     * @throws BarionException
     */
    private function callBarion(Orders $order, int $packageId): ?string
    {
        $packageLevel = $order->getPackageLevel();
        match ($packageLevel) {
            'beginner' => $packageLevel = 'Pályakezdő',
            'junior' => $packageLevel = 'Junior',
            'medior' => $packageLevel = 'Medior',
            'senior' => $packageLevel = 'Senior',
            'leader' => $packageLevel = 'Vezető',
        };

        $item = new ItemModel();
        $item->Name = $order->getPackageTitle() . ' ' . $packageLevel . ' önéletrajz készítés';
        $item->Quantity = 1;
        $item->Unit = "db";
        $item->UnitPrice = $order->getPrice();
        $item->ItemTotal = $order->getPrice();
        $item->SKU = $packageLevel . '_' . $packageId;
        $item->Description = $item->Name;

        $transaction = new PaymentTransactionModel();
        $transaction->POSTransactionId = uniqid();
        $transaction->Payee = $_ENV['BARION_EMAIL'];
        $transaction->Total = $order->getPrice();
        $transaction->AddItem($item);

        $ppr = new PreparePaymentRequestModel();
        $ppr->GuestCheckout = true;
        $ppr->PaymentType = PaymentType::Immediate;
        $ppr->FundingSources = array(FundingSourceType::All);
        $ppr->PaymentRequestId = uniqid();
        $ppr->PayerHint = $order->getEmail();
        $ppr->Locale = UILocale::HU;
        $ppr->OrderNumber = "ORDER-" . $order->getId();
        $ppr->Currency = Currency::HUF;
        $ppr->RedirectUrl = $this->generateUrl('checkout_payment_redirect', [], 0);
        if ($_ENV['BARION_ENV'] === 'PROD') {
            $ppr->CallbackUrl = $this->generateUrl('checkout_payment_callback', [], 0);
        }
        $ppr->AddTransaction($transaction);

        $barionClient = $this->getBarionClient();

        $myPayment = $barionClient->PreparePayment($ppr);
        $paymentId = $myPayment->PaymentId;

        if ($paymentId) {
            return $myPayment->PaymentRedirectUrl;
        } elseif (!empty($myPayment->Errors)) {
            $errors = $myPayment->Errors;
        }
        return null;
    }

    /**
     * @return BarionClient
     * @throws BarionException
     */
    private function getBarionClient(): BarionClient
    {
        if ($_ENV['BARION_ENV'] === 'TEST') {
            $environment = BarionEnvironment::Test;
        } elseif ($_ENV['BARION_ENV'] === 'PROD') {
            $environment = BarionEnvironment::Prod;
        } else {
            throw new BarionException("Missing environment!");
        }

        return new BarionClient(
            $_ENV['BARION_POSKEY'],
            $_ENV['BARION_API_VERSION'],
            $environment
        );
    }

    /**
     * @param string $paymentId
     * @return PaymentStateResponseModel
     * @throws BarionException
     */
    private function getBarionPaymentDetails(string $paymentId): PaymentStateResponseModel
    {
        $barionClient = $this->getBarionClient();
        $barionClient->SetVersion(4);
        return $barionClient->PaymentState($paymentId);
    }
}
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
use App\Repository\SettingsRepository;
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
    /**
     * @throws BarionException
     */
    #[Route('/checkout', name: 'checkout', methods: ['GET', 'POST'])]
    public function index(
        Request                $request,
        ValidatorInterface     $validator,
        PackagesRepository     $packagesRepository,
        SessionInterface       $session,
        EntityManagerInterface $entityManager,
        ReCaptchaService       $reCaptchaService,
        CouponRepository       $couponRepository,
        SettingsRepository     $settingsRepository
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
                return $this->getCartTwig($session, $data, $validations);
            }

            $session->set('checkout_data', $data);
            $session->remove('email_sent');
            $order = $this->createOrder($session, $packagesRepository, $entityManager, $couponRepository, $settingsRepository);
            if ($order === null) {
                return $this->redirectToRoute('home_page');
            }
            $product = $session->get('product');
            $gatewayUrl = $this->callBarion($order, $product['sku']);
            if ($gatewayUrl) {
                return $this->redirect($gatewayUrl);
            }
            //todo: sikertelen azonosítás lekezelés, mondjuk sikertelen kártyás fizetés oldalra vigyen
        } else {
            $session->remove('coupon');
            return $this->getCartTwig($session);
        }
    }

    #[Route('/checkout/coupon', name: 'checkout_coupon', methods: ['POST'])]
    public function coupon(
        Request            $request,
        SessionInterface   $session,
        PackagesRepository $packagesRepository,
        CouponRepository   $couponRepository,
        SettingsRepository $settingsRepository
    ): JsonResponse {
        $couponCode = $request->request->get('coupon');
        $sessionProduct = $session->get('product');
        $productId = $sessionProduct['id'];

        $originalPrice = null;
        if ($sessionProduct['type'] === 'package') {
            $package = $packagesRepository->find($productId);
            $packagePrice = $package->getPrice();
            if ($package->getDiscountPrice() !== null && $package->getDiscountPrice() < $packagePrice) {
                $packagePrice = $package->getDiscountPrice();
            }
            $originalPrice = $packagePrice;
        } elseif ($sessionProduct['type'] === 'analysis') {
            $originalPrice = $settingsRepository->findValueByKey('analysis_price');
        }

        if ($originalPrice === null) {
            return new JsonResponse([
                'valid' => false,
                'message' => 'Hiányzó csomag.'
            ]);
        }

        $processedCoupon = CouponProcessor::calculatePrices($couponCode, $originalPrice, $couponRepository);
        if ($processedCoupon['valid']) {
            $session->set('coupon', $couponCode);
        }
        return new JsonResponse($processedCoupon);
    }

    #[Route('/checkout/success', name: 'checkout_success', methods: ['GET'])]
    public function success(SessionInterface $session, OrdersRepository $ordersRepository, EmailSender $emailSender)
    {
        $checkoutData = $session->get('checkout_data');
        $uniqueId = $session->get('order_custom_id');
        $barionPaymentId = $session->get('barion_payment_id');
        $barionPaymentPrice = $session->get('barion_payment_price');
        $emailSent = $session->get('email_sent');

        $ordersRepository->setOrderSuccessWith($uniqueId);
        $order = $ordersRepository->findByUniqueId($uniqueId);

        if (!$emailSent) {
            $result = $this->sendEmail($emailSender, $checkoutData, $order, $uniqueId);
        }
        $session->set('email_sent', true);
        $session->remove('product');
        return $this->render('cart/success.html.twig', [
            'uniqueId' => $uniqueId,
            'barion_success' => true,
            'barion_payment_id' => $barionPaymentId,
            'barion_payment_price' => $barionPaymentPrice,
            'product_type' => $order->getProductType()
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
        $product = $session->get('product');
        $gatewayUrl = $this->callBarion($order, $product['sku']);
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
     * @param SessionInterface $session
     * @param array $data
     * @param array $validations
     * @return Response
     */
    private function getCartTwig(SessionInterface $session, array $data = [], array $validations = []): Response
    {
        $product = $session->get('product');
        if (empty($product)) {
            return $this->redirectToRoute('home_page');
        }
        return $this->render('cart/cart.html.twig', [
            'product' => $product,
            'data' => $data,
            'validations' => $validations,
        ]);
    }

    /**
     * @param EmailSender $emailSender
     * @param array $values
     * @param Orders $order
     * @param string $uniqueId
     * @return bool
     */
    private function sendEmail(EmailSender $emailSender, array $values, Orders $order, string $uniqueId): bool
    {
        $contactEmails = explode(';', $_ENV['CONTACT_EMAIL']);
        $secretEmails = explode(';', $_ENV['SECRET_EMAIL']);
        $price = $order->getPrice();
        $result = $emailSender->send(
            [$values['email']],
            'Megrendelés megerősítése',
            'emails/order.html.twig',
            [
                'firstname' => $values['firstname'],
                'productName' => $order->getProductName(),
                'price' => $price,
                'uniqueId' => $uniqueId,
                'contactEmail' => reset($contactEmails),
                'contactPhone' => $_ENV['CONTACT_PHONE'],
                'productType' => $order->getProductType(),
                'formData' => $order->getDataSheet(),
            ]
        );

        $adminResult = $emailSender->send(
            $contactEmails,
            'Új primecv.hu rendelés érkezett',
            'emails/admin/order.html.twig',
            [
                'values' => $values,
                'createdDate' => new \DateTime(),
                'productName' => $order->getProductName(),
                'price' => $price,
                'coupon' => $order->getCoupon(),
                'uniqueId' => $uniqueId,
                'productType' => $order->getProductType(),
                'formData' => $order->getDataSheet(),
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
     * @param SettingsRepository $settingsRepository
     * @return Orders|null
     */
    private function createOrder(
        SessionInterface       $session,
        PackagesRepository     $packagesRepository,
        EntityManagerInterface $entityManager,
        CouponRepository       $couponRepository,
        SettingsRepository     $settingsRepository
    ): ?Orders
    {
        $checkoutData = $session->get('checkout_data');
        $sessionProduct = $session->get('product');
        $productId = $sessionProduct['id'];

        $productName = null;
        $originalPrice = null;
        if ($sessionProduct['type'] === 'package') {
            $package = $packagesRepository->find($productId);
            $packagePrice = $package->getPrice();
            if ($package->getDiscountPrice() !== null && $package->getDiscountPrice() < $packagePrice) {
                $packagePrice = $package->getDiscountPrice();
            }
            $originalPrice = $packagePrice;

            match ($package->getLevel()) {
                'beginner' => $packageLevel = 'Pályakezdő',
                'junior' => $packageLevel = 'Junior',
                'medior' => $packageLevel = 'Medior',
                'senior' => $packageLevel = 'Senior',
                'leader' => $packageLevel = 'Vezető',
            };
            $productName = $package->getTitle() . ' ' . $packageLevel . ' önéletrajz készítés';
        } elseif ($sessionProduct['type'] === 'analysis') {
            $productName = $sessionProduct['name'];
            $originalPrice = $settingsRepository->findValueByKey('analysis_price');
        }

        if ($productName === null || $originalPrice === null) {
            return null;
        }

        $uniqueId = uniqid();
        $couponCode = $session->get('coupon');
        $couponText = null;
        $price = $originalPrice;
        if (!empty($couponCode)) {
            $processedCoupon = CouponProcessor::calculatePrices($couponCode, $originalPrice, $couponRepository);
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
            $productName,
            $sessionProduct['type'],
            $originalPrice,
            $price,
            new \DateTime(),
            new \DateTime(),
            false
        );

        if ($couponText) {
            $order->setCoupon($couponText);
        }

        if ($sessionProduct['type'] === 'analysis') {
            $order->setDataSheet($session->get('analysisFormData'));
        }

        $entityManager->persist($order);
        $entityManager->flush();

        $session->set('order_custom_id', $uniqueId);
        return $order;
    }

    /**
     * @param Orders $order
     * @param string $productSku
     * @return string|null
     * @throws BarionException
     */
    private function callBarion(Orders $order, string $productSku): ?string
    {
        $item = new ItemModel();
        $item->Name = $order->getProductName();
        $item->Quantity = 1;
        $item->Unit = "db";
        $item->UnitPrice = $order->getPrice();
        $item->ItemTotal = $order->getPrice();
        $item->SKU = $productSku;
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
<?php

namespace App\Model\Coupon;

use App\Repository\CouponRepository;
use App\Repository\PackagesRepository;

class CouponProcessor
{
    /**
     * @param string $couponCode
     * @param int $originalPrice
     * @param CouponRepository $couponRepository
     * @return array
     */
    public static function calculatePrices(string $couponCode, int $originalPrice, CouponRepository $couponRepository): array
    {
        $coupon = $couponRepository->findByCode($couponCode);
        if ($coupon) {
            $discountType = $coupon->getType();
            $discountValue = $coupon->getValue();

            if ($discountType === 'fixed') {
                $couponText = 'Kuponkedvezmény (' . $couponCode . '): - ' . number_format($discountValue, 0, ',', '.') . ' Ft';
                $newPrice = $originalPrice - $discountValue;
            } elseif ($discountType === 'percentage') {
                $couponText = 'Kuponkedvezmény (' . $couponCode . '): - ' . number_format($discountValue, 0, ',', '.') . ' %';
                $newPrice = $originalPrice * (1 - $discountValue / 100);
            } else {
                return [
                    'valid' => false,
                    'message' => 'Érvénytelen kuponkód.'
                ];
            }
            if ($newPrice < 0) {
                $newPrice = 0;
            }

            return [
                'valid' => true,
                'originalPriceFormatted' => number_format($originalPrice, 0, ',', '.') . ' Ft',
                'newPriceFormatted' => number_format($newPrice, 0, ',', '.') . ' Ft',
                'newPriceValue' => $newPrice,
                'couponText' => $couponText
            ];
        }

        return [
            'valid' => false,
            'message' => 'Érvénytelen kuponkód.'
        ];
    }
}
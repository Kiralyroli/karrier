<?php

namespace App\Entity;

use App\Repository\OrdersRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrdersRepository::class)]
class Orders
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $custom_id;

    #[ORM\Column(length: 255)]
    private string $lastname;

    #[ORM\Column(length: 255)]
    private string $firstname;

    #[ORM\Column(length: 255)]
    private string $email;

    #[ORM\Column(length: 255)]
    private string $phone;

    #[ORM\Column(length: 255)]
    private string $country;

    #[ORM\Column(length: 255)]
    private string $zipcode;

    #[ORM\Column(length: 255)]
    private string $city;

    #[ORM\Column(length: 255)]
    private string $address;

    #[ORM\Column(length: 255)]
    private string $product_name;

    #[ORM\Column(length: 255)]
    private string $product_type;

    #[ORM\Column]
    private int $product_price;

    #[ORM\Column]
    private int $price;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $created;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updated;

    #[ORM\Column]
    private bool $success;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coupon;

    #[ORM\Column]
    private array $data_sheet;

    /**
     * @param string $custom_id
     * @param string $lastname
     * @param string $firstname
     * @param string $email
     * @param string $phone
     * @param string $country
     * @param string $zipcode
     * @param string $city
     * @param string $address
     * @param string $product_name
     * @param string $product_type
     * @param int $product_price
     * @param int $price
     * @param \DateTimeInterface $created
     * @param \DateTimeInterface $updated
     * @param bool $success
     */
    public function __construct(
        string             $custom_id,
        string             $lastname,
        string             $firstname,
        string             $email,
        string             $phone,
        string             $country,
        string             $zipcode,
        string             $city,
        string             $address,
        string             $product_name,
        string             $product_type,
        int                $product_price,
        int                $price,
        \DateTimeInterface $created,
        \DateTimeInterface $updated,
        bool               $success
    ) {
        $this->custom_id = $custom_id;
        $this->lastname = $lastname;
        $this->firstname = $firstname;
        $this->email = $email;
        $this->phone = $phone;
        $this->country = $country;
        $this->zipcode = $zipcode;
        $this->city = $city;
        $this->address = $address;
        $this->product_name = $product_name;
        $this->product_type = $product_type;
        $this->product_price = $product_price;
        $this->price = $price;
        $this->created = $created;
        $this->updated = $updated;
        $this->success = $success;
        $this->coupon = null;
        $this->data_sheet = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomId(): string
    {
        return $this->custom_id;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getZipcode(): string
    {
        return $this->zipcode;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getProductName(): string
    {
        return $this->product_name;
    }

    public function getProductType(): string
    {
        return $this->product_type;
    }

    public function getProductPrice(): int
    {
        return $this->product_price;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function getCreated(): \DateTimeInterface
    {
        return $this->created;
    }

    public function getUpdated(): \DateTimeInterface
    {
        return $this->updated;
    }

    public function setUpdated(\DateTimeInterface $updated): void
    {
        $this->updated = $updated;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): void
    {
        $this->success = $success;
    }

    public function getCoupon(): ?string
    {
        return $this->coupon;
    }

    public function setCoupon(?string $coupon): void
    {
        $this->coupon = $coupon;
    }

    public function getDataSheet(): array
    {
        return $this->data_sheet;
    }

    public function setDataSheet(array $data_sheet): void
    {
        $this->data_sheet = $data_sheet;
    }
}

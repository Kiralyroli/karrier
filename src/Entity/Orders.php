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
    private string $package_level;

    #[ORM\Column(length: 255)]
    private string $package_title;

    #[ORM\Column]
    private int $package_price;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $created;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updated;

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
     * @param string $package_level
     * @param string $package_title
     * @param int $package_price
     * @param \DateTimeInterface $created
     * @param \DateTimeInterface $updated
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
        string             $package_level,
        string             $package_title,
        int                $package_price,
        \DateTimeInterface $created,
        \DateTimeInterface $updated)
    {
        $this->custom_id = $custom_id;
        $this->lastname = $lastname;
        $this->firstname = $firstname;
        $this->email = $email;
        $this->phone = $phone;
        $this->country = $country;
        $this->zipcode = $zipcode;
        $this->city = $city;
        $this->address = $address;
        $this->package_level = $package_level;
        $this->package_title = $package_title;
        $this->package_price = $package_price;
        $this->created = $created;
        $this->updated = $updated;
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

    public function getPackageLevel(): string
    {
        return $this->package_level;
    }

    public function getPackageTitle(): string
    {
        return $this->package_title;
    }

    public function getPackagePrice(): int
    {
        return $this->package_price;
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
}

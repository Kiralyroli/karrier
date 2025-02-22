<?php

namespace App\Entity;

use App\Repository\PackagesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PackagesRepository::class)]
class Packages
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $level;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column]
    private int $price;

    #[ORM\Column(type: Types::JSON)]
    private array $features;

    /**
     * @param string $type
     * @param string $title
     * @param int $price
     * @param array $features
     */
    public function __construct(string $type, string $title, int $price, array $features)
    {
        $this->level = $type;
        $this->title = $title;
        $this->price = $price;
        $this->features = $features;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getLevel(): string
    {
        return $this->level;
    }

    /**
     * @param string $level
     * @return void
     */
    public function setLevel(string $level): void
    {
        $this->level = $level;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return void
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return int
     */
    public function getPrice(): int
    {
        return $this->price;
    }

    /**
     * @param int $price
     * @return void
     */
    public function setPrice(int $price): void
    {
        $this->price = $price;
    }

    /**
     * @return array
     */
    public function getFeatures(): array
    {
        return $this->features;
    }

    /**
     * @param array $features
     * @return void
     */
    public function setFeatures(array $features): void
    {
        $this->features = $features;
    }
}

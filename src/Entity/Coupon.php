<?php

namespace App\Entity;

use App\Repository\CouponRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CouponRepository::class)]
class Coupon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $code;

    #[ORM\Column(length: 255)]
    private string $type;

    #[ORM\Column]
    private int $value;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_from;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_to;

    #[ORM\Column]
    private bool $status;

    /**
     * @param string $code
     * @param string $type
     * @param int $value
     * @param bool $status
     */
    public function __construct(string $code, string $type, int $value, bool $status)
    {
        $this->code = $code;
        $this->type = $type;
        $this->value = $value;
        $this->date_from = null;
        $this->date_to = null;
        $this->status = $status;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @param string|null $code
     */
    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     */
    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return int|null
     */
    public function getValue(): ?int
    {
        return $this->value;
    }

    /**
     * @param int|null $value
     */
    public function setValue(?int $value): void
    {
        $this->value = $value;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateFrom(): ?\DateTimeInterface
    {
        return $this->date_from;
    }

    /**
     * @param \DateTimeInterface|null $date_from
     */
    public function setDateFrom(?\DateTimeInterface $date_from): void
    {
        $this->date_from = $date_from;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateTo(): ?\DateTimeInterface
    {
        return $this->date_to;
    }

    /**
     * @param \DateTimeInterface|null $date_to
     */
    public function setDateTo(?\DateTimeInterface $date_to): void
    {
        $this->date_to = $date_to;
    }

    /**
     * @return bool
     */
    public function getStatus(): bool
    {
        return $this->status;
    }

    /**
     * @param bool $status
     */
    public function setStatus(bool $status): void
    {
        $this->status = $status;
    }
}

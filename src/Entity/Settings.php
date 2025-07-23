<?php

namespace App\Entity;

use App\Repository\SettingsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SettingsRepository::class)]
class Settings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 255)]
    private string $settingKey;

    #[ORM\Column(length: 255)]
    private string $value;

    /**
     * @param string $settingKey
     * @param string $value
     */
    public function __construct(string $settingKey, string $value)
    {
        $this->settingKey = $settingKey;
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getSettingKey(): string
    {
        return $this->settingKey;
    }

    /**
     * @param string $settingKey
     */
    public function setSettingKey(string $settingKey): void
    {
        $this->settingKey = $settingKey;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}

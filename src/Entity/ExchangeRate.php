<?php

namespace App\Entity;

use App\Repository\ExchangeRateRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExchangeRateRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_currency_date', columns: ['currency_id', 'date'])]
class ExchangeRate
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'exchangeRates')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Currency $currency = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 4)]
    private string $value = '0.0000';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 4, nullable: true)]
    private ?string $vunitRate = null;

    public function getId(): ?int { return $this->id; }
    public function getCurrency(): ?Currency { return $this->currency; }
    public function setCurrency(?Currency $currency): self { $this->currency = $currency; return $this; }
    public function getDate(): ?\DateTimeInterface { return $this->date; }
    public function setDate(\DateTimeInterface $date): self { $this->date = $date; return $this; }
    public function getValue(): string { return $this->value; }
    public function setValue(string $value): self { $this->value = $value; return $this; }
    public function getVunitRate(): ?string { return $this->vunitRate; }
    public function setVunitRate(?string $vunitRate): self { $this->vunitRate = $vunitRate; return $this; }
}
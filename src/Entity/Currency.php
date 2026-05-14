<?php

namespace App\Entity;

use App\Repository\CurrencyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CurrencyRepository::class)]
class Currency
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private string $code = '';

    #[ORM\Column(length: 100)]
    private string $name = '';

    #[ORM\Column]
    private int $nominal = 1;

    #[ORM\OneToMany(mappedBy: 'currency', targetEntity: ExchangeRate::class, orphanRemoval: true)]
    private Collection $exchangeRates;

    public function __construct()
    {
        $this->exchangeRates = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function setCode(string $code): self { $this->code = $code; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getNominal(): int { return $this->nominal; }
    public function setNominal(int $nominal): self { $this->nominal = $nominal; return $this; }
    /** @return Collection<int, ExchangeRate> */
    public function getExchangeRates(): Collection { return $this->exchangeRates; }
    public function addExchangeRate(ExchangeRate $rate): self
    {
        if (!$this->exchangeRates->contains($rate)) {
            $this->exchangeRates->add($rate);
            $rate->setCurrency($this);
        }
        return $this;
    }
    public function removeExchangeRate(ExchangeRate $rate): self
    {
        if ($this->exchangeRates->removeElement($rate)) {
            if ($rate->getCurrency() === $this) {
                $rate->setCurrency(null);
            }
        }
        return $this;
    }
}
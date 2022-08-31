<?php

namespace App\Entity;

use App\Repository\TaxRateRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TaxRateRepository::class)
 */
class TaxRate
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $percent;

    /**
     * @ORM\ManyToOne(targetEntity=City::class, inversedBy="taxRates")
     */
    private $city;

    /**
     * @ORM\ManyToOne(targetEntity=Profession::class, inversedBy="taxRates")
     */
    private $profession;

    public function __toString(): string
    {
        return $this->getCity()->getName() .' - '. $this->getProfession()->getName() .' - '. $this->percent * 100 . '%';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPercent(): ?float
    {
        return $this->percent;
    }

    public function setPercent(?float $percent): self
    {
        $this->percent = $percent;

        return $this;
    }

    public function getCity(): ?City
    {
        return $this->city;
    }

    public function setCity(?City $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getProfession(): ?Profession
    {
        return $this->profession;
    }

    public function setProfession(?Profession $profession): self
    {
        $this->profession = $profession;

        return $this;
    }
}

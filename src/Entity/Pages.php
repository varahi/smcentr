<?php

namespace App\Entity;

use App\Repository\PagesRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PagesRepository::class)
 */
class Pages
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $oferta;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $privacy;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOferta(): ?string
    {
        return $this->oferta;
    }

    public function setOferta(?string $oferta): self
    {
        $this->oferta = $oferta;

        return $this;
    }

    public function getPrivacy(): ?string
    {
        return $this->privacy;
    }

    public function setPrivacy(?string $privacy): self
    {
        $this->privacy = $privacy;

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Repository\ProfessionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ProfessionRepository::class)
 */
class Profession
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity=Order::class, mappedBy="profession")
     */
    private $orders;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Profession", inversedBy="children")
     * @ORM\JoinColumn(name="parent", referencedColumnName="id", nullable=true)
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Profession", mappedBy="parent")
     */
    private $children;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, mappedBy="professions")
     */
    private $users;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isHidden = false;

    /**
     * @ORM\OneToMany(targetEntity=JobType::class, mappedBy="profession", cascade={"persist", "remove"})
     */
    private $jobTypes;

    /**
     * @ORM\OneToMany(targetEntity=TaxRate::class, mappedBy="profession", cascade={"persist", "remove"})
     */
    private $taxRates;

    public function __construct()
    {
        $this->jobType = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->jobTypes = new ArrayCollection();
        $this->taxRates = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name;
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): self
    {
        if (!$this->orders->contains($order)) {
            $this->orders[] = $order;
            $order->setProfession($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): self
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getProfession() === $this) {
                $order->setProfession(null);
            }
        }

        return $this;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return Collection<int, self>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children[] = $child;
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): self
    {
        if ($this->children->removeElement($child)) {
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->addProfession($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            $user->removeProfession($this);
        }

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function isIsHidden(): ?bool
    {
        return $this->isHidden;
    }

    public function setIsHidden(bool $isHidden): self
    {
        $this->isHidden = $isHidden;

        return $this;
    }

    /**
     * @return Collection<int, JobType>
     */
    public function getJobTypes(): Collection
    {
        return $this->jobTypes;
    }

    public function addJobType(JobType $jobType): self
    {
        if (!$this->jobTypes->contains($jobType)) {
            $this->jobTypes[] = $jobType;
            $jobType->setProfession($this);
        }

        return $this;
    }

    public function removeJobType(JobType $jobType): self
    {
        if ($this->jobTypes->removeElement($jobType)) {
            // set the owning side to null (unless already changed)
            if ($jobType->getProfession() === $this) {
                $jobType->setProfession(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TaxRate>
     */
    public function getTaxRates(): Collection
    {
        return $this->taxRates;
    }

    public function addTaxRate(TaxRate $taxRate): self
    {
        if (!$this->taxRates->contains($taxRate)) {
            $this->taxRates[] = $taxRate;
            $taxRate->setProfession($this);
        }

        return $this;
    }

    public function removeTaxRate(TaxRate $taxRate): self
    {
        if ($this->taxRates->removeElement($taxRate)) {
            // set the owning side to null (unless already changed)
            if ($taxRate->getProfession() === $this) {
                $taxRate->setProfession(null);
            }
        }

        return $this;
    }
}

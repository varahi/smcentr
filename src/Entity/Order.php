<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OrderRepository::class)
 * @ORM\Table(name="`order`")
 */
class Order
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @ORM\Column(type="float")
     */
    private $price;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $level;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $phone;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $deadline;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="orders")
     */
    private $users;

    /**
     * @ORM\ManyToOne(targetEntity=Profession::class, inversedBy="orders")
     */
    private $profession;

    /**
     * @ORM\ManyToOne(targetEntity=JobType::class, inversedBy="orders")
     */
    private $jobType;

    /**
     * @ORM\ManyToOne(targetEntity=City::class, inversedBy="orders")
     */
    private $city;

    /**
     * @ORM\ManyToOne(targetEntity=District::class, inversedBy="orders")
     */
    private $district;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="assignments")
     */
    private $performer;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $closed;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $address;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $estimatedTime;

    /**
     * @ORM\OneToMany(targetEntity=Notification::class, mappedBy="application", cascade={"persist", "remove"})
     */
    private $notifications;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $customTaxRate;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $sendOwnMasters;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $sendAllMasters;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $typeCreated;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $clearOrder;

    public function __construct()
    {
        $this->created = new \DateTime();
        $this->notifications = new ArrayCollection();
    }

    public function __toString(): string
    {
        return 'ID Заказа - ' . $this->id .' - '. $this->jobType->getName() .' - ' . $this->profession->getName();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

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

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(string $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getDeadline(): ?\DateTimeInterface
    {
        return $this->deadline;
    }

    public function setDeadline(?\DateTimeInterface $deadline): self
    {
        $this->deadline = $deadline;

        return $this;
    }

    public function getUsers(): ?User
    {
        return $this->users;
    }

    public function setUsers(?User $users): self
    {
        $this->users = $users;

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

    public function getJobType(): ?JobType
    {
        return $this->jobType;
    }

    public function setJobType(?JobType $jobType): self
    {
        $this->jobType = $jobType;

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

    public function getDistrict(): ?District
    {
        return $this->district;
    }

    public function setDistrict(?District $district): self
    {
        $this->district = $district;

        return $this;
    }

    public function getPerformer(): ?User
    {
        return $this->performer;
    }

    public function setPerformer(?User $performer): self
    {
        $this->performer = $performer;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getClosed(): ?\DateTimeInterface
    {
        return $this->closed;
    }

    public function setClosed(\DateTimeInterface $closed): self
    {
        $this->closed = $closed;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getEstimatedTime(): ?string
    {
        return $this->estimatedTime;
    }

    public function setEstimatedTime(?string $estimatedTime): self
    {
        $this->estimatedTime = $estimatedTime;

        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): self
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications[] = $notification;
            $notification->setApplication($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): self
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getApplication() === $this) {
                $notification->setApplication(null);
            }
        }

        return $this;
    }

    public function getCustomTaxRate(): ?string
    {
        return $this->customTaxRate;
    }

    public function setCustomTaxRate(?string $customTaxRate): self
    {
        $this->customTaxRate = $customTaxRate;

        return $this;
    }

    public function isSendOwnMasters(): ?bool
    {
        return $this->sendOwnMasters;
    }

    public function setSendOwnMasters(?bool $sendOwnMasters): self
    {
        $this->sendOwnMasters = $sendOwnMasters;

        return $this;
    }

    public function isSendAllMasters(): ?bool
    {
        return $this->sendAllMasters;
    }

    public function setSendAllMasters(?bool $sendAllMasters): self
    {
        $this->sendAllMasters = $sendAllMasters;

        return $this;
    }

    public function getTypeCreated(): ?string
    {
        return $this->typeCreated;
    }

    public function setTypeCreated(?string $typeCreated): self
    {
        $this->typeCreated = $typeCreated;

        return $this;
    }

    public function isClearOrder(): ?bool
    {
        return $this->clearOrder;
    }

    public function setClearOrder(?bool $clearOrder): self
    {
        $this->clearOrder = $clearOrder;

        return $this;
    }
}

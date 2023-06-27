<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=NotificationRepository::class)
 */
class Notification
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $message;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="notifications", cascade={"persist"})
     */
    private $user;

    /**
     * @ORM\OneToMany(targetEntity=Notification::class, mappedBy="user", cascade={"persist"})
     */
    private $notifications;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isRead;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $type;

    /**
     * @ORM\ManyToOne(targetEntity=Order::class, inversedBy="notifications", cascade={"persist"})
     */
    private $application;

    /**
     * @ORM\ManyToOne(targetEntity=NotificationGroup::class, inversedBy="notification", cascade={"persist", "remove"})
     */
    private $notificationGroup;

    public function __construct()
    {
        $this->created = new \DateTime();
    }

    public function __toString(): string
    {
        return $this->created->format('H:i d.m.Y') .' '. $this->message;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function isIsRead(): ?bool
    {
        return $this->isRead;
    }

    public function setIsRead(?int $isRead): self
    {
        $this->isRead = $isRead;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getApplication(): ?Order
    {
        return $this->application;
    }

    public function setApplication(?Order $application): self
    {
        $this->application = $application;

        return $this;
    }

    public function getNotificationGroup(): ?NotificationGroup
    {
        return $this->notificationGroup;
    }

    public function setNotificationGroup(?NotificationGroup $notificationGroup): self
    {
        $this->notificationGroup = $notificationGroup;

        return $this;
    }
}

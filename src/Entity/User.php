<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use DateTime;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @UniqueEntity(fields={"email"}, message="There is already an account with this email")
 * @vich\Uploadable
 * @method string getUserIdentifier()
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true, nullable=true)
     */
    private $username = null;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $password = null;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isVerified = false;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $fullName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $avatar;

    /**
     * @Vich\UploadableField(mapping="user", fileNameProperty="avatar")
     * @var File
     */
    private $avatarFile;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $phone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $doc1;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $doc2;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $doc3;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $balance;

    /**
     * @ORM\OneToMany(targetEntity=Order::class, mappedBy="users", cascade={"persist", "remove"})
     */
    private $orders;

    /**
     * @ORM\ManyToOne(targetEntity=City::class, inversedBy="users")
     */
    private $city;

    /**
     * @ORM\Column(type="boolean")
     */
    private $getNotifications = false;

    /**
     * @ORM\ManyToMany(targetEntity=Profession::class, inversedBy="users")
     */
    private $professions;

    /**
     * @ORM\ManyToMany(targetEntity=JobType::class, inversedBy="users")
     */
    private $jobTypes;

    /**
     * @ORM\OneToMany(targetEntity=Order::class, mappedBy="performer", cascade={"persist", "remove"})
     */
    private $assignments;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\OneToMany(targetEntity=Ticket::class, mappedBy="user", cascade={"persist", "remove"})
     */
    private $ticket;

    /**
     * @ORM\OneToMany(targetEntity=Answer::class, mappedBy="user", cascade={"persist", "remove"})
     */
    private $answers;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isDisabled;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $taxRate;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $serviceTaxRate;

    /**
     * @ORM\ManyToOne(targetEntity=District::class, inversedBy="users")
     */
    private $district;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="companyClients")
     */
    private $client;

    /**
     * @ORM\OneToMany(targetEntity=User::class, mappedBy="client")
     */
    private $companyClients;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="companyMasters")
     */
    private $master;

    /**
     * @ORM\OneToMany(targetEntity=User::class, mappedBy="master")
     */
    private $companyMasters;

    /**
     * @ORM\OneToMany(targetEntity=Notification::class, mappedBy="user", cascade={"persist", "remove"})
     */
    private $notifications;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $responsiblePersonFullName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $responsiblePersonPhone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $responsiblePersonEmail;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $inn;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ogrn;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $checkingAccount;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $bank;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $legalAddress;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $physicalAdress;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $cardNumber;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $cardFullName;

    /**
     * @ORM\OneToMany(targetEntity=Request::class, mappedBy="user", cascade={"persist", "remove"})
     */
    private $requests;

    /**
     * @ORM\OneToMany(targetEntity=Firebase::class, mappedBy="user")
     */
    private $firebases;

    /**
     * @ORM\OneToMany(targetEntity=ResetPasswordRequest::class, mappedBy="user", cascade={"persist", "remove"})
     */
    private $resetPasswordRequest;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $level;

    /**
     * @ORM\OneToMany(targetEntity=Payment::class, mappedBy="user")
     */
    private $payment;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $selector;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
        $this->professions = new ArrayCollection();
        $this->jobTypes = new ArrayCollection();
        $this->assignments = new ArrayCollection();
        $this->created = new \DateTime();
        $this->ticket = new ArrayCollection();
        $this->answers = new ArrayCollection();
        $this->companyClients = new ArrayCollection();
        $this->companyMasters = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->requests = new ArrayCollection();
        $this->firebases = new ArrayCollection();
        $this->resetPasswordRequest = new ArrayCollection();
        $this->payment = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->fullName .' - '. $this->email;
    }

    public function getSelector(): ?string
    {
        return $this->fullName .' ('. $this->email .') ' .$this->phone;
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function setUsername(string $username): self
    {
        //$this->username = $username;

        if (!is_null($username)) {
            $this->username = $username;
        } else {
            $this->email = $username;
        }

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getRoles(): ?array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): ?string
    {
        return (string) $this->password;
    }

    public function setPassword(?string $password): self
    {
        //$this->password = $password;
        if (!is_null($password)) {
            $this->password = $password;
        }
        return $this;
    }

    public function isIsVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;

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

    public function getDoc1(): ?string
    {
        return $this->doc1;
    }

    public function setDoc1(?string $doc1): self
    {
        $this->doc1 = $doc1;

        return $this;
    }

    public function getDoc2(): ?string
    {
        return $this->doc2;
    }

    public function setDoc2(?string $doc2): self
    {
        $this->doc2 = $doc2;

        return $this;
    }

    public function getDoc3(): ?string
    {
        return $this->doc3;
    }

    public function setDoc3(?string $doc3): self
    {
        $this->doc3 = $doc3;

        return $this;
    }

    public function getBalance(): ?string
    {
        return $this->balance;
    }

    public function setBalance(?string $balance): self
    {
        $this->balance = $balance;

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
            $order->setUsers($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): self
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getUsers() === $this) {
                $order->setUsers(null);
            }
        }

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

    public function isGetNotifications(): ?bool
    {
        return $this->getNotifications;
    }

    public function setGetNotifications(bool $getNotifications): self
    {
        $this->getNotifications = $getNotifications;

        return $this;
    }

    /**
     * @return Collection<int, Profession>
     */
    public function getProfessions(): Collection
    {
        return $this->professions;
    }

    public function addProfession(Profession $profession): self
    {
        if (!$this->professions->contains($profession)) {
            $this->professions[] = $profession;
        }

        return $this;
    }

    public function removeProfession(Profession $profession): self
    {
        $this->professions->removeElement($profession);

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
        }

        return $this;
    }

    public function removeJobType(JobType $jobType): self
    {
        $this->jobTypes->removeElement($jobType);

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getAssignments(): Collection
    {
        return $this->assignments;
    }

    public function addAssignment(Order $assignment): self
    {
        if (!$this->assignments->contains($assignment)) {
            $this->assignments[] = $assignment;
            $assignment->setPerformer($this);
        }

        return $this;
    }

    public function removeAssignment(Order $assignment): self
    {
        if ($this->assignments->removeElement($assignment)) {
            // set the owning side to null (unless already changed)
            if ($assignment->getPerformer() === $this) {
                $assignment->setPerformer(null);
            }
        }

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

    /**
     * @return Collection<int, Ticket>
     */
    public function getTicket(): Collection
    {
        return $this->ticket;
    }

    public function addTicket(Ticket $ticket): self
    {
        if (!$this->ticket->contains($ticket)) {
            $this->ticket[] = $ticket;
            $ticket->setUser($this);
        }

        return $this;
    }

    public function removeTicket(Ticket $ticket): self
    {
        if ($this->ticket->removeElement($ticket)) {
            // set the owning side to null (unless already changed)
            if ($ticket->getUser() === $this) {
                $ticket->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Answer>
     */
    public function getAnswers(): Collection
    {
        return $this->answers;
    }

    public function addAnswer(Answer $answer): self
    {
        if (!$this->answers->contains($answer)) {
            $this->answers[] = $answer;
            $answer->setUser($this);
        }

        return $this;
    }

    public function removeAnswer(Answer $answer): self
    {
        if ($this->answers->removeElement($answer)) {
            // set the owning side to null (unless already changed)
            if ($answer->getUser() === $this) {
                $answer->setUser(null);
            }
        }

        return $this;
    }

    public function isIsDisabled(): ?bool
    {
        return $this->isDisabled;
    }

    public function setIsDisabled(?bool $isDisabled): self
    {
        $this->isDisabled = $isDisabled;

        return $this;
    }

    public function getTaxRate(): ?float
    {
        return $this->taxRate;
    }

    public function setTaxRate(?float $taxRate): self
    {
        $this->taxRate = $taxRate;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getServiceTaxRate(): ?float
    {
        return $this->serviceTaxRate;
    }

    /**
     * @param mixed $serviceTaxRate
     */
    public function setServiceTaxRate(?float $serviceTaxRate): void
    {
        $this->serviceTaxRate = $serviceTaxRate;
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


    public function getClient(): ?self
    {
        return $this->client;
    }

    public function setClient(?self $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getCompanyClients(): Collection
    {
        return $this->companyClients;
    }

    public function addCompanyClient(self $companyClient): self
    {
        if (!$this->companyClients->contains($companyClient)) {
            $this->companyClients[] = $companyClient;
            $companyClient->setClient($this);
        }

        return $this;
    }

    public function removeCompanyClient(self $companyClient): self
    {
        if ($this->companyClients->removeElement($companyClient)) {
            // set the owning side to null (unless already changed)
            if ($companyClient->getClient() === $this) {
                $companyClient->setClient(null);
            }
        }

        return $this;
    }

    public function getMaster(): ?self
    {
        return $this->master;
    }

    public function setMaster(?self $master): self
    {
        $this->master = $master;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getCompanyMasters(): Collection
    {
        return $this->companyMasters;
    }

    public function addCompanyMaster(self $companyMaster): self
    {
        if (!$this->companyMasters->contains($companyMaster)) {
            $this->companyMasters[] = $companyMaster;
            $companyMaster->setMaster($this);
        }

        return $this;
    }

    public function removeCompanyMaster(self $companyMaster): self
    {
        if ($this->companyMasters->removeElement($companyMaster)) {
            // set the owning side to null (unless already changed)
            if ($companyMaster->getMaster() === $this) {
                $companyMaster->setMaster(null);
            }
        }

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
            $notification->setUser($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): self
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getUser() === $this) {
                $notification->setUser(null);
            }
        }

        return $this;
    }

    public function getResponsiblePersonFullName(): ?string
    {
        return $this->responsiblePersonFullName;
    }

    public function setResponsiblePersonFullName(?string $responsiblePersonFullName): self
    {
        $this->responsiblePersonFullName = $responsiblePersonFullName;

        return $this;
    }

    public function getResponsiblePersonPhone(): ?string
    {
        return $this->responsiblePersonPhone;
    }

    public function setResponsiblePersonPhone(?string $responsiblePersonPhone): self
    {
        $this->responsiblePersonPhone = $responsiblePersonPhone;

        return $this;
    }

    public function getResponsiblePersonEmail(): ?string
    {
        return $this->responsiblePersonEmail;
    }

    public function setResponsiblePersonEmail(?string $responsiblePersonEmail): self
    {
        $this->responsiblePersonEmail = $responsiblePersonEmail;

        return $this;
    }

    public function getInn(): ?string
    {
        return $this->inn;
    }

    public function setInn(?string $inn): self
    {
        $this->inn = $inn;

        return $this;
    }

    public function getOgrn(): ?string
    {
        return $this->ogrn;
    }

    public function setOgrn(?string $ogrn): self
    {
        $this->ogrn = $ogrn;

        return $this;
    }

    public function getCheckingAccount(): ?string
    {
        return $this->checkingAccount;
    }

    public function setCheckingAccount(?string $checkingAccount): self
    {
        $this->checkingAccount = $checkingAccount;

        return $this;
    }

    public function getBank(): ?string
    {
        return $this->bank;
    }

    public function setBank(?string $bank): self
    {
        $this->bank = $bank;

        return $this;
    }

    public function getLegalAddress(): ?string
    {
        return $this->legalAddress;
    }

    public function setLegalAddress(?string $legalAddress): self
    {
        $this->legalAddress = $legalAddress;

        return $this;
    }

    public function getPhysicalAdress(): ?string
    {
        return $this->physicalAdress;
    }

    public function setPhysicalAdress(?string $physicalAdress): self
    {
        $this->physicalAdress = $physicalAdress;

        return $this;
    }

    public function getCardNumber(): ?string
    {
        return $this->cardNumber;
    }

    public function setCardNumber(?string $cardNumber): self
    {
        $this->cardNumber = $cardNumber;

        return $this;
    }

    public function getCardFullName(): ?string
    {
        return $this->cardFullName;
    }

    public function setCardFullName(?string $cardFullName): self
    {
        $this->cardFullName = $cardFullName;

        return $this;
    }

    public function getAvatarBack(): ?string
    {
        return $this->avatar;
    }

    public function setAvatarBack(string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    /**
     * @param string|null $avatar
     */
    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;
        return $this;
    }

    /**
     * @return File|null
     */
    public function getAvatarFile(): ?File
    {
        return $this->avatarFile;
    }

    /**
     * @param File|null $avatarFile
     */
    public function setAvatarFile(?File $avatarFile = null)
    {
        $this->avatarFile = $avatarFile;
    }

    /**
     * @return Collection<int, Request>
     */
    public function getRequests(): Collection
    {
        return $this->requests;
    }

    public function addRequest(Request $request): self
    {
        if (!$this->requests->contains($request)) {
            $this->requests[] = $request;
            $request->setUser($this);
        }

        return $this;
    }

    public function removeRequest(Request $request): self
    {
        if ($this->requests->removeElement($request)) {
            // set the owning side to null (unless already changed)
            if ($request->getUser() === $this) {
                $request->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Firebase>
     */
    public function getFirebases(): Collection
    {
        return $this->firebases;
    }

    public function addFirebase(Firebase $firebase): self
    {
        if (!$this->firebases->contains($firebase)) {
            $this->firebases[] = $firebase;
            $firebase->setUser($this);
        }

        return $this;
    }

    public function removeFirebase(Firebase $firebase): self
    {
        if ($this->firebases->removeElement($firebase)) {
            // set the owning side to null (unless already changed)
            if ($firebase->getUser() === $this) {
                $firebase->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getResetPasswordRequest(): ArrayCollection
    {
        return $this->resetPasswordRequest;
    }

    /**
     * @param ArrayCollection $resetPasswordRequest
     */
    public function setResetPasswordRequest(ArrayCollection $resetPasswordRequest): void
    {
        $this->resetPasswordRequest = $resetPasswordRequest;
    }

//    public function removeResetPasswordRequest(ResetPasswordRequest $resetPasswordRequest): self
//    {
//        if ($this->requests->removeElement($resetPasswordRequest)) {
//            // set the owning side to null (unless already changed)
//            if ($resetPasswordRequest->getUser() === $this) {
//                $resetPasswordRequest->setUser(null);
//            }
//        }
//
//        return $this;
//    }

    /**
     * @return mixed
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param mixed $level
     */
    public function setLevel($level): void
    {
        $this->level = $level;
    }

    /**
     * @return Collection<int, Payment>
     */
    public function getPayment(): Collection
    {
        return $this->payment;
    }

    public function addPayment(Payment $payment): self
    {
        if (!$this->payment->contains($payment)) {
            $this->payment[] = $payment;
            $payment->setUser($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): self
    {
        if ($this->payment->removeElement($payment)) {
            // set the owning side to null (unless already changed)
            if ($payment->getUser() === $this) {
                $payment->setUser(null);
            }
        }

        return $this;
    }
}

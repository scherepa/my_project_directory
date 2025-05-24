<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @UniqueEntity(fields={"username"}, message="There is already an account with this username")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    const ROLE_ADMIN = 'ROLE_ADMIN';
    const ROLE_REP = 'ROLE_REP';

    const DEFAULT_CURRENCY = 'USD';
    const CURRENCIES = ['USD', 'BTC', 'EUR'];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="bigint", nullable=false)
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $username;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $login_time;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $currency;

    /**
     * @ORM\Column(name="manager_id", type="bigint", nullable=true)
     */
    private $manager_id;


    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $role;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="children")
     * @ORM\JoinColumn(name="manager_id",referencedColumnName="id",onDelete="SET NULL")
     */
    private $manager;

    /**
     * @ORM\OneToMany(targetEntity=User::class, mappedBy="manager", fetch="LAZY")
     */
    private $children;

    /**
     * @ORM\Column(type="decimal", precision=20, scale=8)
     */
    private $pnl;

    /**
     * @ORM\Column(type="decimal", precision=20, scale=8)
     */
    private $equity;


    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->pnl = 0.0;
        $this->equity = 0.0;
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = [$this->getRole()];
        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        foreach (array_unique($roles) as $role) {
            if (!empty($role) && in_array($role, $this->getAvailableRoles(), true)) {
                $this->setRole($role);
            }
        }
        $role = $this->getRole();
        $this->roles = !empty($role) ? [$role] : [];
        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

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

    public function getLoginTime(): ?\DateTimeInterface
    { //last login time
        return $this->login_time;
    }

    public function setLoginTime(?\DateTimeInterface $login_time): self
    {
        $this->login_time = $login_time;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = !in_array($currency, self::CURRENCIES, true) ?
            self::DEFAULT_CURRENCY :
            $currency;

        return $this;
    }

    public function getManagerId(): ?string
    {
        return $this->manager_id;
    }

    public function setManagerId(?string $manager_id): self
    {
        $this->manager_id = $manager_id;

        return $this;
    }

    public function getEquity(): string
    {
        return $this->equity;
    }

    public function setEquity(string $equity): self
    {
        $this->equity = $equity;

        return $this;
    }



    /**
     * @return ?string
     */
    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): self
    {
        if (!empty($role) && !in_array($role, $this->getAvailableRoles(), true)) {
            throw new \InvalidArgumentException("Invalid role: $role");
        }
        $this->role = empty($role) ? null : $role;
        return $this;
    }

    public function getManager(): ?User
    {
        return $this->manager;
    }

    public function getHierarchialManegers(): Collection
    {
        $target = new ArrayCollection();
        $manager = $this->manager;

        while (!empty($manager) && !$target->contains($manager)) {
            $target->add($manager);
            $manager = $manager->getManager();
        }
        return $target;
    }

    public function setManager(?self $manager): self
    {
        // remove this user from its children
        if ($this->manager !== null && $this->manager !== $manager) {
            $this->manager->removeChild($this);
        }
        //dd($manager, $this);
        if (empty($manager)) {
            $this->manager = null;
            return $this;
        }
        /*if ($manager->getRole() !== self::ROLE_REP) {
            dd('role');
            throw new \InvalidArgumentException("Invalid manager role.");
        }
        if ($manager->getId() === $this->getId()) {
            dd('man');
            throw new \InvalidArgumentException("Invalid manager.");
        }
        //dd($this->getChildren(), $this->getChildren()->contains($manager), $this, $manager);
        if ($this->getChildren()->contains($manager)) {
            // dd('sub');
            throw new \InvalidArgumentException("Invalid manager. Subordinate can not be assigned as a manager.");
        }*/
        

        // Set the new manager
        $this->manager = $manager;

        // add this user to its children
        $manager->addChild($this);
        return $this;
    }
    /**
     * @return Collection|self[]
     */
    public function getChildren(): \Doctrine\Common\Collections\Collection
    {
        return $this->children;
    }
    
    public function addChild(self $child): self
    {
        if (!$this->getChildren()->contains($child)) {
            $this->getChildren()->add($child);
        }
        // Set the owning side to null (unless already changed)
        if ($child->getManager() !== $this) {
            $child->setManager($this);
        }
        return $this;
    }

    public function removeChild(self $child): self
    {
        if ($this->getChildren()->contains($child)) {
            $this->children->removeElement($child);
            // Set the owning side to null (unless already changed)
            if ($child->getManager() === $this) {
                $child->setManager(null);
            }
        }
        return $this;
    }

    public static function getAvailableRoles(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_REP
        ];
    }

    public function getPnl(): ?string
    {
        return $this->pnl;
    }

    public function setPnl(string $pnl): self
    {
        $this->pnl = $pnl;

        return $this;
    }
}

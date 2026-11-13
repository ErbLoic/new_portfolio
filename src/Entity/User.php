<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity, ORM\Table(name: 'users'), ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use Timestamps;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column] public ?int $id = null;
    #[ORM\Column] public string $name = '';
    #[ORM\Column(unique: true)] public string $email = '';
    #[ORM\Column(name: 'email_verified_at', type: 'datetime', nullable: true)] public ?\DateTimeInterface $emailVerifiedAt = null;
    #[ORM\Column] public string $password = '';
    #[ORM\Column(name: 'remember_token', length: 100, nullable: true)] public ?string $rememberToken = null;
    /** 'admin' ou 'reader' (schéma Laravel). */
    #[ORM\Column] public string $role = 'reader';

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return 'admin' === $this->role ? ['ROLE_ADMIN', 'ROLE_USER'] : ['ROLE_USER'];
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function eraseCredentials(): void
    {
    }
}

<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity, ORM\Table(name: 'certifications'), ORM\HasLifecycleCallbacks]
class Certification
{
    use Timestamps;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column] public ?int $id = null;
    #[ORM\Column] public string $name = '';
    #[ORM\Column] public string $issuer = '';
    #[ORM\Column(name: 'issued_at', type: 'date', nullable: true)] public ?\DateTimeInterface $issuedAt = null;
    #[ORM\Column(name: 'expires_at', type: 'date', nullable: true)] public ?\DateTimeInterface $expiresAt = null;
    #[ORM\Column(name: 'credential_url', nullable: true)] public ?string $credentialUrl = null;
    #[ORM\Column(name: 'badge_url', nullable: true)] public ?string $badgeUrl = null;
    #[ORM\Column(name: '`order`')] public int $order = 0;
}

<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity, ORM\Table(name: 'companies'), ORM\HasLifecycleCallbacks]
class Company
{
    use Timestamps;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column] public ?int $id = null;
    #[ORM\Column] public string $name = '';
    #[ORM\Column] public string $sector = '';
    #[ORM\Column(type: 'text')] public string $description = '';
    #[ORM\Column] public string $location = '';
    #[ORM\Column(name: 'photo_url', nullable: true)] public ?string $photoUrl = null;

    public function __toString(): string
    {
        return $this->name;
    }
}

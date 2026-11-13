<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity, ORM\Table(name: 'formations'), ORM\HasLifecycleCallbacks]
class Formation
{
    use Timestamps;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column] public ?int $id = null;
    #[ORM\Column] public string $title = '';
    #[ORM\Column] public string $school = '';
    #[ORM\Column(nullable: true)] public ?string $location = null;
    #[ORM\Column(name: 'degree_type', nullable: true)] public ?string $degreeType = null;
    #[ORM\Column(name: 'start_date', type: 'date')] public ?\DateTimeInterface $startDate = null;
    #[ORM\Column(name: 'end_date', type: 'date', nullable: true)] public ?\DateTimeInterface $endDate = null;
    #[ORM\Column(name: 'is_current')] public bool $isCurrent = false;
    #[ORM\Column(type: 'text', nullable: true)] public ?string $description = null;
    #[ORM\Column(name: 'logo_url', nullable: true)] public ?string $logoUrl = null;
    #[ORM\Column(name: 'diploma_url', nullable: true)] public ?string $diplomaUrl = null;
    #[ORM\Column(name: '`order`')] public int $order = 0;

    public function __toString(): string
    {
        return $this->school.' — '.$this->title;
    }
}

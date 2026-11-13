<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity, ORM\Table(name: 'realisation_tags'), ORM\HasLifecycleCallbacks]
class RealisationTag
{
    use Timestamps;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column] public ?int $id = null;
    #[ORM\ManyToOne(inversedBy: 'tags'), ORM\JoinColumn(name: 'realisation_id', nullable: false, onDelete: 'CASCADE')]
    public ?Realisation $realisation = null;
    #[ORM\Column(length: 255)] public string $tag = '';
}

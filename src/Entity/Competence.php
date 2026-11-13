<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity, ORM\Table(name: 'competences'), ORM\HasLifecycleCallbacks]
class Competence
{
    use Timestamps;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column] public ?int $id = null;
    #[ORM\Column(unique: true)] public string $code = '';
    #[ORM\Column] public string $label = '';
    #[ORM\Column] public string $bloc = '';
    #[ORM\Column(type: 'text', nullable: true)] public ?string $description = null;

    public function __toString(): string
    {
        return $this->code.' — '.$this->label;
    }
}

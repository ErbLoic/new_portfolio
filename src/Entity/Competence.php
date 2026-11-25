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
    /** Niveau de maîtrise, 1 (notions) à 5 (expert). */
    #[ORM\Column(type: 'smallint', options: ['default' => 3])] public int $level = 3;
    /** Nom d'icône Bootstrap Icons (ex. bi-filetype-php), optionnel. */
    #[ORM\Column(length: 60, nullable: true)] public ?string $icon = null;

    public function __toString(): string
    {
        return $this->label;
    }
}

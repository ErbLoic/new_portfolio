<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity, ORM\Table(name: 'personal_project_tags')]
class PersonalProjectTag
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] public ?int $id = null;
    #[ORM\ManyToOne(inversedBy: 'tags'), ORM\JoinColumn(name: 'personal_project_id', nullable: false, onDelete: 'CASCADE')]
    public ?PersonalProject $personalProject = null;
    #[ORM\Column(length: 255)] public string $tag = '';
}

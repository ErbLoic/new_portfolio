<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity, ORM\Table(name: 'project_tags'), ORM\HasLifecycleCallbacks]
class ProjectTag
{
    use Timestamps;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column] public ?int $id = null;
    #[ORM\ManyToOne(inversedBy: 'tags'), ORM\JoinColumn(name: 'project_id', nullable: false, onDelete: 'CASCADE')]
    public ?Project $project = null;
    #[ORM\Column(length: 255)] public string $tag = '';
}

<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity, ORM\Table(name: 'personal_project_images'), ORM\HasLifecycleCallbacks]
class PersonalProjectImage
{
    use Timestamps;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column] public ?int $id = null;
    #[ORM\ManyToOne(inversedBy: 'images'), ORM\JoinColumn(name: 'personal_project_id', nullable: false, onDelete: 'CASCADE')]
    public ?PersonalProject $personalProject = null;
    #[ORM\Column(name: 'image_url')] public string $imageUrl = '';
    #[ORM\Column(nullable: true)] public ?string $caption = null;
    #[ORM\Column(type: 'text', nullable: true)] public ?string $description = null;
    #[ORM\Column(name: '`order`')] public int $order = 0;
}

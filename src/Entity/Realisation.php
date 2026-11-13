<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity, ORM\Table(name: 'realisations'), ORM\HasLifecycleCallbacks]
class Realisation implements GalleryOwner
{
    use Timestamps;
    use GalleryOwnerTrait;
    use ProjectDate;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column] public ?int $id = null;
    /** 'stage' ou 'projet' */
    #[ORM\Column] public string $type = 'stage';
    #[ORM\ManyToOne, ORM\JoinColumn(name: 'company_id', nullable: true, onDelete: 'SET NULL')]
    public ?Company $company = null;
    #[ORM\Column] public string $title = '';
    #[ORM\Column(type: 'text')] public string $description = '';
    #[ORM\Column(name: 'long_description', type: 'text', nullable: true)] public ?string $longDescription = null;

    /** @var Collection<int, RealisationTag> */
    #[ORM\OneToMany(targetEntity: RealisationTag::class, mappedBy: 'realisation', cascade: ['persist'], orphanRemoval: true)]
    public Collection $tags;

    /** @var Collection<int, RealisationImage> */
    #[ORM\OneToMany(targetEntity: RealisationImage::class, mappedBy: 'realisation', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['order' => 'ASC', 'id' => 'ASC'])]
    public Collection $images;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->images = new ArrayCollection();
    }

    public function newTag(string $tag): object
    {
        $t = new RealisationTag();
        $t->realisation = $this;
        $t->tag = $tag;

        return $t;
    }

    public function newImage(string $url): object
    {
        $i = new RealisationImage();
        $i->realisation = $this;
        $i->imageUrl = $url;
        $i->order = $this->images->count();
        $this->images->add($i);

        return $i;
    }
}

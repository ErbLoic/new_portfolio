<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity, ORM\Table(name: 'personal_projects'), ORM\HasLifecycleCallbacks]
class PersonalProject implements GalleryOwner
{
    use Timestamps;
    use GalleryOwnerTrait;
    use ProjectDate;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column] public ?int $id = null;
    #[ORM\Column] public string $title = '';
    #[ORM\Column(nullable: true)] public ?string $type = null;
    #[ORM\Column(type: 'text', nullable: true)] public ?string $description = null;
    #[ORM\Column(name: 'long_description', type: 'text', nullable: true)] public ?string $longDescription = null;
    #[ORM\Column(nullable: true)] public ?string $year = null;
    #[ORM\Column(name: 'github_url', length: 500, nullable: true)] public ?string $githubUrl = null;
    #[ORM\Column(name: 'demo_url', length: 500, nullable: true)] public ?string $demoUrl = null;
    #[ORM\Column(name: '`order`')] public int $order = 0;
    #[ORM\Column(name: 'is_featured')] public bool $isFeatured = false;

    /** @var Collection<int, PersonalProjectTag> */
    #[ORM\OneToMany(targetEntity: PersonalProjectTag::class, mappedBy: 'personalProject', cascade: ['persist'], orphanRemoval: true)]
    public Collection $tags;

    /** @var Collection<int, PersonalProjectImage> */
    #[ORM\OneToMany(targetEntity: PersonalProjectImage::class, mappedBy: 'personalProject', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['order' => 'ASC', 'id' => 'ASC'])]
    public Collection $images;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->images = new ArrayCollection();
    }

    public function newTag(string $tag): object
    {
        $t = new PersonalProjectTag();
        $t->personalProject = $this;
        $t->tag = $tag;

        return $t;
    }

    public function newImage(string $url): object
    {
        $i = new PersonalProjectImage();
        $i->personalProject = $this;
        $i->imageUrl = $url;
        $i->order = $this->images->count();
        $this->images->add($i);

        return $i;
    }
}

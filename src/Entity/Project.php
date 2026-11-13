<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity, ORM\Table(name: 'projects'), ORM\HasLifecycleCallbacks]
class Project implements GalleryOwner
{
    use Timestamps;
    use GalleryOwnerTrait;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column] public ?int $id = null;
    #[ORM\ManyToOne, ORM\JoinColumn(name: 'company_id', nullable: true, onDelete: 'SET NULL')]
    public ?Company $company = null;
    /** École / formation dans le cadre de laquelle le projet a été réalisé. */
    #[ORM\ManyToOne, ORM\JoinColumn(name: 'formation_id', nullable: true, onDelete: 'SET NULL')]
    public ?Formation $formation = null;
    #[ORM\Column] public string $title = '';
    #[ORM\Column] public string $type = '';
    #[ORM\Column(type: 'text')] public string $description = '';
    #[ORM\Column(name: 'long_description', type: 'text', nullable: true)] public ?string $longDescription = null;
    #[ORM\Column] public string $year = '';
    /** Lien facultatif vers le code source (http/https uniquement). */
    #[ORM\Column(name: 'github_url', length: 500, nullable: true)]
    #[\Symfony\Component\Validator\Constraints\Url(protocols: ['http', 'https'], requireTld: true, message: 'Lien invalide (ex. https://github.com/toi/projet).')]
    public ?string $githubUrl = null;

    /** @var Collection<int, Competence> */
    #[ORM\ManyToMany(targetEntity: Competence::class)]
    #[ORM\JoinTable(name: 'competence_project')]
    #[ORM\JoinColumn(name: 'project_id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'competence_id', onDelete: 'CASCADE')]
    public Collection $competences;

    /** @var Collection<int, ProjectTag> */
    #[ORM\OneToMany(targetEntity: ProjectTag::class, mappedBy: 'project', cascade: ['persist'], orphanRemoval: true)]
    public Collection $tags;

    /** @var Collection<int, ProjectImage> */
    #[ORM\OneToMany(targetEntity: ProjectImage::class, mappedBy: 'project', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['order' => 'ASC', 'id' => 'ASC'])]
    public Collection $images;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->competences = new ArrayCollection();
    }

    public function newTag(string $tag): object
    {
        $t = new ProjectTag();
        $t->project = $this;
        $t->tag = $tag;

        return $t;
    }

    public function newImage(string $url): object
    {
        $i = new ProjectImage();
        $i->project = $this;
        $i->imageUrl = $url;
        $i->order = $this->images->count();
        $this->images->add($i);

        return $i;
    }
}

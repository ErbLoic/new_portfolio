<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/** Mission réalisée pendant une expérience (alternance, stage), décrite de façon anonymisée. */
#[ORM\Entity, ORM\Table(name: 'missions'), ORM\HasLifecycleCallbacks]
class Mission
{
    use Timestamps;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column] public ?int $id = null;
    #[ORM\ManyToOne, ORM\JoinColumn(name: 'stage_id', nullable: false, onDelete: 'CASCADE')]
    public ?Stage $stage = null;
    #[ORM\Column] public string $title = '';
    #[ORM\Column(type: 'text', nullable: true)] public ?string $context = null;
    #[ORM\Column(type: 'text', nullable: true)] public ?string $actions = null;
    #[ORM\Column(type: 'text', nullable: true)] public ?string $results = null;
    /** Technos séparées par des virgules. */
    #[ORM\Column(nullable: true)] public ?string $tags = null;
    #[ORM\Column(name: '`order`')] public int $order = 0;
    #[ORM\Column(name: 'is_published')] public bool $isPublished = true;

    /** @return list<string> */
    public function tagList(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->tags))));
    }
}

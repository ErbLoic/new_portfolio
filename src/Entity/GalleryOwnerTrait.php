<?php

namespace App\Entity;

/** Tags « séparés par des virgules » pour simplifier les formulaires d'admin. */
trait GalleryOwnerTrait
{
    public function getTags(): \Doctrine\Common\Collections\Collection
    {
        return $this->tags;
    }

    public function getImages(): \Doctrine\Common\Collections\Collection
    {
        return $this->images;
    }

    public function getTagsText(): string
    {
        return implode(', ', $this->tags->map(fn ($t) => $t->tag)->toArray());
    }

    public function setTagsText(?string $text): void
    {
        $wanted = array_values(array_unique(array_filter(array_map('trim', explode(',', (string) $text)))));
        foreach ($this->tags->toArray() as $tag) {
            if (!in_array($tag->tag, $wanted, true)) {
                $this->tags->removeElement($tag);
            }
        }
        $existing = $this->tags->map(fn ($t) => $t->tag)->toArray();
        foreach ($wanted as $label) {
            if (!in_array($label, $existing, true)) {
                $this->tags->add($this->newTag($label));
            }
        }
    }
}

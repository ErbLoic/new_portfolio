<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;

/** Entité possédant des tags et une galerie d'images (projet, réalisation, projet perso). */
interface GalleryOwner
{
    /** @return Collection<int, object> */
    public function getTags(): Collection;

    /** @return Collection<int, object> */
    public function getImages(): Collection;

    public function newTag(string $tag): object;

    public function newImage(string $url): object;
}

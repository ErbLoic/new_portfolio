<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/** Colonnes created_at / updated_at héritées du schéma Laravel. */
trait Timestamps
{
    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $updatedAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt ??= new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }
}

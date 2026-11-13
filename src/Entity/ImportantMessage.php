<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity, ORM\Table(name: 'important_messages'), ORM\HasLifecycleCallbacks]
class ImportantMessage
{
    use Timestamps;

    public const TYPES = ['Information' => 'info', 'Succès' => 'success', 'Attention' => 'warning', 'Urgent' => 'urgent'];

    #[ORM\Id, ORM\GeneratedValue, ORM\Column] public ?int $id = null;
    #[ORM\Column(nullable: true)] public ?string $title = null;
    #[ORM\Column(type: 'text')] public string $message = '';
    #[ORM\Column] public string $type = 'info';
    #[ORM\Column(nullable: true)] public ?string $icon = null;
    #[ORM\Column(name: 'link_url', nullable: true)] public ?string $linkUrl = null;
    #[ORM\Column(name: 'link_text', nullable: true)] public ?string $linkText = null;
    #[ORM\Column(name: 'is_active')] public bool $isActive = true;
    #[ORM\Column(name: '`order`')] public int $order = 0;
    #[ORM\Column(name: 'start_date', type: 'datetime', nullable: true)] public ?\DateTimeInterface $startDate = null;
    #[ORM\Column(name: 'end_date', type: 'datetime', nullable: true)] public ?\DateTimeInterface $endDate = null;

    /** Statut lisible pour l'admin : pourquoi le message est (ou n'est pas) sur le site. */
    public function getStatus(): string
    {
        $now = new \DateTime();

        return match (true) {
            !$this->isActive => '⏸ Désactivé',
            null !== $this->endDate && $this->endDate < $now => '⌛ Expiré le '.$this->endDate->format('d/m/Y'),
            null !== $this->startDate && $this->startDate > $now => '🕒 Programmé au '.$this->startDate->format('d/m/Y'),
            default => '✅ Affiché sur le site',
        };
    }

    /** Même règle que le scope Laravel « active ». */
    public function isVisible(\DateTimeInterface $now = new \DateTime()): bool
    {
        return $this->isActive
            && (null === $this->startDate || $this->startDate <= $now)
            && (null === $this->endDate || $this->endDate >= $now);
    }
}

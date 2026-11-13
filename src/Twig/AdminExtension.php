<?php

namespace App\Twig;

use App\Admin\AdminSections;
use Doctrine\Common\Collections\Collection;
use Twig\Attribute\AsTwigFunction;

/** Petites aides d'affichage pour l'admin. */
final class AdminExtension
{
    #[AsTwigFunction('admin_sections')]
    public function sections(): array
    {
        return AdminSections::SECTIONS;
    }

    #[AsTwigFunction('admin_is_image')]
    public function isImage(?string $url): bool
    {
        return (bool) preg_match('/\.(jpe?g|png|gif|webp|svg)(\?.*)?$/i', (string) $url);
    }

    /** Formate une valeur pour une cellule de tableau. */
    #[AsTwigFunction('admin_value')]
    public function value(mixed $v): string
    {
        return match (true) {
            null === $v || '' === $v => '—',
            is_bool($v) => $v ? 'Oui' : 'Non',
            $v instanceof \DateTimeInterface => $v->format('d/m/Y'),
            $v instanceof Collection => (string) $v->count(),
            is_object($v) && method_exists($v, '__toString') => (string) $v,
            default => mb_strimwidth((string) $v, 0, 70, '…'),
        };
    }
}

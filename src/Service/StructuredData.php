<?php

namespace App\Service;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Données structurées schema.org (JSON-LD) pour Google & co.
 * Elles aident les moteurs à comprendre que la page parle d'une personne
 * (nom, prénom, poste, compétences, employeur, écoles) et à l'associer à ses profils.
 */
final class StructuredData
{
    public function __construct(private readonly UrlGeneratorInterface $urls)
    {
    }

    /** Page d'accueil : site + page de profil + personne. */
    public function home(array $data): array
    {
        $p = $data['portfolio'] ?? null;
        if (!$p) {
            return [];
        }
        $home = $this->urls->generate('home', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $person = $this->person($p, $data);

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebSite',
                    '@id' => $home.'#site',
                    'url' => $home,
                    'name' => $person['name'],
                    'inLanguage' => 'fr-FR',
                    'author' => ['@id' => $person['@id']],
                ],
                [
                    '@type' => 'ProfilePage',
                    '@id' => $home.'#page',
                    'url' => $home,
                    'name' => $person['name'].' — Portfolio',
                    'isPartOf' => ['@id' => $home.'#site'],
                    'mainEntity' => ['@id' => $person['@id']],
                    'hasPart' => $this->workList($data),
                ],
                $person,
            ],
        ];
    }

    /** Page d'un projet / d'une réalisation. */
    public function work(array $item, ?array $owner, string $url): array
    {
        $home = $this->urls->generate('home', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $image = ($item['images'][0]['image_url'] ?? null) ?: ($owner['photo_url'] ?? null);

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'CreativeWork',
            'name' => $item['title'] ?? null,
            'headline' => $item['title'] ?? null,
            'description' => strip_tags((string) ($item['description'] ?? '')) ?: null,
            'url' => $url,
            'image' => $image ? $this->absolute($image) : null,
            'dateCreated' => $item['date'] ?? ($item['year'] ?? null),
            'keywords' => implode(', ', array_column($item['tags'] ?? [], 'tag')) ?: null,
            'codeRepository' => $item['github_url'] ?? null,
            'author' => $owner ? [
                '@type' => 'Person',
                '@id' => $home.'#person',
                'name' => trim($owner['first_name'].' '.$owner['last_name']),
                'url' => $home,
            ] : null,
            'inLanguage' => 'fr-FR',
        ]);
    }

    private function person(array $p, array $data): array
    {
        $home = $this->urls->generate('home', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $first = trim((string) $p['first_name']);
        $last = trim((string) $p['last_name']);

        // Compétences : technologies les plus utilisées dans les projets
        $tags = [];
        foreach (array_merge($data['projects'] ?? [], $data['realisations'] ?? [], $data['personalProjects'] ?? []) as $item) {
            foreach ($item['tags'] ?? [] as $t) {
                $key = mb_strtolower(trim($t['tag']));
                $tags[$key] = ['name' => trim($t['tag']), 'n' => ($tags[$key]['n'] ?? 0) + 1];
            }
        }
        uasort($tags, fn ($a, $b) => $b['n'] <=> $a['n']);

        // Employeur actuel (expérience en cours) et écoles
        $current = null;
        foreach ($data['stages'] ?? [] as $s) {
            if (!empty($s['is_current']) && !empty($s['company']['name'])) {
                $current = ['@type' => 'Organization', 'name' => $s['company']['name']];
                break;
            }
        }
        $schools = [];
        foreach ($data['formations'] ?? [] as $f) {
            if (!empty($f['school'])) {
                $schools[$f['school']] = ['@type' => 'EducationalOrganization', 'name' => $f['school']];
            }
        }

        return array_filter([
            '@type' => 'Person',
            '@id' => $home.'#person',
            'name' => trim("$first $last"),
            'givenName' => $first ?: null,
            'familyName' => $last ?: null,
            // Variantes que les gens tapent dans Google (« Nom Prénom », sans accents…)
            'alternateName' => array_values(array_unique(array_filter([
                trim("$last $first"),
                $this->ascii(trim("$first $last")) !== trim("$first $last") ? $this->ascii(trim("$first $last")) : null,
            ]))) ?: null,
            'jobTitle' => $p['job_title'] ?? null,
            'description' => strip_tags((string) ($p['bio_html'] ?? $p['bio'] ?? '')) ?: null,
            'image' => !empty($p['photo_url']) ? $this->absolute($p['photo_url']) : null,
            'url' => $home,
            'sameAs' => array_values(array_filter([$p['linkedin_url'] ?? null, $p['github_url'] ?? null])) ?: null,
            'address' => !empty($p['location']) ? ['@type' => 'PostalAddress', 'addressLocality' => $p['location']] : null,
            'worksFor' => $current,
            'alumniOf' => array_values($schools) ?: null,
            'knowsAbout' => array_values(array_column(array_slice($tags, 0, 15), 'name')) ?: null,
        ]);
    }

    /** Liste des projets publiés (liens internes pour les moteurs). */
    private function workList(array $data): array
    {
        $parts = [];
        foreach ($data['projects'] ?? [] as $p) {
            $parts[] = ['@type' => 'CreativeWork', 'name' => $p['title'], 'url' => $this->urls->generate('project_show', ['id' => $p['id']], UrlGeneratorInterface::ABSOLUTE_URL)];
        }
        foreach ($data['realisations'] ?? [] as $r) {
            $parts[] = ['@type' => 'CreativeWork', 'name' => $r['title'], 'url' => $this->urls->generate('realisation_show', ['id' => $r['id']], UrlGeneratorInterface::ABSOLUTE_URL)];
        }
        foreach ($data['personalProjects'] ?? [] as $p) {
            $parts[] = ['@type' => 'CreativeWork', 'name' => $p['title'], 'url' => $this->urls->generate('personal_project_show', ['id' => $p['id']], UrlGeneratorInterface::ABSOLUTE_URL)];
        }

        return $parts;
    }

    private function absolute(string $url): string
    {
        if (str_starts_with($url, 'http')) {
            return $url;
        }

        return rtrim($this->urls->generate('home', [], UrlGeneratorInterface::ABSOLUTE_URL), '/').'/'.ltrim($url, '/');
    }

    private function ascii(string $s): string
    {
        return \Symfony\Component\String\u($s)->ascii()->toString();
    }
}

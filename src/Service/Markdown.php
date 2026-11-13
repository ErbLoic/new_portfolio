<?php

namespace App\Service;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\InlinesOnly\InlinesOnlyExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Conversion Markdown → HTML pour les textes saisis dans l'admin.
 * Le HTML saisi est échappé et les liens dangereux (javascript:…) sont retirés.
 */
final class Markdown
{
    private const CONFIG = [
        'html_input' => 'escape',
        'allow_unsafe_links' => false,
        'max_nesting_level' => 20,
        // Un retour à la ligne simple reste un retour à la ligne (comme avant l'éditeur)
        'renderer' => ['soft_break' => "<br>\n"],
    ];

    private ?MarkdownConverter $block = null;
    private ?MarkdownConverter $inline = null;

    /** Paragraphes, listes, titres, tableaux… */
    public function block(?string $text): string
    {
        if (null === $text || '' === trim($text)) {
            return '';
        }
        $this->block ??= $this->converter(false);

        return trim($this->block->convert($text)->getContent());
    }

    /** Gras, italique, liens, code : pour les cartes et les bandeaux sur une ligne. */
    public function inline(?string $text): string
    {
        if (null === $text || '' === trim($text)) {
            return '';
        }
        $this->inline ??= $this->converter(true);

        return trim($this->inline->convert($text)->getContent());
    }

    private function converter(bool $inlineOnly): MarkdownConverter
    {
        $env = new Environment(self::CONFIG);
        if ($inlineOnly) {
            $env->addExtension(new InlinesOnlyExtension());
            $env->addExtension(new AutolinkExtension());
        } else {
            $env->addExtension(new CommonMarkCoreExtension());
            $env->addExtension(new GithubFlavoredMarkdownExtension());
        }

        return new MarkdownConverter($env);
    }
}

<?php

namespace App\Service;

use Aws\S3\S3Client;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\ByteString;

/**
 * Stockage des fichiers envoyés depuis l'admin.
 * - Cloudflare R2 si les variables CLOUDFLARE_R2_* sont renseignées (comme l'ancienne API) ;
 * - sinon dossier public/uploads (pratique en développement).
 */
final class FileStorage
{
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    public const DOCUMENT_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];
    public const MAX_SIZE = 5 * 1024 * 1024; // 5 Mo

    private ?S3Client $client = null;

    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads')] private readonly string $localDir,
        #[Autowire('%env(default::CLOUDFLARE_R2_ACCESS_KEY_ID)%')] private readonly ?string $key,
        #[Autowire('%env(default::CLOUDFLARE_R2_SECRET_ACCESS_KEY)%')] private readonly ?string $secret,
        #[Autowire('%env(default::CLOUDFLARE_R2_BUCKET)%')] private readonly ?string $bucket,
        #[Autowire('%env(default::CLOUDFLARE_R2_ENDPOINT)%')] private readonly ?string $endpoint,
        #[Autowire('%env(default::CLOUDFLARE_R2_PUBLIC_URL)%')] private readonly ?string $publicUrl,
        /** Bundle de certificats (utile sous Windows quand PHP n'en a pas) ; vide = celui du système. */
        #[Autowire('%env(default::SSL_CA_BUNDLE)%')] private readonly ?string $caBundle = null,
    ) {
    }

    public function usesR2(): bool
    {
        return (bool) ($this->key && $this->secret && $this->bucket && $this->endpoint && $this->publicUrl);
    }

    /**
     * Envoie le fichier et retourne son URL publique.
     *
     * @throws \InvalidArgumentException si le fichier est refusé
     */
    public function upload(UploadedFile $file, string $folder, bool $isDocument = false): string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $allowed = $isDocument ? self::DOCUMENT_EXTENSIONS : self::IMAGE_EXTENSIONS;
        if (!in_array($ext, $allowed, true)) {
            throw new \InvalidArgumentException(sprintf('Extension « %s » non autorisée (%s).', $ext, implode(', ', $allowed)));
        }
        if ($file->getSize() > self::MAX_SIZE) {
            throw new \InvalidArgumentException('Fichier trop volumineux (5 Mo maximum).');
        }

        $folder = preg_replace('/[^a-z0-9_-]/', '', strtolower($folder));
        $filename = sprintf('%s_%s.%s', date('Ymd_His'), ByteString::fromRandom(8)->toString(), $ext);

        if ($this->usesR2()) {
            $this->client()->putObject([
                'Bucket' => $this->bucket,
                'Key' => "$folder/$filename",
                'SourceFile' => $file->getPathname(),
                'ContentType' => $file->getMimeType() ?: 'application/octet-stream',
            ]);

            return rtrim($this->publicUrl, '/')."/$folder/$filename";
        }

        $file->move("{$this->localDir}/$folder", $filename);

        return "/uploads/$folder/$filename";
    }

    /** Supprime un fichier précédemment envoyé (silencieux si introuvable ou externe). */
    public function delete(?string $url): void
    {
        if (!$url) {
            return;
        }
        try {
            if ($this->usesR2() && str_starts_with($url, rtrim($this->publicUrl, '/').'/')) {
                $path = ltrim(substr($url, strlen(rtrim($this->publicUrl, '/'))), '/');
                $this->client()->deleteObject(['Bucket' => $this->bucket, 'Key' => $path]);
            } elseif (str_starts_with($url, '/uploads/') && !str_contains($url, '..')) {
                @unlink(dirname($this->localDir).$url);
            }
        } catch (\Throwable) {
            // Une suppression ratée ne doit pas bloquer l'admin
        }
    }

    private function client(): S3Client
    {
        return $this->client ??= new S3Client([
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => $this->endpoint,
            'credentials' => ['key' => $this->key, 'secret' => $this->secret],
            'use_path_style_endpoint' => false,
            // La vérification SSL reste active : on indique seulement où trouver les certificats
            'http' => $this->caBundle ? ['verify' => $this->caBundle] : [],
        ]);
    }
}

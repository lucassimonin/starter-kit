<?php

namespace App\Service;

use App\Entity\Media;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Upload d'images : stockage dans public/uploads/media,
 * génération automatique d'une variante WebP et d'une miniature 480px (GD).
 */
class MediaUploader
{
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    private const THUMB_WIDTH = 480;

    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly string $uploadDir,
    ) {
    }

    public function upload(UploadedFile $file, string $alt = ''): Media
    {
        $mime = $file->getMimeType() ?? '';
        if (!\in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new \InvalidArgumentException(sprintf('Format non autorisé (%s). Formats acceptés : JPEG, PNG, GIF, WebP, SVG.', $mime));
        }

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0775, true);
        }

        $safeName = strtolower($this->slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))->toString());
        $extension = $file->guessExtension() ?: 'bin';
        $base = sprintf('%s-%s', $safeName ?: 'media', substr(bin2hex(random_bytes(4)), 0, 8));
        $filename = $base.'.'.$extension;

        $media = new Media();
        $media->setOriginalName($file->getClientOriginalName())
            ->setAlt($alt !== '' ? $alt : str_replace('-', ' ', $safeName))
            ->setMimeType($mime)
            ->setSize($file->getSize() ?: 0)
            ->setFilename($filename);

        $file->move($this->uploadDir, $filename);
        $fullPath = $this->uploadDir.'/'.$filename;

        // Dimensions + variantes (hors SVG)
        if ('image/svg+xml' !== $mime && \function_exists('imagecreatefromstring')) {
            $info = @getimagesize($fullPath);
            if ($info) {
                $media->setWidth($info[0])->setHeight($info[1]);
            }

            $source = @imagecreatefromstring((string) file_get_contents($fullPath));
            if ($source instanceof \GdImage) {
                imagesavealpha($source, true);

                // Variante WebP pleine taille
                if (\function_exists('imagewebp') && 'image/webp' !== $mime) {
                    $webpName = $base.'.webp';
                    if (@imagewebp($source, $this->uploadDir.'/'.$webpName, 82)) {
                        $media->setWebpFilename($webpName);
                    }
                }

                // Miniature 480px (pour la bibliothèque admin)
                $w = imagesx($source);
                $h = imagesy($source);
                if ($w > self::THUMB_WIDTH) {
                    $th = (int) round($h * (self::THUMB_WIDTH / $w));
                    $thumb = imagecreatetruecolor(self::THUMB_WIDTH, $th);
                    imagealphablending($thumb, false);
                    imagesavealpha($thumb, true);
                    imagecopyresampled($thumb, $source, 0, 0, 0, 0, self::THUMB_WIDTH, $th, $w, $h);
                    $thumbName = $base.'-thumb.webp';
                    $ok = \function_exists('imagewebp')
                        ? @imagewebp($thumb, $this->uploadDir.'/'.$thumbName, 78)
                        : @imagejpeg($thumb, $this->uploadDir.'/'.($thumbName = $base.'-thumb.jpg'), 80);
                    if ($ok) {
                        $media->setThumbFilename($thumbName);
                    }
                    imagedestroy($thumb);
                }
                imagedestroy($source);
            }
        }

        return $media;
    }

    public function remove(Media $media): void
    {
        foreach ([$media->getFilename(), $media->getWebpFilename(), $media->getThumbFilename()] as $name) {
            if ($name && is_file($this->uploadDir.'/'.$name)) {
                @unlink($this->uploadDir.'/'.$name);
            }
        }
    }
}

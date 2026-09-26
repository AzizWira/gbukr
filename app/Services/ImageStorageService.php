<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageStorageService
{
    public function storeOptimized(UploadedFile $file, string $directory, string $disk = 'local', int $maxDimension = 1800, int $quality = 82): array
    {
        $mime = strtolower((string) $file->getMimeType());
        if (!str_starts_with($mime, 'image/')) {
            $path = $file->store($directory, $disk);
            return ['path' => $path, 'mime_type' => $mime ?: 'application/octet-stream', 'size' => Storage::disk($disk)->size($path)];
        }

        $sourcePath = $file->getRealPath();
        $image = $this->open($sourcePath, $mime);
        if (!$image || !function_exists('imagewebp')) {
            $path = $file->store($directory, $disk);
            return ['path' => $path, 'mime_type' => $mime, 'size' => Storage::disk($disk)->size($path)];
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxDimension / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $transparent);
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        $tmp = tempnam(sys_get_temp_dir(), 'gbukr-img-');
        if ($tmp === false || !imagewebp($canvas, $tmp, $quality)) {
            if (is_resource($image) || $image instanceof \GdImage) imagedestroy($image);
            if (is_resource($canvas) || $canvas instanceof \GdImage) imagedestroy($canvas);
            $path = $file->store($directory, $disk);
            return ['path' => $path, 'mime_type' => $mime, 'size' => Storage::disk($disk)->size($path)];
        }

        $filename = Str::uuid() . '.webp';
        $path = trim($directory, '/') . '/' . $filename;
        Storage::disk($disk)->put($path, file_get_contents($tmp));
        @unlink($tmp);
        imagedestroy($image);
        imagedestroy($canvas);

        return ['path' => $path, 'mime_type' => 'image/webp', 'size' => Storage::disk($disk)->size($path)];
    }

    private function open(string $path, string $mime): \GdImage|false
    {
        return match ($mime) {
            'image/jpeg', 'image/jpg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($path) : false,
            'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($path) : false,
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
    }
}

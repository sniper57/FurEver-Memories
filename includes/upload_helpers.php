<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Gumlet\ImageResize;
use Gumlet\ImageResizeException;

function ensure_upload_path(string $folder): string
{
    $path = rtrim(UPLOAD_DIR, '/\\') . '/' . trim($folder, '/\\');
    if (!is_dir($path)) {
        mkdir($path, 0775, true);
    }
    return $path;
}

function uploaded_file_is_valid(array $file): bool
{
    return !empty($file['tmp_name']) && is_uploaded_file($file['tmp_name']) && (int)($file['error'] ?? UPLOAD_ERR_OK) === UPLOAD_ERR_OK;
}

function save_optimized_image(array $file, string $folder, string $prefix = 'img'): ?string
{
    if (!uploaded_file_is_valid($file)) {
        return null;
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = mime_content_type($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Unsupported image type. Only JPG, PNG, and WEBP are allowed.');
    }

    $ext = $allowed[$mime];
    $dir = ensure_upload_path($folder);
    $filename = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $fullPath = $dir . '/' . $filename;

    try {
        $image = new ImageResize($file['tmp_name']);
        $image->resizeToBestFit(2200, 2200);
        $quality = 88;
        while ($quality >= 45) {
            $image->save($fullPath, $ext === 'png' ? IMAGETYPE_PNG : ($ext === 'webp' ? IMAGETYPE_WEBP : IMAGETYPE_JPEG), $quality);
            clearstatcache(true, $fullPath);
            if (file_exists($fullPath) && filesize($fullPath) <= DEFAULT_IMAGE_TARGET_MAX_BYTES) {
                break;
            }
            $quality -= 5;
        }
        if (!file_exists($fullPath)) {
            throw new RuntimeException('Image save failed.');
        }
    } catch (ImageResizeException $e) {
        throw new RuntimeException('Image resize failed: ' . $e->getMessage());
    }

    return trim($folder, '/\\') . '/' . $filename;
}

function save_video_file(array $file, string $folder, int $maxMb = DEFAULT_VIDEO_MAX_MB): ?string
{
    if (!uploaded_file_is_valid($file)) {
        return null;
    }

    $allowed = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowed, true)) {
        throw new RuntimeException('Unsupported video type.');
    }

    if (($file['size'] ?? 0) > ($maxMb * 1024 * 1024)) {
        throw new RuntimeException('Video file exceeds the configured size limit.');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'mp4');
    $dir = ensure_upload_path($folder);
    $filename = 'video_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $fullPath = $dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        throw new RuntimeException('Unable to save uploaded video.');
    }
    return trim($folder, '/\\') . '/' . $filename;
}

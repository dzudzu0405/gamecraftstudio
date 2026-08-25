<?php
namespace App\Services;

use App\Core\Config;
use App\Core\Database;

/**
 * Handles user image uploads (FR-31: map backgrounds made with an outside AI tool).
 *
 * STORAGE RULE: image files live on disk under uploads/, and THE DATABASE ONLY
 * STORES THE PATH. Never put images in a BLOB column - it bloats every backup
 * and drags binary data into every query.
 *
 * Layers of protection:
 *   - Only allowed extensions are accepted.
 *   - The file is verified with getimagesize(), not by trusting its extension
 *     or Content-Type header.
 *   - Files are renamed randomly; the user's original name is never used on disk.
 *   - Images are re-encoded through GD, which strips any foreign data hidden
 *     inside the file (EXIF, embedded code). The EXIF orientation is read
 *     first and baked into the pixels, so phone photos are not left sideways.
 *   - The uploads/ folder already blocks PHP execution via .htaccess.
 */
class Uploader
{
    /** Largest size kept after resizing - still sharp enough to print at A3 */
    public const MAX_WIDTH  = 2400;
    public const MAX_HEIGHT = 2400;

    /** Preview thumbnail width */
    public const THUMB_WIDTH = 480;

    private const MIME_MAP = [
        IMAGETYPE_JPEG => ['jpg',  'image/jpeg'],
        IMAGETYPE_PNG  => ['png',  'image/png'],
        IMAGETYPE_WEBP => ['webp', 'image/webp'],
    ];

    /**
     * Processes one uploaded file.
     *
     * @return array{ok: bool, message: string, asset_id?: int, path?: string}
     */
    public static function handle(array $file, int $userId, ?int $projectId = null, string $kind = 'background'): array
    {
        $err = self::checkUploadError($file);
        if ($err !== null) {
            return ['ok' => false, 'message' => $err];
        }

        $maxSize = (int) Config::get('upload_max_size', 12 * 1024 * 1024);
        if (($file['size'] ?? 0) > $maxSize) {
            return ['ok' => false, 'message' => 'That image is larger than ' . self::human($maxSize) . '. Please shrink it and try again.'];
        }

        $tmp = $file['tmp_name'] ?? '';
        if (!is_uploaded_file($tmp) && !is_file($tmp)) {
            return ['ok' => false, 'message' => 'The uploaded file could not be read.'];
        }

        // Confirm it really is an image rather than trusting the extension
        $info = @getimagesize($tmp);
        if ($info === false || !isset(self::MIME_MAP[$info[2]])) {
            return ['ok' => false, 'message' => 'That file is not a valid JPG, PNG or WEBP image.'];
        }

        [$ext, $mime] = self::MIME_MAP[$info[2]];
        $srcW = (int) $info[0];
        $srcH = (int) $info[1];

        if ($srcW < 400 || $srcH < 300) {
            return ['ok' => false, 'message' => 'That image is too small (' . $srcW . 'x' . $srcH . '). At least 400x300 is needed to print without blurring.'];
        }

        // Reject enormous images that would exhaust memory during re-encoding
        if ($srcW * $srcH > 60000000) {
            return ['ok' => false, 'message' => 'That image has too many pixels. Please keep it under 60 megapixels.'];
        }

        $dir    = self::ensureDir($kind);
        $name   = self::randomName();
        $relDir = $kind . '/' . date('Y/m');

        $mainRel  = $relDir . '/' . $name . '.' . $ext;
        $thumbRel = $relDir . '/' . $name . '_thumb.' . $ext;
        $mainAbs  = $dir . '/' . $name . '.' . $ext;
        $thumbAbs = $dir . '/' . $name . '_thumb.' . $ext;

        // Without GD, copy the file as-is (still safe, it was verified above)
        if (!function_exists('imagecreatetruecolor')) {
            if (!@move_uploaded_file($tmp, $mainAbs) && !@copy($tmp, $mainAbs)) {
                return ['ok' => false, 'message' => 'Could not write to the uploads folder. Please check its permissions (chmod 755).'];
            }
            $outW = $srcW;
            $outH = $srcH;
            $thumbRel = null;
        } else {
            $result = self::reencode($tmp, $info[2], $mainAbs, $thumbAbs, $srcW, $srcH);
            if ($result === null) {
                return ['ok' => false, 'message' => 'The image could not be processed. Please try a different file or format.'];
            }
            [$outW, $outH] = $result;
        }

        $assetId = Database::insert('user_assets', [
            'user_id'       => $userId,
            'project_id'    => $projectId,
            'kind'          => $kind,
            'original_name' => mb_substr((string) ($file['name'] ?? ''), 0, 180),
            'path'          => $mainRel,
            'thumb_path'    => $thumbRel,
            'mime'          => $mime,
            'width'         => $outW,
            'height'        => $outH,
            'size_bytes'    => is_file($mainAbs) ? filesize($mainAbs) : null,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        return [
            'ok'       => true,
            'message'  => 'Image uploaded successfully.',
            'asset_id' => $assetId,
            'path'     => $mainRel,
        ];
    }

    /** Re-draws the image through GD: shrinks if oversized, strips all metadata */
    private static function reencode(string $tmp, int $type, string $mainAbs, string $thumbAbs, int $srcW, int $srcH): ?array
    {
        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($tmp),
            IMAGETYPE_PNG  => @imagecreatefrompng($tmp),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false,
            default        => false,
        };

        if (!$src) {
            return null;
        }

        // Straighten the picture before anything measures it
        $orientation = self::readOrientation($tmp, $type);
        if ($orientation > 1) {
            $src = self::applyOrientation($src, $orientation);

            // A quarter turn swaps the sides, so re-read them rather than trusting
            // the numbers getimagesize() reported for the file on disk
            $srcW = imagesx($src);
            $srcH = imagesy($src);
        }

        // Shrink while keeping the aspect ratio
        $scale = min(1.0, self::MAX_WIDTH / $srcW, self::MAX_HEIGHT / $srcH);
        $outW  = max(1, (int) round($srcW * $scale));
        $outH  = max(1, (int) round($srcH * $scale));

        $main = self::resample($src, $srcW, $srcH, $outW, $outH, $type);
        self::save($main, $mainAbs, $type, 88);

        // Preview thumbnail
        $tScale = min(1.0, self::THUMB_WIDTH / $outW);
        $tW = max(1, (int) round($outW * $tScale));
        $tH = max(1, (int) round($outH * $tScale));
        $thumb = self::resample($main, $outW, $outH, $tW, $tH, $type);
        self::save($thumb, $thumbAbs, $type, 82);

        imagedestroy($src);
        imagedestroy($main);
        imagedestroy($thumb);

        return [$outW, $outH];
    }

    /**
     * Re-drawing through GD throws EXIF away, and the orientation tag with it.
     * A phone photo is usually stored sideways with a tag saying which way is up,
     * so without this the picture arrives rotated or mirrored.
     *
     * The rotation is baked into the pixels here, while the tag is still readable.
     */
    private static function applyOrientation($img, int $orientation)
    {
        // 3 = 180 degrees, 6 = 90 clockwise, 8 = 90 anticlockwise.
        // 2, 4, 5 and 7 are the mirrored versions of the same four.
        $rotate = match ($orientation) {
            3, 4 => 180,
            5, 6 => -90,
            7, 8 => 90,
            default => 0,
        };

        if ($rotate !== 0) {
            $rotated = @imagerotate($img, $rotate, 0);
            if ($rotated !== false) {
                imagedestroy($img);
                $img = $rotated;
            }
        }

        if (in_array($orientation, [2, 4, 5, 7], true) && function_exists('imageflip')) {
            imageflip($img, IMG_FLIP_HORIZONTAL);
        }

        return $img;
    }

    /**
     * Reads the EXIF orientation of a JPEG, 1 when there is nothing to do.
     *
     * Uses the exif extension when the host has it. Plenty of cPanel accounts do
     * not, so the fallback walks the JPEG markers to the APP1 segment and reads
     * tag 0x0112 out of the IFD0 directory by hand.
     */
    private static function readOrientation(string $file, int $type): int
    {
        if ($type !== IMAGETYPE_JPEG) {
            return 1;   // PNG and WEBP carry no orientation tag
        }

        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($file);
            $o    = (int) ($exif['Orientation'] ?? 1);
            return ($o >= 1 && $o <= 8) ? $o : 1;
        }

        return self::readOrientationRaw($file);
    }

    /** The hand-rolled reader used when the exif extension is missing */
    private static function readOrientationRaw(string $file): int
    {
        $fh = @fopen($file, 'rb');
        if (!$fh) {
            return 1;
        }

        try {
            if (fread($fh, 2) !== "\xFF\xD8") {
                return 1;   // not a JPEG
            }

            // Walk the marker segments looking for APP1 (0xFFE1)
            while (!feof($fh)) {
                $marker = fread($fh, 2);
                if (strlen($marker) < 2 || $marker[0] !== "\xFF") {
                    return 1;
                }

                $id = ord($marker[1]);
                if ($id === 0xDA || $id === 0xD9) {
                    return 1;   // image data starts here; no APP1 found
                }

                $sizeBytes = fread($fh, 2);
                if (strlen($sizeBytes) < 2) {
                    return 1;
                }
                $size = unpack('n', $sizeBytes)[1] - 2;
                if ($size < 0) {
                    return 1;
                }

                if ($id !== 0xE1) {
                    fseek($fh, $size, SEEK_CUR);
                    continue;
                }

                $app1 = fread($fh, $size);
                if (!str_starts_with($app1, "Exif\x00\x00")) {
                    return 1;
                }

                return self::orientationFromTiff(substr($app1, 6));
            }
        } finally {
            fclose($fh);
        }

        return 1;
    }

    /** Pulls tag 0x0112 out of IFD0 of a TIFF header */
    private static function orientationFromTiff(string $tiff): int
    {
        if (strlen($tiff) < 8) {
            return 1;
        }

        // Byte order: 'II' little-endian (Intel), 'MM' big-endian (Motorola)
        $little = str_starts_with($tiff, 'II');
        if (!$little && !str_starts_with($tiff, 'MM')) {
            return 1;
        }

        $short = static fn (int $at): int => strlen($tiff) >= $at + 2
            ? unpack($little ? 'v' : 'n', substr($tiff, $at, 2))[1]
            : 0;
        $long  = static fn (int $at): int => strlen($tiff) >= $at + 4
            ? unpack($little ? 'V' : 'N', substr($tiff, $at, 4))[1]
            : 0;

        $ifd = $long(4);
        if ($ifd < 8 || $ifd + 2 > strlen($tiff)) {
            return 1;
        }

        $count = $short($ifd);
        for ($i = 0; $i < $count; $i++) {
            $entry = $ifd + 2 + ($i * 12);
            if ($entry + 12 > strlen($tiff)) {
                break;
            }
            if ($short($entry) !== 0x0112) {
                continue;
            }
            // A SHORT value sits in the first 2 bytes of the 4-byte value field
            $o = $short($entry + 8);
            return ($o >= 1 && $o <= 8) ? $o : 1;
        }

        return 1;
    }
    private static function resample($src, int $sw, int $sh, int $dw, int $dh, int $type)
    {
        $dst = imagecreatetruecolor($dw, $dh);

        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $dw, $dh, $transparent);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dw, $dh, $sw, $sh);
        return $dst;
    }

    private static function save($img, string $path, int $type, int $quality): void
    {
        switch ($type) {
            case IMAGETYPE_PNG:
                imagepng($img, $path, 6);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagewebp')) {
                    imagewebp($img, $path, $quality);
                } else {
                    imagejpeg($img, $path, $quality);
                }
                break;
            default:
                imagejpeg($img, $path, $quality);
        }
        @chmod($path, 0644);
    }

    private static function checkUploadError(array $file): ?string
    {
        $code = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        return match ($code) {
            UPLOAD_ERR_OK        => null,
            UPLOAD_ERR_INI_SIZE,
            UPLOAD_ERR_FORM_SIZE => 'That image exceeds the server upload limit. Please choose a smaller file.',
            UPLOAD_ERR_PARTIAL   => 'The upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_FILE   => 'No file was selected.',
            UPLOAD_ERR_NO_TMP_DIR=> 'The server is missing its temporary folder. Please contact your host.',
            UPLOAD_ERR_CANT_WRITE=> 'The server could not write the file. Please check folder permissions.',
            default              => 'Upload failed (error code ' . $code . ').',
        };
    }

    /** Creates uploads/{kind}/YYYY/MM if it does not exist yet */
    private static function ensureDir(string $kind): string
    {
        $base = (string) Config::get('upload_dir', dirname(__DIR__, 2) . '/uploads');
        $dir  = $base . '/' . $kind . '/' . date('Y/m');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    private static function randomName(): string
    {
        return date('Ymd') . '_' . bin2hex(random_bytes(8));
    }

    private static function human(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        return round($bytes / 1024) . ' KB';
    }

    /** Removes an image from disk and from the database */
    public static function delete(int $assetId, int $userId): bool
    {
        $asset = Database::first(
            'SELECT * FROM user_assets WHERE id = ? AND user_id = ? LIMIT 1',
            [$assetId, $userId]
        );
        if (!$asset) {
            return false;
        }

        $base = (string) Config::get('upload_dir', dirname(__DIR__, 2) . '/uploads');
        foreach (['path', 'thumb_path'] as $col) {
            $rel = trim((string) ($asset[$col] ?? ''));
            if ($rel !== '') {
                $abs = $base . '/' . ltrim($rel, '/');
                if (is_file($abs)) {
                    @unlink($abs);
                }
            }
        }

        // Unlink it from any project still using it
        Database::run('UPDATE projects SET background_id = NULL WHERE background_id = ?', [$assetId]);
        Database::delete('user_assets', ['id' => $assetId]);
        return true;
    }

    /** Total bytes of images owned by one user */
    public static function usageBytes(int $userId): int
    {
        return (int) Database::value(
            'SELECT COALESCE(SUM(size_bytes), 0) FROM user_assets WHERE user_id = ?',
            [$userId]
        );
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)       return round($bytes / 1024) . ' KB';
        return $bytes . ' B';
    }
}

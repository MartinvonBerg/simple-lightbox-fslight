<?php
/**
 * Robust check: does $href point to an image (local or external)?
 * - No GD/Imagick needed for WEBP/AVIF: signature-based sniff.
 * - Minimizes network calls; caches results; rate-limits per request.
 */
function href_is_image(string $href): bool {
    // 0) Data URI
    if (stripos($href, 'data:image/') === 0) {
        return true;
    }

    // 1) URL valid?
    if (!\wp_http_validate_url($href)) {
        return false;
    }

    // 2) Exclude known non-image providers (oEmbed/video)
    $host = strtolower(parse_url($href, PHP_URL_HOST) ?: '');
    foreach (['youtube.com','youtu.be','vimeo.com','dailymotion.com','soundcloud.com','tiktok.com'] as $nh) {
        if ($host === $nh || ($nh && substr($host, -strlen($nh)) === $nh)) {
            return false;
        }
    }

    // 3) Quick extension whitelist (no network)
    $path = parse_url($href, PHP_URL_PATH) ?: '';
    $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: '');
    $imageExts = ['jpg','jpeg','png','gif','webp','avif','svg','bmp'];
    $hasImageExt = in_array($ext, $imageExts, true);

    // 4) Local vs external
    $siteHost = strtolower(parse_url(\home_url('/'), PHP_URL_HOST) ?: '');
    $isLocal  = ($siteHost !== '' && $host !== '' && strcasecmp($host, $siteHost) === 0);

    if ($isLocal) {
        // Local: WordPress resolver; no HTTP
        $ft = \wp_check_filetype($path);
        if (!empty($ft['type']) && strpos($ft['type'], 'image/') === 0) {
            return true;
        }
        // Optional: filesystem mime if path mapbar
        // $abs = ...; if (is_string($abs) && file_exists($abs)) { $mime = \wp_get_image_mime($abs); if (is_string($mime) && strpos($mime, 'image/') === 0) return true; }
        return $hasImageExt;
    }

    // 5) External: cached?
    $cacheKey = 'is_img_href_' . md5($href);
    $cached   = \get_transient($cacheKey);
    if ($cached === '1') return true;
    if ($cached === '0') return false;

    // Heuristic: good extension → accept & cache
    if ($hasImageExt) {
        \set_transient($cacheKey, '1', DAY_IN_SECONDS * 2);
        return true;
    }

    // 6) Rate-limit remote checks per request
    static $remoteChecks = 0;
    if ($remoteChecks >= 8) {
        \set_transient($cacheKey, '0', MINUTE_IN_SECONDS * 30);
        return false;
    }
    $remoteChecks++;

    $args = [
        'timeout'     => 3,
        'redirection' => 5,
        'user-agent'  => 'WordPress/' . \get_bloginfo('version') . '; ' . \home_url('/'),
        'decompress'  => true,
        'sslverify'   => true,
    ];

    // 7) Try HEAD first
    $resp = \wp_remote_head($href, $args);
    if (!\is_wp_error($resp)) {
        $ctype = \wp_remote_retrieve_header($resp, 'content-type');
        if (\is_array($ctype)) $ctype = implode(', ', $ctype);
        if (\is_string($ctype)) {
            if (stripos($ctype, 'image/') === 0) {
                \set_transient($cacheKey, '1', DAY_IN_SECONDS * 2);
                return true;
            }
            // Some CDNs mislabel images as octet-stream; continue to sniff below.
        }
    }

    // 8) Fallback: GET small range & sniff signatures (WEBP/AVIF included)
    $argsGet = $args;
    $argsGet['headers'] = ['Range' => 'bytes=0-8191']; // 8KB is enough for ftyp & RIFF
    $resp = \wp_remote_get($href, $argsGet);
    if (!\is_wp_error($resp)) {
        $code = \wp_remote_retrieve_response_code($resp);
        if ($code >= 200 && $code < 300) {
            $body = \wp_remote_retrieve_body($resp);
            if (\is_string($body) && strlen($body) > 0) {
                // Strict cap: only inspect first 8KB
                $bytes = substr($body, 0, 8192);

                // 8.1) Prefer native detectors if available
                if (\function_exists('getimagesizefromstring')) {
                    $info = @\getimagesizefromstring($bytes);
                    if (\is_array($info) && !empty($info['mime']) && strpos($info['mime'], 'image/') === 0) {
                        \set_transient($cacheKey, '1', DAY_IN_SECONDS * 2);
                        return true;
                    }
                }
                
                if (\function_exists('finfo_buffer')) {
                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
                    $mime  = @$finfo->buffer($bytes); // statt @\finfo_buffer($finfo, $bytes)
                    if (\is_string($mime) && \strpos($mime, 'image/') === 0) {
                        \set_transient($cacheKey, '1', DAY_IN_SECONDS * 2);
                        return true;
                    }
                }


                // 8.2) Signature-based detection (WEBP/AVIF without GD/Imagick)
                if (is_webp_bytes($bytes) || is_avif_bytes($bytes)) {
                    \set_transient($cacheKey, '1', DAY_IN_SECONDS * 2);
                    return true;
                }

                // 8.3) SVG heuristics (text-based)
                $trim = ltrim($bytes);
                if (stripos($trim, '<svg') !== false || (stripos($trim, '<?xml') === 0 && stripos($trim, '<svg') !== false)) {
                    \set_transient($cacheKey, '1', DAY_IN_SECONDS * 2);
                    return true;
                }
            }
        }
    }

    // 9) Default: not image
    \set_transient($cacheKey, '0', MINUTE_IN_SECONDS * 30);
    return false;
}

/**
 * WEBP signature detection.
 * WEBP files start with RIFF (4 bytes), size (4 bytes), "WEBP" (4 bytes), then VP8/VP8L/VP8X chunks.
 */
function is_webp_bytes(string $bytes): bool {
    if (strlen($bytes) < 12) return false;
    // "RIFF" at 0..3 and "WEBP" at 8..11
    if (substr($bytes, 0, 4) !== "RIFF") return false;
    if (substr($bytes, 8, 4) !== "WEBP") return false;
    // Optional: check for VP8/VP8L/VP8X chunk labels within first 64 bytes
    $chunk = substr($bytes, 12, 4);
    return ($chunk === "VP8 " || $chunk === "VP8L" || $chunk === "VP8X") || (strpos(substr($bytes, 12, 64), "VP8") !== false);
}

/**
 * AVIF signature detection.
 * AVIF is ISO-BMFF/HEIF: initial "ftyp" box with major brand "avif"/"avis" or compatible brand list containing "avif".
 */
function is_avif_bytes(string $bytes): bool {
    if (strlen($bytes) < 12) return false;
    // ISO BMFF: box starts with 4-byte size, then 4-byte type "ftyp".
    $type = substr($bytes, 4, 4);
    if ($type !== 'ftyp') {
        // Sometimes there can be a preceding box, search ftyp in first 128 bytes
        $pos = strpos(substr($bytes, 0, 128), 'ftyp');
        if ($pos === false) return false;
        $start = $pos;
        $major = substr($bytes, $start + 4 + 4, 4);
        $compat = substr($bytes, $start + 4 + 8, 32); // sample compat brands region
        return ($major === 'avif' || $major === 'avis' || strpos($compat, 'avif') !== false);
    }
    $majorBrand = substr($bytes, 8, 4);
    $compatBrands = substr($bytes, 12, 32);
    return ($majorBrand === 'avif' || $majorBrand === 'avis' || strpos($compatBrands, 'avif') !== false);
}

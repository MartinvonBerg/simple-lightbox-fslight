<?php
use PHPUnit\Framework\TestCase;
use function Brain\Monkey\Functions\expect;

/**
 * Dieser Test mockt WordPress-Funktionen, damit href_is_image()
 * deterministisch und ohne echte Netz-Requests getestet werden kann.
 *
 * PHPUnit >= 9.6, PHP >= 8.1
 */

include_once PLUGIN_DIR . '\classes\HrefImageDetection.php';

// Simpler, in-memory Transient-Store für Tests
$GLOBALS['__TEST_TRANSIENTS__'] = [];

if (!function_exists('get_transient')) {
    function get_transient(string $key) {
        if (!isset($GLOBALS['__TEST_TRANSIENTS__'][$key])) return false;
        [$value, $expires] = $GLOBALS['__TEST_TRANSIENTS__'][$key];
        if ($expires !== null && $expires < time()) {
            unset($GLOBALS['__TEST_TRANSIENTS__'][$key]);
            return false;
        }
        return $value;
    }
}
if (!function_exists('set_transient')) {
    function set_transient(string $key, $value, int $expiration = 0): bool {
        $expires = $expiration > 0 ? (time() + $expiration) : null;
        $GLOBALS['__TEST_TRANSIENTS__'][$key] = [$value, $expires];
        return true;
    }
}

if (!function_exists('wp_http_validate_url')) {
    function wp_http_validate_url(string $url): bool {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
if (!function_exists('home_url')) {
    function home_url(string $path = '/'): string {
        return 'https://example.com';
    }
}
if (!function_exists('get_bloginfo')) {
    function get_bloginfo(string $show = ''): string {
        if ($show === 'version') return '6.5.0';
        return '';
    }
}
if (!function_exists('wp_check_filetype')) {
    function wp_check_filetype(string $path): array {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: '');
        $map = [
            'jpg' => 'image/jpeg',
            'jpeg'=> 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp'=> 'image/webp',
            'avif'=> 'image/avif',
            'svg' => 'image/svg+xml',
            'bmp' => 'image/bmp',
        ];
        return ['ext' => $ext, 'type' => $map[$ext] ?? ''];
    }
}

// WP_Error / is_wp_error Minimalstubs
if (!class_exists('WP_Error')) {
    class WP_Error {
        public function __construct(public string $code = '', public string $message = '') {}
    }
}
if (!function_exists('is_wp_error')) {
    function is_wp_error($thing): bool {
        return $thing instanceof WP_Error;
    }
}

// HTTP-Response-Helfer
if (!function_exists('wp_remote_retrieve_header')) {
    function wp_remote_retrieve_header($response, string $name) {
        if (is_wp_error($response)) return null;
        $headers = $response['headers'] ?? [];
        $name = strtolower($name);
        foreach ($headers as $k => $v) {
            if (strtolower($k) === $name) return $v;
        }
        return null;
    }
}
if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response): int {
        if (is_wp_error($response)) return 0;
        return $response['response']['code'] ?? 0;
    }
}
if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response): string {
        if (is_wp_error($response)) return '';
        return (string)($response['body'] ?? '');
    }
}

// Fake HEAD/GET basierend auf URL-Patterns
if (!function_exists('wp_remote_head')) {
    function wp_remote_head(string $url, array $args = []) {
        // Simuliere typische Fälle
        if (str_contains($url, 'image.jpg')) {
            return ['headers' => ['content-type' => 'image/jpeg'], 'response' => ['code' => 200]];
        }
        if (str_contains($url, 'octet-webp')) {
            return ['headers' => ['content-type' => 'application/octet-stream'], 'response' => ['code' => 200]];
        }
        if (str_contains($url, 'badhead-avif')) {
            // Kein Content-Type Header
            return ['headers' => [], 'response' => ['code' => 200]];
        }
        if (str_contains($url, 'svgfile')) {
            // Absichtlich falscher Header, damit Sniffing greift
            return ['headers' => ['content-type' => 'application/octet-stream'], 'response' => ['code' => 200]];
        }
        if (str_contains($url, 'error')) {
            return new WP_Error('http_error', 'Simulated error');
        }
        // Default: octet-stream
        return ['headers' => ['content-type' => 'application/octet-stream'], 'response' => ['code' => 200]];
    }
}
if (!function_exists('wp_remote_get')) {
    function wp_remote_get(string $url, array $args = []) {
        $body = '';
        $code = 200;

        if (str_contains($url, 'octet-webp')) {
            $body = TestBytes::makeWebp();
            $code = 206; // Range unterstützt
        } elseif (str_contains($url, 'badhead-avif')) {
            $body = TestBytes::makeAvif();
            $code = 206;
        } elseif (str_contains($url, 'svgfile')) {
            $body = "<?xml version=\"1.0\"?><svg xmlns=\"http://www.w3.org/2000/svg\"></svg>";
            $code = 200;
        } elseif (str_contains($url, 'notimage.txt')) {
            $body = "hello world";
            $code = 200;
        } else {
            // Neutraler, kleiner Body
            $body = "data";
        }

        return ['headers' => ['content-type' => 'application/octet-stream'], 'response' => ['code' => $code], 'body' => $body];
    }
}

// === Hilfsklasse: Testbytes für WEBP/AVIF ==================================
final class TestBytes {
    public static function makeWebp(): string {
        // "RIFF" + size(4) + "WEBP" + "VP8X" + padding
        return "RIFF" . pack('V', 26) . "WEBP" . "VP8X" . str_repeat("\0", 64);
    }
    public static function makeAvif(): string {
        // size(4) + "ftyp" + major "avif" + compat includes "avif"
        return pack('N', 24) . "ftyp" . "avif" . "avif" . str_repeat("\0", 32);
    }
}

// === Die Tests ===============================================================
final class HrefIsImageTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset Transients vor jedem Test
        $GLOBALS['__TEST_TRANSIENTS__'] = [];
    }

    public function testDataUriIsImage(): void
    {
        $this->assertTrue(href_is_image('data:image/png;base64,iVBORw0KGgo='));
    }

    public function testInvalidUrlReturnsFalse(): void
    {
        $this->assertFalse(href_is_image('javascript:alert(1)'));
    }

    public function testLocalImageByExtensionNoNetwork(): void
    {
        // Gleiches Host wie home_url()
        $this->assertTrue(href_is_image('https://example.com/uploads/pic.webp'));
        $this->assertTrue(href_is_image('https://example.com/uploads/pic.avif'));
        $this->assertTrue(href_is_image('https://example.com/uploads/pic.svg'));
    }

    public function testExternalImageContentTypeHead(): void
    {
        $this->assertTrue(href_is_image('https://cdn.example.org/image.jpg'));
    }

    public function testExternalOctetStreamHeadButWebpSignatureGet(): void
    {
        $this->assertTrue(href_is_image('https://files.example.org/octet-webp'));
    }

    public function testExternalMissingHeaderButAvifSignatureGet(): void
    {
        $this->assertTrue(href_is_image('https://files.example.org/badhead-avif'));
    }

    public function testSvgDetectedByTextHeuristics(): void
    {
        // Keine Extension, HEAD liefert absichtlich octet-stream, Body enthält <svg>
        $this->assertTrue(href_is_image('https://files.example.org/svgfile'));
    }

    public function testNonImageHostYoutubeReturnsFalse(): void
    {
        $this->assertFalse(href_is_image('https://youtube.com/watch?v=abc'));
        $this->assertFalse(href_is_image('https://youtu.be/abc'));
    }

    /**
     * Dieser Test demonstriert das Rate-Limit (max. ~8 Remote-Checks).
     * Wir isolieren den Prozess, damit statische Zähler sauber sind.
     *
     * @runInSeparateProcess
     */
    public function testRateLimitTriggersNegativeResult(): void
    {
        // 8 URLs, die Remote-Checks verursachen (ohne Extension, ohne klare Header)
        for ($i = 0; $i < 8; $i++) {
            $url = "https://rl.example.org/notimage.txt?i={$i}";
            $this->assertFalse(href_is_image($url));
        }
        // 9. Aufruf überschreitet das Limit und sollte ebenfalls false sein (negativer Cache)
        $this->assertFalse(href_is_image('https://rl.example.org/notimage.txt?i=9'));
    }
}

<?php
namespace Tests;
use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use function Brain\Monkey\setUp;
use function Brain\Monkey\tearDown;

abstract class BaseWpTestCase extends TestCase
{
    /** @var array<string, array{value:mixed, expires:?int}> */
    private static array $transients = [];

    protected function setUp(): void
    {
        parent::setUp();
        setUp();

        // === Default-Umfeld ===
        Functions\when('is_feed')->justReturn(false);
        Functions\when('is_trackback')->justReturn(false);

        Functions\when('is_singular')->justReturn(true);
        Functions\when('is_main_query')->justReturn(true);
        Functions\when('in_the_loop')->justReturn(true);

        Functions\when('get_queried_object_id')->justReturn(123);

        // Site/Blog Infos
        Functions\when('home_url')->alias(function (string $path = '/'): string {
            // Einheitlicher Host für "lokal vs extern" Checks
            return 'https://example.com';
        });
        Functions\when('get_bloginfo')->alias(function (string $show = ''): string {
            if ($show === 'version') {
                return '6.5.0';
            }
            return '';
        });

        // --- Transients (In-Memory) ---
        Functions\when('get_transient')->alias(function (string $key) {
            if (!isset(self::$transients[$key])) {
                return false;
            }
            $entry = self::$transients[$key];
            if ($entry['expires'] !== null && $entry['expires'] < time()) {
                unset(self::$transients[$key]);
                return false;
            }
            return $entry['value'];
        });

        Functions\when('set_transient')->alias(function (string $key, $value, int $expiration = 0): bool {
            $expires = $expiration > 0 ? (time() + $expiration) : null;
            self::$transients[$key] = ['value' => $value, 'expires' => $expires];
            return true;
        });

        // --- URL-Validierung ---
        Functions\when('wp_http_validate_url')->alias(function (string $url): bool {
            // Simuliere realistische Validierung
            return (bool)filter_var($url, FILTER_VALIDATE_URL);
        });

        // --- HTTP API Helpers ---
        Functions\when('wp_remote_retrieve_header')->alias(function ($response, string $name) {
            if ($response instanceof \WP_Error) {
                return null;
            }
            $headers = $response['headers'] ?? [];
            $name = strtolower($name);
            foreach ($headers as $k => $v) {
                if (strtolower((string)$k) === $name) {
                    return $v;
                }
            }
            return null;
        });

        Functions\when('wp_remote_retrieve_response_code')->alias(function ($response): int {
            if ($response instanceof \WP_Error) {
                return 0;
            }
            return (int)($response['response']['code'] ?? 0);
        });

        Functions\when('wp_remote_retrieve_body')->alias(function ($response): string {
            if ($response instanceof \WP_Error) {
                return '';
            }
            return (string)($response['body'] ?? '');
        });

        // --- HTTP API: deterministische Stubs für HEAD/GET ---
        // HEAD: liefere Header je nach URL-Pattern
        Functions\when('wp_remote_head')->alias(function (string $url, array $args = []) {
            // Simuliere typische Fälle
            if (str_contains($url, 'image.jpg')) {
                return [
                    'headers'  => ['content-type' => 'image/jpeg'],
                    'response' => ['code' => 200],
                    'body'     => '',
                ];
            }
            if (str_contains($url, 'octet-webp')) {
                // absichtlich falscher Header -> Fallback GET-Sniffing
                return [
                    'headers'  => ['content-type' => 'application/octet-stream'],
                    'response' => ['code' => 200],
                    'body'     => '',
                ];
            }
            if (str_contains($url, 'badhead-avif')) {
                // kein Content-Type Header
                return [
                    'headers'  => [],
                    'response' => ['code' => 200],
                    'body'     => '',
                ];
            }
            if (str_contains($url, 'svgfile')) {
                // falscher Header, damit Sniffing greift
                return [
                    'headers'  => ['content-type' => 'application/octet-stream'],
                    'response' => ['code' => 200],
                    'body'     => '',
                ];
            }
            if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
                return [
                    'headers'  => ['content-type' => 'text/html'],
                    'response' => ['code' => 200],
                    'body'     => '',
                ];
            }
            // Default: neutral
            return [
                'headers'  => ['content-type' => 'application/octet-stream'],
                'response' => ['code' => 200],
                'body'     => '',
            ];
        });

        // GET: liefere kleinen Body mit Signaturen/WebP/AVIF/SVG je nach URL-Pattern
        Functions\when('wp_remote_get')->alias(function (string $url, array $args = []) {
            $code = 200;
            $body = 'data';
            $headers = ['content-type' => 'application/octet-stream'];

            if (str_contains($url, 'octet-webp')) {
                $body = self::makeWebpBytes();
                $code = 206; // Range unterstützt
            } elseif (str_contains($url, 'badhead-avif')) {
                $body = self::makeAvifBytes();
                $code = 206;
            } elseif (str_contains($url, 'svgfile')) {
                $body = "<?xml version=\"1.0\"?><svg xmlns=\"http://www.w3.org/2000/svg\"></svg>";
                $code = 200;
            } elseif (str_contains($url, 'notimage.txt')) {
                $body = "hello world";
                $code = 200;
                $headers = ['content-type' => 'text/plain'];
            }

            return [
                'headers'  => $headers,
                'response' => ['code' => $code],
                'body'     => $body,
            ];
        });

        // Optional: Enqueue-Stubs (falls Tests sie berühren)
        Functions\when('wp_register_script')->justReturn(null);
        Functions\when('wp_enqueue_script')->justReturn(null);
        Functions\when('wp_script_add_data')->justReturn(null);
        Functions\when('wp_register_style')->justReturn(null);
        Functions\when('wp_enqueue_style')->justReturn(null);
        Functions\when('wp_script_is')->alias(fn() => false);
        Functions\when('wp_style_is')->alias(fn() => false);
    }

    protected function tearDown(): void
    {
        tearDown();
        parent::tearDown();
    }

    // ==== Helpers, um pro Test gezielt umzuschalten ====

    /** Simuliere Nicht-Singular (Archive etc.) */
    protected function setNotSingular(): void
    {
        Functions\when('is_singular')->justReturn(false);
    }

    /** Simuliere Nicht-Main-Query (z. B. Sidebar/Secondary Loop) */
    protected function setNotMainQuery(): void
    {
        Functions\when('is_main_query')->justReturn(false);
    }

    /** Simuliere außerhalb des Loops */
    protected function setOutOfLoop(): void
    {
        Functions\when('in_the_loop')->justReturn(false);
    }

    /** Simuliere Admin-Kontext */
    protected function setAdmin(): void
    {
        Functions\when('is_admin')->justReturn(true);
    }

    /**
     * Falls du für einen Test einen eigenen HTTP-Response brauchst,
     * kannst du hiermit wp_remote_head/wp_remote_get temporär überschreiben.
     */
    protected function overrideHttpHead(callable $fn): void
    {
        Functions\when('wp_remote_head')->alias($fn);
    }

    protected function overrideHttpGet(callable $fn): void
    {
        Functions\when('wp_remote_get')->alias($fn);
    }

    // ==== Testbytes für Signatur-Sniffing (WEBP/AVIF) ====

    protected static function makeWebpBytes(): string
    {
        // "RIFF" + size(4) + "WEBP" + "VP8X" + padding
        return "RIFF" . pack('V', 26) . "WEBP" . "VP8X" . str_repeat("\0", 64);
    }

    protected static function makeAvifBytes(): string
    {
        // size(4) + "ftyp" + major "avif" + compat includes "avif"
        return pack('N', 24) . "ftyp" . "avif" . "avif" . str_repeat("\0", 32);
    }
}

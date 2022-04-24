<?php

/**
 *
 * Version:           1.0.0
 * Requires at least: 5.9
 * Requires PHP       7.3
 * Author:            Martin von Berg
 * Author URI:        https://www.berg-reise-foto.de/software-wordpress-lightroom-plugins/wordpress-plugins-fotos-und-gpx/
 * License:           GPL-2.0
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 *  
 */

namespace mvbplugins\fslightbox;

if (!defined('ABSPATH')) die('Are you ok?');

require_once __DIR__ . "/html5-dom-document-php/autoload.php"; 
const ALLOW_DUPLICATE_IDS = 67108864;

/**
 * Class to adopt the images, galleries and media-with-text in the content of a page / post with settings for fslightbox.js
 * 
 * @phpstan-type hrefTypes array{string}
 * @phpstan-type postTypes array{string}
 * @phpstan-type cssClassesToSearch array{string}
 */
final class RewriteFigureTags
{
    // --------------- settings ----------------------------------------
    // PHP 7.3 version :: no type definition
    
    protected $posttype = '';
    protected $siteUrl  = '';
    protected $doRewrite = false;
    protected $hrefEmpty = false;
    protected $hrefMedia = false;
    protected $plugin_main_dir = '';

    protected $hrefTypes = [
        'Empty',
        'Media',
    ];

    protected $postTypes = [
        'page',
        'post',
        //'attachment',
        'home',
        'front',
        //'archive',
        //'date',
        //'author',
        //'tag',
        //'category',
    ];

    protected $cssClassesToSearch = [
        'block-image',
        'media-text',
        'block-video',
        'postie-image'
    ];

    /*
    // PHP 7.4 version 
    protected string $posttype = '';
    protected string $siteUrl  = '';
    protected bool $doRewrite = false;
    protected bool $hrefEmpty = false;
    protected bool $hrefMedia = false;
    protected string $plugin_main_dir = '';
    */
    /**
     * @var hrefTypes
     */
    #protected array $hrefTypes = [ 'Empty', 'Media' ];

    /**
     * @var postTypes
     */
    #protected array $postTypes = [ 'page', 'post',  'home', 'front', ];
    //'attachment', 'archive', 'date', 'author', 'tag', 'category'

    /**
     * @var cssClassesToSearch
     */
    #protected array $cssClassesToSearch = [ 'block-image','media-text', 'block-video', 'postie-image' ];
    

    /**
     * Do settings for the class. Load from json-settings-file.
     */
    public function __construct()
    {
        $this->plugin_main_dir = dirname(__DIR__, 1);
        $this->siteUrl = \get_site_url();
        $this->posttype = strval(\get_post_type());
        $this->doRewrite = in_array($this->posttype, $this->postTypes, true);

        // load settings from file plugin-settings.json
        $path = $this->plugin_main_dir . "/plugin-settings.json"; 
        if (is_file($path)) {
            $settings = strval(file_get_contents($path, false));
            $settings = \json_decode($settings, true);
            $this->hrefTypes = $settings['hrefTypes'];
            $this->postTypes = $settings['postTypes'];
            $this->cssClassesToSearch = $settings['cssClassesToSearch'];
        };

        foreach ($this->hrefTypes as $type) {
            switch (strtolower($type)) {
                case 'empty':
                    $this->hrefEmpty = true;
                    break;
                case 'media':
                    $this->hrefMedia = true;
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * Find the Css-Class from settings in the class-attribute.
     *
     * @param  string $class the class-attribute as string  
     * @return array{bool, bool}
     */
    private function findCssClass(string $class)
    {
        $classFound = false;
        $isVideo = false;
        $search = '';

        foreach ($this->cssClassesToSearch as $search) {
            $classFound = 0;
            $classFound = strpos($class, $search);
            if ($classFound !== false) {
                $classFound = true;
                break;
            }
        }
        $isVideo = ((strpos($search, 'video') !== false) && $classFound) ? true : false; // works because $search is set to last key.
        return [$classFound, $isVideo];
    }

    /**
     * enqueue the fslightbox.js script as basic or paid version, if available
     *
     * @return void
     */
    private function my_enqueue_script()
    {
        $path = $this->plugin_main_dir . '/js/fslightbox-paid/fslightbox.js'; 
        $slug = \WP_PLUGIN_URL . '/' . \basename($this->plugin_main_dir); // @phpstan-ignore-line

        if (is_file($path)) {
            $path = $slug . '/js/fslightbox-paid/fslightbox.js';
            wp_enqueue_script("fslightbox", $path, [], "3.4.1", true);
        }

        $path = $this->plugin_main_dir . '/js/fslightbox-basic/fslightbox.js'; 
        if (is_file($path)) {
            $path = $slug . '/js/fslightbox-basic/fslightbox.js';
            wp_enqueue_script("fslightbox", $path, [], "3.3.1", true);
        }
    }

    /**
     * Adopt the images, galleries and media-with-text in the content of a page / post with settings for fslightbox.js
     *
     * @param  string $content the content of the page / post to adopt with fslightbox
     * @return string the altered $content of the page post to show in browser
     */
    public function lightbox_gallery_for_gutenberg($content)
    {
        if (!$this->doRewrite) return $content;

        $dom = new \IvoPetkov\HTML5DOMDocument();
        $dom->loadHTML($content, ALLOW_DUPLICATE_IDS);

        $allFigures = $dom->querySelectorAll('figure');

        // append all old imgs in <div><a><img>..</img></a></div> to $allFigures. These are converted to figures.
        $allImgs = $dom->querySelectorAll('img');
        foreach ($allImgs as $image) {
            $parent = $image->parentNode->parentNode; // todo: what if there is no a tag? Ignore, because wont't work anyway?
            $tag = $parent->tagName;
            if ($tag === 'div') {
                $allFigures->append($parent);
            }
        }
        $nFound = 0;

        foreach ($allFigures as $figure) {

            $class = $figure->getAttribute("class");
            $tagType = $figure->tagName;
            $classFound = false;
            [$classFound, $isVideo] = $this->findCssClass($class);
            $isMediaFile = false;
            $hasHref = false;
            $item = null;
            $dataType = '';

            // provide item, $dataType, $isMediaFile, $hasHref from $figure, $classFound, $isVideo
            if ($classFound && !$isVideo) {
                $item = $figure->querySelector('img');
                $dataType = 'image';

                $href = null;
                $href = $figure->querySelector("a");
                $hasHref = \is_null($href) ? false : true;

                if ($hasHref) {
                    $href = $href->getAttribute('href');
                    $header = \wp_remote_head($href, array('timeout' => 2));
                    $content_type = wp_remote_retrieve_header($header, 'content-type');
                    $isMediaFile = \strpos($content_type, 'image');
                    $hasSiteUrl = \strpos($href, $this->siteUrl); // only shows lokal files in lightbox
                    $hasSiteUrl = true; // all files are shown, even externals. todo: remove this logic? Or keep for further extension?
                    if (($isMediaFile !== false) && ($hasSiteUrl !== false))
                        $isMediaFile = true;
                }
            } elseif ($isVideo) {
                $item = $figure->querySelector('video');
                $dataType = 'video';
            }

            // create new dom-element and append to dom
            if (($classFound) && (!is_null($item)) && ((!$hasHref && $this->hrefEmpty) || ($isMediaFile && $this->hrefMedia) || $isVideo)) {
                $caption = $figure->querySelector("figcaption");

                $newfigure = $dom->createElement($tagType);
                $newfigure->setAttribute("class", $class);

                $a = $dom->createElement("a");
                $a->setAttribute("data-fslightbox", '1');
                $a->setAttribute("data-type", $dataType);
                if (!is_null($caption)) {
                    $text = $caption->getNodeValue();
                    $a->setAttribute("data-caption", $text);
                }
                $a->setAttribute("href", $item->getAttribute("src"));
                $a->appendChild($item);

                $newfigure->appendChild($a);

                if (!is_null($caption)) {
                    $newfigure->appendChild($caption);
                }

                $figure->parentNode->replaceChild($newfigure, $figure);
                $nFound += 1;
            }
        }

        if ($nFound > 0)
            $this->my_enqueue_script();

        return $dom->saveHTML();
    }
}

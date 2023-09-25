<?php

/**
 *
 * Version:           2.0.0
 * Requires at least: 5.9
 * Requires PHP       7.3
 * Author:            Martin von Berg
 * Author URI:        https://www.berg-reise-foto.de/software-wordpress-lightroom-plugins/wordpress-plugins-fotos-und-gpx/
 * License:           GPL-2.0
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace mvbplugins\fslightbox;

if (!defined('ABSPATH')) {
    die('Are you ok?');
}

require_once __DIR__ . '/html5-dom-document-php/autoload.php';
const ALLOW_DUPLICATE_IDS = 67108864;

/**
 * The Command interface declares a method for executing a command.
 */
interface RewriteFigureTagsInterface
{
    public function execute(): bool;
}

/**
 * Class to adopt the images, galleries and media-with-text in the content of a page / post with settings for fslightbox.js
 *
 * @phpstan-type hrefTypes array{string}
 * @phpstan-type postTypes array{string}
 * @phpstan-type cssClassesToSearch array{string}
 * @phpstan-type excludeIDs array{integer}
 */
final class RewriteFigureTags implements RewriteFigureTagsInterface
{

    // --------------- settings ----------------------------------------
    // PHP 7.3 version :: no type definition

    protected $posttype        = '';
    protected $siteUrl         = '';
    protected $doRewrite       = false;
    protected $hrefEmpty       = false;
    protected $hrefMedia       = false;
    protected $plugin_main_dir = '';

    protected $hrefTypes = array(
        'Empty',
        'Media',
    );

    protected $postTypes = array(
        'page',
        'post',
        // 'attachment',
        'home',
        'front',
        // 'archive',
        // 'date',
        // 'author',
        // 'tag',
        // 'category',
    );

    protected $cssClassesToSearch = array(
        'wp-block-image',
        'wp-block-media-text',
        'wp-block-video',
        'postie-image',
    );

    protected $excludeIds = array();
    private $nFound = 0;
    private $want_to_modify_body = false;
    private $includedTags = array();

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
    // protected array $hrefTypes = [ 'Empty', 'Media' ];

    /**
     * @var postTypes
     */
    // protected array $postTypes = [ 'page', 'post',  'home', 'front', ];
    // 'attachment', 'archive', 'date', 'author', 'tag', 'category'

    /**
     * @var cssClassesToSearch
     */
    // protected array $cssClassesToSearch = [ 'block-image','media-text', 'block-video', 'postie-image' ];

    /**
     * @var excludeIDs
     */
    //  protected array $excludeIds = array();

    /**
     * Do settings for the class and Plugin. Load from json-settings-file.
     */
    public function __construct()
    {
        $this->plugin_main_dir  = dirname(__DIR__, 1);
        $this->siteUrl          = \get_site_url();

        // load and parse settings from file plugin-settings.json in main directory
        $path = $this->plugin_main_dir . '/plugin-settings.json';

        if (is_file($path)) {
            $settings                 = strval(file_get_contents($path, false));
            $settings                 = \json_decode($settings, true);
            $this->hrefTypes          = $settings['hrefTypes'];
            $this->postTypes          = $settings['postTypes'];
            $this->cssClassesToSearch = $settings['cssClassesToSearch'];
            $this->excludeIds         = $settings['excludeIDs'];
            // extract $want_to_modify_body from settings
            if (\key_exists('rewriteScope', $settings)) {
                $this->want_to_modify_body = $settings['rewriteScope'] === 'body';
            }
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
     * Prepares the rewrite for the HTML rewrite of figures.
     *
     * @return bool Returns a boolean indicating whether the rewrite shall be done.
     */
    private function prepare(): bool
    {
        // prepare rewrite only for posts that are in settings and only for front-end
        $postID = (int) \get_the_ID();
        $exclude = \in_array($postID, $this->excludeIds, true);
        $this->posttype         = strval(\get_post_type());
        $this->doRewrite        = in_array($this->posttype, $this->postTypes, true) && !$exclude && !is_admin(); // && !wp_doing_ajax(); TODO : REST-API-request???
        return $this->doRewrite;
    }

    // --------------- Interface ----------------------------------------
    public function execute(): bool
    {
        if (!$this->want_to_modify_body) {
            add_filter('the_content', array($this, 'changeFigureTagsInContent'), 10, 1);
        } else {
            //  here for output buffering.
            $this->changeFigureTagsInBody();
        }
        return true;
    }

    // --------------- public functions called by WP Hooks --------------
    public function changeFigureTagsInContent(string $content): string
    {
        if ($this->prepare()) {
            $content = $this->lightbox_gallery_for_gutenberg($content);
            // include scripts and styles if rewrite was actually done.
            if ($this->nFound > 0) {
                $this->my_enqueue_script();
                $this->my_enqueue_style();
            }
        }
        return $content;
    }

    public function changeFigureTagsInBody()
    {
        add_action('wp_body_open', array($this, 'rewrite_body_buffer_start'), 0); // hook to 'template_redirect' for whole html even html-head
    }

    public function rewrite_body_buffer_start()
    {
        ob_start(array($this, 'rewrite_body_modify_content'));
        add_action('wp_footer', array($this, 'rewrite_body_buffer_stop'), PHP_INT_MAX, 0); // stop at the end of the body tag.
    }

    public function rewrite_body_buffer_stop()
    {
        $status = ob_get_status(true);
        if (!empty($status)) {
            foreach ($status as $s) {
                if (in_array('mvbplugins\fslightbox\RewriteFigureTags::rewrite_body_modify_content', $s, false)) {
                    ob_end_flush();
                }
            }
        }
    }

    public function rewrite_body_modify_content($content)
    {
        //modify $content
        if ($this->prepare()) {
            $content = $this->lightbox_gallery_for_gutenberg($content);
            if ($this->nFound > 0) {
                $content .= $this->rewrite_body_add_scripts();
            }
        }
        return $content;
    }

    /**
     * Adds scripts and styles to the HTML page.
     *
     * @return string The generated HTML code for the scripts and styles.
     */
    public function rewrite_body_add_scripts(): string
    {
        $out = '';

        $slug = \WP_PLUGIN_URL . '/' . \basename($this->plugin_main_dir); // @phpstan-ignore-line

        // check for fslightbox.js free version
        $path = $this->plugin_main_dir . '/js/fslightbox-basic/fslightbox.js';
        if (is_file($path)) {
            $path = $slug . '/js/fslightbox-basic/fslightbox.js';
            $out = "<script defer src='{$path}' id='fslightbox-js'></script>";
        }
        // check for fslightbox.js paid version
        $path = $this->plugin_main_dir . '/js/fslightbox-paid/fslightbox.js';
        if (is_file($path)) {
            $path = $slug . '/js/fslightbox-paid/fslightbox.js';
            $out = "<script defer src='{$path}' id='fslightbox-js'></script>";
        }
        // check for simple-lightbox.min.js script to handle videos
        $path = $this->plugin_main_dir . '/js/simple-lightbox.min.js';
        if (is_file($path)) {
            $path = $slug . '/js/simple-lightbox.min.js';
            $out .= "<script defer src='{$path}' id='yt-script-js'></script>";
        }
        // check for CSS file for simple-fslightbox for YouTube Videos
        $path = $this->plugin_main_dir . '/css/simple-fslightbox.css';
        if (is_file($path)) {
            $path = $slug . '/css/simple-fslightbox.css';
            $out .= "<link rel='stylesheet' id='simple-fslightbox-css' href='{$path}' media='all' />";
        }

        return $out;
    }

    // --------------- private functions : HTML rewriter ------------------------
    /**
     * Adopt the images, galleries and media-with-text in the content of a page / post with settings for fslightbox.js
     *
     * @param  string $content the content of the page / post to adopt with fslightbox
     * @return string the altered $content of the page post to show in browser
     */
    private function lightbox_gallery_for_gutenberg(string $content): string
    {
        if ($this->want_to_modify_body) {
            $this->includedTags['<!DOCTYPE html>'] = strpos($content, '<!DOCTYPE html>') !== false;
            $this->includedTags['<html'] = strpos($content, '<html') !== false;
            $this->includedTags['</html>'] = strpos($content, '</html>') !== false;
            $this->includedTags['<head ']  = strpos($content, '<head ') !== false;
            $this->includedTags['</head>'] = strpos($content, '</head>') !== false;
            $this->includedTags['<body']  = strpos($content, '<body') !== false;
            $this->includedTags['</body>'] = strpos($content, '</body>') !== false;
        }
        // rewrite HTML code with figures
        $dom = new \IvoPetkov\HTML5DOMDocument();
        $dom->loadHTML($content, ALLOW_DUPLICATE_IDS);

        $allFigures = $dom->querySelectorAll('figure');

        // append all old imgs in <div><a><img>..</img></a></div> to $allFigures. These are converted to figures.
        // is done for old images and media-text also!
        $allImgs = $dom->querySelectorAll('img');
        foreach ($allImgs as $image) {
            $parent = $image->parentNode->parentNode; // todo: what if there is no a tag? Ignore, because wont't work anyway?
            $tag    = $parent->tagName;
            $class  = $parent->getAttribute('class');
            if (($tag === 'div') && ($class === 'postie-image-div')) { // TODO: add further classes?
                $allFigures->append($parent);
            }
        }

        $this->nFound = 0;

        foreach ($allFigures as $figure) {

            $class                  = $figure->getAttribute('class');
            $tagType                = $figure->tagName;
            [$classFound, $isVideo, $isEmbed] = $this->findCssClass($class);
            $isMediaFile            = false;
            $hasHref                = false;
            $item                   = null;
            $dataType               = '';
            $videoThumb             = null;
            $hrefParent             = null;

            if (!$classFound) {
                $classInParent = $this->parentFindCssClass($figure);
                $classFound    = $classInParent;
            }

            // provide item, $dataType, $isMediaFile, $hasHref from $figure, $classFound, $isVideo
            if ($classFound && !$isVideo) {
                $item     = $figure->querySelector('img');
                $dataType = 'image';

                $href    = null;
                $href    = $figure->querySelector('a');
                if (!\is_null($href)) {
                    $hrefParent   = $href->parentNode;
                }
                $hasHref = \is_null($href) ? false : true;

                if ($hasHref) {
                    $href         = $href->getAttribute('href');
                    if ($hrefParent->tagName === 'figcaption') {
                        $hasHref = false;
                    }
                    $header       = \wp_remote_head($href, array('timeout' => 2));
                    $content_type = wp_remote_retrieve_header($header, 'content-type');
                    $isMediaFile  = \strpos($content_type, 'image');
                    $hasSiteUrl   = \strpos($href, $this->siteUrl); // only show local files in lightbox (except YouTube videos)
                    $hasSiteUrl   = true; // all files are shown, even externals. todo: remove this logic? Or keep for further extension?
                    if (($isMediaFile !== false) && ($hasSiteUrl !== false)) {
                        $isMediaFile = true;
                    }
                }
            } elseif ($classFound && $isVideo && !$isEmbed) {
                $item     = $figure->querySelector('video');
                $videoThumb   = $item->getAttribute('poster');
                $dataType = 'video';
            } elseif ($classFound && $isVideo && $isEmbed) {
                $item = $figure->querySelector('iframe');
                $dataType = 'video';
            }

            // create new dom-element and append to dom
            if (($classFound) && (!is_null($item)) && ((!$hasHref && $this->hrefEmpty) || ($isMediaFile && $this->hrefMedia) || $isVideo) && !$isEmbed) {
                $caption = $figure->querySelector('figcaption');

                $newfigure = $dom->createElement($tagType);
                $newfigure->setAttribute('class', $class);

                $a = $dom->createElement('a');
                $a->setAttribute('data-fslightbox', '1'); // Mind: This is used in javascript, too!
                $a->setAttribute('data-type', $dataType);
                $a->setAttribute('aria-label', 'Open fullscreen lightbox with current ' . $dataType);

                if (!is_null($caption)) {
                    $text = $caption->getNodeValue();
                    $a->setAttribute('data-caption', $text);
                }

                if (!empty($videoThumb)) {
                    $a->setAttribute('data-thumb', $videoThumb);
                }

                $a->setAttribute('href', $item->getAttribute('src'));
                $a->appendChild($item);

                $newfigure->appendChild($a);

                if (!is_null($caption)) {
                    $newfigure->appendChild($caption);
                }

                $figure->parentNode->replaceChild($newfigure, $figure);
                $this->nFound += 1;
            } elseif (($classFound) && !is_null($item) && $isVideo && $isEmbed) {
                // prepare YouTube Videos here
                $newfigure = $dom->createElement($tagType);
                $newfigure->setAttribute('class', $class);

                $a = $dom->createElement('a');
                $a->setAttribute('data-fslightbox', '1'); // Mind: This is used in javascript, too!
                //$a->setAttribute('data-type', $dataType); // Does not work with YouTube
                $a->setAttribute('aria-label', 'Open fullscreen lightbox with current ' . $dataType);

                $href = $item->getAttribute('src');
                $ytHref = $href;
                $ytHref = \str_replace('youtube.com', 'youtube-nocookie.com', $ytHref);
                $ytHref = \str_replace('feature=oembed', 'feature=oembed&enablejsapi=1', $ytHref);
                $item->setAttribute('src', $ytHref);

                $href = explode('?', $href)[0];
                $a->setAttribute('href', $href);

                // get the ID and thumbnail from img.youtube.com/vi/[Video-ID]/default.jpg. source: https://internetzkidz.de/2021/03/youtube-thumbnail-url/
                $ytID = explode('/', $href);
                $ytID = end($ytID);
                $videoThumbUrl = 'https://img.youtube.com/vi/' . $ytID . '/default.jpg';

                // Use get_headers() function
                $headers = @get_headers($videoThumbUrl);

                // Use condition to check the existence of URL
                if ($headers && strpos($headers[0], '200')) {
                    $a->setAttribute('data-thumb', $videoThumbUrl);
                }

                // create the button to open the lightbox
                $lbdiv = $dom->createElement('div');
                $lbdiv->setAttribute('class', 'yt-button-simple-fslb-mvb');

                $a->appendChild($lbdiv);
                $newfigure->appendChild($a);
                $newfigure->appendChild($item);

                $figure->parentNode->replaceChild($newfigure, $figure);
                $this->nFound += 1;
            }
        }

        if ($this->nFound > 0) {
            $originalContent = $content;
            $content = $dom->saveHTML();

            // remove html, head, body tags if not in original content
            if ($this->want_to_modify_body) {
                foreach ($this->includedTags as $tag => $inOriginal) {

                    if (!$inOriginal && (strpos($content, $tag) !== false)) {
                        $content = str_replace($tag, '', $content);
                    } elseif ($inOriginal && (strpos($content, $tag) === false)) {
                        // new content is wrong: provide original content
                        break;
                        return $originalContent;
                    }
                }
                // final clean-up for closing tags
                $content = str_replace('>>', '', $content);
            }
        }

        return $content;
    }

    /**
     * Find the Css-Class from settings in the class-attribute.
     *
     * @param string $class The class-attribute as a string.
     * @return array{bool,bool} An array containing a boolean indicating whether the class was found
     * and a boolean indicating whether it is a video class.
     */
    private function findCssClass(string $class): array
    {
        $classFound = false;
        $isVideo    = false;
        $isEmbed = false;
        $search     = '';

        foreach ($this->cssClassesToSearch as $search) {
            $classFound = 0;
            $classFound = strpos($class, $search);
            if ($classFound !== false) {
                $classFound = true;
                break;
            }
        }

        if (((strpos($search, 'video') !== false) || (strpos($search, 'youtube') !== false)) && $classFound) { // works only because $search is set to last key after break in for-loop
            $isVideo = true;
        };

        if ($isVideo && (strpos($search, 'youtube') !== false) && $classFound) { // works only because $search is set to last key after break in for-loop
            $isEmbed = true;
        };

        return array($classFound, $isVideo, $isEmbed);
    }

    /**
     * Find the Css-Class in parent of the figure as DOM-Element.
     *
     * @param  object $figure the class-attribute as DOM-Object
     * @return bool
     */
    private function parentFindCssClass(object $figure): bool
    {
        $classFound = false;
        $search     = '';
        $parent     = $figure->parentNode; // @phpstan-ignore-line
        if (is_null($parent)) {
            return $classFound;
        }
        $class      = $parent->getAttribute('class');

        foreach ($this->cssClassesToSearch as $search) {
            $classFound = 0;
            $classFound = strpos($class, $search);
            if ($classFound !== false) {
                $classFound = true;
                break;
            }
        }

        return $classFound;
    }

    // --------------- private functions : Enqueueing ------------------------
    /**
     * Enqueues the fslightbox.js script as basic or paid version, if available.
     *
     * @return void
     */
    private function my_enqueue_script(): void
    {
        $path = $this->plugin_main_dir . '/js/fslightbox-paid/fslightbox.js';
        $slug = \WP_PLUGIN_URL . '/' . \basename($this->plugin_main_dir); // @phpstan-ignore-line

        if (is_file($path)) {
            $path = $slug . '/js/fslightbox-paid/fslightbox.js';
            wp_enqueue_script('fslightbox', $path, array(), '3.6.0', true);
        }

        $path = $this->plugin_main_dir . '/js/fslightbox-basic/fslightbox.js';
        if (is_file($path)) {
            $path = $slug . '/js/fslightbox-basic/fslightbox.js';
            wp_enqueue_script('fslightbox', $path, array(), '3.4.1', true);
        }

        $path = $this->plugin_main_dir . '/js/simple-lightbox.min.js';
        if (is_file($path)) {
            $path = $slug . '/js/simple-lightbox.min.js';
            wp_enqueue_script('yt-script', $path, array('fslightbox'), '2.0.0', true);
        }
    }

    /**
     * Enqueues the simple-fslightbox.css style.
     *
     * @return void
     */
    private function my_enqueue_style(): void
    {
        $path = $this->plugin_main_dir . '/css/simple-fslightbox.css';
        $slug = \WP_PLUGIN_URL . '/' . \basename($this->plugin_main_dir); // @phpstan-ignore-line

        if (is_file($path)) {
            $path = $slug . '/css/simple-fslightbox.css';
            wp_enqueue_style('simple-fslightbox-css', $path, array(), '2.0.0', 'all');
        }
    }
}

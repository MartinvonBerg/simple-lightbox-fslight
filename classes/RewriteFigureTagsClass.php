<?php
/**
 *
 * @wordpress-plugin
 * Plugin Name:       Simple Lightbox for Gutenberg - Responsive Lightbox Effect for Image, Gallery and Media-Text Block.
 * Plugin URI:        https://github.com/MartinvonBerg/simple-lightbox-gutenberg
 * Description:       An easy way to create lightbox effect for WordPress Gutenberg images and galleries. Settings provided with json-File in Plugin-Folder.
 * Version:           0.0.1
 * Requires at least: 5.9
 * Requires PHP       7.3
 * Author:            Martin von Berg
 * Author URI:        https://www.berg-reise-foto.de/software-wordpress-lightroom-plugins/wordpress-plugins-fotos-und-gpx/
 * License:           GPL-2.0
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 *  
 */

// todo: phpdoc, phpstan, phpunit

namespace mvbplugins\fslightbox;

// fallback for wordpress security
if ( ! defined('ABSPATH' )) die('Are you ok?');

require_once \WP_PLUGIN_DIR . "./simple-lightbox-gutenberg/vendor/autoload.php";
const ALLOW_DUPLICATE_IDS = 67108864;

final class RewriteFigureTags
{
    // --------------- settings ----------------------------------------
    // PHP 7.3 version :: no type definition
    protected $posttype = '';
    protected $siteUrl  = '';
    protected $doRewrite = false;
    protected $hrefEmpty = false;
    protected $hrefMedia = false;

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

    public function __construct()
    {
       $this->siteUrl = \get_site_url();
       $this->posttype = \get_post_type();
       $this->doRewrite = in_array($this->posttype, $this->postTypes, true);

       foreach($this->hrefTypes as $type) {
           switch ( strtolower($type)) {
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
       // load settings from file plugin-settings.json
       $path = \WP_PLUGIN_DIR . "./simple-lightbox-gutenberg/plugin-settings.json";
       if (is_file($path)) {
           $settings = file_get_contents( $path, 'plugin-settings.json' );
           $settings = \json_decode( $settings, true );
           $this->hrefTypes = $settings['hrefTypes'];
           $this->postTypes = $settings['postTypes'];
           $this->cssClassesToSearch = $settings['cssClassesToSearch'];
       };
    }

    private function findCssClass($class)
    {
        $classFound = false;
        $isVideo = false;

        foreach($this->cssClassesToSearch as $search) {
            $classFound = 0;
            $classFound =strpos($class, $search); 
            if ($classFound !== false) {
                $classFound = true;
                break;
            }
        }
        $isVideo = ((strpos($search, 'video') !== false) && $classFound) ? true : false;
        return [$classFound, $isVideo];
    }

    private function my_enqueue_script() 
    {
        $path = \WP_PLUGIN_DIR . '/simple-lightbox-gutenberg/js/fslightbox-paid/fslightbox.js';
        if (is_file($path)) {
            $path = \WP_PLUGIN_URL . '/simple-lightbox-gutenberg/js/fslightbox-paid/fslightbox.js';
            wp_enqueue_script( "fslightbox", $path, [], "3.3.1", true );
        }

        $path = \WP_PLUGIN_DIR . '/simple-lightbox-gutenberg/js/fslightbox-basic/fslightbox.js';
        if (is_file($path)) {
            $path = \WP_PLUGIN_URL . '/simple-lightbox-gutenberg/js/fslightbox-basic/fslightbox.js';
            wp_enqueue_script( "fslightbox", $path, [], "3.3.1", true );
        }
    }

    public function lightbox_gallery_for_gutenberg($content)
    {
        if ( ! $this->doRewrite) return $content;

        $dom = new \IvoPetkov\HTML5DOMDocument();
        $dom->loadHTML($content, ALLOW_DUPLICATE_IDS);

        $allFigures = $dom->querySelectorAll('figure');

        // append all old divs to $allFigures. These are converted to figures.
        $allImgs = $dom->querySelectorAll('img');
        foreach($allImgs as $image) {
            $parent = $image->parentNode->parentNode; // todo: what if there is no a tag? Ignore, because wont't work anyway?
            $tag = $parent->tagName;
            if ($tag === 'div') {
                $allFigures->append($parent);
            }
        }
        $nFound = 0;
            
        foreach ($allFigures as $figure) {

            $class = $figure->getAttribute("class");
            $classFound = false;
            [$classFound, $isVideo] = $this->findCssClass($class);
            $isMediaFile = false;
            $hasHref = false;
            $item = null;

            if ($classFound && ! $isVideo) 
            {
                $item = $figure->querySelector( 'img');
                $dataType = 'image';  

                $href = null;
                $href = $figure->querySelector("a");
                $hasHref = \is_null($href) ? false : true;
                            
                if ( $hasHref ) {
                    $href = $href->getAttribute('href');
                    $isMediaFile = \strpos( $href, 'uploads');
                    $hasSiteUrl = \strpos( $href, $this->siteUrl);
                    if ( ($isMediaFile !== false) && ($hasSiteUrl !== false) )
                        $isMediaFile = true;
                }  
            }
            elseif ($classFound && $isVideo) 
            {   
                $item = $figure->querySelector( 'video');
                $dataType = 'video';
            } 
           
            if ( ($classFound) && (! is_null($item)) && ( ( ! $hasHref && $this->hrefEmpty) || ($isMediaFile && $this->hrefMedia) || $isVideo )) {
                $newfigure = $dom->createElement("figure");
                $newfigure->setAttribute("class", $class);
                $a = $dom->createElement("a");
                $a->setAttribute("data-fslightbox", true);
                $a->setAttribute("data-type", $dataType);
                $a->setAttribute("href", $item->getAttribute("src"));
                $a->appendChild($item);
                $newfigure->appendChild($a);

                $caption = $figure->querySelector("figcaption");
                if (! is_null($caption)) {
                    $newfigure->appendChild($caption);
                }
                $figure->parentNode->replaceChild($newfigure, $figure);
                $nFound += 1;   
            } 
        }
        
        if ( $nFound > 0) 
            $this->my_enqueue_script();

        return $dom->saveHTML();  
    }  
}
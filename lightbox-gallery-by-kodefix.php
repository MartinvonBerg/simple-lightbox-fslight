<?php
/**
 *
 * @wordpress-plugin
 * Plugin Name:       Lightbox Gallery by Kodefix - Responsive Lightbox Effect for gallery block
 * Plugin URI:        https://kodefix.pl/
 * Description:       An easy way to create lightbox effect for WordPress new editor's gallery block. Just turn on the plugin and use powerful lightbox.
 * Version:           1.2
 * Author:            Kodefix / Martin von Berg
 * Author URI:        https://kodefix.pl/?utm_source=lightbox-for-gutenberg&utm_medium=plugin&utm_campaign=created_plugins
 * License:           GPL-2.0
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 *  
 * @package           LightboxGalleryForGutenberg
 * @category          Core
 * @author            Kodefix / Martin von Berg
 * 
 */

namespace mvbplugins\fslightbox;

// fallback for wordpress security
if ( ! defined('ABSPATH' )) { die('Are you ok?');}

require_once __DIR__ . "/vendor/autoload.php";
const ALLOW_DUPLICATE_IDS = 67108864;

final class RewriteFigureTags
{
    // --------------- settings ----------------------------------------
    // PHP 7.3 version :: no type definition
    protected $page = true;
    protected $post = true;
    protected $home = true;
    protected $front = true;

    protected $archive = false;
    protected $date = false;
    protected $author = false;
    protected $tag = false;
    protected $category = false;
    protected $attachment = false;
    protected $siteUrl  = '';

    protected $hrefEmpty = true;
    protected $hrefMedia = true;
    protected $hrefAttach = false; // only for information. No Functionality provided.
    protected $hrefExternal = false; // only for information. No Functionality provided.

    protected $cssClassesToSearch = [
        'block-image',
        'media-text',
        'block-video'

    ];

    public function __construct()
    {
       $this->siteUrl = \get_site_url();
       // todo: load settings from json
    }

    private function findCssClass($class)
    {
        $classFound = false;
        $isVideo = false;

        foreach($this->cssClassesToSearch as $search) {
            $classFound = 0;
            $classFound =strpos($class, $search); // todo: include videos
            if ($classFound > 0) {
                $classFound = true;
                break;
            }
        }
        $isVideo = ((strpos($search, 'video') > 0) && $classFound) ? true : false;
        return [$classFound, $isVideo];
    }

    public function lightbox_gallery_for_gutenberg($content)
    {
        $dom = new \IvoPetkov\HTML5DOMDocument();
        $dom->loadHTML($content, ALLOW_DUPLICATE_IDS);

        $allFigures = $dom->querySelectorAll('figure');
        $nFound = 0;
            
        foreach ($allFigures as $figure) {

            $class = $figure->getAttribute("class");
            [$classFound, $isVideo] = $this->findCssClass($class);

            if ($classFound && ! $isVideo) {;

                $item = $figure->querySelector("img");
                if (is_null($item)) $classFound = false;

                $href = '';
                $href = $figure->querySelector("a");
                $hasHref = \is_null($href) ? false : true;
                $isMediaFile = false;
                
                if ( $hasHref ) {
                    $href = $href->getAttribute('href');
                    $isMediaFile = \strpos( $href, 'uploads');
                    $hasSiteUrl = \strpos( $href, $this->siteUrl);
                    if ($isMediaFile > 0 && $hasSiteUrl > 0)
                        $isMediaFile = true;
                }
                    
                if (($classFound > 0) && ( (!$hasHref && $this->hrefEmpty) || ($isMediaFile && $this->hrefMedia) )) {
                    $newfigure = $dom->createElement("figure");
                    $newfigure->setAttribute("class", $class);
                    $a = $dom->createElement("a");
                    $a->setAttribute("data-fslightbox", true);
                    $a->setAttribute("data-type", "image");
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
            } elseif ($classFound && $isVideo) {
                // <figure class="wp-block-video"><video controls preload="auto" src="http://localhost/wordpress/wp-content/uploads/2022/04/sample-mp4-file.mp4"></video></figure>
                $item = $figure->querySelector("video"); // diff
                $newfigure = $dom->createElement("figure");
                $newfigure->setAttribute("class", $class);
                $a = $dom->createElement("a");
                $a->setAttribute("data-fslightbox", true);
                $a->setAttribute("data-type", "video"); // diff
                $a->setAttribute("href", $item->getAttribute("src"));
                $a->appendChild($item);
                $newfigure->appendChild($a);
                $figure->parentNode->replaceChild($newfigure, $figure);
                $nFound += 1;
            }
        }
        
        if ( $nFound > 0) {
            wp_enqueue_script( 
                "engine", 
                plugin_dir_url(__FILE__) . "/js/fslightbox.js", 
                [], 
                "3.3.1", 
                true
            );
        }

        return $dom->saveHTML();  
    }  
}


add_filter("the_content", '\mvbplugins\fslightbox\wrapClass', 10, 1);

function wrapClass($content)
{
    $rewrite = new RewriteFigureTags();
    $new=$rewrite->lightbox_gallery_for_gutenberg($content);
    return $new;
}
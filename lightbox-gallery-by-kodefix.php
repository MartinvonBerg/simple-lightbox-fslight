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
    protected $posttype = '';
    protected $siteUrl  = '';
    protected $doRewrite = false;

    protected $hrefEmpty = false;
    protected $hrefMedia = false;
    protected $hrefAttach = false; // only for information. No Functionality provided.
    protected $hrefExternal = false; // only for information. No Functionality provided.

    protected $hrefTypes = [
        'Empty',
        'Media',
        //'Attachment', // only for information. No Functionality provided.
        //'External', // only for information. No Functionality provided.
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
        'block-video'
    ];

    public function __construct()
    {
       $this->siteUrl = \get_site_url();
       $this->posttype = \get_post_type();
       $this->doRewrite = in_array($this->posttype, $this->postTypes, true);

       foreach($this->hrefTypes as $type) {
           switch ($type) {
                case 'Empty':
                    $this->hrefEmpty = true;
                    break;
                case 'Media':
                    $this->hrefMedia = true;
                    break;
                default:
                    break;
           }
       }
       // todo: load settings from json
    }

    private function findCssClass($class)
    {
        $classFound = false;
        $isVideo = false;

        foreach($this->cssClassesToSearch as $search) {
            $classFound = 0;
            $classFound =strpos($class, $search); 
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
        if ( ! $this->doRewrite) return $content;

        $dom = new \IvoPetkov\HTML5DOMDocument();
        $dom->loadHTML($content, ALLOW_DUPLICATE_IDS);

        $allFigures = $dom->querySelectorAll('figure');
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
                    if ($isMediaFile > 0 && $hasSiteUrl > 0)
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
        
        if ( $nFound > 0) wp_enqueue_script( "fslightbox", plugin_dir_url(__FILE__) . "/js/fslightbox.js", [], "3.3.1", true );

        return $dom->saveHTML();  
    }  
}


add_filter("the_content", '\mvbplugins\fslightbox\wrapClass', 10, 1);

function wrapClass($content)
{
    $rewrite = new RewriteFigureTags();
    $new=$rewrite->lightbox_gallery_for_gutenberg($content);
    $rewrite = null;
    return $new;
}
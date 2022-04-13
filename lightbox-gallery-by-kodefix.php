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

require __DIR__ . "./vendor/autoload.php";

function lightbox_gallery_for_gutenberg($content)
{
    $dom = new \IvoPetkov\HTML5DOMDocument();
    $dom->loadHTML($content);

    //$gallery_block = $dom->querySelectorAll(".wp-block-gallery");
    $allFigures = $dom->querySelectorAll('figure');
        
    foreach ($allFigures as $figure) {
        $item = $figure->querySelector("img");
        $caption = $figure->querySelector("figcaption");
        $class = $figure->getAttribute("class");
        $found =strpos($class, 'gallery');

        if ($found === false) {
            $newfigure = $dom->createElement("figure");
            $newfigure->setAttribute("class", $class);
            $a = $dom->createElement("a");
            $a->setAttribute("data-fslightbox", true);
            $a->setAttribute("data-type", "image");
            $a->setAttribute("href", $item->getAttribute("src"));
            $a->appendChild($item);
            $newfigure->appendChild($a);
            if (! is_null($caption)) {
                $newfigure->appendChild($caption);
            }
            $figure->parentNode->replaceChild($newfigure, $figure);
        }
    }
    
    if ( count($allFigures) > 0) {
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

add_filter("the_content", '\mvbplugins\fslightbox\lightbox_gallery_for_gutenberg', 10, 1);

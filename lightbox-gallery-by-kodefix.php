<?php
/*
  Plugin Name: Lightbox Gallery by Kodefix - Responsive Lightbox Effect for gallery block
  Plugin URI: https://kodefix.pl/
  Description: An easy way to create lightbox effect for WordPress new editor's gallery block. Just turn on the plugin and use powerful lightbox.

  Version: 1.1
  Author: Kodefix
  Author URI: https://kodefix.pl/?utm_source=lightbox-for-gutenberg&utm_medium=plugin&utm_campaign=created_plugins
  Text Domain:  lightbox-gallery-for-gutenberg

  @package  LightboxGalleryForGutenberg
  @category Core
  @author   Kodefix
*/

require __DIR__ . "./vendor/autoload.php";
require_once __DIR__ . "./vendor/ivopetkov/html5-dom-document-php/autoload.php";

function lgfg_scripts()
{
    wp_enqueue_script(
        "engine",
        plugin_dir_url(__FILE__) . "/js/fslightbox.js",
        "",
        "3.3.1",
        true
    );
}
add_action("wp_enqueue_scripts", "lgfg_scripts");

function lightbox_gallery_for_gutenberg($content)
{
    $dom = new IvoPetkov\HTML5DOMDocument();
    $dom->loadHTML($content);

    $gallery_block = $dom->querySelectorAll(".wp-block-gallery");

    foreach ($gallery_block as $block) {
        $gallery = $block->querySelectorAll("figure");
      
        foreach ($gallery as $figure) {
            $item = $figure->querySelector("img");
            $caption = $figure->querySelector("figcaption");
            $class = $figure->getAttribute("class");

            $newfigure = $dom->createElement("figure");
            $newfigure->setAttribute("class", $class);
         
            $a = $dom->createElement("a");
            $a->setAttribute("data-fslightbox", true);
            $a->setAttribute("href", $item->getAttribute("src"));
            $a->appendChild($item);
            $newfigure->appendChild($a);
            $newfigure->appendChild($caption);
           
            $figure->parentNode->replaceChild($newfigure, $figure);
        }
    }
    return $dom->saveHTML();
}

add_filter("the_content", "lightbox_gallery_for_gutenberg", 10, 1);

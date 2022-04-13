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

require "vendor/autoload.php";

function lgfg_scripts()
{
    wp_enqueue_script(
        "engine",
        plugin_dir_url(__FILE__) . "/js/fslightbox.js",
        "",
        "1.0.1",
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

        $li = $block->querySelectorAll("li");

        for ($i = 0; $i < $li->length; $i++) {
            $node = $li->item($i);
            $parent = $node->parentNode;
            $node->parentNode->removeChild($node);
        }

        $ul = $block->querySelector("ul");
        $ul->parentNode->removeChild($ul);

        $ul = $dom->createElement("ul");
        $ul->setAttribute("class", "blocks-gallery-grid");
        $block->appendChild($ul);

        foreach ($gallery as $figure) {
            $item = $figure->querySelector("img");
            $caption = $figure->querySelector("figcaption");

            $li = $dom->createElement("li");
            $li->classList->add("blocks-gallery-item");

            $figure = $dom->createElement("figure");
            $li->appendChild($figure);

            $a = $dom->createElement("a");
            $a->setAttribute("data-fslightbox", true);
            $a->setAttribute("href", $item->getAttribute("src"));
            $figure->appendChild($a);

            $img = $dom->createElement("img");
            $img->setAttribute("src", $item->getAttribute("src"));
            $img->setAttribute("alt", $item->getAttribute("alt"));
            $img->setAttribute("data-id", $item->getAttribute("data-id"));
            $img->setAttribute("data-link", $item->getAttribute("data-link"));
            $img->setAttribute("data-class", $item->getAttribute("data-class"));
            $a->appendChild($img);

            //caption
            if ($caption) {
                $new_caption = $dom->createElement("figcaption");
                $new_caption->setAttribute(
                    "class",
                    "blocks-gallery-item__caption"
                );

                $text = $dom->createTextNode($caption->getTextContent());
                $new_caption->appendChild($text);

                $figure->appendChild($new_caption);
            }

            $ul->appendChild($li);
        }
    }

    return $dom->saveHTML();
}

add_filter("the_content", "lightbox_gallery_for_gutenberg", 6);
?>

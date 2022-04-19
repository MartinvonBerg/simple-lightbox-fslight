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

// --------------- settings ----------------------------------------
$page = true;
$post = true;
$home = true;
$front = true;

$archive = false;
$date = false;
$author = false;
$tag = false;
$category = false;
$attachment = false;

$hrefEmpty = true;
$hrefattach = false;
$hrefMedia = true;

// --------------- settings end ------------------------------------

function lightbox_gallery_for_gutenberg($content)
{
    
    $dom = new \IvoPetkov\HTML5DOMDocument();
    $dom->loadHTML($content, ALLOW_DUPLICATE_IDS);

    $allFigures = $dom->querySelectorAll('figure');
    $nFound = 0;
           
    foreach ($allFigures as $figure) {

        $class = $figure->getAttribute("class");
        $found = 0;
        $isMediaFile = 0;
        $found =strpos($class, 'image');
        $item = $figure->querySelector("img");

        //if (\current_user_can('edit_posts')) echo "fslight" . $class;
        
        if (is_null($item) ) {
            $found = 0;
        }

        $caption = $figure->querySelector("figcaption");

        $href = '';
        $hasHref = true;
        $hasHref = $figure->querySelector("a");
        
        if (! is_null($hasHref)) {
            $href = $hasHref->getAttribute('href');
            $isMediaFile = \strpos( $href, 'uploads');

            if (!filter_var($href, FILTER_VALIDATE_URL) === false) {
                //break;
              } 
        }
        
        if (($found > 0) && ( is_null($hasHref) || ($isMediaFile > 0) )) {
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

add_filter("the_content", '\mvbplugins\fslightbox\lightbox_gallery_for_gutenberg', 10, 1);


/**
 * Get the upload URL/path in right way (works with SSL).
 *
 * @param string $param  "basedir" or "baseurl"
 * @param string $subfolder  subfolder to append to basedir or baseurl
 * @return string the base appended with subfolder
 */
function my_get_upload_dir($param, $subfolder = '') :string
{
	$upload_dir = wp_get_upload_dir();
	$url = $upload_dir[$param];

	if ($param === 'baseurl' && is_ssl()) {
		$url = str_replace('http://', 'https://', $url);
	}

	return $url . $subfolder;
}
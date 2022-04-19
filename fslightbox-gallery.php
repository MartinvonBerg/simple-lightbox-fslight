<?php
/**
 *
 * @wordpress-plugin
 * Plugin Name:       Simple Lightbox for Gutenberg
 * Plugin URI:        https://github.com/MartinvonBerg/simple-lightbox-gutenberg
 * Description:       An easy way to create lightbox effect for WordPress Gutenberg images, galleries and Media-Text-Block. Settings provided with json-File in Plugin-Folder, see there and Readme.
 * Version:           0.1.0
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

require_once __DIR__ . '/classes/RewriteFigureTagsClass.php';

add_filter("the_content", '\mvbplugins\fslightbox\wrapClass', 10, 1);

function wrapClass($content)
{
    $rewrite = new RewriteFigureTags();
    $new=$rewrite->lightbox_gallery_for_gutenberg($content);
    $rewrite = null;
    return $new;
}
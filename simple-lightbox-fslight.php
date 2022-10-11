<?php

/**
 *
 * @wordpress-plugin
 * Plugin Name:       Simple Lightbox with fslightbox
 * Plugin URI:        https://github.com/MartinvonBerg/simple-lightbox-fslight
 * Description:       An easy way to create lightbox effect for WordPress Gutenberg images, galleries and Media-Text-Block. Settings provided with json-File in Plugin-Folder, see there and Readme.
 * Version:           1.2.0
 * Requires at least: 5.9
 * Requires PHP       7.3
 * Author:            Martin von Berg
 * Author URI:        https://www.berg-reise-foto.de/software-wordpress-lightroom-plugins/wordpress-plugins-fotos-und-gpx/
 * License:           GPL-2.0
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace mvbplugins\fslightbox;

// fallback for WordPress security
if (!defined('ABSPATH')) {
    die('Are you ok?');
}

require_once __DIR__ . '/classes/RewriteFigureTagsClass.php';

add_filter('the_content', '\mvbplugins\fslightbox\wrapClass', 10, 1);

/**
 * Adopt the images, galleries and media-with-text in the content of a page / post with settings for fslightbox.js
 *
 * @param  string $content the content of the page / post to adopt with fslightbox
 * @return string the altered $content of the page post to show in browser
 */
function wrapClass(string $content)
{
    $rewrite = new RewriteFigureTags();
    $new     = $rewrite->lightbox_gallery_for_gutenberg($content);
    $rewrite = null;
    return $new;
}

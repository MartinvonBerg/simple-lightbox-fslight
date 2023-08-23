<?php

/**
 *
 * @wordpress-plugin
 * Plugin Name:       Simple Lightbox with fslightbox
 * Plugin URI:        https://github.com/MartinvonBerg/simple-lightbox-fslight
 * Description:       An easy way to create lightbox effect for WordPress Gutenberg images, galleries and Media-Text-Block. Settings provided with json-File in Plugin-Folder, see there and Readme.
 * Version:           1.4.0
 * Requires at least: 5.9
 * Requires PHP       7.3
 * Author:            Martin von Berg
 * Author URI:        https://www.berg-reise-foto.de/software-wordpress-lightroom-plugins/wordpress-plugins-fotos-und-gpx/
 * License:           GPL-2.0
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// TBD: open YT video at the current time, if it is already running and play only one video at time. and do the same on close. open only one video. So, if running in fullscreen do switch to that tab?
//      see: https://stackoverflow.com/questions/6970013/getting-current-youtube-video-time
//      and: https://github.com/banthagroup/fslightbox/issues/103, function managaAutoplay

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
function wrapClass(string $content): string
{
    return (new RewriteFigureTags())->lightbox_gallery_for_gutenberg($content);
}

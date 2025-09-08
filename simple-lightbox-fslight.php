<?php

/**
 *
 * @wordpress-plugin
 * Plugin Name:       Simple Lightbox with fslightbox
 * Plugin URI:        https://github.com/MartinvonBerg/simple-lightbox-fslight
 * Description:       An easy way to create lightbox effect for WordPress Gutenberg images, galleries and Media-Text-Block. Settings provided with json-File in Plugin-Folder, see there and Readme.
 * Version:           3.0.0
 * Requires at least: 5.9
 * Requires PHP:      8.0
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

require_once __DIR__ . '/admin/pre-post-install.php';
require_once __DIR__ . '/classes/RewriteFigureTagsClass.php';

(new RewriteFigureTags)->execute();
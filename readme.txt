=== Simple Lightbox for WordPress ===
Plugin Name: Simple Lightbox with fslightbox
Contributors: martinvonberg
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=CQA6XZ7LUMBJQ
Tags: lightbox, gallery, fslightbox, Gutenberg, Video, Image, Youtube
Requires at least: 5.9
Tested up to: 6.5
Stable tag: 2.1.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Provides an easy was to add a Lightbox to Images in Gutenberg image, gallery and Media-with-Text-Blocks. Additionally to Youtube and HTML5 Videos but no other videos. Just install, activate and use it.


== Description ==

Provides an easy was to add a Lightbox to Images in Gutenberg image, gallery and Media-with-Text-Blocks. Additionally to Youtube and HTML5 Videos but no other videos. Other video types like VideoPress, Vimeo etc. are currently NOT supported. 
The Javascript library fslightbox.js is used for that. You even may use the paid version of fslightbox.js. 
Plugin settings are provided by a JSON-file that may be easily changed and backed-up manually (automatically after V2.0.0).

NEW: Added support for the complete HTML-code to the post, page etc. inbetween body tags and not only the content. This is an Opt-in. See example settings file in ./settings/plugin-settings-body.json.

The support for HTML5-Videos and Youtube-Videos will add a small red button on the top left of the embedded Youtube-Video which opens the lightbox with that video.
YT-Videos could be disabled by deleting the line ``` "wp-block-embed-youtube" ``` in the file ```plugin-settings.json```. If you do so please delete the comma at the end of the line before, too!
See live example here: https://www.berg-reise-foto.de/software-wordpress-lightroom-plugins/wordpress-plugins-fotos-und-karten/

The support for Youtube-Videos is working with the given functionality and extended to stop running videos onOpen and for the paid version to stop running videos on slideChange.


== Frequently Asked Questions ==

= How does the plugin work? =

Plugin is filtering the content of posts and pages and appends lightbox effect for native Gutenberg-Blocks: Image, Gallery, Media-and-Text, Featured Image, Video and Youtube-Video. It also works for external images or YT-videos that are not on your site.

= What exactly are the preconditions? =
- It filters the content of the page, post etc (setting: postTypes). Meaning exactly the content that WP stores in the database as content for posts, pages etc. (Using the_content filter)
- All elements in html figure tags and img tags will be filtered and if its CSS-Class is defined in the plugin-settings.json (setting: cssClassesToSearch).

= Will it work with my page builder, Theme, ACF etc.? =
Maybe, see preconditions above. If your theme provides output that is NOT stored as content in the database it will not work. If your media ( be it image or video ) is not given in a figure tag it will not work. 
Positive feedback is reported from Beaver Builder, Post Types of Advanced Custom Fields. Negative feedback is given by GeneratePress that generates page content in special elements by the theme files. So, just try if it works and mind the preconditions.

= How to change the plugin settings? =

The settings are written in a separate file 'plugin-settings.json' in the main plugin folder. 
Change here the type of posts, pages which shall include the lightbox. Additionally change whether existing links to Media-Files should be overwritten and which CSS-Classes should be used for the fslightbox.
In the JSON file, you can select which link may already be present on the image (hrefTypes : Empty, Media). In addition, it can be set for which pages or posts the lightbox should be activated (postTypes). The selection of the actual media type is done with the CSS class that is used for the image or video (cssClassesToSearch). With excludeIDs you can set which posts / pages should not be equipped with a lightbox. Reasonable basic settings have been chosen, so there should be no reason to change them at the beginning.

= Is the paid version of fslightbox.js supported? =

Yes, you may buy the fslightbox.js an add the file fslightbox.js to the folder ./folder-where-the-plugin-is-installed/js/fslightbox-paid. That's it.

= Does the plugin have any requirements? =

No. You can use this plugin with pure WordPress with Gutenberg editor enabled. But mind the preconditions above.

= Is the lightbox responsive? =

Yes. Lightbox is fully responsive - it scales to every device.

= Does the plugin use jQuery? =

No. Plugin just uses fslightbox.js. Free or paid version optionally.

= Does the plugin backup and restore my plugin-settings.json and my paid Version of fslightbox? =
Yes, the Update to 2.0.0 implements a backup / restore logic for ```plugin-settings.json``` and the files in ./js/fslightbox-paid. This will work ONLY for all future updates because the php-files have to be on your server already. So, with this update it is the last time you have to save your files in advance. The process creates the folder ```../simple-lightbox-fslight-backup``` in you Plugin-Directory which won't be deleted after Update. If you want the backup-restore process running with the Update to V2.0.0 you have to manually copy the files ```simple-lightbox-fslight.php``` and ```./admin/pre-post-install.php``` from Github via ftp to your server. The backup / restore logic won't work if you install the Plugin manually as zip-File.

= With Youtube-Videos the Browser Console shows Javascript Errors. It it a Problem? =
Yes, there are errors shown like "The service worker navigation preload request was cancelled before 'preloadResponse' settled. If you intend to use 'preloadResponse', use waitUntil() or respondWith() to wait for the promise to settle". This issue is not solvable by me. If you dislike it: Just delete the JS-File ```simple-lightbox.min.js```. The Video Sync will no longer work after that.

= Why does it not work with flickr images?
You might add "wp-block-embed-flickr" but the lightbox does not open? That is, because flicks image blocks contain a link to the image on the flickr website. The Gutenberg block does not have an option to change this. The plugin functionality does NOT change existing links, because this is usually intentionally.

= Why does it not work with featured images?
Featured images are not stored in the database as content. The plugin does usuallay not filter the content of post, page etc. So, add the option "rewriteScope": "body" to the settings.json file. See example settings file in ./settings/plugin-settings-body.json.


== Screenshots ==
1. Example lightbox.

== Upgrade Notice ==
Upgrade to 2.1.0+ if you want support for Youtube Videos or want to have your settings restored automatically.

== Changelog ==

= V2.1.1 =
- BUGFIX: PHP Bugfixes to avoid incomplete script tag together with rewritescope : body

= V2.1.0 =
- BUGFIX: PHP Bugfixes to avoid crashes for unsopperted media types.

= V2.0.0 =
Breaking Changes:
- NEW: Minimum PHP Version is now 7.4
- NEW: added an option to handle the complete HTML code inbetween the Body tags (not only the_content)
- NEW: for WP 6.4: Do not open the lightbox where the new WP lightbox was activated.
- BUGFIX: changed process for backup and restore of plugin files. V1.5.0 did not work in all cases and caused PHP fatal errors in some cases.
- BUGFIX: changed code for HTML5Videos to be compatible with W3C standards. Will add a Button Icon on the Top Left.
- 
- removed handling of Postie-images (was my private use)
- Code Refactoring of Main Class
- Updated JS to pause Videos on Slide change (only paid version of fsligthbox will support this)
- Updated PHPDocBlocks for PHPUnit Tests, PHPStan Level 6 and PHPCS

= 1.5.0 =
Added JS to pause all running videos on Open of lightbox and pause current video on slide change (paid version, only).
Added backup / restore logic for plugin-settings.json and fslightbox-paid files. 

= 1.4.0 =
Added support for Youtube-Videos. (The access to the YT-JS-API is not feasible for me to CORS-Policy, so the trial to sync the running videos was stopped.)

= 1.3.3 =
Some changes for WPCS rules and code reformatting. No functional change. Updated HTML5DOMDocument.php from github.
Test with WordPress 6.3. Save and Restore function for settings before Update not realized. Save your settings before update.

= 1.3.2 =
Added PHP type definitions.

= 1.3.1 =
Added an aria-label to the button for accessibility and lighthouse tests. Updated Unit-Tests successfully. No functional change.

= 1.3.0 =
Update of fslightbox.js (basic, free version) to 3.4.1. Download from: https://fslightbox.com/fslightbox/javascript/fslightbox-basic-3.4.1.zip
Test with WP 6.2. 

= 1.2.0 =
Test with WP 6.1. No changes.

= 1.1.1 =
Test with WP 6.0. No changes.

= 1.1.0 =
Added a Setting to exclude certain IDs (post or page or whatever is set)
Included the Preview ('poster') of videos in the thumbnails (only paid version will see thumbnails)
Included a logic for old Gutenberg images with div-tag figure-tag img-tag... structure where the class is defined in the div.
Bugfixes: Corrected the generated html for Media-Text and for images with a link in their caption

= 1.0.0 =
First Version based on Lightbox Gallery by Kodefix.


== Plugin uses ==

Following libraries and WP-Plugins were used to create this plugin:

1. [fslightbox.js](https://fslightbox.com/ "fslightbox.js")
2. [HTML5DOMDocument by ivopetkov](https://github.com/ivopetkov/html5-dom-document-php "HTML5DOMDocument by ivopetkov")
3. [Lightbox Gallery by Kodefix](https://wordpress.org/plugins/kodefix-lightbox-gallery/ "Lightbox Gallery by Kodefix")

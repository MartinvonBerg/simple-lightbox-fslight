=== Simple Lightbox for WordPress ===
Plugin Name: Simple Lightbox with fslightbox
Contributors: Martin von Berg
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=CQA6XZ7LUMBJQ
Tags: lightbox, gallery, fslightbox, Gutenberg
Requires at least: 5.9
Tested up to: 6.1
Stable tag: 1.2.0
Requires PHP: 7.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Provides an easy was to add a Lightbox to Gutenberg videos, images, galleries and Media-with-Text-Blocks. Just install and activate and use it.


== Description ==

Provides an easy was to add a Lightbox to Gutenberg videos, images, galleries and Media-with-Text-Blocks. Just install and activate and use it.
The Javascript-library fslightbox.js is used for that. You even may use the paid version of fslightbox.js. 
Available Settings are provided by a JSON-file that may be easily changed and backed-up.


Just create gallery by using "gallery" block and use lightbox gallery effect powered by fslightbox.js


== Frequently Asked Questions ==

= How does the plugin work? =

Plugin is filtering the content of posts and pages and appends lightbox effect for video, image, gallery and wp-text blocks. It also works for external images or videos that are not on your site.

= How to change the plugin settings? =

The settings are written in a separate file 'plugin-settings.json' in the main plugin folder. 
Change here the type of posts, pages which shall include the lightbox. Additionally change whether existing links to Media-Files should be overwritten and which CSS-Classes should be used for the fslightbox.
In the JSON file, you can select which link may already be present on the image (hrefTypes : Empty, Media). In addition, it can be set for which pages or posts the lightbox should be activated (postTypes). The selection of the actual media type is done with the CSS class that is used for the image or video (cssClassesToSearch). With excludeIDs you can set which posts / pages should not be equipped with a lightbox. Reasonable basic settings have been chosen, so there should be no reason to change them at the beginning.

= Is the paid version of fslightbox.js supported? =

Yes, you may buy the fslightbox.js an add the file fslightbox.js to the folder ./folder-where-the-plugin-is-installed/js/fslightbox-paid. That's it.

= Does plugin has any requirements? =

No. You can use this plugin with pure WordPress with Gutenberg editor enabled.

= Is the lightbox responsive? =

Yes. Lightbox is fully responsive - it scales to every device.

= Does the plugin use jQuery? =

No. Plugin just uses fslightbox.js. Free or paid version optionally.


== Screenshots ==

Example lightbox.


== Changelog ==

= 1.2.0 =
* Test with WP 6.1. No changes.

= 1.1.1 =
* Test with WP 6.0. No changes.

= 1.1.0 =
* Added a Setting to exclude certain IDs (post or page or whatever is set)
* Included the Preview ('poster') of videos in the thumbnails (only paid version will see thumbnails)
* Included a logic for old Gutenberg images with div-tag figure-tag img-tag... structure where the class is defined in the div.
* Bugfixes: Corrected the generated html for Media-Text and for images with a link in their caption

= 1.0.0 =
* First Version based on Lightbox Gallery by Kodefix.


== Plugin uses ==

Following libraries and WP-Plugins were used to create this plugin:

1. [fslightbox.js](https://fslightbox.com/ "fslightbox.js")
2. [HTML5DOMDocument by ivopetkov](https://github.com/ivopetkov/html5-dom-document-php "HTML5DOMDocument by ivopetkov")
3. [Lightbox Gallery by Kodefix](https://wordpress.org/plugins/kodefix-lightbox-gallery/ "Lightbox Gallery by Kodefix")

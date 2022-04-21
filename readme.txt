=== Simple Lightbox for WordPress ===
Plugin Name: Simple Lightbox for WordPress with fslight
Contributors: Martin von Berg
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=CQA6XZ7LUMBJQ
Tags: lightbox, gallery
Requires at least: 5.9
Tested up to: 5.9.3
Stable tag: 4.3
Requires PHP: 7.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Provides an easy was to add a Lightbox to Gutenberg videos, images, galleries and Media-with-Text-Blocks. Just intall and activate and use it.


== Description ==

Provides an easy was to add a Lightbox to Gutenberg videos, images, galleries and Media-with-Text-Blocks. Just intall and activate and use it.
The Javascript-library fslightbox.js is used for that. You even may use the paid version of fslightbox.js. 
Available Settings are provided by a JSON-file that may be easily changed and backed-up.


Just create gallery by using "gallery" block and use lightbox gallery effect powered by fslightbox.js


== Installation ==

1. Visit the plugins page on your Admin-page and click  ‘Add New’
2. Search for 'wp_wpcat_json_rest', or 'JSON' and 'REST'
3. Once found, click on 'Install'
4. Go to the plugins page and activate the plugin


== Frequently Asked Questions ==

= How does the plugin work? =

Plugin is filtering the content of posts and pages and appends lightbox effect for video, image, gallery and wp-text blocks. It also works for external images or videos that are not on your site.

= How to change the plugin settings? =

The settings are written in a seperate file 'plugin-settings.json' in the main plugin folder. 
Change here the type of posts, pages which shall include the lightbox. Additionally change whether existing links to Media-Files should be overwritten and which CSS-Classes should be used for the fslightbox.

= Is the paid version of fslightbox.js supported? =

Yes, you may buy the fslightbox.js an add the file fslightbox.js to the folder ./simple-lightbox-fslight/js/fslightbox-paid. That's it.

= Does plugin has any requirements? =

No. You can use this plugin with pure WordPress with Gutenberg editor enabled.

= Is the lightbox responsive? =

Yes. Lightbox is fully responsive - it scales to every device.

= Does the plugin use jQuery? =

No. Plugin just uses fslightbox.js. Free or paid version optionally.


== Screenshots ==

Example lightbox.


== Changelog ==

= 0.1.0 =
* First Version based on Lightbox Gallery by Kodefix.



== Plugin uses ==

Following libraries and WP-Plugins were used to create this plugin:

1. [fslightbox.js](https://fslightbox.com/ "fslightbox.js")
2. [HTML5DOMDocument by ivopetkov](https://github.com/ivopetkov/html5-dom-document-php "HTML5DOMDocument by ivopetkov")
3. [Lightbox Gallery by Kodefix](https://wordpress.org/plugins/kodefix-lightbox-gallery/ "Lightbox Gallery by Kodefix")

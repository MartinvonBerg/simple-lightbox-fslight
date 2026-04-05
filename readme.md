# Simple Lightbox with fslightbox

WordPress plugin that adds a lightbox to Gutenberg image, gallery, media-and-text, featured image, video, and YouTube blocks.

This plugin provides an easy way to add a lightbox to images in Gutenberg image, gallery, and media-with-text blocks, plus YouTube and HTML5 videos.

- Uses the JavaScript library `fslightbox.js`
- Supports free and paid versions of `fslightbox.js`
- Plugin settings are stored in `plugin-settings.json`
- Automatic backup/restore of settings (since v2.0.0)

### Body-Level Rendering (Opt-in)

Support was added to process the complete HTML between `<body>` tags, not only post content.

See:

- `./settings/plugin-settings-body.json`

### YouTube and HTML5 Video Behaviour

For YouTube and HTML5 video support, the plugin adds a small red button on the top-left of embedded YouTube videos to open them in the lightbox.

To disable YouTube handling, remove `"wp-block-embed-youtube"` from `plugin-settings.json` (and remove the preceding trailing comma if needed).

Live example:

- https://www.berg-reise-foto.de/software-wordpress-lightroom-plugins/wordpress-plugins-fotos-und-karten/

The YouTube integration includes stopping running videos on open, and for paid versions also on slide change.

## Frequently Asked Questions

### How does the plugin work?

The plugin filters post/page content and appends lightbox behaviour for native Gutenberg blocks:

- Image
- Gallery
- Media and Text
- Featured Image
- Video
- YouTube Video

It also works for external images or YouTube videos.

### What exactly are the preconditions?

- It filters post/page content via `the_content` according to `postTypes`
- Elements in `figure`/`img` tags are processed if their CSS class is listed in `plugin-settings.json` (`cssClassesToSearch`)

### Will it work with my page builder, theme, ACF, etc.?

Maybe. If output is not stored as post/page content in the database, it may not work. If media is not wrapped in expected tags, it may not work.

Reported feedback:

- Works with Beaver Builder and ACF post types
- Issues reported with GeneratePress in some theme-generated content cases

### How to change plugin settings?

Settings are in `plugin-settings.json` in the plugin root.

You can configure:

- **postTypes**: Post/page/custom types to include
- **hrefTypes**: Existing link type allowed (`Empty`, `Media`)
- **cssClassesToSearch**: CSS classes to include (defined on the `figure` around the `img`)
- **excludeIDs**: WordPress IDs to exclude
- **rewriteScope**:
  - `""` (empty): default, content-only rendering
  - `"body"`: render entire page in PHP
  - `"javascript"`: render client-side in JavaScript

Predefined classes include:

- `wp-block-image`
- `wp-block-post-featured-image`
- `wp-block-embed-youtube`
- `wp-block-media-text__media`
- `wp-block-video`

Not working with:

- `wp-block-cover`

### Is the paid version of fslightbox.js supported?

Yes. Place paid files in:

- `./js/fslightbox-paid`

### Does the plugin have any requirements?

No additional requirements beyond WordPress with Gutenberg enabled, assuming the preconditions above are met.

### Is the lightbox responsive?

Yes, fully responsive.

### Does the plugin use jQuery?

No.

### Does the plugin back up and restore `plugin-settings.json` and paid fslightbox files?

Yes, since `2.0.0` for future updates. Backup folder:

- `../simple-lightbox-fslight-backup`

Important notes from original release text still apply for migration behaviour.

### With YouTube videos, the browser console shows JavaScript errors. Is it a problem?

Known issue from service worker preload cancellation in some environments. If undesired, remove:

- `simple-lightbox.min.js`

This disables video sync behaviour.

### Why does it not work with Flickr images?

Flickr embed blocks often link to Flickr pages, not direct media URLs. The plugin does not rewrite existing links by design.

### Why does it not work with featured images?

Featured images are not stored the same way as post content. Use:

- `"rewriteScope": "body"`

See:

- `./settings/plugin-settings-body.json`

### Does it work with AVIF image format?

Yes.

## Screenshots

1. Example lightbox.

## Upgrade Notice

- Upgrade only if your server uses PHP 8.0+
- Do not upgrade if you still use PHP 7.4
- Upgrade to `2.1.0+` for YouTube support and automatic settings restore

## Changelog

### 3.3.0

- Replaced the `ivopetkov` PHP library with native PHP functions and updated PHPUnit tests
- Significant performance improvement
- Updated PHPUnit to `9.6.34`

### 3.2.0

- Added full client-side JavaScript HTML rendering for fslightbox
- Added required JS/CSS files
- Tested with local and public site versions

### 3.1.0

- Updated `RewriteFigureTagsClass.php` for `the_content` and `body` filters
- Improved frontend restrictions and early exits
- Ensured HTML is processed only once
- Updated enqueue logic, error handling, MIME detection, and DOM wrapper handling
- Updated unit tests
- Updated YouTube thumbnail generation

### 3.0.0

- Updated `html5-dom-document-php` to `v2.8.1` (requires PHP 8)
- Raised minimum PHP to `8.0`
- Updated fslightbox basic to `3.7.4` and pro to `3.8.3`
- Tested with WP `6.9.0`

### 2.2.0

- Updated fslightbox basic to `3.6.0` and pro to `3.8.0`
- Tested with WP `6.8`

### 2.1.1

- Tested with WP `6.7`
- Tested AVIF images

### 2.1.1

- Tested with WP `6.6`
- Tested AVIF images

### 2.1.1

- Bugfixes to avoid incomplete script tags with `rewriteScope: body`

### 2.1.0

- Bugfixes to avoid crashes for unsupported media types

### 2.0.0

Breaking changes and updates:

- Minimum PHP version changed to `7.4` (historical note in original changelog)
- Added option to handle complete HTML between body tags
- For WP `6.4`: skip opening where native WP lightbox is active
- Backup/restore bugfixes
- HTML5 video markup compatibility improvements
- Removed Postie image handling
- Main class refactoring
- Updated JS to pause videos on slide change (paid version support)
- Updated docblocks and quality tooling alignment (PHPUnit, PHPStan, PHPCS)

### 1.5.0

- Added JS to pause running videos on lightbox open and slide change (paid version)
- Added backup/restore logic for `plugin-settings.json` and paid files

### 1.4.0

- Added YouTube video support
- Attempted sync via YT JS API was stopped due to CORS constraints

### 1.3.3

- WPCS and formatting updates
- Updated `HTML5DOMDocument.php`
- Tested with WP `6.3`

### 1.3.2

- Added PHP type definitions

### 1.3.1

- Added ARIA label for accessibility/lighthouse checks
- Updated unit tests

### 1.3.0

- Updated fslightbox basic to `3.4.1`
- Download source: https://fslightbox.com/fslightbox/javascript/fslightbox-basic-3.4.1.zip
- Tested with WP `6.2`

### 1.2.0

- Tested with WP `6.1`, no changes

### 1.1.1

- Tested with WP `6.0`, no changes

### 1.1.0

- Added setting to exclude selected IDs
- Included video poster thumbnails (paid version visibility)
- Added support for older Gutenberg image structures
- Bugfixes for Media-Text and linked-caption image HTML

### 1.0.0

- First version based on Lightbox Gallery by Kodefix

## Plugin Uses

The following libraries/plugins were used:

1. [fslightbox.js](https://fslightbox.com/)
2. [HTML5DOMDocument by ivopetkov](https://github.com/ivopetkov/html5-dom-document-php)
3. [Lightbox Gallery by Kodefix](https://wordpress.org/plugins/kodefix-lightbox-gallery/)

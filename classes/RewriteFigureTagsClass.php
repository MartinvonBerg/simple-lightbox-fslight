<?php

/**
 *
 * Version:           3.1.0
 * Requires at least: 5.9
 * Requires PHP:      8.0
 * Author:            Martin von Berg
 * Author URI:        https://www.berg-reise-foto.de/software-wordpress-lightroom-plugins/wordpress-plugins-fotos-und-gpx/
 * License:           GPL-2.0
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace mvbplugins\fslightbox;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Are you ok?' );
}

require_once __DIR__ . '/html5-dom-document-php/autoload.php';
require_once __DIR__ . '/hrefImageDetection.php';

/**
 * The Command interface declares a method for executing a command.
 */
interface RewriteFigureTagsInterface {
	public function execute(): bool;
}

/**
 * Class to adopt the images, galleries and media-with-text in the content of a page / post with settings for fslightbox.js
 *
 * @phpstan-type hrefTypes array{string}
 * @phpstan-type postTypes array{string}
 * @phpstan-type cssClassesToSearch array{string}
 * @phpstan-type excludeIDs array{integer}
 * @phpstan-type includedTags array<string, bool>
 */
final class RewriteFigureTags implements RewriteFigureTagsInterface {

	// --------------- settings ----------------------------------------
	protected string $posttype = '';
	protected string $siteUrl = '';
	protected bool $doRewrite = false;
	protected bool $hrefEmpty = false;
	protected bool $hrefMedia = false;
	protected string $plugin_main_dir = '';
	protected int $nFound = 0;
	protected bool $want_to_modify_body = false;

	/**
	 * @var hrefTypes
	 */
	protected array $hrefTypes = [ 'Empty', 'Media' ];

	/**
	 * @var postTypes
	 */
	protected array $postTypes = [ 'page', 'post', 'home', 'front',];

	/**
	 * @var cssClassesToSearch
	 */
	protected array $cssClassesToSearch = [ 'wp-block-image', 'wp-block-media-text', 'wp-block-video', 'postie-image' ];

	/**
	 * @var excludeIDs
	 */
	protected array $excludeIds = [ 0 ];

	/**
	 * @var includedTags
	 */
	protected array $includedTags = [];
	
	/**
	 * @var int called_counter
	 */
	public static int $called_counter = 0;

	/**
	 * @var bool needs_assets
	 */
	private bool $needs_assets = false;


	/**
	 * Do settings for the class and Plugin. Load from json-settings-file.
	 */
	public function __construct( ?string $file = null ) {
		$this->plugin_main_dir = dirname( __DIR__, 1 );
		$this->siteUrl = \get_site_url();

		// load and parse settings from file plugin-settings.json in main directory
		if ( \is_null( $file ) ) {
			$path = $this->plugin_main_dir . '/plugin-settings.json';
		} else {
			$path = $this->plugin_main_dir . $file;
		}

		if ( is_file( $path ) ) {
			$settings = strval( file_get_contents( $path, false ) );
			$settings = \json_decode( $settings, true );
			$this->hrefTypes = \key_exists( 'hrefTypes', $settings ) ? $settings['hrefTypes'] : $this->hrefTypes;
			$this->postTypes = \key_exists( 'postTypes', $settings ) ? $settings['postTypes'] : $this->postTypes;
			$this->cssClassesToSearch = \key_exists( 'cssClassesToSearch', $settings ) ? $settings['cssClassesToSearch'] : $this->cssClassesToSearch;
			$this->excludeIds = \key_exists( 'excludeIDs', $settings ) ? $settings['excludeIDs'] : $this->excludeIds;

			// extract $want_to_modify_body from settings
			if ( \key_exists( 'rewriteScope', $settings ) ) {
				$this->want_to_modify_body = $settings['rewriteScope'] === 'body';
			}
		}
		;

		foreach ( $this->hrefTypes as $type ) {
			switch ( strtolower( $type ) ) {
				case 'empty':
					$this->hrefEmpty = true;
					break;
				case 'media':
					$this->hrefMedia = true;
					break;
				default:
					break;
			}
		}
	}

	/**
	 * Prepares the rewrite for the HTML rewrite of figures.
	 *
	 * @return boolean Returns a boolean indicating whether the rewrite shall be done.
	 */
	private function prepare(): bool {
		// prepare rewrite only for posts that are in settings and only for front-end
		if ( \in_the_loop() ) {
			$postID = (int) \get_the_ID();
		} else {
			$postID = (int) \get_queried_object_id();
		}

		$exclude = \in_array( $postID, $this->excludeIds, true ); // exclude IDs from settings
		
		// // get the post type of current post and check against settings
    	$ptype = get_post_type( $postID );
    	$this->posttype = $ptype ? (string) $ptype : '';

		
 		//Frontend-Guards
    	$is_rest = defined( 'REST_REQUEST' ) && REST_REQUEST;

		$this->doRewrite =
			in_array( $this->posttype, $this->postTypes, true )
			&& ! $exclude
			&& ! is_admin()
			&& ! is_feed()
			&& ! is_trackback()
			&& ! $is_rest
			&& is_singular()
			//&& in_the_loop() // removed to have it run for body content
			&& is_main_query();

		$this->nFound = 0;
		return $this->doRewrite;
	}

	// --------------- Interface ----------------------------------------
	/**
	 * Run the Class Code Rewriter
	 *
	 * @return boolean
	 */
	public function execute(): bool {
		if ( ! $this->want_to_modify_body ) {
			add_filter( 'the_content', array( $this, 'changeFigureTagsInContent' ), 20, 1 );
		} else {
			//  here for output buffering.
			$this->changeFigureTagsInBody();
		}
		return true;
	}

	// --------------- public functions called by WP Hooks --------------
	/**
	 * Rewrite the HTML code with figures if the rewrite shall be done and enqueue scripts and styles if so.
	 *
	 * @param  string $content the html to rewrite
	 * @return string the rewritten html
	 */
	public function changeFigureTagsInContent( string $content ): string {
		// exit if the content was already rewritten or is currently rewriting
		if (str_contains($content, 'simple-lightbox-fslight processed figures') || self::$called_counter > 0) {
			return $content;
		}

		// exit if no figure or img tag in content
		if (!str_contains($content, '<figure') && !str_contains($content, '<img')) {
			return $content;
		}

		self::$called_counter += 1;
		if ( $this->prepare() ) {
			$content = $this->rewriteHTML( $content );
			// include scripts and styles if rewrite was actually done.
			if ( $this->nFound > 0 ) {
				$this->needs_assets = true;
				$this->my_enqueue_script();
			}
		}
		self::$called_counter -= 1;
		return $content;
	}

	/**
	 * Hook the PHP output buffer to the 'wp_body_open' action.
	 *
	 * @return void
	 */
	public function changeFigureTagsInBody() {
		add_action( 'template_redirect', [ $this, 'rewrite_body_buffer_start' ], 9999, 0 ); // hook to 'template_redirect' for whole html even html-head
	}

	/**
	 * Pass the output buffer to rewrite function and hook ob-buffer-stop to the 'wp_footer' action.
	 *
	 * @return void
	 */
	public function rewrite_body_buffer_start() {
		ob_start( [ $this, 'rewrite_body_modify_content' ] );
	}

	/**
	 * Rewrite the HTML code with figures if the rewrite shall be done and add script and style tags to html if so.
	 *
	 * @param  string $content the html to rewrite
	 * @return string the rewritten html $content
	 */
	public function rewrite_body_modify_content( string $content ): string {
		// check if html contains <body> .... </body> 
		if (!preg_match('/(<body\b[^>]*>)(.*?)(<\/body>)/is', $content, $m)) {
        	return $content; // Fallback
    	}

		// extract and change innner body content and add script and style tags at the end.
		$content = preg_replace_callback(
				'/(<body\b[^>]*>)(.*?)(<\/body>)/is',
				function (array $matches) {
					$body_open  = $matches[1]; // <body ...>
					$body_inner = $matches[2]; // Inhalt des Body
					$body_close = $matches[3]; // </body>

					// Deine eigentliche Rewrite-Logik nur auf $body_inner anwenden
					if ($this->prepare()) {
						$body_inner = $this->rewriteHTML($body_inner);

						if ($this->nFound > 0) {
							$scripts = $this->rewrite_body_add_scripts();
							$body_inner .= $scripts;
						}
					}

					return $body_open . $body_inner . $body_close;
				},
				$content,
				1 // nur das erste Match ersetzen (sicherer)
			);
		
		return $content;
	}

	/**
	 * Adds scripts and styles to the HTML page.
	 *
	 * @return string The generated HTML code for the scripts and styles.
	 */
	public function rewrite_body_add_scripts(): string {
		$out = '';

		$slug = plugins_url() . '/' . \basename( $this->plugin_main_dir ); // @phpstan-ignore-line

		// check for fslightbox.js paid version
		$path = $this->plugin_main_dir . '/js/fslightbox-paid/fslightbox.js';
		if ( is_file( $path ) ) {
			$path = $slug . '/js/fslightbox-paid/fslightbox.js';
			$out = "<script defer src='{$path}' id='mvb-fslightbox'></script>";
		} else {
			// check for fslightbox.js free version
			$path = $this->plugin_main_dir . '/js/fslightbox-basic/fslightbox.js';
			if ( is_file( $path ) ) {
				$path = $slug . '/js/fslightbox-basic/fslightbox.js';
				$out = "<script defer src='{$path}' id='mvb-fslightbox'></script>";
			}
		}

		// check for simple-lightbox.min.js script to handle videos
		$path = $this->plugin_main_dir . '/js/simple-lightbox.min.js';
		if ( is_file( $path ) ) {
			$path = $slug . '/js/simple-lightbox.min.js';
			$out .= "<script defer src='{$path}' id='yt-script-mvb-fslightbox'></script>";
		}

		// check for CSS file for simple-fslightbox
		$path = $this->plugin_main_dir . '/css/simple-fslightbox.css';
		if ( is_file( $path ) ) {
			$path = $slug . '/css/simple-fslightbox.css';
			$out .= "<link rel='stylesheet' id='simple-fslightbox-css' href='{$path}' media='all' />";
		}

		return $out;
	}

	// --------------- private functions : HTML rewriter ------------------------
	/**
	 * Adopt the images, galleries and media-with-text in the content of a page / post with settings for fslightbox.js. This is the main function.
	 *
	 * @param  string $content the content of the page / post to adopt with fslightbox
	 * @return string the altered $content of the page post to show in browser
	 */
	private function rewriteHTML( string $content ): string {
		
		
		// 1) BOM & XML-PI entfernen (PI taucht in HTML-Fragmenten manchmal als Kommentar wieder auf)
		$originalContent = $content;

		// 2) Stabiler Wrapper: Du gibst später NUR den Inhalt dieses DIV zurück
		$wrapId = '__fslbx_wrap_' . wp_generate_password(8, false, false);
		$html = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' 
				. '<div id="' . $wrapId . '">' . $content . '</div>' 
				. '</body></html>';

		// rewrite HTML code with figures
		$dom = new \IvoPetkov\HTML5DOMDocument();
		libxml_use_internal_errors(true);
		//$dom->loadHTML( $html, \IvoPetkov\HTML5DOMDocument::ALLOW_DUPLICATE_IDS | \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD );
		$dom->loadHTML( $html, \IvoPetkov\HTML5DOMDocument::ALLOW_DUPLICATE_IDS | LIBXML_NOERROR | \LIBXML_NOWARNING );

		$container = $dom->getElementById($wrapId);
		$allFigures = $container->querySelectorAll( 'figure' );

		$this->nFound = 0;

		foreach ( $allFigures as $figure ) {

			$class = $figure->getAttribute( 'class' );
			$tagType = $figure->tagName;
			[ $classFound, $isVideo, $isYouTube ] = $this->findCssClass( $class );
			$isMediaFile = false;
			$hasHref = false;
			$item = null;
			$dataType = '';
			$videoThumb = null;
			$hrefParent = null;
			$hasDivInFigure = false; // 2023-09: new decision for figures with structure not regarded in first development.
			$hasWPLightbox = strpos( $class, 'wp-lightbox' ) !== false; // usage for WP 6.4+ with simple CSS lightbox.

			if ( ! $classFound ) {
				$classFound = $this->parentFindCssClass( $figure );
			}

			if ( $classFound && ! $hasWPLightbox ) {
				// provide item, $dataType, $isMediaFile, $hasHref from $figure, $classFound, $isVideo
				if ( ! $isVideo ) {
					$item = $figure->querySelector( 'img' );
					$dataType = 'image';

					$href = null;
					$href = $figure->querySelector( 'a' );
					if ( ! \is_null( $href ) ) {
						$hrefParent = $href->parentNode;
					}
					$hasHref = \is_null( $href ) ? false : true;

					if ( $hasHref ) {
						$href = $href->getAttribute( 'href' );
						if ( $hrefParent->tagName === 'figcaption' ) {
							$hasHref = false;
						}
						
						$isMediaFile = $this->isMediaFile( $href );
						$hasSiteUrl = true; // all files are treated and shown, even externals. Keep this for further extension.

						if ( ( $isMediaFile !== false ) && ( $hasSiteUrl !== false ) ) {
							$isMediaFile = true;
						}
					}
					$hasDivInFigure = !is_null( $item ) ? $this->hasDivInFigure( $item ) : false;

				} elseif ( ! $isYouTube ) {
					$item = $figure->querySelector( 'video' );
					!is_null( $item ) ? $videoThumb = $item->getAttribute( 'poster' ) : $videoThumb = null;
					$dataType = 'video';
				} elseif ( $isYouTube ) {
					$item = $figure->querySelector( 'iframe' );
					$dataType = 'video';
				}

				// create new dom-element and append to dom
				if ( ! is_null( $item ) && ( ( ! $hasHref && $this->hrefEmpty ) || ( $isMediaFile && $this->hrefMedia ) ) && ! $isVideo && ! $hasDivInFigure ) {

					$caption = $figure->querySelector( 'figcaption' );

					$a = $this->classCreateElement( $dom, $dataType, $caption, $item );
					$a->appendChild( $item );

					$newfigure = $dom->createElement( $tagType );
					$newfigure->setAttribute( 'class', $class );
					$newfigure->appendChild( $a );

					! is_null( $caption ) ? $newfigure->appendChild( $caption ) : null;

					$figure->parentNode->replaceChild( $newfigure, $figure );
					$this->nFound += 1;
				}
				// new method for featured images in header with tag sequence: figure-div-img. This is a new case in 2023-09.
				elseif ( ! is_null( $item ) && ( ( ! $hasHref && $this->hrefEmpty ) || ( $isMediaFile && $this->hrefMedia ) ) && ! $isVideo && $hasDivInFigure ) {

					$caption = $figure->querySelector( 'figcaption' );
					$a = $this->classCreateElement( $dom, $dataType, $caption, $item );

					$newitem = $item->cloneNode( true );
					$a->appendChild( $newitem ); // this MOVES the $item from $figure to $a! Is this a bug?

					$item->parentNode->replaceChild( $a, $item );
					$this->nFound += 1;
				}
				// handle html5 videos here
				elseif ( ! is_null( $item ) && $isVideo && ! $isYouTube ) {

					$caption = $figure->querySelector( 'figcaption' );
					$a = $this->classCreateElement( $dom, $dataType, $caption, $item, $videoThumb );

					// create the button to open the lightbox
					$lbdiv = $dom->createElement( 'div' );
					$lbdiv->setAttribute( 'class', 'yt-button-simple-fslb-mvb' );
					$a->appendChild( $lbdiv );

					$newfigure = $dom->createElement( $tagType );
					$newfigure->setAttribute( 'class', $class );
					$newfigure->appendChild( $a );
					$newfigure->appendChild( $item );

					$figure->parentNode->replaceChild( $newfigure, $figure );
					$this->nFound += 1;
				}
				// handle YouTube Videos here
				elseif ( ! is_null( $item ) && $isVideo && $isYouTube ) {

					$a = $dom->createElement( 'a' );
					$a->setAttribute( 'data-fslightbox', '1' ); // Mind: This is used in javascript, too!   //$a->setAttribute('data-type', $dataType); // Does not work with YouTube
					$a->setAttribute( 'aria-label', 'Open fullscreen lightbox with current ' . $dataType );

					$href = $item->getAttribute( 'src' );
					$ytHref = $href;
					$ytHref = \str_replace( 'youtube.com', 'youtube-nocookie.com', $ytHref );
					$ytHref = \str_replace( 'feature=oembed', 'feature=oembed&enablejsapi=1', $ytHref );

					$item->setAttribute( 'src', $ytHref );

					$href = explode( '?', $href )[0];
					$a->setAttribute( 'href', $href );

					// get the ID and thumbnail from img.youtube.com/vi/[Video-ID]/default.jpg. source: https://internetzkidz.de/2021/03/youtube-thumbnail-url/
					$ytID = explode( '/', $href );
					$ytID = end( $ytID );
					$videoThumbUrl = 'https://img.youtube.com/vi/' . $ytID . '/default.jpg';
					// Get the video thumbnail. Use get_headers() function
					$headers = @get_headers( $videoThumbUrl );
					// TODO: @get_headers() ist blockierend; du setzt den Daumen sowieso optional—mach das per späterem JS oder zwischengespeichert.
					// Use condition to check the existence of URL
					$headers && strpos( $headers[0], '200' ) ? $a->setAttribute( 'data-thumb', $videoThumbUrl ) : null;

					// create the button to open the lightbox
					$lbdiv = $dom->createElement( 'div' );
					$lbdiv->setAttribute( 'class', 'yt-button-simple-fslb-mvb' );

					$a->appendChild( $lbdiv );

					$newfigure = $dom->createElement( $tagType );
					$newfigure->setAttribute( 'class', $class );
					$newfigure->appendChild( $a );
					$newfigure->appendChild( $item );

					$figure->parentNode->replaceChild( $newfigure, $figure );
					$this->nFound += 1;
				}
			}
		}

		// finally prepare the html to send to browser
		if ( $this->nFound > 0 ) {
			if ($container instanceof \IvoPetkov\HTML5DOMElement) {
				// Variante A: neutral
				$out = '';
				foreach ($container->childNodes as $child) {
						$out .= $dom->saveHTML($child);
				}

				// Variante B: falls unterstützt
				//$inner = $container->innerHTML; // direkt nur der Inhalt im Wrapper
    			//$content = "<!-- simple-lightbox-fslight processed figures -->" . $inner;
    			//return $content;

				// add an html comment to show that fslightbox processed the content
				$content = "<!-- simple-lightbox-fslight processed figures -->" . $out;
				return $content;

			} else {
				// Fallback: wenn etwas schief ging, Original zurück
				return $originalContent;
			}
		} else {
			// Fallback: wenn nichts geaendert wurde, Original zurueckgeben
			return $originalContent;
		}
	}

	/**
	 * Create the HTML5DOMElement with A-Tag and attributes
	 *
	 * @param  \IvoPetkov\HTML5DOMDocument   $dom the dom-object to which the element shall be appended
	 * @param  string      $dataType either image or video type
	 * @param  \IvoPetkov\HTML5DOMElement|null $caption the caption of the image
	 * @param  \IvoPetkov\HTML5DOMElement      $item theo originating item in the figure which is being processed
	 * @param  string|null $videoThumb the video thumbnail
	 * @return \IvoPetkov\HTML5DOMElement|false      the new generated A-Tag as \IvoPetkov\HTML5DOMElement written as \DOMElement for PHPStan LVL 8
	 */
	private function classCreateElement( \IvoPetkov\HTML5DOMDocument $dom, string $dataType, &$caption, \IvoPetkov\HTML5DOMElement &$item, ?string $videoThumb = '' ) {
		$a = $dom->createElement( 'a' );
		$a->setAttribute( 'data-fslightbox', '1' ); // Mind: This is used in javascript, too!
		$a->setAttribute( 'data-type', $dataType );
		$a->setAttribute( 'aria-label', 'Open fullscreen lightbox with current ' . $dataType );

		if ( ! is_null( $caption ) ) {
			$a->setAttribute( 'data-caption', $caption->getNodeValue() );
		}

		if ( ! empty( $videoThumb ) ) {
			$a->setAttribute( 'data-thumb', $videoThumb );
		}

		$a->setAttribute( 'href', $item->getAttribute( 'src' ) );

		return $a;
	}

	/**
	 * a function that detects wether the domnode $item has a div tag in its parents and stops searching if tag is figure
	 *
	 * @param  object  $item
	 * @return boolean
	 */
	private function hasDivInFigure( object $item ): bool {
		// Check if $item is a DOMNode
		if ( get_class( $item ) === 'IvoPetkov\HTML5DOMElement' && $item->tagName === 'img' ) {
			// Check if $item has a <div> tag in its parents
		} else
			return false;

		// Start from the parent node
		$parent = $item->parentNode;

		while ( $parent !== null ) {
			// Check if the parent node is a <figure> tag
			if ( $parent->nodeName === 'figure' ) {
				break; // Stop searching at <figure> tag
			}

			// Check if the parent node contains a <div> tag
			// phpstan-ignore-line: is OK because $parent is not DOMNode but IvoPetkov\HTML5DOMElement.
			if ( $parent->tagName === 'div' ) { // @phpstan-ignore-line
				// Found a <div> tag in parents
				return true;
			}

			// Move up to the next parent node
			$parent = $parent->parentNode;
		}

		return false; // No <div> tag found in parents

	}

	/**
	 * Find the Css-Class from settings in the class-attribute.
	 *
	 * @param string $class The class-attribute as a string.
	 * @return array{bool,bool,bool} An array containing a boolean indicating whether the class was found and a boolean indicating whether it is a video class.
	 */
	private function findCssClass( string $class ): array {
		$classFound = false;
		$isVideo = false;
		$isYouTube = false;
		$search = '';

		foreach ( $this->cssClassesToSearch as $search ) {
			$classFound = 0;
			$classFound = strpos( $class, $search );
			if ( $classFound !== false ) {
				$classFound = true;
				break;
			}
		}

		if ( ( ( strpos( $search, 'video' ) !== false ) || ( strpos( $search, 'youtube' ) !== false ) ) && $classFound ) { // works only because $search is set to last key after break in for-loop
			$isVideo = true;
		}
		;

		if ( $isVideo && ( strpos( $search, 'youtube' ) !== false ) && $classFound ) { // works only because $search is set to last key after break in for-loop
			$isYouTube = true;
		}
		;

		return array( $classFound, $isVideo, $isYouTube );
	}

	/**
	 * Find the Css-Class in parent of the figure as DOM-Element.
	 *
	 * @param  \IvoPetkov\HTML5DOMElement $figure the class-attribute as DOM-Object
	 * @return bool
	 */
	private function parentFindCssClass( \IvoPetkov\HTML5DOMElement $figure ): bool {
		$classFound = false;
		$search = '';
		$parent = $figure->parentNode;

		if ( is_null( $parent ) ) {
			return false;
		}
		
		// Wenn es keinen Parent gibt oder der Parent kein HTML5DOMElement ist: Abbrechen
    	if (!$parent instanceof \IvoPetkov\HTML5DOMElement) {
       		return false;
    	}

		// Klassen-Attribut holen (leerer String, wenn nicht vorhanden)
		$class = $parent->getAttribute('class') ?? '';
		if ($class === '') {
			return false;
		}

		foreach ( $this->cssClassesToSearch as $search ) {
			$classFound = 0;
			$classFound = strpos( $class, $search );
			if ( $classFound !== false ) {
				$classFound = true;
				break;
			}
		}

		return $classFound;
	}

	private function isMediaFile ( string $url ): bool {
		return href_is_image( $url );
	}

	// --------------- private functions : Enqueueing ------------------------
	/**
	 * Enqueues the fslightbox.js script as basic or paid version, if available.
	 *
	 * @return void
	 */
	public function my_enqueue_script(): void {
		if ( ! $this->needs_assets ) return;

		$path = $this->plugin_main_dir . '/js/fslightbox-paid/fslightbox.js';
		$slug = plugins_url() . '/' . \basename( $this->plugin_main_dir ); // @phpstan-ignore-line

		if ( is_file( $path ) ) {
			$path = $slug . '/js/fslightbox-paid/fslightbox.js';
			\wp_register_script( 'mvb-fslightbox', $path, array(), '3.8.3', [ 'strategy'  => 'defer', 'in_footer' => true] );
			\wp_enqueue_script( 'mvb-fslightbox' );
		} else {
			$path = $this->plugin_main_dir . '/js/fslightbox-basic/fslightbox.js';
			if ( is_file( $path ) ) {
				$path = $slug . '/js/fslightbox-basic/fslightbox.js';
				\wp_register_script( 'mvb-fslightbox', $path, array(), '3.7.4', [ 'strategy'  => 'defer', 'in_footer' => true] );
				\wp_enqueue_script( 'mvb-fslightbox' );
			}
		}

		$path = $this->plugin_main_dir . '/js/simple-lightbox.min.js';
		if ( is_file( $path ) ) {
			$path = $slug . '/js/simple-lightbox.min.js';
			\wp_register_script( 'yt-script-mvb-fslightbox', $path, array( 'mvb-fslightbox' ), '3.1.0', [ 'strategy'  => 'defer', 'in_footer' => true] );
			\wp_enqueue_script( 'yt-script-mvb-fslightbox' );
		}

		$path = $this->plugin_main_dir . '/css/simple-fslightbox.css';
		if ( is_file( $path ) ) {
			$path = $slug . '/css/simple-fslightbox.css';
			\wp_enqueue_style( 'simple-fslightbox-css', $path, array(), '3.1.0', 'all' );
		}

		$this->needs_assets = false;
	}
}
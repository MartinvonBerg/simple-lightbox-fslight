<?php

use PHPUnit\Framework\TestCase;
use function Brain\Monkey\setUp;
use function Brain\Monkey\tearDown;
//use function Brain\Monkey\Functions\stubs;
use function Brain\Monkey\Functions\expect;
//use function Brain\Monkey\Actions\expectDone;
//use function Brain\Monkey\Filters\expectApplied;

include_once PLUGIN_DIR . '\classes\RewriteFigureTagsClass.php';

final class RewriteFigureTagsClassTest extends TestCase {
	public function setUp(): void {
		parent::setUp();
		setUp();
		expect( 'wp_add_inline_script' )
			->andReturn( 0 );
	}
	public function tearDown(): void {
		tearDown();
		parent::tearDown();
	}

	public function test_construct_no_file() {
		expect( 'get_site_url' )
			->once()
			->andReturn( 'http://localhost/wordpress' );

		$tested = new mvbplugins\fslightbox\RewriteFigureTags( 'no-file' );
		$this->assertInstanceOf( '\mvbplugins\fslightbox\RewriteFigureTags', $tested );

		$privateProp1 = new \ReflectionProperty( "mvbplugins\\fslightbox\RewriteFigureTags", "doRewrite" );
		$privateProp1->setAccessible( true );

		$privateProp2 = new \ReflectionProperty( "mvbplugins\\fslightbox\RewriteFigureTags", "hrefEmpty" );
		$privateProp2->setAccessible( true );

		$privateProp3 = new \ReflectionProperty( "mvbplugins\\fslightbox\RewriteFigureTags", "hrefMedia" );
		$privateProp3->setAccessible( true );

		$nFound = new \ReflectionProperty( "mvbplugins\\fslightbox\RewriteFigureTags", "nFound" );
		$nFound->setAccessible( true );

		$want_to_modify_body = new \ReflectionProperty( "mvbplugins\\fslightbox\RewriteFigureTags", "want_to_modify_body" );
		$want_to_modify_body->setAccessible( true );

		$this->assertEquals( false, $privateProp1->getValue( $tested ) );
		$this->assertEquals( true, $privateProp2->getValue( $tested ) );
		$this->assertEquals( true, $privateProp3->getValue( $tested ) );
		$this->assertEquals( 0, $nFound->getValue( $tested ) );
		$this->assertEquals( false, $want_to_modify_body->getValue( $tested ) );
	}

	public function test_construct_with_empty_settings() {
		expect( 'get_site_url' )
			->once()
			->andReturn( 'http://localhost/wordpress' );

		$tested = new mvbplugins\fslightbox\RewriteFigureTags( '/tests/phpunit/testdata/test-empty-settings.json' );
		$this->assertInstanceOf( '\mvbplugins\fslightbox\RewriteFigureTags', $tested );

		$privateProp1 = new \ReflectionProperty( "mvbplugins\\fslightbox\RewriteFigureTags", "doRewrite" );
		$privateProp1->setAccessible( true );

		$privateProp2 = new \ReflectionProperty( "mvbplugins\\fslightbox\RewriteFigureTags", "hrefEmpty" );
		$privateProp2->setAccessible( true );

		$privateProp3 = new \ReflectionProperty( "mvbplugins\\fslightbox\RewriteFigureTags", "hrefMedia" );
		$privateProp3->setAccessible( true );

		$nFound = new \ReflectionProperty( "mvbplugins\\fslightbox\RewriteFigureTags", "nFound" );
		$nFound->setAccessible( true );

		$want_to_modify_body = new \ReflectionProperty( "mvbplugins\\fslightbox\RewriteFigureTags", "want_to_modify_body" );
		$want_to_modify_body->setAccessible( true );

		$this->assertEquals( false, $privateProp1->getValue( $tested ) );
		$this->assertEquals( true, $privateProp2->getValue( $tested ) );
		$this->assertEquals( true, $privateProp3->getValue( $tested ) );
		$this->assertEquals( 0, $nFound->getValue( $tested ) );
		$this->assertEquals( false, $want_to_modify_body->getValue( $tested ) );
	}

	public function test_construct_with_standard_settings() {

		expect( 'get_site_url' )
			->once()
			->andReturn( 'http://localhost/wordpress' );

		$tested = new mvbplugins\fslightbox\RewriteFigureTags( '/tests/phpunit/testdata/plugin-settings-body.json' );
		$this->assertInstanceOf( '\mvbplugins\fslightbox\RewriteFigureTags', $tested );

		$privateProp1 = new \ReflectionProperty( "mvbplugins\\fslightbox\RewriteFigureTags", "excludeIds" );
		$privateProp1->setAccessible( true );
		$this->assertEquals( 0, $privateProp1->getValue( $tested )[0] );

		$privateProp2 = new \ReflectionProperty( "mvbplugins\\fslightbox\RewriteFigureTags", "want_to_modify_body" );
		$privateProp2->setAccessible( true );
		$this->assertEquals( true, $privateProp2->getValue( $tested ) );
	}

	public function test_construct_and_prepare() {

		expect( 'get_site_url' )
			->once()
			->andReturn( 'http://localhost/wordpress' );

		expect( 'get_post_type' )
			->once()
			->andReturn( 'post' );

		expect( 'get_the_ID' )
			->once()
			->andReturn( -1 );

		expect( 'is_admin' )
			->once()
			->andReturn( false );

		$class = new \ReflectionClass( 'mvbplugins\fslightbox\RewriteFigureTags' );
		$privateMethod = $class->getMethod( 'prepare' );
		$privateMethod->setAccessible( TRUE );


		$privateProp1 = new \ReflectionProperty( "mvbplugins\\fslightbox\RewriteFigureTags", "doRewrite" );
		$privateProp1->setAccessible( true );

		$privateProp2 = new \ReflectionProperty( "mvbplugins\\fslightbox\RewriteFigureTags", "hrefEmpty" );
		$privateProp2->setAccessible( true );

		$privateProp3 = new \ReflectionProperty( "mvbplugins\\fslightbox\RewriteFigureTags", "hrefMedia" );
		$privateProp3->setAccessible( true );

		$tested = new mvbplugins\fslightbox\RewriteFigureTags();
		$privateMethod->invoke( $tested );

		$this->assertInstanceOf( '\mvbplugins\fslightbox\RewriteFigureTags', $tested );
		$this->assertEquals( true, $privateProp1->getValue( $tested ) );
		$this->assertEquals( true, $privateProp2->getValue( $tested ) );
		$this->assertEquals( true, $privateProp3->getValue( $tested ) );
	}

	/**
	 * @dataProvider htmlProvider
	 */
	public function test_changeFigureTagsInContent( $htmlin, $expected ) {
		expect( 'get_site_url' )
			->once()
			->andReturn( 'http://localhost/wordpress' );

		expect( 'get_post_type' )
			->once()
			->andReturn( 'post' );

		expect( 'get_the_ID' )
			->once()
			->andReturn( -1 );

		expect( 'is_admin' )
			->once()
			->andReturn( false );

		expect( 'wp_remote_head' )
			->andReturn( 'header' );

		expect( 'wp_remote_retrieve_header' )
			->andReturn( 'image/jpeg' );

		$class = new \ReflectionClass( 'mvbplugins\fslightbox\RewriteFigureTags' );
		$privateMethod = $class->getMethod( 'rewriteHTML' );
		$privateMethod->setAccessible( TRUE );

		$tested = new mvbplugins\fslightbox\RewriteFigureTags( '/tests/phpunit/testdata/plugin-settings-body.json' );
		$this->assertInstanceOf( '\mvbplugins\fslightbox\RewriteFigureTags', $tested );

		$nFound = new \ReflectionProperty( "mvbplugins\\fslightbox\RewriteFigureTags", "nFound" );
		$nFound->setAccessible( true );

		$out = $tested->changeFigureTagsInContent( '' );
		$this->assertEquals( '', $out );
		$this->assertEquals( 0, $nFound->getValue( $tested ) );

		$result = $privateMethod->invoke( $tested, $htmlin );

		$this->assertEquals( $expected, $result );

		if ( $result === $htmlin ) {
			$this->assertEquals( 0, $nFound->getValue( $tested ) );
		} else {
			$this->assertEquals( 1, $nFound->getValue( $tested ) );
		}

	}

	public function test_add_scripts_to_html() {
		expect( 'get_site_url' )
			->once()
			->andReturn( 'http://localhost/wordpress' );

		$tested = new mvbplugins\fslightbox\RewriteFigureTags();
		$this->assertInstanceOf( '\mvbplugins\fslightbox\RewriteFigureTags', $tested );

		$out = $tested->rewrite_body_add_scripts();
		$expected = "<script defer src='http://localhost/wordpress/wp-content/plugins/simple-lightbox-fslight/simple-lightbox-fslight/js/fslightbox-paid/fslightbox.js' id='fslightbox-js'></script><script defer src='http://localhost/wordpress/wp-content/plugins/simple-lightbox-fslight/simple-lightbox-fslight/js/simple-lightbox.min.js' id='yt-script-js'></script><link rel='stylesheet' id='simple-fslightbox-css' href='http://localhost/wordpress/wp-content/plugins/simple-lightbox-fslight/simple-lightbox-fslight/css/simple-fslightbox.css' media='all' />";
		$this->assertEquals( $expected, $out );
	}

	// --------------------------------------
	/**
	 * @dataProvider htmlProvider
	 */
	public function test_RewriteClass_rewriter( $htmlin, $expected ) {

		expect( 'get_site_url' )
			->once()
			->andReturn( 'http://localhost/wordpress' );

		expect( 'wp_remote_head' )
			->andReturn( 'header' );

		expect( 'wp_remote_retrieve_header' )
			->andReturn( 'image/jpeg' );

		$class = new \ReflectionClass( 'mvbplugins\fslightbox\RewriteFigureTags' );
		$privateMethod = $class->getMethod( 'rewriteHTML' );
		$privateMethod->setAccessible( TRUE );

		$tested = new mvbplugins\fslightbox\RewriteFigureTags( '/tests/phpunit/testdata/plugin-settings-body.json' );
		$this->assertInstanceOf( '\mvbplugins\fslightbox\RewriteFigureTags', $tested );

		$out = $privateMethod->invoke( $tested, '' );
		$this->assertEquals( '', $out );

		$out = $privateMethod->invoke( $tested, '<body><div></div</body>' );
		$this->assertEquals( '<body><div></div</body>', $out );

		$out = $privateMethod->invoke( $tested, '<body><dib><figure><img></img></figure></div</body>' );
		$this->assertEquals( '<body><dib><figure><img></img></figure></div</body>', $out );

		$result = $privateMethod->invoke( $tested, $htmlin );
		$this->assertEquals( $expected, $result );

	}

	public function test_RewriteClass_3() {

		expect( 'get_site_url' )
			->once()
			->andReturn( 'http://localhost/wordpress' );

		expect( 'get_post_type' )
			->once()
			->andReturn( 'no-post' );

		expect( 'get_the_ID' )
			->once()
			->andReturn( -1 );

		$class = new \ReflectionClass( 'mvbplugins\fslightbox\RewriteFigureTags' );
		$privateMethod = $class->getMethod( 'prepare' );
		$privateMethod->setAccessible( TRUE );


		$privateProp1 = new \ReflectionProperty( "mvbplugins\\fslightbox\RewriteFigureTags", "doRewrite" );
		$privateProp1->setAccessible( true );

		$privateProp2 = new \ReflectionProperty( "mvbplugins\\fslightbox\RewriteFigureTags", "hrefEmpty" );
		$privateProp2->setAccessible( true );

		$privateProp3 = new \ReflectionProperty( "mvbplugins\\fslightbox\RewriteFigureTags", "hrefMedia" );
		$privateProp3->setAccessible( true );

		$tested = new mvbplugins\fslightbox\RewriteFigureTags();
		$privateMethod->invoke( $tested );

		$this->assertInstanceOf( '\mvbplugins\fslightbox\RewriteFigureTags', $tested );
		$this->assertEquals( false, $privateProp1->getValue( $tested ) );
		$this->assertEquals( true, $privateProp2->getValue( $tested ) );
		$this->assertEquals( true, $privateProp3->getValue( $tested ) );
	}

	/**
	 * @dataProvider CssClassProvider
	 */
	public function test_findCssClass_1( $inpclass, $exp1, $exp2 ) {

		expect( 'get_site_url' )
			->once()
			->andReturn( 'http://localhost/wordpress' );

		expect( 'get_post_type' )
			->once()
			->andReturn( 'post' );

		expect( 'get_the_ID' )
			->andReturn( -1 );

		expect( 'is_admin' )
			->once()
			->andReturn( false );

		$class = new \ReflectionClass( 'mvbplugins\fslightbox\RewriteFigureTags' );
		$privateMethod = $class->getMethod( 'findCssClass' );
		$privateMethod->setAccessible( TRUE );

		$prepare = $class->getMethod( 'prepare' );
		$prepare->setAccessible( TRUE );

		$tested = new mvbplugins\fslightbox\RewriteFigureTags( '/tests/phpunit/testdata/plugin-settings-body.json' );
		$prepare->invoke( $tested );

		[ $result1, $result2 ] = $privateMethod->invoke( $tested, $inpclass );
		$this->assertEquals( $exp1, $result1 );
		$this->assertEquals( $exp2, $result2 );
	}

	public function test_has_div_in_figure() {
		expect( 'get_site_url' )
			->once()
			->andReturn( 'http://localhost/wordpress' );

		$class = new \ReflectionClass( 'mvbplugins\fslightbox\RewriteFigureTags' );
		$privateMethod = $class->getMethod( 'hasDivInFigure' );
		$privateMethod->setAccessible( TRUE );

		$tested = new mvbplugins\fslightbox\RewriteFigureTags();

		$out = $privateMethod->invoke( $tested, new stdClass() );
		$this->assertEquals( false, $out );

		$dom = new DOMDocument( '1.0', 'utf-8' );
		$element = $dom->createElement( 'figure', '' );
		// We insert the new element as root (child of the document)
		$dom->appendChild( $element );

		$element2 = $dom->createElement( 'div', '' );
		$element3 = $dom->createElement( 'img', '' );
		$element->appendChild( $element2 );
		$element2->appendChild( $element3 );

		$out = $privateMethod->invoke( $tested, $dom );
		$this->assertEquals( false, $out );

		$html = $dom->saveHTML();
		$html = preg_replace( "/\r|\n/", "", $html );
		$this->assertEquals( '<figure><div><img></div></figure>', $html );

		$dom = new \IvoPetkov\HTML5DOMDocument();
		$content = '<figure class="featured-media"><div class="featured-media-inner section-inner"><img width="1200" height="904" src="http://localhost/wordpress/wp-content/uploads/2023/04/PXL_20230414_134820368-scaled.jpg" class="attachment-post-thumbnail size-post-thumbnail wp-post-image" alt="" decoding="async" fetchpriority="high" srcset="http://localhost/wordpress/wp-content/uploads/2023/04/PXL_20230414_134820368-scaled.jpg 2560w, http://localhost/wordpress/wp-content/uploads/2023/04/PXL_20230414_134820368-300x226.jpg 300w, http://localhost/wordpress/wp-content/uploads/2023/04/PXL_20230414_134820368-1024x771.jpg 1024w, http://localhost/wordpress/wp-content/uploads/2023/04/PXL_20230414_134820368-768x578.jpg 768w, http://localhost/wordpress/wp-content/uploads/2023/04/PXL_20230414_134820368-1536x1157.jpg 1536w, http://localhost/wordpress/wp-content/uploads/2023/04/PXL_20230414_134820368-2048x1542.jpg 2048w" sizes="(max-width: 1200px) 100vw, 1200px" /></div><!-- .featured-media-inner --></figure><!-- .featured-media -->';
		$dom->loadHTML( $content, 67108864 );
		$img = $dom->querySelectorAll( 'img' )[0];

		$out = $privateMethod->invoke( $tested, $img );
		$this->assertEquals( true, $out );
	}

	public function htmlProvider(): array {
		return [ 
			/* omitted because special treatment for postie-image-div removed
			['<div class="postie-image-div"><a href="https://www.berg-reise-foto.de/smrtzl/uploads/2018/12/PSX_20181225_174247.jpg"><img class="postie-image" style="border: none;" title="PSX_20181225_174247.jpg" src="https://www.berg-reise-foto.de/smrtzl/uploads/2018/12/PSX_20181225_174247-150x150.jpg" alt="PSX_20181225_174247.jpg"></a></div>', 
			 '<div class="postie-image-div"><a data-fslightbox="1" data-type="image" aria-label="Open fullscreen lightbox with current image" href="https://www.berg-reise-foto.de/smrtzl/uploads/2018/12/PSX_20181225_174247-150x150.jpg"><img class="postie-image" style="border: none;" title="PSX_20181225_174247.jpg" src="https://www.berg-reise-foto.de/smrtzl/uploads/2018/12/PSX_20181225_174247-150x150.jpg" alt="PSX_20181225_174247.jpg"></a></div>'],
			
			['<div class="postie-image-div"><a href="https://www.berg-reise-foto.de/"><img class="postie-image" style="border: none;" title="none" src="https://www.berg-reise-foto.de/" alt="none"></a></div>', 
			 '<div class="postie-image-div"><a data-fslightbox="1" data-type="image" aria-label="Open fullscreen lightbox with current image" href="https://www.berg-reise-foto.de/"><img class="postie-image" style="border: none;" title="none" src="https://www.berg-reise-foto.de/" alt="none"></a></div>'],
			*/
			[ 
				'<span class="postie-image-div"><a href="https://www.berg-reise-foto.de/smrtzl/uploads/2018/12/PSX_20181225_174247.jpg"><img class="postie-image" style="border: none;" title="PSX_20181225_174247.jpg" src="https://www.berg-reise-foto.de/smrtzl/uploads/2018/12/PSX_20181225_174247-150x150.jpg" alt="PSX_20181225_174247.jpg"></a></span>',
				'<span class="postie-image-div"><a href="https://www.berg-reise-foto.de/smrtzl/uploads/2018/12/PSX_20181225_174247.jpg"><img class="postie-image" style="border: none;" title="PSX_20181225_174247.jpg" src="https://www.berg-reise-foto.de/smrtzl/uploads/2018/12/PSX_20181225_174247-150x150.jpg" alt="PSX_20181225_174247.jpg"></a></span>'
			],

			[ 
				'<figure class="wp-block-video"><video controls src="http://localhost/wordpress/wp-content/uploads/2022/04/sample-mp4-file.mp4"></video></figure>',
				'<figure class="wp-block-video"><a data-fslightbox="1" data-type="video" aria-label="Open fullscreen lightbox with current video" href="http://localhost/wordpress/wp-content/uploads/2022/04/sample-mp4-file.mp4"><div class="yt-button-simple-fslb-mvb"></div></a><video controls src="http://localhost/wordpress/wp-content/uploads/2022/04/sample-mp4-file.mp4"></video></figure>'
			],

			[ 
				'<figure class="wp-block-video"><video controls src="http://localhost/wordpress/wp-content/uploads/2022/04/sample-mp4-file.mp4" poster="test.jpg"></video></figure>',
				'<figure class="wp-block-video"><a data-fslightbox="1" data-type="video" aria-label="Open fullscreen lightbox with current video" data-thumb="test.jpg" href="http://localhost/wordpress/wp-content/uploads/2022/04/sample-mp4-file.mp4"><div class="yt-button-simple-fslb-mvb"></div></a><video controls src="http://localhost/wordpress/wp-content/uploads/2022/04/sample-mp4-file.mp4" poster="test.jpg"></video></figure>'
			],

			[ 
				'<figure class="wp-block-image size-large is-style-default"><a href="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg" alt="" class="wp-image-5035" srcset="http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg 1024w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-300x189.jpg 300w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-768x485.jpg 768w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1536x969.jpg 1536w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-2048x1292.jpg 2048w" sizes="(max-width: 1024px) 100vw, 1024px"></a></figure>',
				'<figure class="wp-block-image size-large is-style-default"><a data-fslightbox="1" data-type="image" aria-label="Open fullscreen lightbox with current image" href="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg" alt="" class="wp-image-5035" srcset="http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg 1024w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-300x189.jpg 300w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-768x485.jpg 768w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1536x969.jpg 1536w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-2048x1292.jpg 2048w" sizes="(max-width: 1024px) 100vw, 1024px"></a></figure>'
			],

			[ 
				'<figure class="wp-block-image size-large is-style-default"><a href="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg" alt="" class="wp-image-5035"></a><figcaption>Italien</figcaption></figure>',
				'<figure class="wp-block-image size-large is-style-default"><a data-fslightbox="1" data-type="image" aria-label="Open fullscreen lightbox with current image" data-caption="Italien" href="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg" alt="" class="wp-image-5035"></a><figcaption>Italien</figcaption></figure>'
			],

			[ 
				'<figure class="wp-block-image size-large is-style-default"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg" alt="" class="wp-image-5035"></a><figcaption>Italien</figcaption></figure>',
				'<figure class="wp-block-image size-large is-style-default"><a data-fslightbox="1" data-type="image" aria-label="Open fullscreen lightbox with current image" data-caption="Italien" href="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg" alt="" class="wp-image-5035"></a><figcaption>Italien</figcaption></figure>'
			],

			[ 
				'<div class="wp-block-image size-large is-style-default"><figure class="aligncenter"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg" alt="" class="wp-image-5035"></a><figcaption>Italien</figcaption></figure></div>',
				'<div class="wp-block-image size-large is-style-default"><figure class="aligncenter"><a data-fslightbox="1" data-type="image" aria-label="Open fullscreen lightbox with current image" data-caption="Italien" href="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg" alt="" class="wp-image-5035"></a><figcaption>Italien</figcaption></figure></div>'
			],

			[ 
				'<div class="postie-image"><figure class="aligncenter"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg" alt="" class="wp-image-5035"></a><figcaption>Italien</figcaption></figure></div>',
				'<div class="postie-image"><figure class="aligncenter"><a data-fslightbox="1" data-type="image" aria-label="Open fullscreen lightbox with current image" data-caption="Italien" href="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg" alt="" class="wp-image-5035"></a><figcaption>Italien</figcaption></figure></div>'
			],

			[ 
				'<figure class="wp-block-image"><a href="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"></a><figcaption>Hier gehts nach <a href="link-irgenwohin">Italien</a></figcaption></figure>',
				'<figure class="wp-block-image"><a data-fslightbox="1" data-type="image" aria-label="Open fullscreen lightbox with current image" data-caption="Hier gehts nach Italien" href="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"></a><figcaption>Hier gehts nach <a href="link-irgenwohin">Italien</a></figcaption></figure>'
			],

			[ 
				'<figure class="wp-block-image"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"></a><figcaption>Hier gehts nach <a href="link-irgenwohin">Italien</a></figcaption></figure>',
				'<figure class="wp-block-image"><a data-fslightbox="1" data-type="image" aria-label="Open fullscreen lightbox with current image" data-caption="Hier gehts nach Italien" href="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"></a><figcaption>Hier gehts nach <a href="link-irgenwohin">Italien</a></figcaption></figure>'
			],

			[ 
				'<figure class="wp-block-image size-large is-style-default"><a href="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg" alt="" class="wp-image-5035" srcset="http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg 1024w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-300x189.jpg 300w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-768x485.jpg 768w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1536x969.jpg 1536w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-2048x1292.jpg 2048w" sizes="(max-width: 1024px) 100vw, 1024px"></a></figure>',
				'<figure class="wp-block-image size-large is-style-default"><a data-fslightbox="1" data-type="image" aria-label="Open fullscreen lightbox with current image" href="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg" alt="" class="wp-image-5035" srcset="http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg 1024w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-300x189.jpg 300w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-768x485.jpg 768w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1536x969.jpg 1536w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-2048x1292.jpg 2048w" sizes="(max-width: 1024px) 100vw, 1024px"></a></figure>'
			],

			[ 
				'<figure class="featured-media"><div class="featured-media-inner section-inner"><img width="1200" height="904" src="http://localhost/wordpress/wp-content/uploads/2023/04/PXL_20230414_134820368-scaled.jpg" class="attachment-post-thumbnail size-post-thumbnail wp-post-image" alt="" decoding="async" fetchpriority="high" srcset="http://localhost/wordpress/wp-content/uploads/2023/04/PXL_20230414_134820368-scaled.jpg 2560w, http://localhost/wordpress/wp-content/uploads/2023/04/PXL_20230414_134820368-300x226.jpg 300w, http://localhost/wordpress/wp-content/uploads/2023/04/PXL_20230414_134820368-1024x771.jpg 1024w, http://localhost/wordpress/wp-content/uploads/2023/04/PXL_20230414_134820368-768x578.jpg 768w, http://localhost/wordpress/wp-content/uploads/2023/04/PXL_20230414_134820368-1536x1157.jpg 1536w, http://localhost/wordpress/wp-content/uploads/2023/04/PXL_20230414_134820368-2048x1542.jpg 2048w" sizes="(max-width: 1200px) 100vw, 1200px" /></div><!-- .featured-media-inner --></figure><!-- .featured-media -->',
				'<figure class="featured-media"><div class="featured-media-inner section-inner"><a data-fslightbox="1" data-type="image" aria-label="Open fullscreen lightbox with current image" href="http://localhost/wordpress/wp-content/uploads/2023/04/PXL_20230414_134820368-scaled.jpg"><img width="1200" height="904" src="http://localhost/wordpress/wp-content/uploads/2023/04/PXL_20230414_134820368-scaled.jpg" class="attachment-post-thumbnail size-post-thumbnail wp-post-image" alt="" decoding="async" fetchpriority="high" srcset="http://localhost/wordpress/wp-content/uploads/2023/04/PXL_20230414_134820368-scaled.jpg 2560w, http://localhost/wordpress/wp-content/uploads/2023/04/PXL_20230414_134820368-300x226.jpg 300w, http://localhost/wordpress/wp-content/uploads/2023/04/PXL_20230414_134820368-1024x771.jpg 1024w, http://localhost/wordpress/wp-content/uploads/2023/04/PXL_20230414_134820368-768x578.jpg 768w, http://localhost/wordpress/wp-content/uploads/2023/04/PXL_20230414_134820368-1536x1157.jpg 1536w, http://localhost/wordpress/wp-content/uploads/2023/04/PXL_20230414_134820368-2048x1542.jpg 2048w" sizes="(max-width: 1200px) 100vw, 1200px"></a></div><!-- .featured-media-inner --></figure><!-- .featured-media -->'
			],

		];
	}

	public function CssClassProvider(): array {
		return [ 
			[ 'wp-block-image-ewer', true, false ],
			[ "wp-block-media-text-fdsdf", true, false ],
			[ "wp-block-video-666fgh", true, true ],
			[ "this-postie-image-dfdfg", true, false ],
			[ "not-in-array-class", false, false ],
		];
	}

	public function htmlProviderNoImage(): array {
		return [ 
			[ 
				'<figure class="wp-block-image size-large is-style-default"><a href="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg" alt="" class="wp-image-5035" srcset="http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg 1024w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-300x189.jpg 300w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-768x485.jpg 768w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1536x969.jpg 1536w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-2048x1292.jpg 2048w" sizes="(max-width: 1024px) 100vw, 1024px"></a></figure>',
				'<figure class="wp-block-image size-large is-style-default"><a href="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg" alt="" class="wp-image-5035" srcset="http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg 1024w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-300x189.jpg 300w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-768x485.jpg 768w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1536x969.jpg 1536w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-2048x1292.jpg 2048w" sizes="(max-width: 1024px) 100vw, 1024px"></a></figure>',
			],

		];
	}

	/**
	 * @dataProvider htmlProviderNoImage
	 */
	public function test_rewriteTagFunctionNoImageTag( $htmlin, $expected ) {

		expect( 'get_site_url' )
			->once()
			->andReturn( 'http://localhost/wordpress' );

		expect( 'get_post_type' )
			->once()
			->andReturn( 'post' );

		expect( 'wp_remote_head' )
			->once()
			->andReturn( 'header' );

		expect( 'wp_remote_retrieve_header' )
			->once()
			->andReturn( 'text/json' );

		expect( 'get_the_ID' )
			->once()
			->andReturn( -1 );

		expect( 'is_admin' )
			->once()
			->andReturn( false );

		$tested = new mvbplugins\fslightbox\RewriteFigureTags();

		$result = $tested->changeFigureTagsInContent( $htmlin );
		$this->assertEquals( $expected, $result );
	}

	public function htmlProviderNoPost(): array {
		return [ 
			[ 
				'<figure class="wp-block-image size-large is-style-default"><a href="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg" alt="" class="wp-image-5035" srcset="http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg 1024w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-300x189.jpg 300w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-768x485.jpg 768w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1536x969.jpg 1536w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-2048x1292.jpg 2048w" sizes="(max-width: 1024px) 100vw, 1024px"></a></figure>',
				'<figure class="wp-block-image size-large is-style-default"><a href="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg" alt="" class="wp-image-5035" srcset="http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg 1024w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-300x189.jpg 300w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-768x485.jpg 768w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1536x969.jpg 1536w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-2048x1292.jpg 2048w" sizes="(max-width: 1024px) 100vw, 1024px"></a></figure>',
			],

		];
	}

	/**
	 * @dataProvider htmlProviderNoPost
	 */
	public function test_rewriteTagFunctionNoPost( $htmlin, $expected ) {

		expect( 'get_site_url' )
			->once()
			->andReturn( 'http://localhost/wordpress' );

		expect( 'get_post_type' )
			->once()
			->andReturn( 'attachment' );

		expect( 'get_the_ID' )
			->once()
			->andReturn( -1 );

		$tested = new mvbplugins\fslightbox\RewriteFigureTags();

		$result = $tested->changeFigureTagsInContent( $htmlin );
		$this->assertEquals( $expected, $result );
	}

	public function htmlProviderExludeID(): array {
		return [ 
			[ 
				'<figure class="wp-block-image size-large is-style-default"><a href="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg" alt="" class="wp-image-5035" srcset="http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg 1024w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-300x189.jpg 300w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-768x485.jpg 768w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1536x969.jpg 1536w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-2048x1292.jpg 2048w" sizes="(max-width: 1024px) 100vw, 1024px"></a></figure>',
				'<figure class="wp-block-image size-large is-style-default"><a href="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg" alt="" class="wp-image-5035" srcset="http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg 1024w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-300x189.jpg 300w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-768x485.jpg 768w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1536x969.jpg 1536w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-2048x1292.jpg 2048w" sizes="(max-width: 1024px) 100vw, 1024px"></a></figure>',
			],

		];
	}

	/**
	 * @dataProvider htmlProviderExludeID
	 */
	public function test_rewriteTagFunctionExcludeID( $htmlin, $expected ) {
		expect( 'get_site_url' )
			->once()
			->andReturn( 'http://localhost/wordpress' );

		expect( 'get_post_type' )
			->once()
			->andReturn( 'post' );

		expect( 'get_the_ID' )
			->once()
			->andReturn( 0 );

		$tested = new mvbplugins\fslightbox\RewriteFigureTags();

		$result = $tested->changeFigureTagsInContent( $htmlin );
		$this->assertEquals( $expected, $result );
	}

}
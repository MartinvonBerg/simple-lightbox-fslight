<?php
use PHPUnit\Framework\TestCase;
use function Brain\Monkey\setUp;
use function Brain\Monkey\tearDown;
use function Brain\Monkey\Functions\stubs;
use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Actions\expectDone;
use function Brain\Monkey\Filters\expectApplied;

include_once 'C:\Bitnami\wordpress-5.2.2-0\apps\wordpress\htdocs\wp-content\plugins\simple-lightbox-fslight\classes\RewriteFigureTagsClass.php';

final class RewriteFigureTagsClassTest extends TestCase {
	public function setUp(): void {
		parent::setUp();
		setUp();
        expect('wp_add_inline_script')
            ->andReturn(0);
	}
	public function tearDown(): void {
		tearDown();
		parent::tearDown();
	}

    public function test_RewriteClass_1 () {

		expect( 'get_site_url' )
			->once()
			->andReturn('http://localhost/wordpress');

		expect( 'get_post_type' )
			->once()
			->andReturn('post');	

        expect( 'get_the_ID' )
			->once()
			->andReturn(-1);    

        $tested = new mvbplugins\fslightbox\RewriteFigureTags();
        $this->assertInstanceOf('\mvbplugins\fslightbox\RewriteFigureTags', $tested);
    }

	public function test_RewriteClass_2 () {

		expect( 'get_site_url' )
			->once()
			->andReturn('http://localhost/wordpress');

		expect( 'get_post_type' )
			->once()
			->andReturn('post');	

        expect( 'get_the_ID' )
			->once()
			->andReturn(-1);

        //$class = new \ReflectionClass('mvbplugins\fslightbox\RewriteFigureTags');
		$privateProp1 = new \ReflectionProperty("mvbplugins\\fslightbox\RewriteFigureTags", "doRewrite");
		$privateProp1->setAccessible(true);

		$privateProp2 = new \ReflectionProperty("mvbplugins\\fslightbox\RewriteFigureTags", "hrefEmpty");
		$privateProp2->setAccessible(true);

		$privateProp3 = new \ReflectionProperty("mvbplugins\\fslightbox\RewriteFigureTags", "hrefMedia");
		$privateProp3->setAccessible(true);

        $tested = new mvbplugins\fslightbox\RewriteFigureTags();
        $this->assertInstanceOf('\mvbplugins\fslightbox\RewriteFigureTags', $tested);
		$this->assertEquals($privateProp1->getValue($tested), true);
		$this->assertEquals($privateProp2->getValue($tested), true);
		$this->assertEquals($privateProp3->getValue($tested), true);
    }

	public function test_RewriteClass_3 () {

		expect( 'get_site_url' )
			->once()
			->andReturn('http://localhost/wordpress');

		expect( 'get_post_type' )
			->once()
			->andReturn('other');	

        expect( 'get_the_ID' )
			->once()
			->andReturn(-1);
            
        //$class = new \ReflectionClass('mvbplugins\fslightbox\RewriteFigureTags');
		$privateProp1 = new \ReflectionProperty("mvbplugins\\fslightbox\RewriteFigureTags", "doRewrite");
		$privateProp1->setAccessible(true);

		$tested = new mvbplugins\fslightbox\RewriteFigureTags();
        $this->assertInstanceOf('\mvbplugins\fslightbox\RewriteFigureTags', $tested);
		$this->assertEquals($privateProp1->getValue($tested), false);
    }

	/**
     * @dataProvider CssClassProvider
     */
	public function test_findCssClass_1 ($inpclass, $exp1, $exp2) {

		expect( 'get_site_url' )
			->once()
			->andReturn('http://localhost/wordpress');

		expect( 'get_post_type' )
			->once()
			->andReturn('post');	

        expect( 'get_the_ID' )
			->andReturn(-1);

		$class = new \ReflectionClass('mvbplugins\fslightbox\RewriteFigureTags');
		$privateMethod = $class->getMethod('findCssClass');
        $privateMethod->setAccessible(TRUE);

		$tested = new mvbplugins\fslightbox\RewriteFigureTags();
      
		[$result1, $result2] = $privateMethod->invoke( $tested, $inpclass);
		$this->assertEquals( $result1, $exp1);
		$this->assertEquals( $result2, $exp2);
    }

	public function CssClassProvider(): array 
	{
		return [
			['wp-block-image-ewer',true, false],
			["wp-block-media-text-fdsdf",true, false],
			["wp-block-video-666fgh", true, true],
			["this-postie-image-dfdfg", true, false],
			["not-in-array-class", false, false],
		];
	}

	/**
     * @dataProvider htmlProvider
     */
	public function test_rewriteTagFunction ($htmlin, $expected) {

		expect( 'get_site_url' )
			->once()
			->andReturn('http://localhost/wordpress');

		expect( 'get_post_type' )
			->once()
			->andReturn('post');	

		expect('wp_remote_head')
			->andReturn('header');
		
		expect('wp_remote_retrieve_header')
			->andReturn('image/jpeg');

		expect('wp_enqueue_script')
			->andReturn( true);

        expect( 'get_the_ID' )
			->once()
			->andReturn(-1);

		$tested = new mvbplugins\fslightbox\RewriteFigureTags();
      
		$result = $tested->lightbox_gallery_for_gutenberg($htmlin);
		$result = str_replace(array("\r", "\n", "<!DOCTYPE html><html>", '</html>', '<body>', '</body>'), '', $result);
		$this->assertEquals( $result, $expected);
    }

	public function htmlProvider(): array 
	{
		return [
			['', ''],
			['<div class="postie-image-div"><a href="https://www.berg-reise-foto.de/smrtzl/uploads/2018/12/PSX_20181225_174247.jpg"><img class="postie-image" style="border: none;" title="PSX_20181225_174247.jpg" src="https://www.berg-reise-foto.de/smrtzl/uploads/2018/12/PSX_20181225_174247-150x150.jpg" alt="PSX_20181225_174247.jpg"></a></div>', 
			 '<div class="postie-image-div"><a data-fslightbox="1" data-type="image" href="https://www.berg-reise-foto.de/smrtzl/uploads/2018/12/PSX_20181225_174247-150x150.jpg"><img class="postie-image" style="border: none;" title="PSX_20181225_174247.jpg" src="https://www.berg-reise-foto.de/smrtzl/uploads/2018/12/PSX_20181225_174247-150x150.jpg" alt="PSX_20181225_174247.jpg"></a></div>'],
			
			['<div class="postie-image-div"><a href="https://www.berg-reise-foto.de/"><img class="postie-image" style="border: none;" title="none" src="https://www.berg-reise-foto.de/" alt="none"></a></div>', 
			 '<div class="postie-image-div"><a data-fslightbox="1" data-type="image" href="https://www.berg-reise-foto.de/"><img class="postie-image" style="border: none;" title="none" src="https://www.berg-reise-foto.de/" alt="none"></a></div>'],
			
			['<figure class="wp-block-video"><video controls src="http://localhost/wordpress/wp-content/uploads/2022/04/sample-mp4-file.mp4"></video></figure>',
			 '<figure class="wp-block-video"><a data-fslightbox="1" data-type="video" href="http://localhost/wordpress/wp-content/uploads/2022/04/sample-mp4-file.mp4"><video controls src="http://localhost/wordpress/wp-content/uploads/2022/04/sample-mp4-file.mp4"></video></a></figure>'],	
			
             ['<figure class="wp-block-video"><video controls src="http://localhost/wordpress/wp-content/uploads/2022/04/sample-mp4-file.mp4" poster="test.jpg"></video></figure>',
			 '<figure class="wp-block-video"><a data-fslightbox="1" data-type="video" data-thumb="test.jpg" href="http://localhost/wordpress/wp-content/uploads/2022/04/sample-mp4-file.mp4"><video controls src="http://localhost/wordpress/wp-content/uploads/2022/04/sample-mp4-file.mp4" poster="test.jpg"></video></a></figure>'],	
			  

			['<figure class="wp-block-image size-large is-style-default"><a href="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg" alt="" class="wp-image-5035" srcset="http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg 1024w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-300x189.jpg 300w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-768x485.jpg 768w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1536x969.jpg 1536w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-2048x1292.jpg 2048w" sizes="(max-width: 1024px) 100vw, 1024px"></a></figure>',
			 '<figure class="wp-block-image size-large is-style-default"><a data-fslightbox="1" data-type="image" href="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg" alt="" class="wp-image-5035" srcset="http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg 1024w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-300x189.jpg 300w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-768x485.jpg 768w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1536x969.jpg 1536w, http://localhost/wordpress/wp-content/uploads/2020/12/GanzneuesBild-2048x1292.jpg 2048w" sizes="(max-width: 1024px) 100vw, 1024px"></a></figure>'], 

			['<figure class="wp-block-image size-large is-style-default"><a href="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg" alt="" class="wp-image-5035"></a><figcaption>Italien</figcaption></figure>',
			 '<figure class="wp-block-image size-large is-style-default"><a data-fslightbox="1" data-type="image" data-caption="Italien" href="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg" alt="" class="wp-image-5035"></a><figcaption>Italien</figcaption></figure>'], 
			
			['<span class="postie-image-div"><a href="https://www.berg-reise-foto.de/smrtzl/uploads/2018/12/PSX_20181225_174247.jpg"><img class="postie-image" style="border: none;" title="PSX_20181225_174247.jpg" src="https://www.berg-reise-foto.de/smrtzl/uploads/2018/12/PSX_20181225_174247-150x150.jpg" alt="PSX_20181225_174247.jpg"></a></span>', 
			 '<span class="postie-image-div"><a href="https://www.berg-reise-foto.de/smrtzl/uploads/2018/12/PSX_20181225_174247.jpg"><img class="postie-image" style="border: none;" title="PSX_20181225_174247.jpg" src="https://www.berg-reise-foto.de/smrtzl/uploads/2018/12/PSX_20181225_174247-150x150.jpg" alt="PSX_20181225_174247.jpg"></a></span>'],
			
			['<figure class="wp-block-image size-large is-style-default"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg" alt="" class="wp-image-5035"></a><figcaption>Italien</figcaption></figure>',
			  '<figure class="wp-block-image size-large is-style-default"><a data-fslightbox="1" data-type="image" data-caption="Italien" href="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg" alt="" class="wp-image-5035"></a><figcaption>Italien</figcaption></figure>'], 
			
            ['<div class="wp-block-image size-large is-style-default"><figure class="aligncenter"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg" alt="" class="wp-image-5035"></a><figcaption>Italien</figcaption></figure></div>',
			 '<div class="wp-block-image size-large is-style-default"><figure class="aligncenter"><a data-fslightbox="1" data-type="image" data-caption="Italien" href="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg" alt="" class="wp-image-5035"></a><figcaption>Italien</figcaption></figure></div>'], 
			
            ['<div class="postie-image"><figure class="aligncenter"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg" alt="" class="wp-image-5035"></a><figcaption>Italien</figcaption></figure></div>',
			 '<div class="postie-image"><figure class="aligncenter"><a data-fslightbox="1" data-type="image" data-caption="Italien" href="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg" alt="" class="wp-image-5035"></a><figcaption>Italien</figcaption></figure></div>'], 
			 
            ['<figure class="wp-block-image"><a href="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"></a><figcaption>Hier gehts nach <a href="link-irgenwohin">Italien</a></figcaption></figure>',
			 '<figure class="wp-block-image"><a data-fslightbox="1" data-type="image" data-caption="Hier gehts nach Italien" href="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"></a><figcaption>Hier gehts nach <a href="link-irgenwohin">Italien</a></figcaption></figure>'], 

             ['<figure class="wp-block-image"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"></a><figcaption>Hier gehts nach <a href="link-irgenwohin">Italien</a></figcaption></figure>',
			 '<figure class="wp-block-image"><a data-fslightbox="1" data-type="image" data-caption="Hier gehts nach Italien" href="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"><img loading="lazy" width="1024" height="646" src="http://127.0.0.1/wordpress/wp-content/uploads/2020/12/GanzneuesBild-1024x646.jpg"></a><figcaption>Hier gehts nach <a href="link-irgenwohin">Italien</a></figcaption></figure>'], 
 

			];
	}

	/**
     * @dataProvider htmlProviderNoImage
     */
	public function test_rewriteTagFunctionNoImageTag ($htmlin, $expected) {

		expect( 'get_site_url' )
			->once()
			->andReturn('http://localhost/wordpress');

		expect( 'get_post_type' )
			->once()
			->andReturn('post');	

		expect('wp_remote_head')
			->andReturn('header');
		
		expect('wp_remote_retrieve_header')
			->andReturn('text/json');

		expect('wp_enqueue_script')
			->andReturn( true);

        expect( 'get_the_ID' )
			->once()
			->andReturn(-1);

		$tested = new mvbplugins\fslightbox\RewriteFigureTags();
      
		$result = $tested->lightbox_gallery_for_gutenberg($htmlin);
		$result = str_replace(array("\r", "\n", "<!DOCTYPE html><html>", '</html>', '<body>', '</body>'), '', $result);
		$this->assertEquals( $result, $expected);
    }

	public function htmlProviderNoImage(): array 
	{
		return [
			['', ''],
			['<div class="postie-image-div"><a href="https://www.berg-reise-foto.de/smrtzl/uploads/2018/12/PSX_20181225_174247.jpg"><img class="postie-image" style="border: none;" title="PSX_20181225_174247.jpg" src="https://www.berg-reise-foto.de/smrtzl/uploads/2018/12/PSX_20181225_174247-150x150.jpg" alt="PSX_20181225_174247.jpg"></a></div>', 
			 '<div class="postie-image-div"><a href="https://www.berg-reise-foto.de/smrtzl/uploads/2018/12/PSX_20181225_174247.jpg"><img class="postie-image" style="border: none;" title="PSX_20181225_174247.jpg" src="https://www.berg-reise-foto.de/smrtzl/uploads/2018/12/PSX_20181225_174247-150x150.jpg" alt="PSX_20181225_174247.jpg"></a></div>'],
		
			];
	}

	/**
     * @dataProvider htmlProviderNoPost
     */
	public function test_rewriteTagFunctionNoPost ($htmlin, $expected) {

		expect( 'get_site_url' )
			->once()
			->andReturn('http://localhost/wordpress');

		expect( 'get_post_type' )
			->once()
			->andReturn('attachment');	

		expect('wp_remote_head')
			->andReturn('header');
		
		expect('wp_remote_retrieve_header')
			->andReturn('text/json');

		expect('wp_enqueue_script')
			->andReturn( true);

        expect( 'get_the_ID' )
			->once()
			->andReturn(-1);

		$tested = new mvbplugins\fslightbox\RewriteFigureTags();
      
		$result = $tested->lightbox_gallery_for_gutenberg($htmlin);
		$result = str_replace(array("\r", "\n", "<!DOCTYPE html><html>", '</html>', '<body>', '</body>'), '', $result);
		$this->assertEquals( $result, $expected);
    }

	public function htmlProviderNoPost(): array 
	{
		return [
			['', ''],
			['<div class="postie-image-div"><a href="https://www.berg-reise-foto.de/smrtzl/uploads/2018/12/PSX_20181225_174247.jpg"><img class="postie-image" style="border: none;" title="PSX_20181225_174247.jpg" src="https://www.berg-reise-foto.de/smrtzl/uploads/2018/12/PSX_20181225_174247-150x150.jpg" alt="PSX_20181225_174247.jpg"></a></div>', 
			 '<div class="postie-image-div"><a href="https://www.berg-reise-foto.de/smrtzl/uploads/2018/12/PSX_20181225_174247.jpg"><img class="postie-image" style="border: none;" title="PSX_20181225_174247.jpg" src="https://www.berg-reise-foto.de/smrtzl/uploads/2018/12/PSX_20181225_174247-150x150.jpg" alt="PSX_20181225_174247.jpg"></a></div>'],
		
			];
	}

    	/**
     * @dataProvider htmlProviderExludeID
     */
	public function test_rewriteTagFunctionExcludeID ($htmlin, $expected) {

		expect( 'get_site_url' )
			->once()
			->andReturn('http://localhost/wordpress');

		expect( 'get_post_type' )
			->once()
			->andReturn('attachment');	

		expect('wp_remote_head')
			->andReturn('header');
		
		expect('wp_remote_retrieve_header')
			->andReturn('text/json');

		expect('wp_enqueue_script')
			->andReturn( true);

        expect( 'get_the_ID' )
			->once()
			->andReturn(0);

		$tested = new mvbplugins\fslightbox\RewriteFigureTags();
      
		$result = $tested->lightbox_gallery_for_gutenberg($htmlin);
		$result = str_replace(array("\r", "\n", "<!DOCTYPE html><html>", '</html>', '<body>', '</body>'), '', $result);
		$this->assertEquals( $result, $expected);
    }

	public function htmlProviderExludeID(): array 
	{
		return [
			['', ''],
			['<div class="postie-image-div"><a href="https://www.berg-reise-foto.de/smrtzl/uploads/2018/12/PSX_20181225_174247.jpg"><img class="postie-image" style="border: none;" title="PSX_20181225_174247.jpg" src="https://www.berg-reise-foto.de/smrtzl/uploads/2018/12/PSX_20181225_174247-150x150.jpg" alt="PSX_20181225_174247.jpg"></a></div>', 
			 '<div class="postie-image-div"><a href="https://www.berg-reise-foto.de/smrtzl/uploads/2018/12/PSX_20181225_174247.jpg"><img class="postie-image" style="border: none;" title="PSX_20181225_174247.jpg" src="https://www.berg-reise-foto.de/smrtzl/uploads/2018/12/PSX_20181225_174247-150x150.jpg" alt="PSX_20181225_174247.jpg"></a></div>'],
		
			];
	}
}
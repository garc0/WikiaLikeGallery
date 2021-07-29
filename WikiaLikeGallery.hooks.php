<?php
use \ImageGalleryBase;
use \TraditionalImageGallery;
use \Html;
use \Xml;

use MediaWiki\Linker\LinkRenderer;
use MediaWiki\MediaWikiServices;

class WikiaLikeGalleryTag extends TraditionalImageGallery{

	const SLIDER_MIN_IMG_WIDTH = 670;
	const SLIDER_MIN_IMG_HEIGHT = 360;
	const SLIDER_MOSAIC_MIN_IMG_WIDTH = 320;
	const SLIDER_MOSAIC_MIN_IMG_HEIGHT = 210;

	protected $mParams;
	
	protected function setParam( $name, $data){
		$this->mParams[$name] = $data;
	}

	protected function getParam( $name, $data){
		if(isset($this->mParams[$name]))
			return $this->mParams[$name];
		return null;
	}

	static public function sanitizeCssColor($color) {
		$color = preg_replace('@[^a-z0-9#]@i', '', $color);
		return $color;
	}

	public function setAdditionalOptions( $options ) {
		if ( isset( $options['id'] ) )
			$this->mParams['id'] = $options['id'];

		if ( isset( $options['position'] ) && in_array($options['position'], ['left', 'center', 'right'] ) ){
			$this->mParams['position'] = $options['position'];
		} else {
			$this->mParams['position'] = $options['center'];
		}

		//if ( !isset( $options['widths'] ) ) {
		//	$this->mWidths = SLIDER_MIN_IMG_WIDTH;
		//}

		if ( isset( $options['bordercolor'] ) ){
			$this->mParams['bordercolor'] = sanitizeCssColor($options['bordercolor']);
		}

		if ( isset( $options['captiontextcolor'] ) ){
			$this->mParams['captiontextcolor'] = sanitizeCssColor($options['captiontextcolor']);
		}

		if ( isset( $options['navigation'] ) ){
			$useNavigation = !empty( $options['navigation'] ) && strtolower( $options['navigation'] ) == 'true';
		} else{
			$useNavigation = true;
		}

		$this->setParam( 'navigation' , $useNavigation );

		if ( isset( $options['orientation'] ) &&
			 in_array( strtolower($options['orientation']), ['bottom', 'top', 'left','right', 'mosaic' ] ) ) {
			$this->setParam( 'orientation', strtolower($options['orientation']) );
		} else {
			$this->setParam( 'orientation', 'bottom' );
		}
	}

	public function getThumb(){
	}

	public function toHTML(){
		$services = MediaWikiServices::getInstance();
		$repoGroup = $services->getRepoGroup();
		$badFileLookup = $services->getBadFileLookup();

					
		if(intval($this->mWidths) < self::SLIDER_MIN_IMG_WIDTH)
		$this->mWidths = "" . self::SLIDER_MIN_IMG_WIDTH;
		
		if(intval($this->mHeights) < self::SLIDER_MIN_IMG_HEIGHT)
			$this->mHeights = "" . self::SLIDER_MIN_IMG_HEIGHT;

		$imageGallery  = Xml::openElement('div', ['class' => 'slider__list', 'id' => "3000|$this->mHeights"]);
        $navBoxThumbs  = Xml::openElement('div', ['class' => 'slider__nav__thumbs']);
		$navBoxCaption = Xml::openElement('div', ['class' => 'slider__nav__captions']);

		$lang = $this->getRenderLang();

		foreach ( $this->mImages as [ $nt, $text, $alt, $link, $handlerOpts, $loading ] ) {
			$descQuery = false;
			if ( $nt->getNamespace() === NS_FILE ) {
				# Get the file...
				if ( $resolveFilesViaParser ) {
					# Give extensions a chance to select the file revision for us
					$options = [];
					Hooks::runner()->onBeforeParserFetchFileAndTitle(
						$this->mParser, $nt, $options, $descQuery );
					# Fetch and register the file (file title may be different via hooks)
					list( $img, $nt ) = $this->mParser->fetchFileAndTitle( $nt, $options );
				} else {
					$img = $repoGroup->findFile( $nt );
				}
			} else {
				$img = false;
			}

			$params = [
				'width' => $this->mWidths,
				'height' => $this->mHeights
			];;
			$transformOptions = $params + $handlerOpts;

			$thumb = false;

			if ( $loading === ImageGalleryBase::LOADING_LAZY ) {
				$imageParameters['loading'] = 'lazy';
			}

			if ( !$img ) {
				# We're dealing with a non-image, spit out the name and be done with it.
				$thumbhtml = '<div class="thumb" style="height: '
					. ( $this->getThumbPadding() + $this->mHeights ) . 'px;">'
					. htmlspecialchars( $nt->getText() ) . '</div>';

				if ( $resolveFilesViaParser ) {
					$this->mParser->addTrackingCategory( 'broken-file-category' );
				}
			} elseif ( $this->mHideBadImages &&
				$badFileLookup->isBadFile( $nt->getDBkey(), $this->getContextTitle() )
			) {
				# The image is blacklisted, just show it as a text link.
				$thumbhtml = '<div class="thumb" style="height: ' .
					( $this->getThumbPadding() + $this->mHeights ) . 'px;">' .
					$linkRenderer->makeKnownLink( $nt, $nt->getText() ) .
					'</div>';
			} else {
				{
					$thumb = $img->transform( $transformOptions );


					$imageParameters = [
						'alt' => $alt,
						'custom-url-link' => "",
					];

					if ( $alt == '' && $text == '' ) {
						$imageParameters['alt'] = $nt->getText();
					}

					$this->adjustImageParameters( $thumb, $imageParameters );

					Linker::processResponsiveImages( $img, $thumb, $transformOptions );
					
					$imageGallery .= Xml::openElement('div', ['class' => 'slider__gallerybox slider__gallerybox__img', 'style' => "height:$this->mHeights;"]) .
								$thumb->toHtml( $imageParameters ) .
								Xml::closeElement('div');
					
				}
				$params = [
					'width' => 64,
					'height' => 50
				];
				$transformOptions = $params + $handlerOpts;

				$thumb = $img->transform( $params );

				$imageParameters = [
					'alt' => $alt,
					'custom-url-link' => "",
				];

				if ( $alt == '' && $text == '' ) {
					$imageParameters['alt'] = $nt->getText();
				}

				$this->adjustImageParameters( $thumb, $imageParameters );

				Linker::processResponsiveImages( $img, $thumb, $params );

				$navBoxThumbs .= Xml::openElement('div', array('class' => 'slider__nav__thumb')) .
							$thumb->toHtml( $imageParameters ) .
							Xml::closeElement('div');

				$navBoxCaption .= Xml::openElement('div', array('class' => 'slider__nav__caption')) .
							$text .
							Xml::closeElement('div');				
			}
			
		}

		$imageGallery  .= Xml::closeElement('div');
		$navBoxCaption .= Xml::closeElement('div');
		$navBoxThumbs  .= Xml::closeElement('div');

		$navBox = "";

		if($this->mParams['navigation'] != false){
			$place = 'nmBottom';
			switch($this->mParams['orientation']){
				case 'bottom':
					$place = 'orientation-bottom';
					break;
				case 'top':
					$place = 'orientation-top';
					break;
				case 'left':
					$place = 'orientation-left';
					break;
				case 'right':
					$place = 'orientation-right';
					break;
			}
			$navBox = Xml::openElement('div', ['class' => 'slider__nav ' . $place]) . 
					$navBoxCaption . $navBoxThumbs .
					Xml::closeElement('div');
		}

        $navControls = "";
        $navControls .= "<div class=\"slider__controls__prev\"></div> ";
        $navControls .= "<div class=\"slider__controls__next\"></div> ";

		$Html =  $imageGallery .$navControls . $navBox;

		$id = (isset($mParams['id']) ? $mParams['id'] : 'slider_' . rand());

		// set default if not set
		$refresh = (isset($mParams['refresh']) ? $mParams['refresh'] : '1000');
		$transitiontime = (isset($mParams['transitiontime']) ? $mParams['transitiontime'] : '400');
		$center = (isset($mParams['center']) ? $mParams['center'] : 'false');

		$output = '';
		$styleAttrs = ($center == 'true' ? "style='margin-left:auto;margin-right:auto'" : "");
		$output .= Xml::openElement('div', ['id' => $id, 'class' => 'slider', 'style' => "width:$this->mWidths;height:$this->mHeights"] ) . $Html . Xml::closeElement('div');
		return $output;
	}

	
}

class WikiaLikeGalleryHooks{
	public static function onGalleryGetModes( array &$modeArray ) {
		$modeArray['slider'] = WikiaLikeGalleryTag::class;
	}

	public static function extensionHook() {
		global $wgOut;
		$wgOut->addModules( 'ext.slider.icons' );
		$wgOut->addModules('ext.slider.main');
		return true;
	}
}
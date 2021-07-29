var NAV_ORIENT_BOTTOM = 0;
var NAV_ORIENT_TOP = 1;
var NAV_ORIENT_LEFT = 2;
var NAV_ORIENT_RIGHT = 3;
( function(){
    var slider = function(parentId){
        this._parent =  $(parentId);
        this._gallery = this._parent.find( '.slider__list' );
        this._nav_bar = this._parent.find( '.slider__nav' );
        this._navThumbs = this._nav_bar.find( '.slider__nav__thumbs' );
        this._nav_thumb = this._navThumbs.find( '.slider__nav__thumb' );
        this._navCaptions = this._nav_bar.find( '.slider__nav__captions' );
        this._navCaption = this._navCaptions.find( ' .slider__nav__caption' );

        this._slides = this._parent.find( '.slider__gallerybox' );

        this._nav_orientation = NAV_ORIENT_BOTTOM;
        if(this._nav_bar != undefined && this._nav_bar != null){
            if(this._nav_bar.hasClass('orientation-bottom'))
                this._nav_orientation = NAV_ORIENT_BOTTOM;
            else if(this._nav_bar.hasClass('orientation-top'))
                this._nav_orientation = NAV_ORIENT_TOP;
            else if(this._nav_bar.hasClass('orientation-right'))
                this._nav_orientation = NAV_ORIENT_RIGHT;
            else if(this._nav_bar.hasClass('orientation-left'))
                this._nav_orientation = NAV_ORIENT_LEFT;
        }

        console.log(this._nav_orientation);

        this._pause = false;
        this._current_slide = 0;
        this._max_slide = 0;
        
        this._HeightSize = 'auto';
        var Data = (this._gallery.attr('id') || '').split('|');
    
        var Interval = Data[0];
        this._HeightSize = Data[1];
        
        if (Interval === 0 || Interval === undefined) Interval = 3000;

        this._max_slide = this._gallery.children().length;
        this._gallery.css('width', 100 * this._max_slide + '%');
        this._slides.css('width', 100 / this._max_slide + '%');

        setInterval(function tick() {
            if (this._pause === false) this.NextSlide();
        }.bind(this), Interval);

        this.InitEvents();
        this.UpdateThumbs();
        this.UpdateNavigation();
    }

    OO.initClass(slider);

    slider.prototype.InitEvents = function (){
        var _controls_prev = this._parent.find( '.slider__controls__prev' );
        var _controls_next = this._parent.find( '.slider__controls__next' );
        var _nav_bar = this._nav_bar;


        this._parent.mouseenter(function () {
            this._pause = true;
            _nav_bar.css('transform', 'translate(0, 0)');

            if(this._nav_orientation == NAV_ORIENT_LEFT)
                _controls_prev.css('left', 6 + this._nav_bar.width() + 'px');
            else _controls_prev.css('left', '6px');

            if(this._nav_orientation == NAV_ORIENT_RIGHT)
                _controls_next.css('right', 6 + this._nav_bar.width() + 'px');
            else _controls_next.css('right', '6px');

            _controls_prev.css('opacity', 1);
            _controls_next.css('opacity', 1);
        }.bind(this));

        this._parent.mouseleave(function () {
            this._pause = false;

            if(this._nav_orientation == NAV_ORIENT_BOTTOM)
                _nav_bar.css('transform', 'translate(0px, ' + _nav_bar.height() + 'px)');
            else if(this._nav_orientation == NAV_ORIENT_TOP)
                _nav_bar.css('transform', 'translate(0px, ' + - _nav_bar.height() + 'px)');
            else if(this._nav_orientation == NAV_ORIENT_LEFT)
                _nav_bar.css('transform', 'translate('+ - _nav_bar.width() + 'px, ' +  '0px)');
            else if(this._nav_orientation == NAV_ORIENT_RIGHT)
                _nav_bar.css('transform', 'translate('+ _nav_bar.width() + 'px, ' +  '0px)');
            
            _controls_prev.css('left', - _controls_prev.width() + 'px');
            _controls_prev.css('opacity', 0);
            _controls_next.css('right', - _controls_next.width() + 'px');
            _controls_next.css('opacity', 0);
        }.bind(this));

        this._nav_thumb.mouseenter(function () {
            $(this).css('opacity', 1);
        });

        this._nav_thumb.mouseleave(function () {
            $(this).css('opacity', 0.8);
        });

        var ta = this;

        this._nav_thumb.click(function () {
            var navBtnId = $(this).index();
            if (navBtnId != ta._current_slide) {
                ta._current_slide = navBtnId;
                ta.SelectSlide();
            }
            ta.UpdateNavigation();
        });

        _controls_prev.click(function () {
            this.PrevSlide();
        }.bind(this));
        _controls_next.click(function () {
            this.NextSlide();
        }.bind(this));

        $( window ).on(
			'resize',
			this.UpdateThumbs()
		);
    }

    slider.prototype.UpdateThumbs = function(){
        if(this._nav_orientation == NAV_ORIENT_BOTTOM || this._nav_orientation == NAV_ORIENT_TOP){
            var nav_width = 0;
            if (this._HeightSize != 'auto') {
                nav_width = this._parent.outerHeight(true);
                this._slides.each(function () {
                    var HSlide = $(this).find('img').outerHeight(false);
                    var RMath = (nav_width - HSlide) / 2;
                    $(this).find('img').css('transform', 'translateY(' + RMath + 'px)');
                });
            }
            nav_width = this._nav_bar.outerWidth(true);
            var thumb_width = this._navThumbs.find( 'div' ).outerWidth(true);
            
            this._navThumbs.css('transform', 'translateX(' + (nav_width - thumb_width * this._nav_thumb.length - 10) + 'px)');
        }else if(this._nav_orientation == NAV_ORIENT_LEFT || this._nav_orientation == NAV_ORIENT_RIGHT){
            var nav_height = this._nav_bar.outerHeight(true);
            var thumb_height = this._navThumbs.find( 'div' ).outerHeight(true);
            
            this._navThumbs.css('transform', 'translateY(' + (nav_height - thumb_height * this._nav_thumb.length - 10) + 'px)');
        }
        this.SelectSlide();
    }

    slider.prototype.SelectSlide = function (Id){
        if(Id === undefined) Id = this._current_slide;
        var _w = - this._parent.width() * Id;
        this._gallery.css('margin', '0px 0px 0px ' + _w + 'px');
    }

    slider.prototype.PrevSlide = function () {
        this._current_slide <= 0 ? (this._current_slide = this._max_slide - 1) : this._current_slide--;
        
        this.SelectSlide();
        this.UpdateNavigation();
    }

    slider.prototype.NextSlide = function () {
        if (this._current_slide + 1 >= this._max_slide || this._current_slide < 0) {
            this._current_slide = 0;
        } else  this._current_slide++;
        
        this.SelectSlide();
        this.UpdateNavigation();
    }

    slider.prototype.UpdateNavigation = function (Id){
        if(Id === undefined) Id = this._current_slide;
        var _activeCaption = this._navCaptions.children().eq(Id);
        var _activeThumb = this._navThumbs.children().eq(Id);

        $(window).trigger( 'scroll' );
        this._nav_thumb.removeClass( 'active__thumb' );
        this._navCaption.css({'opacity' : '0', 'display' : 'none'});

        _activeThumb.addClass( 'active__thumb' );
        _activeCaption.css({'opacity' : '1', 'display' : 'block'});
    }

    mw.hook( 'wikipage.content' ).add( function ( $content ) {
		$content.find( '.slider' ).each( function () {
			new slider( this );
		} );
	} );
}() );
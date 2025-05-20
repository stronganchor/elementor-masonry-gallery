(function($){

    // hard-coded gutter
    const GUTTER = 10;

    function initMasonry($scope) {
        const $wrap = $scope.hasClass('emg-masonry')
            ? $scope
            : $scope.find('.emg-masonry');
        if (!$wrap.length) {
            return;
        }

        const cols = parseInt($wrap.attr('data-columns'), 10) || 4;
        let $grid = $wrap.find('.gallery');
        if (!$grid.length) {
            $grid = $wrap;
        }

        $grid.imagesLoaded(function(){
            const totalW = $grid.width();
            const colW = Math.floor((totalW - (cols - 1) * GUTTER) / cols);

            $grid.masonry({
                itemSelector: '.gallery-item',
                columnWidth: colW,
                gutter: GUTTER,
                horizontalOrder: true
            });
        });
    }

    $(window).on('elementor/frontend/init', function(){
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/image-gallery.default',
            initMasonry
        );
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/global',
            initMasonry
        );
    });

})(jQuery);

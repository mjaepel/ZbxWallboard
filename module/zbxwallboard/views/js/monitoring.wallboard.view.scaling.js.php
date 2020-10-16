<?php

?>

<script type="text/javascript">
(function($) {
    $.fn.scaledgrid = function(getContainerSize) {
        var $this = this;
        var $firstTile = this.eq(0);
        var horizontalMargin = parseInt($firstTile.css('margin-left')) + parseInt($firstTile.css('margin-right'));
        var verticalMargin = parseInt($firstTile.css('margin-top')) + parseInt($firstTile.css('margin-bottom'));
        var initialWidth = parseInt($firstTile.css('width')) + horizontalMargin;
        var initialHeight = parseInt($firstTile.css('height')) + verticalMargin;
        var initialFontSize = parseFloat($firstTile.css('font-size'));
        var initialTextAccentSize = parseFloat($firstTile.find('.zbxwallboard-text-big:eq(0)').css('font-size'));
        var initialTextDefaultSize = parseFloat($firstTile.find('.zbxwallboard-text-normal:eq(0)').css('font-size'));
        var aspectRatio = initialWidth / initialHeight;

        function adaptTileSizes() {
            var containerSize = getContainerSize();
            var tileWidth = initialWidth;
            var tileHeight = initialHeight;
            var columnCount = tileWidth ? Math.floor(containerSize[0] / tileWidth) : 0;
            var rowCount = columnCount ? Math.ceil($this.length / columnCount) : 0;
            var scaleFactor;

            while (tileWidth + horizontalMargin < containerSize[0] && (columnCount * tileWidth < containerSize[0] || rowCount * tileHeight < containerSize[1])) {
                tileWidth++;

                while (tileHeight + verticalMargin < containerSize[1] && tileWidth / tileHeight > aspectRatio) {
                    tileHeight++;
                }

                columnCount = tileWidth ? Math.floor(containerSize[0] / tileWidth) : 1;
                rowCount = columnCount ? Math.ceil($this.length / columnCount) : 1;
            }

            while (tileWidth - horizontalMargin > 0 && (columnCount * tileWidth > containerSize[0] || rowCount * tileHeight > containerSize[1])) {
                tileWidth--;

                while (tileHeight - verticalMargin > 0 && tileWidth / tileHeight < aspectRatio) {
                    tileHeight--;
                }

                columnCount = tileWidth ? Math.floor(containerSize[0] / tileWidth) : 0;
                rowCount = columnCount ? Math.ceil($this.length / columnCount) : 0;
            }

            scaleFactor = tileHeight / initialHeight;

            $this.css('width', tileWidth - horizontalMargin);
            $this.css('height', tileHeight - verticalMargin);
            $this.css('font-size', initialFontSize * scaleFactor);
            $this.find('.zbxwallboard-text-big').css('font-size', initialTextAccentSize * scaleFactor);
            $this.find('.zbxwallboard-text-normal').css('font-size', initialTextDefaultSize * scaleFactor);
        }

        $(window).on('resize', adaptTileSizes);

        adaptTileSizes();

        return this;
    }
})(jQuery);

$(document).ready(function() {
    var $window = $(window);

	if ($('.sidebar').length > 0) {
		$('.zbxwallboard-tile').scaledgrid(function() {
			return [$window.width() - $('.sidebar').width() - 40, $window.height() - $('header').height() - $('.ui-tabs').height() - $('footer').height() - 100];
		});
	} 
	else {
		$('.zbxwallboard-tile').scaledgrid(function() {
			return [$window.width() - 40, $window.height() - 20];
		});
	};
});
</script>

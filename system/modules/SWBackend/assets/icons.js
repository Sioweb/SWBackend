(function($){
var openLayerTimeout = false,
	glossarTimeout = false,
	LayerAjaxRequest = false,
	layer = false;

$(function(){

	layer = $('body')
				.append('<div class="glossar_layer">')
				.find('.glossar_layer');

	$('.glossar').each(function(key, elem)
	{
		$(elem).mouseenter(function()
		{
			var glossar = $(this);

			removeLayer();

			layer.append('<div class="layer_loading"><div class="layer_ring"></div><div class="layer_content"><span></span></div></div>');
			
			layer
				.addClass('layer_load')
				.css({
					top: (glossar.offset().top - 20),
					left: (glossar.offset().left - Math.round(layer.width() * 1.2)),
					'max-width': glossar.data('maxwidth'),
					'max-height': glossar.data('maxheight')
				});

			openLayerTimeout = setTimeout(function(){loadLayer(glossar);},1000);
		})
		.mouseout(function()
		{
			glossarTimeout = setTimeout(removeLayer,200);
		});
	});
});


function loadLayer(glossar)
{
	var left = false,
		top = false,
		maxWidth = glossar.data('maxwidth'),
		maxHeight = glossar.data('maxheight');

	left = ((glossar.offset().left + maxWidth) < $(window).width() ? true : false);

	/*
	.append('<div class="ce_glossar_close">X</div>');
				
	$('.ce_glossar_close')
		.click(function(){
			$(this).parent().remove();
		});
	/**/

	if(LayerAjaxRequest)
		LayerAjaxRequest.abort();

	LayerAjaxRequest = $.ajax(
	{
		type: "POST",
		url:  "ajax.php",
		data: { isAjaxRequest: 1, glossar: 1, id: glossar.data('glossar'), REQUEST_TOKEN: Contao.request_token},
		success: function(result)
		{
			layer.addClass('layer_loaded').append($($.parseJSON(result).content));
			layer.append('<div class="ce_glossar_close">X</div>').children('.ce_glossar_close')
				.click(function(){
					removeLayer();
				});
			
			if(!left)
				layer.css({left: 'auto','right': 20});
			if(layer.offset().top + layer.height() > $(window).height() + $(window).scrollTop())
				layer.css({top: 'auto', bottom: 20, position: 'fixed' });

			$('.ce_glossar_layer').mouseenter(function(){
				clearTimeout(glossarTimeout);
			}).mouseleave(function(){
				glossarTimeout = setTimeout(removeLayer, 750);
			});
		}
	});
/**/
}

function removeLayer()
{
	clearTimeout(glossarTimeout);
	clearTimeout(openLayerTimeout);
	$('.layer_loading,.ce_glossar_close').remove();
	layer.css({position: 'absolute'}).removeClass('layer_loaded layer_load').children('.ce_glossar_layer').remove();
}


})(jQuery);

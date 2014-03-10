$s(document).ready(function(){
	/**/
	$s('.dragNdrop').uploadAjax({
		url: window.location.href,
		data: {
			action: 'dragNdrop',
			isAjaxRequest: 1,
			dragNdrop: 1,
			REQUEST_TOKEN: ''
		},
		dragover: function(e){
			e.stopPropagation();
			e.preventDefault();

			$s(this).addClass('sw_drag_over');
		},
		dragleave: function(e){
			$s(this).removeClass('sw_drag_over');
		}
	});
	/**/
});
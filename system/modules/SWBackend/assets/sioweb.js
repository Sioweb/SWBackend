$s(document).ready(function(){
	/**/
	$s('.dragNdrop').uploadAjax({
		url: window.location.href,
		success: function(msg,settings){
			var Images = new Array(),
				found = false,
				items = $$(settings.items).getElements('li')[0];
			msg = JSON.decode(msg);

			for(var i=0;i<msg.length;i++)
			{
				items.each(function(value, index){
					if(value.get('data-id') == msg[i].uuid)
						found = true;
				});

				if(!found)
				{
					Images[i] = msg[i];
					$$(settings.items).getElements('#load-'+i)[0].set('data-id',msg[i].uuid);
				}
				else
					$$(settings.items).getElements('#load-'+i)[0].destroy();
			}


			for(i=0;i<Images.length;i++)
			{
				var elem = $$(settings.items).getElements('.loading')[0][i];
				elem.getElement('img').destroy();
				elem.adopt(msg[i].img.toElement());

				var re = new RegExp(msg[i].uuid, "g");
				if(document.getElementById('ctrl_multiSRC').value.search(re) == -1)
				{
					document.getElementById('ctrl_multiSRC').value += ',' + msg[i].uuid;
					document.getElementById('ctrl_orderSRC').value += ',' + msg[i].uuid;
				}
			}
			Backend.makeMultiSrcSortable('sort_multiSRC', 'ctrl_orderSRC');
		},
		beforeSend: function(item,files){
			for(var i = 0; i < files.length; i++)
			{
				var newItem = new Element('li.loading#load-'+i,{html:'<img src="system/modules/SWBackend/assets/loading.gif">'});
				var deleteItem = new Element('span',{
					'class': 'sortable_delete',
					'events': {
						click: function(){
							Backend.removeMultiSrcThumbnails(this,'ctrl_multiSRC','ctrl_orderSRC');
						}
					}
				});

				newItem.adopt(deleteItem);
				$$(item).getElements('.sortable')[0].adopt(newItem);
			}
		},
		data: {
			isAjaxRequest: 1,
			FORM_SUBMIT: 'tl_upload',
			MAX_FILE_SIZE: 2048000
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
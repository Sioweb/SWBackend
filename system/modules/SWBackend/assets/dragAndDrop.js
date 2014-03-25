$s(document).ready(function(){
	/**/
	$s('.dragNdrop').uploadAjax({
		success: function(msg,settings){
			msg = JSON.decode(msg);
			var Images = new Array(),
				found = false,
				msgFiles = msg.images;
				items = $$(settings.items).getElements('li')[0];
			
			if(msg.fieldName == 'multiSRC')
			{
				for(var i=0;i<msgFiles.length;i++)
				{
					items.each(function(value, index){
						if(value.get('data-id') == msgFiles[i].uuid)
							found = true;
					});

					if(!found)
					{
						Images[i] = msgFiles[i];
						$$(settings.items).getElements('#load-'+i)[0].set('data-id',msgFiles[i].uuid);
					}
					else
						$$(settings.items).getElements('#load-'+i)[0].destroy();
				}


				for(i=0;i<Images.length;i++)
				{
					var elem = $$(settings.items).getElements('.loading')[0][i];
					elem.getElement('img').destroy();
					elem.adopt(msgFiles[i].img.toElement());

					var re = new RegExp(msgFiles[i].uuid, "g");
					if(document.getElementById('ctrl_'+msg.fieldName).value.search(re) == -1)
					{
						document.getElementById('ctrl_'+msg.fieldName).value += ',' + msgFiles[i].uuid;
						document.getElementById('ctrl_orderSRC').value += ',' + msgFiles[i].uuid;
					}
				}
				
				Backend.makeMultiSrcSortable('sort_'+msg.fieldName, 'ctrl_orderSRC');
			}
			else
			{
				var elem = $('sort_'+msg.fieldName).getElements('li')[0],
					re = new RegExp(msgFiles[0].uuid, "g");

				if(typeof elem !== 'undefined')
					elem.getElement('img').destroy();
				else
				{
					elem = $('sort_'+msg.fieldName).adopt(new Element('<li>',{'data-id':msgFiles[0].uuid})).getElement('li');
				}
				elem.adopt(msgFiles[0].img.toElement());

				if(document.getElementById('ctrl_'+msg.fieldName).value.search(re) == -1)
					document.getElementById('ctrl_'+msg.fieldName).value = msgFiles[0].uuid;
			}
		},
		beforeSend: function(item,files){
			var fieldName = item[0].getElementsByTagName('input')[0].name;
			for(var i = 0; i < files.length; i++)
			{
				var newItem = new Element('li.loading#load-'+i,{html:'<img src="system/modules/SWBackend/assets/loading.gif">'});
				var deleteItem = new Element('span',{
					'class': 'sortable_delete',
					'events': {
						click: function(){
							Backend.removeMultiSrcThumbnails(this,'ctrl_'+fieldName,'ctrl_orderSRC');
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
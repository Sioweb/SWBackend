String.implement({
	toElement: function() {
		return new Element('div', {html:this}).getFirst();
	}
});

Backend.removeMultiSrcThumbnails = function(item, id, oid) {
	var parent = item.getParent('li'),
		uuid = parent.get('data-id'),
		re = new RegExp(",?"+uuid,'g'),
		idv = document.getElementById(id).value.replace(re,''),
		oidv = document.getElementById(oid).value.replace(re,'');
	document.getElementById(id).value = (idv.substring(0,1) == ',' ? idv.substring(1) : idv);
	document.getElementById(oid).value = (oidv.substring(0,1) == ',' ? oidv.substring(1) : oidv);
	parent.destroy();
};

Backend.makeMultiSrcSortable = function(id, oid) {
	var list = new Sortables($(id), {
		contstrain: true,
		opacity: 0.6,
		handle: 'img'
	}).addEvent('complete', function() {
		var els = [],
			lis = $(id).getChildren('li'),
			i;
		for (i=0; i<lis.length; i++) {
			els.push(lis[i].get('data-id'));
		}
		$(oid).value = els.join(',');
	});
	list.fireEvent("complete"); // Initial sorting
};

Backend.openModalFolderSelector = function(options) {
	var opt = options || {},
		max = (window.getSize().y-180).toInt();
	if (!opt.height || opt.height > max) opt.height = max;
	var M = new SimpleModal({
		'width': opt.width,
		'btn_ok': Contao.lang.close,
		'draggable': false,
		'overlayOpacity': .5,
		'onShow': function() { document.body.setStyle('overflow', 'hidden'); },
		'onHide': function() { document.body.setStyle('overflow', 'auto'); }
	});
	M.addButton(Contao.lang.close, 'btn', function() {
		this.hide();
	});
	M.addButton(Contao.lang.apply, 'btn primary', function() {
		var val = [],
			frm = null,
			frms = window.frames;
		for (i=0; i<frms.length; i++) {
			if (frms[i].name == 'simple-modal-iframe') {
				frm = frms[i];
				break;
			}
		}
		if (frm === null) {
			alert('Could not find the SimpleModal frame');
			return;
		}
		var inp = frm.document.getElementById('tl_listing').getElementsByTagName('input');
		for (var i=0; i<inp.length; i++) {
			if (!inp[i].checked || inp[i].id.match(/^check_all_/)) continue;
			if (!inp[i].id.match(/^reset_/)) val.push(inp[i].get('value'));
		}

		if(val[0])
			options.upload_callback(val[0]);
		
		this.hide();
	});
	M.show({
		'title': opt.title,
		'contents': '<iframe src="' + opt.url + '" name="simple-modal-iframe" width="100%" height="' + opt.height + '" frameborder="0"></iframe>',
		'model': 'modal'
	});
};

Backend.moduleWizard = function(el, command, id) {
	var table = $(id),
		tbody = table.getElement('tbody'),
		parent = $(el).getParent('tr'),
		rows = tbody.getChildren(),
		tabindex = tbody.get('data-tabindex'),
		input, select, childs, a, i, j;

	Backend.getScrollOffset();

	switch (command) {
		case 'copy':
			var tr = new Element('tr');
			childs = parent.getChildren();
			for (i=0; i<childs.length; i++) {
				var next = childs[i].clone(true).inject(tr, 'bottom');
				if (select = childs[i].getFirst('select')) {
					next.getFirst('select').value = select.value;
				}
			}

			tr.inject(parent, 'after');
			tr.getElement('.chzn-container').destroy();
			tr.getElement("div.tl_select_column").destroy();
			new Chosen(tr.getElement('select.tl_select'));
			Stylect.convertSelects();
			break;
		case 'up':
			if (tr = parent.getPrevious('tr')) {
				parent.inject(tr, 'before');
			} else {
				parent.inject(tbody, 'bottom');
			}
			break;
		case 'down':
			if (tr = parent.getNext('tr')) {
				parent.inject(tr, 'after');
			} else {
				parent.inject(tbody, 'top');
			}
			break;
		case 'delete':
			if (rows.length > 1) {
				parent.destroy();
			}
			break;
	}

	rows = tbody.getChildren();

	for (i=0; i<rows.length; i++) {
		childs = rows[i].getChildren();
		for (j=0; j<childs.length; j++) {
			if (a = childs[j].getFirst('a.chzn-single')) {
				a.set('tabindex', tabindex++);
			}
			if (select = childs[j].getFirst('select')) {
				select.name = select.name.replace(/\[[0-9]+\]/g, '[' + i + ']');
			}
			if (input = childs[j].getFirst('input[type="checkbox"]')) {
				input.set('tabindex', tabindex++);
				input.name = input.name.replace(/\[[0-9]+\]/g, '[' + i + ']');
			}
		}
	}

	new Sortables(tbody, {
		contstrain: true,
		opacity: 0.6,
		handle: '.drag-handle'
	});
};

AjaxRequest.toggleVisibility = function(el, id, table) {
	el.blur();
	var img = null,
		image = $(el).getFirst('img'),
		publish = (image.src.indexOf('invisible') != -1),
		div = el.getParent('div'),
		next;

	// Find the icon depending on the view (tree view, list view, parent view)
	if (div.hasClass('tl_right')) {
		img = div.getPrevious('div').getElement('img');
	} else if (div.hasClass('tl_listing_container')) {
		img = el.getParent('td').getPrevious('td').getFirst('div.list_icon');
		if (img == null) { // Comments
			img = el.getParent('td').getPrevious('td').getElement('div.cte_type');
		}
		if (img == null) { // showColumns
			img = el.getParent('tr').getFirst('td').getElement('div.list_icon_new');
		}
	} else if ((next = div.getNext('div')) && next.hasClass('cte_type')) {
		img = next;
	}

	// Change the icon
	if (img != null) {
		// Tree view
		if (img.nodeName.toLowerCase() == 'img') {
			if (img.getParent('ul.tl_listing').hasClass('tl_tree_xtnd')) {
				if (publish) {
					img.src = img.src.replace(/_\.(gif|png|jpe?g)/, '.$1');
				} else {
					img.src = img.src.replace(/\.(gif|png|jpe?g)/, '_.$1');
				}
			} else {
				if (img.src.match(/folPlus|folMinus/)) {
					if (img.getParent('a').getNext('a')) {
						img = img.getParent('a').getNext('a').getFirst('img');
					} else {
						img = new Element('img'); // no icons used (see #2286)
					}
				}
				var index;
				if (publish) {
					index = img.src.replace(/.*_([0-9])\.(gif|png|jpe?g)/, '$1');
					img.src = img.src.replace(/_[0-9]\.(gif|png|jpe?g)/, ((index.toInt() == 1) ? '' : '_' + (index.toInt() - 1)) + '.$1');
				} else {
					index = img.src.replace(/.*_([0-9])\.(gif|png|jpe?g)/, '$1');
					img.src = img.src.replace(/(_[0-9])?\.(gif|png|jpe?g)/, ((index == img.src) ? '_1' : '_' + (index.toInt() + 1)) + '.$2');
				}
			}
		}
		// Parent view
		else if (img.hasClass('cte_type')) {
			if (publish) {
				img.addClass('published');
				img.removeClass('unpublished');
			} else {
				img.addClass('unpublished');
				img.removeClass('published');
			}
		}
		// List view
		else {
			if (publish) {
				img.setStyle('background-image', img.getStyle('background-image').replace(/_\.(gif|png|jpe?g)/, '.$1'));
			} else {
				img.setStyle('background-image', img.getStyle('background-image').replace(/\.(gif|png|jpe?g)/, '_.$1'));
			}
		}
	}

	// Mark disabled format definitions
	if (table == 'tl_style') {
		div.getParent('div').getElement('pre').toggleClass('disabled');
	}

	// Send request
	if (publish) {
		image.getParent('a').addClass('published');
		image.getParent('a').removeClass('unpublished');
		image.src = image.src.replace('invisible.gif', 'visible.gif');
		new Request.Contao({'url':window.location.href, 'followRedirects':false}).get({'tid':id, 'state':1,'use': table});
	} else {
		image.getParent('a').addClass('unpublished');
		image.getParent('a').removeClass('published');
		image.src = image.src.replace('visible.gif', 'invisible.gif');
		new Request.Contao({'url':window.location.href, 'followRedirects':false}).get({'tid':id, 'state':0,'use': table});
	}

	return false;
};
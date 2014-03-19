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
		new Request.Contao({'url':window.location.href, 'followRedirects':false}).get({'tid':id, 'state':1});
	} else {
		image.getParent('a').addClass('unpublished');
		image.getParent('a').removeClass('published');
		image.src = image.src.replace('visible.gif', 'invisible.gif');
		new Request.Contao({'url':window.location.href, 'followRedirects':false}).get({'tid':id, 'state':0});
	}

	return false;
};
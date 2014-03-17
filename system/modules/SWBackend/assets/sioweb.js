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
String.implement({
  toElement: function() {
    return new Element('div', {html:this}).getFirst();
  }
});

Backend.toggleNextArticles = function(img,action_id,id) {
  var parent = img.getParent('.tl_folder'),
    item = parent,
    nextItem = null,
    next = false;

  if(parent.hasClass('open') === false) {
    next = true;
    img.src = img.src.replace('open','close');
    while(next && (nextItem = item.addClass('open').getNext()).hasClass('tl_file') === true) {
      item = nextItem.addClass('open');
      next = (item.getNext() !== null);
    }
    new Request.Contao({
      onRequest: AjaxRequest.displayBox(Contao.lang.loading + ' …'),
      onSuccess: function(txt) {
        AjaxRequest.hideBox();
      }
    }).post({'action':'toggleArticle', 'id':action_id, 'state':1,'REQUEST_TOKEN':Contao.request_token});
  } else {
    next = true;
    img.src = img.src.replace('close','open');
    while(next && (nextItem = item.removeClass('open').getNext()).hasClass('tl_file') === true) {
      item = nextItem.removeClass('open');
      next = (item.getNext() !== null);
    }
    new Request.Contao({
    }).post({'action':'toggleArticle', 'id':action_id, 'state':0,'REQUEST_TOKEN':Contao.request_token});
  }
};

Backend.siowebOptionsWizard = function(item,mode,id) {
  var tl_right = item.getParent(),
    row = tl_right.getParent();

  switch(mode)
  {
    case 'delete':
      row.destroy();
      break;
    case 'add':
      row = item.getParent().getPrevious();
      Backend.openModalOptionsWizard({title: 'Filemanager', url: 'contao/main.php?do=files&amp;key=createField&amp;mode=add&amp;id=newLabel&amp;popup=1&amp;rt='+Contao.request_token,width:640,upload_callback: function(frm){
        var val = [],
          inp = frm.document.getElementById('fileManager').getElementsByTagName('input'),
          newRow = row.clone(true),
          mode = row.getChildren('label').get('for')[0].replace(/ctrl_(.+?)(?![0-9]+)_([0-9])+/g,'$1_$2').split('_'),
          label = newRow.getChildren('label'),
          input = newRow.getChildren('input').set('id','ctrl_'+mode[0]+'_'+mode[1]);

        Backend.addModalOptionsWizard(inp,label,input,mode[0]);
        newRow.inject(row,'after');
      }});
      break;
  }
};

Backend.addModalOptionsWizard = function(inp,label,input,mode){
  var val = [],
    inpText = null;

  for(var i=0;i<inp.length;i++)
  {
    if(inp[i].name == 'newLabel') inpText = inp[i];
    if(inp[i].type!="radio" || !inp[i].checked || inp[i].id.match(/^check_all_/)) continue;
    if(!inp[i].id.match(/^reset_/)) val.push(inp[i].get('value'));
  }

  if(val[0])
  {
    if(val[0] == 'newLabel')
      val[0] = inpText.value.replace(/[^a-zA-Z0-9]+/g,'');
    if(!val[0])
      return;

    if(typeof Sioweb.lang['aw_'+val[0]] !== 'undefined')
      label.set('text',Sioweb.lang['aw_'+val[0]]+' ('+val[0]+')');
    else if(typeof Sioweb.lang[val[0]] !== 'undefined')
      label.set('text',Sioweb.lang[val[0]]+' ('+val[0]+')');
    else
      label.set('text',val[0]);

    label[0].removeAttribute('onclick');
    label.set('for', label.get('for')[0].replace('_'+mode+'_','_'+val[0]+'_'));
    label.removeEvent('click').addEvent('click',function(){
      Backend.changeOptionWizardField(this,val[0]);
    });
    input.set('name', input.get('name')[0].replace('['+mode+']','['+val[0]+']'));
    input.set('id', input.get('id')[0].replace('_'+mode+'_','_'+val[0]+'_'));
  }
};

Backend.changeOptionWizardField = function(item, mode) {
  var parent = item.getParent(),
    label = parent.getChildren('label'),
    input = parent.getChildren('input');

  Backend.openModalOptionsWizard({
    title: 'Filemanager', url: 'contao/main.php?do=files&amp;key=createField&amp;mode=change&amp;id='+mode+'&amp;popup=1&amp;rt='+Contao.request_token,width:640,upload_callback: function(frm){
      var val = [],
        inpText = null,
        inp = frm.document.getElementById('fileManager').getElementsByTagName('input');
      
      Backend.addModalOptionsWizard(inp,label,input);
    }
  });
};

Backend.openModalOptionsWizard = function(options){
  var opt = options || {},
    max = (window.getSize().y-180).toInt();
  if(!opt.height || opt.height > max) opt.height = max;
  var M = new SimpleModal({
    'width': opt.width,
    'btn_ok': Contao.lang.close,
    'draggable': false,
    'overlayOpacity': 0.5,
    'onShow': function() { document.body.setStyle('overflow', 'hidden'); },
    'onHide': function() { document.body.setStyle('overflow', 'auto'); }
  });
  M.addButton(Contao.lang.close, 'btn', function() {
    this.hide();
  });
  M.addButton(Contao.lang.apply, 'btn primary', function() {
    var frms = window.frames;
    for(i=0; i<frms.length; i++) {
      if(frms[i].name == 'simple-modal-iframe') {
        options.upload_callback(frms[i]);
        break;
      }
    }
    this.hide();
  });
  M.show({
    'title': opt.title,
    'contents': '<iframe src="' + opt.url + '" name="simple-modal-iframe" width="100%" height="' + opt.height + '" frameborder="0"></iframe>',
    'model': 'modal'
  });
};

Backend.removeMultiSrcThumbnails = function(item, id, oid) {
  var parent = item.getParent('li'),
    uuid = parent.get('data-id'),
    re = new RegExp(",?"+uuid,'g'),
    idv = document.getElementById(id).value.replace(re,'');
  document.getElementById(id).value = (idv.substring(0,1) == ',' ? idv.substring(1) : idv);

  if(document.getElementById(oid) !== null) {
    var oidv = document.getElementById(oid).value.replace(re,'');
    document.getElementById(oid).value = (oidv.substring(0,1) == ',' ? oidv.substring(1) : oidv);
  }
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
    for(i=0; i<lis.length; i++) {
      els.push(lis[i].get('data-id'));
    }
    $(oid).value = els.join(',');
  });
  list.fireEvent("complete"); // Initial sorting
};

Backend.openModalFolderSelector = function(options) {
  var opt = options || {},
    max = (window.getSize().y-180).toInt();
  if(!opt.height || opt.height > max) opt.height = max;
  var M = new SimpleModal({
    'width': opt.width,
    'btn_ok': Contao.lang.close,
    'draggable': false,
    'overlayOpacity': 0.5,
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
    for(i=0; i<frms.length; i++) {
      if(frms[i].name == 'simple-modal-iframe') {
        frm = frms[i];
        break;
      }
    }
    if(frm === null) {
      alert('Could not find the SimpleModal frame');
      return;
    }
    if (frm.document.location.href.indexOf('contao/main.php') != -1) {
      alert(Contao.lang.picker);
      return; // see #5704
    }
    var inp = frm.document.getElementById('tl_select').getElementsByTagName('input');
    for(var i=0; i<inp.length; i++) {
      if(!inp[i].checked || inp[i].id.match(/^check_all_/)) continue;
      if(!inp[i].id.match(/^reset_/)) val.push(inp[i].get('value'));
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

Backend.addModalItems = function(msg) {
  var json = JSON.parse(msg),
      parent = $('ctrl_'+json.id).getParent();
  parent.set('html',json.html);
};

Backend.moduleWizard = function(el, command, id) {
  var table = $(id),
    tbody = table.getElement('tbody'),
    parent = $(el).getParent('tr'),
    rows = tbody.getChildren(),
    tabindex = tbody.get('data-tabindex'),
    input, select, childs, a, i, j;

  Backend.getScrollOffset();

  switch(command) {
    case 'copy':
      var tr = new Element('tr');
      childs = parent.getChildren();
      for(i=0; i<childs.length; i++) {
        var next = childs[i].clone(true).inject(tr, 'bottom');
        if((select = childs[i].getFirst('select'))) {
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
      if((tr = parent.getPrevious('tr'))) {
        parent.inject(tr, 'before');
      } else {
        parent.inject(tbody, 'bottom');
      }
      break;
    case 'down':
      if((tr = parent.getNext('tr'))) {
        parent.inject(tr, 'after');
      } else {
        parent.inject(tbody, 'top');
      }
      break;
    case 'delete':
      if(rows.length > 1) {
        parent.destroy();
      }
      break;
  }

  rows = tbody.getChildren();

  for(i=0; i<rows.length; i++) {
    childs = rows[i].getChildren();
    for(j=0; j<childs.length; j++) {
      if((a = childs[j].getFirst('a.chzn-single'))) {
        a.set('tabindex', tabindex++);
      }
      if((select = childs[j].getFirst('select'))) {
        select.name = select.name.replace(/\[[0-9]+\]/g, '[' + i + ']');
      }
      if((input = childs[j].getFirst('input[type="checkbox"]'))) {
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
    next,
    icon = null;

  // Find the icon depending on the view (tree view, list view, parent view)
  if(div.hasClass('tl_right')) {
    img = div.getPrevious('div').getElement('img');
  } else if(div.hasClass('tl_listing_container')) {
    img = el.getParent('td').getPrevious('td').getFirst('div.list_icon');
    if(img === null) { // Comments
      img = el.getParent('td').getPrevious('td').getElement('div.cte_type');
    }
    if(img === null) { // showColumns
      img = el.getParent('tr').getFirst('td').getElement('div.list_icon_new');
    }
  } else if((next = div.getNext('div')) && next.hasClass('cte_type')) {
    img = next;
  }

  if(div.getPrevious('div') !== null && div.getPrevious('div').getElement('.icon') !== null)
    icon = div.getPrevious('div').getElement('.icon img');

  // Change the icon
  if(img !== null) {
    // Tree view
    if(img.nodeName.toLowerCase() == 'img') {
      if(!icon && img.getParent('ul.tl_listing').hasClass('tl_tree_xtnd')) {
        if(publish) {
          img.src = img.src.replace(/_\.(gif|png|jpe?g)/, '.$1');
        } else {
          img.src = img.src.replace(/\.(gif|png|jpe?g)/, '_.$1');
        }
      } else {
        var index;
        if(publish) {
          index = icon.src.replace(/.*_([0-9])\.(gif|png|jpe?g)/, '$1');
          icon.src = icon.src.replace(/_[0-9]\.(gif|png|jpe?g)/, ((index.toInt() == 1) ? '' : '_' + (index.toInt() - 1)) + '.$1');
        } else {
          index = icon.src.replace(/.*_([0-9])\.(gif|png|jpe?g)/, '$1');
          icon.src = icon.src.replace(/(_[0-9])?\.(gif|png|jpe?g)/, ((index == icon.src) ? '_1' : '_' + (index.toInt() + 1)) + '.$2');
        }
      }
    }
    // Parent view
    else if(img.hasClass('cte_type')) {
      if(publish) {
        img.addClass('published');
        img.removeClass('unpublished');
      } else {
        img.addClass('unpublished');
        img.removeClass('published');
      }
    }
    // List view
    else {
      if(publish) {
        img.setStyle('background-image', img.getStyle('background-image').replace(/_\.(gif|png|jpe?g)/, '.$1'));
      } else {
        img.setStyle('background-image', img.getStyle('background-image').replace(/\.(gif|png|jpe?g)/, '_.$1'));
      }
    }
  }

  // Mark disabled format definitions
  if(table == 'tl_style') {
    div.getParent('div').getElement('pre').toggleClass('disabled');
  }

  // Send request
  if(publish) {
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
$s(document).ready(function(){
  var dragged = null,
      dragNDrop = $s('.dragNdrop');
  dragNDrop.uploadAjax({
    data: {
      isAjaxRequest: 1,
      FORM_SUBMIT: 'tl_upload',
      MAX_FILE_SIZE: 2048000,
    },
    dragover: function(e){
      var selfObj = this;
      e.stopPropagation();
      e.preventDefault();
      clearTimeout(dragged);

      $s(selfObj).addClass('sw_drag_over');
      dragged = setTimeout(function(){$s(selfObj).removeClass('sw_drag_over');},400);
    }
  });
});
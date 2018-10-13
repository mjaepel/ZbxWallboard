obj_refresh = setTimeout('auto_refresh();',30000);

function auto_refresh(){
	window.location = location.href;
}

function showDialogDetails(id,eventid){
	clearTimeout(obj_refresh);

	var dialog = $(id).data('dialog');
	dialog.options.onDialogClose =  function() { obj_refresh = setTimeout('auto_refresh();',30000); };

	if (dialog.element.data('opened')) {
		dialog.close();
	}
	else {
		$.ajax({
			url: 'index.php?action=details&eventid=' + eventid,
			data: 'html',
			async: false
		}).done(function(data) {
			$(id + '_content').html(data);
		}).fail(function() {
			$(id + '_content').html('<h1>Error</h1><p>Ajax Request failed!</p>');
		});
		dialog.open();
	}
}

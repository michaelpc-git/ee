jQuery(function($){

	$(".squat-radar-widget.squat-radar-ajax").each(function(index, widget) {
		$.ajax({
			url:		squat_radar_widget.ajaxurl,
			context:	document.body,
			type:		'POST',
			data:		{
						action: "squat_radar_events",
						instance: squat_radar_widget[widget.id],
			},
			success: 	function(result){

				if (result.is_error) {
					if (result.error) {
						$(widget).append(
							'<p>Error: ' + result.error.code + ' - ' + result.error.message + '</p>'
						);
					}
				} else {
					$(widget).empty();
					$(widget).append(result.html);
				}
			},
			error: 		function(res){
						console.log('AJAX error', res);
			}
		});
	});
});

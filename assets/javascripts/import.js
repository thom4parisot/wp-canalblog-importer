/**
 * Canalblog Importer Utilities
 * 
 * Globally, a response is composed of WP_Object_Response with objects+operation
 * It could be nicely done through custom listeners but you know...
 */
(function($, endpoint){
	var CI = {}
	
	CI.StartOperation = function(){
		$('#ajax-results').removeClass('hidden');
		$(this).attr('disabled', true);
		
		CI.ExecuteRemoteOperation();
	};
	
	CI.ExecuteRemoteOperation = function(){
		$.post(endpoint, {
			"action": "canalblog_import_remote_operation",
			"_wpnonce": $('#_wpnonce').val()
		}, CI.HandleResponse);
	};
	
	CI.PrepareNextOperation = function(){
		$('#ajax-results .worker-container').addClass('hidden');
		$('.submit .start-remote-operation').addClass('hidden');
		$('.submit .next-operation').removeClass('hidden');
	};
	
	CI.HandleResponse = function(response){
		var $AjaxResponseHolder = $('#ajax-responses');

		/*
		 * Adding messages to the UI
		 */
		$('object > response_data', response).each(function(){
			$AjaxResponseHolder.prepend('<li>'+$(this).text()+'</li>');
		});
		$('#import-progress-value').text($('operation progress', response).text());
		
		/*
		 * Continue batch
		 */
		if (1 !== parseInt($('operation finished', response).text(), 10))
		{
			return CI.ExecuteRemoteOperation();
		}
	
		/*
		 * Next page?
		 */
		else
		{
			return CI.PrepareNextOperation();
		}
	};
	
	$(function(){
		$('.submit .button-primary.start-remote-operation').bind('click', CI.StartOperation);
	});
})(jQuery, ajaxurl);
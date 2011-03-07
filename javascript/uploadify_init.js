jQuery.noConflict();
(function($) {
$(function() {
	$('.UploadifyField').livequery(function() {
		$(this).each(function() {
			$t = $(this);
			if(!$t.hasClass('backend')) {
				var $input = $('input.uploadify',$t);
				name = $input.attr('name');
				id = $input.attr('id');
				klass = $input.attr('class');
				var $uploader = $('<input type="hidden" class="'+klass+'" name="'+name+'" id="'+id+'" disabled="disabled"/>');
				$input.replaceWith($uploader);
			}
			else {
				$uploader = $('input.uploadify', $t);
			}			
			/**
			 Build a set of options to pass to the uploadify object
			 														**/			
			opts = $uploader.metadata();
			$.extend(opts, {
				onComplete: function(event, queueID, fileObj, response, data) {
					$e = $(event.currentTarget);
					$container = $e.parents('.UploadifyField:first');
					if(isNaN(response)) {
						alert(response);
					}
					$e = $(event.currentTarget);
					if($e.metadata().refreshlink) {
						$preview = $('#upload_preview_'+$e.attr('id'));
						$inputs = $('.inputs input', $preview);			
						if($preview.length) {
							ids = new Array();
							$inputs.each(function() {
								if($(this).val().length) {
									ids.push($(this).val());
								}
							});
							ids.push(response);
														
							$.ajax({
								url: $e.metadata().refreshlink,
								data: {'FileIDs' : ids.join(",")},
								async: false,
								dataType: "json",
								success: function(data) {
									$preview.html(data.html);
								}
							});
						}
					}
				},
				onAllComplete : function(event) {
					$e = $(event.currentTarget);
					$e.data('active',false);
					$e.parents('form').find(':submit').attr('disabled',false).removeClass('disabled');
					$container = $e.parents('.UploadifyField:first');
					$('.preview',$container).show();
					if($e.metadata().upload_on_submit) {
						$e.parents('form').submit();
					}

				},
				onSelectOnce: function(event, queueID, fileObj) {
					$e = $(event.currentTarget);
					if($('#folder_select_'+$e.attr('id')).length) {
						folder_id = $('#folder_select_'+$e.attr('id')).find('select:first').val();
					}
					else if($('#folder_hidden_'+$e.attr('id')).length) {
						folder_id = $('#folder_hidden_'+$e.attr('id')).val();
					}
					data = $e.uploadifySettings('scriptData');
					$.extend(data, {
						FolderID : folder_id
					});
					$e.uploadifySettings('scriptData', data, true);
					$e.data('active',true);
					if(!$e.metadata().upload_on_submit) {
						$e.parents('form').find(':submit').attr('disabled',true).addClass('disabled');
					}
				},
				onCancel: function(event, queueID, fileObj, data) {
					$e = $(event.currentTarget);
					if (data.fileCount == 0) {
						$e.closest('.UploadifyField').find('.preview').show().html('<div class="no_files"></div>');
						if (!$e.metadata().auto && !$e.metadata().upload_on_submit) { 
							$('.uploadifyfield_queue_actions').show(); 
						}
					}
				}
			});
			
			// Handle form submission if the upload happens on submit
			if($uploader.metadata().upload_on_submit) {
				$(this).parents('form:first').submit(function(e) {				
					cansubmit = true;
					$('input.uploadify').each(function() {
						if($(this).data('active')) {
							cansubmit = false;
							$(this).uploadifyUpload();
						}
					});
					return cansubmit;						

				});
			}
			$uploader.uploadify(opts);

			// Build the "fake" CSS button
			var $buttonWrapper = $('.button_wrapper', $t);
			var $fakeButton = $(".button_wrapper a",$t);
			var width = $fakeButton.outerWidth();
			var height = $fakeButton.outerHeight();
			opts.width = width;
			opts.height = height;
			$buttonWrapper.css("width", width + "px").css("height", height + "px")			
			
			// Activate uploadify
			// Tabs for the backend
			if($t.find('.horizontal_tab_wrap').length) {
		      $tabSet = $t.find('.horizontal_tab_wrap');
		      var tabContainers = $('div.horizontal_tabs > div', $tabSet);
		      tabContainers.hide().filter(':last').show();
		      
		      $('div.tabNavigation ul.navigation a', $tabSet).live("click",function () {		      
		          tabContainers.hide();
		          tabContainers.filter(this.hash).show();
		          $(this).parents('ul:first').find('.selected').removeClass('selected');
		          $(this).addClass('selected');
		          return false;
		      });
		      
		      $('div.tabNavigation ul.navigation a:last', $tabSet).click();			
			}
			
			
						
		});
	});
	
	/**
	 Attach behaviours external to the uploader, e.g. queue functions
	 																	**/
	
	// Delete buttons for the queue items
	$('.upload_previews li .delete a').live("click", function() {
		$t = $(this);
		$.post(
			$t.attr('href'),
			{'FileID' : $t.attr('rel')},
			function() {
				$t.parents("li:first").fadeOut(function() {
					$(this).remove();
					$('.inputs input[value='+$t.attr('rel')+']').remove();
				});
			}
		);
		return false;
	});

	
	// Change folder ajax post
	$('.folder_select').find(':submit').live("click", function() {
		$t = $(this);
		$target = $(this).parents('.UploadifyField').find('.uploadify');
		$folderSelect = $('#folder_select_'+$target.attr('id'));
		folder_id = $('select:first', $folderSelect).val();
		new_folder = $('input:first', $folderSelect).val();
		$folderSelect.parents('.folder_select_wrap').load(
			$t.metadata().url, 
			{ FolderID : folder_id, NewFolder : new_folder}
		);
		return false;
	});
	$('.folder_select :submit').livequery(function() {
		$(this).siblings('label').hide();
	});
	
	// Attach sorting, if multiple uploads
	$('.upload_previews ul.sortable').livequery(function() {
		var $list = $(this);
		var meta = $list.metadata();
		$list.sortable({
			update: function(e) {
				$.post(meta.url, $list.sortable("serialize"));
			},
			containment : 'document',
			tolerance : 'intersect'
		});
	});

	
	$('.import_dropdown select').livequery("change", function() {
		$t = $(this);
		$target = $t.parents('.import_dropdown').find('.import_list');
		$t.parents('.import_dropdown').find('button').hide();
		$target.html('').addClass('loading').show().css('height','50px');
		$.ajax({
			url : $t.metadata().url,
			data : { FolderID : $t.val() },
			success : function(data) {
				$target.slideUp(function() {
					$(this).removeClass('loading').css({'height' : 'auto', 'max-height' : '150px','overflow' : 'auto'});
					$(this).html(data);
					if($('input', $(this)).length) {
						$t.parents('.import_dropdown').find('button').show();
					}
					$(this).slideDown();		
				});	
			}
		});
	});
	
	$('.import_dropdown button').live("click", function() {
		url = $(this).metadata().url;
		$target = $(this).parents('.UploadifyField').find('.preview');
		$uploader = $(this).parents('.UploadifyField').find('.uploadify'); 
		$list = $(this).parents('.import_dropdown');
		ids = new Array();
		$target.find('input').each(function() {
			if($(this).val().length) {
				ids.push($(this).val());
			}
		});
		$list.find(':checked').each(function() {
			ids.push($(this).val());
		});
		$.ajax({
			url: $uploader.metadata().refreshlink,
			data: {'FileIDs' : ids.join(",")},
			dataType : "json",
			success: function(data) {
				$target.html(data.html);
				$msg = $list.find('.import_message');
				$msg.html(data.success).fadeIn();
				setTimeout(function() {
					$msg.fadeOut()
				},5000);
				$list.find('select').val('');
				$list.find('button').hide();
				$list.find('.import_list').slideUp();
				
			}
		});
		return false;
	});
});
})(jQuery);
(function( $ ) {
	'use strict';
	$(function () {
	
		/***
		 * send message to trainer and classmate
		 */
		$('.bbclms-send-message-widget').each( function(idx){
			
			$(this).on('click', function(e){
				
				e.preventDefault();
				var $this = $(this);
				var $form_wrapper = $this.parents('#form_wrapper');			
				var $parent_form = $this.parents('#form_contact_trainer');
				var $content = $parent_form.find('.bbclms-trainer-message');
				var $sender_id = $parent_form.find('.bbclms-trainer-msg-sender-id');
				var $reciever_id = $parent_form.find('.bbclms-trainer-msg-receiver-id');
				var $reciever_name = $parent_form.find('.bbclms-trainer-msg-receiver-name');
				var $course_id = $parent_form.find('.bbclms-trainer-msg-course-id');			
				
				$.post( ajaxurl, {
						action: 'bbclms_contact_trainer_ajax',					
						content: $content.val(),                    
						sender_id: $sender_id.val(),
						reciever_id: $reciever_id.val(),
						//reciever_name: $reciever_name.val(),
						course_id: $course_id.val()
					},
					function(response) {
						if ( response.length > 0 && response != 'Failed' ) {
							$form_wrapper.append('<div class="learndash-message tick fa fa-check">The message has been sent to '+$reciever_name.val()+'.</div>');
						}
					});			
			});
		});
		
		/***
		 * toggle classmate msg box
		 */
		$('.form_toggler').each( function(idx){
			
			$(this).on('click', function(e){
				
				e.preventDefault();
				var $this = $(this);
				var $data_toggler = $this.attr('data-toggler');
			//alert($data_link)	
				$( '#form_toggler_'+$data_toggler ).toggle( 'slow' );
				
			});
		});
		
		/***
		 * Classmates Widget View All
		 */	
		$('.learndash-view-all-participants a').click( function( event ) {
			event.preventDefault();
			var el = $(this);
			var hiddenLearners = el.closest('.widget_learndash_course_classmates').find('.learndash-course-classmate.hide');
			var txt = hiddenLearners.is(':visible') ? 'View All' : 'Close';
			$(this).text(txt);
			hiddenLearners.slideToggle( 300 );
		});
		
		/***
		 * Admin View Import Users show/hide
		 */			
		$('.toggle_group_users_view').each( function(idx){			
			$(this).on('click', function(e){				
				e.preventDefault();			
				var el = $(this);
				var groupid = el.attr('data-groupid');
				var prnt = el.parents('.group_users');
				var hiddenLearners = prnt.next('.toggle_group_users'); // next()
				var txt = hiddenLearners.is(':visible') ? 'SHOW '+groupid+' GROUP USERS' : 'HIDE '+groupid+' GROUP USERS';
				el.text(txt);
				hiddenLearners.slideToggle( 600 );
			});
		});
	
		/***
		 * backend manual students csv import
		 */		
		$('body').on('click', '#manual_import', function(e){
			//alert('aa');
			e.preventDefault();
			var $this = $(this);
				
			var $response_div = $('#manual_import_response');
			$.post( ajaxurl, {
					action: 'bbclms_students_to_courses_groups_csv'
				},
				function(response) {
					if ( response.length > 0 && response != 'Failed' ) {
						//alert(response);
						//response = $.parseJSON(response);
						$response_div.html(response);
						
					}
				});			
		});
		
		
		/***
		 * backend manual trainers import
		 */		
		$('body').on('click','#manual_import_trainers', function(e){
			//alert('aa');
			e.preventDefault();
			var $this = $(this);
				
			var $response_div = $('#manual_import_response');
			$.post( ajaxurl, {
					action: 'bbclms_trainers_to_groups_csv'
				},
				function(response) {
					if ( response.length > 0 && response != 'Failed' ) {
						//alert(response);
						//response = $.parseJSON(response);
						$response_div.html(response);
						$('.manual_import_students').attr('id', 'manual_import').css('opacity','1');
					}
				});			
		});
		
		
		/***
		 * backend manual groups to courses csv import
		 */		
		$('#manual_import_groups').on('click', function(e){
			//alert('aa');
			e.preventDefault();
			var $this = $(this);
				
			var $response_div = $('#manual_import_response');
			$.post( ajaxurl, {
					action: 'bbclms_groups_to_courses_csv'
				},
				function(response) {
					if ( response.length > 0 && response != 'Failed' ) {
						//alert(response);						
						$response_div.html(response);						
						$('.manual_import_trainers').attr('id', 'manual_import_trainers').css('opacity','1');
					}
				});			
		});
		
		
		/***
		 * process_all_imports	 csv import
		 */	
	 
		$('#process_all_imports').on('click', function(e){
			//alert('aa');
			e.preventDefault();
			var $this = $(this);
				
			var $response_div = $('#manual_import_response');
			$.post( ajaxurl, {
					action: 'process_all_imports'
				},
				function(response) {
					if ( response.length > 0 && response != 'Failed' ) {
						//alert(response);						
						$response_div.html(response);						
						$this.css('display','none');
					}
				});			
		}); 
		
		
		/***
		 * check_process_all_imports csv import
		 */	
	 
		$('#wpbody-content').on('click', '#check_process_all_imports', function(e){
			//alert('aa');
			e.preventDefault();
			var $this = $(this);
			var $process_all_imports = $('#process_all_imports');
				
			var $response_div = $('#manual_import_response');
			$.post( ajaxurl, {
					action: 'check_process_all_imports'
				},
				function(response) {
					if ( response.length > 0 && response != 'Failed' ) {
						//alert(response);						
						if( response == 'completed' ){
							$this.css('display','none');
							$process_all_imports.css('display','inline-block');
							$response_div.html(response);
						} else{
							$response_div.append('Import in progress<br />');
						}
						
						
					}
				});			
		});
		
		// if csv cron checked hide process all button
		if( $( '#csv_files_enable_cron' ).is(':checked') ){			
			$( '#process_all_imports' ).css('display', 'none').removeClass( 'cansee' );			
		} else{
			$( '#process_all_imports' ).css('display', 'inline-block');
		}
		
		/***
		 * toggle visibility process_all_imports button on Cron job check 
		 */
		$( '#csv_files_enable_cron' ).on('click', function(e){
			
			e.preventDefault();
			var $this = $(this);
			var $process_all_imports = $('#process_all_imports');				
			var $response_div = $('#manual_import_response');			
			
			$.post( ajaxurl, {
					action: 'check_process_all_imports'
				},
				function(response) {
					if ( response.length > 0 && response != 'Failed' ) {
						//alert(response);						
						if( response == 'completed' ){
														
							if( $this.prop( 'checked' ) == true ){
								$this.prop('checked', false);
							} else if( $this.prop( 'checked' ) == false ){
								$this.prop('checked', true);
							}
							$response_div.html('');
							// cansee
							if( $process_all_imports.hasClass( 'cansee' ) ){
								$process_all_imports.css('display', 'none').removeClass( 'cansee' );														
							} else{
								$process_all_imports.css('display', 'inline-block').addClass( 'cansee' );														
							}														
							$( '.cron-warning' ).remove();							
						} else{														
							$this.parents('form').prepend('<div class="cron-warning" style="color:red; font-weight:bold;">Manual Import in progress, try Cron function later ... </div>');
						}												
					}
				});
		});
		
		/***
		 * list import logs
		 */
		$('#list_log_files').each( function(idx){
			
			$(this).on('click', function(e){
				
				e.preventDefault();
				var $this = $(this);
						
				var $response_div = $('#log_files_response');
				$.post( ajaxurl, {
						action: 'bbclms_list_import_user_logs'	
					},
					function(response) {
						if ( response.length > 0 && response != 'Failed' ) {
							//alert(response);							
							$response_div.html(response);
						}
					});			
			});
		});
		
		/***
		 * delete_log_files
		 */
		$('#delete_log_files').each( function(idx){
			
			$(this).on('click', function(e){				
				confirm("You are about to delete All Log files. Please confirm!");
				e.preventDefault();
				var $this = $(this);
						
				var $response_div = $('#log_files_response');
				$.post( ajaxurl, {
						action: 'bbclms_delete_log_files'	
					},
					function(response) {
						if ( response.length > 0 && response != 'Failed' ) {
							//alert(response);							
							$response_div.html(response);
						}
					});			
			});
		});
	});
})( jQuery );

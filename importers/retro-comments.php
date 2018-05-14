<?php
if ( ! defined( 'MYCRED_RETRO_VERSION' ) ) exit;

/**
 * Tool: Comments
 * @since 1.0
 * @version 1.0
 */
if ( ! class_exists( 'myCRED_Retro_Comments_Tool' ) ) :
	class myCRED_Retro_Comments_Tool {

		const tool   = 'mycred_retro_comments';

		/**
		 * Register Tool 
		 * @since 1.0
		 * @version 1.0
		 */
		static function register() {

			register_importer(
				self::tool,
				sprintf( __( '%s Retroactive Comments', 'mycred_retro' ), mycred_label() ),
				__( 'Award or deduct points from your users for past comments. If you want to continue this process for future comments, make sure you enable the "Points for comments" hook.', 'mycred_retro' ),
				array( __CLASS__, 'render' )
			);

		}

		/**
		 * Header
		 * @since 1.0
		 * @version 1.0
		 */
		static function header() {

			$screen = get_current_screen();

			$screen->add_help_tab( array(
				'id'       => 'retro-comment',
				'title'    => __( 'Introduction', 'mycred_retro' ),
				'content'  => '
<h2>Retroactive Comments</h2>
<p>This tool allows you give your users points for comments retroactively. You can award or deduct points for approved, trashed or comments marked as spam.</p>
<p>To prevent to heavy queries, this tool will process <strong>' . MYCRED_RETRO_MAX . '</strong> comments at a time.</p>
<p>If you feel your site can handle more in one session, use the <code>MYCRED_RETRO_MAX</code> constant to change the threshold, by defining it in your wp-config.php file.</p>'
			) );
			$screen->add_help_tab( array(
				'id'       => 'retro-comment-eligible',
				'title'    => __( 'Eligible Comments', 'mycred_retro' ),
				'content'  => '<h2>Exclusions</h2><p>Comments that are posted by users you have set to be excluded, including if you selected to exclude "Point Editors" and/or "Points Administrators", will be ignored.</p>'
			) );

			$screen->add_help_tab( array(
				'id'       => 'retro-comment-amount',
				'title'    => __( 'Amount', 'mycred_retro' ),
				'content'  => '<h2>Point Amount</h2><p>You can give points to a user by providing a positive number (without a plus sign) or take points form a user by providing a negative number.</p>'
			) );
			$screen->add_help_tab( array(
				'id'       => 'retro-comment-log',
				'title'    => __( 'Log Entries', 'mycred_retro' ),
				'content'  => '<h2>Log Entries</h2><p>Saving a log entry for each point adjustments will allow you to reward users with badges / ranks and it also prevents users from gaining points twice for the same comment. But adding a log entry for each adjustment is optional. If you do not want to do this, simply make sure the log entry template is empty. If you do not add log entries, the users balance will be updated but there will be no record of how they got those points. These adjustments will not be seen by e.g. our hook limits or the badges add-on.</p><p>Supports <a href="http://codex.mycred.me/category/template-tags/temp-general/" target="_blank">General</a> and <a href="http://codex.mycred.me/category/template-tags/temp-comment/" target="_blank">Comment related</a> template tags.</p>'
			) );

			$screen->set_help_sidebar(
				'<p><strong>' . __( 'For more information:', 'mycred_retro' ) . '</strong></p>' .
				'<p><a href="https://mycred.me/" target="_blank">' . __( 'myCRED Website', 'mycred_retro' ) . '</a></p>' .
				'<p><a href="http://codex.mycred.me/" target="_blank">' . __( 'Documentation', 'mycred_retro' ) . '</a></p>'
			);

		}

		/**
		 * Render Tool 
		 * @since 1.0
		 * @version 1.0
		 */
		static function render() {

			global $wpdb, $mycred;

			$total        = 0;
			$total_all    = 0;
			$approved     = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 1 AND user_id != 0;" );
			if ( $approved === NULL ) $approved = 0;

			$total += $approved;

			$trashed      = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'trash' AND user_id != 0;" );
			if ( $trashed === NULL ) $trashed = 0;

			$total += $trashed;

			$spam         = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'spam' AND user_id != 0;" );
			if ( $spam === NULL ) $spam = 0;

			$total += $spam;

			$approved_all = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 1;" );
			if ( $approved_all === NULL ) $approved_all = 0;

			$total_all += $approved_all;

			$trashed_all  = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'trash';" );
			if ( $trashed_all === NULL ) $trashed_all = 0;

			$total_all += $trashed_all;

			$spam_all     = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'spam';" );
			if ( $spam_all === NULL ) $spam_all = 0;

			$total_all += $spam_all;

?>
<style type="text/css">
.wrap h1 { margin-bottom: 12px; }
form .form-control, form select { width: 100%; }
form p label { display: block; }
form table.widefat { margin-bottom: 24px; }
#import-action { text-align: right; }
td h5 { margin: 6px 0; line-height: 16px; font-size: 16px; }
#task-status { padding-top: 24px; }
#stop-tool-action { text-align: center; margin-bottom: 24px; }
.loading-indicator { height: 5px; width: 100%; position: relative; overflow: hidden; background-color: white; margin-bottom: 24px; }
.loading-indicator:before { display: block; position: absolute; content: ""; left: -200px; width: 200px; height: 5px; background-color: #c5d93d; animation: loading 2s linear infinite; }
@keyframes loading { from { left: -200px; width: 30%; } 25% { width: 50%; } 50% { width: 50%; } 70% { width: 50%; } 80% { left: 75%; } 95% { left: 100%; } to { left: 100%; } }
h1.task-completed { text-align: center; color: green; }
h1.task-failed { text-align: center; color: red; }
#progress-indicator { width: 100%; line-height: 64px; font-size: 18px; text-align: center; margin-bottom: 24px; }
#progress-indicator .border { border: 1px solid #ddd; padding: 12px; height: 64px }
#progress-indicator .border div { height: 64px; }
#progress-indicator .progress-bars { width: 100%; background-color: white; }
#progress-indicator #progress-bar { background-color: orange; color: white; }
#progress-indicator #progress-end.error { background-color: red !important; color: white; }
#progress-indicator #progress-end.success { background-color: green !important; color: white; }
</style>
<div class="wrap">
	<h1><?php _e( 'Retroactive Comments', 'mycred_retro' ); ?> <a href="<?php echo admin_url( 'import.php' ); ?>" class="page-title-action"><?php _e( 'All Tools', 'mycred_retro' ); ?></a></h1>
	<div id="message" class="info notice notice-info"><p><?php _e( 'Remember to disable this plugin once you are done using it!', 'mycred_retro' ); ?></p></div>
<?php

			if ( $total == 0 ) {

?>
	<div id="message" class="updated notice"><p><?php _e( 'No comments found.', 'mycred_retro' ); ?></p></div>
<?php

			}

			else {

?>
	<form id="import-upload-form" method="post" action="">
		<table class="wp-list-table widefat fixed striped users">
			<thead>
				<tr>
					<th style="width: 30%;"><?php _e( 'Comment Status', 'mycred_retro' ); ?></th>
					<th style="width: 20%;"><?php _e( 'Point Type', 'mycred_retro' ); ?></th>
					<th style="width: 15%;"><?php _e( 'Amount', 'mycred_retro' ); ?></th>
					<th style="width: 25%;"><?php _e( 'Log Entry Template', 'mycred_retro' ); ?></th>
					<th style="width: 10%;"><?php _e( 'Comments', 'mycred_retro' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>
						<select name="mycred_retro[comment_status]" id="select-comment-status" class="form-control">
							<option value="" data-template="" data-count="<?php echo $total; ?>" data-all="<?php echo $total_all; ?>"><?php _e( 'Select Comment Status', 'mycred_retro' ); ?></option>
							<option value="approved" data-template="%plural% for approved comment"<?php if ( $approved == 0 ) echo ' disabled="disabled"'; ?> data-count="<?php echo $approved; ?>" data-all="<?php echo $approved_all; ?>"><?php _e( 'Approved Comments', 'mycred_retro' ); ?></option>
							<option value="trash" data-template="%plural% for deleted comment"<?php if ( $trashed == 0 ) echo ' disabled="disabled"'; ?> data-count="<?php echo $trashed; ?>" data-all="<?php echo $trashed_all; ?>"><?php _e( 'Trashed Comments', 'mycred_retro' ); ?></option>
							<option value="spam" data-template="%plural% for SPAM comment"<?php if ( $spam == 0 ) echo ' disabled="disabled"'; ?> data-count="<?php echo $spam; ?>" data-all="<?php echo $spam_all; ?>"><?php _e( 'Marked as SPAM Comments', 'mycred_retro' ); ?></option>
						</select>
					</td>
					<td>
						<?php mycred_types_select_from_dropdown( 'mycred_retro[point_type]', 'comment-log-type' ); ?>
					</td>
					<td>
						<input type="text" name="mycred_retro[amount]" placeholder="<?php _e( 'amount', 'mycred_retro' ); ?>" id="comment-log-amount" class="form-control" value="" />
					</td>
					<td>
						<input type="text" name="mycred_retro[log_template]" id="comment-log-template" placeholder="<?php _e( 'no log entry', 'mycred_retro' ); ?>" class="form-control" value="" />
					</td>
					<td id="comment-count"><h5><?php echo $total; ?></h5></td>
				</tr>
			</tbody>
		</table>
		<p>
			<label for="comment-original-date"><input type="checkbox" name="mycred_retro[date]" id="comment-original-date" checked="checked" value="1" /> <?php _e( 'When adding a log entry, use the date the comment was originally added, and not the current date.', 'mycred_retro' ); ?></label>
			<label for="comment-original-visitors"><input type="checkbox" name="mycred_retro[visitor]" id="comment-original-visitors" value="1" /> <?php _e( 'Some comments were left by users while they were logged out. If the comment authors email matches a user on the website, credit that user for the comment.', 'mycred_retro' ); ?></label>
		</p>
		<p id="import-action" style="display: none;">
			<?php submit_button( __( 'Run Task', 'mycred_retro' ), 'primary', '', false ); ?>
		</p>
		<div id="task-status" style="display: none;">
			<div id="progress-indicator">
				<div class="border">
					<div id="progress-start" class="progress-bars" style="display: block;">0 %</div>
					<div id="progress-end" class="progress-bars" style="display: none;"></div>
					<div id="progress-bar" class="progress-bars" style="display: none; width: 0% !important;"></div>
				</div>
			</div>
			<div id="stop-tool-action">
				<button type="button" id="cancel-task" class="button button-secondary button-large"><?php _e( 'Stop Task', 'mycred_retro' ); ?></button>
			</div>
			<h3><?php _e( 'Task Report', 'mycred_retro' ); ?></h3>
			<table class="wp-list-table widefat fixed striped users" id="task-progress-table">
				<thead>
					<tr>
						<th style="width: 20%;"><?php _e( 'Eligible Comments', 'mycred_retro' ); ?></th>
						<th style="width: 20%;"><?php _e( 'Completed', 'mycred_retro' ); ?></th>
						<th style="width: 20%;"><?php _e( 'Excluded', 'mycred_retro' ); ?></th>
						<th style="width: 20%;"><?php _e( 'Missing User', 'mycred_retro' ); ?></th>
						<th style="width: 20%;"><?php _e( 'Remaining', 'mycred_retro' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td id="tak-report-total">0</td>
						<td id="tak-report-completed">0</td>
						<td id="tak-report-excluded">0</td>
						<td id="tak-report-missing">0</td>
						<td id="tak-report-remaining">0</td>
					</tr>
				</tbody>
			</table>
			<div id="completed-actions"></div>
		</div>
	</form>
<script type="text/javascript">
jQuery(function($){

	var run_all        = false;
	var task_completed = 0;
	var task_size      = 0;
	var run_task       = true;
	var comments       = { 'status' : '', 'type' : '', 'amount' : 0, 'log' : '', 'original_date' : true, 'visitors' : false };

	var run_this_task  = function( offset ) {

		if ( run_task === false ) return false;

		$.ajax({
			type : "POST",
			data : {
				action     : '<?php echo self::tool; ?>',
				_token     : '<?php echo wp_create_nonce( self::tool ); ?>',
				task       : comments,
				offset     : offset
			},
			dataType   : "JSON",
			url        : '<?php echo admin_url( 'admin-ajax.php' ); ?>',
			success    : function( response ) {

				if ( response.success === undefined ) {
					alert( 'Lost communication.' );
				}
				else {

					console.log( response.data );
					var processed   = parseInt( response.data.processed );

					task_completed += processed;
					task_size      -= processed;

					var completed_el = parseInt( $( '#tak-report-completed' ).text() );
					var completed    = parseInt( response.data.report.completed );
					$( '#tak-report-completed' ).empty().text( completed_el + completed );

					var excluded_el  = parseInt( $( '#tak-report-excluded' ).text() );
					var excluded     = parseInt( response.data.report.excluded );
					$( '#tak-report-excluded' ).empty().text( excluded_el + excluded );

					var missing_el   = parseInt( $( '#tak-report-missing' ).text() );
					var missing      = parseInt( response.data.report.missing );
					$( '#tak-report-missing' ).empty().text( missing_el + missing );

					var originaltotal = task_size + task_completed;
					var progress      = parseInt( ( task_completed / originaltotal ) * 100 );
					if ( progress > 100 ) progress = 100;

					$( '#progress-indicator #progress-bar' ).css({ 'width' : progress + '%' });

					if ( response.success && ! response.data.finished )
						run_this_task( task_completed );

					else {
					
						if ( response.success && response.data.finished ) {
							$( '#progress-indicator #progress-bar' ).hide();
							$( '#progress-indicator #progress-end' ).addClass( 'success' ).empty().text( 'Task completed' ).show();
							$( '#completed-actions' ).empty().html( response.data.actions );
						}

						else if ( ! response.success ) {
							$( '#progress-indicator #progress-bar' ).hide();
							$( '#progress-indicator #progress-end' ).addClass( 'error' ).empty().text( response.data ).show();
						}

						run_task = false;

						$( '#stop-tool-action' ).hide();

					}

				}

			}
		});

	};

	$(document).ready(function(){

		$( '#comment-original-visitors' ).click(function(){

			var selectoptions  = $( '#select-comment-status option' );
			var selectedoption = $( '#select-comment-status' ).find( ':selected' );

			if ( $(this).is( ':checked' ) ) {

				run_all = true;

				if ( selectedoption !== undefined ) {
					$( '#comment-count h5' ).empty().text( selectedoption.data( 'all' ) );
					$( '#tak-report-total' ).empty().text( selectedoption.data( 'all' ) );
				}
				else {
					$( '#comment-count h5' ).empty().text( "<?php echo $total_all; ?>" );
					$( '#tak-report-total' ).empty().text( "<?php echo $total_all; ?>" );
				}

			}
			else {

				run_all = false;

				if ( selectedoption !== undefined ) {
					$( '#comment-count h5' ).empty().text( selectedoption.data( 'count' ) );
					$( '#tak-report-total' ).empty().text( selectedoption.data( 'count' ) );
				}
				else {
					$( '#comment-count h5' ).empty().text( "<?php echo $total; ?>" );
					$( '#tak-report-total' ).empty().text( "<?php echo $total; ?>" );
				}

			}

		});

		$( '#select-comment-status' ).change(function(){

			var selectedstatus = $(this).find( ':selected' );
			if ( selectedstatus === undefined || selectedstatus.val() == '' ) {

				$( '#import-action' ).hide();
				return false;

			}

			$( '#comment-log-template' ).val( selectedstatus.data( 'template' ) );

			$( '#import-action' ).show();

			if ( run_all ) {

				$( '#comment-count h5' ).empty().text( selectedstatus.data( 'all' ) );
				$( '#tak-report-total' ).empty().text( selectedstatus.data( 'all' ) );

				task_size = parseInt( selectedstatus.data( 'all' ) );

			}
			else {

				$( '#comment-count h5' ).empty().text( selectedstatus.data( 'count' ) );
				$( '#tak-report-total' ).empty().text( selectedstatus.data( 'count' ) );

				task_size = parseInt( selectedstatus.data( 'count' ) );

			}
			console.log( 'Task size: ' + task_size );

		});

		$( '#import-upload-form' ).on( 'submit', function(e){

			if ( $( '#comment-log-amount' ).val() == '' ) {
				alert( 'You must enter an amount.' );
				return false;
			}

			e.preventDefault();

			$( '#import-action' ).hide();
			$( '#cancel-task' ).removeAttr( 'disabled' );
			

			$( '#select-comment-status' ).attr( 'disabled', 'disabled' );
			$( '#comment-log-type' ).attr( 'disabled', 'disabled' );
			$( '#comment-log-amount' ).attr( 'disabled', 'disabled' );
			$( '#comment-log-template' ).attr( 'disabled', 'disabled' );
			$( '#comment-original-date' ).attr( 'disabled', 'disabled' );
			$( '#comment-original-visitors' ).attr( 'disabled', 'disabled' );

			comments.status        = $( '#select-comment-status' ).find( ':selected' ).val();
			comments.type          = $( '#comment-log-type' ).find( ':selected' ).val();
			comments.amount        = $( '#comment-log-amount' ).val();
			comments.log           = $( '#comment-log-template' ).val();
			comments.original_date = $( '#comment-original-date' ).is( ':checked' );
			comments.visitors      = $( '#comment-original-visitors' ).is( ':checked' );

			run_task = true;

			$( '#task-status' ).show();
			$( '#progress-indicator #progress-end' ).removeClass( 'success error' ).hide();
			$( '#progress-indicator #progress-start' ).hide();
			$( '#progress-indicator #progress-bar' ).css( 'width', '0% !important' ).show();

			run_this_task( 0 );

		});

		$( '#cancel-task' ).click(function(){

			run_task = false;

			$(this).attr( 'disabled', 'disabled' ).text( 'Refresh page to start over.' );

			comments = { 'status' : '', 'type' : '', 'amount' : 0, 'log' : '', 'original_date' : false, 'visitors' : false };

		});

	});

});
</script>
<?php

			}

?>
</div>
<?php

		}

		/**
		 * AJAX Handler
		 * @since 1.0
		 * @version 1.0
		 */
		static function ajax_handler() {

			check_ajax_referer( self::tool, '_token' );

			$args = shortcode_atts( array(
				'status'        => '',
				'type'          => MYCRED_DEFAULT_TYPE_KEY,
				'amount'        => 0,
				'log'           => '',
				'original_date' => 'true',
				'visitors'      => 'false'
			), $_POST['task'] );

			$args['type']          = sanitize_key( $args['type'] );

			if ( ! mycred_point_type_exists( $args['type'] ) )
				wp_send_json_error( __( 'Selected point type does not exist. Please refresh this page and try again.', 'mycred_retro' ) );

			$mycred                = mycred( $args['type'] );

			$args['status']        = sanitize_text_field( $args['status'] );
			$args['amount']        = $mycred->number( $args['amount'] );

			if ( $args['amount'] == $mycred->zero() )
				wp_send_json_error( __( 'Amount can not be zero. Please refresh this page and try again.', 'mycred_retro' ) );

			$args['log']           = sanitize_text_field( $args['log'] );

			$now                   = current_time( 'timestamp' );
			$number                = absint( MYCRED_RETRO_MAX );
			$offset                = absint( $_POST['offset'] );

			$format                = '%s';
			if ( $mycred->format['decimals'] > 0 )
				$format = '%f';

			elseif ( $mycred->format['decimals'] == 0 )
				$format = '%d';

			$reference             = 'approved_comment';
			if ( $args['status'] == 'spam' )
				$reference = 'spam_comment';

			elseif ( $args['status'] == 'trash' )
				$reference = 'deleted_comment';

			else {
				$args['status'] = '1';
			}

			global $wpdb;

			if ( defined( 'MYCRED_LOG_TABLE' ) )
				$log_table = MYCRED_LOG_TABLE;

			else {

				if ( mycred_centralize_log() )
					$log_table = $wpdb->base_prefix . 'myCRED_log';
				else
					$log_table = $wpdb->prefix . 'myCRED_log';

			}

			$report                = array( 'completed' => 0, 'excluded' => 0, 'missing' => 0 );
			$processed             = 0;

			if ( $args['visitors'] === 'false' )
				$comments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->comments} WHERE user_id != 0 AND comment_approved = %s ORDER BY comment_ID ASC LIMIT %d,%d;", $args['status'], $offset, $number ) );

			else {
				$comments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->comments} WHERE comment_approved = %s ORDER BY comment_ID ASC LIMIT %d,%d;", $args['status'], $offset, $number ) );
			}

			if ( ! empty( $comments ) ) {
				foreach ( $comments as $comment ) {

					$user_id = absint( $comment->user_id );
					if ( $user_id === 0 && $args['visitors'] === 'false' ) {

						$report['missing']++;
						$processed++;

						continue;

					}

					if ( $user_id === 0 ) {
						$user = get_user_by( 'email', $comment->comment_author_email );
					}
					else {
						$user = get_userdata( $user_id );
					}

					if ( ! isset( $user->ID ) ) {

						$report['missing']++;
						$processed++;

						continue;

					}

					if ( $mycred->exclude_user( $user->ID ) ) {

						$report['excluded']++;
						$processed++;

						continue;

					}

					$report['completed']++;

					$mycred->update_users_balance( $user_id, $args['amount'], $args['type'] );

					if ( $args['log'] != '' ) {

						$time = ( $args['original_date'] === 'true' ) ? strtotime( $comment->comment_date_gmt, $now ) : $now;

						// Insert into DB
						$wpdb->insert(
							$log_table,
							array(
								'ref'     => $reference,
								'ref_id'  => $comment->comment_ID,
								'user_id' => $user_id,
								'creds'   => $args['amount'],
								'ctype'   => $args['type'],
								'time'    => $time,
								'entry'   => $args['log'],
								'data'    => serialize( array( 'ref_type' => 'comment' ) )
							),
							array( '%s', '%d', '%d', $format, '%s', '%d', '%s', '%s' )
						);

					}

					$processed++;

				}
			}

			$finished = false;
			$actions  = '';
			if ( $processed < $number ) {

				$finished  = true;
				$actions   = array();
				$admin_url = admin_url( 'admin.php' );

				$page      = MYCRED_SLUG;
				if ( $args['type'] != MYCRED_DEFAULT_TYPE_KEY )
					$page .= '_' . $args['type'];

				$actions[] = '<a href="' . add_query_arg( array( 'page' => $page, 'ref' => $reference ), $admin_url ) . '" class="button button-secondary">' . __( 'View Log Entries', 'mycred_retro' ) . '</a>';
				$actions[] = '<a href="' . add_query_arg( array( 'import' => self::tool ), $admin_url ) . '" class="button button-secondary">' . __( 'Reload Tool', 'mycred_retro' ) . '</a>';

				$actions = implode( ' ', $actions );

			}

			wp_send_json_success( array(
				'processed' => $processed,
				'finished'  => $finished,
				'actions'   => $actions,
				'report'    => $report
			) );

		}

	}
endif;

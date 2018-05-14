<?php
if ( ! defined( 'MYCRED_RETRO_VERSION' ) ) exit;

/**
 * Tool: Content
 * @since 1.0
 * @version 1.0
 */
if ( ! class_exists( 'myCRED_Retro_Content_Tool' ) ) :
	class myCRED_Retro_Content_Tool {

		const tool   = 'mycred_retro_content';

		/**
		 * Register Tool 
		 * @since 1.0
		 * @version 1.0
		 */
		static function register() {

			register_importer(
				self::tool,
				sprintf( __( '%s Retroactive Content', 'mycred_retro' ), mycred_label() ),
				__( 'Award or deduct points from your users for content they have published in the past. If you want to continue this process for future content, make sure you enable the "Points for publishing content" hook.', 'mycred_retro' ),
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
				'id'       => 'retro-content',
				'title'    => __( 'Introduction', 'mycred_retro' ),
				'content'  => '
<h2>Retroactive Content</h2>
<p>This tool allows you give post authors points for published content on your website.</p>
<p>To prevent to heavy queries, this tool will process <strong>' . MYCRED_RETRO_MAX . '</strong> posts at a time.</p>
<p>If you feel your site can handle more in one session, use the <code>MYCRED_RETRO_MAX</code> constant to change the threshold, by defining it in your wp-config.php file.</p>'
			) );
			$screen->add_help_tab( array(
				'id'       => 'retro-content-eligible',
				'title'    => __( 'Eligible Content', 'mycred_retro' ),
				'content'  => '<h2>Post Status</h2><p>Only posts that are "Published" or "Privately Published" qualify to be used with this tool.</p><p>Content published by authors that are excluded do not qualify for this tool.</p>'
			) );

			$screen->add_help_tab( array(
				'id'       => 'retro-content-amount',
				'title'    => __( 'Amount', 'mycred_retro' ),
				'content'  => '<h2>Point Amount</h2><p>You can give points to a user by providing a positive number (without a plus sign) or take points form a user by providing a negative number.</p>'
			) );
			$screen->add_help_tab( array(
				'id'       => 'retro-content-log',
				'title'    => __( 'Log Entries', 'mycred_retro' ),
				'content'  => '<h2>Log Entries</h2><p>Saving a log entry for each point adjustments will allow you to reward users with badges / ranks and it also prevents users from gaining points twice for the same published content. But adding a log entry for each adjustment is optional. If you do not want to do this, simply make sure the log entry template is empty. If you do not add log entries, the users balance will be updated but there will be no record of how they got those points. These adjustments will not be seen by e.g. our hook limits or the badges add-on.</p><p>Supports <a href="http://codex.mycred.me/category/template-tags/temp-general/" target="_blank">General</a> and <a href="http://codex.mycred.me/category/template-tags/temp-post/" target="_blank">Post related</a> template tags.</p>'
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

			$content = $wpdb->get_results( "SELECT post_type, COUNT(*) as total FROM {$wpdb->posts} WHERE ( post_status IN ( 'publish', 'private' ) AND post_type != 'nav_menu_item' ) OR ( post_type = 'attachment' ) GROUP BY post_type;" );

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
	<h1><?php _e( 'Retroactive Content', 'mycred_retro' ); ?> <a href="<?php echo admin_url( 'import.php' ); ?>" class="page-title-action"><?php _e( 'All Tools', 'mycred_retro' ); ?></a></h1>
	<div id="message" class="info notice notice-info"><p><?php _e( 'Remember to disable this plugin once you are done using it!', 'mycred_retro' ); ?></p></div>
<?php

			if ( empty( $content ) ) {

?>
	<div id="message" class="updated notice"><p><?php _e( 'No content found.', 'mycred_retro' ); ?></p></div>
<?php

			}

			else {

				$total = 0;
				foreach ( $content as $entry ) {
					$total += $entry->total;
				}

?>
	<form id="import-upload-form" method="post" action="">
		<table class="wp-list-table widefat fixed striped users">
			<thead>
				<tr>
					<th style="width: 30%;"><?php _e( 'Post Type', 'mycred_retro' ); ?></th>
					<th style="width: 20%;"><?php _e( 'Point Type', 'mycred_retro' ); ?></th>
					<th style="width: 15%;"><?php _e( 'Amount', 'mycred_retro' ); ?></th>
					<th style="width: 25%;"><?php _e( 'Log Entry Template', 'mycred_retro' ); ?></th>
					<th style="width: 10%;"><?php _e( 'Content', 'mycred_retro' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>
						<select name="mycred_retro[post_type]" id="select-content-type" class="form-control">
							<option value="" data-template="" data-count=""><?php _e( 'Select Post Type', 'mycred_retro' ); ?></option>
<?php

				foreach ( $content as $entry ) {

					if ( str_replace( array( 'mycred_', 'buycred_' ), '', $entry->post_type ) != $entry->post_type ) continue;

					$post_type = get_post_type_object( $entry->post_type );
					if ( $post_type === NULL ) continue;

					echo '<option value="' . $entry->post_type . '" data-template="%plural% for published %post_type%" data-count="' . $entry->total . '">' . $post_type->labels->name . '</option>';

				}

?>
						</select>
					</td>
					<td>
						<?php mycred_types_select_from_dropdown( 'mycred_retro[point_type]', 'content-log-type' ); ?>
					</td>
					<td>
						<input type="text" name="mycred_retro[amount]" placeholder="<?php _e( 'amount', 'mycred_retro' ); ?>" id="content-log-amount" class="form-control" value="" />
					</td>
					<td>
						<input type="text" name="mycred_retro[log_template]" id="content-log-template" placeholder="<?php _e( 'no log entry', 'mycred_retro' ); ?>" class="form-control" value="" />
					</td>
					<td id="content-count"><h5><?php echo $total; ?></h5></td>
				</tr>
			</tbody>
		</table>
		<p>
			<label for="content-original-date"><input type="checkbox" name="mycred_retro[date]" id="content-original-date" checked="checked" value="1" /> <?php _e( 'When adding a log entry, use the date the content was published, and not the current date.', 'mycred_retro' ); ?></label>
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
						<th style="width: 20%;"><?php _e( 'Eligible Content', 'mycred_retro' ); ?></th>
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
	var content        = { 'post_type' : '', 'type' : '', 'amount' : 0, 'log' : '', 'original_date' : true };

	var run_this_task  = function( offset ) {

		if ( run_task === false ) return false;

		$.ajax({
			type : "POST",
			data : {
				action     : '<?php echo self::tool; ?>',
				_token     : '<?php echo wp_create_nonce( self::tool ); ?>',
				task       : content,
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

		$( '#select-content-type' ).change(function(){

			var selectedstatus = $(this).find( ':selected' );
			if ( selectedstatus === undefined || selectedstatus.val() == '' ) {

				$( '#import-action' ).hide();
				return false;

			}

			$( '#content-log-template' ).val( selectedstatus.data( 'template' ) );

			$( '#import-action' ).show();

			$( '#content-count h5' ).empty().text( selectedstatus.data( 'count' ) );
			$( '#tak-report-total' ).empty().text( selectedstatus.data( 'count' ) );

			task_size = parseInt( selectedstatus.data( 'count' ) );
			console.log( 'Task size: ' + task_size );

		});

		$( '#import-upload-form' ).on( 'submit', function(e){

			if ( $( '#content-log-amount' ).val() == '' ) {
				alert( 'You must enter an amount.' );
				return false;
			}

			e.preventDefault();

			$( '#import-action' ).hide();
			$( '#cancel-task' ).removeAttr( 'disabled' );
			

			$( '#select-content-type' ).attr( 'disabled', 'disabled' );
			$( '#content-log-type' ).attr( 'disabled', 'disabled' );
			$( '#content-log-amount' ).attr( 'disabled', 'disabled' );
			$( '#content-log-template' ).attr( 'disabled', 'disabled' );
			$( '#content-original-date' ).attr( 'disabled', 'disabled' );

			content.post_type     = $( '#select-content-type' ).find( ':selected' ).val();
			content.type          = $( '#content-log-type' ).find( ':selected' ).val();
			content.amount        = $( '#content-log-amount' ).val();
			content.log           = $( '#content-log-template' ).val();
			content.original_date = $( '#content-original-date' ).is( ':checked' );

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

			content = { 'post_type' : '', 'type' : '', 'amount' : 0, 'log' : '', 'original_date' : false };

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
				'post_type'     => '',
				'type'          => MYCRED_DEFAULT_TYPE_KEY,
				'amount'        => 0,
				'log'           => '',
				'original_date' => 'true'
			), $_POST['task'] );

			$args['post_type']     = sanitize_key( $args['post_type'] );
			$args['type']          = sanitize_key( $args['type'] );

			if ( ! mycred_point_type_exists( $args['type'] ) )
				wp_send_json_error( __( 'Selected point type does not exist. Please refresh this page and try again.', 'mycred_retro' ) );

			$mycred                = mycred( $args['type'] );

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

			$post_status_filter    = "AND post_status IN ( 'publish', 'private' )";
			if ( $args['post_type'] == 'attachment' )
				$post_status_filter = '';

			$contents              = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE post_type = %s {$post_status_filter} ORDER BY ID ASC LIMIT %d,%d;", $args['post_type'], $offset, $number ) );

			if ( ! empty( $contents ) ) {
				foreach ( $contents as $content ) {

					$author_id = absint( $content->post_author );
					if ( $author_id === 0 ) {

						$report['missing']++;
						$processed++;

						continue;

					}

					$author = get_userdata( $author_id );

					if ( ! isset( $author->ID ) ) {

						$report['missing']++;
						$processed++;

						continue;

					}

					if ( $mycred->exclude_user( $author_id ) ) {

						$report['excluded']++;
						$processed++;

						continue;

					}

					$report['completed']++;

					$mycred->update_users_balance( $author_id, $args['amount'], $args['type'] );

					if ( $args['log'] != '' ) {

						$time = ( $args['original_date'] === 'true' ) ? strtotime( $content->post_date_gmt, $now ) : $now;

						// Insert into DB
						$wpdb->insert(
							$log_table,
							array(
								'ref'     => 'publishing_content',
								'ref_id'  => $content->ID,
								'user_id' => $author_id,
								'creds'   => $args['amount'],
								'ctype'   => $args['type'],
								'time'    => $time,
								'entry'   => $args['log'],
								'data'    => serialize( array( 'ref_type' => 'post' ) )
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

				$actions[] = '<a href="' . add_query_arg( array( 'page' => $page, 'ref' => 'publishing_content' ), $admin_url ) . '" class="button button-secondary">' . __( 'View Log Entries', 'mycred_retro' ) . '</a>';
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

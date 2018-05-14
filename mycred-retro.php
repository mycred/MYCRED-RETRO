<?php
/**
 * Plugin Name: myCRED Retro
 * Plugin URI: http://mycred.me
 * Description: Allows you to give out points retroactively for past events.
 * Version: 1.1
 * Tags: mycred, points, retroactive
 * Author: Gabriel S Merovingi
 * Author URI: http://www.merovingi.com
 * Author Email: support@mycred.me
 * Requires at least: WP 4.0
 * Tested up to: WP 4.6
 * Text Domain: mycred_retro
 * Domain Path: /lang
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
if ( ! class_exists( 'myCRED_Retro_Plugin' ) ) :
	final class myCRED_Retro_Plugin {

		// Plugin Version
		public $version             = '1.1';

		// Instnace
		protected static $_instance = NULL;

		// Current session
		public $session             = NULL;

		public $slug                = '';
		public $domain              = '';
		public $plugin              = NULL;
		public $plugin_name         = '';
		protected $update_url       = 'https://mycred.me/api/plugins/';
		public $built_in_tools      = array();

		/**
		 * Setup Instance
		 * @since 1.0
		 * @version 1.0
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Not allowed
		 * @since 1.0
		 * @version 1.0
		 */
		public function __clone() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0' ); }

		/**
		 * Not allowed
		 * @since 1.0
		 * @version 1.0
		 */
		public function __wakeup() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0' ); }

		/**
		 * Define
		 * @since 1.0
		 * @version 1.0
		 */
		private function define( $name, $value, $definable = true ) {
			if ( ! defined( $name ) )
				define( $name, $value );
		}

		/**
		 * Require File
		 * @since 1.0
		 * @version 1.0
		 */
		public function file( $required_file ) {
			if ( file_exists( $required_file ) )
				require_once $required_file;
		}

		/**
		 * Construct
		 * @since 1.0
		 * @version 1.0
		 */
		public function __construct() {

			$this->slug        = 'mycred-retro';
			$this->plugin      = plugin_basename( __FILE__ );
			$this->domain      = 'mycred_retro';
			$this->plugin_name = 'myCRED Retro';

			$this->built_in_tools = array(
				'mycred_retro_comments' => 'myCRED_Retro_Comments_Tool',
				'mycred_retro_content'  => 'myCRED_Retro_Content_Tool',
				'mycred_retro_users'    => 'myCRED_Retro_Users_Tool'
			);

			$this->define_constants();
			$this->includes();
			$this->plugin_updates();

			add_action( 'mycred_pre_init',            array( $this, 'setup_importers' ) );
			add_action( 'mycred_init',                array( $this, 'load_textdomain' ) );
			add_action( 'mycred_admin_init',          array( $this, 'admin_init' ) );

		}

		/**
		 * Define Constants
		 * @since 1.0
		 * @version 1.0
		 */
		public function define_constants() {

			$this->define( 'MYCRED_RETRO_VERSION',      $this->version );
			$this->define( 'MYCRED_RETRO_SLUG',         $this->slug );
			$this->define( 'MYCRED_DEFAULT_TYPE_KEY',   'mycred_default' );

			$this->define( 'MYCRED_RETRO_MAX',           150 );

			$this->define( 'MYCRED_RETRO',               __FILE__ );
			$this->define( 'MYCERD_RETRO_ROOT_DIR',      plugin_dir_path( MYCRED_RETRO ) );
			$this->define( 'MYCRED_RETRO_IMPORTERS_DIR', MYCERD_RETRO_ROOT_DIR . 'importers/' );

		}

		/**
		 * Includes
		 * @since 1.0
		 * @version 1.0
		 */
		public function includes() { }

		/**
		 * Load Textdomain
		 * @since 1.0
		 * @version 1.0
		 */
		public function load_textdomain() {

			// Load Translation
			$locale = apply_filters( 'plugin_locale', get_locale(), $this->domain );

			load_textdomain( $this->domain, WP_LANG_DIR . '/' . $this->slug . '/' . $this->domain . '-' . $locale . '.mo' );
			load_plugin_textdomain( $this->domain, false, dirname( $this->plugin ) . '/lang/' );

		}

		/**
		 * Setup Importers
		 * @since 1.0
		 * @version 1.0
		 */
		public function setup_importers() {

			$this->built_in_tools = apply_filters( 'mycred_retro_tools', $this->built_in_tools );

			$this->file( MYCRED_RETRO_IMPORTERS_DIR . 'retro-comments.php' );
			$this->file( MYCRED_RETRO_IMPORTERS_DIR . 'retro-content.php' );
			$this->file( MYCRED_RETRO_IMPORTERS_DIR . 'retro-users.php' );

			foreach ( $this->built_in_tools as $tool => $import_class ) {

				if ( ! class_exists( $import_class ) ) continue;

				add_action( 'load-importer-' . $tool,         array( $import_class, 'header' ) );
				add_action( 'mycred_retro_register_importer', array( $import_class, 'register' ) );

			}

		}

		/**
		 * Admin Init
		 * @since 1.0
		 * @version 1.0
		 */
		public function admin_init() {

			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				foreach ( $this->built_in_tools as $tool => $import_class ) {

					if ( ! class_exists( $import_class ) ) continue;

					add_action( 'wp_ajax_' . $tool, array( $import_class, 'ajax_handler' ) );

				}
			}

			if ( defined( 'WP_LOAD_IMPORTERS' ) ) {

				do_action( 'mycred_retro_register_importer' );

			}

		}

		/**
		 * Plugin Updates
		 * @since 1.0
		 * @version 1.0
		 */
		public function plugin_updates() {

			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_plugin_update' ), 380 );
			add_filter( 'plugins_api',                           array( $this, 'plugin_api_call' ), 380, 3 );
			add_filter( 'plugin_row_meta',                       array( $this, 'plugin_view_info' ), 380, 3 );

		}

		/**
		 * Plugin Update Check
		 * @since 1.0
		 * @version 1.0
		 */
		public function check_for_plugin_update( $checked_data ) {

			global $wp_version;

			if ( empty( $checked_data->checked ) )
				return $checked_data;

			$args = array(
				'slug'    => $this->slug,
				'version' => $this->version,
				'site'    => site_url()
			);
			$request_string = array(
				'body'       => array(
					'action'     => 'version', 
					'request'    => serialize( $args ),
					'api-key'    => md5( get_bloginfo( 'url' ) )
				),
				'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' )
			);

			// Start checking for an update
			$response = wp_remote_post( $this->update_url, $request_string );

			if ( ! is_wp_error( $response ) ) {

				$result = maybe_unserialize( $response['body'] );

				if ( is_object( $result ) && ! empty( $result ) )
					$checked_data->response[ $this->slug . '/' . $this->slug . '.php' ] = $result;

			}

			return $checked_data;

		}

		/**
		 * Plugin View Info
		 * @since 1.0
		 * @version 1.0
		 */
		public function plugin_view_info( $plugin_meta, $file, $plugin_data ) {

			if ( $file != $this->plugin ) return $plugin_meta;

			$plugin_meta[] = sprintf( '<a href="%s" class="thickbox" aria-label="%s" data-title="%s">%s</a>',
				esc_url( network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $this->slug .
				'&TB_iframe=true&width=600&height=550' ) ),
				esc_attr( __( 'More information about this plugin', $this->domain ) ),
				esc_attr( $this->plugin_name ),
				__( 'View details', $this->domain )
			);

			return $plugin_meta;

		}

		/**
		 * Plugin New Version Update
		 * @since 1.0
		 * @version 1.0
		 */
		public function plugin_api_call( $result, $action, $args ) {

			global $wp_version;

			if ( ! isset( $args->slug ) || ( $args->slug != $this->slug ) )
				return $result;

			// Get the current version
			$args = array(
				'slug'    => $this->slug,
				'version' => $this->version,
				'site'    => site_url()
			);
			$request_string = array(
				'body'       => array(
					'action'     => 'info', 
					'request'    => serialize( $args ),
					'api-key'    => md5( get_bloginfo( 'url' ) )
				),
				'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' )
			);

			$request = wp_remote_post( $this->update_url, $request_string );

			if ( ! is_wp_error( $request ) )
				$result = maybe_unserialize( $request['body'] );

			return $result;

		}

	}
endif;

function mycred_retroactive_plugin() {
	return myCRED_Retro_Plugin::instance();
}
mycred_retroactive_plugin();

<?php
/**
 * Dashboard Widgets Manager
 *
 * Manages and registers all AI Story Maker dashboard widgets
 * following WordPress best practices for dashboard widget integration.
 *
 * @package AI_Story_Maker
 * @author  Hayan Mamoun
 * @license GPLv2 or later
 * @since   0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AISTMA_Widgets_Manager
 * 
 * Central manager for all AI Story Maker dashboard widgets
 */
class AISTMA_Widgets_Manager {

	/**
	 * Initialize the widgets manager
	 */
	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'load_widgets' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_widget_options' ) );
		add_action( 'wp_ajax_aistma_toggle_widget', array( __CLASS__, 'handle_widget_toggle' ) );
	}

	/**
	 * Load all dashboard widgets
	 */
	public static function load_widgets() {
		// Only load widgets in admin area
		if ( ! is_admin() ) {
			return;
		}

		$widgets_path = AISTMA_PATH . 'admin/widgets/';
		
		// Load individual widget files
		$widgets = array(
			'data-cards-widget.php',
			'story-calendar-widget.php',
			'posts-activity-widget.php'
		);

		foreach ( $widgets as $widget_file ) {
			$widget_path = $widgets_path . $widget_file;
			if ( file_exists( $widget_path ) ) {
				require_once $widget_path;
			}
		}
	}

	/**
	 * Register widget options and settings
	 */
	public static function register_widget_options() {
		// Register options for widget preferences
		register_setting( 'aistma_widgets', 'aistma_widget_preferences', array(
			'sanitize_callback' => array( self::class, 'sanitize_widget_preferences' ),
		) );
	}

	/**
	 * Sanitize widget preferences
	 *
	 * @param array $input Input data.
	 * @return array Sanitized data.
	 */
	public static function sanitize_widget_preferences( $input ) {
		$sanitized = array();
		
		if ( is_array( $input ) ) {
			foreach ( $input as $key => $value ) {
				$sanitized_key = sanitize_text_field( $key );
				$sanitized_value = is_bool( $value ) ? (bool) $value : sanitize_text_field( $value );
				$sanitized[ $sanitized_key ] = $sanitized_value;
			}
		}
		
		return $sanitized;
	}

	/**
	 * Get widget preferences
	 */
	public static function get_widget_preferences() {
		$defaults = array(
			'data_cards_enabled' => true,
			'story_calendar_enabled' => true,
			'posts_activity_enabled' => true,
			'widget_refresh_interval' => 300, // 5 minutes
		);

		return wp_parse_args( get_option( 'aistma_widget_preferences', array() ), $defaults );
	}

	/**
	 * Handle AJAX widget toggle
	 */
	public static function handle_widget_toggle() {
		// Verify nonce and capabilities
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'aistma_widget_toggle' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Security check failed', 'ai-story-maker' ) );
		}

		$widget_id = isset( $_POST['widget_id'] ) ? sanitize_text_field( wp_unslash( $_POST['widget_id'] ) ) : '';
		$enabled = isset( $_POST['enabled'] ) ? (bool) $_POST['enabled'] : false;
		
		$preferences = self::get_widget_preferences();
		$preferences[ $widget_id . '_enabled' ] = $enabled;
		
		update_option( 'aistma_widget_preferences', $preferences );
		
		wp_send_json_success( array(
			'message' => __( 'Widget preference updated', 'ai-story-maker' ),
			'widget_id' => $widget_id,
			'enabled' => $enabled
		) );
	}

	/**
	 * Get widget configuration for admin interface
	 */
	public static function get_widget_config() {
		return array(
			'data_cards' => array(
				'title' => __( 'Data Overview Cards', 'ai-story-maker' ),
				'description' => __( 'Key statistics and metrics in card format', 'ai-story-maker' ),
				'class' => 'AISTMA_Data_Cards_Widget',
				'widget_id' => 'aistma_data_cards_widget'
			),
			'story_calendar' => array(
				'title' => __( 'Story Generation Calendar', 'ai-story-maker' ),
				'description' => __( '6-month heatmap of story generation activity', 'ai-story-maker' ),
				'class' => 'AISTMA_Story_Calendar_Widget',
				'widget_id' => 'aistma_story_calendar_widget'
			),
			'posts_activity' => array(
				'title' => __( 'Recent Posts Activity', 'ai-story-maker' ),
				'description' => __( '14-day activity heatmap for recent posts', 'ai-story-maker' ),
				'class' => 'AISTMA_Posts_Activity_Widget',
				'widget_id' => 'aistma_posts_activity_widget'
			)
		);
	}

	/**
	 * Render widget management interface
	 */
	public static function render_widget_management() {
		$preferences = self::get_widget_preferences();
		$widgets = self::get_widget_config();
		?>
		<div class="aistma-widget-management">
			<h3><?php esc_html_e( 'Dashboard Widgets', 'ai-story-maker' ); ?></h3>
			<p><?php esc_html_e( 'Manage which AI Story Maker widgets appear on your WordPress dashboard.', 'ai-story-maker' ); ?></p>
			
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Widget', 'ai-story-maker' ); ?></th>
						<th><?php esc_html_e( 'Description', 'ai-story-maker' ); ?></th>
						<th><?php esc_html_e( 'Enabled', 'ai-story-maker' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $widgets as $widget_key => $widget_config ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $widget_config['title'] ); ?></strong></td>
						<td><?php echo esc_html( $widget_config['description'] ); ?></td>
						<td>
							<label class="switch">
								<input type="checkbox" 
									   name="aistma_widget_<?php echo esc_attr( $widget_key ); ?>" 
									   value="1" 
									   <?php checked( $preferences[ $widget_key . '_enabled' ] ); ?>
									   data-widget-id="<?php echo esc_attr( $widget_key ); ?>">
								<span class="slider round"></span>
							</label>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<div class="aistma-widget-settings">
				<h4><?php esc_html_e( 'Widget Settings', 'ai-story-maker' ); ?></h4>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="widget_refresh_interval"><?php esc_html_e( 'Refresh Interval', 'ai-story-maker' ); ?></label>
						</th>
						<td>
							<select name="widget_refresh_interval" id="widget_refresh_interval">
								<option value="60" <?php selected( $preferences['widget_refresh_interval'], 60 ); ?>><?php esc_html_e( '1 minute', 'ai-story-maker' ); ?></option>
								<option value="300" <?php selected( $preferences['widget_refresh_interval'], 300 ); ?>><?php esc_html_e( '5 minutes', 'ai-story-maker' ); ?></option>
								<option value="600" <?php selected( $preferences['widget_refresh_interval'], 600 ); ?>><?php esc_html_e( '10 minutes', 'ai-story-maker' ); ?></option>
								<option value="1800" <?php selected( $preferences['widget_refresh_interval'], 1800 ); ?>><?php esc_html_e( '30 minutes', 'ai-story-maker' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'How often widget data should be refreshed.', 'ai-story-maker' ); ?></p>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<style>
		.aistma-widget-management .switch {
			position: relative;
			display: inline-block;
			width: 40px;
			height: 20px;
		}
		.aistma-widget-management .switch input {
			opacity: 0;
			width: 0;
			height: 0;
		}
		.aistma-widget-management .slider {
			position: absolute;
			cursor: pointer;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background-color: #ccc;
			transition: .4s;
		}
		.aistma-widget-management .slider:before {
			position: absolute;
			content: "";
			height: 16px;
			width: 16px;
			left: 2px;
			bottom: 2px;
			background-color: white;
			transition: .4s;
		}
		.aistma-widget-management input:checked + .slider {
			background-color: #0073aa;
		}
		.aistma-widget-management input:checked + .slider:before {
			transform: translateX(20px);
		}
		.aistma-widget-management .slider.round {
			border-radius: 20px;
		}
		.aistma-widget-management .slider.round:before {
			border-radius: 50%;
		}
		.aistma-widget-settings {
			margin-top: 20px;
			padding-top: 20px;
			border-top: 1px solid #ddd;
		}
		</style>

		<script>
		document.addEventListener('DOMContentLoaded', function() {
			const toggles = document.querySelectorAll('.aistma-widget-management input[type="checkbox"]');
			toggles.forEach(toggle => {
				toggle.addEventListener('change', function() {
					const widgetId = this.dataset.widgetId;
					const enabled = this.checked;
					
					// Future: Add AJAX call to save preference
					console.log('Widget ' + widgetId + ' ' + (enabled ? 'enabled' : 'disabled'));
					
					// You can add AJAX functionality here later to save preferences
				});
			});
		});
		</script>
		<?php
	}
}

// Initialize the widgets manager
AISTMA_Widgets_Manager::init();

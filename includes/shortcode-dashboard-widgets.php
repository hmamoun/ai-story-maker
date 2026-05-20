<?php
/**
 * Shortcodes that expose AI Story Maker admin dashboard widgets on the frontend.
 *
 * Provides three shortcodes mirroring the admin dashboard widgets:
 *   [aistma_data_overview]
 *   [aistma_generation_calendar]
 *   [aistma_recent_activity]
 *
 * Each accepts a viewable_by attribute (public | logged_in | admin).
 * Widget styles and scripts are emitted inline on first shortcode render so
 * the widgets are styled even though their CSS would otherwise only load on
 * the WP admin dashboard.
 *
 * @package AI_Story_Maker
 * @author  Hayan Mamoun
 * @license GPLv2 or later
 * @since   2.3.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AISTMA_Dashboard_Shortcodes {

	private static $assets_printed_for = array();

	public static function init() {
		add_shortcode( 'aistma_data_overview',       array( __CLASS__, 'data_overview' ) );
		add_shortcode( 'aistma_generation_calendar', array( __CLASS__, 'generation_calendar' ) );
		add_shortcode( 'aistma_recent_activity',     array( __CLASS__, 'recent_activity' ) );
	}

	public static function data_overview( $atts ) {
		return self::render( 'AISTMA_Data_Cards_Widget', $atts );
	}

	public static function generation_calendar( $atts ) {
		return self::render( 'AISTMA_Story_Calendar_Widget', $atts );
	}

	public static function recent_activity( $atts ) {
		return self::render( 'AISTMA_Posts_Activity_Widget', $atts );
	}

	private static function render( $widget_class, $atts ) {
		$atts = shortcode_atts(
			array(
				'viewable_by' => 'public',
			),
			$atts,
			'aistma_dashboard_widget'
		);

		if ( ! self::user_can_view( $atts['viewable_by'] ) ) {
			return '';
		}

		if ( class_exists( 'AISTMA_Widgets_Manager' ) ) {
			AISTMA_Widgets_Manager::load_widget_classes();
		}

		if ( ! class_exists( $widget_class ) || ! method_exists( $widget_class, 'render_widget' ) ) {
			return '';
		}

		ob_start();
		self::maybe_print_assets( $widget_class );
		echo '<div class="aistma-shortcode-wrapper">';
		call_user_func( array( $widget_class, 'render_widget' ) );
		echo '</div>';
		return ob_get_clean();
	}

	private static function maybe_print_assets( $widget_class ) {
		if ( isset( self::$assets_printed_for[ $widget_class ] ) ) {
			return;
		}
		self::$assets_printed_for[ $widget_class ] = true;

		if ( method_exists( $widget_class, 'get_widget_styles' ) ) {
			$css = call_user_func( array( $widget_class, 'get_widget_styles' ) );
			if ( ! empty( $css ) ) {
				echo '<style>' . $css . '</style>';
			}
		}
		if ( method_exists( $widget_class, 'get_widget_scripts' ) ) {
			$js = call_user_func( array( $widget_class, 'get_widget_scripts' ) );
			if ( ! empty( $js ) ) {
				echo '<script>' . $js . '</script>';
			}
		}
	}

	private static function user_can_view( $viewable_by ) {
		switch ( $viewable_by ) {
			case 'admin':
				return current_user_can( 'manage_options' );
			case 'logged_in':
				return is_user_logged_in();
			case 'public':
			default:
				return true;
		}
	}
}

AISTMA_Dashboard_Shortcodes::init();

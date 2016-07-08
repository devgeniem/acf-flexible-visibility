<?php
/*
Plugin Name: Advanced Custom Fields: Flexible Visibility
Plugin URI: https://github.com/devgeniem/acf-flexible-visibility
Description: Limit flexible content layouts not to show on certain page templates.
Version: 0.0.1
Author: Miika Arponen / Geniem
Author URI: https://github.com/devgeniem
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

class ACF_Flexible_Visibility {
	private $css = [];

	/*
	 * Hook actions and filters in place
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_action( "current_screen", array( $this, "init" ) );
		}

		add_action( "acf/render_field", array( $this, "get_hidden_layouts" ) );

		add_action( "acf/input/admin_footer", array( $this, "print_css" ) );

		add_action( "wp_ajax_acf_fv_get_visibility_value", array( $this, "get_visibility_value" ) );
	}

	public function init( $screen ) {
		if ( "post" == $screen->base && "acf-field-group" == $screen->id ) {
			add_action( "admin_enqueue_scripts", array( $this, "enqueue_scripts_edit_group" ) );
		}
	}

	public function enqueue_scripts_edit_group() {

		wp_register_script( "flexible-visibility", plugin_dir_url( __FILE__ ) . "js/field-visibility.js", ["jquery"], "0.0.1", true );

		// Register strings to translate
		$translations = array(
			"page_templates_to_ignore" => __( "Page templates to ignore", "acf-flexible-visibility" ),
			"choose_template" => __( "Choose a template", "acf-flexible-visibility" )
		);

		wp_localize_script( "flexible-visibility", "acf_flexible_visibility", $translations );

		wp_enqueue_script( "flexible-visibility" );
	}

	public function get_visibility_value() {
		$return = (object)[
			"templates" => get_page_templates()
		];

		if ( isset( $_REQUEST["layout_id"] ) && ctype_xdigit( $_REQUEST["layout_id"] ) && isset( $_REQUEST["id"] ) && is_numeric( $_REQUEST["id"] ) ) {
			$field_id = $_REQUEST["layout_id"];
			$post_id = $_REQUEST["id"];
		}
		else {
			wp_send_json_success( $return );
		}

		$post = get_post( $post_id );

		$post_obj = unserialize( $post->post_content );

		$found = false;

		foreach ( $post_obj["layouts"] as $obj ) {
			if ( $obj["key"] == $field_id ) {
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			wp_send_json_success( $return );
		}

		if ( is_array( $obj ) && isset( $obj["visibility"] ) ) {
			if ( ! empty( $obj["visibility"] ) ) {
				$return->visibility = explode( ",", $obj["visibility"] );

				wp_send_json_success( $return );
			}
			else {
				wp_send_json_success( $return );
			}
		}
		else {
			wp_send_json_success( $return );
		}
	}

	public function get_hidden_layouts( $field ) {
		global $wpdb, $post;

		if ( ! isset( $field["parent_layout"] ) ) {
			return;
		}

		if ( is_object( $post ) && isset( $post->ID ) ) {
			$post_id = $post->ID;
		}
		else {
			return;
		}

		$template = get_page_template_slug( $post_id );

		$content = $wpdb->get_var( $wpdb->prepare( "SELECT post_content FROM $wpdb->posts WHERE post_name = %s", $field["parent"] ));

		$content = unserialize( $content );

		$hides = array();

		if ( is_array( $content ) && isset( $content["layouts" ] ) ) {
			foreach ( $content["layouts"] as $layout ) {
				if ( ! isset( $layout["visibility"] ) ) {
					continue;
				}

				$visibility = array_filter( explode( " ", str_replace( ",", " ", $layout["visibility"] ) ) );  

				if ( in_array( $template, $visibility ) ) {
					$hides[ $layout["name"] ] = $layout["visibility"];
				}
			}
		}

		foreach ( $hides as $key => $hide ) {
			$this->css[] = "div.acf-fc-popup a[data-layout='". $key ."'] { display: none; }\n";
		}
	}

	public function print_css() {
		$this->css = array_unique( $this->css );

		if ( count( $this->css ) > 0 ) {
			echo "<style>\n";
			foreach ( $this->css as $css ) {
				echo $css;
			}
			echo "</style>\n";
		}
	}
}

new ACF_Flexible_Visibility();
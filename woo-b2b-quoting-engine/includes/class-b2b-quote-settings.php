<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class B2B_Quote_Settings {

	public function __construct() {
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
		add_action( 'woocommerce_settings_tabs_b2b_quoting', array( $this, 'settings_tab' ) );
		add_action( 'woocommerce_update_options_b2b_quoting', array( $this, 'update_settings' ) );
		
		// Output dynamic CSS based on settings
		add_action( 'wp_head', array( $this, 'output_dynamic_frontend_css' ) );
		add_action( 'admin_head', array( $this, 'output_dynamic_admin_css' ) );
	}

	public function add_settings_tab( $settings_tabs ) {
		$settings_tabs['b2b_quoting'] = __( 'B2B Quoting', 'woo-b2b-quote' );
		return $settings_tabs;
	}

	public function settings_tab() {
		woocommerce_admin_fields( $this->get_settings() );
	}

	public function update_settings() {
		woocommerce_update_options( $this->get_settings() );
	}

	public function get_settings() {
		$settings = array(
			'section_title' => array(
				'name'     => __( 'B2B Quoting Settings', 'woo-b2b-quote' ),
				'type'     => 'title',
				'desc'     => '',
				'id'       => 'b2b_quote_settings_section_title'
			),
			'master_switch' => array(
				'name' => __( 'Enable Quoting Globally', 'woo-b2b-quote' ),
				'type' => 'checkbox',
				'desc' => __( 'If checked, all products will have the Add to Quote button.', 'woo-b2b-quote' ),
				'id'   => 'b2b_quote_master_switch'
			),
			'target_categories' => array(
				'name'    => __( 'Target Categories', 'woo-b2b-quote' ),
				'type'    => 'multiselect',
				'desc'    => __( 'Select specific categories to enable quoting. Ignored if global switch is on.', 'woo-b2b-quote' ),
				'id'      => 'b2b_quote_categories',
				'options' => $this->get_product_categories(),
				'class'   => 'wc-enhanced-select'
			),
			'target_products' => array(
				'name'        => __( 'Specific Target Products', 'woo-b2b-quote' ),
				'type'        => 'multiselect',
				'desc'        => __( 'Search and select specific products. Ignored if global switch or category covers it.', 'woo-b2b-quote' ),
				'id'          => 'b2b_quote_products',
				'options'     => $this->get_saved_products_options(),
				'class'       => 'wc-product-search',
				'custom_attributes' => array(
					'data-multiple' => 'true',
					'data-action'   => 'woocommerce_json_search_products_and_variations',
				)
			),
			'brand_color_primary' => array(
				'name'    => __( 'Primary Button Color', 'woo-b2b-quote' ),
				'type'    => 'color',
				'desc'    => __( 'Used for the main action buttons.', 'woo-b2b-quote' ),
				'id'      => 'b2b_quote_color_primary',
				'default' => '#007cba'
			),
			'brand_color_secondary' => array(
				'name'    => __( 'Secondary/Hover Color', 'woo-b2b-quote' ),
				'type'    => 'color',
				'desc'    => __( 'Used for hover states.', 'woo-b2b-quote' ),
				'id'      => 'b2b_quote_color_secondary',
				'default' => '#005a8c'
			),
			'section_end' => array(
				'type' => 'sectionend',
				'id' => 'b2b_quote_settings_section_end'
			)
		);
		return apply_filters( 'b2b_quoting_settings', $settings );
	}

	private function get_product_categories() {
		$categories = get_terms( array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
		) );
		$options = array();
		if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
			foreach ( $categories as $category ) {
				$options[ $category->term_id ] = $category->name;
			}
		}
		return $options;
	}

	private function get_saved_products_options() {
		$saved = get_option( 'b2b_quote_products', array() );
		$options = array();
		if ( ! empty( $saved ) && is_array( $saved ) ) {
			foreach ( $saved as $product_id ) {
				$product = wc_get_product( $product_id );
				if ( $product ) {
					$options[ $product_id ] = wp_kses_post( $product->get_formatted_name() );
				}
			}
		}
		return $options;
	}
	
	public function output_dynamic_frontend_css() {
		$primary = get_option( 'b2b_quote_color_primary', '#007cba' );
		$secondary = get_option( 'b2b_quote_color_secondary', '#005a8c' );
		echo "<style>
			:root {
				--b2b-quote-primary: {$primary};
				--b2b-quote-secondary: {$secondary};
			}
			.b2b-add-to-quote, .b2b-submit-quote-btn {
				background-color: var(--b2b-quote-primary) !important;
				color: #ffffff !important;
				border-color: var(--b2b-quote-primary) !important;
			}
			.b2b-add-to-quote:hover, .b2b-submit-quote-btn:hover {
				background-color: var(--b2b-quote-secondary) !important;
				border-color: var(--b2b-quote-secondary) !important;
			}
		</style>";
	}

	public function output_dynamic_admin_css() {
		$primary = get_option( 'b2b_quote_color_primary', '#007cba' );
		echo "<style>
			:root {
				--b2b-quote-admin-primary: {$primary};
			}
			.b2b-quote-single-view h2 {
				color: var(--b2b-quote-admin-primary) !important;
				border-bottom: 2px solid var(--b2b-quote-admin-primary) !important;
				padding-bottom: 5px;
			}
		</style>";
	}
}

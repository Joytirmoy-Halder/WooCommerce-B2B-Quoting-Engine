<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class B2B_Quote_Frontend {

	public function __construct() {
		// Hook before the add to cart form starts so we catch Elementor and Quick Views
		add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'hijack_add_to_cart_hooks' ) );
		
		// Loop buttons (archive/shop page) override
		add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'modify_loop_add_to_cart_button' ), 10, 3 );
		
		// Render Floating Quote Cart Badge globally
		add_action( 'wp_footer', array( $this, 'render_floating_cart_widget' ) );
		
		// Enqueue frontend styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
	}

	public function enqueue_frontend_scripts() {
		wp_enqueue_style( 'b2b-quote-frontend', B2B_QUOTE_PLUGIN_URL . 'assets/css/b2b-quote-frontend.css', array(), B2B_QUOTE_VERSION );
	}

	public function render_floating_cart_widget() {
		if ( is_admin() || is_checkout() || is_cart() ) return;
		
		// ALWAYS render visually hidden, JS will control visibility by ajax fetch to bypass hostinger cache
		$page_id = get_option( 'b2b_quote_page_id' );
		$cart_url = $page_id ? get_permalink( $page_id ) : '#';

		echo '<a href="' . esc_url( $cart_url ) . '" id="b2b-floating-cart" style="display: none; align-items: center; justify-content: center; position: fixed; bottom: 30px; right: 30px; background-color: var(--b2b-quote-primary, #007cba); color: #fff; padding: 12px 20px; border-radius: 50px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); z-index: 99999; text-decoration: none; font-weight: bold; transition: all 0.3s ease;">';
		echo '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" style="margin-right:8px;"><path d="M0 2.5A.5.5 0 0 1 .5 2H2a.5.5 0 0 1 .485.379L2.89 4H14.5a.5.5 0 0 1 .485.621l-1.5 6A.5.5 0 0 1 13 11H4a.5.5 0 0 1-.485-.379L1.61 3H.5a.5.5 0 0 1-.5-.5zM3.14 5l1.25 5h8.22l1.25-5H3.14zM5 13a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm-2 1a2 2 0 1 1 4 0 2 2 0 0 1-4 0zm9-1a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm-2 1a2 2 0 1 1 4 0 2 2 0 0 1-4 0z"/></svg>';
		echo 'Quote Request Cart <span class="b2b-cart-count" style="background:#fff; color:var(--b2b-quote-primary, #007cba); margin-left:10px; border-radius:50%; width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; font-size:12px;">0</span>';
		echo '</a>';
	}

	/**
	 * Determine if a product is eligible for quoting.
	 * Stub for Phase 4 hierarchical logic.
	 */
	private function is_product_quoteable( $product ) {
		if ( get_option( 'b2b_quote_master_switch' ) === 'yes' ) {
			return true;
		}

		$categories = get_option( 'b2b_quote_categories', array() );
		if ( ! empty( $categories ) && ! is_array( $categories ) ) {
			$categories = array_map( 'trim', explode( ',', (string) $categories ) );
		}
		if ( ! empty( $categories ) && is_array( $categories ) ) {
			$product_cats = wc_get_product_term_ids( $product->get_id(), 'product_cat' );
			if ( count( array_intersect( $categories, $product_cats ) ) > 0 ) {
				return true;
			}
		}

		$products = get_option( 'b2b_quote_products', array() );
		if ( ! empty( $products ) && ! is_array( $products ) ) {
			$products = array_map( 'trim', explode( ',', (string) $products ) );
		}
		if ( ! empty( $products ) && is_array( $products ) && in_array( (string) $product->get_id(), $products ) ) {
			return true;
		}
		
		return false; 
	}

	public function hijack_add_to_cart_hooks() {
		global $product;
		
		if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		if ( $this->is_product_quoteable( $product ) ) {
			// Inject our button right next to the native add to cart button and hide the native ones.
			add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'render_quote_button_inside_form' ) );
		}
	}

	public function render_quote_button_inside_form() {
		global $product;
		
		// Output the Add to Quote UI
		echo '<button type="button" class="button alt b2b-add-to-quote" data-product_id="' . esc_attr( $product->get_id() ) . '" style="margin-left: 10px;">';
		echo esc_html__( 'Add to Quote Request', 'woo-b2b-quote' );
		echo '</button>';
		
		// Dynamically hide native buttons (Cart, Buy Now, Klarna, etc)
		echo '<style>
			form.cart .single_add_to_cart_button, 
			form.cart .wd-buy-now-btn,
			form.cart .klarna-express-button,
			form.cart .stripe-button-el,
			.klarna-pay-button,
			#klarna-checkout-button { 
				display: none !important; 
			}
			/* Elementor Hyper-Specificity Nuke */
			html body .elementor-widget-container form.cart div.quantity.quantity.quantity,
			html body form.cart div.quantity.quantity.quantity,
			.summary .quantity.quantity.quantity,
			.wd-single-add-cart .quantity.quantity.quantity,
			.wd-entry-summary .quantity.quantity.quantity,
			.qty-wrapper { 
				display: none !important;
				opacity: 0 !important;
				visibility: hidden !important;
				height: 0 !important;
				overflow: hidden !important;
			}
			.b2b-add-to-quote { width: 100%; margin-left: 0 !important; margin-top: 10px; }
		</style>';
	}
	
	public function modify_loop_add_to_cart_button( $link, $product, $args ) {
		if ( $this->is_product_quoteable( $product ) ) {
			$class = isset( $args['class'] ) ? esc_attr( $args['class'] ) : 'button';
			// Replace "Add to Cart" with custom "Add to Quote Request" HTML
			$link = sprintf( 
				'<a href="#" data-product_id="%s" class="%s b2b-add-to-quote">%s</a>',
				esc_attr( $product->get_id() ),
				esc_attr( $class ),
				esc_html__( 'Add to Quote Request', 'woo-b2b-quote' )
			);
		}
		return $link;
	}
}

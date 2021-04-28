<?php

if ( ! function_exists( 'wishlist_enqueue_scripts' ) ) {
	function wishlist_enqueue_scripts() {
		wp_enqueue_style( 'snoopy-wishlist', get_template_directory_uri() . '/inc/wishlist/public/css/wishlist.css', array(), _SNOOPY_VERSION );
		wp_enqueue_script( 'snoopy-wishlist', get_template_directory_uri() . '/inc/wishlist/public/js/wishlist.js', array( 'jquery' ), _SNOOPY_VERSION, true );
		wp_localize_script(
			'snoopy-wishlist',
			'snoopy_wishlist_params',
			array(
				'ajax_nonce'      => wp_create_nonce( 'ajax-nonce' ),
				'shop_name'       => sanitize_title_with_dashes( get_bloginfo( 'name' ) ),
				'add_wishlist'    => esc_html__( 'Add to wishlist', 'snoopy' ),
				'remove_wishlist' => esc_html__( 'Remove from wishlist', 'snoopy' ),
				'in_wishlist'     => esc_html__( 'The item added to the wishlist.', 'snoopy' ),
				'out_wishlist'    => esc_html__( 'The item removed from the wishlist.', 'snoopy' ),
				'no_wishlist'     => esc_html__( 'No wishlist found', 'snoopy' ),
				'error_wishlist'  => esc_html__( 'Something went wrong, could not update the wishlist.', 'snoopy' ),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'wishlist_enqueue_scripts' );


if ( ! function_exists( 'wishlist_button' ) ) {
	function wishlist_button() {
		global $product;

		$product_id            = $product->get_id();
		$wishlist_current_user = wishlist_current_user();
		$wishlist              = explode( ',', $wishlist_current_user['wishlist'] );
		$class                 = array(
			'btn'  => 'wishlist-toggle add-to-wishlist',
			'icon' => 'far fa-heart'
		);
		$title                 = __( 'Add to wishlist', 'snoopy' );

		if ( in_array( $product_id, $wishlist ) ) {
			$class = array(
				'btn'  => 'wishlist-toggle remove-from-wishlist',
				'icon' => 'fas fa-heart'
			);
			$title = __( 'Remove from wishlist', 'snoopy' );
		}

		echo '<button class="' . esc_attr( $class['btn'] ) . '" data-product-id="' . esc_attr( $product_id ) . '" title="' . esc_attr( $title ) . '"><i class="' . esc_attr( $class['icon'] ) . '"></i></button>';
	}
}
add_action( 'woocommerce_before_shop_loop_item', 'wishlist_button' );
add_action( 'woocommerce_single_product_summary', 'wishlist_button' );


if ( ! function_exists( 'wishlist_current_user' ) ) {
	function wishlist_current_user() {
		$wishlist_current_user = array( 'wishlist' => '' );
		if ( is_user_logged_in() ) {
			$current_user          = wp_get_current_user();
			$current_user_wishlist = get_user_meta( $current_user->ID, 'wishlist', true );
			$wishlist_current_user = array(
				'user_id'  => $current_user->ID,
				'wishlist' => $current_user_wishlist
			);
		}

		return $wishlist_current_user;
	}
}


if ( ! function_exists( 'wishlist_fetch_user_data' ) ) {
	function wishlist_fetch_user_data() {
		check_ajax_referer( 'ajax-nonce' );

		if ( wishlist_current_user() ) {
			echo json_encode( wishlist_current_user() );
		}
		die();
	}
}
if ( wp_doing_ajax() ) {
	add_action( 'wp_ajax_wishlist_fetch_user_data', 'wishlist_fetch_user_data' );
	add_action( 'wp_ajax_nopriv_wishlist_fetch_user_data', 'wishlist_fetch_user_data' );
}


if ( ! function_exists( 'wishlist_update_user_data' ) ) {
	function wishlist_update_user_data() {
		check_ajax_referer( 'ajax-nonce' );

		if ( isset( $_POST['user_id'] ) && ! empty( $_POST['user_id'] ) ) {
			$user_id  = $_POST['user_id'];
			$user_obj = get_user_by( 'id', $user_id );
			if ( ! is_wp_error( $user_obj ) && is_object( $user_obj ) ) {
				update_user_meta( $user_id, 'wishlist', $_POST['wishlist'] );
			}
		}
		die();
	}
}
if ( wp_doing_ajax() ) {
	add_action( 'wp_ajax_wishlist_update_user_data', 'wishlist_update_user_data' );
	add_action( 'wp_ajax_nopriv_wishlist_update_user_data', 'wishlist_update_user_data' );
}


if ( ! function_exists( 'wishlist_get_items' ) ) {
	function wishlist_get_items( $products_ids ) {
		$html = '<tr><td colspan="5">' . esc_html__( 'No wishlist found', 'snoopy' ) . '</td></tr>';

		if ( ! empty( $products_ids ) ) {
			$html = '';

			foreach ( $products_ids as $id ) {
				$product = wc_get_product( $id );

				$html .= '<tr><td><a href="' . esc_attr( get_permalink( $product->get_id() ) ) . '">' . $product->get_image() . '</a></td>';
				$html .= '<td><a href="' . esc_attr( get_permalink( $product->get_id() ) ) . '">' . esc_html( $product->get_name() ) . '</a></td>';
				$html .= '<td>' . $product->get_price_html() . '</td>';
				$html .= '<td>' . esc_html( $product->get_stock_status() ) . '</td>';
				$html .= '<td><button class="' . esc_attr( 'wishlist-toggle remove-from-wishlist' ) . '" data-product-id="' . esc_attr( $product->get_id() ) . '" title="' . esc_attr__( 'Remove from wishlist', 'snoopy' ) . '"><i class="' . esc_attr( 'fas fa-heart' ) . '"></i></button></td></tr>';
			}
		}

		return $html;
	}
}


if ( ! function_exists( 'wishlist_shortcode' ) ) {
	function wishlist_shortcode( $atts ) {
		$products_ids = array();

		if ( is_user_logged_in() ) {
			$current_user          = wp_get_current_user();
			$current_user_wishlist = get_user_meta( $current_user->ID, 'wishlist', true );
			$products_ids          = array_filter( explode( ',', $current_user_wishlist ) );
		}

		extract( shortcode_atts( array(), $atts ) );
		$html = '<table class="wishlist-table loading"><thead class="wishlist-table-head"><tr>
                    <th><!-- Left for image --></th>
                    <th>' . esc_html__( 'Name', 'snoopy' ) . '</th>
                    <th>' . esc_html__( 'Price', 'snoopy' ) . '</th>
                    <th>' . esc_html__( 'Stock', 'snoopy' ) . '</th>
                    <th><!-- Left for button --></th>
                </tr></thead><tbody class="wishlist-table-body">' . wishlist_get_items( $products_ids ) . '</tbody></table>';

		return $html;
	}
}
add_shortcode( 'snoopy_wishlist', 'wishlist_shortcode' );


if ( ! function_exists( 'wishlist_get_user_data' ) ) {
	function wishlist_get_user_data() {
		check_ajax_referer( 'ajax-nonce' );

		if ( isset( $_POST['wishlist'] ) && ! empty( $_POST['wishlist'] ) ) {
			$products_ids = array_filter( explode( ',', $_POST['wishlist'] ) );
			echo wishlist_get_items( $products_ids );
		}
		die();
	}
}
if ( wp_doing_ajax() ) {
	add_action( 'wp_ajax_wishlist_get_user_data', 'wishlist_get_user_data' );
	add_action( 'wp_ajax_nopriv_wishlist_get_user_data', 'wishlist_get_user_data' );
}


if ( ! function_exists( 'wishlist_body_class' ) ) {
	function wishlist_body_class( $class ) {
		global $post;

		if ( isset( $post->post_content ) && has_shortcode( $post->post_content, 'snoopy_wishlist' ) ) {
			$class[] = 'snoopy-wishlist';
		}

		return $class;
	}
}
add_filter( 'body_class', 'wishlist_body_class' );


if ( ! function_exists( 'wishlist_notification' ) ) {
	function wishlist_notification() {
		$html = '<div class="' . esc_attr( 'wishlist-notification-layer' ) . '">';
		$html .= '<div class="' . esc_attr( 'wishlist-notification' ) . '">';
		$html .= '<p class="' . esc_attr( 'wishlist-notification-content' ) . '">' . esc_html__( 'The item added to the wishlist.', 'snoopy' ) . '</p>';
		$html .= '</div></div>';
		$html .= '<!-- .wishlist-notification -->';

		echo $html;
	}
}
add_action( 'wp_footer', 'wishlist_notification' );
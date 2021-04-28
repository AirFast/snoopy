<?php

if ( ! function_exists( 'wishlist_user_profile' ) ) {
	function wishlist_user_profile( $user ) {
		$class    = array(
			'table' => 'form-table wishlist-data',
			'input' => 'regular-text'
		);
		$title    = __( 'Customer wishlist', 'snoopy' );
		$wishlist = __( 'Wishlist', 'snoopy' );
		$attr     = 'wishlist';
		$type     = 'text';
		$value    = get_the_author_meta( 'wishlist', $user->ID );

		$html = '<h2>' . esc_html( $title ) . '</h2>';
		$html .= '<table class="' . esc_attr( $class['table'] ) . '">';
		$html .= '<tr><th><label for="' . esc_attr( $attr ) . '">' . esc_html( $wishlist ) . '</label></th>';
		$html .= '<td><input class="' . esc_attr( $class['input'] ) . '" id="' . esc_attr( $attr ) . '" type="' . esc_attr( $type ) . '" name="' . esc_attr( $attr ) . '"  value="' . esc_attr( $value ) . '"/></td></tr></table>';

		echo $html;
	}
}
add_action( 'show_user_profile', 'wishlist_user_profile', 11 );
add_action( 'edit_user_profile', 'wishlist_user_profile' );


if ( ! function_exists( 'wishlist_user_profile_save' ) ) {
	function wishlist_user_profile_save( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}
		update_user_meta( $user_id, 'wishlist', $_POST['wishlist'] );

		return true;
	}
}
add_action( 'personal_options_update', 'wishlist_user_profile_save' );
add_action( 'edit_user_profile_update', 'wishlist_user_profile_save' );
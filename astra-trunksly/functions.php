<?php
/**
 * Trunskly Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Trunskly
 * @since 1.0.0
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_TRUNSKLY_VERSION', wp_get_theme()->get('Version') );

/**
 * Enqueue styles
 */
add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );
function child_enqueue_styles() {

	wp_enqueue_style( 'trunskly-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_TRUNSKLY_VERSION, 'all' );
	wp_enqueue_script( 'main', get_stylesheet_directory_uri() . '/main.js', '', '1.2.1a', true );

} // end function child_enqueue_styles

/**
 * Exclude the WOOF filter from Elementor to prevent loading issues 
 * https://products-filter.com/conflict-with-elementor-page-builder/
 */

add_action('elementor/widgets/widgets_registered', function($widgets_manager) {
 
    $widgets_manager->unregister_widget_type('wp-widget-woof_widget');
 
});

/**
 * Modify the button shown after adding an item to the cart.
 */ 
add_filter ( 'wc_add_to_cart_message_html', 'trunksly_wc_add_to_cart_message_filter', 10, 2 );
function trunksly_wc_add_to_cart_message_filter($current_message, $products = null) {

	$new_text = '';
	foreach($products as $product_id => $product_qty ) {
		$product_title = get_the_title( $product_id );
		if($product_qty > 1) {
			$new_text .= '<div class="woocommerce-message-inner">' . $product_title . ' (x' . $product_qty . ') has been added to your cart. &nbsp;</div>';
		} else {
			$new_text .=  '<div class="woocommerce-message-inner">' . $product_title . ' has been added to your cart. &nbsp;</div>';
		}
	}

	$current_message = sprintf( '%s <a href="%s" class="button">%s</a>',
					$new_text,
					esc_url( wc_get_page_permalink( 'checkout' ) ),
					esc_html__( 'Proceed to Checkout', 'woocommerce' ));

	return $current_message;
} // end function trunksly_wc_add_to_cart_message_filter

/**
 * Adds a Pay with Credit card button to the product page that adds the item to cart
 * and redirects to the checkout page
 */ 
function add_content_after_addtocart() {

	// get the current post/product ID
	$current_product_id = get_the_ID();
  
	// get the product based on the ID
	$product = wc_get_product( $current_product_id );
  
	// get the "Checkout Page" URL
	$checkout_url = WC()->cart->get_checkout_url();
  
	// run only on simple products
	if( $product->is_type( 'simple' ) ){
		
		echo '<div class="pay-with-cc-product-page"><a href="'.$checkout_url.'?add-to-cart='.$current_product_id.'&quantity=1&pay_by_cc=1" class="single_add_to_cart_button button alt">Pay with Credit Card</a></div>';
	}
  }
add_action( 'woocommerce_after_add_to_cart_form', 'add_content_after_addtocart', 10);
/**
 * Writes logs to the log file
 */
function wl ( $log )  {
	if ( is_array( $log ) || is_object( $log ) ) {
		error_log( print_r( $log, true ) );
	} else {
		error_log( $log );
	}
} // end write_log

/**
 * Modifies the WooCommerce price html to show the sale and regular prices to 
 * Trunksly+ members and to also show the signup link for non-members.
 */

add_filter( 'woocommerce_get_price_html', 'trunksly_change_product_html', 1, 2 );
function trunksly_change_product_html( $price_html, $product ) {
	$new_price_html = '';

	$is_trunksly_plus_member = check_if_trunksly_member();

	$price_to_use_for_trunksly_plus_calc = '';

	if($is_trunksly_plus_member) {
		if($product->is_on_sale() && $product->get_sale_price() != '') {
			$new_price_html .= '<span class="trunksly-regular-price woocommerce-Price-amount">Regular Price (MSRP): <span class="trunksly-strikethrough">' . wc_price($product->get_regular_price()) . '</span></span>';
			$new_price_html .= '<span class="trunksly-sale-price woocommerce-Price-amount">Sale Price: <span class="trunksly-strikethrough">' . wc_price($product->get_sale_price()) . '</span></span>';
		} else {
			$new_price_html .= '<span class="trunksly-sale-price woocommerce-Price-amount">Regular Price: <span class="trunksly-strikethrough">' . wc_price($product->get_regular_price()) . '</span></span>';
		}
		$new_price_html .= '<span class="trunksly-members-price woocommerce-Price-amount">Trunksly+ Price: </span>';
	} else {
		if($product->is_on_sale() && $product->get_sale_price() != '') {
			$new_price_html .= '<span class="trunksly-regular-price woocommerce-Price-amount">Regular Price (MSRP): <del>' . wc_price($product->get_regular_price()) . '</del></span>';
			$new_price_html .= '<span class="trunksly-sale-price woocommerce-Price-amount">Sale Price: <ins>' . wc_price($product->get_sale_price()) . '</ins></span>';
			$price_to_use_for_trunksly_plus_calc = $product->get_sale_price();
		} else {
			$new_price_html .= '<span class="trunksly-regular-price woocommerce-Price-amount">Regular Price: <ins>' . wc_price($product->get_regular_price()) . '</ins></span>';
			$price_to_use_for_trunksly_plus_calc = $product->get_regular_price();
		}
		$new_price_html .= '<span class="trunksly-members-price woocommerce-Price-amount">Trunksly+ Exclusive Price: <a class="trunksly-plus-popup trunksly-plus-price-non-member" href="#join-trunksly-plus">' . wc_price($price_to_use_for_trunksly_plus_calc * .90) . '</a> <a class="trunksly-plus-popup trunksly-plus-signup-link" href="#join-trunksly-plus">Sign Up for Free!</a></span>';

	}
	return $new_price_html;
} // end trunksly_change_product_html

/**
 * Returns true or false if the current user has the role "member"
 */
function check_if_trunksly_member() {
	if(is_user_logged_in()) {
		$user = wp_get_current_user();
		$roles = (array) $user->roles;
		if(in_array( 'member', $roles )) {
			return true;
		} else {
			return false;
		}
	} else {
	  return false;
	}
 } // end function check_if_trunksly_member

/**
 * Shortcode to add a membership upgrade button that shows only if logged in
 */
add_shortcode( 'trunksly-plus-upgrade', 'trunksly_add_membership_button_shortcode' );
function trunksly_add_membership_button_shortcode($membership_shortcode_attributes =[], $membership_shortcode_content = null, $membership_shortcode_tag = '') {

	if(is_user_logged_in()) {

		$is_trunksly_plus_member = check_if_trunksly_member();

		if($is_trunksly_plus_member) {
			$membership_button = '<div class="join-trunksly-title"><h2 style="text-align: center;">Congrats! You are already a member of Trunksly+!</h2></div>';
			$membership_button .= '<div style="text-align: center; margin-bottom: 20px;"><a class="button trunksly-button" href="/shop">Shop Now</a></div>';
		} else {
			//make the array keys and attributes lowercase
			$membership_shortcode_attributes = array_change_key_case((array)$membership_shortcode_attributes, CASE_LOWER);
		
			//override any default attributes with the user defined parameters
			$membership_shortcode_custom_attributes = shortcode_atts([
			'button_text' => 'Upgrade to Trunksly+',
			], $membership_shortcode_attributes, $membership_shortcode_tag);

			$membership_button = '<div class="join-trunksly-title"><h2>Click below to upgrade your account to Trunksly+ absolutely free, no strings attached. Get exclusive pricing, weekly offers, discounts & more!</h2></div>';
			$membership_button .= '<button class="button trunksly-plus-membership-upgrade"data-userid="' . get_current_user_id() . '">' . $membership_shortcode_custom_attributes['button_text'] . '</button><div class="trunksly-plus-membership-upgrade-status" style="margin-top: 10px;"></div>';
		}// end if is_member
		return $membership_button;
	}
} //end function trunksly_add_membership_button_shortcode
  
 /**
 * Ajax to tag a member with the WP Fusion tag 'trunksly-plus'
 */
add_action('wp_ajax_trunksly_membership_upgrade', 'trunksly_membership_upgrade_ajax');
add_action('wp_ajax_nopriv_trunksly_membership_upgrade', 'trunksly_membership_upgrade_ajax');
function trunksly_membership_upgrade_ajax() {

	// Add the user to the member role if it doesn't already exist 
	$member_add_status = trunksly_add_member_role($_POST['user_id']);
	$trunksly_plus_tag_id = wp_fusion()->user->get_tag_id('trunksly-plus');
	$trunksly_plus_tags = array($trunksly_plus_tag_id);
	$trunksly_plus_status = wp_fusion()->user->apply_tags($trunksly_plus_tags, $_POST['user_id']);
	wp_fusion()->user->push_user_meta( $_POST['user_id'] );
	if($trunksly_plus_status) {
		$status = 'Added as a Trunksly+ member.';
	} else {
		$status = 'Already a member of Trunksly+.';
	}
    $response = array(
		'status' => $status,
		'user_id' =>$_POST['user_id'],
		'member_add_status' => $member_add_status
	);

    wp_send_json($response);
    wp_die();
}
/*
 * Checks whether the user is logged in and if so shows the membership upgrade button
 * 
 */ 
if(is_user_logged_in()) {
	add_action('wp_head', 'trunksly_membership_button_script');
}

/*
 * Add user to the Member role
 * 
 */ 
function trunksly_add_member_role($user_id) {
	//get the current user
	$user =  get_user_by('id', $user_id);
	$trunksly_role = array('trunksly');
	if( !array_intersect($allowed_roles, $user->roles ) ) { 
		// not already a trunksly member
		$additional_roles = array('editor', 'administrator', 'author');
		if( array_intersect($allowed_roles, $user->roles ) ) { 
			// already a specific role we don't want to remove
			$user->add_role('member');
			return 'Added role "Member."';
		} else {
			$user->set_role('member');
			return 'Set role "Member."';
		}
	} // end if not trunksly member
	return 'Was already role "Member."';
} // end function trunksly_add_member_role

/**
 * jQuery that listens for clicks on the upgrade to Trunksly+ button
 */
function trunksly_membership_button_script() {
    ?>
    <script type="text/javascript">

        jQuery(function($) {
          $(document).ready(function() {
            var ajaxurl="<?php echo admin_url('admin-ajax.php'); ?>";

            $('.trunksly-plus-membership-upgrade').one('click', function(e) {

				e.preventDefault();

				var user_id = $(this).attr('data-userid');
				console.log(user_id);
              	var trunksly_membership_upgrade_action =
					$.ajax({
						type: 'POST',
						url: ajaxurl,
						dataType: 'json',
						data: {
							'action': 'trunksly_membership_upgrade',
							'user_id': user_id,
						},
						success: function (response) {
							$('.trunksly-plus-membership-upgrade-status').html('<h3>' + response.status + ' Redirecting...</h3>');
							//redirect
							var delay = 5000; 
							var url = 'https://trunksly.com/trunksly-plus-new-member/?form=lightbox'
							setTimeout(function(){ window.location = url; }, delay);
                	}
            	}); //end trunksly_membership_upgrade_action
           }); // end select on change
          }); // document ready
        }); // outer wrapper
    </script>
    <?php

} // end function trunksly_membership_button_script

/*
*	Add a hidden field to our WooCommerce login form - passing in the referring page URL
*	Note: the input (hidden) field doesn't actually get created unless the user was directed
*	to this page from a single product page
* 	https://gist.github.com/EvanHerman/492c09fbb584e0c428ae
*/
function redirect_user_back_to_product() {
	// check for a referer
	$referer = wp_get_referer();
	// if there was a referer.. 
	if( $referer ) {
		$post_id = url_to_postid( $referer );
		$post_data = get_post( $post_id );

		if( $post_data ) {
			// if the refering page was a single product, let's append a hidden field to reidrect the user to
			if( isset( $post_data->post_type ) && $post_data->post_type == 'product' ) {
				?>
					<input type="hidden" name="redirect-user" value="<?php echo $referer; ?>">
				<?php
			}
		}
	}
}
add_action( 'woocommerce_login_form', 'redirect_user_back_to_product' );
/*
*	Redirect the user back to the passed in referer page
*	- Which should be the URL to the last viewed product before logging in
*/
function wc_custom_user_redirect( $redirect, $user ) {
	if( isset( $_POST['redirect-user'] ) ) {
		$redirect = esc_url( $_POST['redirect-user'] );
	}
	return $redirect;
}
add_filter( 'woocommerce_login_redirect', 'wc_custom_user_redirect', 10, 2 );

/*
* Builds the custom lightbox to show the Trunksly+ upgrade
* and is loaded on all pages except the checkout page
*/

add_action('wp_footer', 'trunksly_plus_lightbox',1);

function trunksly_plus_lightbox(){
	if(!is_checkout()) {
	?>
	<div class="join-trunksly-plus-popup trunksly-plus-modal">
		<div class="trunksly-plus-modal-box">
			<div class="trunksly-plus-close"><i class="fa fa-times" aria-hidden="true"></i></div><!--trunksly-plus-close-->
			<div class="join-trunksly-not-logged-in" style="font-size: 22px; margin-top: 10px;">
				<div class="join-trunksly-title"><h2>Join Trunksly+ to access exclusive pricing, weekly offers, discounts & more! Absolutely free, no strings attached.</h2></div>
				<p>Already a member? <a href="/my-account">Log in here</a>.</p>
				<hr style="margin-bottom: 10px!important;" />
			</div>
			<div class="join-trunksly-not-logged-in">Or you can register below:</div>
			<div class="join-trunksly-not-logged-in"><?php echo do_shortcode('[wpuf_profile type="registration" id="941"]'); ?></div>
			<div class="join-trunksly-logged-in" style="margin-top: 20px;">
				<?php echo do_shortcode('[trunksly-plus-upgrade]'); ?></div>
		</div><!--trunksly-plus-modal-box-->
		<div class="trunksly-plus-modal-background"></div><!--trunksly-plus-modal-background-->
	</div><!-- trunksly-plus-modal -->
	<?php 
	} // end if
}
/*
*	Adds gtin and brand fields for Schema and Google Product feeds
*   to the Inventory tab of the WooCommerce product
*/
function dst_product_options_sku_add_text_gtin() {
	$gtin_args = array(
	  'label' => 'GTIN', // Text in the label in the editor.
	  'class' => 'dst-custom-woo-field',
	  'id' => 'trnk_product_gtin', // required, will be used as meta_key
	  'desc_tip' => true,
	  'description' => 'UPC, EAN, etc.'
	);
	woocommerce_wp_text_input( $gtin_args );

	$brand_args = array(
		'label' => 'Brand', // Text in the label in the editor.
		'class' => 'dst-custom-woo-field',
		'id' => 'trnk_product_brand', // required, will be used as meta_key
		'desc_tip' => true,
		'description' => 'Brand of the product'
	  );
	  woocommerce_wp_text_input( $brand_args );
}
add_action( 'woocommerce_product_options_sku', 'dst_product_options_sku_add_text_gtin' );

/**
 * Saves the custom product gtin and brand fields when the product
 * is saved or updated
 */
function dst_save_custom_fields( $post_id ) {
	$product = wc_get_product( $post_id );
	
	$gtin = isset( $_POST['trnk_product_gtin'] ) ? $_POST['trnk_product_gtin'] : 'Test';
	$product->update_meta_data( 'trnk_product_gtin', sanitize_text_field( $gtin ) );

	$brand = isset( $_POST['trnk_product_brand'] ) ? $_POST['trnk_product_brand'] : 'one';
	$product->update_meta_data( 'trnk_product_brand', sanitize_text_field( $brand ) );

	$product->save();
}
add_action( 'woocommerce_process_product_meta', 'dst_save_custom_fields' );


/**
 * Filter to add Brand Name & GTIN for Products to the RankMath schema.
 *
 * @param array $entity Snippet Data
 * @return array
 */
 add_filter( 'rank_math/snippet/rich_snippet_product_entity', function( $entity ) {
	global $product;

	$product_brand = get_post_meta($product->get_id(), 'trnk_product_brand', true);
	$entity['brand'] = $product_brand;

	$product_gtin = get_post_meta($product->get_id(), 'trnk_product_gtin', true);
	$entity['gtin'] = $product_gtin;

    return $entity;
});
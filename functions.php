<?php

/**

 * GeneratePress child theme functions and definitions.

 *

 * Add your custom PHP in this file.

 * Only edit this file if you have direct access to it on your server (to fix errors if they happen).

 */

/*---Change Logo for Login Page---*/
function my_login_logo() { ?>
    <style type="text/css">
        #login h1 a, .login h1 a {
        background-image: url(<?php echo get_stylesheet_directory_uri(); ?>/images/jack-front.png);
		height: 135px;
		width: 200px;
		background-size: 200px 135px;
		background-repeat: no-repeat;
		padding-bottom: 0;
		display: inline-block;
		color: transparent;
        }
		#password-protected-logo {
		margin: 0 auto;
		text-align: center;	
		}
    </style>
<?php }
add_action( 'login_enqueue_scripts', 'my_login_logo' );
/*---end Change Logo for Login Page---*/

/*---Admin CSS styles---*/
function adminStylesCss() {
    $url = get_settings('siteurl');
    $url = $url . '/wp-content/themes/generatepress_child/css/wp-admin.css';
    echo '<!-- Admin CSS styles -->
          <link rel="stylesheet" type="text/css" href="' . $url . '" />
          <!-- /end Admin CSS styles -->';
}
add_action('admin_head', 'adminStylesCss');
/*---end Admin CSS styles---*/

/*---Enable Product Revisons---*/
//shameem.dev/blog/woocommerce/enable-revisions-products-woocommerce
add_filter( 'woocommerce_register_post_type_product', 'wc_modify_product_post_type' );

function wc_modify_product_post_type( $args ) {
     $args['supports'][] = 'revisions';

     return $args;
}
/*---end Enable Product Revisons---*/


/**-----------------------------------------------------------------------*/

/**
 * Register a shortcode that creates a product categories dropdown list
 *
 * Use: [product_categories_dropdown orderby="title" show_count="1" fade_to_white="1" ]
 * 
 * Set "show_count" to 0 if you want to hide item counts.
 * Set "fade_to_white" to 0 if you do not want site page fade to white while loading the selected category page.
 *
*/ 
add_shortcode( 'product_categories_dropdown', 'woo_product_categories_dropdown' );

function woo_product_categories_dropdown( $atts ) {

	ob_start();
	
    wc_product_dropdown_categories( $atts );
 
	if ( isset($atts['fade_to_white']) && $atts['fade_to_white'] == 0 ) {
		$fade_to_white = 0;
	}
	else {
		$fade_to_white = 1;
	}
	
	?>
	<style>

		.cover-everything {
				position: fixed;
				top: 0;
				right: 0;
				bottom: 0;
				left: 0;
				background-color: #ffffff;
				z-index: 10000;
		}

		.fade-in-slowly {
			animation-name: fade-in-slowly;
			animation-duration: 2s;
			animation-iteration-count: 1;
		}

		@keyframes fade-in-slowly {
			from {
				opacity: 0;
			}

			to {
				opacity: 1;
			}
		}
	</style>
	
	<?php if ( $fade_to_white ): ?>
		<div id="jimaroos-modal-loader-popup" class="cover-everything" style="display: none;"></div>
	<?php endif; ?>
		
	<script type='text/javascript'>
	/* <![CDATA[ */
		var product_cat_dropdown = document.getElementById("product_cat");
		var loader_popup = document.getElementById("jimaroos-modal-loader-popup");
		
		if ( product_cat_dropdown ) {
			function onProductCatChange() {
				if ( product_cat_dropdown.options[product_cat_dropdown.selectedIndex].value !=='' ) {

					location.href = "<?php echo home_url(); ?>/?product_cat="+product_cat_dropdown.options[product_cat_dropdown.selectedIndex].value;

					if ( loader_popup ) {
						loader_popup.className += " fade-in-slowly";
						loader_popup.style.display = "block";
					}

				}
			}
			product_cat_dropdown.onchange = onProductCatChange;
		}
	/* ]]> */
	</script>
	<?php
	
	return ob_get_clean();
	
}

/**-----------------------------------------------------------------------*/

/**
 * @snippet       Add next/prev buttons @ WooCommerce Single Product Page
 * @how-to        Get CustomizeWoo.com FREE
 * @sourcecode    https://businessbloomer.com/?p=20567
 * @author        Rodolfo Melogli
 * @testedwith    WooCommerce 2.5.5
 *[/]
 
add_action( 'woocommerce_before_single_product', 'bbloomer_prev_next_product' );
 
// and if you also want them at the bottom...
// add_action( 'woocommerce_after_single_product', 'bbloomer_prev_next_product' );
 
function bbloomer_prev_next_product(){
 
echo '<div class="prev_next_buttons">';
 
   // 'product_cat' will make sure to return next/prev from current category
        $previous = next_post_link('%link', '&larr;', FALSE, ' ', 'product_cat');
   $next = previous_post_link('%link', '&rarr;', FALSE, ' ', 'product_cat');
 
   echo $previous;
   echo $next;
    
echo '</div>';
         
}*/

/**-----------------------------------------------------------------------*/

/**------------------------*/
// Add navigation links for previous and next products in single product page
function add_product_navigation_links() {
    // Check if it's a single product page
    if (is_product()) {
        global $post;

        // Get the previous and next product IDs within the same category
        $prev_product_id = get_adjacent_post(true, '', false, 'product_cat');
        $next_product_id = get_adjacent_post(true, '', true, 'product_cat');

        // Output the navigation links
        echo '<div class="product-navigation-links">';

        if ($prev_product_id) {
            echo '<div class="prev-cat"><a href="' . get_permalink($prev_product_id) . '">&larr;</a></div>';
        }

        if ($next_product_id) {
            echo '<div class="next-cat"><a href="' . get_permalink($next_product_id) . '">&rarr;</a></div>';
        }

        echo '</div>';
    }
}

add_action('woocommerce_before_single_product', 'add_product_navigation_links');
        
/**------------------------*/

/**------------------------*/
// YITH Auction: Fix locked product editor when Buy It Now product is purchased, then cancelled.
if( ! function_exists('yith_wcact_remove_closed_by_buy_now' ) ){
	add_action( 'woocommerce_order_status_cancelled', 'yith_wcact_remove_closed_by_buy_now' );

	function yith_wcact_remove_closed_by_buy_now( $order_id ) {
		$order = wc_get_order( $order_id );
		foreach ( $order->get_items() as $item ) {
			$_product = $item->get_product();
			if ( $_product && 'auction' === $_product->get_type() && true === $_product->get_is_closed_by_buy_now() ) {
				$_product->set_is_closed_by_buy_now( false );
				$_product->update_auction_status(true);
				$_product->save();
			}
			
		}
	}
}

/**------------------------*/

/**
 * Allow shortcodes in product excerpts
 */

if (!function_exists('woocommerce_template_single_excerpt')) {

   function woocommerce_template_single_excerpt( $post ) {

      global $post;

       if ($post->post_excerpt) echo '<div itemprop="description">' . do_shortcode(wpautop(wptexturize($post->post_excerpt))) . '</div>';

   }

}
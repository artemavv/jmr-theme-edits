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
 * Use: [product_categories_dropdown orderby="title" show_count="1" remove_past_categories="1" prefix="Ending" ]
 * 
 * Set "show_count" to 0 if you want to hide item counts.
 * Set "remove_past_categories" to 0 if you do not want remove "past" categories
 *
*/ 
add_shortcode( 'product_categories_dropdown', 'woo_product_categories_dropdown' );

function woo_product_categories_dropdown( $atts ) {

	$atts = shortcode_atts( array(
		'show_count'               => 1,
		'remove_past_categories'   => 1,
		'prefix'                   => 'Ending'
	), $atts, 'product_categories_dropdown' );
	
	
	if ( $atts['remove_past_categories'] != 0 ) {
		
		$exclude_ids = woo_get_past_categories_ids( $atts['prefix'], date('j'), date('n'), date('y') );
		
		if ( is_array( $atts) ) {
			$atts['exclude'] = $exclude_ids;
		}
	}

	ob_start();
	
	wc_product_dropdown_categories( $atts );
	
	return ob_get_clean();
	
}

/**
 * Finds all categories whose names match "Ending XX/XX/XX" 
 * and XX/XX/XX is the date earlier than the specified date
 *
 * @return array
 */
function woo_get_past_categories_ids( $prefix = 'Ending', $day = 31, $month = 12, $year = 24 ) {
	$ids = array();
	
	$pattern = '#' . $prefix . '.+([0-9]{2})/([0-9]{2})/([0-9]{2})#';
  
	$args = array(
			'hide_empty'         => 1,
			'show_uncategorized' => 1,
			'value_field'        => 'slug',
			'taxonomy'           => 'product_cat',
	);
	
	$product_categories = get_terms( $args );
					
	foreach ( $product_categories as $category ) {
		
		$match = [];
		
		if ( preg_match_all( $pattern, $category->name, $match ) ) {
			
			$cat_month = intval($match[1][0]);
			$cat_day   = intval($match[2][0]);
			$cat_year  = intval($match[3][0]);
			
			$cat_date = date( 'U', mktime( 0, 0, 0, $cat_month, $cat_day, 2000 + $cat_year) );
			$date     = date( 'U', mktime( 0, 0, 0, $month, $day, 2000 + $year) );
			
			if ( $cat_date <= $date ) {
				$ids[] = $category->term_id;
			}
		}
	}
	
	return $ids;
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
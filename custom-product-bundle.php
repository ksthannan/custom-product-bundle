<?php
/*
Plugin Name: Custom Product Bundle
Description: Create bundle with any selected products
Version:     1.0.0
Author:      CPB
Author URI:  #
Text Domain: p_bundle
Domain Path: /languages
*/

defined( 'ABSPATH' ) or die;

// Add a new product data tab
function add_custom_product_data_tab($tabs) {
    $tabs['custom__bundle_tab'] = array(
        'label'    => __('Custom Bundled Products', 'p_bundle'),
        'target'   => 'custom__bundle_tab_content',
        'class'    => array('show_if_simple', 'show_if_variable'),
    );
    return $tabs;
}
add_filter('woocommerce_product_data_tabs', 'add_custom_product_data_tab');

// Add content to the new product data tab
function custom_product_data_tab_content() {
    global $post;

    echo '<div id="custom__bundle_tab_content" class="panel woocommerce_options_panel">';

    woocommerce_wp_checkbox(
        array(
            'id'          => '_custom_product_bundle_enable',
            'label'       => __('Product Bundle Enable', 'p_bundle'),
            'desc_tip'    => 'true',
            'description' => __('Enable product bundle', 'p_bundle'),
        )
    );

    woocommerce_wp_text_input(
        array(
            'id'          => '_custom_discount',
            'label'       => __('Discount Percentage (%)', 'p_bundle'),
            'placeholder' => __('10', 'p_bundle'),
        )
    );

    woocommerce_wp_select(
        array(
            'id'          => '_custom_product_one',
            'label'       => __('Product One', 'p_bundle'),
            'placeholder' => __('Search and select products', 'p_bundle'),
            'desc_tip'    => 'true',
            'description' => __('Select products to be bundled by title keyword.', 'p_bundle'),
            'options'     => get_product_options_for_select(),
        )
    );
    woocommerce_wp_select(
        array(
            'id'          => '_custom_product_two',
            'label'       => __('Product Two', 'p_bundle'),
            'placeholder' => __('Search and select products', 'p_bundle'),
            'desc_tip'    => 'true',
            'description' => __('Select products to be bundled by title keyword.', 'p_bundle'),
            'options'     => get_product_options_for_select(),
        )
    );
    woocommerce_wp_select(
        array(
            'id'          => '_custom_product_three',
            'label'       => __('Product Three', 'p_bundle'),
            'placeholder' => __('Search and select products', 'p_bundle'),
            'desc_tip'    => 'true',
            'description' => __('Select products to be bundled by title keyword.', 'p_bundle'),
            'options'     => get_product_options_for_select(),
        )
    );

    echo '</div>';
}
add_action('woocommerce_product_data_panels', 'custom_product_data_tab_content');

function get_product_options_for_select() {
    $products = wc_get_products(array('limit' => -1));
    $options = array('' => '--- Select Product ---');
    global $post;
    foreach ($products as $product) {
        if($product->get_type() !== 'simple' || $product->get_id() == $post->ID) continue;
        $options[$product->get_id()] = $product->get_title();
    }

    return $options;
}

// Save custom tab data
function save_custom__bundle_tab_data($post_id) {
    
    $enable = isset($_POST['_custom_product_bundle_enable']) ? sanitize_text_field($_POST['_custom_product_bundle_enable']) : '';
    $discount = isset($_POST['_custom_discount']) ? sanitize_text_field($_POST['_custom_discount']) : '';
    $item_one = isset($_POST['_custom_product_one']) ? sanitize_text_field($_POST['_custom_product_one']) : '';
    $item_two = isset($_POST['_custom_product_two']) ? sanitize_text_field($_POST['_custom_product_two']) : '';
    $item_three = isset($_POST['_custom_product_three']) ? sanitize_text_field($_POST['_custom_product_three']) : '';

    update_post_meta($post_id, '_custom_product_bundle_enable', $enable);
    update_post_meta($post_id, '_custom_discount', $discount);
    update_post_meta($post_id, '_custom_product_one', $item_one);
    update_post_meta($post_id, '_custom_product_two', $item_two);
    update_post_meta($post_id, '_custom_product_three', $item_three);


}
add_action('woocommerce_process_product_meta', 'save_custom__bundle_tab_data');


// Display content after the Add to Cart button
function display_content_after_add_to_cart() {
    global $product;
	if(! $product) return;
    $enable_bundle = get_post_meta($product->get_id(), '_custom_product_bundle_enable', true);
    $_custom_discount = get_post_meta($product->get_id(), '_custom_discount', true);
    $item_one = get_post_meta($product->get_id(), '_custom_product_one', true);
    $item_two = get_post_meta($product->get_id(), '_custom_product_two', true);
    $item_three = get_post_meta($product->get_id(), '_custom_product_three', true);
    $bundle_products = array($item_one, $item_two, $item_three); 

    $stock = true;
    if(count($bundle_products) > 0 ) {
        foreach($bundle_products as $product_id){
			if(! $product_id) return;
            $product_it = wc_get_product($product_id);
            if ( ! $product_it->is_in_stock()) {
                $stock = false;
                return;
            }
        }
    }
    

    if($enable_bundle){
        $html = '<div class="custom-bundle-content_wrap">';
        $html .= '<div class="custom-bundle-content">';
    
        foreach($bundle_products as $product_id){
            $product_content = wc_get_product($product_id);
            
                $title = $product_content->get_title();
                $price_html = $product_content->get_price_html();
                $price = $product_content->get_price_including_tax();
                $thumb = $product_content->get_image('thumbnail');
                $permalink = $product_content->get_permalink();

                $html .= '<div class="custom_bundle_item">';
                $html .= $thumb;
                $html .= '<h2><a href="'.$permalink.'">'.$title.'</a></h2>';
                $html .= $price_html;
                $html .= '</div>';
			
        }
        $html .= '</div>';
		
		$discount = $_custom_discount ? ' ( ' . $_custom_discount . '% Discount )' : '';
        $bundled_one_price = wc_get_product_price_abs($item_one, $product->get_id());
        $bundled_two_price = wc_get_product_price_abs($item_two, $product->get_id());
        $bundled_three_price = wc_get_product_price_abs($item_three, $product->get_id());
		
// 		$html .= var_dump($bundled_three_price);

        $combined_price = 0;
        $html .= '<div class="custom_bundle_item_price_wrap">';
        $html .= '<div class="custom_bundle_item_price">';

        $html .= wc_price($bundled_one_price) . ' + ' . wc_price($bundled_two_price) . ' + ' . wc_price($bundled_three_price);
        
        $price = $bundled_one_price + $bundled_two_price + $bundled_three_price;
        $add_to_cart_url = wc_get_cart_url() . '?add-to-cart=' . $product->get_id() . '&quantity=1&bundled=true';
        $html .= '</div>';
        $html .= '<div class="custom_bundle_item_price_btn">';
        $html .= '<a class="three_items_optional button alt" href="'.$add_to_cart_url.'">Extra '.count($bundle_products).' Items ' . $discount . '</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        echo $html;

    }
    
}
add_action('woocommerce_after_add_to_cart_form', 'display_content_after_add_to_cart');

function display_custom_input_field_data(){
    global $product;
    if(! $product) return;
    $enable_bundle = get_post_meta($product->get_id(), '_custom_product_bundle_enable', true);
    if($enable_bundle){
        echo '<input type="hidden" name="bundle_products" id="bundle_products" value="" />';
    }
}
add_action('woocommerce_before_add_to_cart_button', 'display_custom_input_field_data');

function validate_custom_input_price() {

        global $product;
        if(! $product) return;
        $product_id = $product->get_id();
        $base_price = $product->get_price_including_tax();
        $enable_bundle = get_post_meta($product_id, '_custom_product_bundle_enable', true);
        if(!$enable_bundle) return;

        $discount = get_post_meta($product_id, '_custom_discount', true);

        $item_one = get_post_meta($product_id, '_custom_product_one', true);
        $item_two = get_post_meta($product_id, '_custom_product_two', true);
        $item_three = get_post_meta($product_id, '_custom_product_three', true);

        set_transient('bundled_discount', $discount);
        set_transient('bundled_base_price', $base_price);
        set_transient('bundled_one_price', wc_get_product_price_abs($item_one, $product_id));
        set_transient('bundled_two_price', wc_get_product_price_abs($item_two, $product_id));
        set_transient('bundled_three_price', wc_get_product_price_abs($item_three, $product_id));

}
add_filter('plugins_loaded', 'validate_custom_input_price', 10, 2);

// Validate and save custom input field data to cart item data
function validate_custom_input_field($cart_item_data, $product_id) {
    if (isset($_POST['bundle_products']) && $_POST['bundle_products'] == 'bundled') {
        $product_base = wc_get_product($product_id);
        $base_price = $product_base->get_price_including_tax();

        $item_one = get_post_meta($product_id, '_custom_product_one', true);
        $item_two = get_post_meta($product_id, '_custom_product_two', true);
        $item_three = get_post_meta($product_id, '_custom_product_three', true);

        $cart_item_data['bundled_one'] = wc_get_product_info($item_one);
        $cart_item_data['bundled_two'] = wc_get_product_info($item_two);
        $cart_item_data['bundled_three'] = wc_get_product_info($item_three);

        set_transient('bundled_base_price', $base_price);
        set_transient('bundled_one_price', wc_get_product_price_abs($item_one, $product_id));
        set_transient('bundled_two_price', wc_get_product_price_abs($item_two, $product_id));
        set_transient('bundled_three_price', wc_get_product_price_abs($item_three, $product_id));

    }
    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'validate_custom_input_field', 10, 2);

function wc_get_product_price_abs($item_id, $product_id){
	if(! $item_id) return;
    $discount_percentage = get_post_meta($product_id, '_custom_discount', true);
    if($discount_percentage){
        $product_conten = wc_get_product($item_id);
        $original_price = $product_conten->get_price_including_tax();
        $discount = $original_price * ($discount_percentage / 100);
        $new_price = $original_price - $discount;
        return $new_price;
    }else{
        $product_conten = wc_get_product($item_id);
        $new_price = $product_conten->get_price_including_tax();
        return $new_price;
    }

}
function wc_get_product_info($product_id){
	if(! $product_id) return;
    $product_content = wc_get_product($product_id);
    $data = '';
    $data .= '<div class="cart_addional_bundle_item">';
    $data .= $product_content->get_image('thumbnail');
    $data .= '<a href="'.$product_content -> get_permalink().'">'.$product_content -> get_title().'</a>';
    $data .= '</div>';
    return $data;
}

// Display custom input field value in the cart
function display_custom_input_field_in_cart($item_data, $cart_item) {
    if (isset($cart_item['bundled_one'])) {
		$item_data[] = array(
            'key'   => __('Additional Bundle Items', 'p_bundle'),
            'value' =>'',
        );
        $item_data[] = array(
            'key'   => __('1', 'p_bundle'),
            'value' => $cart_item['bundled_one'],
        );
        $item_data[] = array(
            'key'   => __('2', 'p_bundle'),
            'value' => $cart_item['bundled_two'],
        );
        $item_data[] = array(
            'key'   => __('3', 'p_bundle'),
            'value' => $cart_item['bundled_three'],
        );

    }
	
    return $item_data;
}
add_filter('woocommerce_get_item_data', 'display_custom_input_field_in_cart', 10, 2);





function set_custom_price_in_cart($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];

        if (isset($cart_item['bundled_one'])) {
            $bundled_base_price = intval(get_transient( 'bundled_base_price' ));
            $bundled_one_price = intval(get_transient( 'bundled_one_price' ));
            $bundled_two_price = intval(get_transient( 'bundled_two_price' ));
            $bundled_three_price = intval(get_transient( 'bundled_three_price' ));
            $custom_price = $bundled_base_price + $bundled_one_price + $bundled_two_price + $bundled_three_price; 
            $cart_item['data']->set_price($custom_price);
        }
    }
}
add_action('woocommerce_before_calculate_totals', 'set_custom_price_in_cart');


function custom_bundle_scripts(){
    ?>
<style>
.custom-bundle-content {
    display: flex;
    flex-wrap: wrap;
}
.custom_bundle_item {
    flex: 1 0 33%;
}
.custom_bundle_item h2 a {
    font-size: 18px;
    color: #333;
}
.custom_bundle_item h2 {
    margin-bottom: 0;
	margin-top:0;
}
.custom_bundle_item_price_wrap {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
}
.custom_bundle_item_price_btn {
    margin: 0 10px;
}
.custom_bundle_item_price_wrap {
    padding: 10px 0;
}
.custom-bundle-content_wrap {
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
}
.cart_addional_bundle_item {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
}
.cart_addional_bundle_item img {
    height: 50px;
    width: auto;
    margin-right: 10px;
}   
dt.variation-1, dt.variation-2, dt.variation-3 {
    float: left;
    margin-right: 10px;
}
.custom_bundle_item_price span.woocommerce-Price-amount.amount {
    background: transparent;
    padding: 10px;
    display: inline-block;
    margin: 10px 0;
    font-weight: bold;
    font-size: 22px;
    border-radius: 10px;
}
.custom-bundle-content_wrap {
    margin: 20px 0;
}
.custom_bundle_item ins {
    text-decoration: none;
}
.custom_bundle_item_price_wrap {
    padding: 0 20px 20px 20px;
    border: 1px solid #eee;
    margin: 20px 0 0 0;
}
</style>
<script>
(function($){
	$(document).ready(function($){
		$('.three_items_optional').on('click', function(e){
			e.preventDefault();
			$('#bundle_products').val('bundled');
			$('.single_add_to_cart_button').click();
		});
	});
})(jQuery);
</script>
    <?php 
}
add_action('wp_footer', 'custom_bundle_scripts', 99);
<?php
/*
Plugin Name: Payment Processors
Description:
Version: 0.0.1
Author: Andrew Melnik
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

add_action('woocommerce_settings_tabs_array', 'payment_processors_settings_tabs_array', 50);

function payment_processors_settings_tabs_array($tabs) {
	$tabs['payment_processors'] = __('Payment Processors', 'payment_processors'); 
	return $tabs;
}

add_action( 'woocommerce_settings_tabs_payment_processors', 'payment_processors_settings_tab' );

function payment_processors_settings_tab() {
    woocommerce_admin_fields( payment_processors_edit_account_form() );
}

function payment_processors_edit_account_form() { 
    $settings = array(
        'section_title' => array(
            'name'     => __( 'Payment Processor', 'payment_processors' ),
            'type'     => 'title',
            'desc'     => '',
            'id'       => 'wc_settings_tab_payment_processors_section_title'
        ),
		'section_title' => array(
            'name'     => __( 'Text to show if no payment methods available', 'payment_processors' ),
            'type'     => 'text',
            'desc'     => '',
            'id'       => 'wc_settings_tab_payment_processors_section_text'
        ),
        'description' => array(
            'name' => __( 'Description', 'payment_processors' ),
            'type' => 'payment_processors_table', 
            'desc' => __( 'This is a paragraph describing the setting. Lorem ipsum yadda yadda yadda. Lorem ipsum yadda yadda yadda. Lorem ipsum yadda yadda yadda. Lorem ipsum yadda yadda yadda.', 'payment_processors' ),
            'id'   => 'wc_settings_tab_payment_processors_description'
        ),
        'section_end' => array(
             'type' => 'sectionend',
             'id' => 'wc_settings_tab_payment_processors_section_end'
        )
    );
    return apply_filters( 'wc_settings_tab_payment_processors_settings', $settings );
}

add_action( 'woocommerce_update_options', 'update_payment_processors_settings' );
function update_payment_processors_settings() { 
    woocommerce_update_options( payment_processors_edit_account_form() );
}

add_action('woocommerce_admin_field_payment_processors_table','payment_processors_admin_field_payment_processors_table');
function payment_processors_admin_field_payment_processors_table($value){
	
		$country_codes=get_option( 'woocommerce_specific_allowed_countries',array());
		$payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
		$payment_gateways_settings = get_option( 'payment_processors', array() );
		$gateways = array();
		if( !empty( $payment_gateways ) ){
		
			foreach ($payment_gateways as $payment_gateway) {
				$title = $payment_gateway->title;
				$id = $payment_gateway->id;
				$gateways[$id]['id'] = $id;
				$gateways[$id]['title'] = $title;
			}
		} else {
			return 'No available payment gateways found';
		}

		?>

		<table class="form-table payment_processors">
			<th scope="row" class="titledesc"><?php _e( 'Payment processors (check TO REMOVE processors)', 'woocommerce' ) ?></th>
			<?php
			foreach ( $country_codes as $country_code ) { 
				?>
				<tr>
				<td> <?php echo WC()->countries->countries[$country_code] ?> </td>
				<?php foreach ($gateways as $gateway) {
					$checked = $payment_gateways_settings[$country_code][$gateway['id']]['disable'];  
					$val = $payment_gateways_settings[$country_code][$gateway['id']]['value'];
					?>
					<td>
						<input <?php checked($checked, 'on') ?> name="<?php echo 'payment_processors['.$country_code.']['.$gateway['id'].'][disable]' ?>" type="checkbox">
						<label for="payment-processors-<?php echo $gateway['id'] ?>"><?php echo $gateway['title'] ?></label>
						<input name="<?php echo 'payment_processors['.$country_code.']['.$gateway['id'].'][value]' ?>" value="<?php echo $val ?>" placeholder="Max $ amount" type="number">
					</td>
				<?php } ?>
				</tr>
			<?php } ?>
			</table>
			
	<?php
	
}

add_action('woocommerce_update_option_payment_processors_table','payment_processors_update_option_payment_processors_table');
function payment_processors_update_option_payment_processors_table($value){
	$payment_gateways_settings_new = $_POST['payment_processors'];
	if($payment_gateways_settings_new) {
		$payment_gateways_settings = array();
		$country_codes=get_option( 'woocommerce_specific_allowed_countries',array());
		$payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
		foreach($payment_gateways_settings_new as $country => $gateways ){
			foreach ($gateways as $gateway => $option) {
				$payment_gateways_settings[$country][$gateway]['disable'] = $payment_gateways_settings_new[$country][$gateway]['disable'];
				$payment_gateways_settings[$country][$gateway]['value'] = $payment_gateways_settings_new[$country][$gateway]['value'];
			}
		} 
		update_option('payment_processors',$payment_gateways_settings);
	}
}

add_filter( 'woocommerce_available_payment_gateways', 'payment_processors_hide_unrelated_gateways');
function payment_processors_hide_unrelated_gateways ($available_gateways) {

	if( is_checkout() ){	
	
		$ct = iqxzvqhmye_country_code();
		$payment_gateways_settings = get_option( 'payment_processors');
		$gateways = $payment_gateways_settings[$ct];
		$cart_total = WC()->cart->total;
		

		foreach ($gateways as $gateway => $option) {
			$amount = $option['value'] + 0;
			if($option['disable'] == 'on') {
				unset($available_gateways[$gateway] );
			}
			if($amount < $cart_total) {
				unset($available_gateways[$gateway] );
			}
		}
	}
	
    return $available_gateways;	
}

add_filter( 'woocommerce_no_available_payment_methods_message', 'payment_processors_empty_gateways_text');
function payment_processors_empty_gateways_text($text){
	$new_text = get_option( 'wc_settings_tab_payment_processors_section_text');
	if(!empty($new_text)){
		return $new_text;
	} else {
		return $text;
	}
}

<?php
/**
 * Plugin Name: Paystack Gateway for Tour Master
 * Plugin URI: https://paystack.com
 * Description: Processes payments via Paystack for Tour Master theme
 * Version: 1.0.0
 * Author: Paystack
 * License: GPLv2 or later
 */

/*	
*	---------------------------------------------------------------------
*	creating the paystack payment option
*	---------------------------------------------------------------------
*/

add_filter('goodlayers_plugin_payment_option', 'goodlayers_paystack_payment_gateway_options');
if( !function_exists('goodlayers_paystack_payment_gateway_options') ){
  function goodlayers_paystack_payment_gateway_options( $options ){
    $options['payment-settings']['options']['payment-method']['options'][0] = esc_html__('Paystack', 'tourmaster');

    return $options;
  }
}		

// set field option in the admin
add_filter('goodlayers_plugin_payment_option', 'tourmaster_paystack_payment_option');
if( !function_exists('tourmaster_paystack_payment_option') ){
  function tourmaster_paystack_payment_option( $options ){
    
    $options['paystack'] = array(
        'title' => esc_html__('Paystack', 'tourmaster'),
        'options' => array(
          'paystack-live-mode' => array(
            'title' => __('Paystack Live Mode', 'tourmaster'),
            'type' => 'checkbox',
            'default' => 'disable',
            'description' => esc_html__('Disable this option to test Paystack.', 'tourmaster')
          ),
          'paystack_tsk' => array(
            'title' => __('Paystack Test Secret Key', 'tourmaster'),
            'type' => 'text'
          ),
          'paystack_tpk' => array(
            'title' => __('Paystack Test Public Key', 'tourmaster'),
            'type' => 'text'
          ),
          'paystack_lsk' => array(
            'title' => __('Paystack Live Secret Key', 'tourmaster'),
            'type' => 'text'
          ),
          'paystack_lpk' => array(
            'title' => __('Paystack Live Public Key', 'tourmaster'),
            'type' => 'text'
          ),
          'Paystack-currency-code' => array(
            'title' => esc_html__('Paystack Currency Code', 'tourmaster'),
            'type' => 'text',	
            'default' => 'NGN'
          ),
            
        )
      );

      return $options;
  } // tourmaster_Paystack_payment_option
}
		
add_filter('goodlayers_paystack_payment_form', 'tourmaster_paystack_payment_form', 10, 2);; 
if( !function_exists('tourmaster_paystack_payment_form') ){

}

add_filter('tourmaster_paypal_button_atts', 'tourmaster_paypal_button_attribute');
	if( !function_exists('tourmaster_paypal_button_attribute') ){
		function tourmaster_paypal_button_attribute( $attributes ){
			$service_fee = tourmaster_get_option('payment', 'paypal-service-fee', '');

			return array('method' => 'ajax', 'type' => 'paypal', 'service-fee' => $service_fee);
		}
	}

	add_filter('goodlayers_paypal_payment_form', 'tourmaster_paypal_payment_form', 10, 2);
	if( !function_exists('tourmaster_paypal_payment_form') ){
		function tourmaster_paypal_payment_form( $ret = '', $tid = '' ){
			
			$live_mode = tourmaster_get_option('payment', 'paypal-live-mode', 'disable');
			$business_email = tourmaster_get_option('payment', 'paypal-business-email', '');
			$currency_code = tourmaster_get_option('payment', 'paypal-currency-code', '');
			$service_fee = tourmaster_get_option('payment', 'paypal-service-fee', '');

			$t_data = apply_filters('goodlayers_payment_get_transaction_data', array(), $tid, array('price', 'tour_id'));
			
			$price = '';
			if( $t_data['price']['deposit-price'] ){
				$price = $t_data['price']['deposit-price'];
			}else{
				$price = $t_data['price']['total-price'];
			}

			ob_start();
?>
<div class="goodlayers-paypal-redirecting-message" ><?php esc_html_e('Please wait while we redirect you to paypal.', 'tourmaster') ?></div>
<form id="goodlayers-paypal-redirection-form" method="post" action="<?php
		if( empty($live_mode) || $live_mode == 'disable' ){
			echo 'https://www.sandbox.paypal.com/cgi-bin/webscr';
		}else{
			echo 'https://www.paypal.com/cgi-bin/webscr';
		}
	?>" >
	<input type="hidden" name="cmd" value="_xclick" />
	<input type="hidden" name="business" value="<?php echo esc_attr(trim($business_email)); ?>" />
	<input type="hidden" name="currency_code" value="<?php echo esc_attr(trim($currency_code)); ?>" />
	<input type="hidden" name="item_name" value="<?php echo get_the_title($t_data['tour_id']); ?>" />
	<input type="hidden" name="invoice" value="<?php
		// 01 for tourmaster
		echo '01' . date('dmY') . $tid;
	?>" />
	<input type="hidden" name="amount" value="<?php echo esc_attr($price); ?>" />
	<input type="hidden" name="notify_url" value="<?php echo add_query_arg(array('paypal'=>''), home_url('/')); ?>" />
	<input type="hidden" name="return" value="<?php
		echo add_query_arg(array('tid' => $tid, 'step' => 4, 'payment_method' => 'paypal'), tourmaster_get_template_url('payment'));
	?>" />
</form>
<script type="text/javascript">
	(function($){
		$('#goodlayers-paypal-redirection-form').submit();
	})(jQuery);
</script>
<?php
			$ret = ob_get_contents();
			ob_end_clean();

			return $ret;

		} // goodlayers_paypal_payment_form
	}

	add_action('init', 'tourmaster_paypal_process_ipn');
	if( !function_exists('tourmaster_paypal_process_ipn') ){
		function tourmaster_paypal_process_ipn(){

			if( isset($_GET['paypal']) ){

				$payment_info = array(
					'payment_method' => 'paypal'
				);

				$live_mode = tourmaster_get_option('payment', 'paypal-live-mode', '');
				if( empty($live_mode) || $live_mode == 'disable' ){
					$paypal_action_url = 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';
				}else{
					$paypal_action_url = 'https://ipnpb.paypal.com/cgi-bin/webscr';
				}
				// read the post data
				$raw_post_data = file_get_contents('php://input');
				$raw_post_array = explode('&', $raw_post_data);
				$myPost = array();
				foreach ($raw_post_array as $keyval) {
				    $keyval = explode('=', $keyval);
				    if (count($keyval) == 2) {
				        // Since we do not want the plus in the datetime string to be encoded to a space, we manually encode it.
				        if ($keyval[0] === 'payment_date') {
				            if (substr_count($keyval[1], '+') === 1) {
				                $keyval[1] = str_replace('+', '%2B', $keyval[1]);
				            }
				        }
				        $myPost[$keyval[0]] = urldecode($keyval[1]);
				    }
				}

				// prepare post request
				$req = 'cmd=_notify-validate';
		        $get_magic_quotes_exists = false;
		        if (function_exists('get_magic_quotes_gpc')) {
		            $get_magic_quotes_exists = true;
		        }
		        foreach ($myPost as $key => $value) {
		            if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
		                $value = urlencode(stripslashes($value));
		            } else {
		                $value = urlencode($value);
		            }
		            $req .= "&$key=$value";
		        }

		        // Post the data back to PayPal, using curl. Throw exceptions if errors occur.
		        $ch = curl_init($paypal_action_url);
		        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		        curl_setopt($ch, CURLOPT_POST, 1);
		        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
		        curl_setopt($ch, CURLOPT_SSLVERSION, 6);
		        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
				curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
		        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close', 'User-Agent: tourmaster'));
				
				$res = curl_exec($ch);
		        if ( !$res ){ 

		            $payment_info['error'] = curl_error($ch);

		            if( !empty($_POST['invoice']) ){
		            	$tid = substr($_POST['invoice'], 10);

		            	tourmaster_update_booking_data( 
							array(
								'payment_info' => json_encode($payment_info),
							),
							array('id' => $tid, 'payment_date' => '0000-00-00 00:00:00'),
							array('%s'),
							array('%d', '%s')
						);
		            }

		        }else if( strcmp ($res, "VERIFIED") == 0 ) {

		        	$tid = substr($_POST['invoice'], 10);
		        	$payment_info['transaction_id'] = $_POST['txn_id'];
		        	$payment_info['amount'] = $_POST['mc_gross'];

		        	$tdata = tourmaster_get_booking_data(array('id'=>$tid), array('single'=>true));
		        	$pricing_info = json_decode($tdata->pricing_info, true);

		        	if( !empty($pricing_info['deposit-price']) && tourmaster_compare_price($pricing_info['deposit-price'], $payment_info['amount']) ){
		        		$order_status = 'deposit-paid';
		        		if( !empty($pricing_info['deposit-price-raw']) ){
		        			$payment_info['deposit_amount'] = $pricing_info['deposit-price-raw'];
						}
		        	}else if( tourmaster_compare_price($pricing_info['total-price'], $payment_info['amount']) ){
		        		$order_status = 'online-paid';
		        	}else{
		        		$order_status = 'deposit-paid';
		        	}

					tourmaster_update_booking_data( 
						array(
							'payment_info' => json_encode($payment_info),
							'payment_date' => current_time('mysql'),
							'order_status' => $order_status,
						),
						array('id' => $tid),
						array('%s', '%s', '%s', '%s', '%s'),
						array('%d')
					);

					tourmaster_mail_notification('payment-made-mail', $tid);
					tourmaster_mail_notification('admin-online-payment-made-mail', $tid);
					tourmaster_send_email_invoice($tid);
				}
				curl_close($ch);

		        exit;
			}

		} // tourmaster_paypal_process_ipn
	}
       
?>

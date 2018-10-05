<?php
/**
 * Plugin Name: Paystack Gateway for Tour Master
 * Plugin URI: https://paystack.com
 * Description: Processes payments via Paystack for Tour Master theme
 * Version: 1.0.0
 * Author: Paystack
 * License: GPLv2 or later
 */
//add paystack as payment option for credit card in the tourmaster payment panel
add_filter('goodlayers_credit_card_payment_gateway_options', 'goodlayers_paystack_payment_gateway_options');
	if( !function_exists('goodlayers_paystack_payment_gateway_options') ){
		function goodlayers_paystack_payment_gateway_options( $options ){
			$options['paystack'] = esc_html__('Paystack', 'tourmaster'); 

			return $options;
		}
	}
// set field option in the admin
add_filter('goodlayers_plugin_payment_option', 'goodlayers_paystack_payment_option');
	if( !function_exists('goodlayers_paystack_payment_option') ){
		function goodlayers_paystack_payment_option( $options ){

		$options['paystack'] = array(
				'title' => esc_html__('Paystack', 'tourmaster'),
				'options' => array(
					'Paystack-live-mode' => array(
						'title' => __('Paystack Live Mode', 'tourmaster'),
						'type' => 'checkbox',
						'default' => 'disable',
						'description' => esc_html__('Disable this option to test on sandbox mode.', 'tourmaster')
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
					'Paystack-business-email' => array(
						'title' => esc_html__('Paystack Business Email', 'tourmaster'),
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
//configurations 
$current_payment_gateway = apply_filters('goodlayers_payment_get_option', '', 'credit-card-payment-gateway');
if( $current_payment_gateway == 'paystack' ){
		//include_once(TOURMASTER_LOCAL . '/include/paystack/autoload.php');

        add_action('goodlayers_payment_page_init', 'goodlayers_paystack_payment_page_init');
		add_filter('goodlayers_plugin_payment_attribute', 'goodlayers_paystack_payment_attribute');	
		add_filter('goodlayers_paystack_payment_form', 'goodlayers_paystack_payment_form', 10, 2);
		
		add_action('wp_ajax_paystack_payment_charge', 'goodlayers_paystack_payment_charge');
		add_action('wp_ajax_nopriv_paystack_payment_charge', 'goodlayers_paystack_payment_charge');
	}

										
	if( !function_exists('goodlayers_paystack_payment_page_init') ){
		function goodlayers_paystack_payment_page_init( $options ){
			add_action('wp_head', 'goodlayers_paystack_payment_script_include');
		}
	}
if( !function_exists('goodlayers_paystack_payment_script_include') ){
		function goodlayers_paystack_payment_script_include( $options ){
			echo '<script type="text/javascript" src="https://js.paystack.co/v1/inline.js"></script>';
		}
	}

									
			if( !function_exists('goodlayers_paystack_payment_attribute') ){
		function goodlayers_paystack_payment_attribute( $attributes ){
			return array('method' => 'ajax', 'type' => 'paystack');
		}
	}
	
		
 
		if( !function_exists('goodlayers_paystack_payment_form') ){
		function goodlayers_paystack_payment_form( $ret = '', $tid = '' ){
			$publishable_key = apply_filters('goodlayers_payment_get_option', '', 'paystack_lsk');

				ob_start();
			?>
				<div class="goodlayers-payment-form goodlayers-with-border" >
	<form action="" method="POST" id="goodlayers-paystack-payment-form" data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" >
		
		
  <button class="goodlayers-payment-button submit" type="button" name="pay_now" id="pay-now" title="Pay now"  onclick="saveOrderThenPayWithPaystack()">Pay now</button>
<input type="hidden" name="tid" value="<?php echo esc_attr($tid) ?>" />
		
		
		<!-- for proceeding to last step -->
		<div class="goodlayers-payment-plugin-complete" ></div>
	</form>
</div>
<script type="text/javascript">
	var PAYSTACK_PUBLIC_KEY = '<?php echo esc_js(trim($public_key)); ?>';
	
  var orderObj = {
    email_prepared_for_paystack: '<?php echo $tourmaster_data['email']; ?>',
    amount: <?php echo $tourmaster_data['price']; ?>,
    cartid: <?php echo $tourmaster_data['tour_id']; ?>
    // other params you want to save
  };
  function saveOrderThenPayWithPaystack(){
    // Send the data to save using post
    var posting = $.post( '/save-order', orderObj );
    posting.done(function( data ) {
      /* check result from the attempt */
      payWithPaystack(data);
    });
    posting.fail(function( data ) { /* and if it failed... */ });
  }
  function payWithPaystack(data){
    var handler = PaystackPop.setup({
      // This assumes you already created a constant named
      // PAYSTACK_PUBLIC_KEY with your public key from the
      // Paystack dashboard. You can as well just paste it
      // instead of creating the constant
      key: '<?php echo PAYSTACK_PUBLIC_KEY; ?>',
      email: orderObj.email_prepared_for_paystack,
      amount: orderObj.amount,
      metadata: {
        cartid: orderObj.cartid,
        orderid: data.orderid,
        custom_fields: [
          {
            display_name: "Paid on",
            variable_name: "paid_on",
            value: 'Website'
          },
          {
            display_name: "Paid via",
            variable_name: "paid_via",
            value: 'Inline Popup'
          }
        ]
      },
      callback: function(response){
        // post to server to verify transaction before giving value
        var verifying = $.get( '/verify.php?reference=' + response.reference);
        verifying.done(function( data ) { /* give value saved in data */ });
      },
      onClose: function(){
        alert('Click "Pay now" to retry payment.');
      }
    });
    handler.openIframe();
  }
</script>							   
																																						 
<?php
			$ret = ob_get_contents();
			ob_end_clean();
			return $ret;
		}
	}

							   
if( !function_exists('goodlayers_paystack_payment_charge') ){
		function goodlayers_paystack_payment_charge(){

			$ret = array();

			if( !empty($_POST['tid']) && !empty($_POST['form']) ){
			
				// prepare data
				$form = stripslashes_deep($_POST['form']);
              
			 // $live_mode = tourmaster_get_option('payment', 'paystack-live-mode', 'disable');
			$api_id = apply_filters('goodlayers_payment_get_option', '', 'paystack_lpk');
			$business_email = tourmaster_get_option('payment', 'paystack-business-email', '');
			$currency_code = tourmaster_get_option('payment', 'paystack-currency-code', '');
			//$service_fee = tourmaster_get_option('payment', 'paystack-service-fee', '');
 			$Paystack_live_mode = tourmaster_get_option('Paystack-live-mode');
		
        if (empty($live_mode) || $Paystack_live_mode == 'enable' ) {
            $paystack_payment_url = 'https://paystack.url';
            $secretkey = get_option('paystack_lsk');
            $publickey = get_option('paystack_lpk');
        } else {
            $paystack_payment_url = 'https://test.paystack.url';
            $secretkey = get_option('paystack_tsk');
            $publickey = get_option('paystack_tpk');
        }
		
		$t_data = apply_filters('goodlayers_payment_get_transaction_data', array(), $_POST['tid'], array('price', 'email'));
	
			$price = ''; $tid =$_POST['tid'];
				if( $t_data['price']['deposit-price'] ){
					$price = $t_data['price']['deposit-price'];
					if( !empty($t_data['price']['deposit-price-raw']) ){
						$deposit_amount = $t_data['price']['deposit-price-raw'];
					}
				}else{
					$price = $t_data['price']['total-price'];
				}

				if( empty($price) ){
					$ret['status'] = 'failed';
					$ret['message'] = esc_html__('Cannot retrieve pricing data, please try again.', 'tourmaster');
				}else{
					if( in_array(strtolower($currency), array('jpy')) ){
						$price = intval(floatval($price));
						$charge_amount = $price;
					}else{
						$price = round(floatval($price) * 100);
						$charge_amount = $price / 100;
					}

					try{							  
		 

						$payment_info = array(
							'payment_method' => 'paystack',
							'amount' => $charge_amount,
							'transaction_id' => $charge->id 
						);
						if( !empty($deposit_amount) ){
							$payment_info['deposit_amount'] = $deposit_amount;
						}
						do_action('goodlayers_set_payment_complete', $_POST['tid'], $payment_info);

						$ret['status'] = 'success';

					}catch( Exception $e ){
						$ret['status'] = 'failed';
						$ret['message'] = $e->getMessage();
					}
				}
			}

			die(json_encode($ret));

		} // goodlayers_paystack_payment_charge
	}
       
?>

<?php

add_filter('goodlayers_credit_card_payment_gateway_options', 'goodlayers_paystack_payment_gateway_options');
	if( !function_exists('goodlayers_paystack_payment_gateway_options') ){
		function goodlayers_paystack_payment_gateway_options( $options ){
			$options['Paystack'] = esc_html__('Paystack', 'tourmaster'); 

			return $options;
		}
	}
add_filter('goodlayers_plugin_payment_option', 'tourmaster_paystack_payment_option');
	if( !function_exists('tourmaster_paystack_payment_option') ){
		function tourmaster_paystack_payment_option( $options ){
		$options['Paystack'] = array(
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
$current_payment_gateway = apply_filters('goodlayers_payment_get_option', '', 'credit-card-payment-gateway');
if( $current_payment_gateway == 'paystack' ){
		//include_once(TOURMASTER_LOCAL . '/include/paystack/autoload.php');

		add_filter('goodlayers_plugin_payment_attribute', 'goodlayers_paystack_payment_attribute');	
		add_filter('goodlayers_paystack_payment_form', 'tourmaster_paystack_payment_url', 10, 2);;

		add_action('wp_ajax_paystack_payment_charge', 'goodlayers_paystack_payment_charge');
		add_action('wp_ajax_nopriv_paystack_charge', 'goodlayers_paystack_payment_charge');
	}

			if( !function_exists('goodlayers_paystack_payment_attribute') ){
		function goodlayers_paystack_payment_attribute( $attributes ){
			return array('method' => 'ajax', 'type' => 'paystack');
		}
	}
		if( !function_exists('goodlayers_paystack_payment_form') ){
		function goodlayers_paystack_payment_form( $ret = '', $tid = '' ){
			//$publishable_key = apply_filters('goodlayers_payment_get_option', '', 'paystack_lsk');
				ob_start();?>
				<div class="goodlayers-payment-form goodlayers-with-border" >
	<form action="" method="POST" id="goodlayers-paystack-payment-form" data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" >
		<div class="goodlayers-payment-form-field">
			<label>
				<span class="goodlayers-payment-field-head" ><?php esc_html_e('Card Number', 'tourmaster'); ?></span>
				<input type="text" data-paystack="number">
			</label>
		</div>
		<div class="goodlayers-payment-form-field">
			<label>
				<span class="goodlayers-payment-field-head" ><?php esc_html_e('Expiration (MM/YY)', 'tourmaster'); ?></span>
				<input class="goodlayers-size-small" type="text" size="2" data-paystack="exp_month" />
			</label>
			<span class="goodlayers-separator" >/</span>
			<input class="goodlayers-size-small" type="text" size="2" data-paystack="exp_year" />
		</div>
		<div class="goodlayers-payment-form-field">
			<label>
				<span class="goodlayers-payment-field-head" ><?php esc_html_e('CVC', 'tourmaster'); ?></span>
				<input class="goodlayers-size-small" type="text" size="4" data-paystack="cvc" />
			</label>
		</div>
		<div class="now-loading"></div>
		<div class="payment-errors"></div>
		<div class="goodlayers-payment-req-field" ><?php esc_html_e('Please fill all required fields', 'tourmaster'); ?></div>
		<input type="hidden" name="tid" value="<?php echo esc_attr($tid) ?>" />
		<input class="goodlayers-payment-button submit" type="submit" value="<?php esc_html_e('Submit Payment', 'tourmaster'); ?>" />
		
		<!-- for proceeding to last step -->
		<div class="goodlayers-payment-plugin-complete" ></div>
	</form>
</div>
<script type="text/javascript">
	(function($){
		var form = $('#goodlayers-paystack-payment-form');

		function goodlayersPaystackCharge(){

			var tid = form.find('input[name="tid"]').val();
			var form_value = {};
			form.find('[data-paystack]').each(function(){
				form_value[$(this).attr('data-paystack')] = $(this).val(); 
			});

			$.ajax({
				type: 'POST',
				url: form.attr('data-ajax-url'),
				data: { 'action':'paystack_payment_charge', 'tid': tid, 'form': form_value },
				dataType: 'json',
				error: function(a, b, c){ 
					console.log(a, b, c); 

					// display error messages
					form.find('.payment-errors').text('<?php echo esc_html__('An error occurs, please refresh the page to try again.', 'tourmaster'); ?>').slideDown(200);
					form.find('.submit').prop('disabled', false).removeClass('now-loading'); 
				},
				success: function(data){
					if( data.status == 'success' ){
						form.find('.goodlayers-payment-plugin-complete').trigger('click');
					}else if( typeof(data.message) != 'undefined' ){
						form.find('.payment-errors').text(data.message).slideDown(200);
					}

					form.find('.submit').prop('disabled', false).removeClass('now-loading'); 
				}
			});	
		};
		
		form.submit(function(event){
			var req = false;
			form.find('input').each(function(){
				if( !$(this).val() ){
					req = true;
				}
			});

			if( req ){
				form.find('.goodlayers-payment-req-field').slideDown(200)
			}else{
				form.find('.submit').prop('disabled', true).addClass('now-loading');
				form.find('.payment-errors, .goodlayers-payment-req-field').slideUp(200);
				goodlayerspaystackCharge();
			}

			return false;
		});
	})(jQuery);
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
			$service_fee = tourmaster_get_option('payment', 'paystack-service-fee', '');
 			$Paystack_live_mode = get_option('Paystack-live-mode');
		$t_data = apply_filters('goodlayers_payment_get_transaction_data', array(), $_POST['tid'], array('price', 'email'));
        if (empty($live_mode) || $Paystack_live_mode == 'enable' ) {
            $paystack_payment_url = 'https://paystack.url';
            $secretkey = get_option('paystack_lsk');
            $publickey = get_option('paystack_lpk');

        } else {
            $paystack_payment_url = 'https://test.paystack.url';
            $secretkey = get_option('paystack_tsk');
            $publickey = get_option('paystack_tpk');

        }
			$price = '';
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
						\paystack\paystack::setApiKey($api_id);

						// Create a Customer:
						$customer = \paystack\Customer::create(array(
							'email' => empty($t_data['email'])? '': $t_data['email'],
							
						));
						
						// Charge the Customer instead of the card:
						$charge = \paystack\Charge::create(array(
							'amount' => $price,
							'currency' => $currency,
							'customer' => $customer->id
						));

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
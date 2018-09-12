<?php
	/*	
	*	Payment Plugin
	*	---------------------------------------------------------------------
	*	creating the stripe payment option
	*	---------------------------------------------------------------------
	*/

	add_filter('goodlayers_credit_card_payment_gateway_options', 'goodlayers_stripe_payment_gateway_options');
	if( !function_exists('goodlayers_stripe_payment_gateway_options') ){
		function goodlayers_stripe_payment_gateway_options( $options ){
			$options['stripe'] = esc_html__('Stripe', 'tourmaster'); 

			return $options;
		}
	}

	add_filter('goodlayers_plugin_payment_option', 'goodlayers_stripe_payment_option');
	if( !function_exists('goodlayers_stripe_payment_option') ){
		function goodlayers_stripe_payment_option( $options ){

			$options['stripe'] = array(
				'title' => esc_html__('Stripe', 'tourmaster'),
				'options' => array(
					'stripe-secret-key' => array(
						'title' => __('Stripe Secret Key', 'tourmaster'),
						'type' => 'text'
					),
					'stripe-publishable-key' => array(
						'title' => __('Stripe Publishable Key', 'tourmaster'),
						'type' => 'text'
					),	
					'stripe-currency-code' => array(
						'title' => __('Stripe Currency Code', 'tourmaster'),
						'type' => 'text',	
						'default' => 'usd'
					),	
				)
			);

			return $options;
		} // goodlayers_stripe_payment_option
	}

	$current_payment_gateway = apply_filters('goodlayers_payment_get_option', '', 'credit-card-payment-gateway');
	if( $current_payment_gateway == 'stripe' ){
		include_once(TOURMASTER_LOCAL . '/include/stripe/init.php');

		add_action('goodlayers_payment_page_init', 'goodlayers_stripe_payment_page_init');
		add_filter('goodlayers_plugin_payment_attribute', 'goodlayers_stripe_payment_attribute');
		add_filter('goodlayers_stripe_payment_form', 'goodlayers_stripe_payment_form', 10, 2);

		add_action('wp_ajax_stripe_payment_charge', 'goodlayers_stripe_payment_charge');
		add_action('wp_ajax_nopriv_stripe_payment_charge', 'goodlayers_stripe_payment_charge');
	}

	// init the script on payment page head
	if( !function_exists('goodlayers_stripe_payment_page_init') ){
		function goodlayers_stripe_payment_page_init( $options ){
			add_action('wp_head', 'goodlayers_stripe_payment_script_include');
		}
	}
	if( !function_exists('goodlayers_stripe_payment_script_include') ){
		function goodlayers_stripe_payment_script_include( $options ){
			echo '<script type="text/javascript" src="https://js.stripe.com/v2/"></script>';
		}
	}	

	// add attribute for payment button
	if( !function_exists('goodlayers_stripe_payment_attribute') ){
		function goodlayers_stripe_payment_attribute( $attributes ){
			return array('method' => 'ajax', 'type' => 'stripe');
		}
	}

	// payment form
	if( !function_exists('goodlayers_stripe_payment_form') ){
		function goodlayers_stripe_payment_form( $ret = '', $tid = '' ){
			$publishable_key = apply_filters('goodlayers_payment_get_option', '', 'stripe-publishable-key');

			ob_start();
?>
<div class="goodlayers-payment-form goodlayers-with-border" >
	<form action="" method="POST" id="goodlayers-stripe-payment-form" data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" >
		<div class="goodlayers-payment-form-field">
			<label>
				<span class="goodlayers-payment-field-head" ><?php esc_html_e('Card Holder Name', 'tourmaster'); ?></span>
				<input type="text" data-stripe="name">
			</label>
		</div>
		<div class="goodlayers-payment-form-field">
			<label>
				<span class="goodlayers-payment-field-head" ><?php esc_html_e('Card Number', 'tourmaster'); ?></span>
				<input type="text" data-stripe="number">
			</label>
		</div>
		<div class="goodlayers-payment-form-field">
			<label>
				<span class="goodlayers-payment-field-head" ><?php esc_html_e('Expiration (MM/YY)', 'tourmaster'); ?></span>
				<input class="goodlayers-size-small" type="text" size="2" data-stripe="exp_month" />
			</label>
			<span class="goodlayers-separator" >/</span>
			<input class="goodlayers-size-small" type="text" size="2" data-stripe="exp_year" />
		</div>
		<div class="goodlayers-payment-form-field">
			<label>
				<span class="goodlayers-payment-field-head" ><?php esc_html_e('CVC', 'tourmaster'); ?></span>
				<input class="goodlayers-size-small" type="text" size="4" data-stripe="cvc" />
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
	Stripe.setPublishableKey('<?php echo esc_js(trim($publishable_key)); ?>');

	(function($){
		var form = $('#goodlayers-stripe-payment-form');

		function stripeResponseHandler(status, response){
			if( response.error ){ 
				form.find('.payment-errors').text(response.error.message).slideDown(200);
				form.find('.submit').prop('disabled', false).removeClass('now-loading'); 
			}else{
				var token = response.id;
				var tid = form.find('input[name="tid"]').val();

				$.ajax({
					type: 'POST',
					url: form.attr('data-ajax-url'),
					data: { 'action':'stripe_payment_charge','token': token, 'tid': tid },
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
			}
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
				Stripe.card.createToken(form, stripeResponseHandler);
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

	// ajax for payment submission
	if( !function_exists('goodlayers_stripe_payment_charge') ){
		function goodlayers_stripe_payment_charge(){

			$ret = array();

			if( !empty($_POST['token']) && !empty($_POST['tid']) ){
				$api_key = trim(apply_filters('goodlayers_payment_get_option', '', 'stripe-secret-key'));
				$currency = trim(apply_filters('goodlayers_payment_get_option', 'usd', 'stripe-currency-code'));

				$t_data = apply_filters('goodlayers_payment_get_transaction_data', array(), $_POST['tid'], array('price', 'email'));
				
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
						\Stripe\Stripe::setApiKey($api_key);

						// Create a Customer:
						$customer = \Stripe\Customer::create(array(
							'email' => empty($t_data['email'])? '': $t_data['email'],
							'source' => $_POST['token'],
						));
						
						// Charge the Customer instead of the card:
						$charge = \Stripe\Charge::create(array(
							'amount' => $price,
							'currency' => $currency,
							'customer' => $customer->id
						));

						$payment_info = array(
							'payment_method' => 'stripe',
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

		} // goodlayers_stripe_payment_charge
	}

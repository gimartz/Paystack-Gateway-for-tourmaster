<?php
/* 
Version: 1.0.0
Author: Diogo Ferreira - HiPay Portugal
License: Apache License 2.0
*/

/*
* CONFIGURATIONS
*/

add_filter('goodlayers_plugin_payment_option', 'tourmaster_hipayprofessional_payment_option');
if( !function_exists('tourmaster_hipayprofessional_payment_option') ){
	function tourmaster_hipayprofessional_payment_option( $options ){

		if (extension_loaded('soap')) {
			$lib_title 	= esc_html__('SOAP Extension active.','tourmaster') . "<br>";
		} else {
			$lib_title 	= esc_html__('Please Install / Activate SOAP Extension.','tourmaster') . "<br>";
		}
		if (extension_loaded('simplexml')) {
			$lib_title .= esc_html__('SimpleXML Extension active.','tourmaster');
		} else {
			$lib_title .= esc_html__('Please Install / Activate SimpleXML Extension.','tourmaster');
		}

		$options['hipayprofessional'] = array(
			'title' => esc_html__('Hipay Professional', 'tourmaster'),
			'options' => array(
				'hipayprofessional-live-mode' => array(
					'title'	 		=> __('Hipay Live Mode', 'tourmaster'),
					'type' 			=> 'checkbox',
					'default' 		=> 'disable',
					'description' 	=> esc_html__('Disable this option to test on sandbox mode.', 'tourmaster')
				),
				'hipayprofessional-merchant-login' => array(
					'title' 		=> esc_html__('Hipay Webservice Login', 'tourmaster'),
					'type' 			=> 'text'
				),
				'hipayprofessional-merchant-password' => array(
					'title' 		=> esc_html__('Hipay Webservice Password', 'tourmaster'),
					'type' 			=> 'text',	
				),
				'hipayprofessional-website' => array(
					'title' 		=> esc_html__('Hipay Website ID', 'tourmaster'),
					'type' 			=> 'text',	
				),
				'hipayprofessional-website-category' => array(
					'title' 		=> esc_html__('Hipay Website Category', 'tourmaster'),
					'type' 			=> 'text',	
					'description' 	=> esc_html__('Replace website_id in one of the following URLs, according the platform you are using, choosing one of the categories ID.', 'tourmaster') . '<br><br>' . esc_html__('Live Platform: https://payment.hipay.com/order/list-categories/id/website_id', 'tourmaster') . '<br><br>' . esc_html__('Sandbox Platform: https://test-payment.hipay.com/order/list-categories/id/website_id', 'tourmaster')
				),
				'hipayprofessional-website-shopid' => array(
					'title' 		=> esc_html__('Hipay Website Shop ID', 'tourmaster'),
					'type' 			=> 'text',	
					'description' 	=> esc_html__('If you have a shop associated with your Website, please provide the Shop ID.', 'tourmaster') 
				),
				'hipayprofessional-website-logo' => array(
					'title' 		=> esc_html__('Logo URL', 'tourmaster'),
					'type' 			=> 'text',	
					'description' 	=> esc_html__('Absolute link to an image that is shown in Hipay payment window.', 'tourmaster')
				),
				'hipayprofessional-email-notification' => array(
					'title' 		=> esc_html__('Technical Email', 'tourmaster'),
					'type' 			=> 'text',	
					'description' 	=> esc_html__('Receive the result of callback notifications on this email address.', 'tourmaster')
				),
				'hipayprofessional-website-rating' => array(
					'title' 		=> esc_html__('Hipay Website Rating', 'tourmaster'),
					'type' 			=> 'text',	
					'default' 		=> 'ALL',
					'description' 	=> esc_html__('Choose between: ALL, +18, +16 or +12.', 'tourmaster')
				),
				'hipayprofessional-currency-code' => array(
					'title' 		=> esc_html__('Hipay Account Currency', 'tourmaster'),
					'type' 			=> 'text',	
					'default' 		=> 'EUR',
					'description'	=> esc_html__('3 digit currency code. Example: EUR, USD, etc.', 'tourmaster')
				),
				'hipayprofessional-min-amount' => array(
					'title' 		=> esc_html__('Minimum amount', 'tourmaster'),
					'type' 			=> 'text',	
					'default' 		=> '2',
					'description' 	=> esc_html__('Minimum amount to use Hipay as payment method.', 'tourmaster')
				),
				'hipayprofessional-max-amount' => array(
					'title' 		=> esc_html__('Maximum amount', 'tourmaster'),
					'type' 			=> 'text',	
					'default' 		=> '2500',
					'description' 	=> esc_html__('Maximum amount to use Hipay as payment method.', 'tourmaster')
				),
				'hipayprofessional-libs' => array(
					'title' 		=> $lib_title,
					'type' 			=> 'select',
					'description' 	=> esc_html__('Hipay uses SOAP to generate payments and SimpleXML to process payments notifications.','tourmaster')
				),
			)
		);
		return $options;
	} 
}


/*
* PAYMENT WINDOW REDIRECTION
*/

add_filter('goodlayers_hipayprofessional_payment_form', 'tourmaster_hipayprofessional_payment_url', 10, 2);
if( !function_exists('tourmaster_hipayprofessional_payment_url') ){
	function tourmaster_hipayprofessional_payment_url( $ret = '', $tid = '' ){
		
		$live_mode 			= tourmaster_get_option('payment', 'hipayprofessional-live-mode', 'disable');
		$username 			= tourmaster_get_option('payment', 'hipayprofessional-merchant-login', '');
		$password 			= tourmaster_get_option('payment', 'hipayprofessional-merchant-password', '');
		$website 			= tourmaster_get_option('payment', 'hipayprofessional-website', '');
		$website_category 	= tourmaster_get_option('payment', 'hipayprofessional-website-category', '');
		$website_rating 	= tourmaster_get_option('payment', 'hipayprofessional-website-rating', '');
		$website_currency 	= tourmaster_get_option('payment', 'hipayprofessional-currency-code', '');
		$website_logo 		= tourmaster_get_option('payment', 'hipayprofessional-website_logo', '');
		$website_shop 		= tourmaster_get_option('payment', 'hipayprofessional-website-shopid', '');
		$technical_email	= tourmaster_get_option('payment', 'hipayprofessional-email-notification', '');

		$tourmaster_data 	= apply_filters('goodlayers_payment_get_transaction_data', array(), $tid, array('price', 'tour_id','email'));
		$t_data 			= apply_filters('goodlayers_payment_get_transaction_data', array(), $tid, array('price', 'tour_id'));

		$amount = '';
		if( $tourmaster_data['price']['deposit-price'] ){
			$amount = $tourmaster_data['price']['deposit-price'];
		}else{
			$amount = $tourmaster_data['price']['total-price'];
		}
		//$amount 	= floatval($tourmaster_data['price']);

		$customerEmail		= $tourmaster_data['email'];
		$ws_url 			= ($live_mode != "disable") ? "https://ws.hipay.com/soap/payment-v2/generate?wsdl" : "https://test-ws.hipay.com/soap/payment-v2/generate?wsdl";
		$language 			= get_locale();
		$ip 				= $_SERVER['REMOTE_ADDR'];
		$currentDate 		= date('Y-m-dTH:i:s');

		try
		{
			$client = new SoapClient($ws_url);
			$parameters = new stdClass(); 
			$parameters->parameters = array(
				'wsLogin' 			=> $username,
				'wsPassword' 		=> $password,
				'websiteId' 		=> $website,
				'categoryId' 		=> $website_category,
				'currency' 			=> $website_currency,
				'amount' 			=> $amount,
				'rating' 			=> $website_rating,
				'locale' 			=> $language,
				'customerIpAddress' => $ip,
				'merchantReference' => $tid,
				'description' 		=> "#" . $tid . $customerEmail,
				'executionDate' 	=> $currentDate,
				'manualCapture' 	=> 0,
				'customerEmail' 	=> $customerEmail,
				'merchantComment' 	=> '',
				'emailCallback' 	=> $technical_email,
				'urlCallback' 		=> add_query_arg(array('tourmaster_hipayprofessional'=>$tid), home_url('/')),
				'urlAccept' 		=> add_query_arg(array('tid' => $tid, 'step' => 4, 'payment_method' => 'hipayprofessional'), tourmaster_get_template_url('payment')),
				'urlDecline' 		=> add_query_arg(array(), tourmaster_get_template_url('payment')),
				'urlCancel' 		=> add_query_arg(array(), tourmaster_get_template_url('payment')),
				'urlLogo' 			=> $website_logo,
			);					

			if ($website_shop != "") $parameters->parameters["shopId"] = $website_shop;
			$result = $client->generate($parameters);

		}
		catch (Exception $e){
			$result->generateResult->code 			= -1;
			$result->generateResult->description 	= $e->getMessage();
		}
		
		ob_start();
		if ($result->generateResult->code == 0) {
		?>
			<input type="hidden" value="<?php echo $result->generateResult->redirectUrl;?>" id="hipayprofessional_url" name="hipayprofessional_url" >
			<div class="goodlayers-hipayprofessional-redirecting-message" ><?php esc_html_e('Please wait while we redirect you to Hipay payment page.', 'tourmaster') ?></div>
			<script type="text/javascript">
				(function($){
					document.location = $("#hipayprofessional_url").val();
				})(jQuery);
			</script>

		<?php
		} else {
		?>
			<div class="goodlayers-hipayprofessional-redirecting-message" ><?php esc_html_e('There was an error generating the payment. Please refresh the page and try again.', 'tourmaster') ?></div>
		<?php				
		}
		$ret = ob_get_contents();
		ob_end_clean();

		return $ret;

	} 
}

/*
* FILTERS
*/
add_filter('tourmaster_hipayprofessional_button_atts', 'tourmaster_hipayprofessional_button_attribute');
if( !function_exists('tourmaster_hipayprofessional_button_attribute') ){
	function tourmaster_hipayprofessional_button_attribute( $attributes ){
		$min_amount	= tourmaster_get_option('payment', 'hipayprofessional-min-amount', '1');
		$max_amount	= tourmaster_get_option('payment', 'hipayprofessional-max-amount', '2500');
		return array('method' => 'ajax', 'type' => 'hipayprofessional', 'min_amount' => $min_amount, 'max_amount' => $max_amount);
	}
}

/*
* CALLBACK NOTIFICATION PROCESSING
*/
add_action('init', 'tourmaster_hipayprofessional_process_callback');
if( !function_exists('tourmaster_hipayprofessional_process_callback') ){
	function tourmaster_hipayprofessional_process_callback(){

		if( isset($_GET['tourmaster_hipayprofessional']) ){

			$payment_info = array(
				'payment_method' => 'hipayprofessional'
			);

			$live_mode = tourmaster_get_option('payment', 'hipayprofessional-live-mode', '');
			if( empty($live_mode) || $live_mode == 'disable' ){
				$hipayprofessional_action_url = "https://test-ws.hipay.com/soap/transaction-v2?wsdl";
			}else{
				$hipayprofessional_action_url = "https://ws.hipay.com/soap/transaction-v2?wsdl";
			}

				$xml = $_POST['xml'];

				$operation = '';
				$status = '';
				$date = '';
				$time = '';
				$transid = '';
				$origAmount = '';
				$origCurrency = '';
				$idformerchant = '';
				$merchantdatas = array();
				$ispayment = true;

				$xml = trim($xml);
				$xml_count = strpos($xml,"<mapi>");
				$xml_len = strlen($xml);
				$xml = substr($xml,$xml_count,$xml_len - $xml_count);

				$obj = new SimpleXMLElement($xml);
				if (isset($obj->result[0]->operation))
					$operation=$obj->result[0]->operation;
				else
					$ispayment =  false;

				if (isset($obj->result[0]->status))
					$status=$obj->result[0]->status;
				else 
					$ispayment =  false;

				if (isset($obj->result[0]->date))
					$date=$obj->result[0]->date;
				else 
					$ispayment =  false;

				if (isset($obj->result[0]->time))
					$time=$obj->result[0]->time;
				else 
					$ispayment =  false;

				if (isset($obj->result[0]->transid))
					$transid=(string)$obj->result[0]->transid;
				else 
					$ispayment =  false;

				if (isset($obj->result[0]->origAmount))
					$origAmount=(string)$obj->result[0]->origAmount;
				else 
					$ispayment =  false;

				if (isset($obj->result[0]->origCurrency))
					$origCurrency=(string)$obj->result[0]->origCurrency;
				else 
					$ispayment = false;

				if (isset($obj->result[0]->idForMerchant))
					$idformerchant=$obj->result[0]->idForMerchant;
				else 
					$ispayment =  false;


				if ($status=="ok" && $operation=="capture") {

					$client 	= new SoapClient($hipayprofessional_action_url);
					$username 	= tourmaster_get_option('payment', 'hipayprofessional-merchant-login', '');
					$password 	= tourmaster_get_option('payment', 'hipayprofessional-merchant-password', '');

					$parameters = new stdClass(); 
					$parameters->parameters = array(
						'wsLogin' => $username,
						'wsPassword' => $password,
						'transactionPublicId' => $transid
					);					

					$result = $client->getDetails($parameters);
					
					if ($result->getDetailsResult->code == "0" && $result->getDetailsResult->amount == $origAmount && $result->getDetailsResult->currency == $origCurrency && strtolower($result->getDetailsResult->transactionStatus) == "captured"){
	
			        	$tid = $idformerchant;
			        	$payment_info['transaction_id'] = $transid;
			        	$payment_info['amount'] = $origAmount;

			        	$tdata = tourmaster_get_booking_data(array('id'=>$tid), array('single'=>true));
						
						if( tourmaster_compare_price($tdata->total_price, $payment_info['amount']) ){
							$order_status = 'online-paid';
						} else {
							$order_status = 'pending';
						}

			        	tourmaster_update_booking_data( 
							array(
								'payment_info' => json_encode($payment_info),
								'payment_date' => current_time('mysql'),
								'order_status' => $order_status,
							),
							array('id' => $tid),
							array('%s', '%s', '%s'),
							array('%d')
						);

						tourmaster_mail_notification('payment-made-mail', $tid);
						tourmaster_mail_notification('admin-online-payment-made-mail', $tid);


					} 	

				} elseif ($status=="nok" || $operation == "cancellation" || $status == "cancel") {

		            $payment_info['error'] = $operation . " " . $status;
	            	$tid = $idformerchant;
					tourmaster_update_booking_data( 
						array(
							'payment_info' => json_encode($payment_info),
						),
						array('id' => $tid, 'payment_date' => '0000-00-00 00:00:00'),
						array('%s'),
						array('%d', '%s')
					);		            	
				}

	        exit;

		}

	} 
}
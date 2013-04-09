#!/usr/bin/php
<?php

# Sandbox
$host = 'https://api.sandbox.paypal.com';
$clientId = 'EOJ2S-Z6OoN_le_KS1d75wsZ6y0SFdVsY9183IvxFyZp';
$clientSecret = 'EClusMEUk8e9ihI7ZdVLF5cZ6y0SFdVsY9183IvxFyZp';

$token = '';
// function to read stdin
function read_stdin() {
        $fr=fopen("php://stdin","r");   // open our file pointer to read from stdin
        $input = fgets($fr,128);        // read a maximum of 128 characters
        $input = rtrim($input);         // trim any trailing spaces.
        fclose ($fr);                   // close the file handle
        return $input;                  // return the text entered
}

function get_access_token($url, $postdata) {
	global $clientId, $clientSecret;
	$curl = curl_init($url); 
	curl_setopt($curl, CURLOPT_POST, true); 
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_USERPWD, $clientId . ":" . $clientSecret);
	curl_setopt($curl, CURLOPT_HEADER, false); 
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
	curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata); 
#	curl_setopt($curl, CURLOPT_VERBOSE, TRUE);
	$response = curl_exec( $curl );
	if (empty($response)) {
	    // some kind of an error happened
	    die(curl_error($curl));
	    curl_close($curl); // close cURL handler
	} else {
	    $info = curl_getinfo($curl);
		echo "Time took: " . $info['total_time']*1000 . "ms\n";
	    curl_close($curl); // close cURL handler
		if($info['http_code'] != 200 && $info['http_code'] != 201 ) {
			echo "Received error: " . $info['http_code']. "\n";
			echo "Raw response:".$response."\n";
			die();
	    }
	}

	// Convert the result from JSON format to a PHP array 
	$jsonResponse = json_decode( $response );
	return $jsonResponse->access_token;
}

function make_post_call($url, $postdata) {
	global $token;
	$curl = curl_init($url); 
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
				'Authorization: Bearer '.$token,
				'Accept: application/json',
				'Content-Type: application/json'
				));
	
	curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata); 
	#curl_setopt($curl, CURLOPT_VERBOSE, TRUE);
	$response = curl_exec( $curl );
	if (empty($response)) {
	    // some kind of an error happened
	    die(curl_error($curl));
	    curl_close($curl); // close cURL handler
	} else {
	    $info = curl_getinfo($curl);
		echo "Time took: " . $info['total_time']*1000 . "ms\n";
	    curl_close($curl); // close cURL handler
		if($info['http_code'] != 200 && $info['http_code'] != 201 ) {
			echo "Received error: " . $info['http_code']. "\n";
			echo "Raw response:".$response."\n";
			die();
	    }
	}

	// Convert the result from JSON format to a PHP array 
	$jsonResponse = json_decode($response, TRUE);
	return $jsonResponse;
}

function make_get_call($url) {
	global $token;
	$curl = curl_init($url); 
	curl_setopt($curl, CURLOPT_POST, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
				'Authorization: Bearer '.$token,
				'Accept: application/json',
				'Content-Type: application/json'
				));
	
	#curl_setopt($curl, CURLOPT_VERBOSE, TRUE);
	$response = curl_exec( $curl );
	if (empty($response)) {
	    // some kind of an error happened
	    die(curl_error($curl));
	    curl_close($curl); // close cURL handler
	} else {
	    $info = curl_getinfo($curl);
		echo "Time took: " . $info['total_time']*1000 . "ms\n";
	    curl_close($curl); // close cURL handler
		if($info['http_code'] != 200 && $info['http_code'] != 201 ) {
			echo "Received error: " . $info['http_code']. "\n";
			echo "Raw response:".$response."\n";
			die();
	    }
	}

	// Convert the result from JSON format to a PHP array 
	$jsonResponse = json_decode($response, TRUE);
	return $jsonResponse;
}

echo "\n";
echo "###########################################\n";
echo "Obtaining OAuth2 Access Token.... \n";
$url = $host.'/v1/oauth2/token'; 
$postArgs = 'grant_type=client_credentials';
$token = get_access_token($url,$postArgs);
echo "Got OAuth Token: ".$token;
echo "\n \n";
echo "###########################################\n";
echo "Making a Credit Card Payment... \n";
$url = $host.'/v1/payments/payment';
$payment = array(
		'intent' => 'sale',
		'payer' => array(
			'payment_method' => 'credit_card',
			'funding_instruments' => array ( array(
					'credit_card' => array (
						'number' => '5500005555555559',
						'type'   => 'mastercard',
						'expire_month' => 12,
						'expire_year' => 2018,
						'cvv2' => 111,
						'first_name' => 'Manoj',
						'last_name' => 'Pali',
						"billing_address": {
                                                  "line1": "12 N Main ST",
            					  "city": "NewYork",
            					  "state": "NY",
            					  "postal_code": "11001",
            					  "country_code": "US"
						
						)
					))
			),
		'transactions' => array (array(
				'amount' => array(
					'total' => '7.47',
					'currency' => 'USD'
					),
				'description' => 'payment by a credit card using a test script'
				))
		);
$json = json_encode($payment);
$json_resp = make_post_call($url, $json);
foreach ($json_resp['links'] as $link) {
	if($link['rel'] == 'self'){
		$payment_detail_url = $link['href'];
		$payment_detail_method = $link['method'];
	}
}
$related_resource_count = 0;
$related_resources = "";
foreach ($json_resp['transactions'] as $transaction) {
	if($transaction['related_resources']) {
		$related_resource_count = count($transaction['related_resources']);
		foreach ($transaction['related_resources'] as $related_resource) {
			if($related_resource['sale']){
				$related_resources = $related_resources."sale ";
				$sale = $related_resource['sale'];
				foreach ($sale['links'] as $link) {
					if($link['rel'] == 'self'){
						$sale_detail_url = $link['href'];
						$sale_detail_method = $link['method'];
					}else if($link['rel'] == 'refund'){
						$refund_url = $link['href'];
						$refund_method = $link['method'];
					}
				}
			} else if($related_resource['refund']){
				$related_resources = $related_resources."refund";
			}	
		}
	}
}

echo "Payment Created successfully: " . $json_resp['id'] ." with state '". $json_resp['state']."'\n";
echo "Payment related_resources:". $related_resource_count . "(". $related_resources.")";
echo "\n \n";
echo "###########################################\n";
echo "Obtaining Payment Details... \n";
$json_resp = make_get_call($payment_detail_url);
echo "Payment details obtained for: " . $json_resp['id'] ." with state '". $json_resp['state']. "'";
echo "\n \n";
echo "###########################################\n";
echo "Obtaining Sale details...\n";
$json_resp = make_get_call($sale_detail_url);
echo "Sale details obtained for: " . $json_resp['id'] ." with state '". $json_resp['state']."'";
echo "\n \n";
echo "###########################################\n";
echo "Refunding a Sale... \n";
$refund = array(
		'amount' => array(
			'total' => '7.47',
			'currency' => 'USD'
			)
	       );
$json = json_encode($refund);
$json_resp = make_post_call($refund_url, $json);
echo "Refund processed " . $json_resp['id'] ." with state '". $json_resp['state']."'";
echo "\n \n";
echo "###########################################\n";
echo "Obtaining Sale details...\n";
$json_resp = make_get_call($sale_detail_url);
echo "Sale details obtained for: " . $json_resp['id'] ." with state '". $json_resp['state']."'";
echo "\n \n";
echo "###########################################\n";
echo "Obtaining Payment Details... \n";
$json_resp = make_get_call($payment_detail_url);
$related_resource_count = 0;
$related_resources = "";
foreach ($json_resp['transactions'] as $transaction) {
	if($transaction['related_resources']) {
		$related_resource_count = count($transaction['related_resources']);
		foreach ($transaction['related_resources'] as $related_resource) {
			if($related_resource['sale']){
				$related_resources = $related_resources."sale ";
			} else if($related_resource['refund']){
				$related_resources = $related_resources."refund";
			}
		}

	}
}

echo "Payment details obtained for: " . $json_resp['id'] ." with state '". $json_resp['state']. "' \n";
echo "Payment related_resources:". $related_resource_count . "(". $related_resources.")";
echo "\n \n";
echo "###########################################\n";
echo "Saving a Credit Card in vault... \n";
$url = $host.'/v1/vault/credit-card';
$creditcard = array(
		'payer_id' => 'testuser@yahoo.com',
		'number' => '4417119669820331',
		'type'   => 'visa',
		'expire_month' => 11,
		'expire_year' => 2018,
		'first_name' => 'John',
		'last_name' => 'Doe'
		);
$json = json_encode($creditcard);
$json_resp = make_post_call($url, $json);
$credit_card_id = $json_resp['id'];
echo "Credit Card saved ".$credit_card_id." with state '".$json_resp['state']."'";
echo "\n \n";
echo "###########################################\n";
echo "Making a Payment with saved credit card... \n";
$url = $host.'/v1/payments/payment';
$payment = array(
                'intent' => 'sale',
                'payer' => array(
                        'payment_method' => 'credit_card',
                        'funding_instruments' => array ( array(
                                        'credit_card_token' => array (
                                                'credit_card_id' => $credit_card_id,
                                                'payer_id' => 'testuser@yahoo.com'
                                                )
                                        ))
                        ),
                'transactions' => array (array(
                                'amount' => array(
                                        'total' => '7.47',
                                        'currency' => 'USD'
                                        ),
                                'description' => 'payment using a saved card'
                                ))
                );
$json = json_encode($payment);
$json_resp = make_post_call($url, $json);
echo "Payment Created successfully: " . $json_resp['id'] ." with state '". $json_resp['state']."'\n";
echo "\n \n";
echo "###########################################\n";
echo "Obtaining all Payments (list) ... \n";
$payment_list_url = $host.'/v1/payments/payment';
$json_resp = make_get_call($payment_list_url);
echo "Number of Payment resources returned: " . count($json_resp['payments']);
$counter = 0;
foreach ($json_resp['payments'] as $payment) {
	echo "\n" . $counter++ . ". " . $payment['id'];
}
echo "\nNext Payment ID: ". $json_resp['next_id'];
echo "\nObtaining subset (2-4) of the Payments ... \n";
$payment_list_url = $host.'/v1/payments/payment?start_index=1&count=3';
$json_resp = make_get_call($payment_list_url);
echo "Number of Payment resources returned: " . count($json_resp['payments']);
$counter = 0;
foreach ($json_resp['payments'] as $payment) {
        echo "\n" . $counter++ . ". " . $payment['id'];
}
echo "\nNext Payment ID: ". $json_resp['next_id'];
echo "\nObtaining the next 10 starting from the previous next_id ... \n";
$payment_list_url = $host.'/v1/payments/payment?start_id='.$json_resp['next_id'];
$json_resp = make_get_call($payment_list_url);
echo "Number of Payment resources returned: " . count($json_resp['payments']);
$counter = 0;
foreach ($json_resp['payments'] as $payment) {
        echo "\n" . $counter++ . ". " . $payment['id'];
}
echo "\n \n";
echo "###########################################\n";
echo "Initiating a Payment with PayPal Account... \n";
$url = $host.'/v1/payments/payment';
$payment = array(
                'intent' => 'sale',
                'payer' => array(
                        'payment_method' => 'paypal'
		),
                'transactions' => array (array(
                                'amount' => array(
                                        'total' => '7.47',
                                        'currency' => 'USD'
                                        ),
                                'description' => 'payment using a PayPal account'
                                )),
		'redirect_urls' => array (
			'return_url' => 'http://www.return.com/?test=123',
			'cancel_url' => 'http://www.cancel.com'
		)
                );
$json = json_encode($payment);
$json_resp = make_post_call($url, $json);
foreach ($json_resp['links'] as $link) {
	if($link['rel'] == 'execute'){
		$payment_execute_url = $link['href'];
		$payment_execute_method = $link['method'];
	} else 	if($link['rel'] == 'approval_url'){
			$payment_approval_url = $link['href'];
			$payment_approval_method = $link['method'];
		}
}
echo "Payment Created successfully: " . $json_resp['id'] ." with state '". $json_resp['state']."'\n\n";
echo "Please goto ".$payment_approval_url." in your browser and approve the payment with a PayPal Account.\n";
echo "Enter PayerId from the return url to continue:";
$payerId = read_stdin();
echo "\n \n";
echo "###########################################\n";
echo "Executing the PayPal Payment for PayerId (".$payerId.")... \n";
$payment_execute = array(
		'payer_id' => $payerId
	       );
$json = json_encode($payment_execute);
$json_resp = make_post_call($payment_execute_url, $json);
echo "Payment Execute processed " . $json_resp['id'] ." with state '". $json_resp['state']."'";
echo "\n \n";


?>

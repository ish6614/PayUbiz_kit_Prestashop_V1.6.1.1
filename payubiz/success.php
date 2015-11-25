<?php  
 
		include(dirname(__FILE__).'/../../config/config.inc.php');
		include(dirname(__FILE__).'/../../init.php');
		include(dirname(__FILE__).'/payubiz.php');
		include(dirname(__FILE__).'/../../header.php');
		include(dirname(__FILE__).'/payubiz_common.inc');

		// require_once dirname(__FILE__).'/../../classes/order/Order.php';
  //       require_once dirname(__FILE__).'/../../classes/order/OrderHistory.php';
			
		 $payu = new payubiz();
		 
		 $response=$_REQUEST;
		             
		 $key=Configuration::get('PAYUBIZ_MERCHANT_ID');
	  	 $salt=Configuration::get('PAYUBIZ_MERCHANT_SALT');

		 $log=Configuration::get('PAYUBIZ_LOGS');

		

		 $baseUrl=Tools::getShopDomain(true, true).__PS_BASE_URI__;	
		 $order_id= $response['txnid']-40000;
		 $transactionId= $response['mihpayid'];		 
		  
		 $smarty->assign('baseUrl',$baseUrl);
		 $smarty->assign('orderId',$order_id);
		 $smarty->assign('transactionId',$transactionId);

		 $amount        = $response['amount'];
		 $productinfo   = $response['productinfo'];
		 $firstname     = $response['firstname'];;
		 $email         = $response['email'];

		 if($response['status'] == 'success' || $response['status'] == 'in progress')
		 {
			
			    $Udf1 = $response['udf1'];
		 		$Udf2 = $response['udf2'];
		 		$Udf3 = $response['udf3'];
		 		$Udf4 = $response['udf4'];
		 		$Udf5 = $response['udf5'];
		 		$Udf6 = $response['udf6'];
		 		$Udf7 = $response['udf7'];
		 		$Udf8 = $response['udf8'];
		 		$Udf9 = $response['udf9'];
		 		$Udf10 = $response['udf10'];
			 
		  $txnid=$response['txnid'];
		  $keyString =  $key.'|'.$txnid.'|'.$amount.'|'.$productinfo.'|'.$firstname.'|'.$email.'|'.$Udf1.'|'.$Udf2.'|'.$Udf3.'|'.$Udf4.'|'.$Udf5.'|'.$Udf6.'|'.$Udf7.'|'.$Udf8.'|'.$Udf9.'|'.$Udf10;
		  
	      $keyArray = explode("|",$keyString);
		  $reverseKeyArray = array_reverse($keyArray);
		  $reverseKeyString=implode("|",$reverseKeyArray);			 
			 
					
			
			 $status=$response['status'];
			 $saltString     = $salt.'|'.$status.'|'.$reverseKeyString;
			 $sentHashString = strtolower(hash('sha512', $saltString));               
			
			 $responseHashString=$_REQUEST['hash'];

			 if($sentHashString != $responseHashString)
			 {			
				$history = new OrderHistory();
				$history->id_order = (int)($order_id);
				$history->changeIdOrderState(Configuration::get('PS_OS_ERROR'), $history->id_order);
				$history->add();				
				$smarty->display('failure.tpl');
             }
			 else
			 {			
				global $cart,$cookie;
				$total = $amount;
				$currency = new Currency(Tools::getValue('currency_payement', false) ? Tools::getValue('currency_payement') : $cookie->id_currency);
				$customer = new Customer((int)$cart->id_customer);

				if($response['status'] == 'success')
				{
					
                   $payu->validateOrder((int)$cart->id, Configuration::get('PS_OS_PAYMENT'), $total, $payu->displayName, NULL, NULL, (int)$currency->id, false, $customer->secure_key);

                   $result = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'orders WHERE id_cart = ' . (int)$cart->id);                 

                    //$objOrder = new Order($result['id_order']); //order with id=1
					// $history = new OrderHistory();
					// $history->id_order = (int)$objOrder->id;

					// $objOrder->setCurrentState(Configuration::get('PS_OS_PAYMENT')); 

					// $i = $history->changeIdOrderState(Configuration::get('PS_OS_PAYMENT'), (int)($objOrder->id)); //order status=3  
					
					                   }            
				else
				{			

				   $payu->validateOrder((int)$cart->id, Configuration::get('PS_OS_PREPARATION'), $total, $payu->displayName, NULL, NULL, (int)$currency->id, false, $customer->secure_key);

				   $result = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'orders WHERE id_cart = ' . (int)$cart->id);

				 //   $objOrder = new Order($result['id_order']); //order with id=1
					// $history = new OrderHistory();
					// $history->id_order = (int)$objOrder->id;

					// $objOrder->setCurrentState(Configuration::get('PS_OS_PREPARATION')); 

					// $history->changeIdOrderState(Configuration::get('PS_OS_PREPARATION'), (int)($objOrder->id)); //order status=3 
				}
				$smarty->display('success.tpl');

				$result = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'orders WHERE id_cart = ' . (int)$cart->id);

		 if($log==1 )	
			 {
			 	pblog( 'payubiz Data: '. print_r( $response, true ) );
			   $responseValue= str_replace( "'"," ",implode(",",$response));
			   $successQuery="update ps_payubiz_order set payment_response='$responseValue', payment_method= '".$response["payment_source"]."', payment_status= '".$response["status"]."', id_order='".$result["id_order"]."'  where id_transaction= ".$response['txnid'];
			   Db::getInstance()->Execute($successQuery);
			 }	

            Tools::redirectLink(__PS_BASE_URI__ . 'order-detail.php?id_order=' . $result['id_order']);

			 }			
		  }		
		
           include(dirname(__FILE__).'/../../footer.php');	
?>

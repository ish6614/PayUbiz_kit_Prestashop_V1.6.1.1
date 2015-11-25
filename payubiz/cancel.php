<?php 
 
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');
include(dirname(__FILE__).'/payubiz.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/payubiz_common.inc');

$payu = new payubiz();
$response=$_REQUEST;


$id_order=$response['txnid']-9410;  // if test enviroment work



$baseUrl=Tools::getShopDomain(true, true).__PS_BASE_URI__;	
$smarty->assign('baseUrl',$baseUrl);
$smarty->assign('orderId',$id_order);
$amount        = $response['amount'];

global $cart,$cookie;

$total = $amount;
$currency = new Currency(Tools::getValue('currency_payement', false) ? Tools::getValue('currency_payement') : $cookie->id_currency);
$customer = new Customer((int)$cart->id_customer);
$payu->validateOrder((int)$cart->id, _PS_OS_CANCELED_, $total, $payu->displayName, NULL, NULL, (int)$currency->id, false, $customer->secure_key);

$smarty->display('cancel.tpl');

$result = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'orders WHERE id_cart = ' . (int)$cart->id);

$log=Configuration::get('PAYU_LOGS');
				
				if($log==1 )	
				{
				     
					pblog( 'payubiz Data: '. print_r( $response, true ) );
				}	

Tools::redirectLink(__PS_BASE_URI__ . 'order-detail.php?id_order=' . $result['id_order']);

include(dirname(__FILE__).'/../../footer.php');
?>
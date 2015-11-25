<?php
/**
 * payubiz.php
 *
 * Copyright (c) 2015 PayU india
 * 
 * 
 * @author     Ayush Mittal
 * @version    1.0
 * @date       30/10/2015
 * 
 * @copyright  2015 PayU india
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       https://www.payubiz.in/index
 */

if (!defined('_PS_VERSION_'))
    exit;

class PayUbiz extends PaymentModule
{
    const LEFT_COLUMN = 0;
    const RIGHT_COLUMN = 1;
    const FOOTER = 2;
    const DISABLE = -1;
    const SANDBOX_MERCHANT_SALT = 'eCwWELxi';
    const SANDBOX_MERCHANT_ID = 'gtKFFx';
    
    public function __construct()
    {
        $this->name = 'payubiz';
        $this->tab = 'payments_gateways';
        $this->version = '2.0';  
        $this->currencies = true;
        $this->currencies_mode = 'radio';
        
        parent::__construct();       
       
        $this->author  = 'PayU India';
        $this->page = basename(__FILE__, '.php');

        $this->displayName = $this->l('PayUbiz');
        $this->description = $this->l('Boost Your Sales With Market Leading Payment Gateway');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details ?');
 
        

        /* For 1.4.3 and less compatibility */
        $updateConfig = array('PS_OS_CHEQUE' => 1, 'PS_OS_PAYMENT' => 2, 'PS_OS_PREPARATION' => 3, 'PS_OS_SHIPPING' => 4, 'PS_OS_DELIVERED' => 5, 'PS_OS_CANCELED' => 6,
                      'PS_OS_REFUND' => 7, 'PS_OS_ERROR' => 8, 'PS_OS_OUTOFSTOCK' => 9, 'PS_OS_BANKWIRE' => 10, 'PS_OS_PAYPAL' => 11, 'PS_OS_WS_PAYMENT' => 12);
        foreach ($updateConfig as $u => $v)
            if (!Configuration::get($u) || (int)Configuration::get($u) < 1)
            {
                if (defined('_'.$u.'_') && (int)constant('_'.$u.'_') > 0)
                    Configuration::updateValue($u, constant('_'.$u.'_'));
                else
                    Configuration::updateValue($u, $v);
            }

    }

    public function install()
    {
        unlink(dirname(__FILE__).'/../../cache/class_index.php');
        if ( !parent::install() 
            OR !$this->registerHook('payment') 
            OR !$this->registerHook('paymentReturn') 
            OR !Configuration::updateValue('PAYUBIZ_MERCHANT_ID', '') 
            OR !Configuration::updateValue('PAYUBIZ_MERCHANT_SALT', '') 
            OR !Configuration::updateValue('PAYUBIZ_LOGS', '1') 
            OR !Configuration::updateValue('PAYUBIZ_MODE', 'test')
            OR !Configuration::updateValue('PAYUBIZ_PAYNOW_TEXT', 'Pay Now With')
            OR !Configuration::updateValue('PAYUBIZ_PAYNOW_LOGO', 'on')  
            OR !Configuration::updateValue('PAYUBIZ_PAYNOW_ALIGN', 'right')
              )
        {            
            return false;
        }

        if (!Db::getInstance()->Execute('CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'payubiz_order` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `id_order` int(10) unsigned NOT NULL,
          `id_transaction` varchar(255) NOT NULL,
          `payment_method` int(10) unsigned NOT NULL,
          `payment_status` varchar(255) NOT NULL,
          `order_date` timestamp default now(),
           `payment_request` text,
           `payment_response` text,
          PRIMARY KEY (`id`)
        ) ENGINE='._MYSQL_ENGINE_.'  DEFAULT CHARSET=utf8'))
            

        return true;
    }

    public function uninstall()
    {
        unlink(dirname(__FILE__).'/../../cache/class_index.php');
        return ( parent::uninstall() 
            AND Configuration::deleteByName('PAYUBIZ_MERCHANT_ID') 
            AND Configuration::deleteByName('PAYUBIZ_MERCHANT_SALT') 
            AND Configuration::deleteByName('PAYUBIZ_MODE') 
            AND Configuration::deleteByName('PAYUBIZ_LOGS')
            AND Configuration::deleteByName('PAYUBIZ_PAYNOW_TEXT') 
            AND Configuration::deleteByName('PAYUBIZ_PAYNOW_LOGO')            
            AND Configuration::deleteByName('PAYUBIZ_PAYNOW_ALIGN')
           
            );

    }

    public function getContent()
    {
        global $cookie;
        $errors = array();
        $html = '<div style="width:550px">
            <p style="text-align:center;"><a href="https://www.payubiz.in" target="_blank"><img src="'.__PS_BASE_URI__.'modules/payubiz/payu_logo_pack.png" alt="PayUbiz" boreder="0" /></a></p><br />';

             

        /* Update configuration variables */
        if ( Tools::isSubmit( 'submitPAYUBIZ' ) )
        {
            if( $paynow_text =  Tools::getValue( 'payubiz_paynow_text' ) )
            {
                 Configuration::updateValue( 'PAYUBIZ_PAYNOW_TEXT', $paynow_text );
            }

            if( $paynow_logo =  Tools::getValue( 'payubiz_paynow_logo' ) )
            {
                 Configuration::updateValue( 'PAYUBIZ_PAYNOW_LOGO', $paynow_logo );
            }
            if( $paynow_align =  Tools::getValue( 'payubiz_paynow_align' ) )
            {
                 Configuration::updateValue( 'PAYUBIZ_PAYNOW_ALIGN', $paynow_align );
            }
            if( $merchant_id =  Tools::getValue( 'payubiz_merchant_id' ) )
            {
                 Configuration::updateValue( 'PAYUBIZ_MERCHANT_ID', $merchant_id );
            }

             if( $merchant_salt =  Tools::getValue( 'payubiz_merchant_salt' ) )
            {
                 Configuration::updateValue( 'PAYUBIZ_MERCHANT_SALT', $merchant_salt );
            }

             if( $payubiz_pg =  Tools::getValue( 'pg' ) )
                {
                     Configuration::updateValue( 'PAYUBIZ_PG', $payubiz_pg );
                }

              if( $payubiz_bankcode =  Tools::getValue( 'bankcode' ) )
                {
                     Configuration::updateValue( 'PAYUBIZ_BANKCODE', $payubiz_bankcode );
                } 

             if( $payubiz_mode =  Tools::getValue( 'payubiz_mode' ) )
                {
                     Configuration::updateValue( 'PAYUBIZ_MODE', $payubiz_mode );
                }

            if( Tools::getValue( 'payubiz_logs' ) )
            {
                Configuration::updateValue( 'PAYUBIZ_LOGS', 1 );
            }
            else
            {
                Configuration::updateValue( 'PAYUBIZ_LOGS', 0 );
            } 
            foreach(array('displayLeftColumn', 'displayRightColumn', 'displayFooter') as $hookName)
                if ($this->isRegisteredInHook($hookName))
                    $this->unregisterHook($hookName);
            if (Tools::getValue('logo_position') == self::LEFT_COLUMN)
                $this->registerHook('displayLeftColumn');
            else if (Tools::getValue('logo_position') == self::RIGHT_COLUMN)
                $this->registerHook('displayRightColumn'); 
             else if (Tools::getValue('logo_position') == self::FOOTER)
                $this->registerHook('displayFooter'); 
            if( method_exists ('Tools','clearSmartyCache') )
            {
                Tools::clearSmartyCache();
            } 
            
        }      
        
        /* Display errors */
        if (sizeof($errors))
        {
            $html .= '<ul style="color: red; font-weight: bold; margin-bottom: 30px; width: 506px; background: #FFDFDF; border: 1px dashed #BBB; padding: 10px;">';
            foreach ($errors AS $error)
                $html .= '<li>'.$error.'</li>';
            $html .= '</ul>';
        }



        $blockPositionList = array(
            self::DISABLE => $this->l('Disable'),
            // self::LEFT_COLUMN => $this->l('Left Column'),
            // self::RIGHT_COLUMN => $this->l('Right Column'),
            self::FOOTER => $this->l('Enable'));

        if( $this->isRegisteredInHook('displayLeftColumn') )
        {
            $currentLogoBlockPosition = self::LEFT_COLUMN ;
        }
        elseif( $this->isRegisteredInHook('displayRightColumn') )
        {
            $currentLogoBlockPosition = self::RIGHT_COLUMN; 
        }
        elseif( $this->isRegisteredInHook('displayFooter'))
        {
            $currentLogoBlockPosition = self::FOOTER;
        }
        else
        {
            $currentLogoBlockPosition = -1;
        }
        

    /* Display settings form */
        $html .= '
        <form action="'.$_SERVER['REQUEST_URI'].'" method="post">
          <fieldset>
          <legend><img src="'.__PS_BASE_URI__.'modules/payubiz/logo.png" />'.$this->l('Settings').'</legend>
            <p>'.$this->l('Use the "Test" mode to test out the module then you can use the "Live" mode if no problems arise. Remember to insert your merchant key and ID for the live mode.').'</p>
            <label>
              '.$this->l('Mode').'
            </label>
            <div class="margin-form" style="width:110px;">
              <select name="payubiz_mode">
                <option value="live"'.(Configuration::get('PAYUBIZ_MODE') == 'live' ? ' selected="selected"' : '').'>'.$this->l('Live').'&nbsp;&nbsp;</option>
                <option value="test"'.(Configuration::get('PAYUBIZ_MODE') == 'test' ? ' selected="selected"' : '').'>'.$this->l('Test').'&nbsp;&nbsp;</option>
              </select>
            </div>

            <p>'.$this->l('You can find your Merchant ID and Merchant Salt in your PayUbiz account.').'</p>
            <label>
              '.$this->l('Merchant ID').'
            </label>
            <div class="margin-form">
              <input type="text" name="payubiz_merchant_id" value="'.Tools::getValue('payubiz_merchant_id', Configuration::get('PAYUBIZ_MERCHANT_ID')).'" />
            </div>
            <label>
              '.$this->l('Merchant Salt').'
            </label>
            <div class="margin-form">
              <input type="text" name="payubiz_merchant_salt" value="'.trim(Tools::getValue('payubiz_merchant_salt', Configuration::get('PAYUBIZ_MERCHANT_SALT'))).'" />
            </div> 

          

            <p>'.$this->l('Select Your pg value as per you need Please Select PayUbiz by default').'</p>

              <label>
              '.$this->l('Payment Gateway').'
            </label>
            <div class="margin-form" style="width:110px;">
              <select name="pg">
                <option value="null"'.(Configuration::get('PAYUBIZ_PG') == 'null' ? ' selected="selected"' : '').'>'.$this->l('PayUBiz').'&nbsp;&nbsp;</option>
                <option value="CC"'.(Configuration::get('PAYUBIZ_PG') == 'CC' ? ' selected="selected"' : '').'>'.$this->l('Credit Card').'&nbsp;&nbsp;</option>

                <option value="DC"'.(Configuration::get('PAYUBIZ_PG') == 'DC' ? ' selected="selected"' : '').'>'.$this->l('Debit Card').'&nbsp;&nbsp;</option>
                <option value="NB"'.(Configuration::get('PAYUBIZ_PG') == 'NB' ? ' selected="selected"' : '').'>'.$this->l('NetBanking').'&nbsp;&nbsp;</option>
                <option value="wallet"'.(Configuration::get('PAYUBIZ_PG') == 'wallet' ? ' selected="selected"' : '').'>'.$this->l('PayUMoney').'&nbsp;&nbsp;</option>
                </select>
            </div>

             <p>'.$this->l('Select Your bankcode value as per you need Please Select PayUbiz by default').'</p>

              <label>
              '.$this->l('Bankcode').'
            </label>
            <div class="margin-form" style="width:110px;">
              <select name="bankcode">
                <option value="null"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'null' ? ' selected="selected"' : '').'>'.$this->l('PayUbiz').'&nbsp;&nbsp;</option>
                <option value="payuw"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'payuw' ? ' selected="selected"' : '').'>'.$this->l('PayUw- PayUMoney').'&nbsp;&nbsp;</option>
                <option value="BBCB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'BBCB' ? ' selected="selected"' : '').'>'.$this->l('Bank of Baroda Corporate Banking').'&nbsp;&nbsp;</option>
                <option value="ALLB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'ALLB' ? ' selected="selected"' : '').'>'.$this->l('Allahabad Bank NetBanking').'&nbsp;&nbsp;</option>

                <option value="ADBB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'ADBB' ? ' selected="selected"' : '').'>'.$this->l('Andhra Bank').'&nbsp;&nbsp;</option>

                    <option value="AXIB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'AXIB' ? ' selected="selected"' : '').'>'.$this->l('AXIS Bank NetBanking').'&nbsp;&nbsp;</option>
                    <option value="BBKB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'BBKB' ? ' selected="selected"' : '').'>'.$this->l('Bank of Bahrain and Kuwait').'&nbsp;&nbsp;</option>
                    <option value="BBRB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'BBRB' ? ' selected="selected"' : '').'>'.$this->l('Bank of Baroda Retail Banking').'&nbsp;&nbsp;</option>
                    <option value="BOIB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'BOIB' ? ' selected="selected"' : '').'>'.$this->l('Bank of India').'&nbsp;&nbsp;</option>

                    <option value="BOMB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'BOMB' ? ' selected="selected"' : '').'>'.$this->l('Bank of Maharashtra').'&nbsp;&nbsp;</option>

                    <option value="CABB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'CABB' ? ' selected="selected"' : '').'>'.$this->l('Canara Bank').'&nbsp;&nbsp;</option>

                    <option value="CSBN"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'CSBN' ? ' selected="selected"' : '').'>'.$this->l('Catholic Syrian Bank').'&nbsp;&nbsp;</option>

                    <option value="CBIB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'CBIB' ? ' selected="selected"' : '').'>'.$this->l('Central Bank Of India').'&nbsp;&nbsp;</option>

                    <option value="CITNB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'CITNB' ? ' selected="selected"' : '').'>'.$this->l('Citi Bank NetBanking').'&nbsp;&nbsp;</option>

                    <option value="CUBB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'CUBB' ? ' selected="selected"' : '').'>'.$this->l('CityUnion').'&nbsp;&nbsp;</option>

                    <option value="CRPB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'CRPB' ? ' selected="selected"' : '').'>'.$this->l('Corporation Bank').'&nbsp;&nbsp;</option>

                    <option value="DCBCORP"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'DCBCORP' ? ' selected="selected"' : '').'>'.$this->l('DCB Bank - Corporate Netbanking').'&nbsp;&nbsp;</option>
                    <option value="DENN"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'DENN' ? ' selected="selected"' : '').'>'.$this->l('Dena Bank').'&nbsp;&nbsp;</option>

                    <option value="DSHB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'DSHB' ? ' selected="selected"' : '').'>'.$this->l('Deutsche Bank').'&nbsp;&nbsp;</option>

                    <option value="DCBB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'DCBB' ? ' selected="selected"' : '').'>'.$this->l('Development Credit Bank').'&nbsp;&nbsp;</option>

                    <option value="FEDB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'FEDB' ? ' selected="selected"' : '').'>'.$this->l('Federal Bank').'&nbsp;&nbsp;</option>

                    <option value="HDFB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'HDFB' ? ' selected="selected"' : '').'>'.$this->l('HDFC Bank').'&nbsp;&nbsp;</option>

                    <option value="ICIB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'ICIB' ? ' selected="selected"' : '').'>'.$this->l('ICICI Netbanking').'&nbsp;&nbsp;</option>

                    <option value="INDB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'Indian Bank' ? ' selected="selected"' : '').'>'.$this->l('Indian Bank').'&nbsp;&nbsp;</option>

                    <option value="INOB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'INOB' ? ' selected="selected"' : '').'>'.$this->l('Indian Overseas Bank').'&nbsp;&nbsp;</option>


                    <option value="INIB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'INIB' ? ' selected="selected"' : '').'>'.$this->l('IndusInd Bank').'&nbsp;&nbsp;</option>

                    <option value="IDBB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'IDBB' ? ' selected="selected"' : '').'>'.$this->l('Industrial Development Bank of India').'&nbsp;&nbsp;</option>

                    <option value="INGB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'INGB' ? ' selected="selected"' : '').'>'.$this->l('ING Vysya Bank').'&nbsp;&nbsp;</option>

                    <option value="JAKB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'JAKB' ? ' selected="selected"' : '').'>'.$this->l('Jammu and Kashmir Bank').'&nbsp;&nbsp;</option>

                    <option value="KRKB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'KRKB' ? ' selected="selected"' : '').'>'.$this->l('Karnataka Bank').'&nbsp;&nbsp;</option>

                    <option value="KRVB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'KRVB' ? ' selected="selected"' : '').'>'.$this->l('Karur Vysya').'&nbsp;&nbsp;</option>

                    <option value="KRVB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'KRVB' ? ' selected="selected"' : '').'>'.$this->l('Karur Vysya - Corporate Netbanking').'&nbsp;&nbsp;</option>

                    <option value="162B"'.(Configuration::get('PAYUBIZ_BANKCODE') == '162B' ? ' selected="selected"' : '').'>'.$this->l('Kotak Bank').'&nbsp;&nbsp;</option>

                    <option value="LVCB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'LVCB' ? ' selected="selected"' : '').'>'.$this->l('Laxmi Vilas Bank-Corporate').'&nbsp;&nbsp;</option>

                    <option value="LVRB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'LVRB' ? ' selected="selected"' : '').'>'.$this->l('Laxmi Vilas Bank-Retail').'&nbsp;&nbsp;</option>

                    <option value="OBCB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'OBCB' ? ' selected="selected"' : '').'>'.$this->l('Oriental Bank of Commerce').'&nbsp;&nbsp;</option>

                    <option value="PNBB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'PNBB' ? ' selected="selected"' : '').'>'.$this->l('Punjab National Bank - Retail Banking').'&nbsp;&nbsp;</option>

                    <option value="CPNB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'CPNB' ? ' selected="selected"' : '').'>'.$this->l('Punjab National Bank-Corporate').'&nbsp;&nbsp;</option>

                    <option value="RTN"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'RTN' ? ' selected="selected"' : '').'>'.$this->l('Ratnakar Bank').'&nbsp;&nbsp;</option>

                    <option value="SRSWT"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'SRSWT' ? ' selected="selected"' : '').'>'.$this->l('Saraswat Bank').'&nbsp;&nbsp;</option>

                    <option value="SVCB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'SVCB' ? ' selected="selected"' : '').'>'.$this->l('Shamrao Vitthal Co-operative Bank').'&nbsp;&nbsp;</option>

                    <option value="SOIB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'SOIB' ? ' selected="selected"' : '').'>'.$this->l('South Indian Bank').'&nbsp;&nbsp;</option>

                    <option value="SDCB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'SDCB' ? ' selected="selected"' : '').'>'.$this->l('Standard Chartered Bank').'&nbsp;&nbsp;</option>

                    <option value="SBBJB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'SBBJB' ? ' selected="selected"' : '').'>'.$this->l('State Bank of Bikaner and Jaipur').'&nbsp;&nbsp;</option>

                    <option value="SBHB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'SBHB' ? ' selected="selected"' : '').'>'.$this->l('State Bank of Hyderabad').'&nbsp;&nbsp;</option>

                    <option value="SBIB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'SBIB' ? ' selected="selected"' : '').'>'.$this->l('State Bank of India').'&nbsp;&nbsp;</option>

                    <option value="SBMB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'SBMB' ? ' selected="selected"' : '').'>'.$this->l('State Bank of Mysore').'&nbsp;&nbsp;</option>

                    <option value="SBPB"'.(Configuration::get('PAYUBIZ_BANKCODE') == ' SBPB' ? ' selected="selected"' : '').'>'.$this->l('State Bank of Patiala').'&nbsp;&nbsp;</option>

                    <option value="SBTB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'SBTB' ? ' selected="selected"' : '').'>'.$this->l('State Bank of Travancore').'&nbsp;&nbsp;</option>

                    <option value="UBIBC"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'UBIBC' ? ' selected="selected"' : '').'>'.$this->l('Union Bank - Corporate Netbanking').'&nbsp;&nbsp;</option>

                    <option value="UBIB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'UBIB' ? ' selected="selected"' : '').'>'.$this->l('Union Bank of India').'&nbsp;&nbsp;</option>

                    <option value="UNIB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'UNIB' ? ' selected="selected"' : '').'>'.$this->l('United Bank Of India').'&nbsp;&nbsp;</option>

                    <option value="VJYB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'VJYB' ? ' selected="selected"' : '').'>'.$this->l('Vijaya Bank').'&nbsp;&nbsp;</option>

                    <option value="YESB"'.(Configuration::get('PAYUBIZ_BANKCODE') == 'YESB' ? ' selected="selected"' : '').'>'.$this->l('Yes Bank').'&nbsp;&nbsp;</option>
                </select>
            </div>

            
            
            <p>'.$this->l('You can log the server-to-server communication. The log file for debugging can be found at ').' '.__PS_BASE_URI__.'modules/payubiz/payubiz.log. '.$this->l('If activated, be sure to protect it by putting a .htaccess file in the same directory. If not, the file will be readable by everyone.').'</p>       
            <label>
              '.$this->l('Debug').'
            </label>
            <div class="margin-form" style="margin-top:5px">
              <input type="checkbox" name="payubiz_logs"'.(Tools::getValue('payubiz_logs', Configuration::get('PAYUBIZ_LOGS')) ? ' checked="checked"' : '').' />
            </div>
            <p>'.$this->l('During checkout the following is what the client gets to click on to pay with PayUbiz.').'</p>            
            <label>&nbsp;</label>
            <div class="margin-form" style="margin-top:5px">
                '.Configuration::get('PAYUBIZ_PAYNOW_TEXT');

           if(Configuration::get('PAYUBIZ_PAYNOW_LOGO')=='on')
            {
                $html .= '<img align="'.Configuration::get('PAYUBIZ_PAYNOW_ALIGN').'" alt="Pay Now With PayUbiz" title="Pay Now With PayUbiz" src="'.__PS_BASE_URI__.'modules/payubiz/logo.png">';
            }
            $html .='</div>
            <label>
            '.$this->l('PayNow Text').'
            </label>
            <div class="margin-form" style="margin-top:5px">
                <input type="text" name="payubiz_paynow_text" value="'. Configuration::get('PAYUBIZ_PAYNOW_TEXT').'">
            </div>
            <label>
            '.$this->l('PayNow Logo').'
            </label>
            <div class="margin-form" style="margin-top:5px">
                <input type="radio" name="payubiz_paynow_logo" value="off" '.( Configuration::get('PAYUBIZ_PAYNOW_LOGO')=='off' ? ' checked="checked"' : '').'"> &nbsp; '.$this->l('None').'<br>
                <input type="radio" name="payubiz_paynow_logo" value="on" '.( Configuration::get('PAYUBIZ_PAYNOW_LOGO')=='on' ? ' checked="checked"' : '').'"> &nbsp; <img src="'.__PS_BASE_URI__.'modules/payubiz/logo.png">
            </div>
            <label>
            '.$this->l('PayNow Logo Align').'
            </label>
            <div class="margin-form" style="margin-top:5px">
                <input type="radio" name="payubiz_paynow_align" value="left" '.( Configuration::get('PAYUBIZ_PAYNOW_ALIGN')=='left' ? ' checked="checked"' : '').'"> &nbsp; '.$this->l('Left').'<br>
                <input type="radio" name="payubiz_paynow_align" value="right" '.( Configuration::get('PAYUBIZ_PAYNOW_ALIGN')=='right' ? ' checked="checked"' : '').'"> &nbsp; '.$this->l('Right').'
            </div>
            <p>'.$this->l('Where would you like the the Secure Payments made with PayUbiz image to appear on your website?').'</p>
            <label>
            '.$this->l('Select the image position').'
            </label>
            <div class="margin-form" style="margin-bottom:18px;width:110px;">
                  <select name="logo_position">';
                    foreach($blockPositionList as $position => $translation)
                    {
                        $selected = ($currentLogoBlockPosition == $position) ? 'selected="selected"' : '';
                        $html .= '<option value="'.$position.'" '.$selected.'>'.$translation.'</option>';
                    }
            $html .='</select></div>

            <div style="float:right;"><input type="submit" name="submitPAYUBIZ" class="button" value="'.$this->l('   Save   ').'" /></div><div class="clear"></div>
          </fieldset>
        </form>
        <br /><br />
        <fieldset>
          <legend><img src="../img/admin/warning.gif" />'.$this->l('Information').'</legend>
          <p>- '.$this->l('In order to use your PayUbiz module, you must insert your PayUbiz Merchant ID and Merchant Salt above.').'</p>         
        </fieldset>
        </div>'; 
    
        return $html;
    }

    private function _displayLogoBlock($position)
    {      
        return '<div style="text-align:center;"><a href="https://www.payubiz.in" target="_blank" title="Secure Payments With PayUbiz"><img src="'.__PS_BASE_URI__.'modules/payubiz/payu_logo_pack.png" width="150" /></a></div>';
    }

    public function hookDisplayRightColumn($params)
    {
        return $this->_displayLogoBlock(self::RIGHT_COLUMN);
    }

    public function hookDisplayLeftColumn($params)
    {
        return $this->_displayLogoBlock(self::LEFT_COLUMN);
    }  

    public function hookDisplayFooter($params)
    {
        $html = '<section id="PAYUBIZ_footer_link" class="footer-block col-xs-12 col-sm-2">        
        <div style="text-align:center;"><a href="https://www.payubiz.in" target="_blank" title="Secure Payments With PayUbiz"><img src="'.__PS_BASE_URI__.'modules/payubiz/payu_logo_pack.png" style="display:inline-block; position: relative; left: 20px;"  /></a></div>  
        </section>';
        return $html;
    }    

    public function hookPayment($params)
    {   
        
     
        global $cookie, $cart;


        // $productInfo='Payu product information'; 

      
        if (!$this->active)
        {
            return;
        }
        
        // Buyer details
        $customer = new Customer((int)($cart->id_customer));
        
        $toCurrency = new Currency(Currency::getIdByIsoCode('INR'));
        $fromCurrency = new Currency((int)$cookie->id_currency);

         $total = $cart->getOrderTotal();

        // if($fromCurrency->iso_code != "INR")
        // {

        //     $getAmount = file_get_contents("http://www.google.com/finance/converter?a=".$total."&from=".$fromCurrency->iso_code."&to=INR"); 

        //     $getAmount = explode("<span class=bld>",$getAmount);
        //     $getAmount = explode("</span>",$getAmount[1]);
        //     $convertedAmount = preg_replace("/[^0-9\.]/", null, $getAmount[0]);
        //     $calculatedAmount_INR = round($convertedAmount,2);
        //     $amount = number_format( sprintf( "%01.2f", $calculatedAmount_INR ), 2, '.', '' ); 
        // } else
        // {
        //      $amount = number_format( sprintf( "%01.2f", $total ), 2, '.', '' );
        // }

          $amount = number_format( sprintf( "%01.2f", $total ), 2, '.', '' );


        $data = array();

        $currency = $this->getCurrency((int)$cart->id_currency);
        // if ($cart->id_currency != $currency->id)
        // {
        //     // If PayUbiz currency differs from local currency will check by ayush
        //     $cart->id_currency = (int)$currency->id;
        //     $cookie->id_currency = (int)$cart->id_currency;
        //     $cart->update();
        // }

         $deloveryAddress = new Address((int)($cart->id_address_delivery));       
         $Zipcode      =  $deloveryAddress->postcode;

         if($deloveryAddress->phone)
         {
             $phone=$deloveryAddress->phone;

         } else
         {
             $phone=$deloveryAddress->phone_mobile;

         }

         $pg = Configuration::get('PAYUBIZ_PG');
         $bankcode  = Configuration::get('PAYUBIZ_BANKCODE');


          $baseUrl=Tools::getShopDomain(true, true).__PS_BASE_URI__;

          $salt = Configuration::get('PAYUBIZ_MERCHANT_SALT');
          $log  = Configuration::get('PAYUBIZ_LOGS');

          
          
          
        
        // Use appropriate merchant identifiers

        // Live

        if( Configuration::get('PAYUBIZ_MODE') == 'live' )
        {

            $data['info']['key'] = Configuration::get('PAYUBIZ_MERCHANT_ID');
            $salt = Configuration::get('PAYUBIZ_MERCHANT_SALT');
           
            $data['payubiz_url'] = 'https://secure.payu.in/_payment';

            $Hash=hash('sha512', $data['info']['key'].'|'.$cart->id.'|'.$amount.'|'.Configuration::get('PS_SHOP_NAME') .' purchase, Cart Item ID #'. $cart->id.'|'.$customer->firstname.'|'.$customer->email.'|||||||||||'.$salt); 
        }
        // Sandbox
        else
        {

            $cart->id =  $cart->id + 40000;

            $data['info']['key'] = self::SANDBOX_MERCHANT_ID; 

            $salt = self::SANDBOX_MERCHANT_SALT; 

            $data['payubiz_url'] = 'https://test.payu.in/_payment';

            $Hash=hash('sha512', $data['info']['key'].'|'.$cart->id.'|'.$amount.'|'.Configuration::get('PS_SHOP_NAME') .' purchase, Cart Item ID #'. $cart->id.'|'.$customer->firstname.'|'.$customer->email.'|||||||||||'.$salt); 
        }

          $surl=$baseUrl.'modules/'.$this->name.'/success.php'; 
          $curl=$baseUrl.'modules/'.$this->name.'/cancel.php?id='.base64_encode($cart->id);  
          $furl=$baseUrl.'modules/'.$this->name.'/failure.php'; 

        $data['info']['txnid'] = $cart->id;   
       
        $data['payubiz_paynow_text'] = Configuration::get('PAYUBIZ_PAYNOW_TEXT');        
        $data['payubiz_paynow_logo'] = Configuration::get('PAYUBIZ_PAYNOW_LOGO');      
        $data['payubiz_paynow_align'] = Configuration::get('PAYUBIZ_PAYNOW_ALIGN');
        // Create URLs
        $data['info']['return_url'] = $this->context->link->getPageLink( 'order-confirmation', null, null, 'key='.$cart->secure_key.'&id_cart='.(int)($cart->id).'&id_module='.(int)($this->id));
        $data['info']['cancel_url'] = Tools::getHttpHost( true ).__PS_BASE_URI__;
        $data['info']['notify_url'] = Tools::getHttpHost( true ).__PS_BASE_URI__.'modules/payubiz/validation.php?itn_request=true';
    
        $data['info']['firstname'] = $customer->firstname;
        $data['info']['Lastname'] = $customer->lastname;
        $data['info']['email'] = $customer->email;
        $data['info']['Zipcode'] = $Zipcode ;
        $data['info']['phone'] = $phone;

        $data['info']['surl'] = $surl;
        $data['info']['furl'] = $furl;
        $data['info']['curl'] = $curl;
        $data['info']['Hash'] = $Hash;

        $data['info']['pg'] = $pg;
        $data['info']['bankcode'] = $bankcode;

        $data['info']['amount'] = $amount;
        $data['info']['productinfo'] = Configuration::get('PS_SHOP_NAME') .' purchase, Cart Item ID #'. $cart->id;

        $request=$data['info']['key'].'|'.$data['info']['txnid'].'|'.$data['info']['amount'].'|'.$data['info']['productinfo'].'|'.$data['info']['firstname'].'|'.$data['info']['email'].'|||||||||||'.$salt;

         if($log==1)
          {
             $query="insert into ps_payubiz_order(id_transaction,payment_request) values($cart->id,'$request')";
             Db::getInstance()->Execute($query);
          }
 
           
       
        $this->context->smarty->assign( 'data', $data );   


  
        return $this->display(__FILE__, 'payubiz.tpl'); 
    }

   

    public function hookPaymentReturn($params)
    {

        if (!$this->active)
        {
            return;
        }
        $test = __FILE__;

        return $this->display($test, 'payubiz_success.tpl');
    
    }
   
}



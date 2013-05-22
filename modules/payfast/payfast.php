<?php
/**
 * payfast.php
 *
 * Copyright (c) 2011 PayFast (Pty) Ltd
 * 
 * LICENSE:
 * 
 * This payment module is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation; either version 3 of the License, or (at
 * your option) any later version.
 * 
 * This payment module is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public
 * License for more details.
 * 
 * @author     Jonathan Page
 * @copyright  2011 PayFast (Pty) Ltd
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://www.payfast.co.za/help/prestashop
 */

//require_once(_MODULE_DIR_.'payfast/payfast_common.inc' );
define('SANDBOX_MERCHANT_ID', '10000100');
define('SANDBOX_MERCHANT_KEY', '46f0cd694581a');


if (!defined('_CAN_LOAD_FILES_'))
  exit;

/**
 * PayFast
 *
 * Class for payment module
 */
class PayFast extends PaymentModule
{
    /**
     * __construct
     *
     * Class constructor
     */
    function __construct()
    {
        $this->name = 'payfast';
        $this->tab = 'payments_gateways';
        $this->version = 1.0;
    $this->author = 'PayFast';
    
    $this->currencies = true;
    $this->currencies_mode = 'radio';

        parent::__construct();

        $this->displayName = $this->l('PayFast');
        $this->description = $this->l('Accept payments by credit card, EFT and cash from both local and international buyers, quickly and securely with PayFast.');
    
    if (!sizeof(Currency::checkPaymentCurrencies($this->id)))
      $this->warning = $this->l('No currency set for this module');
    }

    /**
     * install
     *
     * Installs module
     */
    function install()
    {   
        if (!parent::install() OR !$this->registerHook('payment') OR !$this->registerHook('paymentReturn') OR !Configuration::updateValue('PAYFAST_MERCHANT_ID', '') 
    OR !Configuration::updateValue('PAYFAST_MERCHANT_KEY', '') OR !Configuration::updateValue('PAYFAST_LOGS', '1') OR !Configuration::updateValue('PAYFAST_MODE', 'test'))
      return false;
    return true;
    }

    /**
     * uninstall
     *
     * Uninstalls module
     */
    function uninstall()
    {
        return (parent::uninstall() AND Configuration::deleteByName('PAYFAST_MERCHANT_ID') AND Configuration::deleteByName('PAYFAST_MERCHANT_KEY') AND
    Configuration::deleteByName('PAYFAST_MODE') AND Configuration::deleteByName('PAYFAST_LOGS'));
    }
  
    /**
     * getContent
     *
     * Handles the administration area configuration
     */
  function getContent()
  {
    global $currentIndex, $cookie;
    
    if( Tools::isSubmit( 'submitPayfast' ) )
    {
      $errors = array();
      if( $mode = ( Tools::getValue( 'payfast_mode' ) == 'live' ? 'live' : 'test' ) )
        Configuration::updateValue( 'PAYFAST_MODE', $mode );
            
            if( $mode != 'test' )
            {
          if( ( $merchant_id = Tools::getValue( 'payfast_merchant_id' ) ) AND preg_match('/[0-9]/', $merchant_id ) )
            Configuration::updateValue( 'PAYFAST_MERCHANT_ID', $merchant_id );
          else
            $errors[] = '<div class="warning warn"><h3>'.$this->l( 'Merchant ID seems to be wrong' ).'</h3></div>';
          
                if( ( $merchant_key = Tools::getValue( 'payfast_merchant_key' ) ) AND preg_match('/[a-zA-Z0-9]/', $merchant_key ) )
            Configuration::updateValue( 'PAYFAST_MERCHANT_KEY', $merchant_key );
          else
            $errors[] = '<div class="warning warn"><h3>'.$this->l( 'Merchant key seems to be wrong' ).'</h3></div>';
            }
      
            if( Tools::getValue( 'payfast_logs' ) )
        Configuration::updateValue( 'PAYFAST_LOGS', 1 );
      else
        Configuration::updateValue( 'PAYFAST_LOGS', 0 );
      
            if( !sizeof( $errors ) )
        Tools::redirectAdmin( $currentIndex.'&configure=payfast&token='.Tools::getValue( 'token' ) .'&conf=4' );
      foreach( $errors as $error )
        echo $error;
    }
    
    $html = '<h2>'.$this->displayName.'</h2>
    <form action="'.$_SERVER['REQUEST_URI'].'" method="post">
      <fieldset>
      <legend><img src="'.__PS_BASE_URI__.'modules/payfast/logo.gif" />'.$this->l('Settings').'</legend>
        <p>'.$this->l('Use the "Test" mode to test out the module then you can use the "Live" mode if no problems arise. Remember to insert your merchant key and ID for the live mode.').'</p>
        <label>
          '.$this->l('Mode').'
        </label>
        <div class="margin-form">
          <select name="payfast_mode">
            <option value="live"'.(Configuration::get('PAYFAST_MODE') == 'live' ? ' selected="selected"' : '').'>'.$this->l('Live').'&nbsp;&nbsp;</option>
            <option value="test"'.(Configuration::get('PAYFAST_MODE') == 'test' ? ' selected="selected"' : '').'>'.$this->l('Test').'&nbsp;&nbsp;</option>
          </select>
        </div>
        <p>'.$this->l('You can find your ID and Key in your PayFast account > My Account > Integration.').'</p>
        <label>
          '.$this->l('Merchant ID').'
        </label>
        <div class="margin-form">
          <input type="text" name="payfast_merchant_id" value="'.Tools::getValue('payfast_merchant_id', Configuration::get('PAYFAST_MERCHANT_ID')).'" />
        </div>
        <label>
          '.$this->l('Merchant Key').'
        </label>
        <div class="margin-form">
          <input type="text" name="payfast_merchant_key" value="'.trim(Tools::getValue('payfast_merchant_key', Configuration::get('PAYFAST_MERCHANT_KEY'))).'" />
        </div>
        <p>'.$this->l('You can log the server-to-server communication. The log file for debugging can be found at ').' '.__PS_BASE_URI__.'modules/payfast/payfastdebug.log. '.$this->l('If activated, be sure to protect it by putting a .htaccess file in the same directory. If not, the file will be readable by everyone.').'</p>       
        <label>
          '.$this->l('Debug').'
        </label>
        <div class="margin-form" style="margin-top:5px">
          <input type="checkbox" name="payfast_logs"'.(Tools::getValue('payfast_logs', Configuration::get('PAYFAST_LOGS')) ? ' checked="checked"' : '').' />
        </div>
        <div class="clear center"><input type="submit" name="submitPayfast" class="button" value="'.$this->l('   Save   ').'" /></div>
      </fieldset>
    </form>
    <br /><br />
    <fieldset>
      <legend><img src="../img/admin/warning.gif" />'.$this->l('Information').'</legend>
      <p>- '.$this->l('In order to use your PayFast module, you must insert your PayFast Merchant ID and Merchant Key above.').'</p>
      <p>- '.$this->l('Any orders in currencies other than ZAR will be converted by prestashop prior to be sent to the PayFast payment gateway.').'<p>
      <p>- '.$this->l('It is possible to setup an automatic currency rate update using crontab. You will simply have to create a cron job with currency update link available at the bottom of "Currencies" section.').'<p>
    </fieldset>';
    
    return $html;
  }

    /**
     * hookPayment
     *
     * Payment hook
     */
  function hookPayment($params)
  {
    if (!$this->active)
      return;

    global $smarty;
    
    $smarty->assign('buttonText', $this->l('Pay with PayFast'));
    return $this->display(__FILE__, 'payfast_payment.tpl');
  }
  
    /**
     * hookPaymentReturn
     *
     * Payment return hook
     */
    function hookPaymentReturn($params)
    {
    if (!$this->active)
      return;

        $test = __FILE__;

    return $this->display($test, 'payfast_success.tpl');
    }
    
    /**
     * preparePayment
     *
     * Does redirect to PayFast for payment
     */
    function preparePayment()
    {
        // Variable declaration
      global $smarty, $cart, $cookie;
        $pfAmount = 0;
        $pfDescription = '';
        $pfOutput = '';
  
        // Lookup the currency codes and local price
    $currency = $this->getCurrency((int)$cart->id_currency);
    if ($cart->id_currency != $currency->id)
    {
       $url = $smarty->tpl_vars['base_uri']->value.'modules/'.$this->name.'/payment.php';
      
      // If PayFast currency differs from local currency
      $cart->id_currency = (int)$currency->id;
      $cookie->id_currency = (int)$cart->id_currency;
      $cart->update();
      //Tools::redirect('modules/'.$this->name.'/payment.php');

      header('Location: '.$url);
      exit;
    }

        $pf_curr_code = 'ZAR';
    
        // Set default currency
        if( $pf_curr_code == '' )
          $pf_curr_code = 'ZAR';
    
        // Convert from the currency of the users shopping cart to the currency
        // which the user has specified in their payfast preferences.
    
      $total = $cart->getOrderTotal();
        $pfAmount = $total;

        // Use appropriate merchant identifiers
        // Live
        if( Configuration::get('PAYFAST_MODE') == 'live' )
        {
            $merchantId = Configuration::get('PAYFAST_MERCHANT_ID');
            $merchantKey = Configuration::get('PAYFAST_MERCHANT_KEY');
            $payfast_url = 'https://www.payfast.co.za/eng/process';
        }
        // Sandbox
        else
        {
            $merchantId = '10000100';
            $merchantKey = '46f0cd694581a'; 
            $payfast_url = 'https://sandbox.payfast.co.za/eng/process';
        }
        
        // Create URLs
        $returnUrl = Tools::getShopDomain(true, true).__PS_BASE_URI__.'order-confirmation.php?key='.$cart->secure_key.'&id_cart='.(int)($cart->id).'&id_module='.(int)($this->id);
        $cancelUrl = Tools::getShopDomainSsl(true, true).__PS_BASE_URI__. 'order.php';
        $notifyUrl = Tools::getShopDomainSsl(true, true).__PS_BASE_URI__. 'modules/payfast/validation.php' .'?itn_request=true';
    
        // Construct variables for post
        $data = array(
            // Merchant details
            'merchant_id' => $merchantId,
            'merchant_key' => $merchantKey,
            'return_url' => $returnUrl,
            'cancel_url' => $cancelUrl,
            'notify_url' => $notifyUrl,
    
            // Item details
          'item_name' => Configuration::get('PS_SHOP_NAME') .' purchase, Order #'. $cart->id,
          'item_description' => $pfDescription,
          'amount' => number_format( sprintf( "%01.2f", $pfAmount ), 2, '.', '' ),
            'm_payment_id' => $cart->id,
            'currency_code' => $pf_curr_code,
            'custom_str1' => $cart->secure_key,
            'custom_int1' => $cart->id,
            
            // Other details
            'user_agent' => PF_USER_AGENT,
            );

        // Buyer details
        $customer = new Customer((int)($cart->id_customer));
        $data['name_first'] = $customer->firstname;
        $data['name_last'] = $customer->lastname;
    
        // Create output string
        foreach( $data as $key => $val )
            $pfOutput .= $key .'='. urlencode( $val ) .'&';
    
        // Remove last ampersand
        $pfOutput = substr( $pfOutput, 0, -1 );
    
        // Display debugging information (if in debug mode)
      if( Configuration::get('PAYFAST_MODE') == 'test' )
        {
            echo "<a href='". $payfast_url ."?". $pfOutput ."'>Test the URL here</a>";
            echo "<pre>". print_r( $data, true ) ."</pre>";
            exit();
      }
    
        // Send to PayFast (GET)
        header( "Location: ". $payfast_url ."?". $pfOutput );
        exit();
    }
}

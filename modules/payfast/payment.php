<?php
/**
 * payment.php
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

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');
include(dirname(__FILE__).'/payfast.php');
include(dirname(__FILE__).'/payfast_common.inc');

if (!$cookie->isLogged(true))
    Tools::redirect('authentication.php?back=order.php');
elseif (!$cart->getOrderTotal(true, Cart::BOTH))
	Tools::displayError('Error: Empty cart');

$payfast = new PayFast();
// Prepare payment
$payfast->preparePayment();

include(dirname(__FILE__).'/../../header.php');
// Display
echo $payfast->display('payfast.php', 'confirm.tpl');

include_once(dirname(__FILE__).'/../../footer.php');



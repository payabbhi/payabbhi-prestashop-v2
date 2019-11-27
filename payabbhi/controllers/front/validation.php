<?php

require_once __DIR__.'/../../payabbhi-php/init.php';


class PayabbhiValidationModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        global $cookie;

        $access_id            = Configuration::get('PAYABBHI_ACCESS_ID');
        $secret_key        = Configuration::get('PAYABBHI_SECRET_KEY');

        $paymentId = $_REQUEST['payment_id'];

        $cart = $this->context->cart;

        if (($cart->id_customer === 0) or
            ($cart->id_address_delivery === 0) or
            ($cart->id_address_invoice === 0) or
            (!$this->module->active))
        {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $authorized = false;

        // Handles situation when payment method is disabled during payment in progress
        foreach (Module::getPaymentModules() as $module)
        {
            if ($module['name'] == 'payabbhi')
            {
                $authorized = true;
                break;
            }
        }
        if (!$authorized)
        {
            die($this->module->getTranslator()->trans('This payment method is not available.', array(), 'Modules.Payabbhi.Shop'));
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer))
        {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $currency = $this->context->currency;

        $total = (string) intval($cart->getOrderTotal(true, Cart::BOTH) * 100);

        try
        {
            // payabbhi payment signature verification
            try
            {
                session_start();
                $attributes = array(
                    'order_id' => $_SESSION['payabbhi_order_id'],
                    'payment_id' => $_REQUEST['payment_id'],
                    'payment_signature' => $_POST['payment_signature']
                );

                $client = new \Payabbhi\Client($access_id, $secret_key);
                $client->utility->verifyPaymentSignature($attributes);
                $payment = $client->payment->retrieve($paymentId);
            }
            catch(SignatureVerificationError $e)
            {

                Logger::addLog("Payment Failed for Order# ".$cart->id.". Payabbhi payment id: ".$paymentId. "Error: ". $error, 4);

                echo 'Error! Please contact the seller directly for assistance.</br>';
                echo 'Order Id: '.$cart->id.'</br>';
                echo 'Payabbhi Payment Id: '.$paymentId.'</br>';
                echo 'Error: '.$e->getMessage().'</br>';

                exit;
            }

            $customer = new Customer($cart->id_customer);

            /**
             * Validate an order in database
             * Function called from a payment module
             *
             * @param int     $id_cart
             * @param int     $id_order_state
             * @param float   $amount_paid       Amount really paid by customer (in the default currency)
             * @param string  $payment_method    Payment method (eg. 'Credit card')
             * @param null    $message           Message to attach to order
             * @param array   $extra_vars
             * @param null    $currency_special
             * @param bool    $dont_touch_amount
             * @param bool    $secure_key
             * @param Shop    $shop
             *
             * @return bool
             * @throws PrestaShopException
             */
            $extraData = array(
                'transaction_id'    =>  $payment->id,
            );

            $method = "Payabbhi-{$payment->method}";

            $ret = $this->module->validateOrder(
                $cart->id,
                (int) Configuration::get('PS_OS_PAYMENT'),
                $cart->getOrderTotal(true, Cart::BOTH),
                $method,
                'Payment by Payabbhi using ' . $payment->method,
                $extraData,
                NULL,
                false,
                $customer->secure_key
            );

            Logger::addLog("Payment Successful for Order#".$cart->id.". Payabbhi payment id: ".$paymentId . "Ret=" . (int)$ret, 1);

            $query = http_build_query([
                'controller'    => 'order-confirmation',
                'id_cart'       => (int) $cart->id,
                'id_module'     => (int) $this->module->id,
                'id_order'      => $this->module->currentOrder,
                'key'           => $customer->secure_key,
            ], '', '&');

            $url = 'index.php?' . $query;

            Tools::redirect($url);
        }
        catch(\Payabbhi\Error $e)
        {
            $error = $e->getMessage();
            Logger::addLog("Payment Failed for Order# ".$cart->id.". Payabbhi payment id: ".$paymentId. "Error: ". $error, 4);

            echo 'Error! Please contact the seller directly for assistance.</br>';
            echo 'Order Id: '.$cart->id.'</br>';
            echo 'Payabbhi Payment Id: '.$paymentId.'</br>';
            echo 'Error: '.$error.'</br>';

            exit;
        }
    }
}

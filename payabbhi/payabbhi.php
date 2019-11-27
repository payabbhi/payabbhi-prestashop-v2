<?php

require_once __DIR__.'/payabbhi-php/init.php';
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class Payabbhi extends PaymentModule
{

    private $_html = '';
    private $ACCESS_ID = null;
    private $SECRET_KEY = null;

    private $_postErrors = [];

    const PAYABBHI_CHECKOUT_URL = 'https://checkout.payabbhi.com/v1/checkout.js';

    public function __construct()
    {
        $this->controllers = ['validation'];
        $this->name = 'payabbhi';
        $this->displayName = 'Payabbhi';
        $this->tab = 'payments_gateways';
        $this->version = '2.0.0';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
        $this->display = true;

        $this->author = 'Payabbhi Team';

        $config = Configuration::getMultiple([
            'PAYABBHI_ACCESS_ID',
            'PAYABBHI_SECRET_KEY',
            'PAYABBHI_PAYMENT_DESCRIPTION',
            'PAYABBHI_PAYMENT_AUTO_CAPTURE'
        ]);

        if (array_key_exists('PAYABBHI_ACCESS_ID', $config))
        {
            $this->ACCESS_ID = $config['PAYABBHI_ACCESS_ID'];
        }

        if (array_key_exists('PAYABBHI_SECRET_KEY', $config))
        {
            $this->SECRET_KEY = $config['PAYABBHI_SECRET_KEY'];
        }

        if (array_key_exists('PAYABBHI_PAYMENT_DESCRIPTION', $config))
        {
            $this->PAYMENT_DESCRIPTION = $config['PAYABBHI_PAYMENT_DESCRIPTION'];
        }

        if (array_key_exists('PAYABBHI_PAYMENT_AUTO_CAPTURE', $config))
        {
            $this->PAYMENT_AUTO_CAPTURE = $config['PAYABBHI_PAYMENT_AUTO_CAPTURE'];
        }

        parent::__construct();

        // Parent Construct reqired for translation
        $this->page = basename(__FILE__, '.php');
        $this->description = $this->l('Prestashop module for accepting payments with Payabbhi.');

        if ($this->ACCESS_ID === null OR $this->SECRET_KEY === null)
        {
            $this->warning = $this->l('Merchant Payabbhi key must be configured in order to use this module correctly.');
        }
    }

    public function getContent()
    {
        if (Tools::isSubmit('btnSubmit'))
        {
            $this->_postValidation();
            if (empty($this->_postErrors))
            {
                $this->_postProcess();
            }
            else
            {
                foreach ($this->_postErrors AS $err)
                {
                    $this->_html .= "<div class='alert error'>ERROR: {$err}</div>";
                }
            }
        }
        else
        {
            $this->_html .= "<br />";
        }

        $this->_html .= "<img src='../modules/payabbhi/logo.png' style='height:80px; width:80px; float:left; margin-right:15px;' />";
        $this->_displayForm();

        return $this->_html;
    }

    private function _displayForm()
    {
        $settings               = $this->l('Settings');
        $merchantAccessIdLabel  = $this->l('Access ID');
        $merchantSecretKeyLabel = $this->l('Secret Key');
        $merchantAccessId       = $this->ACCESS_ID;
        $merchantSecretKey      = $this->SECRET_KEY;
        $paymentDescriptionLabel = $this->l('Description');
        $paymentAutoCaptureLabel = $this->l('Payment Auto Capture');
        $paymentDescription      = $this->PAYMENT_DESCRIPTION;
        $paymentAutoCapture      = $this->PAYMENT_AUTO_CAPTURE === 'true'? true: false;
        $saveSettings           = $this->l('Save');

        $paymentAutoCaptureHtml = '';
        if ($paymentAutoCapture) {
          $paymentAutoCaptureHtml = "<option value='true' selected>True</option>
          <option value='false'>False</option>";
        } else {
          $paymentAutoCaptureHtml = "<option value='true'>True</option>
          <option value='false' selected>False</option>";
        }

        $this->_html .=
        "
        <br />
        <br />
        <p><form action='{$_SERVER['REQUEST_URI']}' method='post'>
                <fieldset style='background-color:#ffffff;'>
                <legend><i class='icon-cogs'></i>{$settings}</legend>
                        <table border='0' width='100%' cellpadding='0' cellspacing='0' style='font-size:13px;' id='form'>
                                <tr style='height:80px;'>
                                        <td width'25%' align='right' style='padding-right:20px; padding-bottom:20px;'>{$merchantAccessIdLabel}</td>
                                        <td width='75%'>
                                                <input type='text' name='ACCESS_ID' value='{$merchantAccessId}' style='width:75%; height:30px; margin:0 25% 4px 0;' />
                                                <span style='font-size:12px; font-style:italic; opacity:0.8;'>Access ID is available as part of API keys downloaded from the Portal</span>
                                        </td>
                                </tr>
                                <tr style='height:80px;'>
                                        <td width'25%' align='right' style='padding-right:20px; padding-bottom:20px;'>{$merchantSecretKeyLabel}</td>
                                        <td width='75%'>
                                                <input type='text' name='SECRET_KEY' value='{$merchantSecretKey}' style='width:75%; height:30px; margin:0 25% 4px 0;' />
                                                <span style='font-size:12px; font-style:italic; opacity:0.8;'>Secret Key is available as part of API keys downloaded from the Portal</span>
                                        </td>
                                </tr>
                                <tr style='height:80px;'>
                                        <td width'25%' align='right' style='padding-right:20px; padding-bottom:20px;'>{$paymentDescriptionLabel}</td>
                                        <td width='75%'>
                                                <input type='text' name='PAYMENT_DESCRIPTION' value='{$paymentDescription}' style='width:75%; height:30px; margin:0 25% 4px 0;' />
                                                <span style='font-size:12px; font-style:italic; opacity:0.8;'>This text will be displayed alongside payabbhi logo on payments page</span>
                                        </td>
                                </tr>
                                <tr style='height:80px;'>
                                        <td width'25%' align='right' style='padding-right:20px; padding-bottom:20px;'>{$paymentAutoCaptureLabel}</td>
                                        <td width='75%'>
                                                <select name='PAYMENT_AUTO_CAPTURE' style='width:75%; height:30px; margin:0 25% 4px 0;'>
                                                  {$paymentAutoCaptureHtml}
                                                </select>
                                                <span style='font-size:12px; font-style:italic; opacity:0.8;'>Specify whether the payment should be captured automatically. Refer to Payabbhi API Reference.</span>
                                        </td>
                                </tr>
                                <tr style='height:80px;'>
                                        <td colspan='2' align='center'>
                                                  <input class='button' name='btnSubmit' value='{$saveSettings}' type='submit' style='width: 90px; height:30px; ' />
                                        </td>
                                </tr>
                        </table>
                </fieldset>
        </form>
        </p>
        <br />";
    }

    public function install()
    {
        $description = 'Pay with Card, Netbanking, Wallet or UPI';
        Configuration::updateValue('PAYABBHI_PAYMENT_DESCRIPTION', $description);
        $this->PAYMENT_DESCRIPTION = $description;

        $payment_auto_capture = "true";
        Configuration::updateValue('PAYABBHI_PAYMENT_AUTO_CAPTURE', $payment_auto_capture);
        $this->PAYMENT_AUTO_CAPTURE = $payment_auto_capture;

        if (parent::install() and
            $this->registerHook('header') and
            $this->registerHook('orderConfirmation') and
            $this->registerHook('paymentOptions') and
            $this->registerHook('paymentReturn'))
        {
            return true;
        }

        return false;
    }

    public function hookHeader()
    {
        if (Tools::getValue('controller') == "order")
        {
            $this->context->controller->registerJavascript(
               'remote-payabbhi-checkout',
               self::PAYABBHI_CHECKOUT_URL,
               ['server' => 'remote', 'position' => 'head', 'priority' => 20]
            );

            $this->context->controller->registerJavascript(
                'payabbhi-checkout-local-script',
                'modules/' . $this->name . '/script.js',
                ['position' => 'bottom', 'priority' => 30]
            );

            $amount = intval($this->context->cart->getOrderTotal() * 100);
            $payabbhi_order_id = "";
            $payment_auto_capture = Configuration::get('PAYABBHI_PAYMENT_AUTO_CAPTURE');
            try{
                $payabbhi_order  = $this->getPayabbhiLibraryClient()->order->create(array('amount' => $amount, 'currency' => 'INR', 'payment_auto_capture' => $payment_auto_capture, 'merchant_order_id' => $this->context->cart->id));
                $payabbhi_order_id = $payabbhi_order->id;
                session_start();
                $_SESSION['payabbhi_order_id'] = $payabbhi_order_id;
            } catch (\Payabbhi\Error $e){
                $error = $e->getMessage();
                Logger::addLog("Order creation failed with the error " . $error, 4);
            }

            Media::addJsDef([
                'checkout_vars' =>  [
                    'key'                => $this->ACCESS_ID,
                    'name'               => Configuration::get('PS_SHOP_NAME'),
                    'amount'             => $amount,
                    'cart_id'            => $this->context->cart->id,
                    'payabbhi_order_id'  => $payabbhi_order_id,
                ]
            ]);
        }
    }

    public function hookOrderConfirmation($params)
    {
        $order = $params['order'];

        if ($order)
        {
            if ($order->module === $this->name) {

                $payments = $order->getOrderPayments();

                if (count($payments) >= 1)
                {
                    $payment = $payments[0];
                    $paymentId = $payment->transaction_id;
                    return "Your Payabbhi Payment Id is <code>$paymentId</code>";
                }

                return;
            }
        }
    }


    public function uninstall()
    {
        Configuration::deleteByName('PAYABBHI_ACCESS_ID');
        Configuration::deleteByName('PAYABBHI_SECRET_KEY');
        Configuration::deleteByName('PAYABBHI_PAYMENT_DESCRIPTION');
        Configuration::deleteByName('PAYABBHI_PAYMENT_AUTO_CAPTURE');

        return parent::uninstall();
    }

    public function hookPaymentOptions($params)
    {
        $option = new PaymentOption();
        $paymentDescription = $this->PAYMENT_DESCRIPTION;

        $option->setModuleName('payabbhi')
                // ->setLogo('../modules/payabbhi/logo.png') //methods logo
                ->setAction($this->context->link->getModuleLink('payabbhi', 'validation', [], true))
                ->setCallToActionText('Pay via Payabbhi')
                ->setAdditionalInformation("<p>{$paymentDescription}</p>")
                ;

        return [
            $option,
        ];
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }

        if ((!isset($params['order'])) or
            ($params['order']->module != $this->name))
        {
            return false;
        }

        if ((isset($params['order'])) and
            (Validate::isLoadedObject($params['order'])) &&
            (isset($params['order']->valid)))
        {
            $this->smarty->assign([
                'id_order'  => $params['order']->id,
                'valid'     => $params['order']->valid,
            ]);
        }

        if ((isset($params['order']->reference)) and
            (!empty($params['order']->reference))) {
            $this->smarty->assign('reference', $params['order']->reference);
        }

        $this->smarty->assign([
            'shop_name'     => $this->context->shop->name,
            'reference'     => $params['order']->reference,
            'contact_url'   => $this->context->link->getPageLink('contact', true),
        ]);

        return $this->fetch('module:payabbhi/views/templates/hook/payment_return.tpl');
    }

    private function _postValidation()
    {
        if (Tools::isSubmit('btnSubmit'))
        {
            $keyId = Tools::getValue('ACCESS_ID');
            $keySecret = Tools::getValue('SECRET_KEY');

            if (empty($keyId))
            {
                $this->_postErrors[] = $this->l('Merchant Access ID is required.');
            }
            if (empty($keySecret))
            {
                $this->_postErrors[] = $this->l('Merchant Secret Key is required.');
            }
        }
    }

    private function _postProcess()
    {
        if (Tools::isSubmit('btnSubmit'))
        {
            Configuration::updateValue('PAYABBHI_ACCESS_ID', Tools::getValue('ACCESS_ID'));
            Configuration::updateValue('PAYABBHI_SECRET_KEY', Tools::getValue('SECRET_KEY'));
            Configuration::updateValue('PAYABBHI_PAYMENT_DESCRIPTION', Tools::getValue('PAYMENT_DESCRIPTION'));
            Configuration::updateValue('PAYABBHI_PAYMENT_AUTO_CAPTURE', Tools::getValue('PAYMENT_AUTO_CAPTURE'));

            $this->ACCESS_ID= Tools::getValue('ACCESS_ID');
            $this->SECRET_KEY= Tools::getValue('SECRET_KEY');
            $this->PAYMENT_DESCRIPTION= Tools::getValue('PAYMENT_DESCRIPTION');
            $this->PAYMENT_AUTO_CAPTURE= Tools::getValue('PAYMENT_AUTO_CAPTURE');
        }

        $ok = $this->l('Ok');
        $updated = $this->l('Settings Saved');
        $this->_html .= "<div class='conf confirm' style='padding:20px; font-size:16px; font-weight:400; width:100%; text-align:center;'><img src='../img/admin/enabled.gif' alt='{$ok}' />{$updated}</div>";
    }

    //Returns Payabbhi Library Client
    public function getPayabbhiLibraryClient()
    {
        return new \Payabbhi\Client($this->ACCESS_ID, $this->SECRET_KEY);
    }
}

?>

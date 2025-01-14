<?php

class netseasyHostedPaymentModuleFrontController extends ModuleFrontController {

    public function __construct() {
        parent::__construct();
    }

    public function init() {
        parent::init();
        if (!$this->module->active) {
            Tools::redirect($this->context->link->getPageLink('order'));
        }
    }

    public function initcontent() {
        parent::initcontent();
    }

    public function postProcess() {
        /**
         * Get current cart object from session
         */
        $cart = $this->context->cart;
        $nets = new Netseasy();
        $authorized = false;
        /**
         * Verify if this module is enabled and if the cart has
         * a valid customer, delivery address and invoice address
         */
        if (!$this->module->active || $cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        /**
         * Verify if this payment module is authorized
         */
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'netseasy') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->l('This payment method is not available.'));
        }

        /** @var CustomerCore $customer */
        $customer = new Customer($cart->id_customer);
        /**
         * Checked if this is a valid customer account
         */
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $requestObj = $nets->createRequestObject($this->context->cart->id);
        $response = $nets->MakeCurl($nets->getApiUrl()['backend'], $requestObj);

        if ($response && !@$response->errors) {
            $lang = @$this->context->language->locale;
            if ($lang) {
                Tools::redirect($response->hostedPaymentPageUrl . "&language=$lang");
            } else {
                Tools::redirect($response->hostedPaymentPageUrl);
            }
        } else {
            $nets->logger->logError('Invalid request created ' . $response);
            Tools::redirect('index.php?controller=order&step=1');
        }
    }
}

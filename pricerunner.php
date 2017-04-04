<?php

    /**
     * 2016 Modified Solutions ApS www.modified.dk hej@modified.dk
     *
     * NOTICE OF LICENSE
     *
     * This Source Code Form is subject to the terms of the Mozilla Public
     * License, v. 2.0. If a copy of the MPL was not distributed with this
     * file, You can obtain one at http://mozilla.org/MPL/2.0/.
    **/

    if (!defined('_PS_VERSION_')) {
        exit;
    }

    if (!defined('PRICRUNNER_OFFICIAL_PLUGIN_VERSION')) {
        define('PRICRUNNER_OFFICIAL_PLUGIN_VERSION', 'prestashop-v1.0.7');
    }

    require_once(dirname(__FILE__) . '/pricerunner-php-sdk/src/files.php');

    require_once(dirname(__FILE__) . '/classes/prestashopProductValidator.php');
    require_once(dirname(__FILE__) . '/classes/prestashopProductCollectionValidator.php');
    require_once(dirname(__FILE__) . '/classes/prestashopProductExtension.php');
    require_once(dirname(__FILE__) . '/classes/productFetcher.php');

    require_once(dirname(__FILE__) . '/helpers.php');

    class Pricerunner extends Module
    {
        public $allow;
        public $protocol_content;

        protected $html = '';
        protected $postErrors = array();

        public function __construct()
        {
            $this->name = 'pricerunner';
            $this->tab = 'smart_shopping';
            $this->version = '1.0.4';
            $this->author = 'Pricerunner';
            $this->need_instance = 0;
            // $this->ps_versions_compliancy = array(/*'min' => '1.4', 'max' => _PS_VERSION_*/);
            $this->bootstrap = true;

            parent::__construct();

            $this->displayName = $this->l('Pricerunner XML Feed');
            $this->description = $this->l('Generates feeds for pricerunner price matching.');

            $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
        }

        /**
         * Completes registration of shop with Pricerunner.
         * Allows for external execution.
         * @throws Exception
         * @return bool
         */
        public function completeRegistration()
        {
            $pricerunnerName    = Configuration::get('PRICERUNNER_NAME');
            $pricerunnerPhone   = Configuration::get('PRICERUNNER_PHONE');
            $pricerunnerMail    = Configuration::get('PRICERUNNER_MAIL');
            $pricerunnerDomain  = Configuration::get('PRICERUNNER_DOMAIN');
            $pricerunnerFeedUrl = Configuration::get('PRICERUNNER_FEED_URL');

            try {
                \PricerunnerSDK\PricerunnerSDK::postRegistration(
                    $pricerunnerName,
                    $pricerunnerPhone,
                    $pricerunnerMail,
                    $pricerunnerDomain,
                    $pricerunnerFeedUrl
                );
            }
            catch (Exception $e) {
                $this->html .= $this->displayError($e->getMessage());
                return false;
            }

            return true;
        }

        /**
         * Method that processes the API post of the user credentials
         */
        protected function postProcess()
        {
            Configuration::updateValue('PRICERUNNER_DOMAIN', _PS_BASE_URL_);
            Configuration::updateValue('PRICERUNNER_NAME', Tools::getValue('PRICERUNNER_NAME'));
            Configuration::updateValue('PRICERUNNER_PHONE', Tools::getValue('PRICERUNNER_PHONE'));
            Configuration::updateValue('PRICERUNNER_MAIL', Tools::getValue('PRICERUNNER_MAIL'));
            
            if ($this->completeRegistration()) {
                Configuration::updateValue('PRICERUNNER_PLUGIN_ACTIVATED', 1);
                $this->html .= $this->displayConfirmation($this->l('Thank you for your application, you will be contacted by Pricerunner soon'));
            }
        }

        public function getContent()
        {
            $this->sslEnabled = Configuration::get('PS_SSL_ENABLED');

            if (Tools::isSubmit('btnResetFeed')) {
                $this->html .= $this->resetFeed();
                $this->html .= $this->displayConfirmation($this->l('Your feed has been reset.'));
            }

            if (Tools::isSubmit('btnSubmit')) {

                $this->postValidation();

                if (!count($this->postErrors)) {
                    $this->postProcess();
                }
                else {
                    foreach ($this->postErrors as $err) {
                        $this->html .= $this->displayError($err);
                    }
                }
            }

            $pricerunnerPluginActivated = Configuration::get('PRICERUNNER_PLUGIN_ACTIVATED');
            if (empty($pricerunnerPluginActivated)) {
                $this->html .= $this->displayExplainingInfo();

                $this->html .= $this->displayForm();
            } else {
                $this->html .= $this->displayActivatedPluginView();
            }

            return $this->html;
        }

        private function createHashIfEmpty()
        {
            $pricerunnerFeedUrl = Configuration::get('PRICERUNNER_FEED_URL');
            if (empty($pricerunnerFeedUrl)) {
                $randomString = \PricerunnerSDK\PricerunnerSDK::getRandomString();

                $feedPath = dirname(__FILE__) . '/feed/' . $randomString . '.xml';
                $feedUrl = _PS_BASE_URL_ . _MODULE_DIR_ . $this->name . '/feed.php?hash=' . $randomString;

                // Add shopId to URL
                // TODO Fix multishop
                /*if (isNewerPrestashopVersion() && Shop::isFeatureActive()) {
                    $feedUrl .= "&shop_id=" . $this->context->shop->id;
                }*/

                Configuration::updateValue('PRICERUNNER_FEED_PATH', $feedPath);
                Configuration::updateValue('PRICERUNNER_FEED_URL', $feedUrl);
                Configuration::updateValue('PRICERUNNER_FEED_HASH', $randomString);
            }
        }

        /**
         * This method validates the post variables and appends to the error array.
         */
        protected function postValidation()
        {
            if (Tools::isSubmit('btnSubmit'))
            {
                if (!Tools::getValue('PRICERUNNER_NAME')) {
                    $this->postErrors[] = $this->l('Name is required.');
                }
                elseif (!Tools::getValue('PRICERUNNER_PHONE')) {
                    $this->postErrors[] = $this->l('Phone is required.');
                }
                elseif (!Tools::getValue('PRICERUNNER_MAIL')) {
                    $this->postErrors[] = $this->l('Mail is required.');
                }
            }
        }

        /**
         * This method returns the template for an information view.
         *
         * @return string
         */
        public function displayExplainingInfo()
        {
            return Tools::file_get_contents(dirname(__FILE__) . '/templates/infoAlert.tpl');
        }

        /**
         * This method returns the template for an activated plugin view.
         *
         * @return string
         */
        public function displayActivatedPluginView()
        {
            $view = Tools::file_get_contents(dirname(__FILE__) . '/templates/activatedPluginView.tpl');

            $viewBag = array(
                'domain' => _PS_BASE_URL_,
                'feedUrl' => Configuration::get('PRICERUNNER_FEED_URL'),
                'name' => Configuration::get('PRICERUNNER_NAME'),
                'phone' => Configuration::get('PRICERUNNER_PHONE'),
                'email' => Configuration::get('PRICERUNNER_MAIL')
            );

            return $this->getPopulatedView($view, $viewBag);
        }

        /**
         * This method returns the template for the input view.
         *
         * @return string
         */
        public function displayForm()
        {
            $view = Tools::file_get_contents(dirname(__FILE__) . '/templates/formView.tpl');

            $viewBag = array(
                'domain' => _PS_BASE_URL_,
                'feedUrl' => Configuration::get('PRICERUNNER_FEED_URL'),
                'name' => Configuration::get('PRICERUNNER_NAME'),
                'email' => Configuration::get('PRICERUNNER_MAIL')
            );

            return $this->getPopulatedView($view, $viewBag);
        }

        /**
         * This method is not integrated at the moment, but it's purpose is to give the user an easy way to reset the feed.
         */
        private function resetFeed()
        {
            Configuration::updateValue('PRICERUNNER_PLUGIN_ACTIVATED', 0);
            Configuration::updateValue('PRICERUNNER_DOMAIN', _PS_BASE_URL_);
            Configuration::updateValue('PRICERUNNER_NAME', '');
            Configuration::updateValue('PRICERUNNER_PHONE', '');
            Configuration::updateValue('PRICERUNNER_MAIL', '');

            Configuration::updateValue('PRICERUNNER_FEED_PATH', '');
            Configuration::updateValue('PRICERUNNER_FEED_URL', '');
            Configuration::updateValue('PRICERUNNER_FEED_HASH', '');
        }


        /**
         * Helper function for binding view parameters to a view
         *
         * @string $view
         * @array $viewBag
         * @return string
         */
        private function getPopulatedView($view, $viewBag)
        {
            foreach ($viewBag as $key => $value) {
                $view = str_replace('{{' . $key . '}}', $value, $view);
            }

            return $view;
        }

        /**
         * Function that configures module database keys.
         * For use on installation of module.
         */
        private function populateConfiguration()
        {
            Configuration::updateValue('PRICERUNNER_DOMAIN', _PS_BASE_URL_);
            Configuration::updateValue('PRICERUNNER_NAME', Configuration::get('PS_SHOP_NAME'));
            Configuration::updateValue('PRICERUNNER_MAIL', Configuration::get('PS_SHOP_EMAIL'));
            $this->createHashIfEmpty();

            return true;
        }


        public function install()
        {
            if (!parent::install() || !$this->populateConfiguration()) {
                return false;
            }

            return true;
        }

        public function uninstall()
        {
            if (!parent::uninstall()) {
                return false;
            }

            Configuration::deleteByName('PRICERUNNER_PLUGIN_ACTIVATED');

            Configuration::deleteByName('PRICERUNNER_FEED_PATH');
            Configuration::deleteByName('PRICERUNNER_FEED_URL');
            Configuration::deleteByName('PRICERUNNER_FEED_HASH');

            Configuration::deleteByName('PRICERUNNER_DOMAIN');
            Configuration::deleteByName('PRICERUNNER_NAME');
            Configuration::deleteByName('PRICERUNNER_PHONE');
            Configuration::deleteByName('PRICERUNNER_MAIL');

            return true;
        }
    }
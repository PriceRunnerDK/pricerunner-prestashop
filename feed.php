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

    define('PRICRUNNER_OFFICIAL_PLUGIN_VERSION', 'prestashop-v1.0.5');

    require_once(dirname(__FILE__) . '/../../config/config.inc.php');
    require_once(dirname(__FILE__) . '/../../init.php');

    require_once(dirname(__FILE__) . '/pricerunner-php-sdk/src/files.php');

    require_once(dirname(__FILE__) . '/classes/prestashopProductValidator.php');
    require_once(dirname(__FILE__) . '/classes/prestashopProductCollectionValidator.php');
    require_once(dirname(__FILE__) . '/classes/prestashopProductExtension.php');
    require_once(dirname(__FILE__) . '/classes/prestashopLinkExtension.php');
    require_once(dirname(__FILE__) . '/classes/productFetcher.php');

    require_once(dirname(__FILE__) . '/helpers.php');

    /**
     * 
     * Set shop context before any interaction with Prestashop.
     * 
     * We do this to make sure that all the data we fetch
     * from Prestashop corresponds to the correct shop.
     * 
     */
    // TODO Fix multishop
    /*if (Tools::getIsset('shop_id')) {
        $shopId = Tools::getValue('shop_id');
    }

    if (isNewerPrestashopVersion() && Shop::isFeatureActive() && isset($shopId)) {
        Shop::setContext(Shop::CONTEXT_SHOP, $shopId);
        $context = Context::getContext();
        $context->shop = new Shop($shopId);
    }*/

    if (!Module::getInstanceByName('pricerunner')->active) {
        die('Module not active');
    }

    $shopId = null;

    $link = new PrestashopLinkExtension();

    if (!Tools::getIsset('hash')) {
        die('No hash in request.');
    }

    if (Tools::getValue('hash') !== Configuration::get('PRICERUNNER_FEED_HASH')) {
        die('Invalid hash');
    }

    $productFetcher = new ProductFetcher($context, $link);

    $pricerunnerProducts = $productFetcher->getPricerunnerProducts();

    $pricerunnerDataContainer = \PricerunnerSDK\PricerunnerSDK::generateDataContainer($pricerunnerProducts, true, new PrestashopProductCollectionValidator());

    $xmlString = $pricerunnerDataContainer->getXmlString();
    $errors = $pricerunnerDataContainer->getErrors();

    if (Tools::getIsset('test')) {
        $productErrorRenderer = new \PricerunnerSDK\Errors\ProductErrorRenderer($errors);
        echo $productErrorRenderer->render();

        exit;
    }


    if (!headers_sent()) {
        header("Content-Type:application/xml; charset=utf-8");
    }

    echo $xmlString;

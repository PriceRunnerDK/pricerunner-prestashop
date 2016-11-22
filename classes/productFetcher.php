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

class ProductFetcher
{
    public $allow;
    public $protocol_content;

    private $sslEnabled;
    private $context;
    private $link;

    public function __construct($context, $link)
    {
        $this->context = $context;
        $this->link = $link;

        // TODO Does this always work??
        $this->sslEnabled = Configuration::get('PS_SSL_ENABLED');
    }

    /**
     * Returns an array of Pricerunner products from the SDK.
     *
     * @param $langId
     * @return array
     */
    public function getPricerunnerProducts($langId = false)
    {
        if (!$langId) {
            $langId = Configuration::get('PS_LANG_DEFAULT');
        }
        
        $productRows = $this->getPrestashopProducts($langId);

        $products = array();

        foreach ($productRows as $productData) {
            $products = array_merge($products, $this->createPricerunnerProducts($productData));
        }

        return $products;
    }

    /**
     * Returns an array of prestashop products from the DB
     *
     * @param $langId
     * @return array
     */
    private function getPrestashopProducts($langId)
    {
        $db = Db::getInstance();

        if (!isNewerPrestashopVersion()) {
            $sql = 'SELECT p.`id_product`, pl.`name`
                FROM `' . _DB_PREFIX_ . 'product` p
                LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (p.`id_product` = pl.`id_product`)
                WHERE pl.`id_lang` = ' . (int)($langId) . ' AND p.`active`= 1 AND p.`available_for_order`= 1 AND p.`cache_is_pack` = 0
                ORDER BY pl.`name`';

            return $db->ExecuteS($sql);
        }

        $context = Context::getContext();

        $front = true;

        if (!in_array($context->controller->controller_type, array('front', 'modulefront'))) {
            $front = false;
        }

        $sql = 'SELECT 
            p.`id_product`, pl.`name`
        FROM
            `' . _DB_PREFIX_ . 'product` p
            ' . Shop::addSqlAssociation('product', 'p') . '
        LEFT JOIN
            `' . _DB_PREFIX_ . 'product_lang` pl
            ON (p.`id_product` = pl.`id_product`
            ' . Shop::addSqlRestrictionOnLang('pl') . ')
        WHERE
            pl.`id_lang` = ' . (int)$langId . ' AND
            product_shop.`active`= 1 AND
            product_shop.`available_for_order`= 1 AND
            p.`cache_is_pack` = 0
            ' . ($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '') . '
        ORDER BY
            pl.`name`';

        return $db->ExecuteS($sql);
    }

    /**
     * Returns category name, generated from levels of categories e.g.
     * Women > Dresses > Summer Dresses
     *
     * @param PrestashopProductExtension $product
     * @return string
     */
    private function getCategoryName(PrestashopProductExtension $product)
    {
        $category = new Category((int)$product->id_category_default, (int)$this->context->language->id);

        $categoryName = '';

        $parentCategories = $category->getParentsCategories();

        for ($i = count($parentCategories) - 2; $i >= 0; $i--) {
            $categoryName .= $parentCategories[$i]['name'];
            if ($i != 0) {
                $categoryName .= ' > ';
            }
        }

        if (empty($categoryName)) {
            return $category->name;
        }

        return $categoryName;
    }

    /**
     * Returns an array of Pricerunner products from the SDK containing all the parsed product combinations
     *
     * @param \PricerunnerSDK\Models\Product $pricerunnerProduct
     * @param PrestashopProductExtension $product
     * @return array
     */
    private function getProductCombinations(\PricerunnerSDK\Models\Product $pricerunnerProduct, PrestashopProductExtension $product)
    {
        $productCombinations = $product->getAttributeCombinations($this->context->language->id);

        $attributeLinkHelper = array();
        $attributeTextHelper = array();

        $productCombinationProducts = array();

        $maxProductCombinationsIndex = count($productCombinations) - 1;

        foreach ($productCombinations as $key => $combination) {

            $productAttributeId = $combination['id_product_attribute'];
            $attributeId = $combination['id_attribute'];
            $attributeName = $combination['attribute_name'];
            $attributeGroupName = $combination['group_name'];
            $attributeQuantitiy = $combination['quantity'];

            $attributeTextHelper[] = $attributeGroupName . ' ' . $attributeName;

            $nextCombination = (($key + 1) <= $maxProductCombinationsIndex) ? $productCombinations[$key + 1] : null;

            if ($nextCombination != null && $productAttributeId == $nextCombination['id_product_attribute']) {
                continue;
            }

            $clonedPricerunnerProduct = clone $pricerunnerProduct;

            $clonedPricerunnerProduct->setSku($pricerunnerProduct->getSku() . '-' . $productAttributeId);
            $clonedPricerunnerProduct->setStockStatus($attributeQuantitiy > 0 ? 'In Stock' : 'Out of Stock');
            $clonedPricerunnerProduct->setProductName($clonedPricerunnerProduct->getProductName() . ' - ' . implode($attributeTextHelper, ', '));
            $clonedPricerunnerProduct->setProductUrl($pricerunnerProduct->getProductUrl() . $product->getAnchor($productAttributeId, true));
            $clonedPricerunnerProduct->setPrice(round($product->getPrice(true, $productAttributeId, 2, null, false, true, 1), 2));

            $combinationImageUrl = $this->getCombinationImage($product, $productAttributeId);
            if ($combinationImageUrl != '') {
                $clonedPricerunnerProduct->setImageUrl((!$this->sslEnabled ? 'http://' : 'https://') . $combinationImageUrl);
            }

            $productCombinationProducts[] = $clonedPricerunnerProduct;

            $attributeTextHelper = array();
        }

        return $productCombinationProducts;
    }

    /**
     * Looks for a product combination image, and returns an empty string if no results.
     *
     * @param PrestashopProductExtension $product
     * @param $productAttributeId
     * @return string
     */
    private function getCombinationImage(PrestashopProductExtension $product, $productAttributeId)
    {
        $combinationImages = array();

        $sql = 'SELECT pai.`id_image`
            FROM `' . _DB_PREFIX_ . 'product_attribute_image` pai
            LEFT JOIN `' . _DB_PREFIX_ . 'image` i ON pai.id_image = i.id_image
            WHERE pai.`id_product_attribute` = ' . (int)($productAttributeId) . '
            ORDER BY i.cover DESC, i.position ASC';

        $data = Db::getInstance()->ExecuteS($sql);

        foreach ($data as $row) {
            $combinationImages[] = (int)($row['id_image']);
        }

        foreach ($combinationImages as $image) {
            if (empty($image)) {
                return "";
            }

            // Yes, prestashop fixed a typo here, without deprecating the function...
            if (method_exists('ImageType', 'getFormattedName')) {
                return $this->link->getImageLink($product->link_rewrite, $product->id . '-' . $image, ImageType::getFormattedName('large'));
            } else {
                return $this->link->getImageLink($product->link_rewrite, $product->id . '-' . $image, ImageType::getFormatedName('large'));
            }
        }
    }

    /**
     * Returns an array of Pricerunner products from the SDK.
     *
     * @param $productData
     * @return array
     */
    public function createPricerunnerProducts($productData)
    {
        $pricerunnerProduct = new \PricerunnerSDK\Models\Product();

        $product = new PrestashopProductExtension($productData['id_product'], true, (int)$this->context->language->id);
        
        $categoryName = $this->getCategoryName($product);
      
        $image = Image::getCover($productData['id_product']);

        $imageUrl = '';
        if (!empty($image)) {
            // Yes, prestashop fixed a typo here, without deprecating the function...
            if (method_exists('ImageType', 'getFormattedName')) {
                $imageUrl = (!$this->sslEnabled ? 'http://' : 'https://') . Link::getImageLink($product->link_rewrite, $image['id_image'], ImageType::getFormattedName('large'));
            } else {
                $imageUrl = (!$this->sslEnabled ? 'http://' : 'https://') . Link::getImageLink($product->link_rewrite, $image['id_image'], ImageType::getFormatedName('large'));
            }
        }

        $stockStatus = $product->quantity > 0 ? 'In Stock' : 'Out of Stock';

        $productLink = $product->getLink();

        $pricerunnerProduct->setProductName(\PricerunnerSDK\PricerunnerSDK::getXmlReadyString($product->name));
        $pricerunnerProduct->setCategoryName(\PricerunnerSDK\PricerunnerSDK::getXmlReadyString($categoryName));
        $pricerunnerProduct->setSku($productData['id_product']);
        $pricerunnerProduct->setPrice(round($product->getPrice(), 2));
        $pricerunnerProduct->setProductUrl($productLink);
        $pricerunnerProduct->setManufacturerSku($product->id_manufacturer);
        $pricerunnerProduct->setManufacturer($product->manufacturer_name);
        $pricerunnerProduct->setEan($product->ean13);

        if (!empty($product->description)) {
            $pricerunnerProduct->setDescription(\PricerunnerSDK\PricerunnerSDK::getXmlReadyString($product->description));
        } else {
            $pricerunnerProduct->setDescription(\PricerunnerSDK\PricerunnerSDK::getXmlReadyString($product->description_short));
        }

        $pricerunnerProduct->setImageUrl($imageUrl);
        $pricerunnerProduct->setStockStatus($stockStatus);

        $pricerunnerProduct->setProductState($product->condition);

        $products = array($pricerunnerProduct);
        $productCombinations = $this->getProductCombinations($pricerunnerProduct, $product);

        // Return combinations if product has combinations, otherwise just return the product.
        return count($productCombinations) > 0 ? $productCombinations : $products;
    }

}

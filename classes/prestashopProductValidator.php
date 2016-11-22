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

class PrestashopProductValidator extends \PricerunnerSDK\Validators\ProductValidator
{
    public function validate()
    {
        $this->validateCategoryName();
        $this->validateProductName();
        $this->validateSku();
        $this->validatePrice();
        $this->validateProductUrl();
        // $this->validateIsbn();
        $this->validateManufacturer();
        $this->validateManufacturerSku();
        // $this->validateShippingCost();
        $this->validateEan();
        $this->validateUpc();
        $this->validateDescription();
        $this->validateImageUrl();
        $this->validateStockStatus();
        // $this->validateDeliveryTime();
        $this->validateRetailerMessage();
        // $this->validateCatalogId();
        // $this->validateWarranty();
    }
}
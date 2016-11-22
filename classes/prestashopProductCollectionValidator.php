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

class PrestashopProductCollectionValidator extends \PricerunnerSDK\Validators\ProductCollectionValidator
{
    protected function createProductValidator($product)
    {
        return new PrestashopProductValidator($product);
    }

    protected function validateProductAgainstProductCollection(\PricerunnerSDK\Models\Product $product, \PricerunnerSDK\Validators\ProductValidator $productValidator)
    {
        $this->validateEan($product, $productValidator);
        $this->validateSku($product, $productValidator);
    }
}
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

class PrestashopLinkExtension extends Link
{ 
    
    /**
    * Slightly modified, because getImageLink did not return the proper path for multishop.
    * It's a mess and should be cleaned up, but how?
    */
    public function getImageLink($name, $ids, $type = null)
    {
        $imageLink = parent::getImageLink($name, $ids, $type);

        if(isNewerPrestashopVersion() && Shop::isFeatureActive()) {                 
            $baseLink = str_replace(array('http://www.', 'https://www.'), '', $this->getBaseLink());
            $baseLink = rtrim($baseLink, '/');

            return str_replace(Tools::getShopDomain(), $baseLink, $imageLink);
        }

        return $imageLink;
    }
}

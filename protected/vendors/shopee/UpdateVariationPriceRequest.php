<?php

/**
 * Created by PhpStorm.
 * User: laigc
 * Date: 2017/3/8
 * Time: 15:07
 */
class UpdateVariationPriceRequest extends ShopeeApiAbstract
{

    public function setItemId($itemId)
    {
        $this->request['item_id'] = (int)$itemId;
        return $this;
    }

    public function setPrice($price)
    {
        $this->request['price'] = (float)$price;
        return $this;
    }

    public function setVariationId($variationId)
    {
        $this->request['variation_id'] = (int)$variationId;
        return $this;
    }

    public function setRequest()
    {
        // TODO: Implement setRequest() method.
        $this->setEndpoint('items/update_variation_price', $isPost = true);
        return $this;
    }
}
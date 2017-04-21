<?php

/**
 * Created by PhpStorm.
 * User: laigc
 * Date: 2017/3/8
 * Time: 15:08
 */
class UpdateVariationStockRequest extends ShopeeApiAbstract
{

    public function setItemId($itemId)
    {
        $this->request['item_id'] = (int)$itemId;
        return $this;
    }

    public function setQty($qty)
    {
        $this->request['stock'] = (int)$qty;
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
        $this->setEndpoint('items/update_variation_stock', $isPost = true);
        return $this;
    }
}
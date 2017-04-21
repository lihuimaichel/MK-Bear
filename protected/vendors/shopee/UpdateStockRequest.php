<?php

/**
 * Created by PhpStorm.
 * User: laigc
 * Date: 2017/3/8
 * Time: 15:07
 */
class UpdateStockRequest extends ShopeeApiAbstract
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

    public function setRequest()
    {
        // TODO: Implement setRequest() method.
        $this->setEndpoint('items/update_stock', $isPost = true);
        return $this;
    }
}
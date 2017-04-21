<?php

/**
 * Created by PhpStorm.
 * User: laigc
 * Date: 2017/3/8
 * Time: 14:59
 */
class GetItemsListRequest extends ShopeeApiAbstract
{

    public function setLimit($limit , $offset = 0)
    {
        $this->request['pagination_entries_per_page'] = $limit;
        $this->request['pagination_offset'] = $offset;
        return $this;
    }

    public function setRequest()
    {
        // TODO: Implement setRequest() method.
        $this->setEndpoint($endpoint = 'items/get', $isPost = true);

        return $this;
    }
}
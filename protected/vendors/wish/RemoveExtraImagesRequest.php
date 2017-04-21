<?php
/**
 * @desc 更改产品
 *
 */
class RemoveExtraImagesRequest extends WishApiAbstract {


    public function setItemId($itemId)
    {
        $this->request['id'] = $itemId;
        return $this;
    }
    public function setParentSku($parentSku)
    {
        $this->request['parent_sku'] = $parentSku;
        return $this;
    }
    public function setEndpoint($endpoint = 'product/remove-extra-images', $isPost = true)
    {
        parent::setEndpoint($endpoint, $isPost);
    }

    public function setRequest()
    {
        // TODO: Implement setRequest() method.
        return $this;
    }

}
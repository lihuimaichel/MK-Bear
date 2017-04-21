<?php
/**
 * 
 * @author liuj
 *
 */
class AliexpressdeleteimageController extends UebController{
    public $account_id;
    
    /**
     * @desc 删除未被引用图片
     */
    public function actionIndex(){
       
        set_time_limit(2*3600);
        ini_set('memory_limit','2048M');
        ini_set('display_errors', true);
        $account_id = Yii::app()->request->getParam('account_id');
        if($account_id){
            
            $this->account_id = $account_id;
            //获取所有分组中的未被引用图片
            $images = $this->getImages();
            if($images){
                foreach ($images as $image_id){
                    //删除未引用图片
                    $result_del = $this->delUnUsePhoto($image_id);
                }
                $jsonData = array(
                    'message' =>Yii::t('system', '删除成功'),
                    'navTabId'=>'page' . AliexpressAccount::getIndexNavTabId(),
                );
                echo $this->successJson($jsonData);
            } else {
                $jsonData = array(
                    'message' =>Yii::t('system', '删除失败请重试'),
                    'navTabId'=>'page' . AliexpressAccount::getIndexNavTabId(),
                );
                echo $this->failureJson($jsonData);
            }
            exit;
        } else {
            echo $this->failureJson(array('message' => Yii::t('system', '请选择账号')));
            exit;
        }
    }
    

        
    /**
     * @desc 获取图片id
     */
    public function getImages($groupId = null){
        $images  = array();
        $pathArr = array();   
        $currentPage = 1;
        $hasNextPage = true;

        //取出图片库里的主sku还有子sku的所有主图   主要是为了判断哪些未被引用的子sku第一张图片
        $productImageAddModel = new AliexpressProductImageAdd();
        $fields = 'remote_path';
        $where = "account_id = ".$this->account_id." AND type = 1 AND platform_code = '".Platform::CODE_ALIEXPRESS."' AND sku LIKE '%.%' AND upload_status = 1";
        $imageInfo = $productImageAddModel->getListByCondition($fields,$where);
        if($imageInfo){
            foreach ($imageInfo as $imageVal) {
                $pathArr[] = $imageVal['remote_path'];
            }
        }

        try {
            while ($hasNextPage) {
                $request_image = new ListImagePaginationRequest();
                $request_image->setPage($currentPage);
                $request_image->setlocationType('ALL_GROUP');
                if($groupId){
                    $request_image->setgroupId($groupId);
                }

                $response_image = $request_image->setAccount($this->account_id)->setRequest()->sendRequest()->getResponse();
                if($request_image->getIfSuccess()){
                    $totalPage = (int)$response_image->totalPage;
                    foreach ($response_image->images as $image){
                        $referenceCount = (int)$image->referenceCount;
                        $image_id = $image->iid;
                        if($referenceCount == 0 && !in_array($image->url, $pathArr)){
                            $images[] = $image_id;
                        }
                    }
                    $totalPage = (int)$response_image->totalPage;
                    $currentPage ++;
                    if ($currentPage > $totalPage)
                        $hasNextPage = false;
                } else {
                    return false;
                }
            }
            return $images;
        } catch (Exception $e) {
            //$this->setErrorMessage($e->getMessage());
            return false;
        }
    }
        
    /**
     * @desc 删除未被引用图片
     */
    public function delUnUsePhoto($image_id){
        $request_del = new DelUnUsePhotoRequest();
        $request_del->setImageRepositoryId($image_id);
        $response_del = $request_del->setAccount($this->account_id)->setRequest()->sendRequest()->getResponse();
        if($request_del->getIfSuccess()){
            return true;
        } else {
            return false;
        }
    }
    
}
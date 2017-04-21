<?php
/**
 * @desc Lazada品牌相关
 * @author Gordon
 * @since 2015-08-13
 */
class LazadabrandController extends UebController{
    
    /**
     * @desc 访问过滤配置
     * @see CController::accessRules()
     */
    public function accessRules() {
        return array(
			array(
				'allow',
				'users' => array('*'),
				'actions' => array('getbrands')
			),
		);
    }
    
    /**
     * @desc 获取品牌
     * @author Gordon
     */
    public function actionGetbrands(){
        //取一个可用账号
        $account = LazadaAccount::getAbleAccountByOne();
        $accountID = $account['id'];
        $logID = LazadaLog::model()->prepareLog($accountID,LazadaBrand::EVENT_NAME);
        if( $logID ){
            $checkRunning = LazadaLog::model()->checkRunning($accountID, LazadaBrand::EVENT_NAME);
            if( !$checkRunning ){
                LazadaLog::model()->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
            }else{
                LazadaLog::model()->setRunning($logID);
                $lazadaBrandModel = LazadaBrand::model();
                $brands = $lazadaBrandModel->updateBrands($accountID);//拉取品牌
                //更新日志信息
                if( $brands!==false ){
                    LazadaLog::model()->setSuccess($logID);
                }else{
                    LazadaLog::model()->setFailure($logID, $lazadaBrandModel->getExceptionMessage());
                }
                if( !empty($brands) ){//保存数据
                    LazadaBrand::model()->deleteBrands();
                    foreach($brands as $brand){
                        LazadaBrand::model()->saveRecord(array(
                                'name'      => trim(addslashes($brand->Name)),
                                'code'      => isset($brand->GlobalIdentifier) ? addslashes($brand->GlobalIdentifier) : '',
                                'timestamp' => date('Y-m-d H:i:s'),
                        ));   
                    }
                }
            }
        }
    }
    
    /**
     * @desc 品牌列表
     */
    public function actionList(){
        $model = LazadaBrand::model();
        $this->render('list', array(
                'model'    => $model,
        ));
    }
}
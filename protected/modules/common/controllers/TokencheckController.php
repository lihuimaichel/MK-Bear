<?php
/**
 * @desc token检测是否失效
 * @author liuj
 *
 */
class TokencheckController extends UebController {
    /**
     * @desc token检测
     */
    public function actionIndex() {
        set_time_limit(0);
        ini_set('memory_limit','2048M');
        ini_set('display_errors', true);
        
        //删除5天前的数据
        $time = date('Y-m-d H:i:s',time()-5*24*3600);
        $model_token_check = new TokenCheck();
        $model_token_check->getDbConnection()->createCommand()->delete(TokenCheck::tableName(), "time<:time", array(':time' => $time));
        
        //按平台多线程
        MHelper::runThreadSOCKET('/ebay/ebaytokencheck');
        sleep(1);
        MHelper::runThreadSOCKET('/lazada/lazadatokencheck');
        sleep(1);
        MHelper::runThreadSOCKET('/aliexpress/aliexpresstokencheck');
        sleep(1);
        MHelper::runThreadSOCKET('/amazon/amazontokencheck');
        sleep(1);
        MHelper::runThreadSOCKET('/wish/wishtokencheck');
        sleep(1);
        MHelper::runThreadSOCKET('/joom/joomtokencheck');
    }
    
    /**
     * @desc token检测前台显示
     */
    public function actionList() {
        
        $this->render("list", array(
                "model"	=>  new TokenCheck()
        ));
    }
    
    /**
     * @desc 根据平台获取账号
     */
    public function actionPlatformAccount(){
    	$arr = TokenCheck::model()->getPlatformAccount(trim($_POST['platform']));
    	print_r(json_encode($arr));
    	exit;
    }
}
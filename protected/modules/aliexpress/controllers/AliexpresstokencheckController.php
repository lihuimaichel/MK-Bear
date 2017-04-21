<?php
/**
 * @desc token检测是否失效
 * @author liuj
 *
 */
class AliexpresstokencheckController extends UebController {
    /**
     * @desc token检测
     */
    public function actionIndex() {
        set_time_limit(8*3600);
        ini_set('memory_limit','2048M');
        ini_set('display_errors', true);
        
        $Accounts = AliexpressAccount::model()->getAbleAccountList();
        foreach ($Accounts as $account){
            $account_id = $account['id'];
            $request = new GetFreightTemplateRequest();
            $response = $request->setAccount($account_id)->setRequest()->sendRequest()->getResponse();

            $data = array(
                'platform' => 'aliexpress',
                'account_id' => $account_id,
                'time' => date('Y-m-d H:i:s',time()),
            );
            if($request->getIfSuccess()){
                $data['status']     = 1;
            } else {
                $data['status']     = 0;
                $data['message']    = $request->getErrorMsg();
            }

            $model_token_check = new TokenCheck();
            $model_token_check->addData($data);
        }
    }
}
<?php
/**
 * @desc token检测是否失效
 * @author liuj
 *
 */
class JoomtokencheckController extends UebController {
    /**
     * @desc token检测
     */
    public function actionIndex() {
        set_time_limit(3600);
        ini_set('memory_limit','2048M');
        ini_set('display_errors', true);
        
        $Accounts = JoomAccount::model()->getAbleAccountList();
        foreach ($Accounts as $account){
            $account_id = $account['id'];

            $request = new AuthTestRequest();
            $request->setAccount($account_id);
            $response = $request->setRequest()->sendRequest()->getResponse();

            $data = array(
                'platform' => 'joom',
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
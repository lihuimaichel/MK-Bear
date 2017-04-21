<?php
/**
 * @desc token检测是否失效
 * @author liuj
 *
 */
class EbaytokencheckController extends UebController {
    /**
     * @desc token检测
     */
    public function actionIndex() {
        ini_set('memory_limit','2048M');
        ini_set('display_errors', true);
        
        $Accounts = EbayAccount::model()->getAbleAccountList();
        foreach ($Accounts as $account){
            $account_id = $account['id'];
            $request = new GetAPIAccessRulesRequest();
            $response = $request->setAccount($account_id)->setRequest()->sendRequest()->getResponse();

            $data = array(
                'platform' => 'ebay',
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
<?php
/**
 * @desc token检测是否失效
 * @author liuj
 *
 */
class LazadatokencheckController extends UebController {
    /**
     * @desc token检测
     */
    public function actionIndex() {
        ini_set('memory_limit','2048M');
        ini_set('display_errors', true);
        
        $Accounts = LazadaAccount::model()->getAbleAccountList();
        foreach ($Accounts as $account){
            $account_id = $account['id'];
            $ApiAccountID = $account['account_id'];
            $site_id = $account['site_id'];
            $request = new FeedCountRequest();
            $response = $request->setSiteID($site_id)->setAccount($ApiAccountID)->setRequest()->sendRequest()->getResponse();
            $data = array(
                'platform' => 'lazada',
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
            //["ErrorMessage"]=>    string(38) "E007: Login failed. Signature mismatch"
        }
    }
}
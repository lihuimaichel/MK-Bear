<?php
/**
 * @desc joom api接口操作控制器
 * @author liuj
 * @since 2016-04-16
 *
 */
class JoomapiController extends UebController {

    /**
     * @todo joom api接口操作控制器
     * @since 2016-04-16
     */


    /**
     * @desc 第一次获取access_token
     */
    public function actionGetaccesstoken(){
        $accountID = Yii::app()->request->getParam("account_id");
        $code = Yii::app()->request->getParam("code");
        $AccessTokenRequest = new AccessTokenRequest();
        $AccessTokenRequest->setCode($code);
        $AccessTokenRequest->setAccount($accountID);
        $response = $AccessTokenRequest->setRequest()->sendRequest()->getResponse();
        if($AccessTokenRequest->getIfSuccess()){
            //var_dump($response);
            //将token更新到账号表
            $accessToken = $response->data->access_token;
            $refreshToken = $response->data->refresh_token;
            $tokenExpiredTime = $response->data->expiry_time;
            $data = array(
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_expired_time' => $tokenExpiredTime,
            );
            JoomAccount::model()->updateByPk($accountID, $data);
            
        } else {
            $errorMsg = $AccessTokenRequest->getErrorMsg();
            var_dump($errorMsg);
        }
    }
    
    public function actionIndex(){
        
    }

}
<?php
    namespace Lantern {
        class Authenticator extends \Slim\Middleware
        {
            public function call()
            {
                include_once 'conf/api_inc.php';

                //$app = \Slim\Slim::getInstance();
                $headers = apache_request_headers();
                $resopnseUtils = new \Lantern\ResponseUtils();

                if (!empty($headers['Authorization'])) {
                    if (strcmp($headers['Authorization'], API_KEY) === 0) {
                        $this->next->call();
                    }
                    else {
                        $msg['error'] = true;
                        $msg['message'] = 'unauthorized_api_key';

                        $resopnseUtils->sendResponse(401, $msg);
                        return;
                    }
                }
                else {
                    $msg['error'] = true;
                    $msg['message'] = 'empty_api_key';
                    
                    $resopnseUtils->sendResponse(400, $msg);
                    
                    return;
                }
            }

        }

    }


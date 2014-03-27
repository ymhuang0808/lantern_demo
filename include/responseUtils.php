<?php
namespace Lantern;    
    class ResponseUtils {
        function sendResponse($status_code, $msg = array()) {
            $app = \Slim\Slim::getInstance();
            
            $app->response->setStatus($status_code);
            $app->response->headers->set('Content-Type', 'application/json');
            
            echo json_encode($msg);
        }
    }
    

<?php
    require 'vendor/autoload.php';
    require_once 'include/database.php';
    require_once 'authenticator.php';
    include_once 'include/dbCode.php';

    if (file_exists(dirname(__FILE__) . '/include/responseUtils.php')) {
        include_once dirname(__FILE__) . '/include/responseUtils.php';
    }
    else {
        exit ;
    }

    $app = new \Slim\Slim( array('mode' => 'development', 'debug' => true));

    use Lantern\DbCode as DbCode;

    $app->group('/api', function() use ($app)// ------ group start ------ //
    {
        // add middleware
        $app->add(new Lantern\Authenticator());

        $app->get('/test', function()
        {
            echo 'authenticate successfully';
        });

        /**
         * Add new recommendation
         *
         */
        $app->post('/recommendation', function() use ($app)
        {
            $location_name = $app->request->post('location_name');
            $device_serial_number = $app->request->post('device_sn');
            
            $database = new Lantern\Database();
            $responseUtils = new Lantern\ResponseUtils();

            $location_name = urldecode($location_name);

            $location_name = filter_var($location_name, FILTER_SANITIZE_STRING);
            $device_serial_number = filter_var($device_serial_number, FILTER_SANITIZE_STRING);

            if ($database->connect() == DbCode::CONN_SUCCESS) {
                $addCode = $database->addRecommend($location_name, $device_serial_number);

                if ($addCode == DbCode::RECOMM_RECORD_SUCCESS) {
                    // success
                    $status_code = 200;
                    $msg['error'] = false;
                    $msg['msg'] = 'add_success';

                }
                elseif ($addCode == DbCode::RECOMM_RECORD_EXIST) {
                    // exist
                    $status_code = 201;
                    $msg['error'] = true;
                    $msg['msg'] = 'already_added';
                }
                elseif ($addCode == DbCode::RECOMM_RECORD_UNKNOWN_ERROR) {
                    $status_code = 500;
                    $msg['error'] = true;
                    $msg['msg'] = 'unknown_error';
                }
                else {
                    $status_code = 500;
                    $msg['error'] = true;
                    $msg['msg'] = 'unknown_error';
                }
            }
            else {
                $status_code = 500;
                $msg['error'] = true;
                $msg['msg'] = 'db_error';
            }

            $responseUtils->sendResponse($status_code, $msg);
        });

        /**
         * Get recommendation locaitons' name
         *
         */
        $app->get('/recommendation_location_name/:device_serial_number', function($device_serial_number)
        {
            $database = new Lantern\Database();
            $responseUtils = new Lantern\ResponseUtils();

            $device_serial_number = filter_var($device_serial_number, FILTER_SANITIZE_STRING);

            if ($database->connect() == DbCode::CONN_SUCCESS) {
                $location_name_list = $database->getRecommendedLocationName($device_serial_number);

                if (!empty($location_name_list)) {
                    $status_code = 200;
                    $msg['error'] = false;
                    $msg['locationList'] = $location_name_list;
                }
                else {
                    $status_code = 404;
                    $msg['error'] = true;
                    $msg['msg'] = 'empty_location_name';
                }
            }
            else {
                $status_code = 500;
                $msg['error'] = true;
                $msg['msg'] = 'db_error';
            }

            $responseUtils->sendResponse($status_code, $msg);
        });

        $app->get('/recommendation_num/:location_name', function($location_name)
        {
            $database = new Lantern\Database();
            $responseUtils = new Lantern\ResponseUtils();

            $location_name = urldecode($location_name);
            $location_name = filter_var($location_name, FILTER_SANITIZE_STRING);

            if ($database->connect() == DbCode::CONN_SUCCESS) {
                $num = $database->getRecommendationNum($location_name);

                if (is_numeric($num)) {
                    $status_code = 200;
                    $msg['error'] = false;
                    $msg['recommendationNumber'] = $num;
                }
                elseif ($num == DbCode::RECOMM_NUM_ERROR) {
                    $status_code = 500;
                    $msg['error'] = true;
                    $msg['msg'] = 'unknown_error';
                }
                else {
                    $status_code = 500;
                    $msg['error'] = true;
                    $msg['msg'] = 'unknown_error';
                }
            }
            else {
                $status_code = 500;
                $msg['error'] = true;
                $msg['msg'] = 'db_error';
            }

            $responseUtils->sendResponse($status_code, $msg);
        });

        $app->delete('/recommendation/:location_name/:device_serial_number', function($location_name, $device_serial_number)
        {
            $database = new Lantern\Database();
            $responseUtils = new Lantern\ResponseUtils();

            $location_name = urldecode($location_name);
            $location_name = filter_var($location_name, FILTER_SANITIZE_STRING);
            $device_serial_number = filter_var($device_serial_number, FILTER_SANITIZE_STRING);

            if ($database->connect() == DbCode::CONN_SUCCESS) {
                $result = $database->deleteRecommend($location_name, $device_serial_number);
                
                //var_dump($result);

                if ($result == DbCode::RECOMM_DELETE_SUCCESS) {
                    $status_code = 200;
                    $msg['error'] = false;
                    $msg['msg'] = 'delete_success';
                }
                elseif($result == DbCode::RECOMM_DELETE_NOT_CONTENT) {
                    $status_code = 204;
                    $msg['error'] = true;
                    $msg['msg'] = 'delete_item_not_found';
                }
                else {
                    $status_code = 500;
                    $msg['error'] = true;
                    $msg['msg'] = 'unknown_error';
                }
            }
            else {
                //var_dump($result);
                
                $status_code = 500;
                $msg['error'] = true;
                $msg['msg'] = 'db_error';
            }

            $responseUtils->sendResponse($status_code, $msg);
        });

    });
    // ------ group end ------ //

    $app->get('/hello/:name', function($name)
    {
        echo 'Hello ' . $name;

        $db = new \Lantern\Database();
        $db->getDBHost();
    });

    $app->run();

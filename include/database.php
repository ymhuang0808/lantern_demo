<?php
    namespace Lantern;
        include_once dirname(__FILE__) . '/dbCode.php';

        use PDO;
        use PDOException;
        use \Lantern\DbCode as DbCode;

        class Database
        {
            protected $pdo;

            function connect()
            {
                if (file_exists(dirname(dirname(__FILE__)) . '/conf/db_inc.php')) {
                    include (dirname(dirname(__FILE__)) . '/conf/db_inc.php');
                }
                else {
                    return DbCode::CONN_UNKNOWN_ERROR;
                }

                try {
                    $this->pdo = new PDO('mysql:dbname=' . $db['db_name'] . ';host=' . $db['db_host'], $db['db_user'], $db['db_pass']);
                    $this->pdo->exec('SET NAMES utf8');
                }
                catch (PDOException $e) {
                    return DbCode::CONN_UNKNOWN_ERROR;
                }

                return DbCode::CONN_SUCCESS;
            }

            /**
             * Get location Id from location table
             *
             * @param   location name
             */
            function getLocationId($location_name, $create = true)
            {
                $id = $this->locationNameExist($location_name);

                if ($id != false) {
                    return $id;
                }
                elseif($create) {
                    // create a new one in location table
                    try {
                        $this->pdo->beginTransaction();
                        $pre = $this->pdo->prepare('INSERT INTO location (location_name) VALUES(:locationName)');
                        $pre->bindParam(':locationName', $location_name);
                        $pre->execute();
                        $lastId = $this->pdo->lastInsertId('location_id');
                        $this->pdo->commit();

                        return $lastId;
                    }
                    catch(PDOException $e) {
                        $this->pdo->rollback();
                        return false;
                    }
                }

                return false;
            }

            /**
             * Check the location name exist or not
             *
             * @param   location name
             */
            function locationNameExist($location_name)
            {

                try {
                    $this->pdo->beginTransaction();
                    $pre = $this->pdo->prepare('SELECT location_id FROM location WHERE location_name = :locationName ');
                    $pre->bindParam(':locationName', $location_name);

                    $pre->setFetchMode(PDO::FETCH_ASSOC);

                    if ($pre->execute() == 1) {
                        $this->pdo->commit();

                        $result = $pre->fetch();

                        return empty($result['location_id']) ? false : $result['location_id'];
                    }
                }
                catch(PDOException $e) {
                    $this->pdo->commit();
                    return false;
                }

                return false;
            }

            /**
             * Add recommend
             *
             */
            function addRecommend($location_name, $serial_num)
            {
                $location_id = $this->getLocationId($location_name);

                if ($location_id != false) {
                    if (!$this->recommendRecordExist($location_id, $serial_num)) {
                        try {
                            $this->pdo->beginTransaction();
                            $pre = $this->pdo->prepare('INSERT INTO record (location_id, device_serial_num) VALUES(:location_id, :serial_num)');
                            $pre->bindParam(':location_id', $location_id);
                            $pre->bindParam(':serial_num', $serial_num);
                            $pre->execute();
                            $this->pdo->commit();

                            return DbCode::RECOMM_RECORD_SUCCESS;
                        }
                        catch(PDOException $e) {
                            $this->pdo->rollback();

                            return DbCode::RECOMM_RECORD_UNKNOWN_ERROR;
                        }
                    }
                    else {
                        return DbCode::RECOMM_RECORD_EXIST;
                    }
                }

                return DbCode::RECOMM_RECORD_UNKNOWN_ERROR;
            }

            /**
             * Delete recommend
             *
             */
            function deleteRecommend($location_name, $serial_num)
            {
                $locaion_id = $this->getLocationId($location_name);

                if ($this->recommendRecordExist($locaion_id, $serial_num)) {
                    try {
                        $this->pdo->beginTransaction();
                        $pre = $this->pdo->prepare('DELETE FROM record WHERE location_id = :locationId AND device_serial_num = :serialNum');
                        $pre->bindParam(':locationId', $locaion_id);
                        $pre->bindParam(':serialNum', $serial_num);
                        $pre->execute();
                        $affetected_rows = $pre->rowCount();
                        $this->pdo->commit();

                        return $affetected_rows == 1 ? DbCode::RECOMM_DELETE_SUCCESS : DbCode::RECOMM_DELETE_NOT_CONTENT;
                    }
                    catch(PDOException $e) {
                        $this->pdo->rollback();
                        return DbCode::RECOMM_UNKNOWN_ERROR;
                    }
                }
                else {
                    return DbCode::RECOMM_DELETE_NOT_CONTENT;
                }

                return DbCode::RECOMM_UNKNOWN_ERROR;
            }

            /**
             * Get the number of recommendation
             *
             */
            function getRecommendationNum($location_name)
            {            
                $location_id = $this->getLocationId($location_name, true);

                if ($location_id == false) {
                    return false;
                }

                try {
                    $this->pdo->beginTransaction();
                    $pre = $this->pdo->prepare('SELECT COUNT(location_id) AS count FROM record WHERE location_id = :locationId');
                    $pre->bindParam(':locationId', $location_id);
                    $pre->setFetchMode(PDO::FETCH_ASSOC);
                    $pre->execute();
                    $this->pdo->commit();

                    $result = $pre->fetch();

                    return $result['count'];
                }
                catch(PDOException $e) {
                    $this->pdo->rollback();
                    return DbCode::RECOMM_NUM_ERROR;
                }
            }

            /**
             * Get specifized device add recommend location name
             *
             */
            function getRecommendedLocationName($serial_num)
            {

                try {
                    $this->pdo->beginTransaction();
                    $pre = $this->pdo->prepare('SELECT location.location_name AS location_name FROM record JOIN location ON record.location_id = location.location_id WHERE record.device_serial_num = :serialNum');
                    $pre->bindParam(':serialNum', $serial_num);
                    $pre->setFetchMode(PDO::FETCH_ASSOC);
                    $pre->execute();
                    $this->pdo->commit();

                    return $pre->fetchAll(PDO::FETCH_COLUMN);
                }
                catch(PDOException $e) {
                    $this->pdo->rollback();

                    return false;
                }

                return false;
            }

            /**
             * Check the recommed record exists or not
             *
             */
            function recommendRecordExist($location_id, $serial_num)
            {
                try {
                    $this->pdo->beginTransaction();
                    $pre = $this->pdo->prepare('SELECT COUNT(location_id) AS count FROM record WHERE location_id = :locationId AND device_serial_num = :serialNum');
                    $pre->bindParam(':locationId', $location_id);
                    $pre->bindParam(':serialNum', $serial_num);
                    $pre->setFetchMode(PDO::FETCH_ASSOC);
                    $pre->execute();
                    $this->pdo->commit();

                    $result = $pre->fetch();

                    return $result['count'] > 0 ? true : false;
                }
                catch(PDOException $e) {
                    $this->pdo->rollback();
                    return true;
                }
            }

        }

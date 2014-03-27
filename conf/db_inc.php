<?php
    /**
     * Database configurations
     *
     */
    $db = array();
    $db['db_host'] = getenv('OPENSHIFT_MYSQL_DB_HOST');
    $db['db_name'] = getenv('OPENSHIFT_GEAR_NAME');
    $db['db_user'] = getenv('OPENSHIFT_MYSQL_DB_USERNAME');
    $db['db_pass'] = getenv('OPENSHIFT_MYSQL_DB_PASSWORD');

<?php

class Constant {
    public static $dbUserName = 'root';
    public static $dbUserPassword = 'Globalfm@19';
    public static $dbName = 'gems';
    public static $dbHost = '10.101.11.71';
    public static $redisHost = '127.0.0.1';
    public static $redisPort = 6379;
    public static $isLogged = true;
    public static $folderDebug = 'C:\Users\User\logs\gems\\';



    public static $err = array(
        'default' => 'Error on system. Please contact Administrator!'
    );

    public static $attGroupErr = array(
        'siteAlreadyEnabled' => 'Site __ already enabled! Please refresh the page.',
        'siteAlreadyDisabled' => 'Site __ already disabled! Please refresh the page.'
    );

    public static $attGroupSuc = array(
        'enabled' => 'Site __ successfully enabled!',
        'disabled' => 'Site __ successfully disabled!'
    );
}
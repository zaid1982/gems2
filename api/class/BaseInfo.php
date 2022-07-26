<?php


class BaseInfo extends General {
    public $userId = '';
    public $userName = '';
    public $redis;

    /**
     * @throws Exception
     */
    function __construct() {
        try {
            self::connectRedis();
        }
        catch(Exception|Throwable $ex) {
            self::logError(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($ex->getMessage(), $ex->getCode());
        }
    }

    public static function getUserId ():string {
        return self::$userId;
    }

    public static function getUserName ():string {
        return self::$userName;
    }

    public static function setUserId ($userId) {
        self::$userId = $userId;
    }

    /**
     * @throws Exception
     */
    public static function connectRedis () {
        try {
            self::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            self::$redis = new Redis();
            self::$redis->connect(Constant::$redisHost, Constant::$redisPort);
        }
        catch(Exception|Throwable $ex) {
            self::logError(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @throws Exception
     */
    public static function closeRedis () {
        try {
            self::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            self::$redis->close();
        }
        catch(Exception|Throwable $ex) {
            self::logError(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @throws Exception
     */
    public static function getUserInfo() {
        try {
            self::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

        }
        catch(Exception|Throwable $ex) {
            self::logError(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($ex->getMessage(), $ex->getCode());
        }
    }
}
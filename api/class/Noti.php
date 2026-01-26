<?php

class Noti extends General {

    function __construct (int $userId=0, bool $isLogged=false) {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @param int $receiverId
     * @param int $notiTextId
     * @param array $notiParams
     * @return void
     * @throws Exception
     */
    public function prepare (int $receiverId, int $notiTextId, array $notiParams=array()): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($receiverId, 'receiverId');
            parent::checkEmptyInteger($notiTextId, 'notiTextId');

            // Check if user account is active before sending notification
            $userStatus = DbMysql::selectColumn('sys_user', array('userId'=>$receiverId), 'userStatus');
            if (empty($userStatus) || $userStatus !== 1) {
                parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Skipping notification for disabled user: '.$receiverId);
                return; // Skip sending notification to disabled accounts
            }

            $notiText = DbMysql::select('noti_text', array('notiTextId'=>$notiTextId));
            $userToken = DbMysql::selectColumn('sys_user', array('userId'=>$receiverId), 'userToken');

            if (!empty($notiText) && !empty($userToken)) {
                $notiTextTitle = $notiText['notiTextTitle'];
                $notiTextHtml = $notiText['notiTextHtml'];
                $notiParameterArr = DbMysql::selectAll('noti_parameter', array('notiTextId'=>$notiTextId));
                foreach ($notiParameterArr as $notiParameter) {
                    $paramCode = $notiParameter['notiParamCode'];
                    parent::checkMandatoryArray($notiParams, array($paramCode));
                    if (strpos($notiTextTitle,"[".$paramCode."]")) {
                        $notiTextTitle = str_replace("[".$paramCode."]", $notiParams[$paramCode], $notiTextTitle);
                    }
                    if (strpos($notiTextHtml,"[".$paramCode."]")) {
                        $notiTextHtml = str_replace("[".$paramCode."]", $notiParams[$paramCode], $notiTextHtml);
                    }
                }
                DbMysql::insert('noti_send', array('notiTextId'=>$notiTextId, 'notiTo'=>$userToken, 'notiTitle'=>$notiTextTitle, 'notiHtml'=>$notiTextHtml, 'userId'=>$receiverId));
            }
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}
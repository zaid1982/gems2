<?php

class Email extends General {

    function __construct (int $userId=0, bool $isLogged=false) {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @param int $receiverId
     * @param int $emailTemplateId
     * @param array $emailParams
     * @param bool $isExpress
     * @return void
     * @throws Exception
     */
    public function prepare (int $receiverId, int $emailTemplateId, array $emailParams=array(), bool $isExpress=false): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($receiverId, 'receiverId');
            parent::checkEmptyInteger($emailTemplateId, 'emailTemplateId');

            $emailTemplate = DbMysql::select('email_template', array('emailTemplateId'=>$emailTemplateId));
            $receiverName = DbMysql::selectColumn('sys_user', array('userId'=>$receiverId), 'userFirstName');
            $emailAddress = DbMysql::selectColumn('sys_user_profile', array('userId'=>$receiverId, 'user_profile_status'=>'1'), 'userEmail');

            if (!empty($emailTemplate) && !empty($emailAddress) && !empty($receiverName)) {
                $emailTitle = $emailTemplate['emailTemplateTitle'];
                $emailHtml = $emailTemplate['emailTemplateHtml'];
                $emailParameterArr = DbMysql::selectAll('email_parameter', array('emailTemplateId'=>$emailTemplateId));
                foreach ($emailParameterArr as $emailParameter) {
                    $paramCode = $emailParameter['emailParamCode'];
                    parent::checkMandatoryArray($emailParams, array($paramCode));
                    if (strpos($emailTitle,"[".$paramCode."]")) {
                        $emailTitle = str_replace ("[".$paramCode."]", $emailParams[$paramCode], $emailTitle);
                    }
                    if (strpos($emailHtml,"[".$paramCode."]")) {
                        $emailHtml = str_replace ("[".$paramCode."]", $emailParams[$paramCode], $emailHtml);
                    }
                }
                $emailHtml = str_replace ("[fullName]", $receiverName, $emailHtml);
                DbMysql::insert('email_send', array('emailTemplateId'=>$emailTemplateId, 'emailAddress'=>$emailAddress, 'emailTitle'=>$emailTitle, 'emailHtml'=>$emailHtml, 'userId'=>$receiverId));
            }
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}
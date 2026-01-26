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
    public function prepare (int $receiverId, int $emailTemplateId, array $emailParams=array(), $fullName='', $emailAddress=''): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($receiverId, 'receiverId');
            parent::checkEmptyInteger($emailTemplateId, 'emailTemplateId');

            // Check if user account is active before sending email
            $userStatus = DbMysql::selectColumn('sys_user', array('userId'=>$receiverId), 'userStatus');
            if (empty($userStatus) || $userStatus !== 1) {
                parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Skipping email for disabled user: '.$receiverId);
                return; // Skip sending email to disabled accounts
            }

            $emailTemplate = DbMysql::select('email_template', array('emailTemplateId'=>$emailTemplateId));
            $receiverName = !empty($fullName) ? $fullName : DbMysql::selectColumn('sys_user', array('userId'=>$receiverId), 'userFirstName');
            $emailAddress = !empty($emailAddress) ? $emailAddress : DbMysql::selectColumn('sys_user_profile', array('userId'=>$receiverId, 'user_profile_status'=>'1'), 'userEmail');

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
                // Optional attachment support: pass 'emailAttachment' and 'emailFilename' in emailParams
                $attachmentPath = '';
                $attachmentName = '';
                if (array_key_exists('emailAttachment', $emailParams) && !empty($emailParams['emailAttachment'])) {
                    $attachmentPath = $emailParams['emailAttachment'];
                }
                if (array_key_exists('emailFilename', $emailParams) && !empty($emailParams['emailFilename'])) {
                    $attachmentName = $emailParams['emailFilename'];
                }
                $insertArr = array(
                    'emailTemplateId' => $emailTemplateId,
                    'emailAddress' => $emailAddress,
                    'emailTitle' => $emailTitle,
                    'emailHtml' => $emailHtml,
                    'userId' => $receiverId
                );
                if (!empty($attachmentPath) && !empty($attachmentName)) {
                    $insertArr['emailAttachment'] = $attachmentPath;
                    $insertArr['emailFilename'] = $attachmentName;
                }
                DbMysql::insert('email_send', $insertArr);
            }
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}
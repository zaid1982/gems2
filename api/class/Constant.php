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
    //public static $url = '//gems.globalfm.com.my/api/';
    public static $url = '//localhost:8081/gems2/api/';

    public static $err = array(
        'default' => 'Error on system. Please contact Administrator!'
    );

    public static $task = array(
        'errAlreadySubmitted' => 'This task already submitted!',
        'errClaimed' => 'This task currently assigned to other user!',
        'errNotClaimed' => 'This task should be claimed before submission!',
        'errInvalidRole' => 'You do not have __ role to perform this task!'
    );

    public static $attGroup = array(
        'siteEnabled' => 'Site __ successfully enabled!',
        'siteDisabled' => 'Site __ successfully disabled!',
        'errSiteAlreadyEnabled' => 'Site __ already enabled! Please refresh the page.',
        'errSiteAlreadyDisabled' => 'Site __ already disabled! Please refresh the page.'
    );

    public static $fcaTask = array(
        'submitNew' => 'New FCA Audit successfully submitted for recommendation process!',
        'submitRecommend' => 'FCA Audit Recommendation successfully submitted for validation process!',
        'submitCorrection' => 'FCA Audit Recommendation successfully returned to auditor for correction!',
        'submitValidate' => 'FCA Audit Recommendation successfully submitted and complete!',
        'resubmit' => 'FCA Audit Correction successfully resubmitted for recommendation process!',
        'excludeReport' => 'FCA Audit No. __ successfully set to be exclude from PDF Report!',
        'includeReport' => 'FCA Audit No. __ successfully set to be include in PDF Report!',
        'delete' => 'FCA Audit No. __ successfully been deleted!'
    );

    public static $fcaZone = array(
        'add' => 'Zone __ successfully registered!',
        'update' => 'Zone __ successfully updated!',
        'delete' => 'Zone __ successfully deleted!',
        'errStillExist' => 'Zone __ cannot be deleted because it still exist in FCA record!',
        'errAlreadyExist' => 'Zone __ already exist under similar site!'
    );

    public static $fcaDefectCategory = array(
        'add' => 'Defect Category __ successfully registered!',
        'update' => 'Defect Category __ successfully updated!',
        'delete' => 'Defect Category __ successfully deleted!',
        'errStillExist' => 'Defect Category __ cannot be deleted because it still exist in FCA record!',
        'errAlreadyExist' => 'Defect Category __ already exist!'
    );

    public static $fcaDefectCategorySite = array(
        'add' => 'Defect Category __ successfully registered from ___ site!',
        'delete' => 'Defect Category __ successfully deleted from ___ site!',
        'errAlreadyExist' => 'Defect Category __ already exist in ___ site!'
    );

    public static $fcaReport = array(
        'add' => 'FCA Report __ successfully generated. Please check the PDF from the FCA PDF Report List',
        'delete' => 'FCA Report __ successfully deleted!',
        'errEmpty' => 'The requested report is empty!'
    );
}
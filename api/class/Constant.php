<?php

class Constant {
    //public static $dbUserName = 'root';
    public static $dbUserName = 'zaid';
    //public static $dbUserPassword = 'password';
    public static $dbUserPassword = 'Amlyda@1982';
    //public static $dbUserPassword = 'Globalfm@19';
    public static $dbName = 'gems';
    public static $dbHost = 'localhost';
    //public static $dbHost = '10.101.11.71';
    //public static $folderDebug = '../../../logs/gems/debug/';
    //public static string $folderDebug = 'C:\xampp\logs\gems\debug\\';
    public static $folderDebug = '../../../logs/gems.metadatasystem.my/debug/';
    //public static $folderError = '../../../logs/gems/error/';
    //public static string $folderError = 'C:\xampp\logs\gems\error\\';
    public static $folderError = '../../../logs/gems.metadatasystem.my/error/';
    //public static $url = '//localhost/gems2/api/';
    //public static $url = '//gems.globalfm.com.my/api/';
    public static $url = '//gems.metadatasystem.my/api/';
    public static $redisHost = '127.0.0.1';
    public static $redisPort = 6379;
    public static $isLogged = true;
    public static $publicUser = 'Public User';

    public static $err = array(
        'default' => 'Error on system. Please contact Administrator!'
    );

    public static $task = array(
        'errAlreadySubmitted' => 'This task already submitted!',
        'errClaimed' => 'This task currently assigned to other user!',
        'errNotClaimed' => 'This task should be claimed before submission!',
        'errInvalidRole' => 'You do not have __ role to perform this task!',
        'errAlreadySubmitted2' => 'This task __ already submitted!',
        'errNotAllowed' => 'You are not allowed to perform this action!'
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
        'delete' => 'Zone __ successfully removed!',
        'errStillExist' => 'Zone __ cannot be deleted because it still exist in FCA record!',
        'errAlreadyExist' => 'Zone __ already exist under similar site!'
    );

    public static $fcaDefectCategory = array(
        'add' => 'Defect Category __ successfully registered!',
        'update' => 'Defect Category __ successfully updated!',
        'delete' => 'Defect Category __ successfully removed!',
        'errStillExist' => 'Defect Category __ cannot be deleted because it still exist in FCA record!',
        'errAlreadyExist' => 'Defect Category __ already exist!'
    );

    public static $fcaDefectCategorySite = array(
        'add' => 'Defect Category __ successfully registered from ___ site!',
        'delete' => 'Defect Category __ successfully removed from ___ site!',
        'errAlreadyExist' => 'Defect Category __ already exist in ___ site!'
    );

    public static $fcaReport = array(
        'add' => 'FCA Report __ successfully generated. Please check the PDF from the FCA PDF Report List',
        'delete' => 'FCA Report __ successfully removed!',
        'errEmpty' => 'The requested report is empty!'
    );

    public static $attGroup = array(
        'add' => 'Attendance Group __ successfully registered!',
        'update' => 'Attendance Group __ successfully updated!',
        'siteEnabled' => 'Site __ successfully enabled!',
        'siteDisabled' => 'Site __ successfully disabled!',
        'errSiteAlreadyEnabled' => 'Site __ already enabled! Please refresh the page.',
        'errSiteAlreadyDisabled' => 'Site __ already disabled! Please refresh the page.',
        'errAlreadyExist' => 'Attendance Group __ already exist under similar site!'
    );

    public static $attParticipant = array(
        'add' => 'Employee _1 successfully assigned to _2 group!',
        'update' => 'Employee __ attendance configuration successfully updated!',
        'errAlreadyAssigned' => 'Employee _1 already assigned to _2 group!'
    );

    public static $attTransaction = array(
        'update' => 'Employee _1 daily status for date _2 successfully updated!',
        'checkIn' => 'You successfully checked in!',
        'checkOut' => 'You successfully checked out!',
        'errUpdateValidation' => 'Please make sure either Daily Status or Attendance Status selected!',
        'rescheduleSite' => 'Site _1 attendance planner for year _2 and month _3 successfully rescheduled!',
        'rescheduleGroup' => 'Group _1 attendance planner for year _2 and month _3 successfully rescheduled!'
    );

    public static $woTaskRequest = array(
        'draft' => 'New draft material request successfully created!',
        'delete' => 'Request __ successfully removed!',
        'submit' => 'Request __ successfully submitted!',
        'errAlreadySubmitted' => 'Request __ already submitted!',
        'errNotAllowed' => 'You are not allowed to perform this action!',
        'errAlreadyRemoved' => 'Request already removed!'
    );

    public static $woTaskParts = array(
        'add' => 'Material __ successfully added into Request List!',
        'update' => 'Material __ successfully updated!',
        'delete' => 'Material __ successfully removed!',
        'errRequestAlreadySubmitted' => 'Request __ already submitted!',
        'errAlreadySubmitted' => 'Material __ already submitted!',
        'errAlreadyExist' => 'Material __ already exist in this Request List. You can add quantity by modify the similar material.',
        'errNotAllowed' => 'You are not allowed to perform this action!',
    );

    public static $zone = array(
        'add' => 'Zone __ successfully registered!',
        'update' => 'Zone __ successfully updated!',
        'delete' => 'Zone __ successfully removed!',
        'errStillExist' => 'Zone __ cannot be deleted because it already exist in WO/WR record! Please deactivate the zone if you want to disable from form selection.',
        'errAlreadyExist' => 'Zone __ already exist under similar site!'
    );

    public static $wo = array(
        'submitPublic' => 'We have received your Complaint. You will be contacted soon. You will receive notification soon on your email.',
        'returnVerify' => 'Work Order successfully returned to technician!',
        'submitVerify' => 'Work Order successfully verified and closed!',
        'assign' => 'Work Order successfully assigned!',
        'reassign' => 'Work Order successfully reassigned!',
        'update' => 'Work Order successfully updated!'
    );

    public static $ppm = array(
        'addGroup' => 'PPM Asset Group successfully registered!',
        'updateGroup' => 'PPM Asset Group successfully updated!',
        'deleteGroup' => 'PPM Asset Group successfully removed!',
        'submitGroup' => 'PPM Asset Group successfully submitted for Task Scheduling!',
        'errAssigned' => 'This PPM Asset Group cannot be deleted because it is already assigned the PPM Tasks!'
    );

    public static $asset = array(
        'add' => 'Asset successfully assigned to _2 group!',
        'update' => 'Asset Information successfully updated!',
        'delete' => 'Asset successfully removed!'
    );
}
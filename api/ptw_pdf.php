<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once '../function/f_ptw.php';
require_once 'pdf/tcpdf_include.php';
require_once 'pdf/ptw.php';

$api_name = 'api_ptw_pdf';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_pdf_ptw = new Class_pdf_ptw();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_pdf_ptw->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
        $jwt_data = $fn_login->check_jwt($headers['Authorization']);
    } else if (isset($headers['authorization'])) {
        $jwt_data = $fn_login->check_jwt($headers['authorization']);
    } else {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty - '.json_encode($headers));
    }

    if ('GET' === $request_method) {
        $type = filter_input(INPUT_GET, 'type');
        $ptwPermitId = filter_input(INPUT_GET, 'ptwPermitId');
        
        if ($type === 'preview_pdf') {
            $fn_pdf_ptw->__set('ptwPermitId', $ptwPermitId);
            $returnVal = $fn_pdf_ptw->create_pdf();
            $result = $fn_general->getPdf($returnVal['pdfId']);
            $fn_general->save_audit('118', $jwt_data->userId, 'PTW Permit no. = '.$returnVal['ptwPermitNumber']);
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter type invalid');
        }

        $form_data['result'] = $result;
        $form_data['success'] = true;
    } else {
        throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
    }
    
    Class_db::getInstance()->db_close();
} catch (Exception $ex) {
    if ($is_transaction) {
        Class_db::getInstance()->db_rollback();
    }
    Class_db::getInstance()->db_close();
    $form_data['error'] = substr($ex->getMessage(), strpos($ex->getMessage(), '] - ') + 4);
    $form_data['errmsg'] = $constant::ERR_DEFAULT;
    $fn_general->log_error('API', $api_name, __LINE__, $ex->getMessage());
}

echo json_encode($form_data);

switch ($_POST['action']) {

    case 'create_pdf':
        try {
            if (empty($_POST['ptw_id'])) {
                throw new Exception('PTW ID is required');
            }

            $ptwId = intval($_POST['ptw_id']);
            
            // Check if PTW exists
            $ptwData = Class_db::getInstance()->db_select_single('ptw_permits', array('ptw_id' => $ptwId), null, 1);
            if (empty($ptwData)) {
                throw new Exception('PTW not found');
            }

            // Create PDF
            $pdfClass = new Class_pdf_ptw();
            $pdfClass->fn_general = $fn_general;
            $pdfClass->ptwId = $ptwId;

            $result = $pdfClass->create_pdf();

            $array_post['status'] = '1';
            $array_post['message'] = 'PDF created successfully';
            $array_post['data'] = $result;

        } catch (Exception $ex) {
            $fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            $array_post['status'] = '0';
            $array_post['message'] = $ex->getMessage();
        }
        break;

    case 'download_pdf':
        try {
            if (empty($_POST['ptw_id'])) {
                throw new Exception('PTW ID is required');
            }

            $ptwId = intval($_POST['ptw_id']);
            
            // Get PTW data
            $ptwData = Class_db::getInstance()->db_select_single('ptw_permits', array('ptw_id' => $ptwId), null, 1);
            if (empty($ptwData)) {
                throw new Exception('PTW not found');
            }

            // Get PDF information
            $pdfData = null;
            if (!empty($ptwData['pdf_id'])) {
                $pdfData = Class_db::getInstance()->db_select_single('sys_pdf', array('pdf_id' => $ptwData['pdf_id']), null, 1);
            }

            if (empty($pdfData)) {
                throw new Exception('PDF not found. Please generate PDF first.');
            }

            // Build file path
            $config = parse_ini_file('library/config.ini');
            $environment = $config['environment'];
            
            if ($environment == 'windows') {
                $filePath = dirname(__FILE__) . '\ptw\\' . basename($pdfData['pdf_folder']) . '\\' . $pdfData['pdf_filename'];
            } else {
                $filePath = dirname(__FILE__) . '/ptw/' . basename($pdfData['pdf_folder']) . '/' . $pdfData['pdf_filename'];
            }

            if (!file_exists($filePath)) {
                throw new Exception('PDF file not found on server');
            }

            // Return download information
            $array_post['status'] = '1';
            $array_post['message'] = 'PDF ready for download';
            $array_post['data'] = array(
                'filename' => $pdfData['pdf_filename'],
                'download_url' => 'api/ptw_pdf.php?action=get_file&ptw_id=' . $ptwId
            );

        } catch (Exception $ex) {
            $fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            $array_post['status'] = '0';
            $array_post['message'] = $ex->getMessage();
        }
        break;

    case 'get_file':
        try {
            if (empty($_GET['ptw_id'])) {
                throw new Exception('PTW ID is required');
            }

            $ptwId = intval($_GET['ptw_id']);
            
            // Get PTW data
            $ptwData = Class_db::getInstance()->db_select_single('ptw_permits', array('ptw_id' => $ptwId), null, 1);
            if (empty($ptwData)) {
                throw new Exception('PTW not found');
            }

            // Get PDF information
            $pdfData = null;
            if (!empty($ptwData['pdf_id'])) {
                $pdfData = Class_db::getInstance()->db_select_single('sys_pdf', array('pdf_id' => $ptwData['pdf_id']), null, 1);
            }

            if (empty($pdfData)) {
                throw new Exception('PDF not found');
            }

            // Build file path
            $config = parse_ini_file('library/config.ini');
            $environment = $config['environment'];
            
            if ($environment == 'windows') {
                $filePath = dirname(__FILE__) . '\ptw\\' . basename($pdfData['pdf_folder']) . '\\' . $pdfData['pdf_filename'];
            } else {
                $filePath = dirname(__FILE__) . '/ptw/' . basename($pdfData['pdf_folder']) . '/' . $pdfData['pdf_filename'];
            }

            if (!file_exists($filePath)) {
                throw new Exception('PDF file not found on server');
            }

            // Send file for download
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="PTW_' . $ptwData['ptw_permit_number'] . '.pdf"');
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            
            readfile($filePath);
            exit();

        } catch (Exception $ex) {
            $fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            header('HTTP/1.1 500 Internal Server Error');
            echo 'Error: ' . $ex->getMessage();
            exit();
        }
        break;

    case 'check_pdf_status':
        try {
            if (empty($_POST['ptw_id'])) {
                throw new Exception('PTW ID is required');
            }

            $ptwId = intval($_POST['ptw_id']);
            
            // Get PTW data
            $ptwData = Class_db::getInstance()->db_select_single('ptw_permits', array('ptw_id' => $ptwId), null, 1);
            if (empty($ptwData)) {
                throw new Exception('PTW not found');
            }

            $hasPdf = !empty($ptwData['pdf_id']) && $ptwData['ptw_is_pdf'] == '1';
            $pdfCreatedDate = null;
            $pdfFilename = null;

            if ($hasPdf) {
                $pdfData = Class_db::getInstance()->db_select_single('sys_pdf', array('pdf_id' => $ptwData['pdf_id']), null, 1);
                if (!empty($pdfData)) {
                    $pdfCreatedDate = $fn_general->convertDateToDisplay($pdfData['pdf_timeCreated']);
                    $pdfFilename = $pdfData['pdf_filename'];
                }
            }

            $array_post['status'] = '1';
            $array_post['message'] = 'PDF status retrieved';
            $array_post['data'] = array(
                'has_pdf' => $hasPdf,
                'pdf_created_date' => $pdfCreatedDate,
                'pdf_filename' => $pdfFilename,
                'ptw_permit_number' => $ptwData['ptw_permit_number']
            );

        } catch (Exception $ex) {
            $fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            $array_post['status'] = '0';
            $array_post['message'] = $ex->getMessage();
        }
        break;

    case 'regenerate_pdf':
        try {
            if (empty($_POST['ptw_id'])) {
                throw new Exception('PTW ID is required');
            }

            $ptwId = intval($_POST['ptw_id']);
            
            // Check if PTW exists
            $ptwData = Class_db::getInstance()->db_select_single('ptw_permits', array('ptw_id' => $ptwId), null, 1);
            if (empty($ptwData)) {
                throw new Exception('PTW not found');
            }

            // Delete existing PDF file if exists
            if (!empty($ptwData['pdf_id'])) {
                $pdfData = Class_db::getInstance()->db_select_single('sys_pdf', array('pdf_id' => $ptwData['pdf_id']), null, 1);
                if (!empty($pdfData)) {
                    $config = parse_ini_file('library/config.ini');
                    $environment = $config['environment'];
                    
                    if ($environment == 'windows') {
                        $filePath = dirname(__FILE__) . '\ptw\\' . basename($pdfData['pdf_folder']) . '\\' . $pdfData['pdf_filename'];
                    } else {
                        $filePath = dirname(__FILE__) . '/ptw/' . basename($pdfData['pdf_folder']) . '/' . $pdfData['pdf_filename'];
                    }

                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            }

            // Create new PDF
            $pdfClass = new Class_pdf_ptw();
            $pdfClass->fn_general = $fn_general;
            $pdfClass->ptwId = $ptwId;

            $result = $pdfClass->create_pdf();

            $array_post['status'] = '1';
            $array_post['message'] = 'PDF regenerated successfully';
            $array_post['data'] = $result;

        } catch (Exception $ex) {
            $fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            $array_post['status'] = '0';
            $array_post['message'] = $ex->getMessage();
        }
        break;

    default:
        $array_post['status'] = '99';
        $array_post['message'] = 'Invalid action';
        break;
}

echo json_encode($array_post);

$fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Exiting ptw_pdf.php - Status: ' . $array_post['status']);

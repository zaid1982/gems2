<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_ptw.php';
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
        $action = filter_input(INPUT_GET, 'action');
        $ptwIdForGet = filter_input(INPUT_GET, 'ptw_id', FILTER_VALIDATE_INT);

        // Direct file download via GET?action=get_file
        if ($action === 'get_file') {
            try {
                if (empty($ptwIdForGet)) {
                    throw new Exception('PTW ID is required');
                }

                // Prefer new schema; fallback to legacy
                $ptwData = null;
                try {
                    $ptwData = Class_db::getInstance()->db_select_single('ptw_permit', array('ptw_permit_id' => $ptwIdForGet), null, 1);
                } catch (Exception $e) { /* ignore */ }
                if (empty($ptwData)) {
                    $ptwData = Class_db::getInstance()->db_select_single('ptw_permits', array('ptw_id' => $ptwIdForGet), null, 1);
                }

                if (empty($ptwData)) {
                    throw new Exception('PTW not found');
                }

                // Build file path
                $config = parse_ini_file('library/config.ini');
                $environment = $config['environment'];
                $filePath = null;

                // New schema: compute deterministic filename and path without relying on pdf_id
                if (isset($ptwData['ptw_permit_id'])) {
                    $folder_code = floor(intval($ptwData['ptw_permit_id'])/1000);
                    $filename = 'ptw_' . substr((10000000+intval($ptwData['ptw_permit_id'])), 1) . '.pdf';
                    // PDFs now live under /gems2/upload/ptw/pdf/<folder>/<file>
                    $filePath = dirname(__FILE__) . '/../upload/ptw/pdf/' . $folder_code . '/' . $filename;
                }

                // If not new schema or file missing, fallback to sys_pdf lookup via legacy linkage
                if (empty($filePath) || !file_exists($filePath)) {
                    $pdfData = null;
                    if (!empty($ptwData['pdf_id'])) {
                        $pdfData = Class_db::getInstance()->db_select_single('sys_pdf', array('pdf_id' => $ptwData['pdf_id']), null, 1);
                    }
                    if (!empty($pdfData)) {
                        // If sys_pdf is present, respect its recorded folder (expected 'upload/ptw/pdf/<code>')
                        $filePath = dirname(__FILE__) . '/../' . trim($pdfData['pdf_folder'], '/\\') . '/' . $pdfData['pdf_filename'];
                    }
                }

                if (empty($filePath) || !file_exists($filePath)) {
                    throw new Exception('PDF file not found on server');
                }

                // Send file for download; use permit number else request number in filename
                $displayNo = !empty($ptwData['ptw_permit_number']) ? $ptwData['ptw_permit_number'] : ($ptwData['ptw_request_number'] ?? ('ID'.$ptwIdForGet));
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="PTW_' . $displayNo . '.pdf"');
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
        }

        if ($type === 'html_template') {
            if (empty($ptwPermitId)) { throw new Exception('[' . __LINE__ . '] - Parameter ptwPermitId empty'); }
            $fn_pdf_ptw->__set('ptwId', $ptwPermitId);
            // Use the incorporated two-page design from the user's attachment
            $gen = $fn_pdf_ptw->create_pdf_from_attachment_design();
            $form_data['status'] = 'success';
            $form_data['pdf_url'] = 'api/ptw_pdf.php?action=get_file&ptw_id=' . urlencode($ptwPermitId);
            $result = $gen;
        } else if ($type === 'basic_pdf') {
            if (empty($ptwPermitId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ptwPermitId empty');
            }
            // No gating; this is for smoke testing only
            $fn_pdf_ptw->__set('ptwId', $ptwPermitId);
            $basic = $fn_pdf_ptw->create_basic_pdf();
            $form_data['status'] = 'success';
            $form_data['pdf_url'] = 'api/ptw_pdf.php?action=get_file&ptw_id=' . urlencode($ptwPermitId);
            $result = $basic;
        } else if ($type === 'preview_pdf') {
            // Only allow generating PDF once all approvals are completed (FM approved / Active or Closed)
            if (empty($ptwPermitId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ptwPermitId empty');
            }

            // Try to read status from current schema (ptw_permit) or legacy (ptw_permits)
            $ptwRow = null;
            try {
                $ptwRow = Class_db::getInstance()->db_select_single('ptw_permit', array('ptw_permit_id' => $ptwPermitId), null, 1);
            } catch (Exception $e) { /* ignore */ }
            if (empty($ptwRow)) {
                try {
                    $ptwRow = Class_db::getInstance()->db_select_single('ptw_permits', array('ptw_id' => $ptwPermitId), null, 1);
                } catch (Exception $e) { /* ignore */ }
            }

            if (empty($ptwRow)) {
                throw new Exception('[' . __LINE__ . '] - PTW not found');
            }

            $status = isset($ptwRow['ptw_status']) ? strtoupper($ptwRow['ptw_status']) : '';
            $allowed = array('ACTIVE', 'COMPLETED', 'CLOSED', 'APPROVED');
            if (!in_array($status, $allowed, true)) {
                throw new Exception('[' . __LINE__ . '] - PDF is only available after final approval (current status: ' . ($status ?: 'UNKNOWN') . ')');
            }

            $fn_pdf_ptw->__set('ptwId', $ptwPermitId);
            $returnVal = $fn_pdf_ptw->create_pdf();
            $result = $fn_general->getPdf($returnVal['pdfId']);
            $fn_general->save_audit('118', $jwt_data->userId, 'PTW Permit no. = '.$returnVal['ptwPermitNumber']);

            // Normalize response shape for frontends expecting top-level fields
            $form_data['status'] = 'success';
            // Provide a stable download URL via get_file
            $form_data['pdf_url'] = 'api/ptw_pdf.php?action=get_file&ptw_id=' . urlencode($ptwPermitId);
            if (is_array($result)) {
                // Bubble up useful fields if present
                if (isset($result['filename'])) { $form_data['filename'] = $result['filename']; }
                if (isset($result['pdf_filename'])) { $form_data['filename'] = $result['pdf_filename']; }
            }
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
// Ensure GET requests don't fall through to POST action handler below
if ($_SERVER['REQUEST_METHOD'] === 'GET') { exit; }

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

            // Send file for download; use permit number else request number in filename
            $displayNo = !empty($ptwData['ptw_permit_number']) ? $ptwData['ptw_permit_number'] : ($ptwData['ptw_request_number'] ?? ('ID'.$ptwId));
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="PTW_' . $displayNo . '.pdf"');
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

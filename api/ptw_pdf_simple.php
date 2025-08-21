<?php
header('Content-Type: application/json');

// Include required files following WO PDF pattern
require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once '../function/f_ptw.php';
require_once 'tcpdf/tcpdf.php';

// Initialize classes like WO API does
date_default_timezone_set("Asia/Kuala_Lumpur");
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

$constant = new Class_constant();
$fn_general = new Class_general();

// Set up class dependencies
$fn_general->__set('constant', $constant);

// Test database connection
try {
    $test_db = Class_db::getInstance();
    error_log("PTW PDF: Database connection test successful");
} catch (Exception $e) {
    error_log("PTW PDF: Database connection failed: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(array('success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()));
    exit;
}

// PTW PDF Class
class MYPDF_ptw extends TCPDF {
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 9);
        $this->Line(PDF_MARGIN_LEFT, $this->y, $this->w - PDF_MARGIN_RIGHT, $this->y);
        $pageNo = 'Page '.strval($this->getAliasNumPage()).' of '.$this->getAliasNbPages();
        $this->Cell(180, 6, $pageNo, 0, 0, 'R', 0);
    }
}

class Class_pdf_ptw {
    private $ptwPermitId;

    function __construct() {
    }

    private function get_exception($codes, $function, $line, $msg) {
        if ($msg != '') {
            $pos = strpos($msg,'-');
            if ($pos !== false) {
                $msg = substr($msg, $pos+2);
            }
            return "(ErrCode:".$codes.") [".__CLASS__.":".$function.":".$line."] - ".$msg;
        } else {
            return "(ErrCode:".$codes.") [".__CLASS__.":".$function.":".$line."]";
        }
    }

    public function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        } else {
            throw new Exception($this->get_exception('0001', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    public function __set($property, $value) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        } else {
            throw new Exception($this->get_exception('0002', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    public function create_pdf() {
        try {
            if (empty($this->ptwPermitId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ptwPermitId Empty');
            }

            // Debug: Log the permit ID
            error_log("PTW PDF: Attempting to generate PDF for permit ID: " . $this->ptwPermitId);

            // Create new PDF document
            $pdf = new MYPDF_ptw(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // Set document information
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('GEMS 2.0');
            $pdf->SetTitle('GEMS 2.0 PTW');
            $pdf->SetSubject('GEMS 2.0 PTW Permit');

            // Remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(true);

            // Set header and footer fonts
            $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

            // Set default monospaced font
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

            // Set margins
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

            // Set auto page breaks
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

            // Set image scale factor
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

            // Add a page
            $pdf->AddPage();

            // Debug: Log before database query
            error_log("PTW PDF: About to query database for permit ID: " . $this->ptwPermitId);

            // Get permit data directly from database (following WO pattern)
            try {
                $permit = Class_db::getInstance()->db_select_single('ptw_permit', array('ptw_permit_id' => $this->ptwPermitId), null, 1);
                error_log("PTW PDF: Database query completed successfully");
            } catch (Exception $db_error) {
                error_log("PTW PDF: Database query failed: " . $db_error->getMessage());
                throw new Exception('Database query failed: ' . $db_error->getMessage());
            }
            
            // Debug: Log after database query
            error_log("PTW PDF: Found permit: " . (empty($permit) ? 'NO' : 'YES'));
            
            if (empty($permit)) {
                throw new Exception('PTW permit not found');
            }

            // Get site information
            $site = Class_db::getInstance()->db_select_single('sys_site', array('site_id' => $permit['site_id']));
            $site_name = $site ? $site['site_name'] : 'Unknown Site';

            // Get creator information
            $creator = null;
            if (!empty($permit['created_by'])) {
                $creator = Class_db::getInstance()->db_select_single('sys_user', array('user_id' => $permit['created_by']));
            }

            // Get supervisor information
            $supervisor = null;
            if (!empty($permit['approved_supervisor_by'])) {
                $supervisor = Class_db::getInstance()->db_select_single('sys_user', array('user_id' => $permit['approved_supervisor_by']));
            }

            // Get SHE information
            $she_officer = null;
            if (!empty($permit['approved_she_by'])) {
                $she_officer = Class_db::getInstance()->db_select_single('sys_user', array('user_id' => $permit['approved_she_by']));
            }

            // Get FM information
            $fm_officer = null;
            if (!empty($permit['approved_fm_by'])) {
                $fm_officer = Class_db::getInstance()->db_select_single('sys_user', array('user_id' => $permit['approved_fm_by']));
            }

            // Get workers
            $workers = Class_db::getInstance()->db_select('ptw_worker', array('ptw_permit_id' => $this->ptwPermitId), 'ptw_worker_id');

            // Header Section
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'PERMIT TO WORK (PTW)', 0, 1, 'C');
            $pdf->Ln(5);

            // Basic Information Section
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, '1. BASIC INFORMATION', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            
            $html = '<table border="1" cellpadding="3">
                <tr>
                    <td width="30%"><b>Permit Number:</b></td>
                    <td width="70%">'.$permit['ptw_permit_number'].'</td>
                </tr>
                <tr>
                    <td><b>Site:</b></td>
                    <td>'.$site_name.'</td>
                </tr>
                <tr>
                    <td><b>Work Description:</b></td>
                    <td>'.$permit['ptw_permit_description'].'</td>
                </tr>
                <tr>
                    <td><b>Work Area:</b></td>
                    <td>'.$permit['ptw_work_area'].'</td>
                </tr>
                <tr>
                    <td><b>Work Type:</b></td>
                    <td>'.$permit['ptw_work_type'].'</td>
                </tr>
                <tr>
                    <td><b>Risk Level:</b></td>
                    <td>'.$permit['ptw_risk_level'].'</td>
                </tr>
                <tr>
                    <td><b>Valid From:</b></td>
                    <td>'.date('d/m/Y H:i', strtotime($permit['ptw_valid_from'])).'</td>
                </tr>
                <tr>
                    <td><b>Valid To:</b></td>
                    <td>'.date('d/m/Y H:i', strtotime($permit['ptw_valid_to'])).'</td>
                </tr>
            </table>';
            
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(10);

            // Applicant Information
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, '2. APPLICANT INFORMATION', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            
            $html = '<table border="1" cellpadding="3">
                <tr>
                    <td width="30%"><b>Applicant Name:</b></td>
                    <td width="70%">'.(isset($permit['ptw_applicant_name']) ? $permit['ptw_applicant_name'] : '').'</td>
                </tr>
                <tr>
                    <td><b>Contact Number:</b></td>
                    <td>'.(isset($permit['ptw_applicant_contact']) ? $permit['ptw_applicant_contact'] : '').'</td>
                </tr>
                <tr>
                    <td><b>Department:</b></td>
                    <td>'.(isset($permit['ptw_applicant_company_dept']) ? $permit['ptw_applicant_company_dept'] : '').'</td>
                </tr>
                <tr>
                    <td><b>Contractor Company:</b></td>
                    <td>'.(isset($permit['ptw_contractor_company']) ? $permit['ptw_contractor_company'] : '').'</td>
                </tr>
            </table>';
            
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(10);

            // Workers Information
            if (!empty($workers)) {
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->Cell(0, 8, '3. WORKERS INFORMATION', 0, 1, 'L');
                $pdf->SetFont('helvetica', '', 10);
                
                $html = '<table border="1" cellpadding="3">
                    <tr style="background-color:#f0f0f0;">
                        <td width="30%"><b>Name</b></td>
                        <td width="25%"><b>Contact</b></td>
                        <td width="25%"><b>Role</b></td>
                        <td width="20%"><b>Certified</b></td>
                    </tr>';
                
                foreach ($workers as $worker) {
                    $certified = (isset($worker['worker_is_certified']) && $worker['worker_is_certified'] == 1) ? 'Yes' : 'No';
                    $html .= '<tr>
                        <td>'.$worker['worker_name'].'</td>
                        <td>'.$worker['worker_contact_number'].'</td>
                        <td>'.$worker['worker_role'].'</td>
                        <td>'.$certified.'</td>
                    </tr>';
                }
                
                $html .= '</table>';
                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Ln(10);
            }

            // Approval Section
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, '4. APPROVALS', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            
            $supervisor_status = isset($permit['ptw_supervisor_approval']) ? $permit['ptw_supervisor_approval'] : 'PENDING';
            $she_status = isset($permit['ptw_she_approval']) ? $permit['ptw_she_approval'] : 'PENDING';
            $fm_status = isset($permit['ptw_fm_approval']) ? $permit['ptw_fm_approval'] : 'PENDING';
            
            $supervisor_name = $supervisor ? ($supervisor['user_first_name'] . ' ' . $supervisor['user_last_name']) : '';
            $she_name = $she_officer ? ($she_officer['user_first_name'] . ' ' . $she_officer['user_last_name']) : '';
            $fm_name = $fm_officer ? ($fm_officer['user_first_name'] . ' ' . $fm_officer['user_last_name']) : '';
            
            $html = '<table border="1" cellpadding="3">
                <tr>
                    <td width="25%"><b>Supervisor Approval:</b></td>
                    <td width="25%">'.$supervisor_status.'</td>
                    <td width="25%"><b>Approved By:</b></td>
                    <td width="25%">'.$supervisor_name.'</td>
                </tr>
                <tr>
                    <td><b>SHE Approval:</b></td>
                    <td>'.$she_status.'</td>
                    <td><b>Approved By:</b></td>
                    <td>'.$she_name.'</td>
                </tr>
                <tr>
                    <td><b>FM Approval:</b></td>
                    <td>'.$fm_status.'</td>
                    <td><b>Approved By:</b></td>
                    <td>'.$fm_name.'</td>
                </tr>
                <tr>
                    <td><b>Current Status:</b></td>
                    <td colspan="3">'.$permit['ptw_status'].'</td>
                </tr>
            </table>';
            
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(10);

            // Remarks
            if (isset($permit['ptw_remarks']) && !empty($permit['ptw_remarks'])) {
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->Cell(0, 8, '5. REMARKS', 0, 1, 'L');
                $pdf->SetFont('helvetica', '', 10);
                $pdf->writeHTML('<div style="border:1px solid #000; padding:5px;">'.$permit['ptw_remarks'].'</div>', true, false, false, false, '');
            }

            // Generate filename
            $filename = 'PTW_' . $permit['ptw_permit_number'] . '_' . date('Y-m-d_H-i-s') . '.pdf';
            
            // Output PDF
            $pdf->Output($filename, 'D');
            
            return array('success' => true, 'filename' => $filename);

        } catch (Exception $e) {
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $e->getMessage()));
        }
    }
}

try {
    // Check if action is provided
    if (!isset($_POST['action'])) {
        throw new Exception('Action parameter is required');
    }

    $action = $_POST['action'];
    $response = array();

    switch ($action) {
        case 'create_pdf':
            if (!isset($_POST['ptw_id'])) {
                throw new Exception('PTW ID is required');
            }
            
            $permit_id = $_POST['ptw_id'];
            
            // For create_pdf, just return success - the actual download happens in download_pdf
            $response = array(
                'success' => true,
                'message' => 'PDF ready for download',
                'permit_id' => $permit_id
            );
            break;
            
        case 'check_pdf_status':
            if (!isset($_POST['ptw_id'])) {
                throw new Exception('PTW ID is required');
            }
            
            $permit_id = $_POST['ptw_id'];
            
            $response = array(
                'success' => true,
                'status' => 'ready',
                'permit_id' => $permit_id
            );
            break;
            
        case 'download_pdf':
            if (!isset($_POST['ptw_id'])) {
                throw new Exception('PTW ID is required');
            }
            
            $permit_id = $_POST['ptw_id'];
            
            // Don't send JSON header for PDF download
            header_remove(); // Remove existing headers
            
            // Create simple test PDF for download
            try {
                $pdf = new MYPDF_ptw(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetCreator(PDF_CREATOR);
                $pdf->SetAuthor('GEMS 2.0');
                $pdf->SetTitle('GEMS 2.0 PTW Test');
                $pdf->setPrintHeader(false);
                $pdf->setPrintFooter(true);
                $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
                $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
                $pdf->AddPage();
                
                $pdf->SetFont('helvetica', 'B', 16);
                $pdf->Cell(0, 10, 'PTW PDF Test - Permit ID: ' . $permit_id, 0, 1, 'C');
                $pdf->Ln(10);
                $pdf->SetFont('helvetica', '', 12);
                $pdf->Cell(0, 10, 'This is a test PDF to verify TCPDF is working.', 0, 1, 'L');
                $pdf->Cell(0, 10, 'Database integration will be added next.', 0, 1, 'L');
                $pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'L');
                
                $filename = 'PTW_Test_' . $permit_id . '_' . date('Y-m-d_H-i-s') . '.pdf';
                $pdf->Output($filename, 'D');
                exit; // Important: Stop execution after PDF output
                
            } catch (Exception $e) {
                // If PDF generation fails, return JSON error
                header('Content-Type: application/json');
                echo json_encode(array(
                    'success' => false,
                    'message' => 'PDF generation failed: ' . $e->getMessage()
                ));
                exit;
            }
            break;
            
        default:
            throw new Exception('Invalid action');
    }

    echo json_encode($response);

} catch (Exception $e) {
    $error_response = array(
        'success' => false,
        'message' => $e->getMessage()
    );
    echo json_encode($error_response);
}

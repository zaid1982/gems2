<?php

class WoMrfPdf extends General {

    function __construct(int $userId = 0, bool $isLogged = false) {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @param int $pdfId
     * @return int
     * @throws Exception
     */
    public function getWoTaskRequestId (int $pdfId): int {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($pdfId, 'pdfId');
            return DbMysql::selectColumn('wo_task_request', array('woTaskRequestMrfPdf'=>$pdfId), 'woTaskRequestId',1);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $woTaskRequestId
     * @return int
     * @throws Exception
     */
    public function createPdf (int $woTaskRequestId): int {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $woTaskRequest = DbMysql::select('wo_task_request', array('woTaskRequestId'=>$woTaskRequestId), 1);
            $woTask = DbMysql::select('wo_task', array('woTaskId'=>$woTaskRequest['woTaskId']), 1);
            $requester = DbMysql::select('sys_user', array('userId'=>$woTaskRequest['woTaskRequestOrderBy']), 1);
            $orderDate = !empty($woTaskRequest['woTaskRequestTimeOrdered']) ? $this->dateDisplay($woTaskRequest['woTaskRequestTimeOrdered']) : '';
            $severityName = !empty($woTask['woTaskSeverity']) ? DbMysql::selectColumn('ref_severity', array('severityId'=>$woTask['woTaskSeverity']), 'severityName', 1) : '';

            $approvalFinal = array('approvalBy'=>'', 'approvalDate'=>'', 'approvalResult'=>'', 'approvalRemark'=>'', 'approvalSign'=>'');
            if ($woTaskRequest['woTaskRequestStatus'] > 33) {
                $approvalTask = DbMysql::select('wfl_task', array('transactionId'=>$woTaskRequest['transactionId'], 'checkpointId'=>42));
                if (!empty($approvalTask)) {
                    $approver = DbMysql::select('sys_user', array('userId'=>$approvalTask['taskClaimedUser']), 1);
                    $approvalFinal['approvalBy'] = $requester['userFirstName'];
                    $approvalTimeTemp = new DateTime($approvalTask['taskTimeSubmit']);
                    $approvalFinal['approvalDate'] = $approvalTimeTemp->format('d/m/Y');
                    $approvalFinal['approvalRemark'] = $approvalTask['taskRemark'];
                    if ($approvalTask['taskStatus'] === 49) {
                        $approvalFinal['approvalResult'] = 'A';
                    } else if ($approvalTask['taskStatus'] === 50) {
                        $approvalFinal['approvalResult'] = 'N';
                    }
                    if (!empty($approver['userSignature'])) {
                        $approvalSign = DbMysql::select('sys_upload', array('uploadId'=>$approver['userSignature']), 1);
                        $approvalFinal['approvalSign'] = $approvalSign['uploadFolder'].'/'.$approvalSign['uploadFilename'].'.'.$approvalSign['uploadExtension'];
                    }
                }
            }

            $issuedFinal = array('issuedBy'=>'', 'issuedDate'=>'', 'issuedSign'=>'');
            $receivedFinal = array('receivedBy'=>'', 'receivedDate'=>'', 'receivedSign'=>'');
            if ($woTaskRequest['woTaskRequestStatus'] === 36) {
                $issuedTask = DbMysql::select('wfl_task', array('transactionId'=>$woTaskRequest['transactionId'], 'checkpointId'=>43, 'taskStatus'=>'IN|36,51'));
                if (!empty($issuedTask)) {
                    $issuer = DbMysql::select('sys_user', array('userId'=>$issuedTask['taskClaimedUser']), 1);
                    $issuedFinal['issuedBy'] = $issuer['userFirstName'];
                    $issuedTimeTemp = new DateTime($issuedTask['taskTimeSubmit']);
                    $issuedFinal['issuedDate'] = $issuedTimeTemp->format('d/m/Y');
                    if (!empty($issuer['userSignature'])) {
                        $issuerSign = DbMysql::select('sys_upload', array('uploadId'=>$issuer['userSignature']), 1);
                        $issuedFinal['issuedSign'] = $issuerSign['uploadFolder'].'/'.$issuerSign['uploadFilename'].'.'.$issuerSign['uploadExtension'];
                    }
                }
                $receivedFinal['receivedBy'] = $requester['userFirstName'];
                $receivedTimeTemp = new DateTime($woTaskRequest['woTaskRequestTimeCollected']);
                $receivedFinal['receivedDate'] = $receivedTimeTemp->format('d/m/Y');
                if (!empty($requester['userSignature'])) {
                    $receivedSign = DbMysql::select('sys_upload', array('uploadId'=>$requester['userSignature']), 1);
                    $receivedFinal['receivedSign'] = $receivedSign['uploadFolder'].'/'.$receivedSign['uploadFilename'].'.'.$receivedSign['uploadExtension'];
                }
            }

            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetTitle('GEMS 2.0 - WO MRF Report');
            $pdf->SetSubject('GEMS 2.0 - WO MRF Report');
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(15);
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            $this->pdfFontSize = 10;
            $this->pdfPageWidth = 180;
            $this->pdfLineSize = 0.1;
            $this->pdfLineBoldSize = 1;

            $pdf->AddPage();
            $pdf->Image('pdf/images/logo_gfm.png', 25, 20, 60, '', 'PNG', '', '', true);
            parent::pdfWriteColumnV2($pdf, array('', 'GLOBAL FACILITIES MANAGEMENT SDN BHD'), array(80, 100), array('C', 'C'), array('LT', 'LRT'), array('', ''), array('', 11), '', 0, 10, 'B');
            parent::pdfWriteColumnV2($pdf, array('', 'MATERIAL REQUISITION FORM'), array(80, 100), array('C', 'C'), array('L', 'LR'), array('', 'B'), array('', 10), '', 0, 7, 'B');
            parent::pdfWriteColumnV2($pdf, array('', 'BPM 12.1.3/F/002/07:1'), array(80, 100), array('C', 'C'), array('LB', 'LRB'), array('', ''), array('', 8), '', 0, 9, 'T');

            parent::pdfWriteColumnV2($pdf, array('*   This form is to be used for requisition of NON-STATIONERY items only. (For stationery items, please refer to administration.'), array($this->pdfPageWidth), array('L'), array(''), array(''), array(8), '', 0, 6.5, 'B');
            parent::pdfWriteColumnV2($pdf, array('*   Requestor’s Manager In-charge must APPROVE this Form BEFORE material is issued by Authorised Storekeeper(s).'), array($this->pdfPageWidth), array('L'), array(''), array(''), array(8));
            parent::pdfWriteColumnV2($pdf, array('*   Requisition must be given in ADVANCE, giving reasonable time for the immediate Officer and Manager In-charge to '), array($this->pdfPageWidth), array('L'), array(''), array(''), array(8));
            parent::pdfWriteColumnV2($pdf, array('    recommend or approve material requisition and raise purchase requisition (if required)'), array($this->pdfPageWidth), array('L'), array(''), array(''), array(8));

            $pdf->Ln();
            parent::pdfWriteColumnV2($pdf, array('   FACILITY MANAGEMENT    SERVICE  ', 'Zone / Block : ', $woTask['woTaskLocation']), array(65, 45, 70), array('C', 'R', 'L'), array(), array('', 'B', ''), array());
            //$tempY = $pdf->GetY();
            //parent::pdfWriteColumnV2($pdf, array('Requested By : ', $requester['userFirstName']), array(35, 55), array('R', 'L'), array(), array('B', ''), array(), '', 0, 9.619444);
            //$pdf->SetY($tempY);
            parent::pdfWriteColumnV2($pdf, array('Requested By : ', 'Request No :', 'Date :', 'Priority :'), array(65, 45, 35, 35), array('C', 'C', 'C', 'C'), array('LR', 'R', 'R', 'R'), array('B', 'B', 'B', 'B'), array(), '', 0, 0, 'B');
            parent::pdfWriteColumnV2($pdf, array($requester['userFirstName'], $woTaskRequest['woTaskRequestNo'], $orderDate, $severityName), array(65, 45, 35, 35), array('C', 'C', 'C', 'C'), array('LRB', 'RB', 'RB', 'RB'), array(), array(), '', 0, 0, 'T');

            $pdf->Ln();
            $tempY = $pdf->GetY();
            parent::pdfWriteColumnV2($pdf, array('NO', 'ITEM DESCRIPTION', 'QTY', '', 'REMARKS'), array(10, 60, 15, 45, 50), array('C', 'C', 'C', 'C', 'C'), array(), array('B', 'B', 'B', 'B', 'B'), array(), '', 0, 10);
            $pdf->SetY($tempY);
            parent::pdfWriteColumnV2($pdf, array('WORK ORDER(S) NO.'), array(35), array('C'), array(''), array('B'), array(), '', 90, 10);
            $woTaskParts = DbMysql::selectSqlAll(/** @lang text */"SELECT
                tp.*, i.item_description AS item_description
                FROM wo_task_parts tp
                LEFT JOIN ast_part p ON p.part_id = tp.part_id
                LEFT JOIN ref_item i ON i.item_id = p.item_id",
                array('tp.woTaskRequestId'=>$woTaskRequestId)
            );
            $no = 1;
            foreach ($woTaskParts as $woTaskPart) {
                parent::pdfWriteColumnV2($pdf, array($no++, $woTaskPart['itemDescription'], $woTaskPart['woTaskPartsQuantity'], $woTask['woTaskNo'], $woTaskPart['woTaskPartsRemark']), array(10, 60, 15, 45, 50), array('C', 'L', 'C', 'C', 'L'));
            }
            for ($i=$no; $i<=10; $i++) {
                parent::pdfWriteColumnV2($pdf, array($i, '', '', '', ''), array(10, 60, 15, 45, 50), array('C', 'L', 'C', 'C', 'L'));
            }
            parent::pdfWriteColumnV2($pdf, array(''), array($this->pdfPageWidth), array('C'), array(''), array(), array(8), 'B', 0, 4);

            if ($pdf->GetY() > 268) {
                $pdf->AddPage();
                $pdf->setPage($pdf->getPage());
            }

            $pdf->Ln();
            parent::pdfWriteColumnV2($pdf, array('APPROVAL (Facility Engineer) :'), array($this->pdfPageWidth), array('L'), array(''), array(''), array(), '', 0, 6.5, 'T');
            parent::pdfWriteColumnV2($pdf, array('', 'Name', 'Initial', 'Date', 'Remark'), array(40, 50, 30, 25, 35), array('C', 'C', 'C', 'C', 'C'), array(), array('B', 'B', 'B', 'B', 'B'), array(), '', 0, 7);
            $tempY = $pdf->GetY();
            parent::pdfWriteColumnV2($pdf, array('', '', '', '', ''), array(40, 50, 30, 25, 35), array('', '', '', '', ''), array('L', 'L', 'L', 'L', 'LR'), array(), array(5, 5, 5, 5, 5));   //  as it gets, need to cater for very long remark. Please be prepared
            if ($approvalFinal['approvalResult'] === 'A') {
                $pdf->Line($pdf->GetX() + 2.5, $pdf->GetY() + 7, $pdf->GetX() + 27, $pdf->GetY() + 7);
            } else if ($approvalFinal['approvalResult'] === 'N') {
                $pdf->Line($pdf->GetX() + 4.5, $pdf->GetY() + 2.5, $pdf->GetX() + 22.5, $pdf->GetY() + 2.5);
            }
            parent::pdfWriteColumnV2($pdf, array('', '* Approved /          Not Approved', $approvalFinal['approvalBy'], '', $approvalFinal['approvalDate'], $approvalFinal['approvalRemark']), array(2, 38, 50, 30, 25, 35), array('', 'L', 'C', 'C', 'C', 'L'), array('L', '', 'L', 'L', 'L', 'LR'), array('', 'B', '', '', '', ''), array(), '', 0, 0, 'T');
            parent::pdfWriteColumnV2($pdf, array('', '', '', '', ''), array(40, 50, 30, 25, 35), array('', '', '', '', ''), array('LB', 'LB', 'LB', 'LB', 'LRB'), array(), array(5, 5, 5, 5, 5));
            if (!empty($approvalFinal['approvalSign'])) {
                $pdf->Image($approvalFinal['approvalSign'], 110, $tempY, 20, 0, 'PNG', '', 'C', false, 300);
            }
            parent::pdfWriteColumnV2($pdf, array('* Strike off whichever not applicable'), array($this->pdfPageWidth), array('L'), array(''), array(''), array(9), '', 0, 5, 'B');
            parent::pdfWriteColumnV2($pdf, array(''), array($this->pdfPageWidth), array('C'), array(''), array(), array(8), 'B', 0, 4);

            if ($pdf->GetY() > 271) {
                $pdf->AddPage();
                $pdf->setPage($pdf->getPage());
            }

            $pdf->Ln();
            parent::pdfWriteColumnV2($pdf, array('ISSUED BY :'), array($this->pdfPageWidth), array('L'), array(''), array(''), array(), '', 0, 6.5, 'T');
            parent::pdfWriteColumnV2($pdf, array('Name', 'Date', 'Initial'), array(110, 30, 40), array('C', 'C', 'C'), array(), array('B', 'B', 'B'), array(), '', 0, 7);
            $tempY = $pdf->GetY();
            parent::pdfWriteColumnV2($pdf, array('', $issuedFinal['issuedBy'], $issuedFinal['issuedDate'], ''), array(3, 107, 30, 40), array('', 'L', 'C', 'C'), array('LB', 'B', 'LB', 'LRB'), array(), array(), '', 0, 12);
            if (!empty($issuedFinal['issuedSign'])) {
                  $pdf->Image($issuedFinal['issuedSign'], 165, $tempY - 1, 20, 0, 'PNG', '', 'C', false, 300);
            }

            if ($pdf->GetY() > 271) {
                $pdf->AddPage();
                $pdf->setPage($pdf->getPage());
            }
            $pdf->Ln(4);
            parent::pdfWriteColumnV2($pdf, array('ACKNOWLEDGEMENT OF RECEIVE :'), array($this->pdfPageWidth), array('L'), array(''), array(''), array(), '', 0, 6.5, 'T');
            parent::pdfWriteColumnV2($pdf, array('Name', 'Date', 'Initial'), array(110, 30, 40), array('C', 'C', 'C'), array(), array('B', 'B', 'B'), array(), '', 0, 7);
            $tempY = $pdf->GetY();
            parent::pdfWriteColumnV2($pdf, array('', $receivedFinal['receivedBy'], $receivedFinal['receivedDate'], ''), array(3, 107, 30, 40), array('', 'L', 'C', 'C'), array('LB', 'B', 'LB', 'LRB'), array(), array(), '', 0, 12);
            if (!empty($receivedFinal['receivedSign'])) {
                $pdf->Image($receivedFinal['receivedSign'], 165, $tempY - 1, 20, 0, 'PNG', '', 'C', false, 300);
            }

            if ($pdf->GetY() > 258) {
                $pdf->AddPage();
                $pdf->setPage($pdf->getPage());
            } else if ($pdf->PageNo() === 1) {
                $pdf->SetY(265);
            } else {
                $pdf->Ln(6);
            }
            parent::pdfWriteColumnV2($pdf, array('Disclaimer: Any document that is viewed or reproduced outside GFM GEMS system will be considered as Uncontrolled Copy.'), array(180), array('C'), array(), array('I'), array(9), '', 0, 8);

            $woTimeCreated = new DateTime($woTaskRequest['woTaskRequestTimeCreated']);
            $folderName = $woTimeCreated->format('Ym');
            $folder = 'pdf/mrf/'.$folderName;
            if (!parent::folderExist($folder)) {
                mkdir ($folder,0777, true);
            }
            $filename = !empty($woTaskRequest['woTaskRequestNo']) ? 'mrf_'.$woTaskRequest['woTaskRequestNo'].'.pdf' : 'mrf_draft_'.$woTaskRequest['woTaskRequestId'].'.pdf';
            $filenameSrc = trim(dirname(__FILE__), 'class').'pdf\mrf\\'.$folderName.'\\'.$filename;
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'dirname = '.$filenameSrc);
            $pdf->Output($filenameSrc, 'F');

            if (!empty($woTaskRequest['woTaskRequestMrfPdf'])) {
                $pdfIdMrf = $woTaskRequest['woTaskRequestMrfPdf'];
                $sysPdf = DbMysql::select('sys_pdf', array('pdfId'=>$pdfIdMrf), true);
                $file = $sysPdf['pdfFolder'].'/'.$sysPdf['pdfFilename'];
                if ($file !== ($folder.'/'.$filename)) {
                    if (file_exists($file)) {
                        unlink($file);
                    }
                    DbMysql::update('sys_pdf', array('pdfFilename'=>$filename, 'pdfFolder'=>$folder), array('pdfId'=>$pdfIdMrf));
                }
                DbMysql::update('wo_task_request', array('woTaskRequestMrfGenerate'=>0), array('woTaskRequestId'=>$woTaskRequestId));
            } else {
                $pdfIdMrf = DbMysql::insert('sys_pdf', array('pdfType'=>'mrf', 'pdfFolder'=>$folder, 'pdfFilename'=>$filename));
                DbMysql::update('wo_task_request', array('woTaskRequestMrfPdf'=>$pdfIdMrf, 'woTaskRequestMrfGenerate'=>0), array('woTaskRequestId'=>$woTaskRequestId));
            }
            return $pdfIdMrf;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

}
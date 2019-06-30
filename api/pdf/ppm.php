<?php

class MYPDF_ppm extends TCPDF {
    private $fn_general;
    private $ppmTaskId;
    private $ppmDocumentNo;
    private $ppmIssueNo;

    /**
     * @param $property
     * @param $value
     * @throws Exception
     */
    public function __set($property, $value ) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        } else {
            throw new Exception($this->get_exception('0002', __FUNCTION__, __LINE__, 'Get Property not exist ['.$property.']'));
        }
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

    public function PpmTable() {
        try {
            $frequencies = $this->fn_general->getPpmFrequencyCode();


            $this->SetFont('helvetica', '', 11);
            $this->MultiCell(60, 20, '', 0, 'L', 0, 0, '', '');
            $this->MultiCell(120, 20, "\nPREVENTIVE MAINTENANCE CHECKLIST\n[SITE NAME]", 1, 'C', 0, 0, '', '');
            $this->Ln();

            $this->SetFont('helvetica', '', 8);
            $this->SetFillColor(30, 0, 0, 0);
            $this->SetTextColor(0);
            $this->SetDrawColor(128, 0, 0);
            $this->SetLineWidth(0.2);
            $this->Cell(180, 6, '', 0, 0, 'L', 0);
            $this->Ln();

            $this->SetFont('helvetica', '', 11);
            $this->Cell(8, 6, 'A', 1, 0, 'C', 1);
            $this->Cell(172, 6, ' Asset Details', 1, 0, 'L', 1);
            $this->Ln();
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    // Page footer
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 9);
        $this->Line(PDF_MARGIN_LEFT, $this->y, $this->w - PDF_MARGIN_RIGHT, $this->y);
        $pageNo = 'Page '.strval($this->getAliasNumPage()).' of '.$this->getAliasNbPages();
        $this->Cell(65, 6, 'Document No : '.$this->ppmDocumentNo, 0, 0, 'L', 0);
        $this->Cell(70, 6, 'Issue No : '.$this->ppmIssueNo, 0, 0, 'L', 0);
        $this->Cell(55, 6, $pageNo, 0, 0, 'R', 0);
    }
}




class Class_pdf_ppm {
    private $fn_general;
    private $ppmTaskId;

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

    /**
     * @param $property
     * @return mixed
     * @throws Exception
     */
    public function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        } else {
            throw new Exception($this->get_exception('0001', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    /**
     * @param $property
     * @param $value
     * @throws Exception
     */
    public function __set($property, $value) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        } else {
            throw new Exception($this->get_exception('0002', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    /**
     * @param $property
     * @return bool
     * @throws Exception
     */
    public function __isset($property) {
        if (property_exists($this, $property)) {
            return isset($this->$property);
        } else {
            throw new Exception($this->get_exception('0003', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    /**
     * @param $property
     * @throws Exception
     */
    public function __unset($property) {
        if (property_exists($this, $property)) {
            unset($this->$property);
        } else {
            throw new Exception($this->get_exception('0004', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    public function create_pdf () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($this->ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId Empty');
            }

            // create new PDF document
            $pdf = new MYPDF_ppm(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // set document information
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Muhammad Zaid');
            $pdf->SetTitle('GEMS 2.0 PPM');
            $pdf->SetSubject('GEMS 2.0 PPM');

            // set default header data
            //$pdf->SetHeaderData('', PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 011', PDF_HEADER_STRING);

            // remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(true);

            // set header and footer fonts
            $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

            // set default monospaced font
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

            // set margins
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

            // set auto page breaks
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

            // set image scale factor
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

            // add a page
            $pdf->AddPage();

            $pdf->Image('pdf/images/logo.png', 15, 15, 50, 20, 'PNG', 'http://www.tcpdf.org', '', true, 150, '', false, false, 0, false, false, false);

            $pdf->__set('fn_general', $this->fn_general);
            $pdf->__set('ppmTaskId', $this->ppmTaskId);
            $pdf->PpmTable();

            $pdf->Output('example_011.pdf', 'I');
        } catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
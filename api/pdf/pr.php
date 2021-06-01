<?php

class MYPDF_pr extends TCPDF {
    //Page header
    public function Header() {
        $this->Image('pdf/images/logo_1.png', 25, 20, 50, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        $this->SetFont('helvetica', 'B', 13);
        $this->Ln(5);
        $this->MultiCell(90, 5, '',0,'',0,0);
        $this->MultiCell(70, 5, 'PURCHASE REQUISITION FORM', 0, 'C', 0, 0, '', '', true);
        $this->Ln();
        $this->SetFont('helvetica', '', 9);
        $this->MultiCell(90, 5, '',0,'',0,0);
        $this->MultiCell(70, 5, 'BPM 12.1/F/001/07:1', 0, 'C', 0, 0, '', '', true);
        //$this->Cell(0, 20, 'PURCHASE REQUISITION', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln(8);
        $this->Line(25, $this->getY(), $this->w - 25, $this->getY(), array('width' => 0.75));
    }
    // Page footer
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 9);
        $this->Line(20, $this->y, $this->w - 20, $this->y);
        $pageNo = 'Page '.strval($this->getAliasNumPage()).' of '.$this->getAliasNbPages();
        $this->Cell(180, 6, $pageNo, 0, 0, 'R', 0);
    }
}

class Class_pdf_pr
{
    private $fn_general;

    function __construct()
    {
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

    private function checkYRow($pdf, $startY, $maxNoCells) {
        $tempY = $startY+($maxNoCells*4)+2;
        if ($tempY > 273) {
            $previousY = $tempY - 277;
            $pdf->AddPage();
            $pdf->setPage($pdf->getPage());
            if ($previousY > 0) {
                $pdf->SetY($pdf->GetY() + $previousY);
            }
        }
    }

    private function writeSixColumn($pdf, $value1, $value2, $value3, $value4, $value5) {
        $maxNoCells = 0;
        $startX = $pdf->GetX();
        $startY = $pdf->GetY();
        $cellCount = $pdf->MultiCell(8, 4, $value1, 0, 'C', 0, 0);
        if ($cellCount > $maxNoCells ) {$maxNoCells = $cellCount;}
        $cellCount = $pdf->MultiCell(72, 4, $value2, 0, 'L', 0, 0);
        if ($cellCount > $maxNoCells ) {$maxNoCells = $cellCount;}
        $cellCount = $pdf->MultiCell(20, 4, $value3, 0, 'R', 0, 0);
        if ($cellCount > $maxNoCells ) {$maxNoCells = $cellCount;}
        $cellCount = $pdf->MultiCell(20, 4, $value4, 0, 'R', 0, 0);
        if ($cellCount > $maxNoCells ) {$maxNoCells = $cellCount;}
        $cellCount = $pdf->MultiCell(20, 4, $value5, 0, 'R', 0, 0);
        if ($cellCount > $maxNoCells ) {$maxNoCells = $cellCount;}
        $cellCount = $pdf->MultiCell(20, 4, '', 0, 'R', 0, 0);
        if ($cellCount > $maxNoCells ) {$maxNoCells = $cellCount;}
        $pdf->SetXY($startX,$startY);
        $pdf->MultiCell(8, ($maxNoCells*3)+2, '', 'LB', 'C', 0, 0);
        $pdf->MultiCell(72, ($maxNoCells*3)+2, '', 'LB', 'L', 0, 0);
        $pdf->MultiCell(20, ($maxNoCells*3)+2, '', 'LB', 'R', 0, 0);
        $pdf->MultiCell(20, ($maxNoCells*3)+2, '', 'LB', 'R', 0, 0);
        $pdf->MultiCell(20, ($maxNoCells*3)+2, '', 'LB', 'R', 0, 0);
        $pdf->MultiCell(20, ($maxNoCells*3)+2, '', 'LRB', 'R', 0, 0);
        $pdf->Ln();
        $this->checkYRow($pdf, $startY, $maxNoCells);
    }

    private function writeSixHeader($pdf, $value1, $value2, $value3, $value4, $value5, $value6) {
        $maxNoCells = 0;
        $startX = $pdf->GetX();
        $startY = $pdf->GetY();
        $cellCount = $pdf->MultiCell(8, 4, $value1, 0, 'C', 0, 0);
        if ($cellCount > $maxNoCells ) {$maxNoCells = $cellCount;}
        $cellCount = $pdf->MultiCell(72, 4, $value2, 0, 'C', 0, 0);
        if ($cellCount > $maxNoCells ) {$maxNoCells = $cellCount;}
        $cellCount = $pdf->MultiCell(20, 4, $value3, 0, 'C', 0, 0);
        if ($cellCount > $maxNoCells ) {$maxNoCells = $cellCount;}
        $cellCount = $pdf->MultiCell(20, 4, $value4, 0, 'C', 0, 0);
        if ($cellCount > $maxNoCells ) {$maxNoCells = $cellCount;}
        $cellCount = $pdf->MultiCell(20, 4, $value5, 0, 'C', 0, 0);
        if ($cellCount > $maxNoCells ) {$maxNoCells = $cellCount;}
        $cellCount = $pdf->MultiCell(20, 4, $value6, 0, 'C', 0, 0);
        if ($cellCount > $maxNoCells ) {$maxNoCells = $cellCount;}
        $pdf->SetXY($startX,$startY);
        $pdf->SetFillColor(180);
        $pdf->MultiCell(8, ($maxNoCells*3)+2, '', 'LBT', 'C', 1, 0);
        $pdf->MultiCell(72, ($maxNoCells*3)+2, '', 'LBT', 'C', 1, 0);
        $pdf->MultiCell(20, ($maxNoCells*3)+2, '', 'LBT', 'C', 1, 0);
        $pdf->MultiCell(20, ($maxNoCells*3)+2, '', 'LBT', 'C', 1, 0);
        $pdf->MultiCell(20, ($maxNoCells*3)+2, '', 'LBT', 'C', 1, 0);
        $pdf->MultiCell(20, ($maxNoCells*3)+2, '', 'LRTB', 'C', 1, 0);
        $pdf->Ln();
        $this->checkYRow($pdf, $startY, $maxNoCells);
    }

    /**
     * @param $prId
     * @return string
     * @throws Exception
     */
    public function create_pdf ($prId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __CLASS__);
            $this->fn_general->checkEmptyParams(array($prId));

            // create new PDF document
            $pdf = new MYPDF_pr(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // set document information
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Muhammad Zaid');
            $pdf->SetTitle('GEMS 2.0 PR');
            $pdf->SetSubject('GEMS 2.0 PR');

            // set header and footer fonts
            $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

            // set default monospaced font
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

            // set margins
            $pdf->SetMargins(25, 47, 25);
            $pdf->SetHeaderMargin(20);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

            // set auto page breaks
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

            // set image scale factor
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

            // add a page
            $pdf->AddPage();

            $pdf->SetFont('helvetica', 'B', 7);
            $pdf->setCellPaddings(3, 1, 3, 0);
            $pdf->MultiCell(20, 4, 'Issued by:', 'LT', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(25, 4, 'Department:', 'T', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(40, 4, 'Location:', 'LT', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(75, 4, 'PR Ref. No.:', 'LRT', 'L', 0, 0, '', '', true);
            $pdf->Ln();
            $pdf->SetFont('helvetica', '', 7);
            $pdf->MultiCell(20, 4, '', 'L', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(25, 4, 'Operation', '', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(40, 4, 'HQ', 'L', 'L', 0, 0, '', '', true);
            $pdf->setCellPaddings(0, 1, 0, 0);
            $pdf->MultiCell(3, 4, '', 'L', 'L', 0, 0, '', '', true);
            $pdf->setCellPaddings(1, 1, 0, 0);
            $pdf->MultiCell(69, 4, 'PR10002-11212', 'B', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(3, 4, '', 'R', 'L', 0, 0, '', '', true);
            $pdf->Ln();
            $pdf->setCellPaddings(3, 1, 3, 0);
            $pdf->MultiCell(20, 4, '', 'L', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(25, 4, 'Admin', '', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(40, 4, 'Site', 'L', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(75, 4, '', 'LR', 'L', 0, 0, '', '', true);
            $pdf->Ln();
            $pdf->MultiCell(20, 4, '', 'L', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(25, 4, '', '', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(40, 4, '', 'L', 'L', 0, 0, '', '', true);
            $pdf->SetFont('helvetica', 'B', 7);
            $pdf->MultiCell(37, 4, 'Expected Delivery Date:', 'L', 'L', 0, 0, '', '', true);
            $pdf->SetFont('helvetica', '', 7);
            $pdf->setCellPaddings(1, 1, 0, 0);
            $pdf->MultiCell(35, 4, '17 September, 2021', 'B', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(3, 4, '', 'R', 'L', 0, 0, '', '', true);
            $pdf->Ln();
            $pdf->MultiCell(20, 1, '', 'L', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(25, 1, '', '', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(40, 1, '', 'L', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(75, 1, '', 'LR', 'L', 0, 0, '', '', true);
            $pdf->Ln();
            $pdf->Image('pdf/images/tick.png', 62, 52, 5, '', 'PNG', '', '', true, 150, '', false, false, 0, false, false, false);
            $pdf->Image('pdf/images/square.png', 62, 56, 5, '', 'PNG', '', '', true, 150, '', false, false, 0, false, false, false);
            $pdf->Image('pdf/images/tick.png', 102, 52, 5, '', 'PNG', '', '', true, 150, '', false, false, 0, false, false, false);
            $pdf->Image('pdf/images/square.png', 102, 56, 5, '', 'PNG', '', '', true, 150, '', false, false, 0, false, false, false);

            $pdf->SetFont('helvetica', 'B', 7);
            $pdf->setCellPaddings(3, 1, 3, 0);
            $pdf->MultiCell(45, 4, 'Purchase Requisition Category:', 'LT', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(40, 4, '', 'T', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(75, 4, '', 'RT', 'L', 0, 0, '', '', true);
            $pdf->Ln();
            $pdf->SetFont('helvetica', '', 7);
            $pdf->MultiCell(20, 4, '', 'L', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(25, 4, 'Material', '', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(40, 4, 'Office Supply', '', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(37, 4, 'Others (Please Specify)', '', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(35, 4, '', 'B', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(3, 4, '', 'R', 'L', 0, 0, '', '', true);
            $pdf->Ln();
            $pdf->MultiCell(20, 4, '', 'L', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(25, 4, 'Tooling', '', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(40, 4, 'Stationaries', '', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(37, 4, '', '', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(35, 4, '', 'B', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(3, 4, '', 'R', 'L', 0, 0, '', '', true);
            $pdf->Ln();
            $pdf->MultiCell(20, 4, '', 'L', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(25, 4, 'Services', '', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(40, 4, 'Variation Order', '', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(37, 4, '', '', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(35, 4, '', 'B', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(3, 4, '', 'R', 'L', 0, 0, '', '', true);
            $pdf->Ln();
            $pdf->MultiCell(20, 1, '', 'LB', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(25, 1, '', 'B', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(40, 1, '', 'B', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(75, 1, '', 'RB', 'L', 0, 0, '', '', true);
            $pdf->Ln();
            $pdf->Image('pdf/images/tick.png', 62, 72, 5, '', 'PNG', '', '', true, 150, '', false, false, 0, false, false, false);
            $pdf->Image('pdf/images/square.png', 62, 76, 5, '', 'PNG', '', '', true, 150, '', false, false, 0, false, false, false);
            $pdf->Image('pdf/images/square.png', 62, 80, 5, '', 'PNG', '', '', true, 150, '', false, false, 0, false, false, false);
            $pdf->Image('pdf/images/square.png', 102, 72, 5, '', 'PNG', '', '', true, 150, '', false, false, 0, false, false, false);
            $pdf->Image('pdf/images/square.png', 102, 76, 5, '', 'PNG', '', '', true, 150, '', false, false, 0, false, false, false);
            $pdf->Image('pdf/images/square.png', 102, 80, 5, '', 'PNG', '', '', true, 150, '', false, false, 0, false, false, false);

            $pdf->Ln(1);
            $pdf->setCellPaddings(3, 1, 3, 1);
            $pdf->SetFont('helvetica', 'B', 7);
            $pdf->MultiCell(80, 4, 'Preferred Supplier:', 'LT', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(80, 4, 'Payment Term:', 'LRT', 'L', 0, 0, '', '', true);
            $pdf->Ln();
            $pdf->SetFont('helvetica', '', 7);
            $pdf->setCellPaddings(3, 1, 0, 0);
            $pdf->MultiCell(14, 4, 'Name', 'LT', 'L', 0, 0, '', '', true);
            $pdf->setCellPaddings(0, 1, 0, 0);
            $pdf->MultiCell(2, 4, ':', 'T', 'L', 0, 0, '', '', true);
            $pdf->setCellPaddings(1, 1, 0, 0);
            $pdf->MultiCell(61, 4, 'Muhammad Zaid bin Shaharil', 'TB', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(3, 4, '', 'T', 'L', 0, 0, '', '', true);
            $pdf->setCellPaddings(3, 1, 0, 0);
            $pdf->MultiCell(14, 4, 'COD', 'LT', 'L', 0, 0, '', '', true);
            $pdf->setCellPaddings(0, 1, 0, 0);
            $pdf->MultiCell(2, 4, ':', 'T', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(61, 4, '           30 Days                60 Days                90 Days', 'T', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(3, 4, '', 'RT', 'L', 0, 0, '', '', true);
            $pdf->Ln();
            $pdf->setCellPaddings(3, 1, 0, 0);
            $pdf->MultiCell(14, 4, 'Address', 'L', 'L', 0, 0, '', '', true);
            $pdf->setCellPaddings(0, 1, 0, 0);
            $pdf->MultiCell(2, 4, ':', '', 'L', 0, 0, '', '', true);
            $pdf->setCellPaddings(1, 1, 0, 0);
            $pdf->MultiCell(61, 4, 'No 24, Jalan Ainsdale 3/6,', 'B', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(3, 4, '', '', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(14, 4, '', 'L', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(2, 4, ':', '', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(27, 4, '           Others (Specify)', '', 'L', 0, 0, '', '', true);
            $pdf->setCellPaddings(1, 1, 0, 0);
            $pdf->MultiCell(34, 4, '', 'B', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(3, 4, '', 'R', 'L', 0, 0, '', '', true);
            $pdf->Ln();
            $pdf->MultiCell(16, 4, '', 'L', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(61, 4, 'Bandar Ainsdale,', 'B', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(3, 4, '', '', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(80, 4, '', 'LR', 'L', 0, 0, '', '', true);
            $pdf->Ln();
            $pdf->MultiCell(16, 4, '', 'L', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(61, 4, '70200 Seremban,', 'B', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(3, 4, '', '', 'L', 0, 0, '', '', true);
            $pdf->setCellPaddings(3, 1, 0, 0);
            $pdf->MultiCell(80, 4, 'Item Available in the Supplier\'s Price List:                 Yes             No', 'LR', 'L', 0, 0, '', '', true);
            $pdf->Ln();
            $pdf->MultiCell(16, 4, '', 'L', 'L', 0, 0, '', '', true);
            $pdf->setCellPaddings(1, 1, 0, 0);
            $pdf->MultiCell(61, 4, 'Negeri Sembilan', 'B', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(3, 4, '', '', 'L', 0, 0, '', '', true);
            $pdf->setCellPaddings(3, 1, 0, 0);
            $pdf->MultiCell(14, 4, 'Remarks', 'L', 'L', 0, 0, '', '', true);
            $pdf->setCellPaddings(0, 1, 0, 0);
            $pdf->MultiCell(2, 4, ':', '', 'L', 0, 0, '', '', true);
            $pdf->setCellPaddings(1, 1, 0, 0);
            $pdf->MultiCell(61, 4, 'This item is urgent, need it asap bla bla bla', 'B', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(3, 4, '', 'R', 'L', 0, 0, '', '', true);
            $pdf->Ln();
            $pdf->setCellPaddings(3, 1, 0, 0);
            $pdf->MultiCell(14, 4, 'Attn.', 'L', 'L', 0, 0, '', '', true);
            $pdf->setCellPaddings(0, 1, 0, 0);
            $pdf->MultiCell(2, 4, ':', '', 'L', 0, 0, '', '', true);
            $pdf->setCellPaddings(1, 1, 0, 0);
            $pdf->MultiCell(61, 4, 'Pn Syima', 'B', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(3, 4, '', '', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(16, 4, '', 'L', 'L', 0, 0, '', '', true);
            $pdf->setCellPaddings(1, 1, 0, 0);
            $pdf->MultiCell(61, 4, 'bla bla bla', 'B', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(3, 4, '', 'R', 'L', 0, 0, '', '', true);
            $pdf->Ln();
            $pdf->setCellPaddings(3, 1, 0, 0);
            $pdf->MultiCell(14, 4, 'Tel.', 'L', 'L', 0, 0, '', '', true);
            $pdf->setCellPaddings(0, 1, 0, 0);
            $pdf->MultiCell(2, 4, ':', '', 'L', 0, 0, '', '', true);
            $pdf->setCellPaddings(1, 1, 0, 0);
            $pdf->MultiCell(23, 4, '03-89258444', 'B', 'L', 0, 0, '', '', true);
            $pdf->setCellPaddings(3, 1, 0, 0);
            $pdf->MultiCell(14, 4, 'Fax', '', 'L', 0, 0, '', '', true);
            $pdf->setCellPaddings(0, 1, 0, 0);
            $pdf->MultiCell(2, 4, ':', '', 'L', 0, 0, '', '', true);
            $pdf->setCellPaddings(1, 1, 0, 0);
            $pdf->MultiCell(22, 4, '03-89258444', 'B', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(3, 4, '', '', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(16, 4, '', 'L', 'L', 0, 0, '', '', true);
            $pdf->setCellPaddings(1, 1, 0, 0);
            $pdf->MultiCell(61, 4, 'bla bla bla', 'B', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(3, 4, '', 'R', 'L', 0, 0, '', '', true);
            $pdf->Ln();


            $pdf->MultiCell(80, 1, '', 'L', 'L', 0, 0, '', '', true);
            $pdf->MultiCell(80, 1, '', 'LR', 'L', 0, 0, '', '', true);
            $pdf->Ln();
            $pdf->Image('pdf/images/square.png', 122, 95, 5, '', 'PNG', '', '', true, 150, '', false, false, 0, false, false, false);
            $pdf->Image('pdf/images/square.png', 142, 95, 5, '', 'PNG', '', '', true, 150, '', false, false, 0, false, false, false);
            $pdf->Image('pdf/images/square.png', 162, 95, 5, '', 'PNG', '', '', true, 150, '', false, false, 0, false, false, false);
            $pdf->Image('pdf/images/square.png', 122, 99, 5, '', 'PNG', '', '', true, 150, '', false, false, 0, false, false, false);
            $pdf->Image('pdf/images/square.png', 158, 107, 5, '', 'PNG', '', '', true, 150, '', false, false, 0, false, false, false);
            $pdf->Image('pdf/images/square.png', 172, 107, 5, '', 'PNG', '', '', true, 150, '', false, false, 0, false, false, false);


            $pdf->setCellPaddings(1, 1, 1, 1);
            $pdf->SetFont('helvetica', 'B', 7);
            $this->writeSixHeader($pdf, 'No', 'Item Description', 'Quantity', 'Unit Price', 'Total Price', 'Budgeted Cost/Units');
            $pdf->SetFont('helvetica', '', 7);
            $this->writeSixColumn($pdf, '1', 'PYRO Hinge Mortise Anti Panic with L Shape Lever Handle and Key Thumbturn Cylinder', '4', '15.00', '60.00');
            $this->writeSixColumn($pdf, '2', 'PYRO Hinge Mortise Anti Panic with L', '4', '15.00', '60.00');
            $this->writeSixColumn($pdf, '3', 'PYRO Hinge Mortise Anti Panic with L', '4', '15.00', '60.00');
            $this->writeSixColumn($pdf, '', '', '', '', '');
            $this->writeSixColumn($pdf, '', '', '', '', '');
            $this->writeSixColumn($pdf, '', '', '', '', '');
            $this->writeSixColumn($pdf, '', '', '', '', '');
            $this->writeSixColumn($pdf, '', '', '', '', '');
            $this->writeSixColumn($pdf, '', '', '', '', '');
            $this->writeSixColumn($pdf, '', '', '', '', '');
            $this->writeSixColumn($pdf, '', '', '', '', '');
            $this->writeSixColumn($pdf, '', '', '', '', '');
            $this->writeSixColumn($pdf, '', '', '', '', '');




            // close and output PDF document
            $folder_code = floor(intval($prId)/1000);
            $folder = 'pdf/pr/'.$folder_code;

            $result = $this->fn_general->folderExist($folder);
            if (!$result) {
                mkdir ($folder,0777, true);
            }
            $filename = 'pr_'.substr((10000000+intval($prId)),1).'.pdf';
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Filename pdf : '.$filename);

            $config = parse_ini_file('library/config.ini');
            $environment = $config['environment'];
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'File : '.__FILE__);
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Environment : '.$environment);
            if ($environment == 'windows') {
                $filename_src = '\pr\\' . $folder_code . '\\' . $filename;
            } else {
                $filename_src = '/pr/' . $folder_code . '/' . $filename;
            }
            $pdf->Output(dirname(__FILE__). $filename_src, 'F');

            /*$pdfId = $woTask['pdf_id_wr'];
            if (empty($pdfId)) {
                $pdfId = Class_db::getInstance()->db_select_col('sys_pdf', array('pdf_filename'=>$filename, 'pdf_status'=>'1'), 'pdf_id');
            }
            if (empty($pdfId)) {
                $pdfId = Class_db::getInstance()->db_insert('sys_pdf', array('pdf_filename'=>$filename, 'pdf_type'=>'wr', 'pdf_folder'=>$folder));
            } else {
                Class_db::getInstance()->db_update('sys_pdf', array('pdf_filename'=>$filename, 'pdf_type'=>'wr', 'pdf_folder'=>$folder, 'pdf_timeCreated'=>'Now()'), array('pdf_id'=>$pdfId));
            }
            Class_db::getInstance()->db_update('wo_task', array('pdf_id_wr'=>$pdfId), array('wo_task_id'=>$this->woTaskId));*/

            return '1';
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
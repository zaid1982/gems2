<?php
//============================================================+
// File name   : example_011.php
// Begin       : 2008-03-04
// Last Update : 2013-05-14
//
// Description : Example 011 for TCPDF class
//               Colored Table (very simple table)
//
// Author: Nicola Asuni
//
// (c) Copyright:
//               Nicola Asuni
//               Tecnick.com LTD
//               www.tecnick.com
//               info@tecnick.com
//============================================================+

/**
 * Creates an example PDF TEST document using TCPDF
 * @package com.tecnick.tcpdf
 * @abstract TCPDF - Example: Colored Table
 * @author Nicola Asuni
 * @since 2008-03-04
 */

// Include the main TCPDF library (search for installation path).
require_once('tcpdf_include.php');

// extend TCPF with custom functions
class MYPDF extends TCPDF {

	// Load table data from file
	public function LoadData($file) {
		// Read file lines
		$lines = file($file);
		$data = array();
		foreach($lines as $line) {
			$data[] = explode(';', chop($line));
		}
		return $data;
	}

	// Colored table
	public function PpmTable() {
		// Colors, line width and bold font
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

        $this->SetFont('helvetica', '', 9);
        $this->Cell(30, 5, 'Asset Group : ', 1, 0, 'R');
        $this->Cell(60, 5, ' Power Supply', 1, 0, 'L');
        $this->Cell(30, 5, 'Model : ', 1, 0, 'R');
        $this->Cell(60, 5, '', 1, 0, 'L');
        $this->Ln();
        $this->Cell(30, 5, 'Asset Category : ', 1, 0, 'R');
        $this->Cell(60, 5, ' LV System', 1, 0, 'L');
        $this->Cell(30, 5, 'Capacity : ', 1, 0, 'R');
        $this->Cell(60, 5, '', 1, 0, 'L');
        $this->Ln();
        $this->Cell(30, 5, 'Asset Type : ', 1, 0, 'R');
        $this->Cell(60, 5, ' Feeder Pillar', 1, 0, 'L');
        $this->Cell(30, 5, 'Location Code : ', 1, 0, 'R');
        $this->Cell(60, 5, '', 1, 0, 'L');
        $this->Ln();
        $this->Cell(30, 5, 'Task No : ', 1, 0, 'R');
        $this->Cell(60, 5, '', 1, 0, 'L');
        $this->Cell(30, 5, 'PM Start Date : ', 1, 0, 'R');
        $this->Cell(60, 5, '', 1, 0, 'L');
        $this->Ln();

        $this->SetFont('helvetica', '', 11);
        $this->Cell(8, 6, 'B', 1, 0, 'C', 1);
        $this->Cell(172, 6, ' Safety Precaution / General Guidelines prior to maintenance activity', 1, 0, 'L', 1);
        $this->Ln();

        $this->SetFont('helvetica', '', 9);
        $textHTML = "The following guidelines shall be followed:
1.	Switch off the Board if maintenance activity requires adjustments, lubrications, repairs or etc.
2.	 Two technicians must be present to achieve safety operations and carry out PPM.
3.	 Display safety signages at specific Board during carry out PPM.
OR,
Refer to …………………….";
        $maxnocells = 0;
        $cellcount = 0;
        //write text first
        $startX = $this->GetX();
        $startY = $this->GetY();
        //draw cells and record maximum cellcount
        //cell height is 6 and width is 80
        $cellcount = $this->MultiCell(8,4,'',0,'L',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(172,4, $textHTML,0,'L',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $this->SetXY($startX,$startY);
        $this->MultiCell(8, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(172, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->Ln();

        $this->SetFont('helvetica', '', 11);
        $this->Cell(8, 6, 'C', 1, 0, 'C', 1);
        $this->Cell(172, 6, ' Qualitative Tasks', 1, 0, 'L', 1);
        $this->Ln();

        $this->SetFont('helvetica', '', 9);
        $maxnocells = 0;
        $cellcount = 0;
        $startX = $this->GetX();
        $startY = $this->GetY();
        $cellcount = $this->MultiCell(8,4,'',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(112,4, "Description",0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, 'Freq',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, 'Pass',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, 'Fail',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, 'N/A',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(20,4, 'Action',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $this->SetXY($startX,$startY);
        $this->MultiCell(8, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(112, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(20, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->Ln();

        $maxnocells = 0;
        $cellcount = 0;
        $startX = $this->GetX();
        $startY = $this->GetY();
        $cellcount = $this->MultiCell(8,4,'1',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(112,4, "Ensure light fitting functioning",0,'L',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, 'M',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, 'X',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(20,4, 'Very Good',0,'L',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $this->SetXY($startX,$startY);
        $this->MultiCell(8, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(112, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(20, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->Ln();

        $maxnocells = 0;
        $cellcount = 0;
        $startX = $this->GetX();
        $startY = $this->GetY();
        $cellcount = $this->MultiCell(8,4,'2',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(112,4, "Check by visual inspection condition of poles and light fitting,lamp cover,control switch",0,'L',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, 'M',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, 'X',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(20,4, '',0,'L',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $this->SetXY($startX,$startY);
        $this->MultiCell(8, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(112, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(20, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->Ln();

        $maxnocells = 0;
        $cellcount = 0;
        $startX = $this->GetX();
        $startY = $this->GetY();
        $cellcount = $this->MultiCell(8,4,'3',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(112,4, "Check time switch, isolator",0,'L',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, 'M',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, 'X',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(20,4, '',0,'L',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $this->SetXY($startX,$startY);
        $this->MultiCell(8, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(112, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(20, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->Ln();

        $maxnocells = 0;
        $cellcount = 0;
        $startX = $this->GetX();
        $startY = $this->GetY();
        $cellcount = $this->MultiCell(8,4,'4',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(112,4, "Check condition of cable",0,'L',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, 'M',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, 'X',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(20,4, 'Good',0,'L',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $this->SetXY($startX,$startY);
        $this->MultiCell(8, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(112, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(20, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->Ln();

        $maxnocells = 0;
        $cellcount = 0;
        $startX = $this->GetX();
        $startY = $this->GetY();
        $cellcount = $this->MultiCell(8,4,'5',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(112,4, "Check for paint works/rust and corrosion, repair as necessary",0,'L',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, 'M',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, 'X',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(20,4, '',0,'L',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $this->SetXY($startX,$startY);
        $this->MultiCell(8, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(112, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(20, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->Ln();

        $this->MultiCell(8, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(112, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(20, 4, '', 1, 'L', 0, 0);
        $this->Ln();

        $this->SetFont('helvetica', '', 11);
        $this->Cell(8, 6, 'D', 1, 0, 'C', 1);
        $this->Cell(172, 6, ' Quantitative Tasks', 1, 0, 'L', 1);
        $this->Ln();

        $this->SetFont('helvetica', '', 9);
        $maxnocells = 0;
        $cellcount = 0;
        $startX = $this->GetX();
        $startY = $this->GetY();
        $cellcount = $this->MultiCell(8,4,'',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(49,4, "Description",0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(12,4, 'Units',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(15,4, 'Set Value',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(18,4, 'Measured Values',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(18,4, 'Limit / Tolerance',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, 'Freq',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, 'Pass',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, 'Fail',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, 'N/A',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(20,4, 'Action',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $this->SetXY($startX,$startY);
        $this->MultiCell(8, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(49, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(12, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(15, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(18, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(18, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(20, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->Ln();

        $maxnocells = 0;
        $cellcount = 0;
        $startX = $this->GetX();
        $startY = $this->GetY();
        $cellcount = $this->MultiCell(8,4,'1',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(49,4, "MCCB Size",0,'L',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(12,4, 'Amp.',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(15,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(18,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(18,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, 'M',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, 'X',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(20,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $this->SetXY($startX,$startY);
        $this->MultiCell(8, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(49, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(12, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(15, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(18, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(18, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(20, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->Ln();

        $maxnocells = 0;
        $cellcount = 0;
        $startX = $this->GetX();
        $startY = $this->GetY();
        $cellcount = $this->MultiCell(8,4,'2',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(49,4, "RCCB Size",0,'L',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(12,4, 'Amp./mA',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(15,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(18,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(18,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, 'M',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, 'X',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(20,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $this->SetXY($startX,$startY);
        $this->MultiCell(8, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(49, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(12, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(15, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(18, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(18, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(20, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->Ln();

        $maxnocells = 0;
        $cellcount = 0;
        $startX = $this->GetX();
        $startY = $this->GetY();
        $cellcount = $this->MultiCell(8,4,'3',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(49,4, "Check Ampere",0,'L',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(12,4, 'Amp.',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(15,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(18,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(18,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, 'M',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, 'X',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(20,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $this->SetXY($startX,$startY);
        $this->MultiCell(8, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(49, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(12, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(15, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(18, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(18, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(20, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->Ln();

        $maxnocells = 0;
        $cellcount = 0;
        $startX = $this->GetX();
        $startY = $this->GetY();
        $cellcount = $this->MultiCell(8,4,'4',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(49,4, "Check Voltage",0,'L',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(12,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(15,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(18,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(18,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(20,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $this->SetXY($startX,$startY);
        $this->MultiCell(8, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(49, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(12, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(15, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(18, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(18, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(20, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->Ln();

        $maxnocells = 0;
        $cellcount = 0;
        $startX = $this->GetX();
        $startY = $this->GetY();
        $cellcount = $this->MultiCell(8,4,'',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(49,4, " i) Single Phase",0,'L',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(12,4, 'Volt',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(15,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(18,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(18,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, 'M',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, 'X',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(20,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $this->SetXY($startX,$startY);
        $this->MultiCell(8, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(49, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(12, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(15, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(18, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(18, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(20, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->Ln();

        $maxnocells = 0;
        $cellcount = 0;
        $startX = $this->GetX();
        $startY = $this->GetY();
        $cellcount = $this->MultiCell(8,4,'',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(49,4, " ii) Three Phase",0,'L',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(12,4, 'Volt',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(15,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(18,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(18,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, 'M',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, 'X',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(10,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(20,4, '',0,'C',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $this->SetXY($startX,$startY);
        $this->MultiCell(8, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(49, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(12, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(15, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(18, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(18, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->MultiCell(20, $maxnocells*4, '', 1, 'L', 0, 0);
        $this->Ln();

        $this->MultiCell(8, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(49, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(12, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(15, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(18, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(18, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(10, 4, '', 1, 'L', 0, 0);
        $this->MultiCell(20, 4, '', 1, 'L', 0, 0);
        $this->Ln();

        $this->SetFont('helvetica', '', 11);
        $this->Cell(8, 6, 'E', 1, 0, 'C', 1);
        $this->Cell(172, 6, ' Spare Parts / Material Used (if any)', 1, 0, 'L', 1);
        $this->Ln();

        $this->SetFont('helvetica', '', 9);
        $this->Cell(8, 16, '', 1, 0, 'C', 0);
        $this->Cell(172, 16, '', 1, 0, 'L', 0);
        $this->Ln();

        $this->SetFont('helvetica', '', 11);
        $this->Cell(8, 6, 'F', 1, 0, 'C', 1);
        $this->Cell(172, 6, ' Additional Report', 1, 0, 'L', 1);
        $this->Ln();

        $this->SetFont('helvetica', '', 9);
        $this->Cell(8, 10, '', 1, 0, 'C', 0);
        $this->Cell(72, 10, ' Yes                                  No', 1, 0, 'L', 0);
        $this->Cell(100, 10, ' Refer to ...............................................', 1, 0, 'L', 0);
        $this->Ln();

        $this->SetFont('helvetica', '', 11);
        $this->Cell(8, 6, 'G', 1, 0, 'C', 1);
        $this->Cell(172, 6, ' Comments / Remarks', 1, 0, 'L', 1);
        $this->Ln();

        $this->SetFont('helvetica', '', 9);
        $this->Cell(8, 16, '', 1, 0, 'C', 0);
        $this->Cell(172, 16, '', 1, 0, 'L', 0);
        $this->Ln();

        $this->MultiCell(60, 18, "Service By\n\n.................................\nName :\nDate :", 1, 'L', 0, 0);
        $this->MultiCell(60, 18, "Checked By\n\n.................................\nName :\nDate :", 1, 'L', 0, 0);
        $this->MultiCell(60, 18, "Verified By\n\n.................................\nName :\nDate :", 1, 'L', 0, 0);
        $this->Ln();

        $this->Cell(45, 5, 'Document No :', 1, 0, 'L', 0);
        $this->Cell(45, 5, 'Issue No :', 1, 0, 'L', 0);
        $this->Cell(45, 5, 'Effective Date :', 1, 0, 'L', 0);
        $this->Cell(45, 5, 'Page 1 of 1', 1, 0, 'L', 0);

		// Color and font restoration
		//$this->SetFillColor(224, 235, 255);
		//$this->SetTextColor(0);
		//$this->SetFont('');
		// Data
		//$fill = 0;
		//foreach($data as $row) {
		//	$this->Cell(20, 5, $row[0], 1, 0, 'L', 0);
		//	$this->Cell(20, 5, $row[1], 1, 0, 'L', 0);
		//	$this->Cell(20, 5, number_format($row[2]), 1, 0, 'R', 0);
		//	$this->Cell(20, 5, number_format($row[3]), 1, 0, 'R', 0);
		//	$this->Ln();
		//	//$fill=!$fill;
		//}
		//$this->Cell(200, 0, '', 'T');
	}
}

// create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 011');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
//$pdf->SetHeaderData('', PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 011', PDF_HEADER_STRING);

// remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

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

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
	require_once(dirname(__FILE__).'/lang/eng.php');
	$pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

// add a page
$pdf->AddPage();

// column titles
$header = array('Country', 'Capital', 'Area (sq km)', 'Pop. (thousands)');

// data loading
$data = $pdf->LoadData('data/table_data_demo.txt');

$pdf->Image('images/logo.png', 15, 15, 50, 20, 'PNG', 'http://www.tcpdf.org', '', true, 150, '', false, false, 0, false, false, false);

// set font
$pdf->SetFont('helvetica', '', 11);

$pdf->MultiCell(60, 20, '', 0, 'L', 0, 0, '', '');
$pdf->MultiCell(120, 20, "\nPREVENTIVE MAINTENANCE CHECKLIST\nBANK NEGARA MALAYSIA HQ", 1, 'C', 0, 0, '', '');

$pdf->Ln();
// set font
$pdf->SetFont('helvetica', '', 8);

// print colored table
$pdf->PpmTable();

// ---------------------------------------------------------

// close and output PDF document
$pdf->Output('example_011.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+

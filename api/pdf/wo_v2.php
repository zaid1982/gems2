<?php
require_once('tcpdf/tcpdf.php'); // Adjust path as needed

// Extend the TCPDF class to add custom header and footer (optional, but good practice)
class MYPDF extends TCPDF {
    // Page header
    public function Header() {
        // You can add a logo, document title, etc. here
        // Example: $this->Image('path/to/your/logo.png', 10, 10, 30);
        // $this->SetFont('helvetica', 'B', 15);
        // $this->Cell(0, 15, 'WORK REQUEST / WORK ORDER', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        // $this->Ln(5);
        // $this->SetFont('helvetica', '', 8);
        // $this->Cell(0, 15, 'System Generated - No Signature Required', 0, false, 'R', 0, '', 0, false, 'M', 'M');
    }

    // Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
        // Footer notes
        $this->SetX(10);
        $this->Cell(0, 10, 'This form is system-generated and does not require a signature.', 0, 0, 'L', 0, '', 0, false, 'T', 'M');
    }
}

// Create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Your Name/System');
$pdf->SetTitle('Work Request / Work Order');
$pdf->SetSubject('Work Request and Work Order Details');
$pdf->SetKeywords('TCPDF, PDF, work request, work order');

// Set default header data
// $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 001', PDF_HEADER_STRING);

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

// Set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
    require_once(dirname(__FILE__).'/lang/eng.php');
    $pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

// Set font
$pdf->SetFont('helvetica', '', 10);

// Add a page for the Work Request
$pdf->AddPage();

// --- WORK REQUEST (WR) SECTION ---
$pdf->SetFillColor(230, 230, 230); // Light grey for section headers
$pdf->SetTextColor(0, 0, 0);

// Section A: Complaint Details
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'WORK REQUEST (WR)', 1, 1, 'C', 1);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'A. Complaint Details [User Details: Public & Client for Complaints or Internal: for Self-Finding]', 1, 1, 'L', 1);
$pdf->SetFont('helvetica', '', 9);

// Data for WR section (you'd replace these with your actual PHP variables from a database or form)
$wr_data = [
    'Reported by' => 'Muhamad Husaini Bin Abdul Razak',
    'Phone No' => '017-315 6378',
    'Email' => 'Muhamadhusaini91@gmail.com',
    'Reported Date / Time' => '29/04/2025 1:10:PM',
    'Category' => 'Complaint',
    'Severity' => 'Normal',
    'Work Request No' => 'WRGFMHQ25042900001',
    'Location Complaint' => 'HQ – Level 3A',
];

$col_width_label = 40;
$col_width_value = 55;
$current_y = $pdf->GetY();

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->Cell($col_width_label, 6, 'Reported by:', 'LR', 0, 'L');
$pdf->Cell($col_width_value, 6, $wr_data['Reported by'], 'R', 0, 'L');
$pdf->Cell($col_width_label, 6, 'Phone No:', 'R', 0, 'L');
$pdf->Cell($col_width_value, 6, $wr_data['Phone No'], 'R', 1, 'L');

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->Cell($col_width_label, 6, 'Email:', 'LR', 0, 'L');
$pdf->Cell($col_width_value, 6, $wr_data['Email'], 'R', 0, 'L');
$pdf->Cell($col_width_label, 6, 'Reported Date / Time:', 'R', 0, 'L');
$pdf->Cell($col_width_value, 6, $wr_data['Reported Date / Time'], 'R', 1, 'L');

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->Cell($col_width_label, 6, 'Category:', 'LR', 0, 'L');
$pdf->Cell($col_width_value, 6, $wr_data['Category'], 'R', 0, 'L');
$pdf->Cell($col_width_label, 6, 'Severity:', 'R', 0, 'L');
$pdf->Cell($col_width_value, 6, $wr_data['Severity'], 'R', 1, 'L');

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->Cell($col_width_label, 6, 'Work Request No:', 'LR', 0, 'L');
$pdf->Cell($col_width_value, 6, $wr_data['Work Request No'], 'R', 0, 'L');
$pdf->Cell($col_width_label, 6, 'Location Complaint:', 'R', 0, 'L');
$pdf->Cell($col_width_value, 6, $wr_data['Location Complaint'], 'R', 1, 'L');
$pdf->Cell(0, 0, '', 'T', 1); // Bottom border for this section

$pdf->Ln(2); // Small gap

// Section B1: Description of Complaint
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'B1. Description of Complaint [Manual Entry]', 1, 1, 'L', 1);
$pdf->SetFont('helvetica', '', 9);
$description = 'Simen Pecah dekat HR'; // Example description
$pdf->MultiCell(0, 15, $description, 'LRB', 'L', 0, 1, '', '', true, 0, false, true, 0, 'T', false);

$pdf->Ln(2); // Small gap

// Section B2: Complaint Images
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'B2. Complaint Images [Complain from User]', 1, 1, 'L', 1);
$pdf->SetFont('helvetica', '', 9);

// Image placeholders (you'd loop through actual image paths here)
$image_width = 55;
$image_height = 40;
$image_spacing = 5;
$current_x = PDF_MARGIN_LEFT;

$pdf->Cell($image_width, 6, 'Image 1', 1, 0, 'C');
$pdf->Cell($image_spacing, 6, '', 0, 0);
$pdf->Cell($image_width, 6, 'Image 2', 1, 0, 'C');
$pdf->Cell($image_spacing, 6, '', 0, 0);
$pdf->Cell($image_width, 6, 'Image 3', 1, 1, 'C');

// Placeholder for images - you'd replace 'path/to/image.jpg' with actual image paths
// And adjust coordinates as needed
$img_desc = 'Description: [Manual Entry]';
$img_time = 'Date / Time Taken: [System Generated]';
$img_gps = 'GPS Coordinates: [System Generated]';

$y_before_images = $pdf->GetY();
$pdf->MultiCell($image_width, $image_height, '[Capture Image]', 1, 'C', 0, 0, $current_x, $y_before_images, true, 0, false, true, $image_height, 'M', false);
// $pdf->Image('path/to/image1.jpg', $current_x, $y_before_images, $image_width, $image_height, '', '', '', false, 300, '', false, false, 0, false, false, false);

$pdf->MultiCell($image_width, $image_height, '[Capture Image]', 1, 'C', 0, 0, $current_x + $image_width + $image_spacing, $y_before_images, true, 0, false, true, $image_height, 'M', false);
// $pdf->Image('path/to/image2.jpg', $current_x + $image_width + $image_spacing, $y_before_images, $image_width, $image_height, '', '', '', false, 300, '', false, false, 0, false, false, false);

$pdf->MultiCell($image_width, $image_height, '[Capture Image]', 1, 'C', 0, 0, $current_x + ($image_width + $image_spacing) * 2, $y_before_images, true, 0, false, true, $image_height, 'M', false);
// $pdf->Image('path/to/image3.jpg', $current_x + ($image_width + $image_spacing) * 2, $y_before_images, $image_width, $image_height, '', '', '', false, 300, '', false, false, 0, false, false, false);
$pdf->Ln($image_height + 2); // Move past the image boxes

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->MultiCell($image_width, 8, $img_desc . "\n" . $img_time, 'LR', 'L', 0, 0);
$pdf->Cell($image_spacing, 8, '', 0, 0);
$pdf->MultiCell($image_width, 8, $img_desc . "\n" . $img_time, 'R', 'L', 0, 0);
$pdf->Cell($image_spacing, 8, '', 0, 0);
$pdf->MultiCell($image_width, 8, $img_desc . "\n" . $img_time, 'R', 'L', 0, 1);

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->MultiCell($image_width, 6, $img_gps, 'LRB', 'L', 0, 0);
$pdf->Cell($image_spacing, 6, '', 0, 0);
$pdf->MultiCell($image_width, 6, $img_gps, 'RB', 'L', 0, 0);
$pdf->Cell($image_spacing, 6, '', 0, 0);
$pdf->MultiCell($image_width, 6, $img_gps, 'RB', 'L', 0, 1);

$pdf->Ln(2);

// Section C1: Work Assessment Details
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'C1. Work Assessment Details [Selected by P.I.C. to verify the complaint]', 1, 1, 'L', 1);
$pdf->SetFont('helvetica', '', 9);

$assessment_data = [
    'Person in Charge' => 'Nur Hazwani Binti Aziz',
    'SLA Respond Time' => 'Normal',
    'Email' => 'hazwani@globalfm.com.my',
    'WR Due Date Time' => '30/04/2025 12:00PM',
    'Respond Date / Duration' => ['30/04/2025 12:00PM', '2 Hours & 30 Minutes'],
    'Respond Status' => 'Within/Exceed',
];

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->Cell($col_width_label, 6, 'Person in Charge:', 'LR', 0, 'L');
$pdf->Cell($col_width_value, 6, $assessment_data['Person in Charge'], 'R', 0, 'L');
$pdf->Cell($col_width_label, 6, 'SLA Respond Time:', 'R', 0, 'L');
$pdf->Cell($col_width_value, 6, $assessment_data['SLA Respond Time'], 'R', 1, 'L');

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->Cell($col_width_label, 6, 'Email:', 'LR', 0, 'L');
$pdf->Cell($col_width_value, 6, $assessment_data['Email'], 'R', 0, 'L');
$pdf->Cell($col_width_label, 6, 'WR Due Date Time:', 'R', 0, 'L');
$pdf->Cell($col_width_value, 6, $assessment_data['WR Due Date Time'], 'R', 1, 'L');

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->Cell($col_width_label, 6, 'Respond Date / Duration:', 'LR', 0, 'L');
$pdf->Cell($col_width_value, 6, implode(', ', $assessment_data['Respond Date / Duration']), 'R', 0, 'L');
$pdf->Cell($col_width_label, 6, 'Respond Status:', 'R', 0, 'L');
$pdf->Cell($col_width_value, 6, $assessment_data['Respond Status'], 'R', 1, 'L');
$pdf->Cell(0, 0, '', 'T', 1);

$pdf->Ln(2);

// Section C2: Response Images
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'C2. Response Images [P.I.C. verification of the complaint]', 1, 1, 'L', 1);
$pdf->SetFont('helvetica', '', 9);

$pdf->Cell($image_width, 6, 'Image 1', 1, 0, 'C');
$pdf->Cell($image_spacing, 6, '', 0, 0);
$pdf->Cell($image_width, 6, 'Image 2', 1, 0, 'C');
$pdf->Cell($image_spacing, 6, '', 0, 0);
$pdf->Cell($image_width, 6, 'Image 3', 1, 1, 'C');

$img_desc_c2 = 'Description: [Manual Entry]';
$img_time_c2 = 'Date / Time Taken: [System Generated]';
$img_latlong_c2 = 'Longitude/ Latitude: [System Generated]';

$y_before_images_c2 = $pdf->GetY();
$pdf->MultiCell($image_width, $image_height, '[Capture Image]', 1, 'C', 0, 0, $current_x, $y_before_images_c2, true, 0, false, true, $image_height, 'M', false);
$pdf->MultiCell($image_width, $image_height, '[Capture Image]', 1, 'C', 0, 0, $current_x + $image_width + $image_spacing, $y_before_images_c2, true, 0, false, true, $image_height, 'M', false);
$pdf->MultiCell($image_width, $image_height, '[Capture Image]', 1, 'C', 0, 0, $current_x + ($image_width + $image_spacing) * 2, $y_before_images_c2, true, 0, false, true, $image_height, 'M', false);
$pdf->Ln($image_height + 2);

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->MultiCell($image_width, 8, $img_desc_c2 . "\n" . $img_time_c2, 'LR', 'L', 0, 0);
$pdf->Cell($image_spacing, 8, '', 0, 0);
$pdf->MultiCell($image_width, 8, $img_desc_c2 . "\n" . $img_time_c2, 'R', 'L', 0, 0);
$pdf->Cell($image_spacing, 8, '', 0, 0);
$pdf->MultiCell($image_width, 8, $img_desc_c2 . "\n" . $img_time_c2, 'R', 'L', 0, 1);

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->MultiCell($image_width, 6, $img_latlong_c2, 'LRB', 'L', 0, 0);
$pdf->Cell($image_spacing, 6, '', 0, 0);
$pdf->MultiCell($image_width, 6, $img_latlong_c2, 'RB', 'L', 0, 0);
$pdf->Cell($image_spacing, 6, '', 0, 0);
$pdf->MultiCell($image_width, 6, $img_latlong_c2, 'RB', 'L', 0, 1);

$pdf->Ln(2);

// Section D1: Validation Details
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'D1. Validation Details [Who issue/assigned the WR to the P.I.C.]', 1, 1, 'L', 1);
$pdf->SetFont('helvetica', '', 9);

$validation_data = [
    'Validation by' => 'Azlan Bin Tuah',
    'Designation' => 'Head of Department',
    'Verified Date' => '31/04/2025 12:00 PM',
    'Work Request Status' => 'Accept/Reject',
];

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->Cell($col_width_label, 6, 'Validation by:', 'LR', 0, 'L');
$pdf->Cell($col_width_value, 6, $validation_data['Validation by'], 'R', 0, 'L');
$pdf->Cell($col_width_label, 6, 'Designation:', 'R', 0, 'L');
$pdf->Cell($col_width_value, 6, $validation_data['Designation'], 'R', 1, 'L');

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->Cell($col_width_label, 6, 'Verified Date:', 'LR', 0, 'L');
$pdf->Cell($col_width_value, 6, $validation_data['Verified Date'], 'R', 0, 'L');
$pdf->Cell($col_width_label, 6, 'Work Request Status:', 'R', 0, 'L');
$pdf->Cell($col_width_value, 6, $validation_data['Work Request Status'], 'R', 1, 'L');
$pdf->Cell(0, 0, '', 'T', 1);

$pdf->Ln(2);

// Section D2: Remark Details
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'D2. Remark Details [Remarks before selecting WR Status; ensure a note is added if rejected] [Manual Entry]', 1, 1, 'L', 1);
$pdf->SetFont('helvetica', '', 9);
$remark_details = 'Saya telah berbincang dengan aiman pada 31/04/2025 mengenai complain ini dan didapati complain ini luar dari scope kerja GFM.tetapi kami juga telah meminta kepada aiman untuk mengisi balik wo tersebut sekirnaya ingin membuat complain yang lain';
$pdf->MultiCell(0, 15, $remark_details, 'LRB', 'L', 0, 1, '', '', true, 0, false, true, 0, 'T', false);

// ---------------------------------------------------------
// Add a new page for the Work Order section
$pdf->AddPage();

// --- WORK ORDER (WO) SECTION ---
$pdf->SetFillColor(230, 230, 230); // Light grey for section headers
$pdf->SetTextColor(0, 0, 0);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'WORK ORDER (WO)', 1, 1, 'C', 1);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'A. Work Order Details', 1, 1, 'L', 1);
$pdf->SetFont('helvetica', '', 9);

$wo_data = [
    'Work Order No' => 'WOGFMHQ25042900001',
    'Status' => 'Completed',
    'Work Request No' => 'WRGFMHQ25042900001', // Example, from WR data 
    'Category' => 'Complaint', // Example, from WR data 
    'Location Name' => 'HQ – Level 3A', // Example, from WR data 
    'Location Code' => 'HQ-L3A', // Example, from WR data 
    'Asset Name' => 'Main Server Unit',
    'Asset Code' => 'MSU-001', // Example, from Asset Name 
    'Severity' => 'Normal', // Example, from WR data 
    'WO Due Date/Time' => '30/04/2025 12:00PM', // Example, from WR data SLA 
    'Complaint Description' => 'Simen Pecah dekat HR', // Example, from WR data 
];

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->Cell($col_width_label, 6, 'Work Order No:', 'LR', 0, 'L');
$pdf->Cell($col_width_value, 6, $wo_data['Work Order No'], 'R', 0, 'L');
$pdf->Cell($col_width_label, 6, 'Status:', 'R', 0, 'L');
$pdf->Cell($col_width_value, 6, $wo_data['Status'], 'R', 1, 'L');

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->Cell($col_width_label, 6, 'Work Request No:', 'LR', 0, 'L');
$pdf->Cell($col_width_value, 6, $wo_data['Work Request No'], 'R', 0, 'L');
$pdf->Cell($col_width_label, 6, 'Category:', 'R', 0, 'L');
$pdf->Cell($col_width_value, 6, $wo_data['Category'], 'R', 1, 'L');

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->Cell($col_width_label, 6, 'Location Name:', 'LR', 0, 'L');
$pdf->Cell($col_width_value, 6, $wo_data['Location Name'], 'R', 0, 'L');
$pdf->Cell($col_width_label, 6, 'Location Code:', 'R', 0, 'L');
$pdf->Cell($col_width_value, 6, $wo_data['Location Code'], 'R', 1, 'L');

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->Cell($col_width_label, 6, 'Asset Name:', 'LR', 0, 'L');
$pdf->Cell($col_width_value, 6, $wo_data['Asset Name'], 'R', 0, 'L');
$pdf->Cell($col_width_label, 6, 'Asset Code:', 'R', 0, 'L');
$pdf->Cell($col_width_value, 6, $wo_data['Asset Code'], 'R', 1, 'L');

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->Cell($col_width_label, 6, 'Severity:', 'LR', 0, 'L');
$pdf->Cell($col_width_value, 6, $wo_data['Severity'], 'R', 0, 'L');
$pdf->Cell($col_width_label, 6, 'WO Due Date/Time:', 'R', 0, 'L');
$pdf->Cell($col_width_value, 6, $wo_data['WO Due Date/Time'], 'R', 1, 'L');
$pdf->Cell(0, 0, '', 'T', 1);

$pdf->Ln(2);
$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell($col_width_label * 2, 6, 'Complaint Description:', 'LR', 0, 'L');
$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(0, 6, $wo_data['Complaint Description'], 'R', 'L', 0, 1, '', '', true, 0, false, true, 0, 'T', false);
$pdf->Cell(0, 0, '', 'B', 1); // Bottom border for description row

$pdf->Ln(2);

// Section B1: Work Assignment Details
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'B1. Work Assignment Details [Details of task issuer and receiver]', 1, 1, 'L', 1);
$pdf->SetFont('helvetica', '', 9);

$assignment_data = [
    'Received By' => 'Azlan Tuah',
    'Assigned To' => 'Aiman',
    'Date Assigned' => '24/04/2025',
    'Phone No' => '012-334 5567',
];

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->Cell($col_width_label, 6, 'Received By:', 'LR', 0, 'L');
$pdf->Cell($col_width_value, 6, $assignment_data['Received By'], 'R', 0, 'L');
$pdf->Cell($col_width_label, 6, 'Assigned To:', 'R', 0, 'L');
$pdf->Cell($col_width_value, 6, $assignment_data['Assigned To'], 'R', 1, 'L');

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->Cell($col_width_label, 6, 'Date Assigned:', 'LR', 0, 'L');
$pdf->Cell($col_width_value, 6, $assignment_data['Date Assigned'], 'R', 0, 'L');
$pdf->Cell($col_width_label, 6, 'Phone No:', 'R', 0, 'L');
$pdf->Cell($col_width_value, 6, $assignment_data['Phone No'], 'R', 1, 'L');
$pdf->Cell(0, 0, '', 'T', 1);

$pdf->Ln(2);

// Section B2: Support Personnel
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'B2. Support Personnel [Team members involved in execution]', 1, 1, 'L', 1);
$pdf->SetFont('helvetica', '', 9);

$support_personnel = [
    ['No.' => 1, 'Name' => 'Muhamad Husaini'],
];

$pdf->Cell(15, 6, 'No.', 1, 0, 'C');
$pdf->Cell(0, 6, 'Name', 1, 1, 'C');

foreach ($support_personnel as $person) {
    $pdf->Cell(15, 6, $person['No.'], 'LRB', 0, 'C');
    $pdf->Cell(0, 6, $person['Name'], 'RB', 1, 'L');
}
// Add empty rows for more personnel
for ($i = 0; $i < 3; $i++) { // Example: 3 empty rows
    $pdf->Cell(15, 6, '', 'LRB', 0, 'C');
    $pdf->Cell(0, 6, '', 'RB', 1, 'L');
}
$pdf->Ln(2);

// Section C: Material Details
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'C. Material Details [Parts or materials issued, returned, and tracked based on inventory records in the GEMS module]', 1, 1, 'L', 1);
$pdf->SetFont('helvetica', '', 9);

$pdf->Cell(25, 6, 'Part No.', 1, 0, 'C');
$pdf->Cell(50, 6, 'Item Description', 1, 0, 'C');
$pdf->Cell(25, 6, 'Issue Type', 1, 0, 'C');
$pdf->Cell(15, 6, '(D/I)', 1, 0, 'C');
$pdf->Cell(20, 6, 'Unit', 1, 0, 'C');
$pdf->Cell(30, 6, 'Quantity Taken', 1, 0, 'C');
$pdf->Cell(0, 6, 'Quantity Return', 1, 1, 'C');

// Example empty rows for material details
for ($i = 0; $i < 4; $i++) {
    $pdf->Cell(25, 6, '', 'LRB', 0, 'C');
    $pdf->Cell(50, 6, '', 'RB', 0, 'L');
    $pdf->Cell(25, 6, '', 'RB', 0, 'C');
    $pdf->Cell(15, 6, '', 'RB', 0, 'C');
    $pdf->Cell(20, 6, '', 'RB', 0, 'C');
    $pdf->Cell(30, 6, '', 'RB', 0, 'C');
    $pdf->Cell(0, 6, '', 'RB', 1, 'C');
}
$pdf->Cell(0, 6, '**D = Direct Issue, I = Inventory', 0, 1, 'L');
$pdf->Ln(2);

// Section D: Work Execution Details
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'D. Work Execution Details [Action duration, task notes, and work timeline]', 1, 1, 'L', 1);
$pdf->SetFont('helvetica', '', 9);

$execution_details = '[Manual Entry]';
$pdf->MultiCell(0, 15, $execution_details, 'LRB', 'L', 0, 1, '', '', true, 0, false, true, 0, 'T', false);

$execution_data = [
    'Start Date & Time' => '[System Generated based on WO start]',
    'End Date & Time' => '[System Generated based on WO task end]',
    'Duration' => '13 Hours',
    'Status' => 'Within SLA/Exceed',
];

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->Cell($col_width_label, 6, 'Start Date & Time:', 'LR', 0, 'L');
$pdf->Cell($col_width_value, 6, $execution_data['Start Date & Time'], 'R', 0, 'L');
$pdf->Cell($col_width_label, 6, 'End Date & Time:', 'R', 0, 'L');
$pdf->Cell($col_width_value, 6, $execution_data['End Date & Time'], 'R', 1, 'L');

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->Cell($col_width_label, 6, 'Duration:', 'LR', 0, 'L');
$pdf->Cell($col_width_value, 6, $execution_data['Duration'], 'R', 0, 'L');
$pdf->Cell($col_width_label, 6, 'Status:', 'R', 0, 'L');
$pdf->Cell($col_width_value, 6, $execution_data['Status'], 'R', 1, 'L');
$pdf->Cell(0, 0, '', 'T', 1);

$pdf->Ln(2);

// Section E: Work Completion & Verification
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'E. Work Completion & Verification [Sign-off and satisfaction rating]', 1, 1, 'L', 1);
$pdf->SetFont('helvetica', '', 9);

$signature_area_height = 25; // Height for the signature boxes
$signature_label_width = (210 - (PDF_MARGIN_LEFT + PDF_MARGIN_RIGHT) - 10) / 3; // Approx width for 3 cols

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->Cell($signature_label_width, 6, 'Serviced By:', 1, 0, 'C');
$pdf->Cell(5, 6, '', 0, 0); // Gap
$pdf->Cell($signature_label_width, 6, 'Checked By:', 1, 0, 'C');
$pdf->Cell(5, 6, '', 0, 0); // Gap
$pdf->Cell($signature_label_width, 6, 'Verified By:', 1, 1, 'C');

$y_before_signatures = $pdf->GetY();
$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->Cell($signature_label_width, $signature_area_height, '[The person who performed the task for sign]', 1, 0, 'C');
$pdf->Cell(5, $signature_area_height, '', 0, 0);
$pdf->Cell($signature_label_width, $signature_area_height, '[If not a self-finding case, this section must be filled and sign. If self-finding, mark as “Not Required” or leave blank.]', 1, 0, 'C');
$pdf->Cell(5, $signature_area_height, '', 0, 0);
$pdf->Cell($signature_label_width, $signature_area_height, '[For self-finding cases, the immediate superior must sign here. For other cases, the client or relevant party must sign.]', 1, 1, 'C');
$pdf->Ln(1); // Small space

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->MultiCell($signature_label_width, 10, "Name: Aiman [System Generated]", 'LRB', 'L', 0, 0, '', '', true, 0, false, true, 0, 'T', false);
$pdf->Cell(5, 10, '', 0, 0);
$pdf->MultiCell($signature_label_width, 10, "Name: Azlan Tuah [System Generated]", 'RB', 'L', 0, 0, '', '', true, 0, false, true, 0, 'T', false);
$pdf->Cell(5, 10, '', 0, 0);
$pdf->MultiCell($signature_label_width, 10, "Name: Zharif [System Generated]", 'RB', 'L', 0, 1, '', '', true, 0, false, true, 0, 'T', false);

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->MultiCell($signature_label_width, 10, "Designation: [System Generated]\nDate / Time: [System Generated]", 'LRB', 'L', 0, 0, '', '', true, 0, false, true, 0, 'T', false);
$pdf->Cell(5, 10, '', 0, 0);
$pdf->MultiCell($signature_label_width, 10, "Designation: [System Generated]\nDate / Time: [System Generated]", 'RB', 'L', 0, 0, '', '', true, 0, false, true, 0, 'T', false);
$pdf->Cell(5, 10, '', 0, 0);
$pdf->MultiCell($signature_label_width, 10, "Designation: [System Generated]\nDate / Time: [System Generated]", 'RB', 'L', 0, 1, '', '', true, 0, false, true, 0, 'T', false);

$pdf->Ln(2);
$pdf->MultiCell(0, 10, '**Notes: For self-finding cases, part-level checks do not require a signature. Final verification must be signed by the immediate superior.', 0, 'L', 0, 1);
$pdf->Ln(2);

// Satisfactory Level
$pdf->Cell(40, 6, 'Satisfactory Level: [Choose]', 0, 0, 'L');
$pdf->Cell(10, 6, '1', 1, 0, 'C');
$pdf->Cell(30, 6, 'Very Dissatisfied', 1, 0, 'L');
$pdf->Cell(10, 6, '2', 1, 0, 'C');
$pdf->Cell(25, 6, 'Dissatisfied', 1, 0, 'L');
$pdf->Cell(10, 6, '3', 1, 0, 'C');
$pdf->Cell(20, 6, 'Neutral', 1, 0, 'L');
$pdf->Cell(10, 6, '4', 1, 0, 'C');
$pdf->Cell(20, 6, 'Satisfied', 1, 0, 'L');
$pdf->Cell(10, 6, '5', 1, 0, 'C');
$pdf->Cell(0, 6, 'Very Satisfied', 1, 1, 'L');
$pdf->Ln(2);

// Section J: Photo Documentation (Before)
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'J. Photo Documentation (Before) [Visual proof for each repair stage]', 1, 1, 'L', 1);
$pdf->SetFont('helvetica', '', 9);

$pdf->Cell($image_width, 6, 'Image 1', 1, 1, 'C');
$y_before_images_wo = $pdf->GetY();
$pdf->MultiCell($image_width, $image_height, '[Capture Image]', 1, 'C', 0, 0, $current_x, $y_before_images_wo, true, 0, false, true, $image_height, 'M', false);
$pdf->Ln($image_height + 2);

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->MultiCell($image_width, 8, $img_desc_c2 . "\n" . $img_time_c2, 'LR', 'L', 0, 0);
$pdf->MultiCell($image_width, 6, $img_latlong_c2, 'LRB', 'L', 0, 1);
$pdf->Ln(2);

// Section J: Photo Documentation (During)
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'J. Photo Documentation (During) [Visual proof for each repair stage]', 1, 1, 'L', 1);
$pdf->SetFont('helvetica', '', 9);

$pdf->Cell($image_width, 6, 'Image 1', 1, 0, 'C');
$pdf->Cell($image_spacing, 6, '', 0, 0);
$pdf->Cell($image_width, 6, 'Image 2', 1, 0, 'C');
$pdf->Cell($image_spacing, 6, '', 0, 0);
$pdf->Cell($image_width, 6, 'Image 3', 1, 1, 'C');

$y_before_images_woduring = $pdf->GetY();
$pdf->MultiCell($image_width, $image_height, '[Capture Image]', 1, 'C', 0, 0, $current_x, $y_before_images_woduring, true, 0, false, true, $image_height, 'M', false);
$pdf->MultiCell($image_width, $image_height, '[Capture Image]', 1, 'C', 0, 0, $current_x + $image_width + $image_spacing, $y_before_images_woduring, true, 0, false, true, $image_height, 'M', false);
$pdf->MultiCell($image_width, $image_height, '[Capture Image]', 1, 'C', 0, 0, $current_x + ($image_width + $image_spacing) * 2, $y_before_images_woduring, true, 0, false, true, $image_height, 'M', false);
$pdf->Ln($image_height + 2);

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->MultiCell($image_width, 8, $img_desc_c2 . "\n" . $img_time_c2, 'LR', 'L', 0, 0);
$pdf->Cell($image_spacing, 8, '', 0, 0);
$pdf->MultiCell($image_width, 8, $img_desc_c2 . "\n" . $img_time_c2, 'R', 'L', 0, 0);
$pdf->Cell($image_spacing, 8, '', 0, 0);
$pdf->MultiCell($image_width, 8, $img_desc_c2 . "\n" . $img_time_c2, 'R', 'L', 0, 1);

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->MultiCell($image_width, 6, $img_latlong_c2, 'LRB', 'L', 0, 0);
$pdf->Cell($image_spacing, 6, '', 0, 0);
$pdf->MultiCell($image_width, 6, $img_latlong_c2, 'RB', 'L', 0, 0);
$pdf->Cell($image_spacing, 6, '', 0, 0);
$pdf->MultiCell($image_width, 6, $img_latlong_c2, 'RB', 'L', 0, 1);
$pdf->Ln(2);

// Section J: Photo Documentation (After)
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'J. Photo Documentation (After) [Visual proof for each repair stage]', 1, 1, 'L', 1);
$pdf->SetFont('helvetica', '', 9);

$pdf->Cell($image_width, 6, 'Image 1', 1, 1, 'C');
$y_before_images_woafter = $pdf->GetY();
$pdf->MultiCell($image_width, $image_height, '[Capture Image]', 1, 'C', 0, 0, $current_x, $y_before_images_woafter, true, 0, false, true, $image_height, 'M', false);
$pdf->Ln($image_height + 2);

$pdf->SetX(PDF_MARGIN_LEFT);
$pdf->MultiCell($image_width, 8, $img_desc_c2 . "\n" . $img_time_c2, 'LR', 'L', 0, 0);
$pdf->MultiCell($image_width, 6, $img_latlong_c2, 'LRB', 'L', 0, 1);
$pdf->Ln(2);

// Additional Notes mentioned in the document
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 6, 'Notes:', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(0, 5, 'Upon completion, the work request will be closed, and the user will receive a notification email for receipt acknowledgment.', 0, 'L', 0, 1);
$pdf->MultiCell(0, 5, 'If the Work Request (WR) is rejected, and you wish to submit a new complaint or request, please initiate a new Work Request submission.', 0, 'L', 0, 1);
$pdf->MultiCell(0, 5, '[Note: At least one image is required for each stage. Additional images are optional as needed].', 0, 'L', 0, 1);


// ---------------------------------------------------------

// Close and output PDF document
$pdf->Output('Work_Request_Work_Order.pdf', 'I');

?>
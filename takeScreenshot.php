<?php 
require_once "common.php";
require_once "vendor/tcpdf/tcpdf.php";
if(isset($_GET["_id"]) && isset($_GET["_file"])){
	$_id 		= trim($_GET["_id"]);
	$_file 		= $_GET["_file"];
	//get URL and navigate to photo page
	$full_path = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	// $full_path = 'https://www.google.com';
	$result = str_replace('takeScreenshot.php','pdf_conversion.php',$full_path);
	//Take snapshot of image to use in PDF
	$result = urlencode($result);
	$googlePagespeedData = file_get_contents("https://www.googleapis.com/pagespeedonline/v4/runPagespeed?url=$result&screenshot=true");
	$googlePagespeedData = json_decode($googlePagespeedData, true);
	print_rr($googlePagespeedData);
	$screenshot = $googlePagespeedData['screenshot']['data'];
	$screenshot = str_replace(array('_','-'),array('/','+'),$screenshot);
	file_put_contents('vvv.txt', $screenshot); 
	// imagejpeg($screenshot,"test.jpg");
	echo "<img src=\"data:image/jpeg;base64,".$screenshot."\" />";
 








}


function generatePDF(){
	$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

	// set document information
	// $pdf->SetCreator(PDF_CREATOR);
	// $pdf->SetAuthor('Nicola Asuni');
	// $pdf->SetTitle('TCPDF Example 001');
	// $pdf->SetSubject('TCPDF Tutorial');
	// $pdf->SetKeywords('TCPDF, PDF, example, test, guide');

	// set default header data
	// $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 001', PDF_HEADER_STRING, array(0,64,255), array(0,64,128));
	$pdf->setFooterData(array(0,64,0), array(0,64,128));

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

	// set default font subsetting mode
	$pdf->setFontSubsetting(true);

	// Set font
	// dejavusans is a UTF-8 Unicode font, if you only need to
	// print standard ASCII chars, you can use core fonts like
	// helvetica or times to reduce file size.
	$pdf->SetFont('dejavusans', '', 11, '', true);

	// Add a page
	// This method has several options, check the source code documentation for more information.
	$pdf->AddPage();

	// set text shadow effect
	$pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));

	// Set some content to print
	// $html = <<<EOD
	// <h1>Welcome to <a href="http://www.tcpdf.org" style="text-decoration:none;background-color:#CC0000;color:black;">&nbsp;<span style="color:black;">TC</span><span style="color:white;">PDF</span>&nbsp;</a>!</h1>
	// <i>This is the first example of TCPDF library.</i>
	// <p>This text is printed using the <i>writeHTMLCell()</i> method but you can also use: <i>Multicell(), writeHTML(), Write(), Cell() and Text()</i>.</p>
	// <p>Please check the source code documentation and other examples for further information.</p>
	// <p style="color:#CC0000;">TO IMPROVE AND EXPAND TCPDF I NEED YOUR SUPPORT, PLEASE <a href="http://sourceforge.net/donate/index.php?group_id=128076">MAKE A DONATION!</a></p>
	// EOD;
	//  echo $html;

	// Print text using writeHTMLCell()
	$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
	$pdf->Image('AAA.jpg',15, 140, 100, 100);
	//// Image($file, $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false)

	// ---------------------------------------------------------

	// Close and output PDF document
	// This method has several options, check the source code documentation for more information.
	$pdf->Output('example_001.pdf', 'I');

	//============================================================+
	// END OF FILE
	//============================================================+

}







?>
<?php
$uid = md5(uniqid(time()));
$header = "From: seminar@pdp.gov.my\r\n";
$header .= "MIME-Version: 1.0\r\n";
$header .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"\r\n\r\n";

$nmessage = "--".$uid."\r\n";
$nmessage .= "Content-type:text/html; charset=utf-8\n";
$nmessage .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
$nmessage .= "Test\r\n\r\n";
$nmessage .= "--".$uid."\r\n";

mail('hemppok.kembong@gmail.com', 'GEMS 2.0 Forgot Password', $nmessage, $header, '-fzaid@addeen-legacy.com.my');
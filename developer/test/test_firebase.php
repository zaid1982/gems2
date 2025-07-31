<?php

$title = "GEMS 2.0";
$body = "Your PPM task (PBNMHQ19070900001) has been re-Opened for re-maintenance.";

$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => "https://fcm.googleapis.com/fcm/send",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => "{\n \"to\" : \"cL0YUuOMLJU:APA91bFVGyjFQD4K9rJUws52optj4FZ8sqfoMLISuv--M7wn2f7Xg2i2Hob7KWlWMHeu8nfxbxprI7RmA1JOGUOyJBKu8mF2ySsRQ-37lkQwRo4s-zIWzDyYECq2Kyz4b_TYi4fcfEgb\",\n \"collapse_key\" : \"type_a\",\n \"notification\" : {\n     \"body\" : \"".$body."\",\n     \"title\": \"".$title."\"\n }\n}",
    CURLOPT_HTTPHEADER => array(
        "Accept: */*",
        "Authorization: key=AAAA0VbV4yY:APA91bEkhqjl72wrey1qcbBlaaGNZTVtRcDQMwBkIOTkzWzytnTHbEVypleaWjHA3SeO0klvh9M2M_MaX-1yf2jupOZnDyn2Zx9lx2CLDgZGOwPfBpr1HvFO14lnZSKlpqi1rKM5BX-i",
        "Cache-Control: no-cache",
        "Connection: keep-alive",
        "Content-Type: application/json",
        "Host: fcm.googleapis.com",
        "accept-encoding: gzip, deflate",
        "cache-control: no-cache"
    ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
    echo "cURL Error #:" . $err;
} else {
    echo $response;
}
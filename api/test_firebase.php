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
    CURLOPT_POSTFIELDS => "{\n \"to\" : \"f_gWCiQIopg:APA91bE0UqmP0RWwL_eO_f_DrYwHHaqqkbvuX5Jmj6PwW3qENDp4nZlOjB9loBPO7ldiWbzubQZwKalQQh74W-VQuaio_Xw8D2FxPD7dXB6glThpY2Uh-NW7QzzutynUIvhVpLlcccKf\",\n \"collapse_key\" : \"type_a\",\n \"notification\" : {\n     \"body\" : \"".$body."\",\n     \"title\": \"".$title."\"\n }\n}",
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
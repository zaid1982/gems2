<?php
require_once __DIR__.'/../api/class/Constant.php';
require_once __DIR__.'/../api/class/DbMysql.php';

/**
 * Creates email templates and parameters for Space Reservations (Created/Updated/Canceled).
 * Run once after deployment.
 */
function upsertTemplate(string $title, string $html, array $params): int {
    DbMysql::connect();
    $existingId = DbMysql::selectColumn('email_template', array('emailTemplateTitle'=>$title), 'emailTemplateId');
    if (!empty($existingId)) {
        DbMysql::update('email_template', array('emailTemplateHtml'=>$html), array('emailTemplateId'=>intval($existingId)));
        // Refresh parameters: delete and re-insert to keep in sync
        DbMysql::delete('email_parameter', array('emailTemplateId'=>intval($existingId)));
        foreach ($params as $p) { DbMysql::insert('email_parameter', array('emailTemplateId'=>intval($existingId), 'emailParamCode'=>$p)); }
        return intval($existingId);
    }
    $newId = DbMysql::insert('email_template', array('emailTemplateTitle'=>$title, 'emailTemplateHtml'=>$html));
    foreach ($params as $p) { DbMysql::insert('email_parameter', array('emailTemplateId'=>intval($newId), 'emailParamCode'=>$p)); }
    return intval($newId);
}

try {
    DbMysql::connect();
    $createdHtml = '<p>Hi [fullName],</p>'
        .'<p>Your reservation is confirmed.</p>'
        .'<ul>'
        .'<li>Space: [space_name] ([location_name])</li>'
        .'<li>Start: [reservation_start]</li>'
        .'<li>End: [reservation_end]</li>'
        .'</ul>'
        .'<p>You can reschedule or cancel from My Reservations.</p>';
    $updatedHtml = '<p>Hi [fullName],</p>'
        .'<p>Your reservation was updated.</p>'
        .'<ul>'
        .'<li>Space: [space_name] ([location_name])</li>'
        .'<li>Old Start: [old_start] → New Start: [reservation_start]</li>'
        .'<li>Old End: [old_end] → New End: [reservation_end]</li>'
        .'</ul>';
    $canceledHtml = '<p>Hi [fullName],</p>'
        .'<p>Your reservation has been canceled.</p>'
        .'<ul>'
        .'<li>Space: [space_name] ([location_name])</li>'
        .'<li>Start: [reservation_start]</li>'
        .'<li>End: [reservation_end]</li>'
        .'</ul>'
        .'<p>Reason: [cancel_reason]</p>';

    $createdParams = array('space_name','location_name','reservation_start','reservation_end');
    $updatedParams = array('space_name','location_name','old_start','old_end','reservation_start','reservation_end');
    $canceledParams = array('space_name','location_name','reservation_start','reservation_end','cancel_reason');

    $id1 = upsertTemplate('Space Reservation Created', $createdHtml, $createdParams);
    $id2 = upsertTemplate('Space Reservation Updated', $updatedHtml, $updatedParams);
    $id3 = upsertTemplate('Space Reservation Canceled', $canceledHtml, $canceledParams);

    echo json_encode(array('success'=>true, 'result'=>array('created'=>$id1, 'updated'=>$id2, 'canceled'=>$id3)));
} catch (Throwable $ex) {
    echo json_encode(array('success'=>false, 'errmsg'=>$ex->getMessage()));
}

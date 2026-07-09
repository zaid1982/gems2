-- Migration: Seed email templates used by PTW and Space Management notifications.
-- Safe to rerun: template rows are upserted; parameter rows for these template IDs
-- are refreshed to avoid duplicates in email_parameter.

INSERT INTO `email_template`
  (`email_template_id`, `email_template_name`, `email_template_desc`, `email_template_title`, `email_template_html`, `email_template_status`)
VALUES
  (300, 'PTW_PENDING_SUPERVISOR', 'PTW notification: pending supervisor review', 'GEMS 2.0 - PTW Pending Supervisor Review', '<html><body><p>Permit To Work notification.</p></body></html>', 1),
  (301, 'PTW_SUPERVISOR_APPROVED', 'PTW notification: supervisor approved', 'GEMS 2.0 - PTW Supervisor Approved', '<html><body><p>Permit To Work notification.</p></body></html>', 1),
  (302, 'PTW_SUPERVISOR_REJECTED', 'PTW notification: supervisor rejected', 'GEMS 2.0 - PTW Supervisor Rejected', '<html><body><p>Permit To Work notification.</p></body></html>', 1),
  (303, 'PTW_SHE_APPROVED', 'PTW notification: SHE approved', 'GEMS 2.0 - PTW SHE Approved', '<html><body><p>Permit To Work notification.</p></body></html>', 1),
  (304, 'PTW_SHE_REJECTED', 'PTW notification: SHE rejected', 'GEMS 2.0 - PTW SHE Rejected', '<html><body><p>Permit To Work notification.</p></body></html>', 1),
  (305, 'PTW_FM_APPROVED', 'PTW notification: FM approved', 'GEMS 2.0 - PTW FM Approved', '<html><body><p>Permit To Work notification.</p></body></html>', 1),
  (306, 'PTW_FM_REJECTED', 'PTW notification: FM rejected', 'GEMS 2.0 - PTW FM Rejected', '<html><body><p>Permit To Work notification.</p></body></html>', 1),
  (307, 'PTW_EXTENSION_REQUESTED', 'PTW notification: extension requested', 'GEMS 2.0 - PTW Extension Requested', '<html><body><p>Permit To Work notification.</p></body></html>', 1),
  (308, 'PTW_EXTENDED', 'PTW notification: extension approved', 'GEMS 2.0 - PTW Extended', '<html><body><p>Permit To Work notification.</p></body></html>', 1),
  (309, 'PTW_CANCELLATION_REQUESTED', 'PTW notification: cancellation requested', 'GEMS 2.0 - PTW Cancellation Requested', '<html><body><p>Permit To Work notification.</p></body></html>', 1),
  (310, 'PTW_CANCELLED', 'PTW notification: permit cancelled', 'GEMS 2.0 - PTW Cancelled', '<html><body><p>Permit To Work notification.</p></body></html>', 1),
  (311, 'PTW_SUSPENSION_REQUESTED', 'PTW notification: suspension requested', 'GEMS 2.0 - PTW Suspension Requested', '<html><body><p>Permit To Work notification.</p></body></html>', 1),
  (312, 'PTW_SUSPENDED', 'PTW notification: permit suspended', 'GEMS 2.0 - PTW Suspended', '<html><body><p>Permit To Work notification.</p></body></html>', 1),
  (313, 'PTW_CLOSURE_REQUESTED', 'PTW notification: closure requested', 'GEMS 2.0 - PTW Closure Requested', '<html><body><p>Permit To Work notification.</p></body></html>', 1),
  (314, 'PTW_CLOSED', 'PTW notification: permit closed', 'GEMS 2.0 - PTW Closed', '<html><body><p>Permit To Work notification.</p></body></html>', 1),
  (320, 'SPACE_RESERVATION_CREATED', 'Space Management notification: reservation created', 'GEMS 2.0 - Space Reservation Confirmed: [space_name]', '<html><body><p>Hi [fullName],</p><p>Your space reservation has been confirmed.</p><table cellpadding="4" cellspacing="0"><tr><td><strong>Space</strong></td><td>[space_name]</td></tr><tr><td><strong>Location</strong></td><td>[location_name]</td></tr><tr><td><strong>Start</strong></td><td>[reservation_start]</td></tr><tr><td><strong>End</strong></td><td>[reservation_end]</td></tr></table><p>An .ics calendar invite is attached when available.</p><p>This is an automated message. Please do not reply.</p></body></html>', 1),
  (321, 'SPACE_RESERVATION_UPDATED', 'Space Management notification: reservation rescheduled', 'GEMS 2.0 - Space Reservation Rescheduled: [space_name]', '<html><body><p>Hi [fullName],</p><p>Your space reservation has been rescheduled.</p><table cellpadding="4" cellspacing="0"><tr><td><strong>Space</strong></td><td>[space_name]</td></tr><tr><td><strong>Location</strong></td><td>[location_name]</td></tr><tr><td><strong>Previous Start</strong></td><td>[old_start]</td></tr><tr><td><strong>Previous End</strong></td><td>[old_end]</td></tr><tr><td><strong>New Start</strong></td><td>[reservation_start]</td></tr><tr><td><strong>New End</strong></td><td>[reservation_end]</td></tr></table><p>An updated .ics calendar invite is attached when available.</p><p>This is an automated message. Please do not reply.</p></body></html>', 1),
  (322, 'SPACE_RESERVATION_CANCELED', 'Space Management notification: reservation canceled', 'GEMS 2.0 - Space Reservation Canceled: [space_name]', '<html><body><p>Hi [fullName],</p><p>Your space reservation has been canceled.</p><table cellpadding="4" cellspacing="0"><tr><td><strong>Space</strong></td><td>[space_name]</td></tr><tr><td><strong>Location</strong></td><td>[location_name]</td></tr><tr><td><strong>Start</strong></td><td>[reservation_start]</td></tr><tr><td><strong>End</strong></td><td>[reservation_end]</td></tr><tr><td><strong>Reason</strong></td><td>[cancel_reason]</td></tr></table><p>A cancellation .ics notice is attached when available.</p><p>This is an automated message. Please do not reply.</p></body></html>', 1)
ON DUPLICATE KEY UPDATE
  `email_template_name` = VALUES(`email_template_name`),
  `email_template_desc` = VALUES(`email_template_desc`),
  `email_template_title` = VALUES(`email_template_title`),
  `email_template_html` = VALUES(`email_template_html`),
  `email_template_status` = VALUES(`email_template_status`);

DELETE FROM `email_parameter`
WHERE `email_template_id` IN (320, 321, 322);

INSERT INTO `email_parameter` (`email_template_id`, `email_param_code`, `email_param_desc`) VALUES
  (320, 'space_name', 'Reserved space name'),
  (320, 'location_name', 'Reserved space location'),
  (320, 'reservation_start', 'Reservation start date and time'),
  (320, 'reservation_end', 'Reservation end date and time'),
  (321, 'space_name', 'Reserved space name'),
  (321, 'location_name', 'Reserved space location'),
  (321, 'old_start', 'Previous reservation start date and time'),
  (321, 'old_end', 'Previous reservation end date and time'),
  (321, 'reservation_start', 'New reservation start date and time'),
  (321, 'reservation_end', 'New reservation end date and time'),
  (322, 'space_name', 'Reserved space name'),
  (322, 'location_name', 'Reserved space location'),
  (322, 'reservation_start', 'Reservation start date and time'),
  (322, 'reservation_end', 'Reservation end date and time'),
  (322, 'cancel_reason', 'Reservation cancellation reason');

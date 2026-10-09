-- Five years of dashboard sample data for Demo Client only.
--
-- Covers work orders, preventive maintenance, assets, permits, waste and
-- licenses from October 2021 through today. Four extra premises are added
-- under Demo Client so site comparisons have more than one column.
-- Existing operational rows are left in place.
--
-- Marker: D5Y. Safe to run again; the marked rows are removed and rebuilt.

SET NAMES utf8mb4;

SET @client_id := (
  SELECT client_id FROM cli_client WHERE client_name = 'Demo Client' ORDER BY client_id LIMIT 1
);
SET @group_id := (
  SELECT group_id FROM cli_site
  WHERE client_id = @client_id AND site_code = 'DEMO'
  ORDER BY site_id LIMIT 1
);

INSERT INTO cli_site (site_name, site_code, site_desc, client_id, group_id, site_status, site_is_launched)
SELECT seed.site_name, seed.site_code, 'D5Y dashboard sample', @client_id, @group_id, 1, 1
FROM (
  SELECT 'Demo North Block' AS site_name, 'DEMON' AS site_code
  UNION ALL SELECT 'Demo South Block', 'DEMOS'
  UNION ALL SELECT 'Demo Plant Room', 'DEMOP'
  UNION ALL SELECT 'Demo Community Wing', 'DEMOC'
) seed
WHERE @client_id IS NOT NULL
  AND @group_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM cli_site existing WHERE existing.site_code = seed.site_code);

INSERT INTO cli_contract (contract_name, contract_date_start, contract_date_end, site_id, contract_status)
SELECT CONCAT('D5Y ', s.site_name), '2021-01-01', '2027-12-31', s.site_id, 1
FROM cli_site s
WHERE s.client_id = @client_id
  AND s.site_code IN ('DEMON', 'DEMOS', 'DEMOP', 'DEMOC')
  AND NOT EXISTS (
    SELECT 1 FROM cli_contract c
    WHERE c.site_id = s.site_id AND c.contract_name LIKE 'D5Y %'
  );

DELETE task
FROM ppm_task task
INNER JOIN ppm parent ON parent.ppm_id = task.ppm_id
WHERE parent.ppm_task_no LIKE 'D5Y-%';

DELETE FROM ppm WHERE ppm_task_no LIKE 'D5Y-%';
DELETE FROM wo_task WHERE wo_task_no LIKE 'D5Y-%';
DELETE FROM wfl_transaction WHERE transaction_no LIKE 'D5Y-%';
DELETE FROM ptw_permit WHERE ptw_permit_number LIKE 'D5Y-%';
DELETE FROM lic_license WHERE license_title LIKE 'D5Y %';
DELETE FROM wst_transaction WHERE txn_ref LIKE 'D5Y-%';
DELETE FROM ast_asset WHERE asset_no LIKE 'D5Y-%';

DROP TEMPORARY TABLE IF EXISTS tmp_d5y_site;
CREATE TEMPORARY TABLE tmp_d5y_site (
  site_id SMALLINT NOT NULL PRIMARY KEY,
  contract_id SMALLINT NOT NULL,
  site_code VARCHAR(5) NOT NULL,
  persona TINYINT NOT NULL
);

INSERT INTO tmp_d5y_site (site_id, contract_id, site_code, persona)
SELECT s.site_id, MIN(c.contract_id), s.site_code,
       CASE s.site_code
         WHEN 'DEMO' THEN 0
         WHEN 'DEMON' THEN 1
         WHEN 'DEMOS' THEN 2
         WHEN 'DEMOP' THEN 3
         ELSE 4
       END
FROM cli_site s
INNER JOIN cli_contract c ON c.site_id = s.site_id AND c.contract_status = 1
WHERE s.client_id = @client_id
  AND s.site_status = 1
  AND s.site_code IN ('DEMO', 'DEMON', 'DEMOS', 'DEMOP', 'DEMOC')
GROUP BY s.site_id, s.site_code;

DROP TEMPORARY TABLE IF EXISTS tmp_d5y_event;
CREATE TEMPORARY TABLE tmp_d5y_event (
  site_id SMALLINT NOT NULL,
  contract_id SMALLINT NOT NULL,
  persona TINYINT NOT NULL,
  event_date DATE NOT NULL,
  slot TINYINT NOT NULL,
  PRIMARY KEY (site_id, event_date, slot)
);

INSERT INTO tmp_d5y_event (site_id, contract_id, persona, event_date, slot)
WITH RECURSIVE months AS (
  SELECT DATE('2021-10-01') AS month_start
  UNION ALL
  SELECT DATE_ADD(month_start, INTERVAL 1 MONTH)
  FROM months
  WHERE month_start < DATE_FORMAT(CURDATE(), '%Y-%m-01')
)
SELECT site.site_id, site.contract_id, site.persona,
       DATE_ADD(months.month_start, INTERVAL days.slot - 1 DAY), days.slot
FROM months
CROSS JOIN (
  SELECT 1 AS slot UNION ALL SELECT 4 UNION ALL SELECT 8 UNION ALL SELECT 12
  UNION ALL SELECT 16 UNION ALL SELECT 21 UNION ALL SELECT 26
) days
CROSS JOIN tmp_d5y_site site
WHERE DATE_ADD(months.month_start, INTERVAL days.slot - 1 DAY) <= CURDATE()
  AND (site.site_code <> 'DEMOC' OR days.slot IN (1, 8, 16, 26));

INSERT INTO ppm (contract_id, ppm_status, ppm_task_no, ppm_created_by)
SELECT site.contract_id, 1, CONCAT('D5Y-PPM-', site.site_id), 1
FROM tmp_d5y_site site;

INSERT INTO ppm_task (
  ppm_id, transaction_id, ppm_task_no, ppm_task_status,
  ppm_task_start_date, ppm_task_schedule_date, ppm_task_time_serviced
)
SELECT parent.ppm_id, 0,
       CONCAT('D5Y-', event.site_id, '-', DATE_FORMAT(event.event_date, '%y%m%d'), '-', event.slot),
       CASE MOD(event.slot + event.persona, 8)
         WHEN 0 THEN 16
         WHEN 1 THEN 16
         WHEN 2 THEN 16
         WHEN 3 THEN 15
         WHEN 4 THEN 14
         WHEN 5 THEN 13
         WHEN 6 THEN 12
         ELSE 21
       END,
       event.event_date,
       event.event_date,
       CASE
         WHEN MOD(event.slot + event.persona, 4) = 0 THEN DATE_ADD(event.event_date, INTERVAL 4 DAY)
         ELSE event.event_date
       END
FROM tmp_d5y_event event
INNER JOIN ppm parent ON parent.ppm_task_no = CONCAT('D5Y-PPM-', event.site_id);

INSERT INTO wfl_transaction (transaction_no, flow_id, user_id, group_id, transaction_status)
SELECT CONCAT('D5Y-', event.site_id, '-', DATE_FORMAT(event.event_date, '%y%m%d'), '-', event.slot),
       2, 1, 1,
       CASE MOD(event.slot + event.persona, 10)
         WHEN 0 THEN 16
         WHEN 1 THEN 16
         WHEN 2 THEN 16
         WHEN 3 THEN 16
         WHEN 4 THEN 15
         WHEN 5 THEN 13
         WHEN 6 THEN 21
         WHEN 7 THEN 24
         WHEN 8 THEN 25
         ELSE 14
       END
FROM tmp_d5y_event event;

INSERT INTO wo_task (
  wo_task_no, wo_task_type, wo_task_type_init, site_id, transaction_id,
  wo_task_status, wo_task_complaint, wo_task_location, wo_task_created_by, wo_task_time_created
)
SELECT txn.transaction_no,
       MOD(txn.slot_no + site.persona, 6) + 1,
       MOD(txn.slot_no + site.persona, 6) + 1,
       site.site_id,
       txn.transaction_id,
       txn.transaction_status,
       CONCAT('D5Y dashboard sample, ', DATE_FORMAT(txn.event_date, '%d %b %Y')),
       CASE site.persona
         WHEN 1 THEN 'North plant room'
         WHEN 2 THEN 'South lobby'
         WHEN 3 THEN 'Chiller yard'
         WHEN 4 THEN 'Community hall'
         ELSE 'Main block'
       END,
       1,
       TIMESTAMP(txn.event_date, '09:15:00')
FROM (
  SELECT transaction_id, transaction_no, transaction_status,
         CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(transaction_no, '-', 2), '-', -1) AS UNSIGNED) AS site_id,
         CAST(SUBSTRING_INDEX(transaction_no, '-', -1) AS UNSIGNED) AS slot_no,
         STR_TO_DATE(SUBSTRING_INDEX(SUBSTRING_INDEX(transaction_no, '-', 3), '-', -1), '%y%m%d') AS event_date
  FROM wfl_transaction
  WHERE transaction_no LIKE 'D5Y-%'
) txn
INNER JOIN tmp_d5y_site site ON site.site_id = txn.site_id;

INSERT INTO ast_asset (asset_no, asset_name, contract_id, asset_status, asset_registered_by, asset_time_registered)
WITH RECURSIVE seq AS (
  SELECT 1 AS n
  UNION ALL
  SELECT n + 1 FROM seq WHERE n < 48
)
SELECT CONCAT('D5Y-', site.site_code, '-', LPAD(seq.n, 2, '0')),
       ELT(MOD(seq.n, 6) + 1, 'Distribution board', 'Air handling unit', 'Pump set', 'Fire panel', 'Chiller', 'Lift motor'),
       site.contract_id, 1, 1, '2021-10-01 08:00:00'
FROM seq
INNER JOIN tmp_d5y_site site ON site.site_code <> 'DEMO'
WHERE seq.n <= CASE site.site_code
  WHEN 'DEMON' THEN 36
  WHEN 'DEMOS' THEN 24
  WHEN 'DEMOP' THEN 48
  ELSE 15
END;

INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id,
  qty, unit, qty_kg, txn_status, collection_status, remarks, txn_created_by
)
SELECT CONCAT('D5Y-P-', event.site_id, '-', DATE_FORMAT(event.event_date, '%y%m%d'), '-', event.slot),
       CONCAT('D5Y-P-', event.site_id, '-', DATE_FORMAT(event.event_date, '%y%m%d'), '-', event.slot),
       event.site_id, 'P', event.event_date,
       ELT(MOD(event.slot, 6) + 1, 22, 23, 28, 29, 39, 44),
       ROUND(18 + event.persona * 7 + MOD(MONTH(event.event_date) + event.slot, 6) * 4, 3),
       'KG',
       ROUND(18 + event.persona * 7 + MOD(MONTH(event.event_date) + event.slot, 6) * 4, 3),
       'FINAL',
       CASE WHEN event.event_date >= DATE_SUB(CURDATE(), INTERVAL 21 DAY) THEN 'PENDING' ELSE NULL END,
       'D5Y dashboard sample', 1
FROM tmp_d5y_event event
WHERE event.slot IN (1, 8, 16, 26);

INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id,
  qty, unit, qty_kg, txn_status, remarks, txn_created_by
)
SELECT CONCAT('D5Y-D-', event.site_id, '-', DATE_FORMAT(event.event_date, '%y%m%d'), '-', event.slot),
       CONCAT('D5Y-D-', event.site_id, '-', DATE_FORMAT(event.event_date, '%y%m%d'), '-', event.slot),
       event.site_id, 'D',
       LEAST(DATE_ADD(event.event_date, INTERVAL 3 DAY), CURDATE()),
       ELT(MOD(event.slot, 6) + 1, 22, 23, 28, 29, 39, 44),
       ROUND((18 + event.persona * 7 + MOD(MONTH(event.event_date) + event.slot, 6) * 4) * 0.8, 3),
       'KG',
       ROUND((18 + event.persona * 7 + MOD(MONTH(event.event_date) + event.slot, 6) * 4) * 0.8, 3),
       'FINAL', 'D5Y dashboard sample', 1
FROM tmp_d5y_event event
WHERE event.slot IN (1, 8, 16, 26)
  AND event.event_date < DATE_SUB(CURDATE(), INTERVAL 21 DAY);

INSERT INTO ptw_permit (
  ptw_permit_number, ptw_permit_description, ptw_work_area, ptw_work_type, ptw_risk_level,
  ptw_valid_from, ptw_valid_to, ptw_status, ptw_applicant_name, site_id, created_by, ptw_remarks
)
SELECT CONCAT('D5Y-', site.site_code, '-', permit.code),
       permit.description, permit.work_area, permit.work_type, permit.risk_level,
       permit.valid_from, permit.valid_to, permit.ptw_status,
       'Demo Supervisor', site.site_id, 1, 'D5Y dashboard sample'
FROM tmp_d5y_site site
CROSS JOIN (
  SELECT 'ACT' AS code, 'Live electrical isolation' AS description, 'Main switch room' AS work_area,
         'Cold Work' AS work_type, 'MEDIUM' AS risk_level,
         DATE_SUB(NOW(), INTERVAL 1 DAY) AS valid_from, DATE_ADD(NOW(), INTERVAL 20 DAY) AS valid_to,
         'ACTIVE' AS ptw_status
  UNION ALL SELECT 'EXP', 'Hot work ending this week', 'Roof plant', 'Hot Work', 'CRITICAL',
         DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL 3 DAY), 'ACTIVE'
  UNION ALL SELECT 'SUP', 'Awaiting supervisor approval', 'Loading bay', 'Cold Work', 'LOW',
         NOW(), DATE_ADD(NOW(), INTERVAL 5 DAY), 'PENDING_SUPERVISOR'
  UNION ALL SELECT 'SHE', 'Awaiting SHE approval', 'Basement tank', 'Confined Space', 'HIGH',
         NOW(), DATE_ADD(NOW(), INTERVAL 4 DAY), 'PENDING_SHE'
  UNION ALL SELECT 'FM', 'Awaiting facilities approval', 'Lift shaft', 'Cold Work', 'MEDIUM',
         NOW(), DATE_ADD(NOW(), INTERVAL 6 DAY), 'PENDING_FM'
  UNION ALL SELECT 'APP', 'Approved and waiting to start', 'Generator yard', 'Hot Work', 'MEDIUM',
         DATE_ADD(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 4 DAY), 'APPROVED'
  UNION ALL SELECT 'DRF', 'Draft permit', 'Workshop', 'Cold Work', 'LOW',
         DATE_ADD(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL 5 DAY), 'DRAFT'
  UNION ALL SELECT 'CAN', 'Cancelled lift permit', 'Lift lobby', 'Cold Work', 'LOW',
         DATE_SUB(NOW(), INTERVAL 40 DAY), DATE_SUB(NOW(), INTERVAL 38 DAY), 'CANCELLED'
  UNION ALL SELECT 'REJ', 'Rejected confined-space permit', 'Water tank', 'Confined Space', 'HIGH',
         DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(NOW(), INTERVAL 14 DAY), 'REJECTED'
  UNION ALL SELECT 'C22', 'Completed permit 2022', 'Chiller yard', 'Hot Work', 'MEDIUM',
         '2022-06-01', '2022-06-02', 'COMPLETED'
  UNION ALL SELECT 'C23', 'Completed permit 2023', 'Roof', 'Hot Work', 'HIGH',
         '2023-08-14', '2023-08-15', 'COMPLETED'
  UNION ALL SELECT 'C24', 'Completed permit 2024', 'Plant room', 'Cold Work', 'LOW',
         '2024-03-11', '2024-03-12', 'COMPLETED'
  UNION ALL SELECT 'C25', 'Completed permit 2025', 'North riser', 'Cold Work', 'MEDIUM',
         '2025-11-03', '2025-11-04', 'COMPLETED'
) permit;

INSERT INTO lic_license (
  site_id, license_title, license_start_date, license_end_date, license_status, license_created_by
)
SELECT site.site_id,
       CONCAT('D5Y ', license.title),
       DATE_SUB(license.end_date, INTERVAL 1 YEAR),
       license.end_date,
       1, 1
FROM tmp_d5y_site site
CROSS JOIN (
  SELECT 'Fire certificate' AS title, DATE_SUB(CURDATE(), INTERVAL 420 DAY) AS end_date
  UNION ALL SELECT 'Lift permit to operate', DATE_SUB(CURDATE(), INTERVAL 35 DAY)
  UNION ALL SELECT 'Boiler certificate', DATE_ADD(CURDATE(), INTERVAL 12 DAY)
  UNION ALL SELECT 'Generator permit', DATE_ADD(CURDATE(), INTERVAL 55 DAY)
  UNION ALL SELECT 'Trade licence', DATE_ADD(CURDATE(), INTERVAL 200 DAY)
  UNION ALL SELECT 'Environmental licence', DATE_ADD(CURDATE(), INTERVAL 420 DAY)
) license;

DROP TEMPORARY TABLE IF EXISTS tmp_d5y_event;
DROP TEMPORARY TABLE IF EXISTS tmp_d5y_site;

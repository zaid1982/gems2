-- Sample waste data for Demo Client (site_id=19, DEMO)
-- About 100 produce/dispose records across 10 SW codes. Official totals use FINAL only.
-- Safe to re-run: removes previous demo-sample-* rows, then re-inserts.
SET NAMES utf8mb4;

SET @site_id = 19;
SET @user_id = 1;

INSERT INTO wst_premise (site_id, premise_address, premise_contact_no, cutover_date, evidence_required_produced, evidence_required_disposed, premise_status, premise_created_by)
VALUES (@site_id, 'Level 1, Demo Complex, Jalan Demo, 50450 Kuala Lumpur', '03-1234 5678', '2026-01-01', 0, 0, 1, @user_id)
ON DUPLICATE KEY UPDATE premise_address=VALUES(premise_address), premise_contact_no=VALUES(premise_contact_no), cutover_date=VALUES(cutover_date), premise_status=1;

INSERT INTO wst_location (site_id, location_name, location_type, location_status, location_created_by)
SELECT @site_id, 'Scheduled Waste Store A', 'STORAGE', 1, @user_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wst_location l WHERE l.site_id=@site_id AND l.location_name='Scheduled Waste Store A');

INSERT INTO wst_location (site_id, location_name, location_type, location_status, location_created_by)
SELECT @site_id, 'Scheduled Waste Store B', 'STORAGE', 1, @user_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wst_location l WHERE l.site_id=@site_id AND l.location_name='Scheduled Waste Store B');

INSERT INTO wst_location (site_id, location_name, location_type, location_status, location_created_by)
SELECT @site_id, 'Workshop collection point', 'STORAGE', 1, @user_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wst_location l WHERE l.site_id=@site_id AND l.location_name='Workshop collection point');

INSERT INTO wst_location (site_id, location_name, location_type, location_status, location_created_by)
SELECT @site_id, 'Loading bay / outgoing', 'DESTINATION', 1, @user_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wst_location l WHERE l.site_id=@site_id AND l.location_name='Loading bay / outgoing');

INSERT INTO wst_waste_profile (site_id, sw_code_id, profile_alias, profile_status, profile_created_by)
SELECT @site_id, 22, 'Used engine / lubricating oil', 1, @user_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wst_waste_profile p WHERE p.site_id=@site_id AND p.sw_code_id=22);

INSERT INTO wst_opening_balance (site_id, sw_code_id, as_at_date, qty, unit, qty_kg, remarks, opening_status, opening_created_by)
SELECT @site_id, 22, '2026-01-01', 2.400, 'MT', 2400.000, 'Cutover stock for demo dashboard', 1, @user_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wst_opening_balance o WHERE o.site_id=@site_id AND o.sw_code_id=22);

INSERT INTO wst_waste_profile (site_id, sw_code_id, profile_alias, profile_status, profile_created_by)
SELECT @site_id, 23, 'Spent hydraulic oil', 1, @user_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wst_waste_profile p WHERE p.site_id=@site_id AND p.sw_code_id=23);

INSERT INTO wst_opening_balance (site_id, sw_code_id, as_at_date, qty, unit, qty_kg, remarks, opening_status, opening_created_by)
SELECT @site_id, 23, '2026-01-01', 1.200, 'MT', 1200.000, 'Cutover stock for demo dashboard', 1, @user_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wst_opening_balance o WHERE o.site_id=@site_id AND o.sw_code_id=23);

INSERT INTO wst_waste_profile (site_id, sw_code_id, profile_alias, profile_status, profile_created_by)
SELECT @site_id, 28, 'Oily sludge from interceptors', 1, @user_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wst_waste_profile p WHERE p.site_id=@site_id AND p.sw_code_id=28);

INSERT INTO wst_opening_balance (site_id, sw_code_id, as_at_date, qty, unit, qty_kg, remarks, opening_status, opening_created_by)
SELECT @site_id, 28, '2026-01-01', 1.600, 'MT', 1600.000, 'Cutover stock for demo dashboard', 1, @user_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wst_opening_balance o WHERE o.site_id=@site_id AND o.sw_code_id=28);

INSERT INTO wst_waste_profile (site_id, sw_code_id, profile_alias, profile_status, profile_created_by)
SELECT @site_id, 29, 'Workshop oily residue', 1, @user_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wst_waste_profile p WHERE p.site_id=@site_id AND p.sw_code_id=29);

INSERT INTO wst_opening_balance (site_id, sw_code_id, as_at_date, qty, unit, qty_kg, remarks, opening_status, opening_created_by)
SELECT @site_id, 29, '2026-01-01', 0.900, 'MT', 900.000, 'Cutover stock for demo dashboard', 1, @user_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wst_opening_balance o WHERE o.site_id=@site_id AND o.sw_code_id=29);

INSERT INTO wst_waste_profile (site_id, sw_code_id, profile_alias, profile_status, profile_created_by)
SELECT @site_id, 39, 'Spent organic solvents', 1, @user_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wst_waste_profile p WHERE p.site_id=@site_id AND p.sw_code_id=39);

INSERT INTO wst_opening_balance (site_id, sw_code_id, as_at_date, qty, unit, qty_kg, remarks, opening_status, opening_created_by)
SELECT @site_id, 39, '2026-01-01', 0.700, 'MT', 700.000, 'Cutover stock for demo dashboard', 1, @user_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wst_opening_balance o WHERE o.site_id=@site_id AND o.sw_code_id=39);

INSERT INTO wst_waste_profile (site_id, sw_code_id, profile_alias, profile_status, profile_created_by)
SELECT @site_id, 44, 'Spent thermal / chiller fluid', 1, @user_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wst_waste_profile p WHERE p.site_id=@site_id AND p.sw_code_id=44);

INSERT INTO wst_opening_balance (site_id, sw_code_id, as_at_date, qty, unit, qty_kg, remarks, opening_status, opening_created_by)
SELECT @site_id, 44, '2026-01-01', 0.500, 'MT', 500.000, 'Cutover stock for demo dashboard', 1, @user_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wst_opening_balance o WHERE o.site_id=@site_id AND o.sw_code_id=44);

INSERT INTO wst_waste_profile (site_id, sw_code_id, profile_alias, profile_status, profile_created_by)
SELECT @site_id, 53, 'Contaminated drums and containers', 1, @user_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wst_waste_profile p WHERE p.site_id=@site_id AND p.sw_code_id=53);

INSERT INTO wst_opening_balance (site_id, sw_code_id, as_at_date, qty, unit, qty_kg, remarks, opening_status, opening_created_by)
SELECT @site_id, 53, '2026-01-01', 0.800, 'MT', 800.000, 'Cutover stock for demo dashboard', 1, @user_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wst_opening_balance o WHERE o.site_id=@site_id AND o.sw_code_id=53);

INSERT INTO wst_waste_profile (site_id, sw_code_id, profile_alias, profile_status, profile_created_by)
SELECT @site_id, 54, 'Oily rags and filters', 1, @user_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wst_waste_profile p WHERE p.site_id=@site_id AND p.sw_code_id=54);

INSERT INTO wst_opening_balance (site_id, sw_code_id, as_at_date, qty, unit, qty_kg, remarks, opening_status, opening_created_by)
SELECT @site_id, 54, '2026-01-01', 1.100, 'MT', 1100.000, 'Cutover stock for demo dashboard', 1, @user_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wst_opening_balance o WHERE o.site_id=@site_id AND o.sw_code_id=54);

INSERT INTO wst_waste_profile (site_id, sw_code_id, profile_alias, profile_status, profile_created_by)
SELECT @site_id, 61, 'Waste paint and varnish', 1, @user_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wst_waste_profile p WHERE p.site_id=@site_id AND p.sw_code_id=61);

INSERT INTO wst_opening_balance (site_id, sw_code_id, as_at_date, qty, unit, qty_kg, remarks, opening_status, opening_created_by)
SELECT @site_id, 61, '2026-01-01', 0.400, 'MT', 400.000, 'Cutover stock for demo dashboard', 1, @user_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wst_opening_balance o WHERE o.site_id=@site_id AND o.sw_code_id=61);

INSERT INTO wst_waste_profile (site_id, sw_code_id, profile_alias, profile_status, profile_created_by)
SELECT @site_id, 10, 'E-waste / electrical assemblies', 1, @user_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wst_waste_profile p WHERE p.site_id=@site_id AND p.sw_code_id=10);

INSERT INTO wst_opening_balance (site_id, sw_code_id, as_at_date, qty, unit, qty_kg, remarks, opening_status, opening_created_by)
SELECT @site_id, 10, '2026-01-01', 0.350, 'MT', 350.000, 'Cutover stock for demo dashboard', 1, @user_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wst_opening_balance o WHERE o.site_id=@site_id AND o.sw_code_id=10);

DELETE FROM wst_history WHERE entity_type='TRANSACTION' AND entity_id IN (SELECT txn_id FROM wst_transaction WHERE client_ref LIKE 'demo-sample-%');
DELETE FROM wst_transaction_document WHERE txn_id IN (SELECT txn_id FROM wst_transaction WHERE client_ref LIKE 'demo-sample-%');
DELETE FROM wst_transaction WHERE client_ref LIKE 'demo-sample-%';

INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00002', 'demo-sample-00002', @site_id, 'P', '2026-01-08', 22, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=22 LIMIT 1),
  'Facility operations', 'WO-DEM-01-100', 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Workshop collection point' LIMIT 1), 'Painting works waste',
  0.080, 'MT', 80.000, 1, 4,
  'FINAL', 1, '2026-01-08 08:45:00', @user_id, '2026-01-08 08:45:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00003', 'demo-sample-00003', @site_id, 'P', '2026-01-08', 23, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=23 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store B' LIMIT 1), 'Spill clean-up residue',
  0.200, 'MT', 200.000, 2, 1,
  'FINAL', 1, '2026-01-08 11:00:00', @user_id, '2026-01-08 11:00:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00004', 'demo-sample-00004', @site_id, 'D', '2026-01-10', 28, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=28 LIMIT 1),
  'Facility operations', NULL, 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'Fifth Schedule consignment',
  0.117, 'MT', 117.000, 1, 8,
  'FINAL', 1, '2026-01-10 11:00:00', @user_id, '2026-01-10 11:00:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00005', 'demo-sample-00005', @site_id, 'D', '2026-01-10', 29, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=29 LIMIT 1),
  'Facility operations', NULL, 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'Monthly outgoing shipment',
  0.138, 'MT', 138.000, 2, 1,
  'FINAL', 1, '2026-01-10 11:00:00', @user_id, '2026-01-10 11:00:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00006', 'demo-sample-00006', @site_id, 'D', '2026-01-13', 39, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=39 LIMIT 1),
  'Facility operations', NULL, 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'Disposed via approved receiver',
  0.129, 'MT', 129.000, 1, 1,
  'FINAL', 1, '2026-01-13 13:30:00', @user_id, '2026-01-13 13:30:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00007', 'demo-sample-00007', @site_id, 'P', '2026-01-16', 44, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=44 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store B' LIMIT 1), 'Chiller maintenance leftover',
  0.150, 'MT', 150.000, 2, 1,
  'FINAL', 1, '2026-01-16 12:15:00', @user_id, '2026-01-16 12:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00008', 'demo-sample-00008', @site_id, 'P', '2026-01-20', 53, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=53 LIMIT 1),
  'Facility operations', NULL, 9, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Workshop collection point' LIMIT 1), 'Contractor return of used oil',
  0.120, 'MT', 120.000, 4, 8,
  'FINAL', 1, '2026-01-20 15:45:00', @user_id, '2026-01-20 15:45:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00009', 'demo-sample-00009', @site_id, 'P', '2026-01-23', 54, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=54 LIMIT 1),
  'Facility operations', NULL, 9, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Workshop collection point' LIMIT 1), 'Workshop housekeeping',
  0.120, 'MT', 120.000, 1, 1,
  'FINAL', 1, '2026-01-23 15:15:00', @user_id, '2026-01-23 15:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00010', 'demo-sample-00010', @site_id, 'P', '2026-01-23', 61, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=61 LIMIT 1),
  'Facility operations', 'WO-DEM-01-108', 9, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Workshop collection point' LIMIT 1), 'Painting works waste',
  0.150, 'MT', 150.000, 2, 1,
  'FINAL', 1, '2026-01-23 09:15:00', @user_id, '2026-01-23 09:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00011', 'demo-sample-00011', @site_id, 'P', '2026-01-27', 10, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=10 LIMIT 1),
  'Facility operations', NULL, 9, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Workshop collection point' LIMIT 1), 'Monthly scheduled waste collection from plant room',
  0.150, 'MT', 150.000, 1, 8,
  'FINAL', 1, '2026-01-27 09:30:00', @user_id, '2026-01-27 09:30:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00012', 'demo-sample-00012', @site_id, 'P', '2026-01-27', 22, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=22 LIMIT 1),
  'Facility operations', 'WO-DEM-01-110', 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Workshop collection point' LIMIT 1), 'Filter replacement',
  0.080, 'MT', 80.000, 1, 12,
  'FINAL', 1, '2026-01-27 08:45:00', @user_id, '2026-01-27 08:45:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00013', 'demo-sample-00013', @site_id, 'P', '2026-01-30', 23, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=23 LIMIT 1),
  'Facility operations', 'WO-DEM-01-111', 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store A' LIMIT 1), 'Chiller maintenance leftover',
  0.050, 'MT', 50.000, 1, 12,
  'FINAL', 1, '2026-01-30 11:45:00', @user_id, '2026-01-30 11:45:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00014', 'demo-sample-00014', @site_id, 'P', '2026-01-30', 28, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=28 LIMIT 1),
  'Facility operations', 'WO-DEM-01-112', 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store A' LIMIT 1), 'Monthly scheduled waste collection from plant room',
  0.350, 'MT', 350.000, 2, 1,
  'FINAL', 1, '2026-01-30 13:15:00', @user_id, '2026-01-30 13:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00015', 'demo-sample-00015', @site_id, 'P', '2026-02-02', 39, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=39 LIMIT 1),
  'Facility operations', 'WO-DEM-02-113', 9, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store A' LIMIT 1), 'Monthly scheduled waste collection from plant room',
  0.100, 'MT', 100.000, 3, 1,
  'FINAL', 1, '2026-02-02 11:30:00', @user_id, '2026-02-02 11:30:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00016', 'demo-sample-00016', @site_id, 'D', '2026-02-02', 29, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=29 LIMIT 1),
  'Facility operations', NULL, 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'Disposed via approved receiver',
  0.195, 'MT', 195.000, 4, 2,
  'FINAL', 1, '2026-02-02 13:30:00', @user_id, '2026-02-02 13:30:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00017', 'demo-sample-00017', @site_id, 'D', '2026-02-05', 44, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=44 LIMIT 1),
  'Facility operations', 'WO-DEM-02-115', 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'Fifth Schedule consignment',
  0.133, 'MT', 133.000, 1, 12,
  'FINAL', 1, '2026-02-05 13:45:00', @user_id, '2026-02-05 13:45:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00018', 'demo-sample-00018', @site_id, 'D', '2026-02-05', 53, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=53 LIMIT 1),
  'Facility operations', 'WO-DEM-02-116', 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'Disposed via approved receiver',
  0.201, 'MT', 201.000, 1, 6,
  'FINAL', 1, '2026-02-05 10:45:00', @user_id, '2026-02-05 10:45:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00019', 'demo-sample-00019', @site_id, 'P', '2026-02-08', 54, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=54 LIMIT 1),
  'Facility operations', NULL, 9, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store B' LIMIT 1), 'Painting works waste',
  0.250, 'MT', 250.000, 4, 8,
  'FINAL', 1, '2026-02-08 13:00:00', @user_id, '2026-02-08 13:00:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00020', 'demo-sample-00020', @site_id, 'P', '2026-02-08', 61, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=61 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store B' LIMIT 1), 'Workshop housekeeping',
  0.256, 'MT', 256.000, 2, 1,
  'FINAL', 1, '2026-02-08 15:15:00', @user_id, '2026-02-08 15:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00021', 'demo-sample-00021', @site_id, 'D', '2026-02-09', 10, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=10 LIMIT 1),
  'Facility operations', NULL, 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'Collected by licensed scheduled waste contractor',
  0.135, 'MT', 135.000, 2, 2,
  'FINAL', 1, '2026-02-09 08:15:00', @user_id, '2026-02-09 08:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00022', 'demo-sample-00022', @site_id, 'P', '2026-02-13', 22, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=22 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Workshop collection point' LIMIT 1), 'Workshop housekeeping',
  0.080, 'MT', 80.000, 1, 12,
  'FINAL', 1, '2026-02-13 13:15:00', @user_id, '2026-02-13 13:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00023', 'demo-sample-00023', @site_id, 'P', '2026-02-15', 23, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=23 LIMIT 1),
  'Facility operations', 'WO-DEM-02-121', 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store B' LIMIT 1), 'Painting works waste',
  0.050, 'MT', 50.000, 1, 1,
  'FINAL', 1, '2026-02-15 14:00:00', @user_id, '2026-02-15 14:00:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00024', 'demo-sample-00024', @site_id, 'P', '2026-02-15', 28, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=28 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Workshop collection point' LIMIT 1), 'Spill clean-up residue',
  0.120, 'MT', 120.000, 4, 8,
  'FINAL', 1, '2026-02-15 11:00:00', @user_id, '2026-02-15 11:00:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00025', 'demo-sample-00025', @site_id, 'P', '2026-02-20', 39, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=39 LIMIT 1),
  'Facility operations', 'WO-DEM-02-123', 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store B' LIMIT 1), 'Contractor return of used oil',
  0.350, 'MT', 350.000, 2, 2,
  'FINAL', 1, '2026-02-20 14:45:00', @user_id, '2026-02-20 14:45:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00026', 'demo-sample-00026', @site_id, 'D', '2026-02-20', 29, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=29 LIMIT 1),
  'Facility operations', NULL, 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'Fifth Schedule consignment',
  0.151, 'MT', 151.000, 2, 1,
  'FINAL', 1, '2026-02-20 13:15:00', @user_id, '2026-02-20 13:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00027', 'demo-sample-00027', @site_id, 'P', '2026-02-22', 44, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=44 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store B' LIMIT 1), 'Painting works waste',
  0.120, 'MT', 120.000, 2, 2,
  'FINAL', 1, '2026-02-22 16:15:00', @user_id, '2026-02-22 16:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00028', 'demo-sample-00028', @site_id, 'D', '2026-02-22', 53, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=53 LIMIT 1),
  'Facility operations', NULL, 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'eSWIS consignment collection',
  0.136, 'MT', 136.000, 1, 8,
  'FINAL', 1, '2026-02-22 13:15:00', @user_id, '2026-02-22 13:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00029', 'demo-sample-00029', @site_id, 'D', '2026-02-25', 54, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=54 LIMIT 1),
  'Facility operations', NULL, 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'Collected by licensed scheduled waste contractor',
  0.276, 'MT', 276.000, 4, 2,
  'FINAL', 1, '2026-02-25 16:15:00', @user_id, '2026-02-25 16:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00030', 'demo-sample-00030', @site_id, 'D', '2026-02-28', 61, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=61 LIMIT 1),
  'Facility operations', NULL, 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'Collected by licensed scheduled waste contractor',
  0.189, 'MT', 189.000, 4, 1,
  'FINAL', 1, '2026-02-28 15:15:00', @user_id, '2026-02-28 15:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00031', 'demo-sample-00031', @site_id, 'P', '2026-03-03', 10, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=10 LIMIT 1),
  'Facility operations', 'WO-DEM-03-129', 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Workshop collection point' LIMIT 1), 'Chiller maintenance leftover',
  0.080, 'MT', 80.000, 1, 8,
  'FINAL', 1, '2026-03-03 08:30:00', @user_id, '2026-03-03 08:30:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00032', 'demo-sample-00032', @site_id, 'P', '2026-03-03', 22, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=22 LIMIT 1),
  'Facility operations', 'WO-DEM-03-130', 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store B' LIMIT 1), 'Chiller maintenance leftover',
  0.350, 'MT', 350.000, 1, 2,
  'FINAL', 1, '2026-03-03 12:00:00', @user_id, '2026-03-03 12:00:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00033', 'demo-sample-00033', @site_id, 'D', '2026-03-07', 23, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=23 LIMIT 1),
  'Facility operations', 'WO-DEM-03-131', 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'Collected by licensed scheduled waste contractor',
  0.279, 'MT', 279.000, 2, 2,
  'FINAL', 1, '2026-03-07 16:00:00', @user_id, '2026-03-07 16:00:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00034', 'demo-sample-00034', @site_id, 'P', '2026-03-09', 28, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=28 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store A' LIMIT 1), 'Oil change during PPM generator set',
  0.120, 'MT', 120.000, 4, 8,
  'FINAL', 1, '2026-03-09 12:00:00', @user_id, '2026-03-09 12:00:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00035', 'demo-sample-00035', @site_id, 'P', '2026-03-09', 29, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=29 LIMIT 1),
  'Facility operations', 'WO-DEM-03-133', 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store B' LIMIT 1), 'Contractor return of used oil',
  0.250, 'MT', 250.000, 1, 8,
  'FINAL', 1, '2026-03-09 08:15:00', @user_id, '2026-03-09 08:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00036', 'demo-sample-00036', @site_id, 'D', '2026-03-11', 39, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=39 LIMIT 1),
  'Facility operations', 'WO-DEM-03-134', 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'Fifth Schedule consignment',
  0.287, 'MT', 287.000, 3, 1,
  'FINAL', 1, '2026-03-11 11:15:00', @user_id, '2026-03-11 11:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00037', 'demo-sample-00037', @site_id, 'D', '2026-03-14', 44, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=44 LIMIT 1),
  'Facility operations', NULL, 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'eSWIS consignment collection',
  0.098, 'MT', 98.000, 1, 8,
  'FINAL', 1, '2026-03-14 14:00:00', @user_id, '2026-03-14 14:00:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00038', 'demo-sample-00038', @site_id, 'D', '2026-03-17', 53, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=53 LIMIT 1),
  'Facility operations', NULL, 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'Disposed via approved receiver',
  0.122, 'MT', 122.000, 1, 8,
  'FINAL', 1, '2026-03-17 08:15:00', @user_id, '2026-03-17 08:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00039', 'demo-sample-00039', @site_id, 'D', '2026-03-20', 54, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=54 LIMIT 1),
  'Facility operations', NULL, 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'Disposed via approved receiver',
  0.284, 'MT', 284.000, 1, 1,
  'FINAL', 1, '2026-03-20 09:15:00', @user_id, '2026-03-20 09:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00040', 'demo-sample-00040', @site_id, 'P', '2026-03-24', 61, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=61 LIMIT 1),
  'Facility operations', 'WO-DEM-03-138', 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store A' LIMIT 1), 'Monthly scheduled waste collection from plant room',
  0.080, 'MT', 80.000, 1, 2,
  'FINAL', 1, '2026-03-24 13:15:00', @user_id, '2026-03-24 13:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00041', 'demo-sample-00041', @site_id, 'D', '2026-03-24', 10, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=10 LIMIT 1),
  'Facility operations', 'WO-DEM-03-139', 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'Collected by licensed scheduled waste contractor',
  0.183, 'MT', 183.000, 1, 8,
  'FINAL', 1, '2026-03-24 09:15:00', @user_id, '2026-03-24 09:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00042', 'demo-sample-00042', @site_id, 'P', '2026-03-28', 22, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=22 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store A' LIMIT 1), 'Workshop housekeeping',
  0.066, 'MT', 66.000, 1, 1,
  'FINAL', 1, '2026-03-28 12:15:00', @user_id, '2026-03-28 12:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00043', 'demo-sample-00043', @site_id, 'D', '2026-03-30', 23, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=23 LIMIT 1),
  'Facility operations', NULL, 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'Collected by licensed scheduled waste contractor',
  0.255, 'MT', 255.000, 1, 2,
  'FINAL', 1, '2026-03-30 08:45:00', @user_id, '2026-03-30 08:45:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00044', 'demo-sample-00044', @site_id, 'P', '2026-04-03', 28, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=28 LIMIT 1),
  'Facility operations', 'WO-DEM-04-142', 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store B' LIMIT 1), 'Painting works waste',
  0.250, 'MT', 250.000, 3, 6,
  'FINAL', 1, '2026-04-03 10:15:00', @user_id, '2026-04-03 10:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00045', 'demo-sample-00045', @site_id, 'P', '2026-04-03', 29, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=29 LIMIT 1),
  'Facility operations', NULL, 9, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Workshop collection point' LIMIT 1), 'Contractor return of used oil',
  0.200, 'MT', 200.000, 2, 2,
  'FINAL', 1, '2026-04-03 13:45:00', @user_id, '2026-04-03 13:45:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00046', 'demo-sample-00046', @site_id, 'D', '2026-04-07', 39, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=39 LIMIT 1),
  'Facility operations', NULL, 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'Collected by licensed scheduled waste contractor',
  0.143, 'MT', 143.000, 2, 1,
  'FINAL', 1, '2026-04-07 13:45:00', @user_id, '2026-04-07 13:45:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00047', 'demo-sample-00047', @site_id, 'P', '2026-04-10', 44, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=44 LIMIT 1),
  'Facility operations', NULL, 9, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store A' LIMIT 1), 'Oil change during PPM generator set',
  0.364, 'MT', 364.000, 4, 8,
  'FINAL', 1, '2026-04-10 14:30:00', @user_id, '2026-04-10 14:30:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00048', 'demo-sample-00048', @site_id, 'P', '2026-04-13', 53, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=53 LIMIT 1),
  'Facility operations', NULL, 9, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Workshop collection point' LIMIT 1), 'Contractor return of used oil',
  0.300, 'MT', 300.000, 3, 2,
  'FINAL', 1, '2026-04-13 14:30:00', @user_id, '2026-04-13 14:30:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00049', 'demo-sample-00049', @site_id, 'P', '2026-04-13', 54, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=54 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Workshop collection point' LIMIT 1), 'Contractor return of used oil',
  0.206, 'MT', 206.000, 3, 12,
  'FINAL', 1, '2026-04-13 10:45:00', @user_id, '2026-04-13 10:45:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00050', 'demo-sample-00050', @site_id, 'P', '2026-04-17', 10, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=10 LIMIT 1),
  'Facility operations', 'WO-DEM-04-148', 9, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store B' LIMIT 1), 'Chiller maintenance leftover',
  0.080, 'MT', 80.000, 1, 1,
  'FINAL', 1, '2026-04-17 14:00:00', @user_id, '2026-04-17 14:00:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00051', 'demo-sample-00051', @site_id, 'P', '2026-04-17', 61, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=61 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store B' LIMIT 1), 'Painting works waste',
  0.200, 'MT', 200.000, 1, 2,
  'FINAL', 1, '2026-04-17 13:30:00', @user_id, '2026-04-17 13:30:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00052', 'demo-sample-00052', @site_id, 'P', '2026-04-21', 23, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=23 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store B' LIMIT 1), 'Contractor return of used oil',
  0.200, 'MT', 200.000, 3, 1,
  'FINAL', 1, '2026-04-21 13:30:00', @user_id, '2026-04-21 13:30:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00053', 'demo-sample-00053', @site_id, 'D', '2026-04-21', 22, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=22 LIMIT 1),
  'Facility operations', NULL, 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'Fifth Schedule consignment',
  0.269, 'MT', 269.000, 4, 2,
  'FINAL', 1, '2026-04-21 16:15:00', @user_id, '2026-04-21 16:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00054', 'demo-sample-00054', @site_id, 'P', '2026-04-25', 28, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=28 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store B' LIMIT 1), 'Oil change during PPM generator set',
  0.359, 'MT', 359.000, 1, 6,
  'FINAL', 1, '2026-04-25 11:00:00', @user_id, '2026-04-25 11:00:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00055', 'demo-sample-00055', @site_id, 'P', '2026-04-28', 29, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=29 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store A' LIMIT 1), 'Oil change during PPM generator set',
  0.080, 'MT', 80.000, 1, 8,
  'FINAL', 1, '2026-04-28 11:45:00', @user_id, '2026-04-28 11:45:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00056', 'demo-sample-00056', @site_id, 'P', '2026-04-28', 39, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=39 LIMIT 1),
  'Facility operations', NULL, 9, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Workshop collection point' LIMIT 1), 'Monthly scheduled waste collection from plant room',
  0.120, 'MT', 120.000, 4, 8,
  'FINAL', 1, '2026-04-28 11:15:00', @user_id, '2026-04-28 11:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00057', 'demo-sample-00057', @site_id, 'P', '2026-05-01', 44, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=44 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store B' LIMIT 1), 'Spill clean-up residue',
  0.120, 'MT', 120.000, 3, 2,
  'FINAL', 1, '2026-05-01 14:00:00', @user_id, '2026-05-01 14:00:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00058', 'demo-sample-00058', @site_id, 'P', '2026-05-01', 53, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=53 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store A' LIMIT 1), 'Contractor return of used oil',
  0.061, 'MT', 61.000, 1, 1,
  'FINAL', 1, '2026-05-01 10:45:00', @user_id, '2026-05-01 10:45:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00059', 'demo-sample-00059', @site_id, 'P', '2026-05-04', 54, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=54 LIMIT 1),
  'Facility operations', 'WO-DEM-05-157', 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store A' LIMIT 1), 'Chiller maintenance leftover',
  0.059, 'MT', 59.000, 1, 12,
  'FINAL', 1, '2026-05-04 12:45:00', @user_id, '2026-05-04 12:45:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00060', 'demo-sample-00060', @site_id, 'P', '2026-05-07', 10, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=10 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Workshop collection point' LIMIT 1), 'Spill clean-up residue',
  0.266, 'MT', 266.000, 1, 2,
  'FINAL', 1, '2026-05-07 12:15:00', @user_id, '2026-05-07 12:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00061', 'demo-sample-00061', @site_id, 'D', '2026-05-07', 61, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=61 LIMIT 1),
  'Facility operations', NULL, 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'Monthly outgoing shipment',
  0.109, 'MT', 109.000, 1, 2,
  'FINAL', 1, '2026-05-07 10:15:00', @user_id, '2026-05-07 10:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00062', 'demo-sample-00062', @site_id, 'P', '2026-05-08', 22, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=22 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Workshop collection point' LIMIT 1), 'Monthly scheduled waste collection from plant room',
  0.300, 'MT', 300.000, 1, 8,
  'FINAL', 1, '2026-05-08 12:30:00', @user_id, '2026-05-08 12:30:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00063', 'demo-sample-00063', @site_id, 'P', '2026-05-11', 23, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=23 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Workshop collection point' LIMIT 1), 'Workshop housekeeping',
  0.405, 'MT', 405.000, 4, 12,
  'FINAL', 1, '2026-05-11 09:00:00', @user_id, '2026-05-11 09:00:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00064', 'demo-sample-00064', @site_id, 'P', '2026-05-14', 28, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=28 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Workshop collection point' LIMIT 1), 'Oil change during PPM generator set',
  0.350, 'MT', 350.000, 2, 1,
  'FINAL', 1, '2026-05-14 15:30:00', @user_id, '2026-05-14 15:30:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00065', 'demo-sample-00065', @site_id, 'P', '2026-05-15', 29, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=29 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Workshop collection point' LIMIT 1), 'Filter replacement',
  0.120, 'MT', 120.000, 2, 1,
  'FINAL', 1, '2026-05-15 11:00:00', @user_id, '2026-05-15 11:00:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00066', 'demo-sample-00066', @site_id, 'P', '2026-05-18', 39, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=39 LIMIT 1),
  'Facility operations', 'WO-DEM-05-164', 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store A' LIMIT 1), 'Workshop housekeeping',
  0.350, 'MT', 350.000, 1, 2,
  'FINAL', 1, '2026-05-18 12:45:00', @user_id, '2026-05-18 12:45:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00067', 'demo-sample-00067', @site_id, 'P', '2026-05-21', 44, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=44 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store A' LIMIT 1), 'Filter replacement',
  0.180, 'MT', 180.000, 3, 12,
  'FINAL', 1, '2026-05-21 09:30:00', @user_id, '2026-05-21 09:30:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00068', 'demo-sample-00068', @site_id, 'D', '2026-05-21', 53, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=53 LIMIT 1),
  'Facility operations', NULL, 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'Collected by licensed scheduled waste contractor',
  0.181, 'MT', 181.000, 2, 2,
  'FINAL', 1, '2026-05-21 11:30:00', @user_id, '2026-05-21 11:30:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00069', 'demo-sample-00069', @site_id, 'P', '2026-05-23', 54, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=54 LIMIT 1),
  'Facility operations', 'WO-DEM-05-167', 9, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store B' LIMIT 1), 'Monthly scheduled waste collection from plant room',
  0.080, 'MT', 80.000, 1, 8,
  'FINAL', 1, '2026-05-23 08:00:00', @user_id, '2026-05-23 08:00:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00070', 'demo-sample-00070', @site_id, 'P', '2026-05-26', 61, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=61 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store B' LIMIT 1), 'Filter replacement',
  0.100, 'MT', 100.000, 1, 2,
  'FINAL', 1, '2026-05-26 16:45:00', @user_id, '2026-05-26 16:45:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00071', 'demo-sample-00071', @site_id, 'D', '2026-05-27', 10, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=10 LIMIT 1),
  'Facility operations', 'WO-DEM-05-169', 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'Collected by licensed scheduled waste contractor',
  0.220, 'MT', 220.000, 4, 1,
  'FINAL', 1, '2026-05-27 12:30:00', @user_id, '2026-05-27 12:30:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00072', 'demo-sample-00072', @site_id, 'P', '2026-05-29', 22, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=22 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store A' LIMIT 1), 'Workshop housekeeping',
  0.300, 'MT', 300.000, 1, 12,
  'FINAL', 1, '2026-05-29 16:15:00', @user_id, '2026-05-29 16:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00073', 'demo-sample-00073', @site_id, 'P', '2026-05-31', 23, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=23 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store A' LIMIT 1), 'Workshop housekeeping',
  0.100, 'MT', 100.000, 1, 6,
  'FINAL', 1, '2026-05-31 14:15:00', @user_id, '2026-05-31 14:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00074', 'demo-sample-00074', @site_id, 'P', '2026-06-03', 28, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=28 LIMIT 1),
  'Facility operations', 'WO-DEM-06-172', 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Workshop collection point' LIMIT 1), 'Monthly scheduled waste collection from plant room',
  0.150, 'MT', 150.000, 1, 12,
  'FINAL', 1, '2026-06-03 15:30:00', @user_id, '2026-06-03 15:30:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00075', 'demo-sample-00075', @site_id, 'P', '2026-06-07', 29, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=29 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store A' LIMIT 1), 'Filter replacement',
  0.160, 'MT', 160.000, 2, 1,
  'FINAL', 1, '2026-06-07 09:00:00', @user_id, '2026-06-07 09:00:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00076', 'demo-sample-00076', @site_id, 'P', '2026-06-09', 39, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=39 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store B' LIMIT 1), 'Contractor return of used oil',
  0.111, 'MT', 111.000, 4, 2,
  'FINAL', 1, '2026-06-09 14:45:00', @user_id, '2026-06-09 14:45:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00077', 'demo-sample-00077', @site_id, 'P', '2026-06-13', 44, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=44 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store B' LIMIT 1), 'Monthly scheduled waste collection from plant room',
  0.160, 'MT', 160.000, 2, 2,
  'FINAL', 1, '2026-06-13 11:00:00', @user_id, '2026-06-13 11:00:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00078', 'demo-sample-00078', @site_id, 'P', '2026-06-15', 53, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=53 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store B' LIMIT 1), 'Oil change during PPM generator set',
  0.050, 'MT', 50.000, 1, 4,
  'FINAL', 1, '2026-06-15 11:15:00', @user_id, '2026-06-15 11:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00079', 'demo-sample-00079', @site_id, 'P', '2026-06-15', 54, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=54 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store A' LIMIT 1), 'Monthly scheduled waste collection from plant room',
  0.350, 'MT', 350.000, 2, 2,
  'FINAL', 1, '2026-06-15 16:00:00', @user_id, '2026-06-15 16:00:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00080', 'demo-sample-00080', @site_id, 'P', '2026-06-19', 61, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=61 LIMIT 1),
  'Facility operations', 'WO-DEM-06-178', 9, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Workshop collection point' LIMIT 1), 'Contractor return of used oil',
  0.050, 'MT', 50.000, 4, 6,
  'FINAL', 1, '2026-06-19 15:15:00', @user_id, '2026-06-19 15:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00081', 'demo-sample-00081', @site_id, 'D', '2026-06-22', 10, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=10 LIMIT 1),
  'Facility operations', 'WO-DEM-06-179', 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'eSWIS consignment collection',
  0.104, 'MT', 104.000, 4, 2,
  'FINAL', 1, '2026-06-22 13:30:00', @user_id, '2026-06-22 13:30:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00082', 'demo-sample-00082', @site_id, 'P', '2026-06-25', 23, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=23 LIMIT 1),
  'Facility operations', 'WO-DEM-06-180', 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store B' LIMIT 1), 'Workshop housekeeping',
  0.350, 'MT', 350.000, 1, 2,
  'FINAL', 1, '2026-06-25 15:30:00', @user_id, '2026-06-25 15:30:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00083', 'demo-sample-00083', @site_id, 'D', '2026-06-25', 22, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=22 LIMIT 1),
  'Facility operations', NULL, 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'eSWIS consignment collection',
  0.198, 'MT', 198.000, 4, 4,
  'FINAL', 1, '2026-06-25 13:45:00', @user_id, '2026-06-25 13:45:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00084', 'demo-sample-00084', @site_id, 'P', '2026-06-27', 29, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=29 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store B' LIMIT 1), 'Painting works waste',
  0.231, 'MT', 231.000, 2, 1,
  'FINAL', 1, '2026-06-27 12:30:00', @user_id, '2026-06-27 12:30:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00085', 'demo-sample-00085', @site_id, 'D', '2026-06-27', 28, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=28 LIMIT 1),
  'Facility operations', NULL, 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'eSWIS consignment collection',
  0.290, 'MT', 290.000, 1, 1,
  'FINAL', 1, '2026-06-27 09:45:00', @user_id, '2026-06-27 09:45:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00086', 'demo-sample-00086', @site_id, 'P', '2026-06-30', 39, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=39 LIMIT 1),
  'Facility operations', 'WO-DEM-06-184', 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store B' LIMIT 1), 'Workshop housekeeping',
  0.300, 'MT', 300.000, 1, 2,
  'FINAL', 1, '2026-06-30 15:45:00', @user_id, '2026-06-30 15:45:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00087', 'demo-sample-00087', @site_id, 'D', '2026-07-01', 44, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=44 LIMIT 1),
  'Facility operations', 'WO-DEM-07-185', 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'eSWIS consignment collection',
  0.087, 'MT', 87.000, 1, 2,
  'FINAL', 1, '2026-07-01 13:00:00', @user_id, '2026-07-01 13:00:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00088', 'demo-sample-00088', @site_id, 'P', '2026-07-02', 53, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=53 LIMIT 1),
  'Facility operations', 'WO-DEM-07-186', 9, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store A' LIMIT 1), 'Chiller maintenance leftover',
  0.200, 'MT', 200.000, 2, 1,
  'FINAL', 1, '2026-07-02 10:00:00', @user_id, '2026-07-02 10:00:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00089', 'demo-sample-00089', @site_id, 'D', '2026-07-04', 54, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=54 LIMIT 1),
  'Facility operations', NULL, 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'Monthly outgoing shipment',
  0.120, 'MT', 120.000, 1, 4,
  'FINAL', 1, '2026-07-04 15:45:00', @user_id, '2026-07-04 15:45:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00090', 'demo-sample-00090', @site_id, 'P', '2026-07-08', 10, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=10 LIMIT 1),
  'Facility operations', 'WO-DEM-07-188', 9, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Workshop collection point' LIMIT 1), 'Chiller maintenance leftover',
  0.080, 'MT', 80.000, 1, 12,
  'FINAL', 1, '2026-07-08 14:45:00', @user_id, '2026-07-08 14:45:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00091', 'demo-sample-00091', @site_id, 'P', '2026-07-08', 61, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=61 LIMIT 1),
  'Facility operations', 'WO-DEM-07-189', 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Workshop collection point' LIMIT 1), 'Monthly scheduled waste collection from plant room',
  0.364, 'MT', 364.000, 3, 2,
  'FINAL', 1, '2026-07-08 13:00:00', @user_id, '2026-07-08 13:00:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00092', 'demo-sample-00092', @site_id, 'D', '2026-07-12', 22, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=22 LIMIT 1),
  'Facility operations', NULL, 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'eSWIS consignment collection',
  0.268, 'MT', 268.000, 3, 12,
  'FINAL', 1, '2026-07-12 11:00:00', @user_id, '2026-07-12 11:00:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00093', 'demo-sample-00093', @site_id, 'P', '2026-07-17', 23, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=23 LIMIT 1),
  'Facility operations', NULL, 9, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store B' LIMIT 1), 'Monthly scheduled waste collection from plant room',
  0.150, 'MT', 150.000, 1, 6,
  'FINAL', 1, '2026-07-17 16:30:00', @user_id, '2026-07-17 16:30:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00094', 'demo-sample-00094', @site_id, 'D', '2026-07-17', 28, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=28 LIMIT 1),
  'Facility operations', NULL, 7, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Loading bay / outgoing' LIMIT 1), 'Disposed via approved receiver',
  0.217, 'MT', 217.000, 2, 1,
  'FINAL', 1, '2026-07-17 08:00:00', @user_id, '2026-07-17 08:00:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00095', 'demo-sample-00095', @site_id, 'P', '2026-07-21', 29, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=29 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Workshop collection point' LIMIT 1), 'Filter replacement',
  0.350, 'MT', 350.000, 2, 2,
  'FINAL', 1, '2026-07-21 08:00:00', @user_id, '2026-07-21 08:00:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00096', 'demo-sample-00096', @site_id, 'P', '2026-07-21', 39, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=39 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store A' LIMIT 1), 'Workshop housekeeping',
  0.050, 'MT', 50.000, 3, 4,
  'FINAL', 1, '2026-07-21 13:45:00', @user_id, '2026-07-21 13:45:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00097', 'demo-sample-00097', @site_id, 'P', '2026-09-04', 22, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=22 LIMIT 1),
  'Facility operations', NULL, 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store B' LIMIT 1), 'Contractor return of used oil',
  0.100, 'MT', 100.000, 1, 8,
  'DRAFT', NULL, NULL, @user_id, '2026-09-04 09:15:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00098', 'demo-sample-00098', @site_id, 'P', '2026-09-08', 23, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=23 LIMIT 1),
  'Facility operations', 'WO-DEM-09-196', 9, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store A' LIMIT 1), 'Workshop housekeeping',
  0.100, 'MT', 100.000, 1, 1,
  'DRAFT', NULL, NULL, @user_id, '2026-09-08 09:30:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00099', 'demo-sample-00099', @site_id, 'P', '2026-09-11', 28, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=28 LIMIT 1),
  'Facility operations', 'WO-DEM-09-197', 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Scheduled Waste Store A' LIMIT 1), 'Contractor return of used oil',
  0.100, 'MT', 100.000, 1, 8,
  'DRAFT', NULL, NULL, @user_id, '2026-09-11 11:30:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00100', 'demo-sample-00100', @site_id, 'P', '2026-09-16', 29, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=29 LIMIT 1),
  'Facility operations', 'WO-DEM-09-198', 9, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Workshop collection point' LIMIT 1), 'Painting works waste',
  0.100, 'MT', 100.000, 1, 2,
  'DRAFT', NULL, NULL, @user_id, '2026-09-16 12:00:00'
);
INSERT INTO wst_transaction (
  txn_ref, client_ref, site_id, txn_type, event_date, sw_code_id, profile_id,
  source_activity, wo_ref, handling_method_id, location_id, remarks,
  qty, unit, qty_kg, packaging_type_id, package_count,
  txn_status, finalised_by, finalised_at, txn_created_by, txn_created_at
) VALUES (
  'WST-DEMO-2026-00101', 'demo-sample-00101', @site_id, 'P', '2026-09-18', 39, (SELECT profile_id FROM wst_waste_profile WHERE site_id=@site_id AND sw_code_id=39 LIMIT 1),
  'Facility operations', 'WO-DEM-09-199', 6, (SELECT location_id FROM wst_location WHERE site_id=@site_id AND location_name='Workshop collection point' LIMIT 1), 'Workshop housekeeping',
  0.100, 'MT', 100.000, 2, 1,
  'DRAFT', NULL, NULL, @user_id, '2026-09-18 11:30:00'
);

INSERT INTO wst_number_sequence (site_id, seq_year, last_no)
VALUES (@site_id, 2026, 101)
ON DUPLICATE KEY UPDATE last_no = GREATEST(last_no, 101);

SELECT COUNT(*) AS sample_txns FROM wst_transaction WHERE site_id=19;
SELECT txn_type, txn_status, COUNT(*) AS n FROM wst_transaction WHERE site_id=19 GROUP BY txn_type, txn_status;
SELECT c.sw_code, COUNT(*) n FROM wst_transaction t JOIN ref_sw_code c ON c.sw_code_id=t.sw_code_id WHERE t.site_id=19 GROUP BY c.sw_code ORDER BY c.sw_code;

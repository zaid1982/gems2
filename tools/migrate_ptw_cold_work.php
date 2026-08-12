<?php
// Migration: Backfill ptw_checklist_cold_work (canonical nested JSON) from legacy shapes
// Usage:
//   php tools/migrate_ptw_cold_work.php --apply
//   php tools/migrate_ptw_cold_work.php --dry-run (default)

require_once __DIR__ . '/../api/function/db.php';

function map_legacy_to_canonical($legacyRaw) {
    $cw = [
        'electricalWork' => [
            'circuitIsolation' => false,
            'lockOutTaggedOut' => false,
            'fireExtinguisher' => false,
            'mainSupplyCutOff' => false,
            'others' => false,
            'othersText' => ''
        ],
        'workingAtHeight' => [
            'abseilingWork' => false,
            'scaffolding' => false,
            'gondola' => false,
            'workingAtRooftop' => false,
            'usingA' => false,
            'usingAText' => '',
            'others' => false,
            'othersText' => ''
        ],
        'excavationWork' => [
            'depthLt1_2m' => false,
            'depthGt1_2mConfined' => false,
            'safeAccessEgress' => false,
            'protectionFromFallingMaterial' => false,
            'protectionFromEngulfment' => false,
            'others' => false,
            'othersText' => ''
        ],
        'workingUnderLoad' => false,
        'liftingWork' => false,
        'chemicalHandling' => false,
        'specialPrecautions' => ''
    ];
    if (!$legacyRaw) return $cw;
    $legacy = is_string($legacyRaw) ? json_decode($legacyRaw, true) : $legacyRaw;
    if (!is_array($legacy)) return $cw;
    $bool = function($v){ return $v === true || $v === 'true' || $v === 1 || $v === '1' || $v === 'Y' || $v === 'y'; };

    // Common keys
    $cw['electricalWork']['circuitIsolation'] = $bool($legacy['coldElectricalWork'] ?? $legacy['electrical_work'] ?? $legacy['electrical'] ?? false);
    $cw['electricalWork']['lockOutTaggedOut'] = $bool($legacy['coldLockOutTagOut'] ?? $legacy['lock_out_tag_out'] ?? $legacy['loto'] ?? false);
    $cw['electricalWork']['fireExtinguisher'] = $bool($legacy['coldFireExtinguisher'] ?? $legacy['fire_extinguisher'] ?? false);
    $cw['electricalWork']['mainSupplyCutOff'] = $bool($legacy['coldMainSupplyCutOff'] ?? $legacy['main_supply_cut_off'] ?? false);
    $cw['workingAtHeight']['abseilingWork'] = $bool($legacy['abseiling_work'] ?? false);
    $cw['workingAtHeight']['scaffolding'] = $bool($legacy['scaffolding'] ?? false);
    $cw['workingAtHeight']['gondola'] = $bool($legacy['gondola'] ?? false);
    $cw['workingAtHeight']['workingAtRooftop'] = $bool($legacy['working_at_rooftop'] ?? false);
    $cw['workingAtHeight']['usingAText'] = trim(strval($legacy['using_a'] ?? ''));
    if ($cw['workingAtHeight']['usingAText'] !== '') $cw['workingAtHeight']['usingA'] = true;
    $cw['excavationWork']['depthLt1_2m'] = $bool($legacy['depth_lt_1_2'] ?? false);
    $cw['excavationWork']['depthGt1_2mConfined'] = $bool($legacy['depth_gt_1_2_confined'] ?? false);
    $cw['excavationWork']['safeAccessEgress'] = $bool($legacy['safe_access_egress'] ?? false);
    $cw['excavationWork']['protectionFromFallingMaterial'] = $bool($legacy['protect_falling_material'] ?? false);
    $cw['excavationWork']['protectionFromEngulfment'] = $bool($legacy['protect_engulfment'] ?? false);
    // Others
    $elOthers = trim(strval($legacy['electrical_others'] ?? $legacy['coldOthersText'] ?? $legacy['others_text'] ?? ''));
    if ($elOthers !== '') { $cw['electricalWork']['others'] = true; $cw['electricalWork']['othersText'] = $elOthers; }
    $whOthers = trim(strval($legacy['height_others'] ?? ''));
    if ($whOthers !== '') { $cw['workingAtHeight']['others'] = true; $cw['workingAtHeight']['othersText'] = $whOthers; }
    $exOthers = trim(strval($legacy['excavation_others'] ?? ''));
    if ($exOthers !== '') { $cw['excavationWork']['others'] = true; $cw['excavationWork']['othersText'] = $exOthers; }
    // Singles
    $cw['workingUnderLoad'] = $bool($legacy['working_under_load'] ?? false);
    $cw['liftingWork'] = $bool($legacy['coldLiftingWork'] ?? $legacy['lifting_work'] ?? false);
    $cw['chemicalHandling'] = $bool($legacy['chemical_handling'] ?? false);
    // Notes
    $cw['specialPrecautions'] = trim(strval($legacy['coldSpecialPrecautions'] ?? $legacy['special_precautions'] ?? $legacy['cold_work_notes'] ?? ''));
    return $cw;
}

$apply = in_array('--apply', $argv);
$dry = !$apply;
$ts = date('Ymd_His');
$csvPath = __DIR__ . "/../migrations/ptw_cold_work_migration_$ts.csv";
if (!is_dir(dirname($csvPath))) { @mkdir(dirname($csvPath), 0775, true); }
$csv = fopen($csvPath, 'w');
fputcsv($csv, ['ptw_permit_id','had_canonical','legacy_source','canonical_json']);

Class_db::getInstance()->db_connect();
$rows = Class_db::getInstance()->db_select('ptw_permit', array());
$updated = 0; $skipped = 0; $total = count($rows);
foreach ($rows as $r) {
    $id = $r['ptw_permit_id'];
    $canonical = $r['ptw_checklist_cold_work'] ?? null;
    $hadCanonical = !empty(trim((string)$canonical));
    if ($hadCanonical) { $skipped++; fputcsv($csv, [$id, 1, '', '']); continue; }
    $legacy = $r['ptw_hazard_checklist'] ?? null;
    $legacyObj = null; $legacySrc = '';
    if ($legacy) {
        $parsed = json_decode($legacy, true);
        if (is_array($parsed)) {
            if (isset($parsed['cold_work'])) { $legacyObj = $parsed['cold_work']; $legacySrc = 'ptw_hazard_checklist.cold_work'; }
            else { $legacyObj = $parsed; $legacySrc = 'ptw_hazard_checklist'; }
        }
    }
    $cw = map_legacy_to_canonical($legacyObj);
    $json = json_encode($cw, JSON_UNESCAPED_UNICODE);
    fputcsv($csv, [$id, 0, $legacySrc, $json]);
    if ($apply) {
        try {
            Class_db::getInstance()->db_update('ptw_permit', array('ptw_checklist_cold_work' => $json), array('ptw_permit_id' => strval($id)));
            $updated++;
        } catch (Exception $e) {
            // Ignore per-row errors but report at end
        }
    }
}
fclose($csv);
echo "Migration complete. total=$total updated=$updated skipped=$skipped csv=$csvPath\n";

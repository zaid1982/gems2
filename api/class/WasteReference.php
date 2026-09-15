<?php

class WasteReference extends WasteBase {

    public function listLookups(?int $siteId = null): array {
        return array(
            'sites' => $this->listSites(),
            'swCodes' => $this->listSwCodes($siteId),
            'locations' => $this->listLocations($siteId),
            'refValues' => $this->listRefValues(null, $siteId)
        );
    }

    public function listSwCodes(?int $siteId = null, bool $activeOnly = false): array {
        $sql = "SELECT c.sw_code_id, c.sw_code, c.sw_group, c.sw_description, c.sw_status,
                       p.profile_id, p.profile_alias, p.profile_status
                FROM ref_sw_code c
                LEFT JOIN wst_waste_profile p ON p.sw_code_id = c.sw_code_id AND p.site_id = :siteId
                WHERE 1 = 1";
        $params = array('siteId' => $siteId ?: 0);
        if ($activeOnly) {
            $sql .= " AND c.sw_status = 1 AND (p.profile_id IS NULL OR p.profile_status = 1)";
        }
        $sql .= " ORDER BY c.sw_code";
        return $this->queryAll($sql, $params);
    }

    public function listSites(): array {
        $sql = "SELECT s.site_id, s.site_name, s.site_code, s.site_desc, s.site_status,
                       p.premise_address, p.premise_contact_no, p.cutover_date,
                       p.evidence_required_produced, p.evidence_required_disposed, p.premise_status
                FROM cli_site s
                LEFT JOIN wst_premise p ON p.site_id = s.site_id
                WHERE s.site_status = 1";
        $params = array();
        if (!$this->isAdministrator()) {
            $sql .= " AND s.site_id = :siteId";
            $params['siteId'] = $this->resolveSiteId();
        }
        $sql .= " ORDER BY s.site_name";
        return $this->queryAll($sql, $params);
    }

    public function getPremise(int $siteId): array {
        $siteId = $this->resolveSiteId($siteId);
        $this->assertSiteAccess($siteId);
        $sql = "SELECT s.site_id, s.site_name, s.site_code, s.site_desc, s.site_status,
                       p.premise_address, p.premise_contact_no, p.cutover_date,
                       p.evidence_required_produced, p.evidence_required_disposed, p.premise_status
                FROM cli_site s
                LEFT JOIN wst_premise p ON p.site_id = s.site_id
                WHERE s.site_id = :siteId";
        $row = $this->queryOne($sql, array('siteId' => $siteId));
        if (empty($row)) {
            throw new Exception('Select a premise.', 31);
        }
        return $row;
    }

    public function savePremise(array $columns): array {
        $this->requireSetup();
        $siteId = $this->resolveSiteId($columns['siteId'] ?? null);
        $this->assertSiteAccess($siteId);
        $site = $this->getSiteRow($siteId);
        if (intval($site['siteStatus']) !== 1) {
            throw new Exception('Select an authorised active premise.', 31);
        }
        $data = array(
            'site_id' => $siteId,
            'premise_address' => trim(strval($columns['premiseAddress'] ?? '')),
            'premise_contact_no' => trim(strval($columns['premiseContactNo'] ?? '')),
            'cutover_date' => $this->normalizeDate($columns['cutoverDate'] ?? ''),
            'evidence_required_produced' => !empty($columns['evidenceRequiredProduced']) ? 1 : 0,
            'evidence_required_disposed' => !empty($columns['evidenceRequiredDisposed']) ? 1 : 0,
            'premise_status' => isset($columns['premiseStatus']) ? intval($columns['premiseStatus']) : 1
        );
        $existing = DbMysql::select('wst_premise', array('siteId' => $siteId));
        if (empty($existing)) {
            $data['premise_created_by'] = $this->userId;
            DbMysql::insert('wst_premise', $data);
        } else {
            $data['premise_updated_by'] = $this->userId;
            unset($data['site_id']);
            DbMysql::update('wst_premise', $data, array('site_id' => $siteId));
        }
        $this->saveAudit(self::AUDIT_SETUP, 'Updated waste premise ' . $site['siteCode']);
        return $this->getPremise($siteId);
    }

    public function listProfiles(?int $siteId = null): array {
        $siteId = $this->resolveSiteId($siteId);
        $sql = "SELECT p.profile_id, p.site_id, p.sw_code_id, p.profile_alias, p.profile_status,
                       c.sw_code, c.sw_description, c.sw_group
                FROM wst_waste_profile p
                INNER JOIN ref_sw_code c ON c.sw_code_id = p.sw_code_id
                WHERE p.site_id = :siteId
                ORDER BY c.sw_code";
        return $this->queryAll($sql, array('siteId' => $siteId));
    }

    public function saveProfile(array $columns, ?int $profileId = null): array {
        $this->requireSetup();
        $siteId = $this->resolveSiteId($columns['siteId'] ?? null);
        $this->assertSiteAccess($siteId);
        parent::checkMandatoryArray($columns, array('swCodeId'));
        $sw = $this->getSwCode(intval($columns['swCodeId']));
        $data = array(
            'site_id' => $siteId,
            'sw_code_id' => intval($sw['swCodeId']),
            'profile_alias' => trim(strval($columns['profileAlias'] ?? '')),
            'profile_status' => isset($columns['profileStatus']) ? intval($columns['profileStatus']) : 1
        );
        if ($profileId) {
            $existing = DbMysql::select('wst_waste_profile', array('profileId' => $profileId), true);
            $this->assertSiteAccess(intval($existing['siteId']));
            $data['profile_updated_by'] = $this->userId;
            unset($data['site_id']);
            DbMysql::update('wst_waste_profile', $data, array('profile_id' => $profileId));
        } else {
            $dup = DbMysql::select('wst_waste_profile', array('siteId' => $siteId, 'swCodeId' => intval($sw['swCodeId'])));
            if (!empty($dup)) {
                throw new Exception('A waste profile for this SW code already exists at the premise.', 31);
            }
            $data['profile_created_by'] = $this->userId;
            $profileId = DbMysql::insert('wst_waste_profile', $data);
        }
        $this->saveAudit(self::AUDIT_SETUP, 'Updated waste profile ' . $sw['swCode']);
        $rows = $this->queryAll(
            "SELECT p.profile_id, p.site_id, p.sw_code_id, p.profile_alias, p.profile_status,
                    c.sw_code, c.sw_description, c.sw_group
             FROM wst_waste_profile p
             INNER JOIN ref_sw_code c ON c.sw_code_id = p.sw_code_id
             WHERE p.profile_id = :id",
            array('id' => $profileId)
        );
        return $rows[0];
    }

    public function deactivateProfile(int $profileId): void {
        $this->requireSetup();
        $existing = DbMysql::select('wst_waste_profile', array('profileId' => $profileId), true);
        $this->assertSiteAccess(intval($existing['siteId']));
        DbMysql::update('wst_waste_profile', array(
            'profile_status' => 2,
            'profile_updated_by' => $this->userId
        ), array('profile_id' => $profileId));
        $this->saveAudit(self::AUDIT_SETUP, 'Deactivated waste profile ' . $profileId);
    }

    public function listLocations(?int $siteId = null, ?string $type = null): array {
        $siteId = $this->resolveSiteId($siteId);
        $sql = "SELECT location_id, site_id, location_name, location_type, location_status
                FROM wst_location WHERE site_id = :siteId";
        $params = array('siteId' => $siteId);
        if ($type) {
            $sql .= " AND location_type = :type";
            $params['type'] = strtoupper($type);
        }
        $sql .= " ORDER BY location_name";
        return $this->queryAll($sql, $params);
    }

    public function saveLocation(array $columns, ?int $locationId = null): array {
        $this->requireSetup();
        $siteId = $this->resolveSiteId($columns['siteId'] ?? null);
        $this->assertSiteAccess($siteId);
        parent::checkMandatoryArray($columns, array('locationName'));
        $type = strtoupper(trim(strval($columns['locationType'] ?? 'STORAGE')));
        if (!in_array($type, array('STORAGE', 'DESTINATION'), true)) {
            $type = 'STORAGE';
        }
        $data = array(
            'site_id' => $siteId,
            'location_name' => trim(strval($columns['locationName'])),
            'location_type' => $type,
            'location_status' => isset($columns['locationStatus']) ? intval($columns['locationStatus']) : 1
        );
        if ($locationId) {
            $existing = DbMysql::select('wst_location', array('locationId' => $locationId), true);
            $this->assertSiteAccess(intval($existing['siteId']));
            $data['location_updated_by'] = $this->userId;
            unset($data['site_id']);
            DbMysql::update('wst_location', $data, array('location_id' => $locationId));
        } else {
            $data['location_created_by'] = $this->userId;
            $locationId = DbMysql::insert('wst_location', $data);
        }
        $this->saveAudit(self::AUDIT_SETUP, 'Updated waste location ' . $data['location_name'] ?? '');
        return DbMysql::select('wst_location', array('locationId' => $locationId), true);
    }

    public function deactivateLocation(int $locationId): void {
        $this->requireSetup();
        $existing = DbMysql::select('wst_location', array('locationId' => $locationId), true);
        $this->assertSiteAccess(intval($existing['siteId']));
        DbMysql::update('wst_location', array(
            'location_status' => 2,
            'location_updated_by' => $this->userId
        ), array('location_id' => $locationId));
        $this->saveAudit(self::AUDIT_SETUP, 'Deactivated waste location ' . $locationId);
    }

    public function listRefValues(?string $type = null, ?int $siteId = null): array {
        $sql = "SELECT ref_value_id, value_type, site_id, value_name, value_status
                FROM wst_ref_value WHERE value_status = 1";
        $params = array();
        if ($type) {
            $sql .= " AND value_type = :type";
            $params['type'] = strtoupper($type);
        }
        if ($siteId) {
            $siteId = $this->resolveSiteId($siteId);
            $sql .= " AND (site_id IS NULL OR site_id = :siteId)";
            $params['siteId'] = $siteId;
        } else if (!$this->isAdministrator() && intval($this->userSite) > 0) {
            $sql .= " AND (site_id IS NULL OR site_id = :siteId)";
            $params['siteId'] = intval($this->userSite);
        }
        $sql .= " ORDER BY value_type, value_name";
        return $this->queryAll($sql, $params);
    }

    public function saveRefValue(array $columns, ?int $refValueId = null): array {
        $this->requireSetup();
        parent::checkMandatoryArray($columns, array('valueType', 'valueName'));
        $siteId = null;
        if (!empty($columns['siteId'])) {
            $siteId = $this->resolveSiteId($columns['siteId']);
            $this->assertSiteAccess($siteId);
        }
        $data = array(
            'value_type' => strtoupper(trim(strval($columns['valueType']))),
            'site_id' => $siteId,
            'value_name' => trim(strval($columns['valueName'])),
            'value_status' => isset($columns['valueStatus']) ? intval($columns['valueStatus']) : 1
        );
        if ($refValueId) {
            $data['value_updated_by'] = $this->userId;
            DbMysql::update('wst_ref_value', $data, array('ref_value_id' => $refValueId));
        } else {
            $data['value_created_by'] = $this->userId;
            $refValueId = DbMysql::insert('wst_ref_value', $data);
        }
        $this->saveAudit(self::AUDIT_SETUP, 'Updated waste reference ' . $data['value_name']);
        return DbMysql::select('wst_ref_value', array('refValueId' => $refValueId), true);
    }

    public function deactivateRefValue(int $refValueId): void {
        $this->requireSetup();
        DbMysql::update('wst_ref_value', array(
            'value_status' => 2,
            'value_updated_by' => $this->userId
        ), array('ref_value_id' => $refValueId));
        $this->saveAudit(self::AUDIT_SETUP, 'Deactivated waste reference ' . $refValueId);
    }
}

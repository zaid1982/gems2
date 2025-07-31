<?php
/**
 * Site Filter Trait
 * Provides site-based data filtering for non-administrator users
 */
trait SiteFilterTrait {
    
    /**
     * Add site filtering to WHERE clause for non-administrators
     * @param array $whereClause
     * @param string $siteFieldName
     * @return array
     */
    public function addSiteFilterToWhere(array $whereClause, string $siteFieldName = 'site_id'): array {
        if (!$this->isAdministrator() && $this->userSite) {
            $whereClause[$siteFieldName] = $this->userSite;
        }
        return $whereClause;
    }
    
    /**
     * Get site-filtered data using db_select2
     * @param string $table
     * @param array $whereClause
     * @param string $siteFieldName
     * @return string
     * @throws Exception
     */
    public function getSiteFilteredData(string $table, array $whereClause = array(), string $siteFieldName = 'site_id'): string {
        try {
            $filteredWhere = $this->addSiteFilterToWhere($whereClause, $siteFieldName);
            return Class_db::getInstance()->db_select2($table, $filteredWhere);
        } catch(Exception $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
    
    /**
     * Get site-filtered single record
     * @param string $table
     * @param array $whereClause
     * @param string $siteFieldName
     * @return array
     * @throws Exception
     */
    public function getSiteFilteredSingle(string $table, array $whereClause = array(), string $siteFieldName = 'site_id'): array {
        try {
            $filteredWhere = $this->addSiteFilterToWhere($whereClause, $siteFieldName);
            return Class_db::getInstance()->db_select_single2($table, $filteredWhere);
        } catch(Exception $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
    
    /**
     * Check if current user can access specific site data
     * @param int $siteId
     * @return bool
     */
    public function canAccessSite(int $siteId): bool {
        if ($this->isAdministrator()) {
            return true;
        }
        return $this->userSite == $siteId;
    }
    
    /**
     * Validate site access and throw exception if not allowed
     * @param int $siteId
     * @throws Exception
     */
    public function validateSiteAccess(int $siteId): void {
        if (!$this->canAccessSite($siteId)) {
            throw new Exception('Access denied: You can only access data from your assigned site.', 403);
        }
    }
}

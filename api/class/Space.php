<?php

class Space extends General {

    public int $spaceId = 0;
    public string $spaceName = '';

    private static string $tableName = 'spc_space';
    private static string $idName = 'space_id';
    private static string $assetTable = 'spc_space_asset';
    private static string $mediaTable = 'spc_space_media';
    private static string $reservationTable = 'spc_reservation';
    private static int $documentId = 41; // Ref: create_space_module.sql -> ref_document

    private const STATUS_AVAILABLE = 'AVAILABLE';
    private const STATUS_ACTIVE = 'ACTIVE';
    private const STATUS_RESERVED = 'RESERVED';
    private const STATUS_DISABLED = 'DISABLED';

    private const RESERVATION_RESERVED = 'RESERVED';
    private const RESERVATION_CANCELED = 'CANCELED';

    private const EMAIL_TEMPLATE_RESERVATION_CREATED = 320;
    private const EMAIL_TEMPLATE_RESERVATION_UPDATED = 321;
    private const EMAIL_TEMPLATE_RESERVATION_CANCELED = 322;

    public function __construct(int $userId = 0, bool $isLogged = false)
    {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * List spaces with optional filters (siteId, status[]).
     * @param array $filters
     * @return array
     * @throws Exception
     */
    public function getList(array $filters = array()): array
    {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            // Use explicit table alias to avoid ambiguous columns in joined queries
            $where = array('s.is_deleted' => 0);
            if (!$this->isAdministrator() && $this->userSite) {
                $where['s.site_id'] = $this->userSite;
            } else if (!empty($filters['siteId'])) {
                $where['s.site_id'] = intval($filters['siteId']);
            }

            if (!empty($filters['status'])) {
                $statusArr = is_array($filters['status']) ? $filters['status'] : array($filters['status']);
                $statusArr = array_map(static function ($status) {
                    return strtoupper(trim($status));
                }, $statusArr);
                $where['s.space_status'] = 'IN|'.implode(',', $statusArr);
            }

            $sql = /** @lang text */
                "SELECT 
                    s.space_id      AS spaceId,
                    s.site_id       AS siteId,
                    s.space_name    AS spaceName,
                    s.space_status  AS spaceStatus,
                    s.space_area    AS spaceArea,
                    s.space_capacity AS spaceCapacity,
                    s.space_desc    AS spaceDesc,
                    s.space_location_id AS locationId,
                    s.space_category_id AS categoryId,
                    s.space_type_id AS typeId,
                    s.space_created_at AS createdAt,
                    s.space_created_by AS createdBy,
                    s.space_updated_at AS updatedAt,
                    s.space_updated_by AS updatedBy,
                    si.site_name       AS siteName,
                    loc.space_location_name AS locationName,
                    cat.space_category_name AS categoryName,
                    typ.space_type_name     AS typeName,
                    (SELECT COUNT(*) FROM spc_reservation r 
                        WHERE r.space_id = s.space_id AND r.reservation_status = 'RESERVED') AS activeReservationCount
                FROM spc_space s
                LEFT JOIN cli_site si ON si.site_id = s.site_id
                LEFT JOIN ref_space_location loc ON loc.space_location_id = s.space_location_id
                LEFT JOIN ref_space_category cat ON cat.space_category_id = s.space_category_id
                LEFT JOIN ref_space_type typ ON typ.space_type_id = s.space_type_id";

            $spaces = DbMysql::selectSqlAll($sql, $where, 0, false, 'spaceName');
            if (!empty($spaces)) {
                $coverMap = $this->getCoverPhotoMap(array_column($spaces, 'spaceId'));
                foreach ($spaces as &$space) {
                    $sid = intval($space['spaceId'] ?? 0);
                    if (isset($coverMap[$sid])) {
                        $space['coverPhotoUrl'] = $coverMap[$sid]['url'];
                        $space['coverPhotoCaption'] = $coverMap[$sid]['caption'];
                        $space['coverPhotoUploadId'] = $coverMap[$sid]['uploadId'];
                    } else {
                        $space['coverPhotoUrl'] = '';
                        $space['coverPhotoCaption'] = '';
                        $space['coverPhotoUploadId'] = null;
                    }
                }
                unset($space);
            }

            return $spaces;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Get a single space profile with linked data
     * @param int $spaceId
     * @return array
     * @throws Exception
     */
    public function get(int $spaceId): array
    {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($spaceId, 'spaceId');

            $sql = /** @lang text */
                "SELECT 
                    s.space_id      AS spaceId,
                    s.site_id       AS siteId,
                    s.space_name    AS spaceName,
                    s.space_status  AS spaceStatus,
                    s.space_area    AS spaceArea,
                    s.space_capacity AS spaceCapacity,
                    s.space_desc    AS spaceDesc,
                    s.space_location_id AS locationId,
                    s.space_category_id AS categoryId,
                    s.space_type_id AS typeId,
                    s.space_created_at AS createdAt,
                    s.space_created_by AS createdBy,
                    s.space_updated_at AS updatedAt,
                    s.space_updated_by AS updatedBy,
                    si.site_name       AS siteName,
                    loc.space_location_name AS locationName,
                    cat.space_category_name AS categoryName,
                    typ.space_type_name     AS typeName
                FROM spc_space s
                LEFT JOIN cli_site si ON si.site_id = s.site_id
                LEFT JOIN ref_space_location loc ON loc.space_location_id = s.space_location_id
                LEFT JOIN ref_space_category cat ON cat.space_category_id = s.space_category_id
                LEFT JOIN ref_space_type typ ON typ.space_type_id = s.space_type_id";

            $data = DbMysql::selectSql($sql, array('spaceId'=>$spaceId, 'isDeleted'=>0), true);
            parent::checkEmptyArray($data, 'spaceData');

            if (!$this->isAdministrator() && $this->userSite && intval($data['siteId']) !== intval($this->userSite)) {
                throw new Exception('Access denied to space from different site', 403);
            }

            $data['assets'] = $this->getAssets($spaceId);
            $data['media'] = $this->getMedia($spaceId);
            $data['upcomingReservations'] = $this->getUpcomingReservations($spaceId);

            return $data;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Create a new space
     * @param array $params
     * @return array Newly created space details
     * @throws Exception
     */
    public function create(array $params): array
    {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkMandatoryArray($params, array('spaceName', 'siteId', 'status'));

            $spaceName = trim($params['spaceName']);
            $siteId = intval($params['siteId']);
            $status = strtoupper(trim($params['status']));

            if (!in_array($status, array(self::STATUS_AVAILABLE, self::STATUS_ACTIVE, self::STATUS_RESERVED, self::STATUS_DISABLED), true)) {
                throw new Exception('Invalid space status supplied', 31);
            }

            if (!$this->isAdministrator()) {
                $this->validateSiteAccess($siteId);
            }

            if (DbMysql::count(self::$tableName, array('siteId'=>$siteId, 'spaceName'=>$spaceName, 'isDeleted'=>0)) > 0) {
                throw new Exception(str_replace('__', $spaceName, Constant::$space['errAlreadyExist']), 31);
            }

            // Normalize and validate category/type relationship
            $locationId = $this->nullIfEmpty($params['locationId'] ?? null);
            $categoryId = $this->nullIfEmpty($params['categoryId'] ?? null);
            $typeId     = $this->nullIfEmpty($params['typeId'] ?? null);
            if (!is_null($typeId)) {
                // Load type and validate it belongs to supplied category (or derive category if missing)
                $typeRow = DbMysql::select('ref_space_type', array('spaceTypeId'=>intval($typeId)), true);
                parent::checkEmptyArray($typeRow, 'spaceType');
                $typeCatId = intval($typeRow['spaceCategoryId'] ?? 0);
                if (empty($typeCatId)) {
                    throw new Exception('Invalid Space Type (missing category link)', 31);
                }
                if (is_null($categoryId)) {
                    $categoryId = $typeCatId; // auto-align when category not provided
                } else if (intval($categoryId) !== $typeCatId) {
                    throw new Exception('Selected Type does not belong to the selected Category', 31);
                }
            }

            $insertArr = array(
                'siteId' => $siteId,
                'spaceName' => $spaceName,
                'spaceStatus' => $status,
                'spaceLocationId' => $locationId,
                'spaceCategoryId' => $categoryId,
                'spaceTypeId' => $typeId,
                'spaceArea' => $this->nullIfEmpty($params['area'] ?? null),
                'spaceCapacity' => $this->nullIfEmpty($params['capacity'] ?? null),
                'spaceDesc' => $this->nullIfEmpty($params['description'] ?? null),
                'isDeleted' => 0,
                'spaceCreatedBy' => $this->userId,
                'spaceCreatedAt' => 'NOW()'
            );

            $this->spaceId = DbMysql::insert(self::$tableName, $insertArr);
            $this->spaceName = $spaceName;

            $assetIds = $params['assetIds'] ?? array();
            $this->syncAssets($this->spaceId, $assetIds, $siteId);
            $this->syncMedia($this->spaceId, $params);

            $this->saveAudit(70, 'Space = '.$spaceName);

            return $this->get($this->spaceId);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Update existing space
     * @param int $spaceId
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function update(int $spaceId, array $params): array
    {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($spaceId, 'spaceId');

            $space = DbMysql::select(self::$tableName, array('spaceId'=>$spaceId, 'isDeleted'=>0), true);
            parent::checkEmptyArray($space, 'space');

            $this->spaceId = intval($space['spaceId']);
            $this->spaceName = $space['spaceName'];
            $siteId = intval($space['siteId']);

            if (!$this->isAdministrator()) {
                $this->validateSiteAccess($siteId);
            }

            $updateArr = array();

            if (array_key_exists('spaceName', $params)) {
                $newName = trim($params['spaceName']);
                if ($newName === '') {
                    throw new Exception('['.__LINE__.'] - spaceName empty', 31);
                }
                if ($newName !== $space['spaceName'] && DbMysql::count(self::$tableName, array('siteId'=>$siteId, 'spaceName'=>$newName, 'spaceId'=>'<>|'.$spaceId, 'isDeleted'=>0)) > 0) {
                    throw new Exception(str_replace('__', $newName, Constant::$space['errAlreadyExist']), 31);
                }
                $updateArr['spaceName'] = $newName;
                $this->spaceName = $newName;
            }

            if (array_key_exists('status', $params)) {
                $status = strtoupper(trim($params['status']));
                if (!in_array($status, array(self::STATUS_AVAILABLE, self::STATUS_ACTIVE, self::STATUS_RESERVED, self::STATUS_DISABLED), true)) {
                    throw new Exception('Invalid space status supplied', 31);
                }
                $updateArr['spaceStatus'] = $status;
            }

            if (array_key_exists('locationId', $params)) {
                $updateArr['spaceLocationId'] = $this->nullIfEmpty($params['locationId']);
            }
            if (array_key_exists('categoryId', $params)) {
                $updateArr['spaceCategoryId'] = $this->nullIfEmpty($params['categoryId']);
            }
            if (array_key_exists('typeId', $params)) {
                $updateArr['spaceTypeId'] = $this->nullIfEmpty($params['typeId']);
            }

            // Validate final category/type consistency and auto-align when needed
            $finalCategoryId = array_key_exists('categoryId', $params) ? $this->nullIfEmpty($params['categoryId']) : ($space['spaceCategoryId'] ?? null);
            $finalTypeId = array_key_exists('typeId', $params) ? $this->nullIfEmpty($params['typeId']) : ($space['spaceTypeId'] ?? null);
            if (!is_null($finalTypeId)) {
                $typeRow = DbMysql::select('ref_space_type', array('spaceTypeId'=>intval($finalTypeId)), true);
                parent::checkEmptyArray($typeRow, 'spaceType');
                $typeCatId = intval($typeRow['spaceCategoryId'] ?? 0);
                if (empty($typeCatId)) {
                    throw new Exception('Invalid Space Type (missing category link)', 31);
                }
                if (array_key_exists('categoryId', $params)) {
                    // Caller attempted to set category explicitly
                    if (is_null($finalCategoryId)) {
                        // If set to null, align it to type's category
                        $updateArr['spaceCategoryId'] = $typeCatId;
                    } else if (intval($finalCategoryId) !== $typeCatId) {
                        throw new Exception('Selected Type does not belong to the selected Category', 31);
                    }
                } else {
                    // Category not in request; align stored category if needed
                    if (intval($space['spaceCategoryId'] ?? 0) !== $typeCatId) {
                        $updateArr['spaceCategoryId'] = $typeCatId;
                    }
                }
            }
            if (array_key_exists('area', $params)) {
                $updateArr['spaceArea'] = $this->nullIfEmpty($params['area']);
            }
            if (array_key_exists('capacity', $params)) {
                $updateArr['spaceCapacity'] = $this->nullIfEmpty($params['capacity']);
            }
            if (array_key_exists('description', $params)) {
                $updateArr['spaceDesc'] = $this->nullIfEmpty($params['description']);
            }

            if (!empty($updateArr)) {
                $updateArr['spaceUpdatedBy'] = $this->userId;
                $updateArr['spaceUpdatedAt'] = 'NOW()';
                DbMysql::update(self::$tableName, $updateArr, array('spaceId'=>$spaceId));
            }

            if (array_key_exists('assetIds', $params)) {
                $this->syncAssets($spaceId, $params['assetIds'], $siteId);
            }

            if (!empty($params['photos']) || !empty($params['photosRemove']) || !empty($params['floorplan']) || array_key_exists('coverMediaId', $params)) {
                $this->syncMedia($spaceId, $params);
            }

            $this->saveAudit(71, 'Space = '.$this->spaceName);

            return $this->get($spaceId);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Change space status or soft delete entry
     * @param int $spaceId
     * @param string $status
     * @throws Exception
     */
    public function updateStatus(int $spaceId, string $status): void
    {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($spaceId, 'spaceId');

            $space = DbMysql::select(self::$tableName, array('spaceId'=>$spaceId, 'isDeleted'=>0), true);
            parent::checkEmptyArray($space, 'space');

            $status = strtoupper(trim($status));
            if (!in_array($status, array(self::STATUS_AVAILABLE, self::STATUS_ACTIVE, self::STATUS_RESERVED, self::STATUS_DISABLED), true)) {
                throw new Exception('Invalid space status supplied', 31);
            }

            if (!$this->isAdministrator()) {
                $this->validateSiteAccess(intval($space['siteId']));
            }

            DbMysql::update(self::$tableName, array(
                'spaceStatus' => $status,
                'spaceUpdatedBy' => $this->userId,
                'spaceUpdatedAt' => 'NOW()'
            ), array('spaceId'=>$spaceId));

            $auditId = $status === self::STATUS_DISABLED ? 72 : 71;
            $this->saveAudit($auditId, 'Space = '.$space['spaceName'].' (status '.$status.')');
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Soft delete
     * @param int $spaceId
     * @throws Exception
     */
    public function delete(int $spaceId): void
    {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($spaceId, 'spaceId');

            $space = DbMysql::select(self::$tableName, array('spaceId'=>$spaceId, 'isDeleted'=>0), true);
            parent::checkEmptyArray($space, 'space');

            if (!$this->isAdministrator()) {
                $this->validateSiteAccess(intval($space['siteId']));
            }

            DbMysql::update(self::$tableName, array(
                'isDeleted' => 1,
                'spaceStatus' => self::STATUS_DISABLED,
                'spaceUpdatedBy' => $this->userId,
                'spaceUpdatedAt' => 'NOW()'
            ), array('spaceId'=>$spaceId));

            $this->saveAudit(72, 'Space soft deleted = '.$space['spaceName']);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Create reservation with availability check
     * @param int $spaceId
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function reserve(int $spaceId, array $params): array
    {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($spaceId, 'spaceId');
            parent::checkMandatoryArray($params, array('startDateTime', 'endDateTime'));

            $space = DbMysql::select(self::$tableName, array('spaceId'=>$spaceId, 'isDeleted'=>0), true);
            parent::checkEmptyArray($space, 'space');

            $siteId = intval($space['siteId']);
            if (!$this->isAdministrator()) {
                $this->validateSiteAccess($siteId);
            }

            $start = new DateTime($params['startDateTime']);
            $end = new DateTime($params['endDateTime']);
            if ($end <= $start) {
                throw new Exception(Constant::$spaceReservation['errInvalidWindow'], 31);
            }
            if ($start < new DateTime()) {
                throw new Exception(Constant::$spaceReservation['errPast'], 31);
            }

            $this->assertAvailability($spaceId, $start, $end);

            $insertArr = array(
                'spaceId' => $spaceId,
                'siteId' => $siteId,
                'reservationStart' => $start->format('Y-m-d H:i:s'),
                'reservationEnd' => $end->format('Y-m-d H:i:s'),
                'reservationStatus' => self::RESERVATION_RESERVED,
                'specialRequest' => $this->nullIfEmpty($params['specialRequest'] ?? null),
                'requestedBy' => $this->userId,
                'requestedByName' => $this->nullIfEmpty($params['contactName'] ?? ($params['requestedByName'] ?? null)),
                'requestedByContact' => $this->nullIfEmpty($params['contactInfo'] ?? ($params['requestedByContact'] ?? null)),
                'autoApprovedAt' => 'NOW()',
                'createdAt' => 'NOW()'
            );

            $reservationId = DbMysql::insert(self::$reservationTable, $insertArr);
            $this->saveAudit(73, 'Space reservation auto-approved (Space '.$space['spaceName'].', Reservation #'.$reservationId.')');
            $reservation = $this->getReservation($reservationId);

            // Email notifications: Created (attach ICS)
            try {
                $email = new Email($this->userId, $this->isLogged);
                $paramsEmail = array(
                    'space_name' => $space['spaceName'],
                    'location_name' => DbMysql::selectColumn('ref_space_location', array('spaceLocationId'=>intval($space['spaceLocationId'])), 'spaceLocationName'),
                    'reservation_start' => $reservation['reservationStart'],
                    'reservation_end' => $reservation['reservationEnd']
                );
                // Build ICS invite
                $icsPath = $this->buildReservationIcs($space, $reservation, 'CREATED');
                if (!empty($icsPath)) {
                    $paramsEmail['emailAttachment'] = $icsPath;
                    $paramsEmail['emailFilename'] = 'Reservation_'.$reservationId.'.ics';
                }
                // requester
                $email->prepare(intval($this->userId), self::EMAIL_TEMPLATE_RESERVATION_CREATED, $paramsEmail);
                // space owner/maintainer (if stored); fallback to site manager role by convention
                $siteManagerId = DbMysql::selectColumn('sys_user_role', array('siteId'=>intval($siteId), 'roleId'=>'IN|1,10'), 'userId');
                if (!empty($siteManagerId)) { $email->prepare(intval($siteManagerId), self::EMAIL_TEMPLATE_RESERVATION_CREATED, $paramsEmail); }
            } catch (Throwable $ex) { /* log only */ DbMysql::logError(__CLASS__, __FUNCTION__, __LINE__, 'Email prepare error: '.$ex->getMessage()); }

            return $reservation;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Cancel reservation by admin
     * @param int $reservationId
     * @param string|null $reason
     * @return array
     * @throws Exception
     */
    public function cancelReservation(int $reservationId, ?string $reason = null): array
    {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($reservationId, 'reservationId');

            $reservation = $this->getReservation($reservationId, true);
            if (empty($reservation)) {
                throw new Exception('Reservation not found', 31);
            }

            if (!$this->isAdministrator()) {
                // Non-admins can only cancel their own reservation and must have site access
                $this->validateSiteAccess(intval($reservation['siteId']));
                if (intval($reservation['requestedBy']) !== intval($this->userId)) {
                    throw new Exception('You can only cancel your own reservation', 31);
                }
            }

            if ($reservation['reservationStatus'] === self::RESERVATION_CANCELED) {
                return $reservation;
            }

            DbMysql::update(self::$reservationTable, array(
                'reservationStatus' => self::RESERVATION_CANCELED,
                'canceledBy' => $this->userId,
                'canceledAt' => 'NOW()',
                'cancelReason' => $this->nullIfEmpty($reason)
            ), array('reservationId'=>$reservationId));

            $this->saveAudit(74, 'Space reservation canceled (#'.$reservationId.')');
            $reservation = $this->getReservation($reservationId);

            // Email notifications: Canceled (attach ICS cancellation notice)
            try {
                $space = DbMysql::select(self::$tableName, array('spaceId'=>intval($reservation['spaceId'])), true);
                $email = new Email($this->userId, $this->isLogged);
                $paramsEmail = array(
                    'space_name' => $space['spaceName'],
                    'location_name' => DbMysql::selectColumn('ref_space_location', array('spaceLocationId'=>intval($space['spaceLocationId'])), 'spaceLocationName'),
                    'reservation_start' => $reservation['reservationStart'],
                    'reservation_end' => $reservation['reservationEnd'],
                    'cancel_reason' => $reason ?? ''
                );
                $icsPath = $this->buildReservationIcs($space, $reservation, 'CANCELED');
                if (!empty($icsPath)) {
                    $paramsEmail['emailAttachment'] = $icsPath;
                    $paramsEmail['emailFilename'] = 'Reservation_'.$reservationId.'_cancel.ics';
                }
                // requester
                $email->prepare(intval($reservation['requestedBy']), self::EMAIL_TEMPLATE_RESERVATION_CANCELED, $paramsEmail);
                // space/site manager
                $siteManagerId = DbMysql::selectColumn('sys_user_role', array('siteId'=>intval($reservation['siteId']), 'roleId'=>'IN|1,10'), 'userId');
                if (!empty($siteManagerId)) { $email->prepare(intval($siteManagerId), self::EMAIL_TEMPLATE_RESERVATION_CANCELED, $paramsEmail); }
            } catch (Throwable $ex) { DbMysql::logError(__CLASS__, __FUNCTION__, __LINE__, 'Email prepare error: '.$ex->getMessage()); }

            return $reservation;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Fetch current user's reservations (optionally filtered by window/status/site)
     * @param ?DateTime $from
     * @param ?DateTime $to
     * @param ?string $status
     * @param ?int $siteId
     * @return array
     * @throws Exception
     */
    public function getMyReservations(?DateTime $from = null, ?DateTime $to = null, ?string $status = null, ?int $siteId = null): array
    {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $where = array('requestedBy' => intval($this->userId));
            if ($from instanceof DateTime) { $where['reservationEnd'] = '>=|'.$from->format('Y-m-d H:i:s'); }
            if ($to instanceof DateTime) { $where['reservationStart'] = '<=|'.$to->format('Y-m-d H:i:s'); }
            if (!empty($status)) { $where['reservationStatus'] = strtoupper($status); }
            if (!empty($siteId)) {
                $where['siteId'] = intval($siteId);
                if (!$this->isAdministrator()) {
                    // Non-admins: enforce user's site if provided and must match
                    $this->validateSiteAccess(intval($siteId));
                }
            } else if (!$this->isAdministrator() && $this->userSite) {
                // Default site enforcement for non-admins
                $where['siteId'] = intval($this->userSite);
            }

            // Include space name for display
            $sql = /** @lang text */
                "SELECT r.*, s.space_name AS spaceName\n"
                . "FROM spc_reservation r\n"
                . "LEFT JOIN spc_space s ON s.space_id = r.space_id\n"
                . "WHERE r.requested_by = :requestedBy";

            $params = array(':requestedBy' => intval($this->userId));
            if (isset($where['reservationEnd'])) { $sql .= " AND r.reservation_end ".(str_starts_with($where['reservationEnd'], '>=|')?'>=':'')." :resEnd"; $params[':resEnd'] = substr($where['reservationEnd'], 3); }
            if (isset($where['reservationStart'])) { $sql .= " AND r.reservation_start ".(str_starts_with($where['reservationStart'], '<=|')?'<=':'')." :resStart"; $params[':resStart'] = substr($where['reservationStart'], 3); }
            if (isset($where['reservationStatus'])) { $sql .= " AND r.reservation_status = :status"; $params[':status'] = $where['reservationStatus']; }
            if (isset($where['siteId'])) { $sql .= " AND r.site_id = :siteId"; $params[':siteId'] = $where['siteId']; }
            $sql .= " ORDER BY r.reservation_start DESC";

            $stmt = DbMysql::$DBH->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt = null;
            return $rows;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Fetch reservations window for calendar
     * @param int $spaceId
     * @param ?DateTime $from
     * @param ?DateTime $to
     * @return array
     * @throws Exception
     */
    public function getReservations(int $spaceId, ?DateTime $from = null, ?DateTime $to = null): array
    {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($spaceId, 'spaceId');

            $where = array('spaceId'=>$spaceId);
            if ($from instanceof DateTime) {
                $where['reservationEnd'] = '>=|'.$from->format('Y-m-d H:i:s');
            }
            if ($to instanceof DateTime) {
                $where['reservationStart'] = '<=|'.$to->format('Y-m-d H:i:s');
            }

            return DbMysql::selectAll(self::$reservationTable, $where, 0, false, 'reservationStart');
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Internal helper: get single reservation
     * @param int $reservationId
     * @param bool $internal
     * @return array
     * @throws Exception
     */
    private function getReservation(int $reservationId, bool $internal = false): array
    {
        try {
            $reservation = DbMysql::select(self::$reservationTable, array('reservationId'=>$reservationId));
            if (!$internal && empty($reservation)) {
                throw new Exception('Reservation not found', 31);
            }
            return $reservation;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Ensure no overlapping reservation exists
     * @param int $spaceId
     * @param DateTime $start
     * @param DateTime $end
     * @throws Exception
     */
    private function assertAvailability(int $spaceId, DateTime $start, DateTime $end): void
    {
        try {
            $stmt = DbMysql::$DBH->prepare(
                'SELECT COUNT(*) AS cnt FROM spc_reservation 
                 WHERE space_id = ? AND reservation_status = ? 
                   AND reservation_start < ? AND reservation_end > ?'
            );
            $stmt->execute(array($spaceId, self::RESERVATION_RESERVED, $end->format('Y-m-d H:i:s'), $start->format('Y-m-d H:i:s')));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt = null;
            if (intval($row['cnt'] ?? 0) > 0) {
                throw new Exception(Constant::$spaceReservation['errOverlap'], 31);
            }
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Ensure no overlapping reservation exists excluding a specific reservation id
     * @param int $spaceId
     * @param DateTime $start
     * @param DateTime $end
     * @param int $excludeReservationId
     * @throws Exception
     */
    private function assertAvailabilityExcept(int $spaceId, DateTime $start, DateTime $end, int $excludeReservationId): void
    {
        try {
            $stmt = DbMysql::$DBH->prepare(
                'SELECT COUNT(*) AS cnt FROM spc_reservation 
                 WHERE space_id = ? AND reservation_status = ? AND reservation_id <> ?
                   AND reservation_start < ? AND reservation_end > ?'
            );
            $stmt->execute(array($spaceId, self::RESERVATION_RESERVED, $excludeReservationId, $end->format('Y-m-d H:i:s'), $start->format('Y-m-d H:i:s')));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt = null;
            if (intval($row['cnt'] ?? 0) > 0) {
                throw new Exception(Constant::$spaceReservation['errOverlap'], 31);
            }
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Reschedule an existing reservation (owner or admin)
     * @param int $reservationId
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function rescheduleReservation(int $reservationId, array $params): array
    {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($reservationId, 'reservationId');
            parent::checkMandatoryArray($params, array('startDateTime', 'endDateTime'));

            $reservation = $this->getReservation($reservationId, true);
            if (empty($reservation)) {
                throw new Exception('Reservation not found', 31);
            }

            $spaceId = intval($reservation['spaceId']);
            $siteId = intval($reservation['siteId']);

            if (!$this->isAdministrator()) {
                $this->validateSiteAccess($siteId);
                if (intval($reservation['requestedBy']) !== intval($this->userId)) {
                    throw new Exception('You can only reschedule your own reservation', 31);
                }
            }

            if (($reservation['reservationStatus'] ?? '') === self::RESERVATION_CANCELED) {
                throw new Exception('Cannot reschedule a canceled reservation', 31);
            }

            $start = new DateTime($params['startDateTime']);
            $end = new DateTime($params['endDateTime']);
            if ($end <= $start) {
                throw new Exception(Constant::$spaceReservation['errInvalidWindow'], 31);
            }
            if ($start < new DateTime()) {
                throw new Exception(Constant::$spaceReservation['errPast'], 31);
            }

            $this->assertAvailabilityExcept($spaceId, $start, $end, $reservationId);

            $oldStart = $reservation['reservationStart'];
            $oldEnd = $reservation['reservationEnd'];
            DbMysql::update(self::$reservationTable, array(
                'reservationStart' => $start->format('Y-m-d H:i:s'),
                'reservationEnd' => $end->format('Y-m-d H:i:s')
            ), array('reservationId'=>$reservationId));

            $this->saveAudit(75, 'Space reservation rescheduled (#'.$reservationId.')');
            $reservation = $this->getReservation($reservationId);

            // Email notifications: Updated (attach ICS with new time)
            try {
                $space = DbMysql::select(self::$tableName, array('spaceId'=>intval($reservation['spaceId'])), true);
                $email = new Email($this->userId, $this->isLogged);
                $paramsEmail = array(
                    'space_name' => $space['spaceName'],
                    'location_name' => DbMysql::selectColumn('ref_space_location', array('spaceLocationId'=>intval($space['spaceLocationId'])), 'spaceLocationName'),
                    'old_start' => $oldStart,
                    'old_end' => $oldEnd,
                    'reservation_start' => $reservation['reservationStart'],
                    'reservation_end' => $reservation['reservationEnd']
                );
                $icsPath = $this->buildReservationIcs($space, $reservation, 'UPDATED');
                if (!empty($icsPath)) {
                    $paramsEmail['emailAttachment'] = $icsPath;
                    $paramsEmail['emailFilename'] = 'Reservation_'.$reservationId.'_update.ics';
                }
                // requester
                $email->prepare(intval($reservation['requestedBy']), self::EMAIL_TEMPLATE_RESERVATION_UPDATED, $paramsEmail);
                // site manager
                $siteManagerId = DbMysql::selectColumn('sys_user_role', array('siteId'=>intval($reservation['siteId']), 'roleId'=>'IN|1,10'), 'userId');
                if (!empty($siteManagerId)) { $email->prepare(intval($siteManagerId), self::EMAIL_TEMPLATE_RESERVATION_UPDATED, $paramsEmail); }
            } catch (Throwable $ex) { DbMysql::logError(__CLASS__, __FUNCTION__, __LINE__, 'Email prepare error: '.$ex->getMessage()); }

            return $reservation;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Synchronise asset linkage
     * @param int $spaceId
     * @param array $assetIds
     * @param int $siteId
     * @return void
     * @throws Exception
     */
    private function syncAssets(int $spaceId, array $assetIds, int $siteId): void
    {
        try {
            $assetIds = array_filter(array_map('intval', $assetIds));
            $assetIds = array_values(array_unique($assetIds));

            if (!empty($assetIds)) {
                $assetSql = /** @lang text */
                    "SELECT a.asset_id AS assetId, c.site_id AS siteId
                    FROM ast_asset a
                    LEFT JOIN cli_contract c ON c.contract_id = a.contract_id
                    WHERE a.asset_id IN (".implode(',', array_fill(0, count($assetIds), '?')).")";

                $stmtValues = $assetIds;
                DbMysql::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Validating asset ids = '.json_encode($assetIds));

                $stmt = DbMysql::$DBH->prepare($assetSql);
                $stmt->execute($stmtValues);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $stmt = null;

                if (count($rows) !== count($assetIds)) {
                    throw new Exception('One or more assets not found', 31);
                }

                foreach ($rows as $row) {
                    if (!$this->isAdministrator() && $this->userSite && intval($row['siteId']) !== $this->userSite) {
                        throw new Exception('Asset '.$row['assetId'].' belongs to different site', 31);
                    }
                    if (intval($row['siteId']) !== $siteId) {
                        throw new Exception('Asset '.$row['assetId'].' not linked to the same site as space', 31);
                    }
                }
            }

            // current assets
            $currentAssetRows = DbMysql::selectAll(self::$assetTable, array('spaceId'=>$spaceId));
            $currentAssetIds = array_map(static function ($row) {
                return intval($row['assetId']);
            }, $currentAssetRows);

            $toInsert = array_diff($assetIds, $currentAssetIds);
            $toDelete = array_diff($currentAssetIds, $assetIds);

            foreach ($toInsert as $assetId) {
                DbMysql::insert(self::$assetTable, array(
                    'spaceId' => $spaceId,
                    'assetId' => $assetId,
                    'linkedBy' => $this->userId,
                    'linkedAt' => 'NOW()'
                ));
            }

            foreach ($toDelete as $assetId) {
                DbMysql::delete(self::$assetTable, array('spaceId'=>$spaceId, 'assetId'=>$assetId));
            }
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Synchronise media uploads (photos/floorplan)
     * @param int $spaceId
     * @param array $params
     * @return void
     * @throws Exception
     */
    private function syncMedia(int $spaceId, array $params): void
    {
        try {
            $hasCoverParam = array_key_exists('coverMediaId', $params);
            $coverMediaId = $hasCoverParam ? intval($params['coverMediaId']) : null;

            // Remove photos if requested
            if (!empty($params['photosRemove']) && is_array($params['photosRemove'])) {
                foreach ($params['photosRemove'] as $mediaId) {
                    $this->deleteMedia(intval($mediaId));
                }
            }

            // Add new photos
            if (!empty($params['photos']) && is_array($params['photos'])) {
                $this->storeMediaBatch($spaceId, $params['photos'], 'PHOTO');
            }

            // Replace floorplan if provided
            if (!empty($params['floorplan']) && is_array($params['floorplan'])) {
                $existingFloorplans = DbMysql::selectAll(self::$mediaTable, array('spaceId'=>$spaceId, 'mediaType'=>'FLOORPLAN'));
                foreach ($existingFloorplans as $media) {
                    $this->deleteMedia(intval($media['spaceMediaId']));
                }
                $this->storeMediaBatch($spaceId, array($params['floorplan']), 'FLOORPLAN');
            }

            if ($hasCoverParam) {
                if ($coverMediaId !== null && $coverMediaId > 0) {
                    $coverMedia = DbMysql::select(self::$mediaTable, array('spaceMediaId'=>$coverMediaId, 'spaceId'=>$spaceId), true);
                    parent::checkEmptyArray($coverMedia, 'spaceMedia');
                    $mediaType = strtoupper($coverMedia['mediaType'] ?? '');
                    if ($mediaType !== 'PHOTO') {
                        throw new Exception('Only photo media can be selected as cover', 31);
                    }
                }

                // Reset existing covers for this space's photos
                DbMysql::update(self::$mediaTable, array('isCover'=>0), array('spaceId'=>$spaceId, 'mediaType'=>'PHOTO'));

                if (!empty($coverMediaId)) {
                    DbMysql::update(self::$mediaTable, array('isCover'=>1), array('spaceMediaId'=>$coverMediaId));
                }
            }
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Persist batch media uploads
     * @param int $spaceId
     * @param array $mediaArr
     * @param string $type
     * @return void
     * @throws Exception
     */
    private function storeMediaBatch(int $spaceId, array $mediaArr, string $type): void
    {
        try {
            $type = strtoupper($type);
            // Ensure required ref_document entry exists to satisfy FK on sys_upload.document_id
            $this->ensureRefDocumentExists();
            foreach ($mediaArr as $index => $media) {
                if (!is_array($media)) {
                    continue;
                }
                parent::checkMandatoryArray($media, array('name', 'filename', 'type', 'size', 'data'));

                $preparedUpload = $this->uploadPrepare($media, self::$documentId);
                $folder = $type === 'FLOORPLAN' ? 'space/'.$spaceId.'/floorplan' : 'space/'.$spaceId.'/photo';
                $filename = $type.'_'.date('YmdHis').'_'.$this->userId.'_'.$index;
                $uploadId = $this->uploadSave($preparedUpload, $folder, $filename);

                DbMysql::insert(self::$mediaTable, array(
                    'spaceId' => $spaceId,
                    'uploadId' => $uploadId,
                    'mediaType' => $type,
                    'mediaCaption' => $this->nullIfEmpty($media['caption'] ?? null),
                    'mediaCreatedBy' => $this->userId,
                    'mediaCreatedAt' => 'NOW()'
                ));
            }
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Ensure ref_document record for Space uploads exists (id = self::$documentId)
     * to avoid FK constraint failure on sys_upload inserts in environments
     * where the SQL migration hasn't been applied.
     */
    private function ensureRefDocumentExists(): void
    {
        try {
            $doc = DbMysql::select('ref_document', array('documentId' => self::$documentId), true);
            if (empty($doc)) {
                DbMysql::insert('ref_document', array(
                    'documentId' => self::$documentId,
                    'documentDesc' => 'Space Media',
                    'documentType' => 'Space',
                    'documentStatus' => 1
                ));
            }
        } catch (Exception|Throwable $ex) {
            // Swallow and let upper layer handle if DB fails for other reasons
            // This guard is best-effort; upload will fail with clear DB error otherwise
        }
    }

    /**
     * Delete media (and underlying upload + file)
     * @param int $spaceMediaId
     * @throws Exception
     */
    private function deleteMedia(int $spaceMediaId): void
    {
        try {
            parent::checkEmptyInteger($spaceMediaId, 'spaceMediaId');
            $media = DbMysql::select(self::$mediaTable, array('spaceMediaId'=>$spaceMediaId), true);
            if (empty($media)) {
                return;
            }

            $upload = $this->getUpload(intval($media['uploadId']));
            if ($upload['fileExist']) {
                $filePath = $upload['uploadFolder'].'/'.$upload['uploadFilename'].'.'.$upload['uploadExtension'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            DbMysql::delete(self::$mediaTable, array('spaceMediaId'=>$spaceMediaId));
            DbMysql::delete('sys_upload', array('uploadId'=>$media['uploadId']));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Retrieve linked assets info
     * @param int $spaceId
     * @return array
     * @throws Exception
     */
    private function getAssets(int $spaceId): array
    {
        try {
            parent::checkEmptyInteger($spaceId, 'spaceId');
            $sql = /** @lang text */
                "SELECT 
                    sa.space_asset_id AS spaceAssetId,
                    sa.asset_id       AS assetId,
                    a.asset_name      AS assetName,
                    a.asset_no        AS assetNo,
                    a.asset_serial_no AS assetSerialNo,
                    sa.linked_at      AS linkedAt,
                    sa.linked_by      AS linkedBy
                FROM spc_space_asset sa
                LEFT JOIN ast_asset a ON a.asset_id = sa.asset_id";

            return DbMysql::selectSqlAll($sql, array('spaceId'=>$spaceId), 0, false, 'assetName');
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Retrieve media entries with download URLs
     * @param int $spaceId
     * @return array
     * @throws Exception
     */
    private function getMedia(int $spaceId): array
    {
        try {
            parent::checkEmptyInteger($spaceId, 'spaceId');
            $mediaList = DbMysql::selectAll(self::$mediaTable, array('spaceId'=>$spaceId), 0, false, 'mediaCreatedAt', 'DESC');
            foreach ($mediaList as &$media) {
                $media['downloadUrl'] = $this->getUploadLink(intval($media['uploadId']));
                $mediaType = strtoupper($media['mediaType'] ?? ($media['media_type'] ?? ''));
                $isCoverFlag = intval($media['isCover'] ?? ($media['is_cover'] ?? 0)) === 1;
                $media['isCover'] = ($mediaType === 'PHOTO' && $isCoverFlag) ? 1 : 0;
            }
            return $mediaList;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Retrieve latest photo for each supplied space id
     * @param array $spaceIds
     * @return array<int,array{url:string,caption:string,uploadId:int|null}>
     * @throws Exception
     */
    private function getCoverPhotoMap(array $spaceIds): array
    {
        try {
            if (empty($spaceIds)) {
                return array();
            }
            $filtered = array();
            foreach ($spaceIds as $sid) {
                $sid = intval($sid);
                if ($sid > 0) {
                    $filtered[$sid] = $sid;
                }
            }
            if (empty($filtered)) {
                return array();
            }
            $where = array(
                'spaceId' => 'IN|'.implode(',', $filtered),
                'mediaType' => 'PHOTO'
            );
            $mediaList = DbMysql::selectAll(self::$mediaTable, $where, 0, false, 'mediaCreatedAt', 'DESC');
            $map = array();
            foreach ($mediaList as $media) {
                $sid = intval($media['spaceId'] ?? 0);
                if ($sid <= 0) {
                    continue;
                }
                $isCover = intval($media['isCover'] ?? ($media['is_cover'] ?? 0)) === 1;
                if (isset($map[$sid]) && !$isCover) {
                    continue;
                }
                $uploadId = intval($media['uploadId'] ?? 0);
                $map[$sid] = array(
                    'url' => $this->getUploadLink($uploadId),
                    'caption' => $media['mediaCaption'] ?? '',
                    'uploadId' => $uploadId > 0 ? $uploadId : null
                );
            }
            return $map;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Return next reservations (default 5)
     * @param int $spaceId
     * @param int $limit
     * @return array
     * @throws Exception
     */
    private function getUpcomingReservations(int $spaceId, int $limit = 5): array
    {
        try {
            $where = array(
                'spaceId' => $spaceId,
                'reservationStatus' => self::RESERVATION_RESERVED,
                'reservationStart' => '>=|'.(new DateTime())->format('Y-m-d H:i:s')
            );
            return DbMysql::selectAll(self::$reservationTable, $where, 0, false, 'reservationStart', 'ASC', strval($limit));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Build a simple ICS calendar invite for a reservation and save it to a temp folder.
     * Returns absolute file path or empty string on failure.
     * @param array $space Row from spc_space with keys like spaceName, siteId, spaceLocationId
     * @param array $reservation Row from spc_reservation with start/end and requestedBy
     * @param string $eventType CREATED|UPDATED|CANCELED (mapped to METHOD)
     * @return string
     */
    private function buildReservationIcs(array $space, array $reservation, string $eventType = 'CREATED'): string
    {
        try {
            $uid = 'RES-' . ($reservation['reservationId'] ?? uniqid());
            $summary = 'Reservation: ' . ($space['spaceName'] ?? 'Space');
            $locationName = DbMysql::selectColumn('ref_space_location', array('spaceLocationId'=>intval($space['spaceLocationId'] ?? 0)), 'spaceLocationName');
            $location = !empty($locationName) ? $locationName : 'N/A';
            $desc = 'Space reservation for ' . ($space['spaceName'] ?? 'Space');
            $start = new DateTime($reservation['reservationStart']);
            $end = new DateTime($reservation['reservationEnd']);
            $dtStamp = (new DateTime())->setTimezone(new DateTimeZone('UTC'));
            $dtStartUtc = (clone $start)->setTimezone(new DateTimeZone('UTC'));
            $dtEndUtc = (clone $end)->setTimezone(new DateTimeZone('UTC'));
            $method = 'REQUEST';
            $statusLine = '';
            if ($eventType === 'CANCELED') {
                $method = 'CANCEL';
                $statusLine = "STATUS:CANCELLED\r\n";
            }

            // Organizer and attendee: we use requestedBy email if available
            $attendeeEmail = DbMysql::selectColumn('sys_user_profile', array('userId'=>intval($reservation['requestedBy'] ?? 0), 'user_profile_status'=>'1'), 'userEmail');
            $organizerEmail = DbMysql::selectColumn('sys_user_profile', array('userId'=>intval($this->userId), 'user_profile_status'=>'1'), 'userEmail');
            $attendeeLine = !empty($attendeeEmail) ? ('ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=ACCEPTED;CN=' . $attendeeEmail . ':MAILTO:' . $attendeeEmail . "\r\n") : '';
            $organizerLine = !empty($organizerEmail) ? ('ORGANIZER:MAILTO:' . $organizerEmail . "\r\n") : '';

            $ics = "BEGIN:VCALENDAR\r\n".
                "PRODID:-//GEMS2//Space Reservation//EN\r\n".
                "VERSION:2.0\r\n".
                "CALSCALE:GREGORIAN\r\n".
                "METHOD:" . $method . "\r\n".
                "BEGIN:VEVENT\r\n".
                "UID:" . $uid . "@gems2\r\n".
                "DTSTAMP:" . $dtStamp->format('Ymd\THis\Z') . "\r\n".
                "DTSTART:" . $dtStartUtc->format('Ymd\THis\Z') . "\r\n".
                "DTEND:" . $dtEndUtc->format('Ymd\THis\Z') . "\r\n".
                $organizerLine.
                $attendeeLine.
                $statusLine.
                "SUMMARY:" . $this->escapeIcsText($summary) . "\r\n".
                "LOCATION:" . $this->escapeIcsText($location) . "\r\n".
                "DESCRIPTION:" . $this->escapeIcsText($desc) . "\r\n".
                "END:VEVENT\r\n".
                "END:VCALENDAR\r\n";

            $folder = __DIR__ . '/../../tmp/ics';
            if (!is_dir($folder)) {
                @mkdir($folder, 0777, true);
            }
            $fname = $folder . '/' . $uid . '.ics';
            if (@file_put_contents($fname, $ics) === false) {
                return '';
            }
            return realpath($fname) ?: $fname;
        } catch (Throwable $ex) {
            DbMysql::logError(__CLASS__, __FUNCTION__, __LINE__, 'ICS build error: ' . $ex->getMessage());
            return '';
        }
    }

    /**
     * Escape text for ICS fields per RFC (commas, semicolons, newlines)
     */
    private function escapeIcsText(string $text): string
    {
        $text = str_replace(["\r\n", "\n", "\r"], "\\n", $text);
        $text = str_replace([',', ';'], ['\\,', '\\;'], $text);
        return $text;
    }

    /**
     * Utility: convert empty string to null
     * @param mixed $value
     * @return mixed
     */
    private function nullIfEmpty($value)
    {
        if ($value === '' || $value === null) {
            return null;
        }
        return $value;
    }
}

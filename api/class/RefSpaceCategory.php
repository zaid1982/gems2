<?php

class RefSpaceCategory extends General {

    private static string $table = 'ref_space_category';
    private static string $id = 'space_category_id';

    public function __construct(int $userId = 0, bool $isLogged = false)
    {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @param array $filters
     * @return array
     * @throws Exception
     */
    public function list(array $filters = array()): array {
        try {
            $where = array();
            if (isset($filters['status'])) {
                $where['spaceCategoryStatus'] = intval($filters['status']) === 1 ? 1 : 0;
            }
            return DbMysql::selectAll(self::$table, $where, 0, false, 'spaceCategoryName');
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @throws Exception
     */
    public function get(int $id): array {
        try {
            parent::checkEmptyInteger($id, 'spaceCategoryId');
            return DbMysql::select(self::$table, array('spaceCategoryId'=>$id));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @throws Exception
     */
    public function create(array $params): array {
        try {
            parent::checkMandatoryArray($params, array('spaceCategoryName'));
            $insert = array(
                'spaceCategoryName' => trim($params['spaceCategoryName']),
                'spaceCategoryDesc' => $params['spaceCategoryDesc'] ?? null,
                'spaceCategoryStatus' => isset($params['spaceCategoryStatus']) ? (intval($params['spaceCategoryStatus']) ? 1 : 0) : 1,
                'createdBy' => $this->userId
            );
            $newId = DbMysql::insert(self::$table, $insert);
            return $this->get(intval($newId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @throws Exception
     */
    public function update(int $id, array $params): array {
        try {
            parent::checkEmptyInteger($id, 'spaceCategoryId');
            $update = array();
            if (isset($params['spaceCategoryName'])) { $update['spaceCategoryName'] = trim($params['spaceCategoryName']); }
            if (array_key_exists('spaceCategoryDesc', $params)) { $update['spaceCategoryDesc'] = $params['spaceCategoryDesc']; }
            if (isset($params['spaceCategoryStatus'])) { $update['spaceCategoryStatus'] = intval($params['spaceCategoryStatus']) ? 1 : 0; }
            if (empty($update)) { return $this->get($id); }
            $update['updatedBy'] = $this->userId;
            $update['updatedAt'] = date('Y-m-d H:i:s');
            DbMysql::update(self::$table, $update, array('spaceCategoryId'=>$id));
            return $this->get($id);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @throws Exception
     */
    public function delete(int $id): void {
        try {
            parent::checkEmptyInteger($id, 'spaceCategoryId');
            DbMysql::delete(self::$table, array('spaceCategoryId'=>$id));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}

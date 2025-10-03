<?php

class RefSpaceType extends General {

    private static string $table = 'ref_space_type';
    private static string $id = 'space_type_id';

    public function __construct(int $userId = 0, bool $isLogged = false)
    {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    public function list(array $filters = array()): array {
        try {
            $where = array();
            if (isset($filters['status'])) {
                $where['spaceTypeStatus'] = intval($filters['status']) === 1 ? 1 : 0;
            }
            if (isset($filters['spaceCategoryId'])) {
                $where['spaceCategoryId'] = intval($filters['spaceCategoryId']);
            }
            return DbMysql::selectAll(self::$table, $where, 0, false, 'spaceTypeName');
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    public function get(int $id): array {
        try {
            parent::checkEmptyInteger($id, 'spaceTypeId');
            return DbMysql::select(self::$table, array('spaceTypeId'=>$id));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    public function create(array $params): array {
        try {
            parent::checkMandatoryArray($params, array('spaceCategoryId','spaceTypeName'));
            $insert = array(
                'spaceCategoryId' => intval($params['spaceCategoryId']),
                'spaceTypeName' => trim($params['spaceTypeName']),
                'spaceTypeDesc' => $params['spaceTypeDesc'] ?? null,
                'spaceTypeStatus' => isset($params['spaceTypeStatus']) ? (intval($params['spaceTypeStatus']) ? 1 : 0) : 1,
                'createdBy' => $this->userId
            );
            $newId = DbMysql::insert(self::$table, $insert);
            return $this->get(intval($newId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    public function update(int $id, array $params): array {
        try {
            parent::checkEmptyInteger($id, 'spaceTypeId');
            $update = array();
            if (isset($params['spaceCategoryId'])) { $update['spaceCategoryId'] = intval($params['spaceCategoryId']); }
            if (isset($params['spaceTypeName'])) { $update['spaceTypeName'] = trim($params['spaceTypeName']); }
            if (array_key_exists('spaceTypeDesc', $params)) { $update['spaceTypeDesc'] = $params['spaceTypeDesc']; }
            if (isset($params['spaceTypeStatus'])) { $update['spaceTypeStatus'] = intval($params['spaceTypeStatus']) ? 1 : 0; }
            if (empty($update)) { return $this->get($id); }
            $update['updatedBy'] = $this->userId;
            $update['updatedAt'] = date('Y-m-d H:i:s');
            DbMysql::update(self::$table, $update, array('spaceTypeId'=>$id));
            return $this->get($id);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    public function delete(int $id): void {
        try {
            parent::checkEmptyInteger($id, 'spaceTypeId');
            DbMysql::delete(self::$table, array('spaceTypeId'=>$id));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}

<?php

class WoTaskParts extends General {

    public $woTaskPartId = 0;
    private static $tableName = 'wo_task_parts';

    function __construct (int $userId=0, bool $isLogged=false) {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }
}
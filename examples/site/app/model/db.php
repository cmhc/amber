<?php
/**
 * common db
 */
namespace app\model;

use amber\modules\DB\SQLite;

class db extends SQLite
{
    protected $dbFile = DB_PATH . '/main.db';
}
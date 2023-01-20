<?php

namespace Kronos\Keystore\Repository\DBAL;

use Doctrine\DBAL\Connection;

class Factory
{
    /**
     * @param Connection $connection
     * @param $tableName
     * @param $keyField
     * @param $valueField
     * @return Adaptor
     */
    public function createDBALAdaptor(Connection $connection, $tableName, $keyField, $valueField)
    {
        return new Adaptor($connection, $tableName, $keyField, $valueField);
    }
}

<?php

/*
 * This file is part of the PhoneBocx Queue package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhoneBocx\Queue\Pdo;

use PhoneBocx\Queue\NoItemAvailableException;

class SqlitePdoQueue extends PdoQueue
{
    /**
     * {@inheritdoc}
     */
    public function pop()
    {
        return $this->popByRef(false);
    }

    public function popByRef($ref = false)
    {
        $sql = 'SELECT id, item, serialized FROM ' . $this->tableName . ' WHERE eta <= :eta ';
        $params = ["eta" => time()];
        if ($ref !== false) {
            if ($ref === null) {
                $sql .= "and ref is null ";
            } else {
                $sql .= "and ref=:ref ";
                $params["ref"] = $ref;
            }
        }
        $sql .= "ORDER BY eta LIMIT 1";
        $p = $this->pdo->prepare($sql);

        $this->pdo->exec('BEGIN IMMEDIATE');

        try {
            $p->execute($params);
            $row = $p->fetch(\PDO::FETCH_ASSOC);
            $p->closeCursor();

            if ($row) {
                $sql = sprintf('DELETE FROM %s WHERE id = %d', $this->tableName, $row['id']);
                $this->pdo->exec($sql);
            }

            $this->pdo->exec('COMMIT');
        } catch (\Exception $e) {
            $this->pdo->exec('ROLLBACK');
            throw $e;
        }

        if ($row) {
            if ($row['serialized']) {
                return unserialize($row['item']);
            } else {
                return $row['item'];
            }
        }

        throw new NoItemAvailableException($this);
    }

    protected function supportsDriver($driverName)
    {
        return 'sqlite' === $driverName;
    }
}

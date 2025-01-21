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

use PhoneBocx\Queue\Queue;
use PhoneBocx\Queue\QueueUtils;

abstract class PdoQueue implements Queue
{
    /**
     * @var \PDO
     */
    protected $pdo;

    /**
     * @var string
     */
    protected $tableName;

    public function __construct(\PDO $pdo, $tableName)
    {
        $driverName = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if (!$this->supportsDriver($driverName)) {
            throw new \InvalidArgumentException(sprintf(
                'PDO driver "%s" is unsupported by "%s".',
                $driverName,
                get_class($this)
            ));
        }

        $this->pdo = $pdo;
        $this->tableName = $tableName;
    }

    /**
     * Get the raw PDO object
     *
     * @return \PDO
     */
    public function getPdo(): \PDO
    {
        return $this->pdo;
    }

    /**
     * Get the table name that is being used for this queue
     *
     * @return string
     */
    public function getTablename(): string
    {
        return $this->tableName;
    }

    /**
     * {@inheritdoc}
     */
    public function push($item, $eta = null, $ref = null)
    {
        if (is_object($item) || is_array($item)) {
            $serialized = 1;
            $item = serialize($item);
        } else {
            $serialized = 0;
        }

        $sql = sprintf('INSERT INTO %s (eta, item, serialized, ref) VALUES (:eta, :item, :ser, :ref)', $this->tableName);
        $q = $this->pdo->prepare($sql);
        $q->execute(["eta" => QueueUtils::normalizeEta($eta), "item" => $item, "ser" => $serialized, "ref" => $ref]);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(1) FROM ' . $this->tableName);
        $result = $stmt->fetchColumn();
        $stmt->closeCursor();

        return $result;
    }

    public function countRef($ref)
    {
        if ($ref === null) {
            $p = $this->pdo->prepare('SELECT COUNT(1) FROM ' . $this->tableName . ' where ref is null');
            $p->execute();
        } else {
            $p = $this->pdo->prepare('SELECT COUNT(1) FROM ' . $this->tableName . ' where ref=?');
            $p->execute([$ref]);
        }
        $result = $p->fetchColumn();
        $p->closeCursor();

        return $result;
    }

    public function getAllRefs()
    {
        $stmt = $this->pdo->query('SELECT distinct ref FROM ' . $this->tableName);
        $result = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $stmt->closeCursor();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->pdo->exec('DELETE FROM ' . $this->tableName);
    }

    /**
     * @param string $driverName
     *
     * @return bool
     */
    abstract protected function supportsDriver($driverName);
}

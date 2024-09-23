<?php

/*
 * This file is part of the PhoneBocx Queue package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhoneBocx\Queue;

interface Queue extends \Countable
{
    /**
     * Adds an item to the queue.
     *
     * @param mixed $item An item to be added.
     * @param mixed $eta  The earliest time that an item can be popped.
     * @param string $ref Optional reference to query
     */
    public function push($item, $eta = null, $ref = null);

    /**
     * Removes an item from the queue and returns it.
     *
     * @return mixed
     *
     * @throws NoItemAvailableException
     */
    public function pop();

    /**
     * Removes all items from the queue.
     */
    public function clear();
}

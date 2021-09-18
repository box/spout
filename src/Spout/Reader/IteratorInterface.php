<?php

namespace Box\Spout\Reader;

/**
 * Interface IteratorInterface
 * @template TValue
 * @extends  \Iterator<int, TValue>
 */
interface IteratorInterface extends \Iterator
{
    /**
     * Cleans up what was created to iterate over the object.
     *
     * @return void
     */
    public function end();
}

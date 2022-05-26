<?php

declare(strict_types=1);

namespace Box\Spout\Reader;

/**
 * Interface IteratorInterface
 */
interface SheetIteratorInterface extends IteratorInterface
{
    /**
     * Cleans up what was created to iterate over the object.
     *
     * @return void
     */
    public function end();

    /**
     * @return SheetInterface|null
     */
    public function current();
}

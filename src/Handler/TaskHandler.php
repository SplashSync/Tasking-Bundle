<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Tasking\Handler;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractHandler;
use Monolog\Logger;

/**
 * Buffers all records until task is completed.
 */
class TaskHandler extends AbstractHandler
{
    /**
     * @var int
     */
    protected $bufferSize = 0;

    /**
     * How many entries should be buffered at most, beyond that the oldest items are removed from the buffer.
     *
     * @var int
     */
    protected $bufferLimit = 1024;

    /**
     * If true, the buffer is flushed when the max size has been reached,
     * by default oldest entries are discarded
     *
     * @var bool
     */
    protected $flushOnOverflow;

    /**
     * @var string[]
     */
    protected $buffer = array();

    /**
     * @param int  $level           The minimum logging level at which this handler will be triggered
     * @param bool $flushOnOverflow If true, the buffer is flushed when the max size has been reached
     */
    public function __construct($level = Logger::INFO, $flushOnOverflow = false)
    {
        parent::__construct($level, true);
        $this->flushOnOverflow = $flushOnOverflow;
        $this->pushProcessor(array(
            new LineFormatter(null, null, false, true),
            'format'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $record): bool
    {
        if ($record['level'] < $this->level) {
            return false;
        }

        if ($this->bufferLimit > 0 && $this->bufferSize === $this->bufferLimit) {
            if ($this->flushOnOverflow) {
                $this->flush();
            } else {
                array_shift($this->buffer);
                $this->bufferSize--;
            }
        }

        if ($this->processors) {
            foreach ($this->processors as $processor) {
                $record = call_user_func($processor, $record);
            }
        }

        $this->buffer[] = $record;
        $this->bufferSize++;

        return false === $this->bubble;
    }

    /**
     * @return void
     */
    public function flush(): void
    {
        if (0 === $this->bufferSize) {
            return;
        }
        $this->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        $this->flush();
    }

    /**
     * Clears the buffer without flushing any messages down to the wrapped handler.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->bufferSize = 0;
        $this->buffer = array();
    }

    /**
     * Reset handler Buffer
     *
     * @return void
     */
    public function reset(): void
    {
        $this->flush();

        parent::reset();
    }

    /**
     * Reset handler Buffer
     *
     * @return string
     */
    public function getLogAsString(): string
    {
        return implode(" <br />", $this->buffer);
    }
}

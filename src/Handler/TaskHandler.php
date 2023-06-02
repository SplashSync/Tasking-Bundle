<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) Splash Sync  <www.splashsync.com>
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
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * Buffers all records until task is completed.
 *
 * @phpstan-import-type Level from \Monolog\Logger
 * @phpstan-import-type Record from \Monolog\Logger
 */
class TaskHandler extends AbstractProcessingHandler
{
    /**
     * @var int
     */
    protected int $bufferSize = 0;

    /**
     * How many entries should be buffered at most, beyond that the oldest items are removed from the buffer.
     *
     * @var int
     */
    protected int $bufferLimit = 1024;

    /**
     * If true, the buffer is flushed when the max size has been reached,
     * by default the oldest entries are discarded
     *
     * @var bool
     */
    protected bool $flushOnOverflow;

    /**
     * @var string[]
     */
    protected array $buffer = array();

    /**
     * @param int  $level           The minimum logging level at which this handler will be triggered
     * @param bool $flushOnOverflow If true, the buffer is flushed when the max size has been reached
     */
    public function __construct($level = Logger::INFO, bool $flushOnOverflow = false)
    {
        /** @phpstan-var Level $level */
        parent::__construct($level, true);
        $this->flushOnOverflow = $flushOnOverflow;
        $this->setFormatter(
            new LineFormatter(null, null, false, true)
        );
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

        /** @var Record $record */
        $this->write($record);

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
        if (0 === $this->bufferSize) {
            return "";
        }

        return "<br />".implode(" <br />", $this->buffer);
    }

    /**
     * @inheritDoc
     *
     * @phpstan-param  Record $record
     */
    protected function write(array $record): void
    {
        /** @phpstan-ignore-next-line  */
        $this->buffer[] = $this->processRecord($record)["message"] ?? "";
        $this->bufferSize++;
    }
}

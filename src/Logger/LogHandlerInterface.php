<?php

namespace PfinalClub\AsyncioGamekit\Logger;

/**
 * 日志处理器接口
 */
interface LogHandlerInterface
{
    /**
     * 处理日志记录
     * 
     * @param array $record 日志记录
     */
    public function handle(array $record): void;
}


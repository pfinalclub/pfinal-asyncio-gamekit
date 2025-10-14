<?php

namespace PfinalClub\AsyncioGamekit\Logger;

/**
 * 文件日志处理器
 */
class FileLogHandler implements LogHandlerInterface
{
    private string $logFile;
    private int $maxFileSize;
    private int $maxFiles;

    /**
     * @param string $logFile 日志文件路径
     * @param int $maxFileSize 单个日志文件最大大小（字节）
     * @param int $maxFiles 保留的日志文件数量
     */
    public function __construct(
        string $logFile,
        int $maxFileSize = 10 * 1024 * 1024, // 10MB
        int $maxFiles = 5
    ) {
        $this->logFile = $logFile;
        $this->maxFileSize = $maxFileSize;
        $this->maxFiles = $maxFiles;

        // 确保日志目录存在
        $dir = dirname($logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public function handle(array $record): void
    {
        // 检查文件大小，如果超过限制则轮转
        if (file_exists($this->logFile) && filesize($this->logFile) >= $this->maxFileSize) {
            $this->rotateFiles();
        }

        // 格式化日志
        $level = strtoupper($record['level']);
        $datetime = $record['datetime'];
        $message = $record['message'];

        $line = sprintf(
            "[%s] [%s] %s",
            $datetime,
            $level,
            $message
        );

        // 如果有上下文，追加上下文信息
        if (!empty($record['context'])) {
            $line .= ' ' . json_encode($record['context'], JSON_UNESCAPED_UNICODE);
        }

        $line .= "\n";

        // 写入文件
        file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * 轮转日志文件
     */
    private function rotateFiles(): void
    {
        // 删除最旧的文件
        $oldestFile = $this->logFile . '.' . $this->maxFiles;
        if (file_exists($oldestFile)) {
            unlink($oldestFile);
        }

        // 重命名现有文件
        for ($i = $this->maxFiles - 1; $i >= 1; $i--) {
            $oldFile = $this->logFile . '.' . $i;
            $newFile = $this->logFile . '.' . ($i + 1);
            if (file_exists($oldFile)) {
                rename($oldFile, $newFile);
            }
        }

        // 重命名当前文件
        if (file_exists($this->logFile)) {
            rename($this->logFile, $this->logFile . '.1');
        }
    }
}


<?php

namespace App\Model\Logger;

readonly class FileLogger
{
    /**
     * @param string $filePath
     * @param string $maxFileCount
     */
    public function __construct(
        private string $filePath,
        private string $maxFileCount
    ) {
    }

    /**
     * @param $message
     * @return void
     */
    public function logInfo($message): void
    {
        $this->log('INFO', $message);
    }

    /**
     * @param $message
     * @return void
     */
    public function logError($message): void
    {
        $this->log('ERROR', $message);
    }

    /**
     * @param $level
     * @param $message
     * @return void
     */
    private function log($level, $message): void
    {
        $date = new \DateTime();
        $formattedMessage = sprintf(
            "[%s] %s \n",
            $date->format('Y-m-d H:i:s'),
            $message,
        );

        $fileName = $level . '_' . $date->format('Y-m-d') . '.log';
        file_put_contents($this->filePath . '/' . $fileName, $formattedMessage, FILE_APPEND);

        $this->rotateLogs($level);
    }

    private function rotateLogs(string $level): void
    {
        $files = glob($this->filePath . '/' . $level . '*.log');
        $fileCount = count($files);
        while ($fileCount > $this->maxFileCount) {
            usort($files, function ($a, $b) {
                return filemtime($a) - filemtime($b);
            });

            $oldestFile = $files[0];

            if (file_exists($oldestFile)) {
                unlink($oldestFile);
            }

            $files = glob($this->filePath . '/' . $level . '*.log');
            $fileCount = count($files);
        }
    }
}
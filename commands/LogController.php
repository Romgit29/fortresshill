<?php

declare(strict_types=1);

namespace app\commands;

use app\models\Log;
use yii\console\Controller;
use yii\console\ExitCode;
use donatj\UserAgent\UserAgentParser;
use yii\helpers\Console;

class LogController extends Controller
{
    public function actionParse(): int
    {
        $parser = new UserAgentParser();
        $filePath = \Yii::getAlias('@logs/log.txt');
        $regex = '/^(\S+)\s+\S+\s+\S+\s+\[([^\]]+)\]\s+"(?:GET|POST|PUT|DELETE|HEAD|OPTIONS)\s+([^\s"]+)\s+[^"]+"\s+\d+\s+\d+\s+"[^"]*"\s+"([^"]+)"/i';

        if (!file_exists($filePath)) {
            $this->stdout("Error: File not found at " . $filePath . "\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->stdout("Не удалось открыть файл для чтения\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $lineNumber = 1;
        $rows = [];
        $batchSize = 1000;

        ini_set('memory_limit', '-1');

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);

            if (preg_match($regex, $line, $matches)) {
                $userAgent = $matches[4];
                $uaInfo = $parser->parse($userAgent);

                $rows[] = [
                    'ip' => $matches[1],
                    'date' => strtotime($matches[2]),
                    'url' => $matches[3],
                    'browser' => $uaInfo->browser(),
                    'os' => $uaInfo->platform(),
                    'architecture' => $this->getUserAgentArch($line),
                ];

                if (count($rows) >= $batchSize) {
                    $this->insertBatch($rows);
                    $rows = [];
                }
            } else {
                $this->stdout("Не удалось распарсить строку {$lineNumber}: {$line}\n", Console::FG_YELLOW);
            }

            $lineNumber++;
        }

        if (!empty($rows)) {
            $this->insertBatch($rows);
        }

        fclose($handle);
        $this->stdout("Импорт успешно завершен!\n", Console::FG_GREEN);
        return ExitCode::OK;
    }

    private function insertBatch(array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        $columns = array_keys($rows[0]);

        try {
            \Yii::$app->db->createCommand()
                ->batchInsert(Log::tableName(), $columns, $rows)
                ->execute();
        } catch (\Exception $e) {
            $this->stdout("Ошибка при пакетной вставке: " . $e->getMessage() . "\n", Console::FG_RED);
        }
    }

    private function getUserAgentArch(string $logLine)
    {
        if (!preg_match('/"([^"]+)"\s*$/', trim($logLine), $matches)) {
            return 'Unknown';
        }

        $userAgent = strtolower($matches[1]);

        $x64Markers = ['x86_64', 'x64', 'win64', 'wow64', 'amd64', 'arm64', 'aarch64'];
        foreach ($x64Markers as $marker) {
            if (str_contains($userAgent, $marker)) {
                return 'x64';
            }
        }

        $x32Markers = ['i386', 'i686', 'x86', 'win32'];
        foreach ($x32Markers as $marker) {
            if (str_contains($userAgent, $marker)) {
                return 'x32';
            }
        }

        if (str_contains($userAgent, 'android') || str_contains($userAgent, 'iphone')) {
            if (preg_match('/android [2-4]\./', $userAgent)) {
                return 'x32';
            }
        }

        return 'Unknown';
    }
}

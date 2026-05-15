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

        if (file_exists($filePath)) {
            $handle = fopen($filePath, 'r');
            if ($handle) {
                $lineNumber = 1;
                while (($line = fgets($handle)) !== false) {
                    $line = trim($line);

                    if (preg_match($regex, $line, $matches)) {
                        $userAgent = $matches[4];
                        $uaInfo = $parser->parse($userAgent);

                        $log = new Log([
                            'ip' => $matches[1],
                            'date' => strtotime($matches[2]),
                            'url' => $matches[3],
                            'browser' => $uaInfo->browser(),
                            'os' => $uaInfo->platform(),
                            'architecture' => $this->getUserAgentArch($userAgent),
                        ]);
                        if (!$log->save()) {
                            $this->stdout("Не удалось сохранить лог: " . ($lineNumber + 1) . ": $line\n", Console::FG_YELLOW);
                            var_dump($log->errors);
                        }
                    } else {
                        $this->stdout("Не удалось распарсить строку " . ($lineNumber + 1) . ": $line\n", Console::FG_YELLOW);
                    }

                    $lineNumber++;
                }
                fclose($handle);
            } else {
                $this->stderr("Не удалось открыть файл для чтения\n");

                return ExitCode::UNSPECIFIED_ERROR;
            }

            return ExitCode::OK;
        } else {
            echo "Error: File not found at " . $filePath . "\n";

            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    private function getUserAgentArch(string $userAgentString)
    {
        if (!preg_match('/"([^"]+)"\s*$/', trim($userAgentString), $matches)) {
            return null;
        }

        $userAgentTokens = preg_split('/[\s()\/;,_]+/', strtolower($matches[1]));

        $x64Markers = ['x86_64', 'amd64', 'win64', 'x64', 'wow64'];
        $x32Markers = ['i386', 'i686', 'x86', 'win32'];

        foreach ($userAgentTokens as $token) {
            if (in_array($token, $x64Markers, true)) {
                return 'x64';
            }
            if (in_array($token, $x32Markers, true)) {
                return 'x32';
            }
        }

        return null;
    }
}

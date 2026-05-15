<?php

declare(strict_types=1);

namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;

class LogController extends Controller
{
    public function actionParse(): int
    {
        echo "test\n";

        return ExitCode::OK;
    }
}

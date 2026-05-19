<?php

declare(strict_types=1);

namespace app\repositories;

use app\models\Log;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\db\Query;

class LogRepository
{
    public function applyFilters($query, array $params = [])
    {
        if (!empty($params['os'])) {
            $query->andWhere(['os' => $params['os']]);
        }

        if (!empty($params['architecture'])) {
            $query->andWhere(['architecture' => $params['architecture']]);
        }

        if (!empty($params['date_from']) && !empty($params['date_to'])) {
            $query->andFilterWhere(['between', 'log_date', $params['date_from'], $params['date_to']]);
        }

        return $query;
    }

    public function getRequestCountQuery(array $params = []): ActiveQuery
    {
        $query = Log::find()
            ->select([
                'log_date',
                'request_count' => 'COUNT(id)'
            ])
            ->asArray()
            ->groupBy('log_date')
            ->orderBy(['log_date' => SORT_DESC]);

        return $this->applyFilters($query, $params);
    }

    public function getRequestCountGraphData(array $params = []): array
    {
        $data = $this->getRequestCountQuery($params)
            ->limit(5)
            ->all();

        return array_reverse($data);
    }

    public function getMostPopularBrowserGraphData(array $params = []): array
    {
        $requestCounts = $this->getRequestCountGraphData($params);
        if (empty($requestCounts)) {
            return [];
        }

        $dates = array_column($requestCounts, 'log_date');

        $subQuery = Log::find()
            ->select([
                'log_date',
                'browser',
                'browser_count' => 'COUNT(id)'
            ])
            ->andWhere(['not', ['browser' => null]])
            ->groupBy(['log_date', 'browser']);

        $subQuery = $this->applyFilters($subQuery, $params);

        $rankedQuery = (new Query())
            ->select([
                'log_date',
                'browser',
                'browser_count',
                'row_num' => new Expression('ROW_NUMBER() OVER (PARTITION BY log_date ORDER BY browser_count DESC)')
            ])
            ->from($subQuery);

        $topBrowsers = (new Query())
            ->select(['log_date', 'browser', 'browser_count'])
            ->from(['ranked' => $rankedQuery])
            ->where(['<=', 'row_num', 3])
            ->andWhere(['log_date' => $dates])
            ->all();

        $browsersByDate = [];
        foreach ($topBrowsers as $row) {
            $browsersByDate[$row['log_date']][] = [
                'browser' => $row['browser'],
                'request_count' => (int) $row['browser_count'],
            ];
        }

        foreach ($requestCounts as &$requestCount) {
            $date = $requestCount['log_date'];
            $totalDailyRequests = (int) $requestCount['request_count'];

            $dayBrowsers = array_map(function ($browser) use ($totalDailyRequests) {
                if ($totalDailyRequests > 0) {
                    $browser['percentage'] = round(($browser['request_count'] / $totalDailyRequests) * 100, 2);
                } else {
                    $browser['percentage'] = 0;
                }

                return $browser;
            }, $browsersByDate[$date] ?? []);

            $requestCount['top_browsers_data'] = $dayBrowsers;
        }

        return $requestCounts;
    }
}

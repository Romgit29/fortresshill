<?php

declare(strict_types=1);

namespace app\repositories;

use app\models\Log;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\db\Query;

class LogRepository
{
    protected function applyFilters($query, array $params = [])
    {
        if (!empty($params['os'])) {
            $query->andWhere(['os' => $params['os']]);
        }

        if (!empty($params['architecture'])) {
            $query->andWhere(['architecture' => $params['architecture']]);
        }

        if (!empty($params['date_from']) && !empty($params['date_to'])) {
            $dateStartInt = strtotime($params['date_from'] . ' 00:00:00');
            $dateEndInt = strtotime($params['date_to'] . ' 23:59:59');
            $query->andFilterWhere(['between', 'date', $dateStartInt, $dateEndInt]);
        }

        return $query;
    }

    public function getRequestCountGraphData(array $params = []): array
    {
        $data = $this->getRequestCountQuery($params)
            ->limit(5)
            ->all();

        return array_reverse($data);
    }

    public function getRequestCountQuery(array $params = []): ActiveQuery
    {
        $query = Log::find()
            ->select([
                'date_formatted' => "DATE(FROM_UNIXTIME(date))",
                'request_count' => 'COUNT(id)'
            ])
            ->asArray()
            ->groupBy('date_formatted')
            ->orderBy(['date_formatted' => SORT_DESC]);

        return $this->applyFilters($query, $params);
    }

    public function getMostPopularBrowserGraphData(array $params = [])
    {
        $requestCounts = $this->getRequestCountGraphData($params);

        foreach ($requestCounts as &$requestCount) {
            $dateFormatted = $requestCount['date_formatted'];
            $topBrowsersQuery = Log::find()
                ->select([
                    'browser',
                    'request_count' => 'COUNT(id)'
                ])
                ->andWhere(['not', ['browser' => null]])
                ->andWhere("DATE(FROM_UNIXTIME(date)) = '$dateFormatted'")
                ->groupBy('browser')
                ->orderBy(['request_count' => SORT_DESC])
                ->limit(3)
                ->asArray();

            $topBrowsersQuery = $this->applyFilters($topBrowsersQuery, $params);
            
            $requestCount['top_browsers_data'] = $topBrowsersQuery->all();
        }

        foreach ($requestCounts as &$requestCount) {
            foreach ($requestCount['top_browsers_data'] as &$browser) {
                $browser['percentage'] = $requestCount['request_count'] > 0 ? round(($browser['request_count'] / $requestCount['request_count']) * 100, 2) : 0;
            }
        }

        return $requestCounts;
    }

    public function getMostPopularSub(string $column, array $params = []): Query
    {
        $countSub = Log::find()
            ->select([
                'date_formatted' => new Expression('DATE(FROM_UNIXTIME(`date`))'),
                $column,
                'request_count' => new Expression("COUNT(`$column`)"),
            ])
            ->groupBy([
                'date_formatted',
                $column,
            ]);

        $countSub = $this->applyFilters($countSub, $params);

        $rankedSub = (new Query())
            ->select([
                $column,
                'date_formatted',
                'row_num' => new Expression('ROW_NUMBER() OVER (PARTITION BY date_formatted ORDER BY `request_count` DESC)')
            ])
            ->from(['counts' => $countSub]);

        $mostPopularSub = (new Query())
            ->select([
                "$column as most_popular_$column",
                'date_formatted',
            ])
            ->from(['ranked' => $rankedSub])
            ->where(['row_num' => 1]);

        return $mostPopularSub;
    }
}


<?php

namespace app\models;

use yii\data\ActiveDataProvider;
use app\models\Log;
use app\repositories\LogRepository;
use yii\db\Query;

class LogSearch extends Log
{
    public $date_range;
    public $date_from;
    public $date_to;
    public $os;
    public $architecture;

    public $date_formatted;
    public $request_count;
    public $most_popular_url;
    public $most_popular_browser;

    public function rules()
    {
        return [
            [['date_from', 'date_to', 'os', 'architecture'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'date_formatted' => 'Дата',
            'request_count' => 'Число запросов',
            'most_popular_url' => 'Самый популярный URL',
            'most_popular_browser' => 'Самый популярный браузер',
        ];
    }

    public function search($params)
    {
        $params = $params['LogSearch'] ?? [];
        $logRepository = new LogRepository();
        $mostPopularBrowserSub = $logRepository->getMostPopularSub('browser', $params);
        $mostPopularUrlSub = $logRepository->getMostPopularSub('url', $params);

        $query = (new Query())->from(['request_count' => $logRepository->getRequestCountQuery($params)])
            ->innerJoin(
                ['most_popular_browser_sub' => $mostPopularBrowserSub],
                'request_count.date_formatted = most_popular_browser_sub.date_formatted'
            )
            ->innerJoin(
                ['most_popular_url_sub' => $mostPopularUrlSub],
                'request_count.date_formatted = most_popular_url_sub.date_formatted'
            )
            ->addSelect([
                'request_count.date_formatted',
                'request_count',
                'most_popular_url',
                'most_popular_browser'
            ]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 10,
            ],
            'sort' => [
                'attributes' => [
                    'date_formatted',
                    'most_popular_url',
                    'most_popular_browser',
                    'request_count'
                ],
                'defaultOrder' => ['date_formatted' => SORT_DESC],
            ],
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        return $dataProvider;
    }
}

<?php

namespace app\models;

use yii\data\ActiveDataProvider;
use app\models\Log;
use app\repositories\LogRepository;
use yii\db\Expression;
use yii\db\Query;

class LogSearch extends Log
{
    public $date_range;
    public $date_from;
    public $date_to;
    public $os;
    public $architecture;

    public $log_date;
    public $request_count;
    public $most_popular_url;
    public $most_popular_browser;

    /**
     * @var LogRepository
     */
    private LogRepository $logRepository;

    public function __construct(array $config = [])
    {
        $this->logRepository = new LogRepository;
        parent::__construct($config);
    }

    public function rules()
    {
        return [
            [['date_from', 'date_to', 'os', 'architecture'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'log_date' => 'Дата',
            'request_count' => 'Число запросов',
            'most_popular_url' => 'Самый популярный URL',
            'most_popular_browser' => 'Самый популярный браузер',
        ];
    }

    public function search($params)
    {
        $params = $params['LogSearch'] ?? [];

        $countQuery = (new \yii\db\Query())->from('log');
        $this->logRepository->applyFilters($countQuery, $params);
        $totalCount = $countQuery->count('DISTINCT `log_date`');

        $query = (new \yii\db\Query())
            ->select([
                'log_date' => 'log_date',
                'request_count' => new \yii\db\Expression("COUNT(*)"),
                'most_popular_url' => $this->getMaxDailyValueSub('url', $params),
                'most_popular_browser' => $this->getMaxDailyValueSub('browser', $params),
            ])
            ->from('log')
            ->groupBy('log_date');
        $this->logRepository->applyFilters($query, $params);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'totalCount' => $totalCount,
            'pagination' => [
                'pageSize' => 10,
            ],
            'sort' => [
                'attributes' => [
                    'log_date',
                    'most_popular_url',
                    'most_popular_browser',
                    'request_count'
                ],
                'defaultOrder' => ['log_date' => SORT_DESC],
            ],
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        return $dataProvider;
    }

    private function getMaxDailyValueSub(string $column, array $params)
    {
        $sub = (new Query())
            ->select("sub.$column")
            ->from(['sub' => 'log'])
            ->where([
                'and',
                ['=', 'sub.log_date', new Expression('`log`.`log_date`')],
                ['is not', "sub.$column", null]
            ])
            ->groupBy("sub.$column")
            ->orderBy([
                new Expression('COUNT(sub.id) DESC'),
            ])
            ->limit(1);
        $this->logRepository->applyFilters($sub, $params);

        return $sub;
    }
}

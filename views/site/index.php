<?php

use dosamigos\chartjs\ChartJs;
use onmotion\apexcharts\ApexchartsWidget;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use kartik\daterange\DateRangePicker;

$series = [
    [
        'data' => []
    ],
    [
        'data' => []
    ],
    [
        'data' => []
    ],
];

foreach ($mostPopularBrowserGraphData as $queryCount) {
    foreach ($queryCount['top_browsers_data'] as $key => $topBrowserData) {
        $series[$key]['data'][] = [
            'x' => $queryCount['log_date'],
            'y' => $topBrowserData['percentage'],
            'browser_name' => $topBrowserData['browser'],
        ];
    }
}
?>

<div class="site-index">
    <div class="filter-box" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 40px; border: 1px solid #e9ecef;">

        <?php $form = ActiveForm::begin([
            'action' => ['index'],
            'method' => 'get',
            'options' => ['class' => 'form-inline'],
        ]); ?>

        <div style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; color: black;">
            <div class="form-group" style="min-width: 250px;">
                <label class="control-label">Период дат</label>
                <?= DateRangePicker::widget([
                    'model' => $searchModel,
                    'attribute' => 'date_range',
                    'convertFormat' => true,
                    'startAttribute' => 'date_from',
                    'endAttribute' => 'date_to',
                    'bsVersion' => '4',
                    'pluginOptions' => [
                        'showDropdowns' => true,
                        'locale' => [
                            'format' => 'Y-m-d',
                            'separator' => ' - ',
                        ],
                        'maxSpan' => ['years' => 1], 
                        'opens' => 'right'
                    ],
                    'options' => [
                        'class' => 'form-control',
                        'placeholder' => 'Выберите диапазон...',
                        'value' => Yii::$app->request->get('LogSearch')['date_range'] ?? null
                    ]
                ]) ?>
            </div>

            <div class="form-group">
                <?= $form->field($searchModel, 'os')->textInput(['class' => 'form-control', 'value' => Yii::$app->request->get('LogSearch')['os'] ?? null])->label('Операционная система') ?>
            </div>

            <div class="form-group">
                <?= $form->field($searchModel, 'architecture')->textInput(['value' => Yii::$app->request->get('LogSearch')['architecture'] ?? null, 'class' => 'form-control'])->label('Архитектура') ?>
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <?= Html::submitButton('Применить', ['class' => 'btn btn-primary']) ?>
                <?= Html::a('Сбросить', ['index'], ['class' => 'btn btn-outline-secondary', 'style' => 'margin-left: 5px; border: 1px solid #ccc; padding: 6px 12px; border-radius: 4px; color: #333; text-decoration: none;']) ?>
            </div>
        </div>

        <?php ActiveForm::end(); ?>
    </div>

    <h1 style='margin-top: 100px; text-align: center;'>Количество запросов</h1>

    <?= ChartJs::widget([
        'type' => 'bar',
        'options' => [
            'height' => 400,
            'width' => 600
        ],
        'data' => [
            'labels' => array_column($countGraphData, 'log_date'),
            'datasets' => [
                [
                    'label' => "Число запросов",
                    'backgroundColor' => "rgba(255,99,132,0.2)",
                    'borderColor' => "rgba(255,99,132,1)",
                    'pointBackgroundColor' => "rgba(255,99,132,1)",
                    'data' => array_column($countGraphData, 'request_count')
                ]
            ]
        ],
        'clientOptions' => [
            'scales' => [
                'yAxes' => [
                    [
                        'ticks' => [
                            'beginAtZero' => true
                        ]
                    ]
                ]
            ]
        ]
    ]); ?>

    <h1 style='margin-top: 100px; text-align: center;'>Самые популярные браузеры</h1>

    <?= ApexchartsWidget::widget([
        'type' => 'bar',
        'height' => '400',
        'width' => '100%',
        'series' => $series,
        'chartOptions' => [
            'plotOptions' => [
                'bar' => [
                    'horizontal' => false,
                    'columnWidth' => '70%',
                ],
            ],
            'colors' => ['#33b2df', '#546E7A', '#d4526e'],
            'yaxis' => [
                'min' => 0,
                'max' => 100,
                'title' => [
                    'text' => 'Доля запросов (%)'
                ]
            ],
            'legend' => [
                'show' => false
            ],
            'tooltip' => [
                'custom' => new \yii\web\JsExpression("function({series, seriesIndex, dataPointIndex, w}) {
                var data = w.globals.initialSeries[seriesIndex].data[dataPointIndex];
                if (!data || !data.browser_name) return ''; 
                
                return '<div style=\"padding:10px; background:#fff; border:1px solid #ccc; color:#000; font-family: sans-serif;\">' +
                    '<b>' + data.browser_name + '</b><br/>' +
                    'Доля: ' + data.y + '%' +
                    '</div>';
            }")
            ]
        ]
    ]); ?>

    <h1 style='margin-top: 100px; text-align: center;'>Логи</h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel
    ]) ?>
</div>
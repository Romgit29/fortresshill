<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "log".
 *
 * @property int $id
 * @property string|null $ip
 * @property int|null $date
 * @property string|null $url
 * @property string|null $os
 * @property string|null $architecture
 * @property string|null $browser
 * @property string|null $agent
 * @property int|null $createdAt
 * @property int|null $updatedAt
 * @property string|null $log_date
 */
class Log extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ip', 'date', 'url', 'os', 'architecture', 'browser', 'agent', 'createdAt', 'updatedAt', 'log_date'], 'default', 'value' => null],
            [['date', 'createdAt', 'updatedAt'], 'integer'],
            [['log_date'], 'safe'],
            [['ip', 'os', 'architecture', 'browser', 'agent'], 'string', 'max' => 255],
            [['url'], 'string', 'max' => 2048],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ip' => 'Ip',
            'date' => 'Date',
            'url' => 'Url',
            'os' => 'Os',
            'architecture' => 'Architecture',
            'browser' => 'Browser',
            'agent' => 'Agent',
            'createdAt' => 'Created At',
            'updatedAt' => 'Updated At',
            'log_date' => 'Log Date',
        ];
    }

}

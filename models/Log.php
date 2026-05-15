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
 * @property int|null $createdAt
 * @property int|null $updatedAt
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
            [['ip', 'date', 'url', 'os', 'architecture', 'browser', 'createdAt', 'updatedAt'], 'default', 'value' => null],
            [['date', 'createdAt', 'updatedAt'], 'integer'],
            [['ip', 'url', 'os', 'architecture', 'browser'], 'string', 'max' => 255],
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
            'createdAt' => 'Created At',
            'updatedAt' => 'Updated At',
        ];
    }

}

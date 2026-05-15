<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%log}}`.
 */
class m260515_092712_createLogTable extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%log}}', [
            'id' => $this->primaryKey(),
            'ip' => $this->string(),
            'date' => $this->bigInteger(),
            'url' => $this->string(2048),
            'os' => $this->string(),
            'architecture' => $this->string(),
            'browser' => $this->string(),
            'createdAt' => $this->bigInteger(),
			'updatedAt' => $this->bigInteger()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%log}}');
    }
}

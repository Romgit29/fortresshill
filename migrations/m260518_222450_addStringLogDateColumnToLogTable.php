<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%log}}`.
 */
class m260518_222450_addStringLogDateColumnToLogTable extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute("
            ALTER TABLE {{%log}} 
            ADD COLUMN `log_date` DATE GENERATED ALWAYS AS (DATE(FROM_UNIXTIME(`date`))) STORED
        ");

        $this->createIndex(
            'idx_log_optimization',
            '{{%log}}',
            ['log_date', 'os', 'architecture']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx_log_optimization', '{{%log}}');

        $this->dropColumn('{{%log}}', 'log_date');
    }
}

<?php

namespace tests\models;

use yii\db\ActiveRecord;

/**
 * Child whose foreign key (`owner_id`) lives on its own table. A linkOnly relation pointing
 * here cannot be linked without saving the child, so the behavior must reject the configuration.
 */
class LinkOnlyChild extends ActiveRecord
{
    #[\Override]
    public static function tableName()
    {
        return 'lo_child';
    }
}

<?php

namespace tests\models;

use yii\db\ActiveRecord;

/**
 * Child whose foreign key (`owner_id`) lives on its own table, i.e. a non-via has-many relation.
 * Configuring `sortColumn` on such a relation is unsupported (there is no junction table to write
 * a position to), so the behavior must reject it with an InvalidConfigException.
 */
class SortChild extends ActiveRecord
{
    #[\Override]
    public static function tableName()
    {
        return 'sort_child';
    }
}

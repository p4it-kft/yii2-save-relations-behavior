<?php

namespace tests\models;

use yii\db\ActiveRecord;

/**
 * A related record used by the sortColumn tests. It is a plain, valid entity whose only job is
 * to be linked to a {@see SortOwner} through a via-table relation that persists submitted order.
 */
class SortItem extends ActiveRecord
{
    #[\Override]
    public static function tableName()
    {
        return 'sort_item';
    }

    #[\Override]
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string'],
        ];
    }
}

<?php

namespace tests\models;

use yii\db\ActiveRecord;

/**
 * A related record used by the linkOnly tests. Its `code` is required by its own rules,
 * but the seeded rows leave it blank — so the record exists (has a primary key) yet fails
 * validate(). A linkOnly relation must be able to link such a record without validating
 * or saving it.
 */
class LinkOnlyProduct extends ActiveRecord
{
    /** @var int|null transient value written to the junction table through extraColumns */
    public $sortOrder;

    #[\Override]
    public static function tableName()
    {
        return 'lo_product';
    }

    #[\Override]
    public function rules()
    {
        return [
            [['code'], 'required'],
            [['code'], 'string'],
        ];
    }
}

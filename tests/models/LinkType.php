<?php

namespace tests\models;

class LinkType extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    #[\Override]
    public static function tableName()
    {
        return 'link_type';
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'unique'],
        ];
    }
}

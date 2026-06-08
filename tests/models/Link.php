<?php

namespace tests\models;

use p4it\saveRelationsBehavior\SaveRelationsBehavior;

class Link extends \yii\db\ActiveRecord
{
    public const SCENARIO_FIRST = 'first';

    /**
     * @inheritdoc
     */
    #[\Override]
    public static function tableName()
    {
        return 'link';
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function behaviors()
    {
        return [
            'saveRelations' => [
                'class'     => SaveRelationsBehavior::class,
                'relations' => ['linkType'],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function rules()
    {
        return [
            [['language', 'name', 'link'], 'required'],
            [['name'], 'unique', 'targetAttribute' => ['language', 'name']],
            [['link'], 'url', 'on' => [self::SCENARIO_FIRST]],
            [['link_type_id', 'linkType'], 'safe'],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLinkType()
    {
        return $this->hasOne(LinkType::class, ['id' => 'link_type_id']);
    }
}

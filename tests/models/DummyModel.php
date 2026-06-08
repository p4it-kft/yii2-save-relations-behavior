<?php

/**
 * @link http://www.lahautesociete.com
 * @copyright Copyright (c) 2016 La Haute Société
 */

namespace tests\models;

use p4it\saveRelationsBehavior\SaveRelationsBehavior;

/**
 * DummyModel class
 *
 * @author albanjubert
 **/
class DummyModel extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    #[\Override]
    public static function tableName()
    {
        return 'dummy';
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
                'relations' => ['children'],
            ],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChildren()
    {
        return $this->hasOne(DummyModel::class, ['id' => 'parent_id']);
    }

}

<?php

namespace tests\models;

use p4it\saveRelationsBehavior\SaveRelationsBehavior;

class UserProfile extends \yii\db\ActiveRecord
{
    public $agree;

    /**
     * @inheritdoc
     */
    #[\Override]
    public static function tableName()
    {
        return 'user_profile';
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
                'relations' => ['user'],
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
            [['user_id'], 'integer'],
            ['bio', 'required'],
            [['user_id'], 'unique'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['agree'], 'required', 'on' => 'insert'],
            ['user', 'safe'],
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

}

<?php

namespace tests\models;

use p4it\saveRelationsBehavior\SaveRelationsBehavior;
use p4it\saveRelationsBehavior\SaveRelationsTrait;

class User extends \yii\db\ActiveRecord
{
    use SaveRelationsTrait;

    /**
     * @inheritdoc
     */
    #[\Override]
    public static function tableName()
    {
        return 'user';
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
                'relations' => ['userProfile' => ['cascadeDelete' => true], 'company'],
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
            ['company_id', 'integer'],
            [['username'], 'required'],
            ['username', 'unique', 'targetClass' => '\tests\models\User'],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::class, 'targetAttribute' => ['company_id' => 'id']],
            [['userProfile', 'company'], 'safe'],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserProfile()
    {
        return $this->hasOne(UserProfile::class, ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::class, ['id' => 'company_id']);
    }

}

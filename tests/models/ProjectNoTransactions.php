<?php

namespace tests\models;

use p4it\saveRelationsBehavior\SaveRelationsBehavior;

class ProjectNoTransactions extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    #[\Override]
    public static function tableName()
    {
        return 'project';
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
                'relations' => ['company', 'users', 'links'],
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
            [['name', 'company_id'], 'required'],
            [['name'], 'unique', 'targetAttribute' => ['company_id', 'name']],
            [['company', 'users', 'links'], 'safe'],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::class, ['id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProjectUsers()
    {
        return $this->hasMany(ProjectUser::class, ['project_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasMany(User::class, ['id' => 'user_id'])->via('projectUsers');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProjectLinks()
    {
        return $this->hasMany(ProjectLink::class, ['project_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLinks()
    {
        return $this->hasMany(Link::class, ['language' => 'language', 'name' => 'name'])->via('projectLinks');
    }

}

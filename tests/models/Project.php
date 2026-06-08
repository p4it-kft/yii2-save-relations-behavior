<?php

namespace tests\models;

use p4it\saveRelationsBehavior\SaveRelationsBehavior;
use p4it\saveRelationsBehavior\SaveRelationsTrait;

class Project extends \yii\db\ActiveRecord
{
    use SaveRelationsTrait;

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
                'relations' => [
                    'company',
                    'users',
                    'contacts',
                    'images',
                    'links'        => ['scenario' => Link::SCENARIO_FIRST],
                    'projectLinks' => ['cascadeDelete' => true],
                    'tags'         => [
                        'extraColumns' => /** @var $model Tag */
                        fn ($model) => [
                            'order' => $model->order,
                        ],
                    ],
                ],
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
            [['company', 'links', 'users', 'contacts', 'images', 'projectLinks', 'tags'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function transactions()
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_ALL,
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
        return $this->hasMany(User::class, ['id' => 'user_id'])->via('projectUsers', fn ($query) => $query);
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
    public function getContacts()
    {
        return $this->hasMany(ProjectContact::class, ['project_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImages()
    {
        return $this->hasMany(ProjectImage::class, ['project_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLinks()
    {
        return $this->hasMany(Link::class, ['language' => 'language', 'name' => 'name'])->via('projectLinks');
    }

    /**
     * @return ActiveQuery
     */
    public function getTags()
    {
        return $this->hasMany(Tag::class, ['id' => 'tag_id'])->viaTable('project_tags', ['project_id' => 'id']);
    }

}

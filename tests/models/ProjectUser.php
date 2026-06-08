<?php

namespace tests\models;

class ProjectUser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    #[\Override]
    public static function tableName()
    {
        return 'project_user';
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function rules()
    {
        return [
            [['project_id', 'user_id'], 'required'],
            [['project_id', 'user_id'], 'integer'],
        ];
    }

}

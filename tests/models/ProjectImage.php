<?php

namespace tests\models;

class ProjectImage extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    #[\Override]
    public static function tableName()
    {
        return 'project_image';
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function rules()
    {
        return [
            [['project_id'], 'integer'],
            [['path'], 'required'],
            [['path'], 'string'],
        ];
    }
}

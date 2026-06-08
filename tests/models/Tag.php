<?php

namespace tests\models;

class Tag extends \yii\db\ActiveRecord
{
    /** @var int */
    protected $order;

    /**
     * @inheritdoc
     */
    #[\Override]
    public static function tableName()
    {
        return 'tags';
    }

    /**
     * @param int $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string'],
        ];
    }
}

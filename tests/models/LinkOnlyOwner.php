<?php

namespace tests\models;

use p4it\saveRelationsBehavior\SaveRelationsBehavior;
use yii\db\ActiveRecord;

/**
 * Owner exercising the linkOnly option across relation types:
 *  - products       : has-many via-table (M2M), linkOnly + extraColumns
 *  - productsStrict  : same junction WITHOUT linkOnly (control for the regression test)
 *  - mainProduct     : has-one whose foreign key is on the owner, linkOnly
 *  - children        : has-many whose foreign key is on the related record — unsupported for linkOnly
 */
class LinkOnlyOwner extends ActiveRecord
{
    #[\Override]
    public static function tableName()
    {
        return 'lo_owner';
    }

    #[\Override]
    public function behaviors()
    {
        return [
            'saveRelations' => [
                'class'     => SaveRelationsBehavior::class,
                'relations' => [
                    'products'       => [
                        'linkOnly'     => true,
                        'extraColumns' => fn (LinkOnlyProduct $model) => ['sort_order' => $model->sortOrder],
                    ],
                    'productsStrict' => [],
                    'mainProduct'    => ['linkOnly' => true],
                    'children'       => ['linkOnly' => true],
                ],
            ],
        ];
    }

    #[\Override]
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['main_product_id'], 'integer'],
            [['products', 'productsStrict', 'mainProduct', 'children'], 'safe'],
        ];
    }

    public function getProducts()
    {
        return $this->hasMany(LinkOnlyProduct::class, ['id' => 'product_id'])
            ->viaTable('lo_owner_product', ['owner_id' => 'id']);
    }

    public function getProductsStrict()
    {
        return $this->hasMany(LinkOnlyProduct::class, ['id' => 'product_id'])
            ->viaTable('lo_owner_product', ['owner_id' => 'id']);
    }

    public function getMainProduct()
    {
        return $this->hasOne(LinkOnlyProduct::class, ['id' => 'main_product_id']);
    }

    public function getChildren()
    {
        return $this->hasMany(LinkOnlyChild::class, ['owner_id' => 'id']);
    }
}

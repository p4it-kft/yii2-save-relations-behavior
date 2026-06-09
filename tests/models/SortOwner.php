<?php

namespace tests\models;

use p4it\saveRelationsBehavior\SaveRelationsBehavior;
use yii\db\ActiveRecord;

/**
 * Owner exercising the per-relation `sortColumn` option, which persists the submitted order of a
 * via-table (M2M) relation into a junction-table column.
 *
 *  - items        : plain M2M, sortColumn only
 *  - linkedItems  : same junction as items, linkOnly + sortColumn (related records never saved)
 *  - galleryItems : M2M sharing a junction with docItems, scoped by a `type` discriminator
 *  - docItems     : the other half of the shared junction (control for discriminator isolation)
 *  - children     : non-via has-many — sortColumn is unsupported and must be rejected
 *
 * The `type` discriminator is encoded both in the relation's `onCondition` (so reads and unlinks
 * stay scoped to one type) and in the behavior's `extraColumns` (so inserts write the type).
 */
class SortOwner extends ActiveRecord
{
    #[\Override]
    public static function tableName()
    {
        return 'sort_owner';
    }

    #[\Override]
    public function behaviors()
    {
        return [
            'saveRelations' => [
                'class'     => SaveRelationsBehavior::class,
                'relations' => [
                    'items'        => ['sortColumn' => 'sort_order'],
                    'linkedItems'  => ['linkOnly' => true, 'sortColumn' => 'sort_order'],
                    'galleryItems' => ['linkOnly' => true, 'extraColumns' => ['type' => 'gallery'], 'sortColumn' => 'sort_order'],
                    'docItems'     => ['linkOnly' => true, 'extraColumns' => ['type' => 'doc'], 'sortColumn' => 'sort_order'],
                    'children'     => ['sortColumn' => 'sort_order'],
                ],
            ],
        ];
    }

    #[\Override]
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['items', 'linkedItems', 'galleryItems', 'docItems', 'children'], 'safe'],
        ];
    }

    public function getItems()
    {
        return $this->hasMany(SortItem::class, ['id' => 'item_id'])
            ->viaTable('sort_owner_item', ['owner_id' => 'id']);
    }

    public function getLinkedItems()
    {
        return $this->hasMany(SortItem::class, ['id' => 'item_id'])
            ->viaTable('sort_owner_item', ['owner_id' => 'id']);
    }

    public function getGalleryItems()
    {
        return $this->hasMany(SortItem::class, ['id' => 'item_id'])
            ->viaTable('sort_owner_gallery', ['owner_id' => 'id'], fn ($query) => $query->andOnCondition(['type' => 'gallery']));
    }

    public function getDocItems()
    {
        return $this->hasMany(SortItem::class, ['id' => 'item_id'])
            ->viaTable('sort_owner_gallery', ['owner_id' => 'id'], fn ($query) => $query->andOnCondition(['type' => 'doc']));
    }

    public function getChildren()
    {
        return $this->hasMany(SortChild::class, ['owner_id' => 'id']);
    }
}

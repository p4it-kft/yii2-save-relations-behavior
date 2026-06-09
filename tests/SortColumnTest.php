<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use tests\models\SortChild;
use tests\models\SortItem;
use tests\models\SortOwner;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\Query;

/**
 * Tests for the per-relation `sortColumn` option: the behavior persists the submitted order of a
 * via-table (M2M) relation into a column on the junction table, as 0-based positions.
 *
 * Assertions read the junction `sort_order` column directly (never the reloaded relation order),
 * because the positions are what the feature owns — the read ordering stays in the relation getter.
 */
class SortColumnTest extends TestCase
{
    private const array TABLES = ['sort_owner_gallery', 'sort_owner_item', 'sort_child', 'sort_item', 'sort_owner'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDb();
    }

    protected function tearDown(): void
    {
        $db = Yii::$app->getDb();
        foreach (self::TABLES as $table) {
            $db->createCommand('DROP TABLE IF EXISTS ' . $db->quoteTableName($table))->execute();
        }
        parent::tearDown();
    }

    private function setupDb(): void
    {
        $db = Yii::$app->getDb();
        foreach (self::TABLES as $table) {
            $db->createCommand('DROP TABLE IF EXISTS ' . $db->quoteTableName($table))->execute();
        }

        $db->createCommand()->createTable('sort_owner', [
            'id'   => 'pk',
            'name' => 'string NOT NULL',
        ])->execute();
        $db->createCommand()->createTable('sort_item', [
            'id'   => 'pk',
            'name' => 'string NULL',
        ])->execute();
        $db->createCommand()->createTable('sort_child', [
            'id'       => 'pk',
            'owner_id' => 'integer NOT NULL',
        ])->execute();
        // Plain M2M junction (items / linkedItems).
        $db->createCommand()->createTable('sort_owner_item', [
            'owner_id'   => 'integer NOT NULL',
            'item_id'    => 'integer NOT NULL',
            'sort_order' => 'integer NULL',
            'PRIMARY KEY(owner_id, item_id)',
        ])->execute();
        // Junction shared by galleryItems and docItems, distinguished by `type`.
        $db->createCommand()->createTable('sort_owner_gallery', [
            'owner_id'   => 'integer NOT NULL',
            'item_id'    => 'integer NOT NULL',
            'type'       => 'string NOT NULL',
            'sort_order' => 'integer NULL',
            'PRIMARY KEY(owner_id, item_id, type)',
        ])->execute();

        $db->createCommand()->batchInsert('sort_owner', ['id', 'name'], [[1, 'Owner One']])->execute();
        $db->createCommand()->batchInsert('sort_item', ['id', 'name'], [
            [1, 'i1'], [2, 'i2'], [3, 'i3'], [4, 'i4'],
            [10, 'i10'], [11, 'i11'], [12, 'i12'], [20, 'i20'],
        ])->execute();
        $db->createCommand()->batchInsert('sort_child', ['id', 'owner_id'], [[1, 1]])->execute();
    }

    /**
     * @return array<int,int|null> map of item_id => sort_order for the matching junction rows
     */
    private function positions(string $table, array $where): array
    {
        $rows = new Query()
            ->select(['item_id', 'sort_order'])
            ->from($table)
            ->where($where)
            ->all(Yii::$app->getDb());
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['item_id']] = $row['sort_order'] === null ? null : (int) $row['sort_order'];
        }
        return $map;
    }

    private function seedJunction(string $table, array $rows): void
    {
        $db = Yii::$app->getDb();
        foreach ($rows as $row) {
            $db->createCommand()->insert($table, $row)->execute();
        }
    }

    public function testInsertWritesContiguousZeroBasedPositions(): void
    {
        $owner = SortOwner::findOne(1);
        $owner->items = [SortItem::findOne(3), SortItem::findOne(1), SortItem::findOne(2)];

        $this->assertTrue($owner->save(), 'Owner should save');
        $this->assertEquals(
            [3 => 0, 1 => 1, 2 => 2],
            $this->positions('sort_owner_item', ['owner_id' => 1]),
            'Junction sort_order must be 0..N-1 in submitted order'
        );
    }

    public function testReorderWithoutMembershipChangeUpdatesPositions(): void
    {
        $this->seedJunction('sort_owner_item', [
            ['owner_id' => 1, 'item_id' => 1, 'sort_order' => 0],
            ['owner_id' => 1, 'item_id' => 2, 'sort_order' => 1],
            ['owner_id' => 1, 'item_id' => 3, 'sort_order' => 2],
        ]);

        $owner = SortOwner::findOne(1);
        $owner->items = [SortItem::findOne(2), SortItem::findOne(3), SortItem::findOne(1)]; // pure reorder

        $this->assertTrue($owner->save());
        $this->assertEquals(
            [2 => 0, 3 => 1, 1 => 2],
            $this->positions('sort_owner_item', ['owner_id' => 1]),
            'A pure reorder (no membership change) must still rewrite positions'
        );
    }

    public function testAddRemoveReorderRenumbersContiguously(): void
    {
        $this->seedJunction('sort_owner_item', [
            ['owner_id' => 1, 'item_id' => 1, 'sort_order' => 0],
            ['owner_id' => 1, 'item_id' => 2, 'sort_order' => 1],
            ['owner_id' => 1, 'item_id' => 3, 'sort_order' => 2],
        ]);

        $owner = SortOwner::findOne(1);
        // Drop 2, add 4, reorder: submitted order [3, 4, 1].
        $owner->items = [SortItem::findOne(3), SortItem::findOne(4), SortItem::findOne(1)];

        $this->assertTrue($owner->save());
        $this->assertEquals(
            [3 => 0, 4 => 1, 1 => 2],
            $this->positions('sort_owner_item', ['owner_id' => 1]),
            'Surviving + added rows must be renumbered contiguously in submitted order'
        );
    }

    public function testExtraColumnsDiscriminatorScopesPositionsAndLeavesOtherTypeUntouched(): void
    {
        $this->seedJunction('sort_owner_gallery', [
            ['owner_id' => 1, 'item_id' => 10, 'type' => 'gallery', 'sort_order' => 0],
            ['owner_id' => 1, 'item_id' => 11, 'type' => 'gallery', 'sort_order' => 1],
            // Same items under a different type — must be left completely untouched.
            ['owner_id' => 1, 'item_id' => 10, 'type' => 'doc', 'sort_order' => 7],
            ['owner_id' => 1, 'item_id' => 11, 'type' => 'doc', 'sort_order' => 8],
        ]);

        $owner = SortOwner::findOne(1);
        // gallery: drop 11, add 12, reorder -> [12, 10].
        $owner->galleryItems = [SortItem::findOne(12), SortItem::findOne(10)];

        $this->assertTrue($owner->save());
        $this->assertEquals(
            [12 => 0, 10 => 1],
            $this->positions('sort_owner_gallery', ['owner_id' => 1, 'type' => 'gallery']),
            'Only the gallery rows should be renumbered, in submitted order'
        );
        $this->assertEquals(
            [10 => 7, 11 => 8],
            $this->positions('sort_owner_gallery', ['owner_id' => 1, 'type' => 'doc']),
            'Rows of the other relation sharing the junction must be untouched'
        );
    }

    public function testLinkOnlyComposesWithSortColumn(): void
    {
        $dirty = SortItem::findOne(3);
        $dirty->name = 'changed-in-memory'; // must NOT be persisted by a linkOnly relation

        $owner = SortOwner::findOne(1);
        $owner->linkedItems = [$dirty, SortItem::findOne(1), SortItem::findOne(2)];

        $this->assertTrue($owner->save());
        $this->assertEquals(
            [3 => 0, 1 => 1, 2 => 2],
            $this->positions('sort_owner_item', ['owner_id' => 1]),
            'linkOnly + sortColumn must still persist positions'
        );
        $this->assertSame(
            'i3',
            SortItem::findOne(3)->name,
            'A linkOnly related record must never be saved'
        );
    }

    public function testSortColumnOnNonViaRelationThrows(): void
    {
        $this->expectException(InvalidConfigException::class);

        $owner = SortOwner::findOne(1);
        $owner->children = [SortChild::findOne(1)]; // non-via has-many: no junction to write a position to
        $owner->save();
    }
}

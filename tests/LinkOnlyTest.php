<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use tests\models\LinkOnlyChild;
use tests\models\LinkOnlyOwner;
use tests\models\LinkOnlyProduct;
use Yii;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\db\Exception as DbException;
use yii\db\Query;

/**
 * Tests for the per-relation `linkOnly` option: the behavior manages only the relationship
 * link (junction rows for a via-table relation, the owner-side foreign key for an owner-side
 * has-one) and never validates nor saves the related record models.
 */
class LinkOnlyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDb();
    }

    protected function tearDown(): void
    {
        $db = Yii::$app->getDb();
        foreach (['lo_owner_product', 'lo_child', 'lo_owner', 'lo_product'] as $table) {
            $db->createCommand('DROP TABLE IF EXISTS ' . $db->quoteTableName($table))->execute();
        }
        parent::tearDown();
    }

    private function setupDb(): void
    {
        $db = Yii::$app->getDb();
        foreach (['lo_owner_product', 'lo_child', 'lo_owner', 'lo_product'] as $table) {
            $db->createCommand('DROP TABLE IF EXISTS ' . $db->quoteTableName($table))->execute();
        }

        // `code` is nullable in the schema but required by the model rules: seeded rows are
        // valid as data but fail validate(), the exact situation linkOnly must tolerate.
        $db->createCommand()->createTable('lo_product', [
            'id'   => 'pk',
            'code' => 'string NULL',
        ])->execute();
        $db->createCommand()->createTable('lo_owner', [
            'id'              => 'pk',
            'name'            => 'string NOT NULL',
            'main_product_id' => 'integer NULL',
        ])->execute();
        $db->createCommand()->createTable('lo_child', [
            'id'       => 'pk',
            'owner_id' => 'integer NOT NULL',
        ])->execute();
        $db->createCommand()->createTable('lo_owner_product', [
            'owner_id'   => 'integer NOT NULL',
            'product_id' => 'integer NOT NULL',
            'sort_order' => 'integer NULL',
            'PRIMARY KEY(owner_id, product_id)',
        ])->execute();

        $db->createCommand()->batchInsert('lo_product', ['id', 'code'], [
            [1, null],          // exists but invalid (code blank)
            [2, null],          // exists but invalid (code blank)
            [3, 'valid-code'],
        ])->execute();
        $db->createCommand()->batchInsert('lo_owner', ['id', 'name', 'main_product_id'], [
            [1, 'Owner One', null],
        ])->execute();
        $db->createCommand()->batchInsert('lo_child', ['id', 'owner_id'], [
            [1, 1],
        ])->execute();
    }

    /** @return int[] product ids currently linked to the owner via the junction table */
    private function linkedProductIds(int $ownerId): array
    {
        $ids = new Query()
            ->select('product_id')
            ->from('lo_owner_product')
            ->where(['owner_id' => $ownerId])
            ->column(Yii::$app->getDb());
        $ids = array_map(intval(...), $ids);
        sort($ids);
        return $ids;
    }

    public function testLinkOnlyHasManyLinksWithoutValidatingOrSavingRelated()
    {
        $owner = LinkOnlyOwner::findOne(1);

        $product1 = LinkOnlyProduct::findOne(1); // code is null -> invalid
        $product1->code = 'in-memory-change';    // dirty; must NOT be persisted
        $product2 = LinkOnlyProduct::findOne(2);

        $owner->products = [$product1, $product2];

        $this->assertTrue($owner->save(), 'Owner with a linkOnly relation should save despite invalid related records');
        $this->assertSame([], $owner->getErrors(), 'No related-side errors should bubble onto the owner');
        $this->assertSame([1, 2], $this->linkedProductIds(1), 'Junction rows should match the assigned set');

        $reloaded = LinkOnlyProduct::findOne(1);
        $this->assertNull($reloaded->code, 'The related record must not be saved (the in-memory change must not persist)');
    }

    public function testLinkOnlyHasManyAddAndRemoveLinks()
    {
        Yii::$app->getDb()->createCommand()
            ->insert('lo_owner_product', ['owner_id' => 1, 'product_id' => 1])
            ->execute();

        $owner = LinkOnlyOwner::findOne(1);
        $owner->products = [2, 3]; // drop 1, add 2 and 3

        $this->assertTrue($owner->save());
        $this->assertSame([2, 3], $this->linkedProductIds(1), 'Junction should reflect exactly the new set');
        $this->assertNotNull(LinkOnlyProduct::findOne(1), 'A dropped record must only be unlinked, never deleted');
    }

    public function testLinkOnlyComposesWithExtraColumns()
    {
        $product = LinkOnlyProduct::findOne(2);
        $product->sortOrder = 7;
        $owner = LinkOnlyOwner::findOne(1);
        $owner->products = [$product];

        $this->assertTrue($owner->save());

        $sortOrder = new Query()
            ->select('sort_order')
            ->from('lo_owner_product')
            ->where(['owner_id' => 1, 'product_id' => 2])
            ->scalar(Yii::$app->getDb());
        $this->assertEquals(7, $sortOrder, 'extraColumns values should be written to the junction row');
    }

    public function testLinkOnlyHasManyRejectsUnsavedRecord()
    {
        $this->expectException(InvalidArgumentException::class);

        $owner = LinkOnlyOwner::findOne(1);
        $owner->products = [new LinkOnlyProduct(['code' => 'brand-new'])];
        $owner->save();
    }

    public function testNonLinkOnlyStillValidatesAndSavesRelated()
    {
        // Control: the same invalid existing record assigned to a NON-linkOnly relation makes the
        // owner save fail (the behavior re-saves the related record, which fails validation).
        $this->expectException(DbException::class);

        $owner = LinkOnlyOwner::findOne(1);
        $owner->productsStrict = [LinkOnlyProduct::findOne(1)]; // code is null -> invalid
        $owner->save();
    }

    public function testLinkOnlyHasOneWithOwnerSideForeignKey()
    {
        $owner = LinkOnlyOwner::findOne(1);
        $product = LinkOnlyProduct::findOne(1); // code is null -> invalid
        $product->code = 'in-memory-change';    // dirty; must NOT be persisted

        $owner->mainProduct = $product;

        $this->assertTrue($owner->save(), 'linkOnly has-one should link an invalid existing record');
        $this->assertSame([], $owner->getErrors());
        $this->assertEquals(1, $owner->main_product_id, "Owner's foreign key should point at the linked record");

        $reloaded = LinkOnlyProduct::findOne(1);
        $this->assertNull($reloaded->code, 'The related has-one record must not be saved');
    }

    public function testLinkOnlyRejectsForeignKeyOnRelatedRecord()
    {
        // `children` is a has-many whose foreign key lives on the related row: linking would
        // require saving the child, which contradicts linkOnly, so it must be rejected.
        $this->expectException(InvalidConfigException::class);

        $owner = LinkOnlyOwner::findOne(1);
        $owner->children = [LinkOnlyChild::findOne(1)];
        $owner->save();
    }
}

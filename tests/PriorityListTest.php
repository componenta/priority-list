<?php

declare(strict_types=1);

namespace Componenta\Stdlib\Tests;

use InvalidArgumentException;
use Componenta\Stdlib\PriorityList;
use Componenta\Stdlib\SortOrder;
use Componenta\Stdlib\ValidatorFactory;
use PHPUnit\Framework\TestCase;
use stdClass;

final class PriorityListTest extends TestCase
{
    public function testInsertAddsItemsToList(): void
    {
        $list = new PriorityList();
        $list->insert('a', 1)->insert('b', 2);

        $this->assertCount(2, $list);
    }

    public function testInsertWithValidatorAcceptsValidValue(): void
    {
        $list = new PriorityList(ValidatorFactory::string());
        $list->insert('valid');

        $this->assertCount(1, $list);
    }

    public function testInsertWithValidatorRejectsInvalidValue(): void
    {
        $list = new PriorityList(ValidatorFactory::string());

        $this->expectException(InvalidArgumentException::class);
        $list->insert(123);
    }

    public function testContainsFindsExistingValue(): void
    {
        $list = new PriorityList();
        $list->insert('needle', 5);

        $this->assertTrue($list->contains('needle'));
    }

    public function testContainsReturnsFalseForNonExistingValue(): void
    {
        $list = new PriorityList();
        $list->insert('haystack', 1);

        $this->assertFalse($list->contains('needle'));
    }

    public function testContainsStrictDistinguishesTypes(): void
    {
        $list = new PriorityList();
        $list->insert(1, 0);

        $this->assertTrue($list->contains('1', strict: false));
        $this->assertFalse($list->contains('1', strict: true));
    }

    public function testDefaultSortOrderIsDesc(): void
    {
        $list = new PriorityList();
        $list->insert('low', 1)->insert('high', 100);

        $this->assertSame('high', $list->extract());
    }

    public function testExtractReturnsFirstItemByPriorityAsc(): void
    {
        $list = new PriorityList(sortOrder: SortOrder::ASC);
        $list->insert('low', 1)->insert('high', 100)->insert('medium', 50);

        $this->assertSame('low', $list->extract());
    }

    public function testExtractReturnsFirstItemByPriorityDesc(): void
    {
        $list = new PriorityList(sortOrder: SortOrder::DESC);
        $list->insert('low', 1)->insert('high', 100)->insert('medium', 50);

        $this->assertSame('high', $list->extract());
    }

    public function testExtractRemovesItemFromList(): void
    {
        $list = new PriorityList();
        $list->insert('only', 1);

        $list->extract();

        $this->assertTrue($list->isEmpty());
    }

    public function testExtractReturnsNullOnEmptyList(): void
    {
        $list = new PriorityList();

        $this->assertNull($list->extract());
    }

    public function testIteratesInAscOrderByPriority(): void
    {
        $list = new PriorityList(sortOrder: SortOrder::ASC);
        $list->insert('c', 30)->insert('a', 10)->insert('b', 20);

        $values = iterator_to_array($list->getIterator(priorityAsKey: false));

        $this->assertSame(['a', 'b', 'c'], $values);
    }

    public function testIteratesInDescOrderByPriority(): void
    {
        $list = new PriorityList(sortOrder: SortOrder::DESC);
        $list->insert('c', 30)->insert('a', 10)->insert('b', 20);

        $values = iterator_to_array($list->getIterator(priorityAsKey: false));

        $this->assertSame(['c', 'b', 'a'], $values);
    }

    public function testFifoOrderPreservedForEqualPriorities(): void
    {
        $list = new PriorityList();
        $list->insert('first', 10)->insert('second', 10)->insert('third', 10);

        $values = iterator_to_array($list->getIterator(priorityAsKey: false));

        $this->assertSame(['first', 'second', 'third'], $values);
    }

    public function testGetIteratorWithPriorityAsKeyYieldsPriorityAsKey(): void
    {
        $list = (new PriorityList())->asc();
        $list->insert('a', 5)->insert('b', 15);

        $result = iterator_to_array($list->getIterator(priorityAsKey: true));

        $this->assertSame([5 => 'a', 15 => 'b'], $result);
    }

    public function testGetIteratorWithSequentialIndexYieldsIndex(): void
    {
        $list = new PriorityList()->asc();
        $list->insert('a', 100)->insert('b', 200);

        $result = iterator_to_array($list->getIterator(priorityAsKey: false));

        $this->assertSame([0 => 'a', 1 => 'b'], $result);
    }

    public function testOfStringsRejectsNonString(): void
    {
        $list = PriorityList::ofStrings();

        $this->expectException(InvalidArgumentException::class);
        $list->insert(123);
    }

    public function testOfIntsRejectsNonInt(): void
    {
        $list = PriorityList::ofInts();

        $this->expectException(InvalidArgumentException::class);
        $list->insert('not int');
    }

    public function testOfClassRejectsNonInstance(): void
    {
        $list = PriorityList::of(stdClass::class);

        $this->expectException(InvalidArgumentException::class);
        $list->insert('not an object');
    }

    public function testOfClassAcceptsInstance(): void
    {
        $list = PriorityList::of(stdClass::class);
        $obj = new stdClass();

        $list->insert($obj);

        $this->assertTrue($list->contains($obj, strict: true));
    }

    public function testFromArrayWithPlainValues(): void
    {
        $list = PriorityList::fromArray(['a', 'b', 'c']);

        $this->assertCount(3, $list);
        $this->assertTrue($list->contains('a'));
    }

    public function testFromArrayWithPriorityArrays(): void
    {
        $list = PriorityList::fromArray([
            ['value' => 'high', 'priority' => 100],
            ['value' => 'low', 'priority' => 1],
        ]);

        $this->assertSame('high', $list->extract());
    }

    public function testFromArrayWithMixedFormat(): void
    {
        $list = PriorityList::fromArray([
            'plain',
            ['value' => 'prioritized', 'priority' => 10],
        ]);

        $this->assertCount(2, $list);
    }

    public function testIsEmptyReturnsTrueWhenEmpty(): void
    {
        $list = new PriorityList();

        $this->assertTrue($list->isEmpty());
    }

    public function testIsEmptyReturnsFalseWhenNotEmpty(): void
    {
        $list = new PriorityList();
        $list->insert('item');

        $this->assertFalse($list->isEmpty());
    }

    public function testClearRemovesAllItems(): void
    {
        $list = new PriorityList();
        $list->insert('a')->insert('b');

        $list->clear();

        $this->assertTrue($list->isEmpty());
        $this->assertCount(0, $list);
    }

    public function testClearResetsInsertionOrder(): void
    {
        $list = new PriorityList();
        $list->insert('first', 10)->insert('second', 10);
        $list->clear();
        $list->insert('new_first', 10)->insert('new_second', 10);

        $values = iterator_to_array($list->getIterator(priorityAsKey: false));

        $this->assertSame(['new_first', 'new_second'], $values);
    }

    public function testEachCallbackReceivesValueAndPriorityInOrder(): void
    {
        $list = (new PriorityList())->asc();
        $list->insert('b', 20)->insert('a', 10);

        $collected = [];
        $list->each(function (string $value, int $priority) use (&$collected): void {
            $collected[] = [$value, $priority];
        });

        $this->assertSame([['a', 10], ['b', 20]], $collected);
    }

    public function testOrderChangesOrder(): void
    {
        $list = new PriorityList(sortOrder: SortOrder::ASC);
        $list->insert('a', 1)->insert('b', 2);

        $list->order(SortOrder::DESC);
        $values = iterator_to_array($list->getIterator(priorityAsKey: false));

        $this->assertSame(['b', 'a'], $values);
    }

    public function testAscSetsAscendingOrder(): void
    {
        $list = new PriorityList();
        $list->insert('a', 1)->insert('b', 2);

        $list->asc();
        $values = iterator_to_array($list->getIterator(priorityAsKey: false));

        $this->assertSame(['a', 'b'], $values);
    }

    public function testDescSetsDescendingOrder(): void
    {
        $list = new PriorityList(sortOrder: SortOrder::ASC);
        $list->insert('a', 1)->insert('b', 2);

        $list->desc();
        $values = iterator_to_array($list->getIterator(priorityAsKey: false));

        $this->assertSame(['b', 'a'], $values);
    }

    public function testFluentChaining(): void
    {
        $collected = [];

        PriorityList::ofStrings()
            ->insert('low', 1)
            ->insert('high', 100)
            ->asc()
            ->each(function (string $value, int $priority) use (&$collected): void {
                $collected[] = $value;
            });

        $this->assertSame(['low', 'high'], $collected);
    }

    public function testToArrayReturnsItemsWithPriorities(): void
    {
        $list = new PriorityList();
        $list->insert('val', 42);

        $array = $list->toArray();

        $this->assertSame([['value' => 'val', 'priority' => 42]], $array);
    }

    public function testAcceptsReturnsTrueWithoutValidator(): void
    {
        $list = new PriorityList();

        $this->assertTrue($list->accepts('anything'));
        $this->assertTrue($list->accepts(123));
        $this->assertTrue($list->accepts(null));
    }

    public function testAcceptsReturnsTrueForValidValue(): void
    {
        $list = PriorityList::ofStrings();

        $this->assertTrue($list->accepts('valid string'));
    }

    public function testAcceptsReturnsFalseForInvalidValue(): void
    {
        $list = PriorityList::ofStrings();

        $this->assertFalse($list->accepts(123));
        $this->assertFalse($list->accepts(null));
    }

    public function testAcceptsWorksWithInstanceOfValidator(): void
    {
        $list = PriorityList::of(stdClass::class);

        $this->assertTrue($list->accepts(new stdClass()));
        $this->assertFalse($list->accepts('not an object'));
    }
}
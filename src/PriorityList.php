<?php

declare(strict_types=1);

namespace Componenta\Stdlib;

use Countable;
use Componenta\Arrayable\Arrayable;
use Generator;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

/**
 * A sorted collection that maintains items with associated priorities.
 *
 * Items are automatically sorted by priority during iteration. When priorities
 * are equal, items maintain insertion order (FIFO). Supports both ascending and
 * descending sort orders, type validation via callbacks, and provides
 * factory methods for common type-constrained lists.
 *
 * @template T
 * @implements IteratorAggregate<int, T>
 */
class PriorityList implements IteratorAggregate, Countable, Arrayable
{
    /** @var array<int, array{value: T, priority: int, order: int}> */
    protected array $items = [];

    protected bool $isDirty = false;

    protected int $insertionOrder = 0;

    /** @var null|callable(mixed): bool */
    protected $itemValidator = null;

    /**
     * Create a new priority list.
     *
     * @param null|callable(mixed): bool $itemValidator Optional callback to validate items before insertion
     * @param SortOrder $sortOrder Sort direction (ASC or DESC, defaults to DESC)
     */
    public function __construct(
        ?callable $itemValidator = null,
        private(set) SortOrder $sortOrder = SortOrder::DESC
    ) {
        $this->itemValidator = $itemValidator;
    }

    /**
     * Create a list from an iterable of items.
     *
     * Accepts either plain values (assigned priority 0) or arrays with
     * 'value' and 'priority' keys for explicit priority assignment.
     *
     * @template TValue
     * @param iterable<TValue|array{value: TValue, priority: int}> $items
     * @param null|callable(mixed): bool $itemValidator
     * @return static<TValue>
     */
    public static function fromArray(
        iterable $items,
        ?callable $itemValidator = null,
        SortOrder $sortOrder = SortOrder::DESC
    ): static {
        $list = new static($itemValidator, $sortOrder);

        foreach ($items as $item) {
            if (is_array($item) && array_key_exists('value', $item) && array_key_exists('priority', $item)) {
                $list->insert($item['value'], $item['priority']);
            } else {
                $list->insert($item);
            }
        }

        return $list;
    }

    /**
     * Create a list that only accepts string values.
     *
     * @return static<string>
     */
    public static function ofStrings(SortOrder $sortOrder = SortOrder::DESC): static
    {
        return new static(ValidatorFactory::string(), $sortOrder);
    }

    /**
     * Create a list that only accepts integer values.
     *
     * @return static<int>
     */
    public static function ofInts(SortOrder $sortOrder = SortOrder::DESC): static
    {
        return new static(ValidatorFactory::int(), $sortOrder);
    }

    /**
     * Create a list that only accepts float values.
     *
     * @return static<float>
     */
    public static function ofFloats(SortOrder $sortOrder = SortOrder::DESC): static
    {
        return new static(ValidatorFactory::float(), $sortOrder);
    }

    /**
     * Create a list that only accepts boolean values.
     *
     * @return static<bool>
     */
    public static function ofBools(SortOrder $sortOrder = SortOrder::DESC): static
    {
        return new static(ValidatorFactory::bool(), $sortOrder);
    }

    /**
     * Create a list that only accepts array values.
     *
     * @return static<array>
     */
    public static function ofArrays(SortOrder $sortOrder = SortOrder::DESC): static
    {
        return new static(ValidatorFactory::array(), $sortOrder);
    }

    /**
     * Create a list that only accepts object values.
     *
     * @return static<object>
     */
    public static function ofObjects(SortOrder $sortOrder = SortOrder::DESC): static
    {
        return new static(ValidatorFactory::object(), $sortOrder);
    }

    /**
     * Create a list that only accepts callable values.
     *
     * @return static<callable>
     */
    public static function ofCallables(SortOrder $sortOrder = SortOrder::DESC): static
    {
        return new static(ValidatorFactory::callable(), $sortOrder);
    }

    /**
     * Create a list that only accepts instances of the specified class.
     *
     * @template TClass of object
     * @param class-string<TClass> $className Fully qualified class or interface name
     * @return static<TClass>
     */
    public static function of(string $className, SortOrder $sortOrder = SortOrder::DESC): static
    {
        return new static(ValidatorFactory::instanceOf($className), $sortOrder);
    }

    /**
     * Insert a value with the given priority.
     *
     * @param T $value The value to insert
     * @param int $priority Sort priority (higher values come first in DESC order)
     * @throws InvalidArgumentException If validator rejects the value
     */
    public function insert(mixed $value, int $priority = 0): static
    {
        if ($this->itemValidator !== null) {
            $this->validateItem($value);
        }

        $this->items[] = [
            'value' => $value,
            'priority' => $priority,
            'order' => $this->insertionOrder++,
        ];

        $this->isDirty = true;

        return $this;
    }

    /**
     * Check whether the list contains a value.
     *
     * @param T $value The value to search for
     * @param bool $strict Use strict comparison (===) when true
     */
    public function contains(mixed $value, bool $strict = false): bool
    {
        return array_any(
            $this->items,
            static fn($item) => $strict ? $item['value'] === $value : $item['value'] == $value
        );
    }

    /**
     * Remove and return the first item according to current sort order.
     *
     * @return T|null The extracted value, or null if list is empty
     */
    public function extract(): mixed
    {
        if ($this->isEmpty()) {
            return null;
        }

        $this->ensureSorted();

        return array_shift($this->items)['value'];
    }

    /**
     * Execute a callback for each item in priority order.
     *
     * @param callable(T, int): void $callback Receives value and priority as arguments
     */
    public function each(callable $callback): static
    {
        $this->ensureSorted();

        foreach ($this->items as $item) {
            $callback($item['value'], $item['priority']);
        }

        return $this;
    }

    /**
     * Set the sort direction.
     *
     * @param SortOrder $sortOrder ASC for lowest priority first, DESC for highest first
     */
    public function order(SortOrder $sortOrder): static
    {
        if ($this->sortOrder !== $sortOrder) {
            $this->sortOrder = $sortOrder;
            $this->isDirty = true;
        }

        return $this;
    }

    /**
     * Set ascending sort order (lowest priority first).
     */
    public function asc(): static
    {
        return $this->order(SortOrder::ASC);
    }

    /**
     * Set descending sort order (highest priority first).
     */
    public function desc(): static
    {
        return $this->order(SortOrder::DESC);
    }

    /**
     * Get an iterator over the values in priority order.
     *
     * @param bool $priorityAsKey When true, yields priority as key; otherwise sequential index
     * @return Generator<int, T>
     */
    public function getIterator(bool $priorityAsKey = true): Traversable
    {
        $this->ensureSorted();

        foreach ($this->items as $index => $item) {
            yield ($priorityAsKey ? $item['priority'] : $index) => $item['value'];
        }
    }

    /**
     * Get all items as an array with their priorities.
     *
     * @return array<int, array{value: T, priority: int}>
     */
    public function toArray(): array
    {
        return array_map(
            static fn(array $item) => ['value' => $item['value'], 'priority' => $item['priority']],
            $this->items
        );
    }

    /**
     * Get the number of items in the list.
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Check whether the list contains no items.
     */
    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * Remove all items from the list.
     */
    public function clear(): static
    {
        $this->items = [];
        $this->isDirty = false;
        $this->insertionOrder = 0;

        return $this;
    }

    /**
     * Sort items by priority, then by insertion order (FIFO) for equal priorities.
     */
    protected function ensureSorted(): void
    {
        if (!$this->isDirty) {
            return;
        }

        usort($this->items, fn(array $a, array $b) => match ($this->sortOrder) {
            SortOrder::ASC => $a['priority'] <=> $b['priority'] ?: $a['order'] <=> $b['order'],
            SortOrder::DESC => $b['priority'] <=> $a['priority'] ?: $a['order'] <=> $b['order'],
        });

        $this->isDirty = false;
    }

    /**
     * Check whether a value can be inserted into the list.
     *
     * Returns true if no validator is set or if the validator accepts the value.
     *
     * @param T $value The value to check
     */
    public function accepts(mixed $value): bool
    {
        return $this->itemValidator === null ? true : ($this->itemValidator)($value);
    }

    /**
     * Validate a value against the configured validator.
     *
     * @throws InvalidArgumentException If validator returns false
     */
    protected function validateItem(mixed $value): void
    {
        if (!($this->itemValidator)($value)) {
            throw new InvalidArgumentException(
                sprintf('Value of type %s rejected by validator', get_debug_type($value))
            );
        }
    }
}

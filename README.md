# Componenta Priority List

Priority-ordered collection with optional value validation.

Use it for middleware lists, resolver chains, event listeners, plugin hooks, and any ordered registry where equal priorities must keep insertion order.

## Installation

```bash
composer require componenta/priority-list
```

## Related Packages

This package is standalone.

| Package | Why it may be used nearby |
|---|---|
| `componenta/event` | Event listener priority uses the same ordering model. |
| `componenta/pipeline` | Middleware lists can be ordered before pipeline construction. |
| `componenta/di` | Resolver/provider chains can be registered with priorities. |

## Usage

```php
use Componenta\Stdlib\PriorityList;

$list = PriorityList::ofStrings()
    ->insert('low', 10)
    ->insert('high', 100);

$list->extract(); // "high"
```

The default order is descending priority. Equal priorities keep insertion order.

```php
$list->asc();  // lowest priority first
$list->desc(); // highest priority first
```

## Validation

Factory methods restrict accepted values:

- `ofStrings()`
- `ofInts()`
- `ofFloats()`
- `ofBools()`
- `ofArrays()`
- `ofObjects()`
- `ofCallables()`
- `of(SomeClass::class)`

Custom validators can be passed to the constructor. Rejected values throw `InvalidArgumentException` on `insert()`.

## Iteration

`PriorityList` implements:

- `IteratorAggregate`
- `Countable`
- `Componenta\Arrayable\Arrayable`

Iteration sorts lazily. The list marks itself dirty on insert/order changes and sorts only before iteration, extraction, or `each()`.

`getIterator()` yields priority as key by default. `toArray()` returns items with both value and priority.

## Mutability

The collection is mutable. Methods such as `insert`, `asc`, `desc`, `order`, and `clear` mutate the current list and return it for fluent usage.

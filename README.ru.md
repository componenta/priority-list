# Componenta Priority List

Priority-ordered collection с optional value validation.

Используйте его для списков промежуточных обработчиков, цепочек резолверов, слушателей событий, точек расширения и любых упорядоченных реестров, где одинаковые приоритеты должны сохранять порядок вставки.

## Установка

```bash
composer require componenta/priority-list
```

## Связанные пакеты

Пакет самодостаточный и не требует соседних библиотек.

| Пакет | Зачем может использоваться рядом |
|---|---|
| `componenta/event` | Приоритеты слушателей событий используют ту же модель упорядочивания. |
| `componenta/pipeline` | Упорядоченные списки промежуточных обработчиков можно собирать через список приоритетов до создания конвейера. |
| `componenta/di` | Цепочки резолверов и провайдеров можно регистрировать с приоритетами. |

## Использование

```php
use Componenta\Stdlib\PriorityList;

$list = PriorityList::ofStrings()
    ->insert('low', 10)
    ->insert('high', 100);

$list->extract(); // "high"
```

Default order — descending priority. Equal priorities сохраняют insertion order.

```php
$list->asc();  // lowest priority first
$list->desc(); // highest priority first
```

## Валидация

Фабричные методы ограничивают допустимые значения:

- `ofStrings()`
- `ofInts()`
- `ofFloats()`
- `ofBools()`
- `ofArrays()`
- `ofObjects()`
- `ofCallables()`
- `of(SomeClass::class)`

Пользовательские валидаторы можно передать в конструктор. Отклонённые значения выбрасывают `InvalidArgumentException` при `insert()`.

## Iteration

`PriorityList` реализует:

- `IteratorAggregate`
- `Countable`
- `Componenta\Arrayable\Arrayable`

Iteration сортируется лениво. List помечается dirty при insert/order changes и сортируется только перед iteration, extraction или `each()`.

`getIterator()` по умолчанию yield-ит priority как key. `toArray()` возвращает items с value и priority.

## Mutability

Collection мутабельна. Методы `insert`, `asc`, `desc`, `order` и `clear` меняют текущий list и возвращают его для fluent usage.

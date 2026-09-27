<?php // lint >= 8.0

namespace VarTagReflectsUsages;

/** @template T */
class Collection
{

	/** @param array<T> $items */
	public function __construct(array $items)
	{
	}

	/** @param T $item */
	public function add($item): void
	{
	}

}

class Foo
{

}

class Bar
{

}

class Test
{

	public function nullThenFoo(): void
	{
		/** @var Foo|null $a */
		$a = null;
		if (rand(0, 1) === 1) {
			$a = new Foo();
		}
		var_dump($a);
	}

	public function tooWide(): void
	{
		/** @var Foo|Bar|null $a */
		$a = null;
		$a = new Foo();
		var_dump($a);
	}

	public function wrongElement(): void
	{
		/** @var list<int> $a */
		$a = [];
		$a[] = 'x';
		var_dump($a);
	}

	public function rightElement(): void
	{
		/** @var list<int> $a */
		$a = [];
		$a[] = 1;
		$a[] = 2;
		var_dump($a);
	}

	/** @param list<int> $items */
	public function counter(array $items): void
	{
		/** @var int $count */
		$count = 0;
		foreach ($items as $item) {
			$count++;
		}
		var_dump($count);
	}

	public function collection(): void
	{
		/** @var Collection<int|string> $c */
		$c = new Collection([]);
		$c->add(1);
	}

	public function collectionOk(): void
	{
		/** @var Collection<int> $c */
		$c = new Collection([]);
		$c->add(1);
	}

	public function byRefClosure(): void
	{
		/** @var list<int> $a */
		$a = [];
		$push = function (string $s) use (&$a): void {
			$a[] = $s;
		};
		$push('x');
		var_dump($a);
	}

	public function variableLess(): void
	{
		/** @var list<int> */
		$a = [];
		$a[] = 'x';
		var_dump($a);
	}

	public function notADeclaration(Foo $foo): void
	{
		/** @var Foo $a */
		$a = $foo;
		var_dump($a);
	}

	/** @return list<int> */
	public function neverAssignedAgain(): array
	{
		/** @var list<int> $a */
		$a = [];
		return $a;
	}

	public function neverAssignedAgainNull(): ?Foo
	{
		/** @var Foo|null $a */
		$a = null;
		return $a;
	}

	/** @return Collection<int> */
	public function collectionNeverUsed(): Collection
	{
		/** @var Collection<int> $c */
		$c = new Collection([]);
		return $c;
	}

	public function byRefArgument(string $s): void
	{
		/** @var array<int, string> $m */
		$m = [];
		preg_match('~a~', $s, $m);
		var_dump($m);
	}

	public function wrongObject(): void
	{
		/** @var Foo|null $a */
		$a = null;
		if (rand(0, 1) === 1) {
			$a = new Bar();
		}
		var_dump($a);
	}

	/** @param array<mixed> $items */
	public function fromUntypedSource(array $items): void
	{
		/** @var array<string, Foo> $map */
		$map = [];
		foreach ($items as $key => $item) {
			$map[$key] = $item;
		}
		var_dump($map);
	}

}

function staticCache(string $key): string
{
	/** @var array<string, int> $cache */
	static $cache = [];
	if (!isset($cache[$key])) {
		$cache[$key] = strtoupper($key);
	}

	return (string) $cache[$key];
}

function staticCacheOk(string $key): int
{
	/** @var array<string, int> $cache */
	static $cache = [];
	if (!isset($cache[$key])) {
		$cache[$key] = strlen($key);
	}

	return $cache[$key];
}

function staticVariableLess(): void
{
	/** @var int|null */
	static $instance = null;
	$instance = 'x';
}

/** @template T of bool */
class Transaction
{

	/** @param T $value */
	public function commit($value): void
	{
	}

}

/** @template TKey of int|string */
class Entity
{

	public function __construct(string $name)
	{
	}

}

/**
 * @template T
 * @param Entity<T> $entity
 */
function sendEntity(Entity $entity): void
{
}

function moreSpecificTemplateArgument(): void
{
	/** @var Transaction<true> $transaction */
	$transaction = new Transaction();
	$transaction->commit(true);
}

function unconstrainedTemplateArgument(): void
{
	/** @var Entity<int> $entity */
	$entity = new Entity('id');
	sendEntity($entity);
}

function straightLine(): void
{
	/** @var array<int> $a */
	$a = [];
	$a[] = 1;
	$a[] = 'foo';
	var_dump($a);
}

function literalsFit(): void
{
	/** @var array<1|2> $a */
	$a = [];
	$a[] = 1;
	$a[] = 2;
	var_dump($a);
}

function literalsDoNotFit(): void
{
	/** @var array<1|2> $a */
	$a = [];
	$a[] = 1;
	$a[] = 3;
	var_dump($a);
}

/** @param array<string> $items */
function benevolentKeys(array $items): void
{
	/** @var list<int> $keys */
	$keys = [];
	foreach ($items as $key => $item) {
		$keys[] = $key;
	}
	var_dump($keys);
}

function eachWrite(): void
{
	/** @var array<int> $a */
	$a = [];
	$a[] = 'foo';
	$a[] = 1;
	$a[50] = 1.5;
	foreach ([1, 2] as $i) {
		$a[] = $i;
		$a[] = 'x';
	}
	var_dump($a);
}

function compoundAssignment(): void
{
	/** @var 'a'|'b' $s */
	$s = 'a';
	$s .= 'x';
	$s .= 'y';
	var_dump($s);
}

function increment(): void
{
	/** @var int<0, 5> $n */
	$n = 0;
	$n++;
	--$n;
	var_dump($n);
}

function offsetOutsideList(): void
{
	/** @var list<int> $l */
	$l = [];
	$l[50] = 1;
	$l[] = 1;
	var_dump($l);
}

function destructuring(): void
{
	/** @var int $d */
	$d = 0;
	[$d, $e] = ['x', 1];
	['k' => $d] = ['k' => 2];
	var_dump($d, $e);
}

/**
 * @param list<array{key: string, amount: int, id: int}> $rows
 */
function shapeBuiltInPieces(array $rows): void
{
	/** @var array<string, array{amountInCents: int, ids: list<int>, flag: bool}> $settlements */
	$settlements = [];
	foreach ($rows as $row) {
		$settlements[$row['key']] ??= ['amountInCents' => 0, 'ids' => [], 'flag' => false];
		$settlements[$row['key']]['amountInCents'] += $row['amount'];
		$settlements[$row['key']]['ids'][] = $row['id'];
		if ($row['amount'] > 5) {
			$settlements[$row['key']]['flag'] = true;
		}
	}
	var_dump($settlements);
}

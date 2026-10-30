<?php // lint >= 8.0

namespace RedundantVarTag;

class Foo
{

	public function getString(): string
	{
		return '';
	}

	/** @return list<int> */
	public function getList(): array
	{
		return [];
	}

	/** @return array<string, mixed> */
	public function getConfig(): array
	{
		return [];
	}

	/** @return mixed */
	public function getExplicitMixed()
	{
		return null;
	}

	public function getImplicitMixed()
	{
		return null;
	}

	public function doAssign(): void
	{
		/** @var string $a */
		$a = $this->getString();

		/** @var string */
		$b = $this->getString();

		/** @phpstan-var string $c */
		$c = $this->getString();

		/** @var non-empty-string $d */
		$d = $this->getString();

		/** @var string $e */
		$e = 'foo';

		/** @var list<int> $f */
		$f = [];

		/** @var list<int> $g */
		$g = $this->getList();

		/** @var array<string, mixed> $h */
		$h = $this->getConfig();

		/** @var mixed $i */
		$i = $this->getExplicitMixed();

		/** @var mixed $j */
		$j = $this->getImplicitMixed();

		/** @var string $a */
		$a .= $this->getString();

		/** @var int $k */
		[$k, $l] = $this->getList();

		/** @var string $m */
		$m = &$a;
	}

	public function doForeach(): void
	{
		$list = $this->getList();

		/** @var int $value */
		foreach ($list as $value) {
		}

		/** @var int<0, max> $key */
		foreach ($list as $key => $value) {
		}

		/** @var list<int> $list */
		foreach ($list as $value) {
		}

		/** @var positive-int $value */
		foreach ($list as $value) {
		}

		/** @var list<positive-int> $list */
		foreach ($list as $value) {
		}
	}

	public function doStatic(): void
	{
		/** @var null $a */
		static $a = null;
	}

	public function doLoop(): void
	{
		$n = 0;
		while ($this->getString() !== '') {
			/** @var int $n */
			$n = $n + 1;
		}
	}

	public function doStatement(string $s): void
	{
		/** @var string $s */
		echo $s;

		/** @var non-empty-string $s */
		echo $s;
	}

	public function doReturn(): string
	{
		/** @var string */
		return $this->getString();
	}

	public function doReturnNarrowed(): string
	{
		/** @var non-empty-string */
		return $this->getString();
	}

	public function doThrow(\Exception $e): void
	{
		/** @var \Exception */
		throw $e;
	}

	public function doNewGeneric(): void
	{
		/** @var Box<int> $box */
		$box = new Box();
	}

}

/** @template T */
class Box
{

}

trait FooTrait
{

	public function doTrait(): void
	{
		/** @var string $a */
		$a = $this->getValue();
	}

}

class UsesTraitWithString
{

	use FooTrait;

	public function getValue(): string
	{
		return '';
	}

}

class UsesTraitWithMixed
{

	use FooTrait;

	/** @return mixed */
	public function getValue()
	{
		return '';
	}

}

interface Container
{

	public function get(string $id): object;

}

class ReviewCases
{

	/** @var array<string, string> */
	private array $arr = [];

	/** @var int */
	const BAR = 1;

	public function __construct(private Container $container)
	{
	}

	/** @return non-empty-string */
	public function returnOffset(): string
	{
		/** @var non-empty-string */
		return $this->arr['a'];
	}

	public function returnOffsetUnchanged(): string
	{
		/** @var string */
		return $this->arr['a'];
	}

	/** @return non-empty-string */
	public function returnOffsetNarrowed(): string
	{
		if ($this->arr['a'] !== '') {
			/** @var non-empty-string */
			return $this->arr['a'];
		}

		return 'a';
	}

	/**
	 * @template T of object
	 * @param class-string<T> $class
	 * @return T
	 */
	public function templateType(string $class): object
	{
		/** @var T $service */
		$service = $this->container->get($class);
		return $service;
	}

	/** @param int|string $key */
	public function benevolentUnion($key): void
	{
		/** @var array-key $k */
		$k = $key;
	}

	/** @return list<int> */
	public function getList(): array
	{
		return [];
	}

	/** @return list<mixed> */
	public function getExplicitMixedList(): array
	{
		return [];
	}

	/** @return array{0?: int, 1?: int} */
	public function getMaybeList(): array
	{
		return [];
	}

	public function overridingTag(): void
	{
		/**
		 * @var array $a
		 * @phpstan-var list<int> $a
		 */
		$a = $this->getList();
	}

	public function implicitMixedTag(): void
	{
		/** @var list $d */
		$d = $this->getExplicitMixedList();
	}

	public function listness(): void
	{
		/** @var list{0?: int, 1?: int} $x */
		$x = $this->getMaybeList();
	}

	public function narrowedConstant(): void
	{
		if (static::BAR === 1) {
			/** @var int $x */
			$x = static::BAR;
		}
	}

	public function foreachInLoop(): void
	{
		$items = [1];
		while (rand(0, 1) === 1) {
			/** @var int $item */
			foreach ($items as $item) {
				$items[] = $item;
			}
		}
	}

	public function secondVariable(): void
	{
		$m = 0;
		while (rand(0, 1) === 1) {
			$k = $m + 1;
			/** @var int $m */
			$m = $k;
		}
	}

	public function reference(): void
	{
		$a = 0;
		while (rand(0, 1) === 1) {
			/** @var int $m */
			$m = &$a;
			$m++;
		}
	}

	public function byRefClosure(): void
	{
		$count = 0;
		$f = function () use (&$count): void {
			/** @var int $count */
			$count = $count + 1;
		};
		$f();
	}

	public function staticVariable(): int
	{
		static $last = 0;
		/** @var int $id */
		$id = $last + 1;
		$last = $id;
		return $id;
	}

	/** @param list<int> $list */
	public function taggedIteratee(array $list): void
	{
		/**
		 * @var list<positive-int> $list
		 * @var int $value
		 */
		foreach ($list as $value) {
		}
	}

	public function arrayLiteralIteratee(int $a, int|string $b): void
	{
		/** @var int */
		foreach ([$a, $b] as $v) {
		}
	}

	public function maybeDefinedIteratee(bool $b): void
	{
		if ($b) {
			$list = $this->getList();
		}

		/** @var list<int> $list */
		foreach ($list as $v) {
		}
	}

	public function maybeDefinedStatement(bool $b): void
	{
		if ($b) {
			$x = $this->getList();
		}

		/** @var list<int> $x */
		echo 'x';
	}

	/** @return array{int, string} */
	public function pair(): array
	{
		return [1, 'a'];
	}

	public function destructureIntoExisting(): void
	{
		$a = null;
		/** @var int $a */
		[$a, $b] = $this->pair();
	}

	/** @return array<int, int> */
	public function map(): array
	{
		return [];
	}

	public function twoTags(): void
	{
		/**
		 * @var int $k
		 * @var int $v
		 */
		foreach ($this->map() as $k => $v) {
		}
	}

	public function closureTemplateFrame(): void
	{
		$box = new Box();
		$f = function () use ($box): void {
			/** @var Box<int> $b */
			$b = $box;
		};
		$f();
	}

}

class MoreCases
{

	/** @return non-empty-list<int> */
	public function getNonEmptyList(): array
	{
		return [1];
	}

	/** @return array-key */
	public function getArrayKey()
	{
		return 1;
	}

	public function getInt(): int
	{
		return 1;
	}

	public function wideningNonEmpty(): void
	{
		/** @var list<int> $x */
		$x = $this->getNonEmptyList();
	}

	public function iterateeAlreadyNonEmpty(): void
	{
		$list = $this->getNonEmptyList();
		/** @var list<int> $list */
		foreach ($list as $v) {
		}
	}

	public function plainUnionOverBenevolent(): void
	{
		/** @var int|string $k */
		$k = $this->getArrayKey();
	}

	public function unresolvableType(): void
	{
		/** @var self::NOPE $x */
		$x = self::NOPE;
	}

	public function namedAndVariableLess(): void
	{
		/** @var int $x */
		/** @var string */
		$x = $this->getInt();
	}

	public function returnAssign(): int
	{
		/** @var int */
		return $x = $this->getInt();
	}

	public function commentBeforeDoc(): void
	{
		// the doc comment is not the only comment
		/** @var int $x */
		$x = $this->getInt();
	}

}

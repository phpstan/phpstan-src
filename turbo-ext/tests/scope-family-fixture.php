<?php declare(strict_types = 1);

namespace ScopeFamilyFixture;

use Serializable;

const ANSWER = 42;

// closures bound by Closure::bind() to a newScope that is not exactly one
// known class (top level: the walk keeps it), so the walk scopes inside them
// are bound to an unknown class, to several classes, or to none
/** @var class-string $plainScope */
$plainScope = Holder::class;
/** @var class-string<Child>|class-string<Sibling> $siblingScope */
$siblingScope = Child::class;
/** @var class-string<Holder>|class-string<Child> $unrelatedScope */
$unrelatedScope = Holder::class;
/** @var 'static'|null $noScope */
$noScope = null;
\Closure::bind(static function (): void {
	$inPlain = 1;
}, null, $plainScope);
\Closure::bind(static function (): void {
	$inSibling = 1;
}, null, $siblingScope);
\Closure::bind(static function (): void {
	$inUnrelated = 1;
}, null, $unrelatedScope);
\Closure::bind(static function (): void {
	$inNoScope = 1;
}, null, $noScope);
\Closure::bind(static function (): void {
	$inStatic = 1;
}, null, 'static');

trait HelperTrait
{

	public function fromTrait(int $x): int
	{
		$doubled = $x * 2;
		if ($doubled > 4) {
			$maybe = 'yes';
		}

		return $doubled;
	}

}

final class Holder
{

	use HelperTrait;

	public int $counter = 0;

	public function __construct(
		public readonly string $name,
		private readonly ?Holder $inner = null,
	)
	{
		$this->counter = 1;
		if (class_exists('Nope\\Missing') && function_exists('nope_missing')) {
			$this->counter = 2;
		}
		if (defined('ScopeFamilyFixture\\ANSWER')) {
			$this->counter = ANSWER;
		}
	}

	public function read(): string
	{
		if ($this->inner !== null && $this->inner->name !== '') {
			return $this->inner->name . $this->name;
		}

		return $this->name;
	}

	public function find(string $key): ?string
	{
		return $key === '' ? null : $key;
	}

	public function lookup(self $other, string $key): string
	{
		if ($other->find($key) === null) {
			return '';
		}
		$key = 'b';

		return $key . $other->find($key);
	}

	public function stat(string $path): void
	{
		if (is_file($path) && \file_exists($path)) {
			clearstatcache();
		}
		$level = ob_get_level();
		$error = \openssl_error_string();
		if ($error !== false && $level > 0) {
			echo $error;
		}
		if (isset($_GET['x']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
			echo 'get';
		}
	}

}

final class Custom implements Serializable
{

	public function __construct(public readonly int $value)
	{
		if ($this->value > 1) {
			$big = true;
		}
	}

	public function serialize(): ?string
	{
		return null;
	}

	public function unserialize(string $data): void
	{
	}

}

/**
 * @param list<int> $items
 */
function walk(array $items, ?string $prefix = null): string
{
	$out = '';
	foreach ($items as $i => $item) {
		$out .= $prefix . $item;
	}

	$mapped = array_map(static fn (int $item): int => $item + 1, $items);
	$closure = function (int $y) use ($out): string {
		$z = $y + 1;
		return $out . $z;
	};

	if (rand() > 3) {
		$sometimes = 'x';
	}

	extract(['dynamic' => 1]);

	return $out . $closure(count($mapped)) . ($sometimes ?? '') . PHP_EOL;
}

$global = walk([1, 2]);
if (rand() > 5) {
	$maybeGlobal = new Holder('a');
}
echo $global;

abstract class Base
{

	public const PUBLIC_CONST = 1;
	protected const PROTECTED_CONST = 2;
	private const PRIVATE_CONST = 3;

	public private(set) string $tag = '';

	protected int $protectedCounter = 0;

	private int $privateCounter = 0;

	protected function protectedMethod(): int
	{
		return $this->protectedCounter + self::PRIVATE_CONST;
	}

	private function privateMethod(): int
	{
		return $this->privateCounter;
	}

	public function publicMethod(): int
	{
		return $this->protectedMethod() + $this->privateMethod() + self::PUBLIC_CONST;
	}

}

final class Child extends Base
{

	protected const CHILD_PROTECTED_CONST = 5;

	protected int $childCounter = 0;

	protected function protectedMethod(): int
	{
		return parent::protectedMethod() + static::PROTECTED_CONST + $this->childCounter;
	}

}

final class Sibling extends Base
{

	public function siblingMethod(): int
	{
		return $this->protectedCounter + self::PUBLIC_CONST;
	}

}

/**
 * Closure::bind() calls for ParametersAcceptorSelector::selectFromArgs():
 * the bound closure's @param-closure-this type narrows the $newThis parameter
 * wherever the closure argument is written
 *
 * @param-closure-this \stdClass $c
 */
function bindNamed(\Closure $c): void
{
	\Closure::bind($c, new Holder('a'));
	\Closure::bind(closure: $c, newThis: new Holder('b'));
	\Closure::bind(newThis: new Holder('c'), closure: $c);
	\Closure::bind(newScope: Holder::class, newThis: new Holder('d'), closure: $c);
	\Closure::bind(newThis: $c, closure: function (): void {
	});
}

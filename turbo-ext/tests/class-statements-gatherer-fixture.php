<?php declare(strict_types = 1);

// Analysed (never executed) by class-statements-gatherer.php: every branch of
// ClassStatementsGatherer::gatherNodes() and its three helpers is reached from
// the classes below.

namespace ClassStatementsGathererFixture;

trait HelperTrait
{

	private int $fromTrait = 0;

	public function traitMethod(): int
	{
		$this->fromTrait++;

		return $this->fromTrait + self::LIMIT;
	}

}

class Base
{

	public function __construct(
		protected int $baseProp,
		public string $basePublic = '',
		private int $basePrivate = 0,
	)
	{
	}

	public function basePrivate(): int
	{
		return $this->basePrivate;
	}

}

final class Gathered extends Base
{

	use HelperTrait;

	public const LIMIT = 3;

	private const NAMES = ['a', 'b'];

	/** @var array<string|int, int> */
	private array $map = [];

	private ?self $next = null;

	private static int $counter = 0;

	public function __construct(
		private int $promoted,
		private readonly string $name,
		int $plain,
		public string $hooked { get => 'hooked'; },
	)
	{
		parent::__construct($plain);
		Base::__construct($plain, 'again');
		$this->map['x'] = $promoted;
		$this->map[] = $plain;
		$copy = $name;
		$promoted = $promoted + 1;
		echo $copy, $hooked;
		$inner = function () use ($name): string {
			return $name;
		};
		echo $inner();
	}

	public function reads(): int
	{
		$vars = get_object_vars($this);
		array_walk($this, static function (): void {
		});
		GET_OBJECT_VARS($this->next);
		$callable = [$this, 'reads'];
		$notCallable = [$this, 'reads', 'extra'];
		$fcc = $this->reads(...);
		$scc = self::create(...);
		$fn = strlen(...);
		$this->next ??= self::create();
		$this->map['z'] ??= 5;
		$ref = &$this->map;
		$staticRef = &self::$counter;
		$other = &$vars;
		self::$counter++;
		$x = $this->map['x'] ?? 0;
		$deep = $this->map['a']['b'] ?? null;
		$y = static::$counter;
		$z = self::LIMIT + count(self::NAMES) + static::LIMIT + Base::class;
		$this->promoted = $this->promoted + 1;
		$this->map['y'] = 2;
		$this->map[] = 3;
		self::$counter = 4;
		[$this->map['p'], $this->map['q']] = [1, 2];

		return $x + $y + $z + count($vars) + strlen($this->name) + $ref['x'] + $other['x'] + $staticRef;
	}

	public static function create(): self
	{
		return new self(1, 'a', 2, 'b');
	}

	public function nested(): void
	{
		$anon = new class {

			private int $inner = 1;

			public function get(): int
			{
				return $this->inner;
			}

		};
		$closure = function (): int {
			return $this->promoted;
		};
		$arrow = fn (): int => $this->promoted;
		echo $anon->get() + $closure() + $arrow();
	}

	public function __toString(): string
	{
		return $this->name;
	}

}

final class WithoutConstructor extends Base
{

	public function reads(): int
	{
		return $this->baseProp;
	}

}

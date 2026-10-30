<?php // lint >= 8.0

namespace MisplacedPhpStanIgnoreVar;

/**
 * @var int
 * @phpstan-ignore-var
 */
const FOO = 1;

/**
 * @phpstan-ignore-var
 */
function doFoo(): void
{
	/**
	 * @var int $i
	 * @phpstan-ignore-var
	 */
	$i = 1;
}

/**
 * @phpstan-ignore-var
 */
class Foo
{

	/**
	 * @var list<int>
	 * @phpstan-ignore-var
	 */
	public array $ids = [];

	/**
	 * @var non-empty-string
	 * @phpstan-ignore-var
	 */
	public const NAME = 'foo';

	/**
	 * @phpstan-ignore-var
	 */
	public function __construct(
		/**
		 * @var list<int>
		 * @phpstan-ignore-var
		 */
		public array $promoted,
	)
	{
	}

}

/**
 * @phpstan-ignore-var
 */
interface Bar
{

}

/**
 * @phpstan-ignore-var
 */
trait Baz
{

}

class UsesTrait
{

	/**
	 * @var int $x
	 * @phpstan-ignore-var
	 */
	use Baz;

}

class OneLineAndParameters
{

	/** @var list<int> @phpstan-ignore-var */
	public array $oneLine = [];

	public function doFoo(
		/**
		 * @var int
		 * @phpstan-ignore-var
		 */
		$notPromoted,
	): void
	{
		$f = /** @phpstan-ignore-var */ function (
			/** @phpstan-ignore-var */
			$x,
		): void {
		};
		$g = /** @phpstan-ignore-var */ fn () => 1;
	}

	/**
	 * @phpstan-ignore-var
	 */
}

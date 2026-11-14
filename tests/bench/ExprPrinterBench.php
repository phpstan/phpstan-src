<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PHPStan\Node\Printer\ExprPrinter;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\Linear;
use Tally\ScratchDirectory;
use function strlen;

final class ExprPrinterBench
{

	private ?ExprPrinter $printer = null;

	/** @var list<Expr> */
	private array $levels = [];

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function depths(): iterable
	{
		foreach ([16, 64, 256] as $depth) {
			yield 'depth-' . $depth => [$depth];
		}
	}

	#[Before]
	public function boot(int $depth): void
	{
		$container = BenchContainer::create($this->scratch);
		$this->printer = $container->getByType(ExprPrinter::class);
		foreach (self::levels(4) as $level) {
			$this->printer->printExpr($level);
		}

		$this->levels = self::levels($depth);
	}

	/**
	 * The analyser asks for the key of each level of $a->p1->p2…, innermost first, and printing
	 * a level prints the levels below it.
	 */
	#[DataProvider(method: 'depths')]
	#[Linear(size: 'depth')]
	public function benchPrintEveryLevelOfChain(int $depth): int
	{
		if ($this->printer === null) {
			throw new LogicException('boot() did not run.');
		}

		$length = 0;
		foreach ($this->levels as $level) {
			$length += strlen($this->printer->printExpr($level));
		}

		return $length;
	}

	/**
	 * @return list<Expr> innermost first
	 */
	private static function levels(int $depth): array
	{
		$expr = new Variable('a');
		$levels = [];
		for ($i = 1; $i <= $depth; $i++) {
			$expr = new PropertyFetch($expr, 'p' . $i);
			$levels[] = $expr;
		}

		return $levels;
	}

}

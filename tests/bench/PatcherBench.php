<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use Closure;
use LogicException;
use PHPStan\Fixable\Patcher;
use ReflectionMethod;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\Logarithmic;
use Tally\ScratchDirectory;
use function count;

final class PatcherBench
{

	/** @var (Closure(list<array{int, int, list<string>}>, array{int, int, list<string>}): ?int)|null */
	private ?Closure $findInsertionIndex = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{list<array{int, int, list<string>}>}>
	 */
	public static function acceptedRuns(): iterable
	{
		foreach ([16, 64, 256, 1024] as $runs) {
			yield 'runs-' . $runs => [self::runs($runs)];
		}
	}

	/**
	 * @param list<array{int, int, list<string>}> $runs
	 */
	#[Before]
	public function boot(array $runs): void
	{
		BenchContainer::create($this->scratch);
		// private, Patcher::apply() calls it for each change
		$this->findInsertionIndex = (new ReflectionMethod(Patcher::class, 'findInsertionIndex'))->getClosure();
		($this->findInsertionIndex)(self::runs(4), [15, 1, ['warmup']]);
	}

	/**
	 * Applying a fix finds where its lines go among the changes accepted so far by binary search.
	 *
	 * @param list<array{int, int, list<string>}> $runs
	 */
	#[DataProvider(method: 'acceptedRuns')]
	#[Logarithmic(size: 'runs')]
	public function benchFindInsertionIndex(array $runs): ?int
	{
		if ($this->findInsertionIndex === null) {
			throw new LogicException('boot() did not run.');
		}

		// between two runs in the middle
		return ($this->findInsertionIndex)($runs, [count($runs) * 5 + 3, 1, ['new']]);
	}

	/**
	 * @return list<array{int, int, list<string>}> one changed line every 10 lines
	 */
	private static function runs(int $runs): array
	{
		$accepted = [];
		for ($i = 0; $i < $runs; $i++) {
			$accepted[] = [$i * 10, 1, ['changed']];
		}

		return $accepted;
	}

}

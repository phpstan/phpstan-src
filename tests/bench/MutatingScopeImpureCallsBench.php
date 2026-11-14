<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\MutatingScope;
use Tally\Attribute\Before;
use Tally\Attribute\Expected;
use Tally\Attribute\Warmup;
use Tally\ScratchDirectory;
use function count;
use function str_repeat;

#[Warmup(calls: 1)]
final class MutatingScopeImpureCallsBench
{

	private ?MutatingScope $scope = null;

	private ?MutatingScope $otherScope = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	#[Before]
	public function boot(): void
	{
		$calls = str_repeat('$this->value(); self::create(); helper(); ', 16);
		$analysed = AnalysedCode::analyse(
			$this->scratch,
			'<?php function helper(): int { return 1; } class Foo { public function value(): int { return 1; } public static function create(): int { return 1; } '
			. 'public function test(bool $b): int { if ($b) { ' . $calls . 'return 1; } ' . $calls . 'return 2; } }',
		);
		$returns = $analysed->nodes(Return_::class);
		$scope = $returns[count($returns) - 2][1];
		$otherScope = $returns[count($returns) - 1][1];
		$this->scope = $scope;
		$this->otherScope = $otherScope;
		$scope->mergeWith($scope);
	}

	/**
	 * Merging two branches after calls to user functions and methods that are possibly impure.
	 */
	#[Expected(instructions: '75.45K', cycles: '307.0K', memory: '3.523KiB')]
	public function benchMergeWithAfterPossiblyImpureCalls(): MutatingScope
	{
		if ($this->scope === null || $this->otherScope === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->scope->mergeWith($this->otherScope);
	}

}

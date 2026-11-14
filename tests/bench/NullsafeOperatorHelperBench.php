<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;
use Tally\ScratchDirectory;
use function array_reverse;

final class NullsafeOperatorHelperBench
{

	private ?MutatingScope $scope = null;

	private ?Expr $chain = null;

	/** @var list<Expr> */
	private array $levels = [];

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int, bool}>
	 */
	public static function chains(): iterable
	{
		yield 'innermost-nullsafe-64' => [64, true];
		yield 'without-nullsafe-64' => [64, false];
	}

	/**
	 * @return iterable<string, array{int, bool}>
	 */
	public static function chainsWithoutNullsafe(): iterable
	{
		yield 'without-nullsafe-8' => [8, false];
	}

	/**
	 * @return iterable<string, array{int, bool}>
	 */
	public static function levelsWithoutNullsafe(): iterable
	{
		foreach ([16, 64] as $depth) {
			yield 'levels-' . $depth => [$depth, false];
		}
	}

	#[Before]
	public function boot(int $depth, bool $nullsafe): void
	{
		$container = BenchContainer::create($this->scratch);
		$scope = $container->getByType(ScopeFactory::class)->create(ScopeContext::create($this->scratch->path() . '/file.php'));
		$scope = $scope->assignVariable('w', new MixedType(true), new MixedType(true), TrinaryLogic::createYes());
		$this->scope = $scope->assignVariable('a', new MixedType(true), new MixedType(true), TrinaryLogic::createYes());

		NullsafeOperatorHelper::getNullsafeShortcircuitedExpr(self::chain('w', 4, true));
		NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope($this->scope, self::chain('w', 4, false));

		$this->chain = self::chain('a', $depth, $nullsafe);
		$this->levels = [];
		for ($level = $this->chain; $level instanceof PropertyFetch || $level instanceof NullsafePropertyFetch; $level = $level->var) {
			$this->levels[] = $level;
		}
		$this->levels = array_reverse($this->levels);
	}

	/**
	 * The analyser asks about each level of a chain, innermost first.
	 */
	#[DataProvider(method: 'levelsWithoutNullsafe')]
	#[ExpectedFor(dataSet: 'levels-16', instructions: '74.18K', cycles: '124.6K', memory: '6.242KiB')]
	#[ExpectedFor(dataSet: 'levels-64', instructions: '283.4K', cycles: '379.1K', memory: '23.87KiB')]
	public function benchGetNullsafeShortcircuitedExprOfEveryLevel(int $depth, bool $nullsafe): int
	{
		$unchanged = 0;
		foreach ($this->levels as $level) {
			if (NullsafeOperatorHelper::getNullsafeShortcircuitedExpr($level) !== $level) {
				continue;
			}

			$unchanged++;
		}

		return $unchanged;
	}

	/**
	 * Every link above a nullsafe one is rebuilt, a chain without one is returned as it is.
	 */
	#[DataProvider(method: 'chains')]
	#[ExpectedFor(dataSet: 'innermost-nullsafe-64', instructions: '261.6K', cycles: '383.1K', memory: '9.609KiB')]
	#[ExpectedFor(dataSet: 'without-nullsafe-64', instructions: '173.3K', cycles: '258.8K', memory: '23.87KiB')]
	public function benchGetNullsafeShortcircuitedExpr(int $depth, bool $nullsafe): Expr
	{
		if ($this->chain === null) {
			throw new LogicException('boot() did not run.');
		}

		return NullsafeOperatorHelper::getNullsafeShortcircuitedExpr($this->chain);
	}

	/**
	 * A chain without a nullsafe link needs no type from the scope.
	 */
	#[DataProvider(method: 'chainsWithoutNullsafe')]
	#[ExpectedFor(dataSet: 'without-nullsafe-8', instructions: '27.17K', cycles: '67.74K', memory: '3.305KiB')]
	public function benchGetNullsafeShortcircuitedExprRespectingScope(int $depth, bool $nullsafe): Expr
	{
		if ($this->scope === null || $this->chain === null) {
			throw new LogicException('boot() did not run.');
		}

		return NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope($this->scope, $this->chain);
	}

	/**
	 * @return Expr $variable->p0->p1…, with ?-> as the innermost link when $nullsafe
	 */
	private static function chain(string $variable, int $depth, bool $nullsafe): Expr
	{
		$expr = new Variable($variable);
		for ($i = 0; $i < $depth; $i++) {
			$expr = $nullsafe && $i === 0 ? new NullsafePropertyFetch($expr, 'p' . $i) : new PropertyFetch($expr, 'p' . $i);
		}

		return $expr;
	}

}

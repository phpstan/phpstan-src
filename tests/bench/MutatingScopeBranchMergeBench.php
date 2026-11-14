<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;
use Tally\ScratchDirectory;

/**
 * Merging the scopes of two branches, which also creates the conditional expressions that
 * remember which variable types go together.
 */
final class MutatingScopeBranchMergeBench
{

	private ?MutatingScope $ours = null;

	private ?MutatingScope $theirs = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{string, int}>
	 */
	public static function branches(): iterable
	{
		// many variables both branches share the same holders of, one variable differs
		foreach ([16, 256] as $variables) {
			yield 'shared-holders-' . $variables => ['shared-holders', $variables];
		}
		// every variable has a different union in each branch: each is a guard of the others
		foreach ([4, 16] as $variables) {
			yield 'differing-unions-' . $variables => ['differing-unions', $variables];
		}
		// the variables of one branch are subtypes of the other's, a constant array differs
		yield 'absorbed-targets-16' => ['absorbed-targets', 16];
		// the same types, certainly defined in one branch and maybe defined in the other
		yield 'maybe-defined-64' => ['maybe-defined', 64];
		// variables defined only in one branch
		yield 'one-sided-64' => ['one-sided', 64];
	}

	#[Before]
	public function boot(string $shape, int $variables): void
	{
		$container = BenchContainer::create($this->scratch);
		$scope = $container->getByType(ScopeFactory::class)->create(ScopeContext::create(__FILE__));

		// the warm-up merges other variables, so it fills no cache the benchmark reads
		[$ours, $theirs] = self::createBranches($scope, $shape, 'warmup', 2);
		$ours->mergeWith($theirs);

		[$this->ours, $this->theirs] = self::createBranches($scope, $shape, 'v', $variables);
	}

	#[DataProvider(method: 'branches')]
	#[ExpectedFor(dataSet: 'absorbed-targets-16', instructions: '3.036M', cycles: '4.672M', memory: '58.13KiB')]
	#[ExpectedFor(dataSet: 'differing-unions-4', instructions: '3.576M', cycles: '4.634M', memory: '74.34KiB')]
	#[ExpectedFor(dataSet: 'differing-unions-16', instructions: '16.78M', cycles: '20.46M', memory: '599.2KiB')]
	#[ExpectedFor(dataSet: 'maybe-defined-64', instructions: '747.8K', cycles: '1.081M', memory: '18.58KiB')]
	#[ExpectedFor(dataSet: 'one-sided-64', instructions: '1.405M', cycles: '2.004M', memory: '50.22KiB')]
	#[ExpectedFor(dataSet: 'shared-holders-16', instructions: '153.0K', cycles: '400.9K', memory: '9.445KiB')]
	#[ExpectedFor(dataSet: 'shared-holders-256', instructions: '1.372M', cycles: '2.123M', memory: '121.9KiB')]
	public function benchMergeWith(string $shape, int $variables): MutatingScope
	{
		if ($this->ours === null || $this->theirs === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->ours->mergeWith($this->theirs);
	}

	/**
	 * @return array{MutatingScope, MutatingScope}
	 */
	private static function createBranches(MutatingScope $scope, string $shape, string $prefix, int $variables): array
	{
		$yes = TrinaryLogic::createYes();
		$ours = $scope;
		$theirs = $scope;
		switch ($shape) {
			case 'shared-holders':
				for ($i = 0; $i < $variables; $i++) {
					$type = self::strings($prefix . $i . '_', 8);
					$scope = $scope->assignVariable($prefix . $i, $type, $type, $yes);
				}
				$ours = self::assign($scope, $prefix . 'x', new ConstantIntegerType(1));
				$theirs = self::assign($scope, $prefix . 'x', new ConstantIntegerType(2));
				break;
			case 'differing-unions':
				for ($i = 0; $i < $variables; $i++) {
					$ours = self::assign($ours, $prefix . $i, self::strings($prefix . $i . 'a', 16));
					$theirs = self::assign($theirs, $prefix . $i, self::strings($prefix . $i . 'b', 16));
				}
				break;
			case 'absorbed-targets':
				for ($i = 0; $i < $variables; $i++) {
					$ours = self::assign($ours, $prefix . $i, new ConstantIntegerType($i));
					$theirs = self::assign($theirs, $prefix . $i, new IntegerType());
				}
				$ours = self::assign($ours, $prefix . 'array', self::constantArray($prefix . 'a', 16));
				$theirs = self::assign($theirs, $prefix . 'array', self::constantArray($prefix . 'b', 16));
				break;
			case 'maybe-defined':
				for ($i = 0; $i < $variables; $i++) {
					// the same type object, as when one branch assigned what the other only might have
					$type = self::strings($prefix . $i . '_', 8);
					$ours = self::assign($ours, $prefix . $i, $type);
					$theirs = $theirs->assignVariable($prefix . $i, $type, $type, TrinaryLogic::createMaybe());
				}
				break;
			case 'one-sided':
				for ($i = 0; $i < $variables; $i++) {
					$ours = self::assign($ours, $prefix . $i, new ConstantIntegerType($i));
				}
				break;
			default:
				throw new LogicException($shape);
		}

		return [$ours, $theirs];
	}

	private static function assign(MutatingScope $scope, string $name, Type $type): MutatingScope
	{
		return $scope->assignVariable($name, $type, $type, TrinaryLogic::createYes());
	}

	private static function strings(string $prefix, int $count): Type
	{
		$types = [];
		for ($i = 0; $i < $count; $i++) {
			$types[] = new ConstantStringType($prefix . $i);
		}

		return TypeCombinator::union(...$types);
	}

	private static function constantArray(string $prefix, int $count): ConstantArrayType
	{
		$keys = [];
		$values = [];
		for ($i = 0; $i < $count; $i++) {
			$keys[] = new ConstantStringType($prefix . $i);
			$values[] = new ConstantIntegerType($i);
		}

		return new ConstantArrayType($keys, $values);
	}

}

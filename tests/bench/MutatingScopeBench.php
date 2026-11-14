<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\MutatingScope;
use PHPStan\TrinaryLogic;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\Expected;
use Tally\Attribute\ExpectedFor;
use Tally\ScratchDirectory;
use function implode;
use function sprintf;

/**
 * MutatingScope's operations on a scope that tracks 32 property fetches besides the variables,
 * like a method that initializes the properties of its class. The counted calls run on a scope of
 * its own, the warm-up on another one from the same code, because scopes remember resolved types.
 */
final class MutatingScopeBench
{

	private const PROPERTIES = 32;

	private ?MutatingScope $scope = null;

	private ?MutatingScope $warmupScope = null;

	private ?MutatingScope $conditionalScope = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	#[Before]
	public function boot(): void
	{
		$container = BenchContainer::create($this->scratch);
		$warmup = BenchScope::analyse($container, $this->scratch, 'warmup', self::code());
		$this->warmupScope = $warmup->scope('marker');
		$warmup->scope('markerConditional')->filterByTruthyValue(self::notNull());
		$counted = BenchScope::analyse($container, $this->scratch, 'counted', self::code());
		$this->scope = $counted->scope('marker');
		$this->conditionalScope = $counted->scope('markerConditional');

		foreach ([$this->warmupScope, $this->scope] as $i => $scope) {
			// the counted scope has resolved what pushInFunctionCall() is to remember
			$scope->getType(self::resolvedExpr());
			if ($i !== 0) {
				continue;
			}
			$scope->getVariableType('i');
			$scope->getType(self::coalesce());
			$scope->getKeepVoidType(new Expr\Variable('str'));
			$scope->getKeepVoidType(self::funcCall('trim'));
			$scope->getType(self::funcCall('ucfirst'));
			$scope->pushInFunctionCall(null, null, true)->getType(self::resolvedExpr());
			$scope->specifyExpressionType(new String_('x'), new StringType(), new StringType(), TrinaryLogic::createYes());
			$scope->filterByTruthyValue(self::condition());
			$scope->assignExpression(self::propertyFetch(0), new IntegerType(), new IntegerType());
			$scope->restoreThis($scope);
		}
	}

	/**
	 * The certainty is looked up once (#4726).
	 */
	#[Expected(instructions: '2.247K', cycles: '19.93K', memory: '32B')]
	public function benchGetVariableType(): Type
	{
		return $this->scope()->getVariableType('i');
	}

	/**
	 * Only the cases with a left side that may be set narrow the scope by isset() (#4661).
	 */
	#[Expected(instructions: '965.3K', cycles: '2.091M', memory: '93.89KiB')]
	public function benchGetTypeOfCoalesceWithNullLeft(): Type
	{
		return $this->scope()->getType(self::coalesce());
	}

	/**
	 * @return iterable<string, array{string}>
	 */
	public static function keepVoidExprs(): iterable
	{
		// not a call, and a call whose type has no null in it: the type without void is the same (#4735)
		yield 'variable' => ['variable'];
		yield 'func-call' => ['func-call'];
	}

	#[DataProvider(method: 'keepVoidExprs')]
	#[ExpectedFor(dataSet: 'func-call', instructions: '389.4K', cycles: '1.305M', memory: '15.49KiB')]
	#[ExpectedFor(dataSet: 'variable', instructions: '7.615K', cycles: '55.59K', memory: '128B')]
	public function benchGetKeepVoidType(string $expr): Type
	{
		return $this->scope()->getKeepVoidType($expr === 'variable' ? new Expr\Variable('str') : self::funcCall('trim'));
	}

	/**
	 * A function without a dynamic return type extension: the extensions for it are remembered (#4644).
	 */
	#[Expected(instructions: '377.5K', cycles: '1.270M', memory: '15.49KiB')]
	public function benchGetTypeOfFuncCall(): Type
	{
		return $this->scope()->getType(self::funcCall('ucfirst'));
	}

	/**
	 * Creates a scope, without getting the parser from the container each time (#4672).
	 */
	#[Expected(instructions: '11.82K', cycles: '57.24K', memory: '944B')]
	public function benchPushInFunctionCall(): MutatingScope
	{
		return $this->scope()->pushInFunctionCall(null, null, true);
	}

	/**
	 * An argument's type resolved before is not resolved again (#4648).
	 */
	#[Expected(instructions: '52.32K', cycles: '219.8K', memory: '3.086KiB')]
	public function benchPushInFunctionCallGetType(): Type
	{
		return $this->scope()->pushInFunctionCall(null, null, true)->getType(self::resolvedExpr());
	}

	/**
	 * A scalar's type is not stored (#4663).
	 */
	#[Expected(instructions: '3.965K', cycles: '23.76K', memory: '176B')]
	public function benchSpecifyScalarType(): MutatingScope
	{
		return $this->scope()->specifyExpressionType(new String_('x'), new StringType(), new StringType(), TrinaryLogic::createYes());
	}

	/**
	 * The keys of the narrowed expressions are not printed again (#4691).
	 */
	#[Expected(instructions: '2.047M', cycles: '3.874M', memory: '109.7KiB')]
	public function benchFilterByTruthyValue(): MutatingScope
	{
		return $this->scope()->filterByTruthyValue(self::condition());
	}

	/**
	 * The scope remembers what the properties assigned in `if ($s !== null)` are when it holds,
	 * but not what they are otherwise, so they are not intersected with anything (#4690).
	 */
	#[Expected(instructions: '611.7K', cycles: '1.427M', memory: '26.10KiB')]
	public function benchFilterByConditionOfConditionalExpressions(): MutatingScope
	{
		if ($this->conditionalScope === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->conditionalScope->filterByTruthyValue(self::notNull());
	}

	/**
	 * Invalidates the method calls on $this without searching each tracked expression (#4692).
	 */
	#[Expected(instructions: '324.9K', cycles: '640.5K', memory: '34.63KiB')]
	public function benchAssignPropertyFetch(): MutatingScope
	{
		return $this->scope()->assignExpression(self::propertyFetch(0), new IntegerType(), new IntegerType());
	}

	/**
	 * Finds the expressions on $this by their keys, not by searching them (#4697).
	 */
	#[Expected(instructions: '66.12K', cycles: '169.7K', memory: '16.61KiB')]
	public function benchRestoreThis(): MutatingScope
	{
		$scope = $this->scope();

		return $scope->restoreThis($scope);
	}

	private function scope(): MutatingScope
	{
		if ($this->scope === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->scope;
	}

	private static function code(): string
	{
		$properties = [];
		$assigns = [];
		$conditionalAssigns = [];
		for ($i = 0; $i < self::PROPERTIES; $i++) {
			$properties[] = sprintf('public int $p%d;', $i);
			$assigns[] = sprintf('$this->p%d = $i;', $i);
			$conditionalAssigns[] = sprintf('$this->p%d = %d;', $i, $i);
		}

		return sprintf(<<<'PHP'
			<?php declare(strict_types = 1);

			namespace MutatingScopeBench;

			class Foo
			{

				%s

				public function doFoo(?string $s, int $i, string $str): void
				{
					%s
					$null = null;
					marker();
				}

				public function doConditional(?string $s): void
				{
					if ($s !== null) {
						%s
					}
					markerConditional();
				}

			}
			PHP, implode("\n", $properties), implode("\n", $assigns), implode("\n", $conditionalAssigns));
	}

	private static function coalesce(): Expr
	{
		return new Expr\BinaryOp\Coalesce(new Expr\Variable('null'), new String_('x'));
	}

	/**
	 * @param non-empty-string $name
	 */
	private static function funcCall(string $name): Expr
	{
		return new Expr\FuncCall(new Name($name), [new Arg(new Expr\Variable('str'))]);
	}

	private static function resolvedExpr(): Expr
	{
		return new Expr\FuncCall(new Name('str_repeat'), [new Arg(new Expr\Variable('str')), new Arg(new Expr\Variable('i'))]);
	}

	private static function notNull(): Expr
	{
		return new Expr\BinaryOp\NotIdentical(new Expr\Variable('s'), new Expr\ConstFetch(new Name('null')));
	}

	private static function condition(): Expr
	{
		return new Expr\BinaryOp\BooleanAnd(
			self::notNull(),
			new Expr\BinaryOp\Greater(new Expr\Variable('i'), new Int_(0)),
		);
	}

	private static function propertyFetch(int $i): Expr
	{
		return new Expr\PropertyFetch(new Expr\Variable('this'), 'p' . $i);
	}

}

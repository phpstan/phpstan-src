<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\Greater;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Node\Expr\AlwaysRememberedExpr;
use PHPStan\TrinaryLogic;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use SplObjectStorage;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\Expected;
use Tally\Attribute\ExpectedFor;
use Tally\Meter;
use Tally\ScratchDirectory;
use function count;

final class TypeSpecifierBench
{

	private const EXPRESSIONS = 64;

	private ?MutatingScope $scope = null;

	private ?TypeSpecifier $typeSpecifier = null;

	public function __construct(private Meter $meter, private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{string, int}>
	 */
	public static function chains(): iterable
	{
		foreach (['falsey', 'truthy'] as $context) {
			foreach ([16, 64] as $arms) {
				yield $context . '-' . $arms => [$context, $arms];
			}
		}
	}

	/**
	 * @return iterable<string, array{string}>
	 */
	public static function contexts(): iterable
	{
		yield 'truthy' => ['truthy'];
		yield 'true' => ['true'];
	}

	#[Before]
	public function boot(): void
	{
		$container = BenchContainer::create($this->scratch);
		$this->typeSpecifier = $container->getByType(TypeSpecifier::class);
		$object = new ObjectType(SplObjectStorage::class);
		$this->scope = $container->getByType(ScopeFactory::class)->create(ScopeContext::create(__FILE__))
			->assignVariable('t', new StringType(), new StringType(), TrinaryLogic::createYes())
			->assignVariable('i', new IntegerType(), new IntegerType(), TrinaryLogic::createYes())
			->assignVariable('o', $object, $object, TrinaryLogic::createYes());

		// the warm-up uses other expressions, the scope remembers the types it resolved
		$this->specify(self::orChain('warmup', 4), TypeSpecifierContext::createFalsey());
		$this->specify(self::orChain('warmup', 4), TypeSpecifierContext::createTruthy());
	}

	/**
	 * 'a' === $t || 'b' === $t || ..., which PHP parses to a chain as deep as it is long.
	 */
	#[DataProvider(method: 'chains')]
	#[ExpectedFor(dataSet: 'falsey-16', instructions: '11.38M', cycles: '16.93M', memory: '476.1KiB')]
	#[ExpectedFor(dataSet: 'falsey-64', instructions: '50.77M', cycles: '71.38M', memory: '1.886MiB')]
	#[ExpectedFor(dataSet: 'truthy-16', instructions: '16.18M', cycles: '23.04M', memory: '497.0KiB')]
	#[ExpectedFor(dataSet: 'truthy-64', instructions: '94.60M', cycles: '122.5M', memory: '2.028MiB')]
	public function benchSpecifyBooleanOrChain(string $context, int $arms): SpecifiedTypes
	{
		$chain = $this->meter->exclude(static fn (): Expr => self::orChain('arm', $arms));

		return $this->specify($chain, $context === 'falsey' ? TypeSpecifierContext::createFalsey() : TypeSpecifierContext::createTruthy());
	}

	/**
	 * $i ?: $t
	 */
	#[Expected(instructions: '186.7M', cycles: '239.2M', memory: '2.017MiB')]
	public function benchSpecifyShortTernary(): int
	{
		$exprs = $this->meter->exclude(static function (): array {
			$exprs = [];
			for ($i = 0; $i < self::EXPRESSIONS; $i++) {
				$exprs[] = new Ternary(new Variable('i'), null, new Identical(new Variable('t'), new String_('t' . $i)));
			}

			return $exprs;
		});

		return $this->specifyAll($exprs, TypeSpecifierContext::createTruthy());
	}

	/**
	 * An int remembered by match() compared with a string is always false.
	 */
	#[Expected(instructions: '38.39M', cycles: '52.40M', memory: '284.4KiB')]
	public function benchSpecifyAlwaysFalseIdentical(): int
	{
		$exprs = $this->meter->exclude(static function (): array {
			$exprs = [];
			$int = new IntegerType();
			for ($i = 0; $i < self::EXPRESSIONS; $i++) {
				$exprs[] = new Identical(new AlwaysRememberedExpr(new Variable('i'), $int, $int), new String_('s' . $i));
			}

			return $exprs;
		});

		return $this->specifyAll($exprs, TypeSpecifierContext::createFalse());
	}

	/**
	 * abs($i) > 0 is not one of the calls a comparison narrows, like count() or strlen().
	 */
	#[Expected(instructions: '132.1M', cycles: '172.5M', memory: '140.2KiB')]
	public function benchSpecifyComparisonWithCall(): int
	{
		$exprs = $this->meter->exclude(function (): array {
			// loads the reflection of abs()
			$this->specify(new Greater(new FuncCall(new Name('abs'), [new Arg(new Variable('i'))]), new Int_(-1)), TypeSpecifierContext::createTruthy());
			$exprs = [];
			for ($i = 0; $i < self::EXPRESSIONS; $i++) {
				$exprs[] = new Greater(new FuncCall(new Name('abs'), [new Arg(new Variable('i'))]), new Int_($i));
			}

			return $exprs;
		});

		return $this->specifyAll($exprs, TypeSpecifierContext::createTruthy());
	}

	/**
	 * Creating the narrowing of an expression: only the true and false contexts need its key.
	 */
	#[DataProvider(method: 'contexts')]
	#[ExpectedFor(dataSet: 'true', instructions: '4.120M', cycles: '5.675M', memory: '79.96KiB')]
	#[ExpectedFor(dataSet: 'truthy', instructions: '4.120M', cycles: '5.681M', memory: '79.96KiB')]
	public function benchCreate(string $context): int
	{
		[$exprs, $type] = $this->meter->exclude(function (): array {
			// loads the reflection of the method
			$this->typeSpecifier()->create(new MethodCall(new Variable('o'), 'contains', [new Arg(new Variable('warmup'))]), new StringType(), TypeSpecifierContext::createTrue(), $this->scope());
			$exprs = [];
			for ($i = 0; $i < self::EXPRESSIONS; $i++) {
				$exprs[] = new MethodCall(new Variable('o'), 'contains', [new Arg(new Variable('o' . $i))]);
			}

			return [$exprs, new StringType()];
		});

		$typeContext = $context === 'true' ? TypeSpecifierContext::createTrue() : TypeSpecifierContext::createTruthy();
		$typeSpecifier = $this->typeSpecifier();
		$scope = $this->scope();
		$sure = 0;
		foreach ($exprs as $expr) {
			$sure += count($typeSpecifier->create($expr, $type, $typeContext, $scope)->getSureTypes());
		}

		return $sure;
	}

	/**
	 * @param list<Expr> $exprs
	 */
	private function specifyAll(array $exprs, TypeSpecifierContext $context): int
	{
		$sure = 0;
		foreach ($exprs as $expr) {
			$sure += count($this->specify($expr, $context)->getSureNotTypes());
		}

		return $sure;
	}

	private function specify(Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return $this->typeSpecifier()->specifyTypesInCondition($this->scope(), $expr, $context);
	}

	private static function orChain(string $prefix, int $arms): Expr
	{
		$chain = new Identical(new String_($prefix . '0'), new Variable('t'));
		for ($i = 1; $i < $arms; $i++) {
			$chain = new BooleanOr($chain, new Identical(new String_($prefix . $i), new Variable('t')));
		}

		return $chain;
	}

	private function scope(): MutatingScope
	{
		if ($this->scope === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->scope;
	}

	private function typeSpecifier(): TypeSpecifier
	{
		if ($this->typeSpecifier === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->typeSpecifier;
	}

}

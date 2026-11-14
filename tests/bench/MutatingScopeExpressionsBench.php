<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use ArrayIterator;
use ArrayObject;
use Closure;
use LogicException;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\BinaryOp\Plus;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use ReflectionMethod;
use SplDoublyLinkedList;
use SplFixedArray;
use SplObjectStorage;
use SplPriorityQueue;
use SplQueue;
use SplStack;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\Linear;
use Tally\Meter;
use Tally\ScratchDirectory;
use function count;
use function strlen;

final class MutatingScopeExpressionsBench
{

	private const COUNTABLE_CLASSES = [
		ArrayObject::class,
		ArrayIterator::class,
		SplObjectStorage::class,
		SplDoublyLinkedList::class,
		SplFixedArray::class,
		SplPriorityQueue::class,
		SplQueue::class,
		SplStack::class,
	];

	private ?MutatingScope $scope = null;

	public function __construct(private Meter $meter, private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function expressions(): iterable
	{
		foreach ([16, 128] as $expressions) {
			yield 'expressions-' . $expressions => [$expressions];
		}
	}

	#[Before]
	public function boot(int $expressions): void
	{
		$container = BenchContainer::create($this->scratch);
		$this->scope = $container->getByType(ScopeFactory::class)->create(ScopeContext::create(__FILE__));
	}

	/**
	 * Without openssl_error_string() or file functions remembered in the scope, there is
	 * nothing to forget.
	 */
	#[DataProvider(method: 'expressions')]
	#[Linear(size: 'expressions')]
	public function benchAfterOpenSslAndClearstatcacheCall(int $expressions): MutatingScope
	{
		$scope = $this->meter->exclude(fn (): MutatingScope => $this->scopeWithProperties($expressions));

		return $scope->afterOpenSslCall('openssl_sign')->afterClearstatcacheCall();
	}

	/**
	 * Removing never from an expression changes nothing and does not need its type.
	 */
	#[DataProvider(method: 'expressions')]
	#[Linear(size: 'expressions')]
	public function benchRemoveNeverFromExpression(int $expressions): MutatingScope
	{
		[$scope, $exprs] = $this->meter->exclude(function () use ($expressions): array {
			$exprs = [];
			for ($i = 0; $i < $expressions; $i++) {
				$exprs[] = new ArrayDimFetch(new Variable('v' . $i), new String_('key'));
			}

			return [$this->scopeWithArrays($expressions), $exprs];
		});

		$never = new NeverType();
		foreach ($exprs as $expr) {
			$scope = $scope->removeTypeFromExpression($expr, $never);
		}

		return $scope;
	}

	/**
	 * Narrowing $array['key'] also narrows $array to the types that can be accessed by an offset.
	 */
	#[DataProvider(method: 'expressions')]
	#[Linear(size: 'expressions')]
	public function benchSpecifyArrayDimFetch(int $expressions): MutatingScope
	{
		[$scope, $exprs] = $this->meter->exclude(function () use ($expressions): array {
			$exprs = [];
			for ($i = 0; $i < $expressions; $i++) {
				$exprs[] = new ArrayDimFetch(new Variable('v' . $i), $i % 2 === 0 ? new Int_($i) : new String_('k' . $i));
			}

			return [$this->scopeWithArrays($expressions), $exprs];
		});

		$int = new IntegerType();
		foreach ($exprs as $expr) {
			$scope = $scope->specifyExpressionType($expr, $int, $int, TrinaryLogic::createYes());
		}

		return $scope;
	}

	/**
	 * A method called on a union of objects that all have it.
	 */
	#[DataProvider(method: 'expressions')]
	#[Linear(size: 'expressions')]
	public function benchGetMethodReflectionOnUnion(int $expressions): int
	{
		$scope = $this->scope();
		$union = $this->meter->exclude(static function () use ($scope): Type {
			$types = [];
			foreach (self::COUNTABLE_CLASSES as $class) {
				$types[] = new ObjectType($class);
			}
			$union = TypeCombinator::union(...$types);
			// fills the reflection caches
			$scope->getMethodReflection($union, 'count');
			$scope->getMethodReflection($union, 'valid');

			return $union;
		});

		$found = 0;
		for ($i = 0; $i < $expressions; $i++) {
			$found += $scope->getMethodReflection($union, 'count') !== null ? 1 : 0;
			$found += $scope->getMethodReflection($union, 'valid') !== null ? 1 : 0;
		}

		return $found;
	}

	/**
	 * Assigning $x forgets the remembered expressions that contain it, after searching each
	 * one whose key contains "$x" for it.
	 */
	#[DataProvider(method: 'expressions')]
	#[Linear(size: 'expressions')]
	public function benchInvalidateExpression(int $expressions): MutatingScope
	{
		$scope = $this->meter->exclude(function () use ($expressions): MutatingScope {
			$scope = $this->scope();
			$int = new IntegerType();
			for ($i = 0; $i < $expressions; $i++) {
				// $o->m0($x, $y)->p0, every other one with $xx, which only passes the substring prefilter
				$expr = new PropertyFetch(
					new MethodCall(new Variable('o'), 'm' . $i, [new Arg(new Variable($i % 2 === 0 ? 'x' : 'xx')), new Arg(new Variable('y'))]),
					'p' . $i,
				);
				$scope = $scope->specifyExpressionType($expr, $int, $int, TrinaryLogic::createYes());
			}

			return $scope;
		});

		return $scope->invalidateExpression(new Variable('x'));
	}

	/**
	 * Variables are by far the most common expressions that get a key.
	 */
	#[DataProvider(method: 'expressions')]
	#[Linear(size: 'expressions')]
	public function benchGetNodeKeyOfVariables(int $expressions): int
	{
		[$getNodeKey, $exprs] = $this->meter->exclude(function () use ($expressions): array {
			$exprs = [];
			for ($i = 0; $i < $expressions; $i++) {
				$exprs[] = new Variable('variable' . $i);
			}

			// private before 2.2
			$getNodeKey = (new ReflectionMethod(MutatingScope::class, 'getNodeKey'))->getClosure($this->scope());
			$getNodeKey(new Variable('warmup'));

			return [$getNodeKey, $exprs];
		});

		/** @var Closure(Expr): string $getNodeKey */
		$length = 0;
		foreach ($exprs as $expr) {
			$length += strlen($getNodeKey($expr));
		}

		return $length;
	}

	/**
	 * $object[$dim] on an ArrayAccess object is the type of $object->offsetGet($dim).
	 */
	#[DataProvider(method: 'expressions')]
	#[Linear(size: 'expressions')]
	public function benchGetTypeOfArrayAccessDimFetch(int $expressions): int
	{
		[$scope, $exprs] = $this->meter->exclude(function () use ($expressions): array {
			$type = new GenericObjectType(ArrayObject::class, [new StringType(), new IntegerType()]);
			$scope = $this->scope()
				->assignVariable('object', $type, $type, TrinaryLogic::createYes())
				->assignVariable('key', new StringType(), new StringType(), TrinaryLogic::createYes());
			$scope->getType(new ArrayDimFetch(new Variable('object'), new Concat(new Variable('key'), new String_('warmup'))));
			$exprs = [];
			for ($i = 0; $i < $expressions; $i++) {
				$exprs[] = new ArrayDimFetch(new Variable('object'), new Concat(new Variable('key'), new String_('k' . $i)));
			}

			return [$scope, $exprs];
		});

		$integers = 0;
		foreach ($exprs as $expr) {
			$integers += $scope->getType($expr)->isInteger()->yes() ? 1 : 0;
		}

		return $integers;
	}

	/**
	 * The type of `new ArrayObject($array)` infers the template types of the class from the
	 * arguments of the constructor.
	 */
	#[DataProvider(method: 'expressions')]
	#[Linear(size: 'expressions')]
	public function benchGetTypeOfGenericNew(int $expressions): int
	{
		[$scope, $exprs] = $this->meter->exclude(function () use ($expressions): array {
			$scope = $this->scopeWithArrays($expressions + 1);
			$scope->getType(new New_(new FullyQualified(ArrayObject::class), [new Arg(new Variable('v' . $expressions))]));
			$scope->getType(new New_(new FullyQualified(ArrayIterator::class), [new Arg(new Variable('v' . $expressions))]));
			$exprs = [];
			for ($i = 0; $i < $expressions; $i++) {
				$exprs[] = new New_(new FullyQualified($i % 2 === 0 ? ArrayObject::class : ArrayIterator::class), [new Arg(new Variable('v' . $i))]);
			}

			return [$scope, $exprs];
		});

		$classes = 0;
		foreach ($exprs as $expr) {
			$classes += count($scope->getType($expr)->getObjectClassNames());
		}

		return $classes;
	}

	/**
	 * Each expression is resolved by the handler of its class.
	 */
	#[DataProvider(method: 'expressions')]
	#[Linear(size: 'expressions')]
	public function benchGetTypeOfFreshExpressions(int $expressions): int
	{
		[$scope, $exprs] = $this->meter->exclude(function () use ($expressions): array {
			$scope = $this->scope()->assignVariable('i', new IntegerType(), new IntegerType(), TrinaryLogic::createYes());
			$scope->getType(new Plus(new Variable('i'), new Int_(-1)));
			$exprs = [];
			for ($i = 0; $i < $expressions; $i++) {
				$exprs[] = new Plus(new Variable('i'), new Int_($i));
			}

			return [$scope, $exprs];
		});

		$integers = 0;
		foreach ($exprs as $expr) {
			$integers += $scope->getType($expr)->isInteger()->yes() ? 1 : 0;
		}

		return $integers;
	}

	private function scope(): MutatingScope
	{
		if ($this->scope === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->scope;
	}

	/**
	 * $v0 to $vN, lists of strings
	 */
	private function scopeWithArrays(int $variables): MutatingScope
	{
		$scope = $this->scope();
		$type = new ArrayType(new IntegerType(), new StringType());
		for ($i = 0; $i < $variables; $i++) {
			$scope = $scope->assignVariable('v' . $i, $type, $type, TrinaryLogic::createYes());
		}

		return $scope;
	}

	/**
	 * $o->p0 to $o->pN remembered as 1
	 */
	private function scopeWithProperties(int $properties): MutatingScope
	{
		$scope = $this->scope();
		$type = new ConstantIntegerType(1);
		for ($i = 0; $i < $properties; $i++) {
			$scope = $scope->specifyExpressionType(new PropertyFetch(new Variable('o'), 'p' . $i), $type, $type, TrinaryLogic::createYes());
		}

		return $scope;
	}

}

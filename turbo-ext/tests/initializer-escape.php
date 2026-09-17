<?php declare(strict_types = 1);

/**
 * The native callers hand InitializerExprTypeResolver their operand reader as
 * a stack-backed pt_ietr_get_type; where it escapes into PHP as a callable
 * (OversizedArrayBuilder::build(), a resolver that is not the native class)
 * the callable must own what it reads. Keeping collaborators store every
 * callable they are handed and answer through the twins; the kept callables
 * are called again after the handler call, its results and storage are gone
 * and the stack has been reused: they must answer exactly as they did inside
 * the call.
 *
 * Runs under the prefixed activation: the ports are the PHPStanTurbo\*
 * declarations over the container's PHP collaborators (the direct entries'
 * fallback paths). Included by smoke.php (uses its check()); runnable alone
 * too, e.g. under a sanitizer build:
 *   php -d extension=.../phpstan_turbo.so turbo-ext/tests/initializer-escape.php
 */

namespace {

if (!function_exists('check')) {
	require __DIR__ . '/activate-prefixed.php';
	$failures = 0;
	function check(bool $cond, string $msg): void
	{
		global $failures;
		if (!$cond) {
			$failures++;
			echo "FAIL: $msg\n";
		}
	}
	$initializerEscapeStandalone = true;
}

$ieContainerFactory = new \PHPStan\DependencyInjection\ContainerFactory(dirname(__DIR__, 2));
$ieContainer = $ieContainerFactory->create(sys_get_temp_dir() . '/phpstan-turbo-smoke', [$ieContainerFactory->getConfigDirectory() . '/config.level8.neon'], []);
$ieNodeScopeResolver = $ieContainer->getByType(\PHPStan\Analyser\NodeScopeResolver::class);
$ieFactory = $ieContainer->getByType(\PHPStan\Analyser\ExpressionResultFactory::class);
$ieDefault = $ieContainer->getByType(\PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper::class);
$ieIdentical = $ieContainer->getByType(\PHPStan\Analyser\ExprHandler\Helper\IdenticalNarrowingHelper::class);
$ieInitializer = $ieContainer->getByType(\PHPStan\Reflection\InitializerExprTypeResolver::class);
$ieStmt = new \PhpParser\Node\Stmt\Nop();
$ieScope = $ieContainer->getByType(\PHPStan\Analyser\ScopeFactory::class)->create(\PHPStan\Analyser\ScopeContext::create(__FILE__))
	->assignVariable('a', new \PHPStanTurbo\IntegerType(), new \PHPStanTurbo\IntegerType(), \PHPStan\TrinaryLogic::createYes())
	->assignVariable('n', new \PHPStanTurbo\ConstantStringType('a'), new \PHPStanTurbo\StringType(), \PHPStan\TrinaryLogic::createYes());

// The native callers hand InitializerExprTypeResolver their operand reader as
// a stack-backed pt_ietr_get_type; where it escapes into PHP as a callable
// (OversizedArrayBuilder::build(), a resolver that is not the native class)
// the callable must own what it reads. Keeping collaborators store every
// callable they are handed, answer through the twins, and the kept callables
// are called again after the handler call, its results and storage are gone
// and the stack has been reused: they must answer exactly as they did inside
// the call.
$ieDescribe = static fn ($type): string => $type instanceof \PHPStan\Type\Type ? $type->describe(\PHPStan\Type\VerbosityLevel::precise()) : get_debug_type($type);
$ieDeepStack = static function (int $depth) use (&$ieDeepStack): int {
	$filler = array_fill(0, 16, str_repeat('x', 64));
	return $depth === 0 ? count($filler) : $ieDeepStack($depth - 1) + 1;
};
// a PHP stand-in for the resolver: the direct entries' fallback hands it the
// callable, it keeps it, records what it answers now and delegates
$ieKeeping = new class ($ieInitializer, $ieDescribe) {

	/** @var list<array{string, callable, \PhpParser\Node\Expr, string}> */
	public array $kept = [];

	public function __construct(private object $inner, private \Closure $describe)
	{
	}

	private function keep(string $method, callable $callback, \PhpParser\Node\Expr $asked): void
	{
		$this->kept[] = [$method, $callback, $asked, ($this->describe)($callback($asked))];
	}

	public function getUnaryMinusType($expr, callable $cb) { $this->keep(__FUNCTION__, $cb, $expr); return $this->inner->getUnaryMinusType($expr, $cb); }
	public function getUnaryPlusType($expr, callable $cb) { $this->keep(__FUNCTION__, $cb, $expr); return $this->inner->getUnaryPlusType($expr, $cb); }
	public function getBitwiseNotType($expr, callable $cb) { $this->keep(__FUNCTION__, $cb, $expr); return $this->inner->getBitwiseNotType($expr, $cb); }
	public function getPlusType($left, $right, callable $cb) { $this->keep(__FUNCTION__, $cb, $left); return $this->inner->getPlusType($left, $right, $cb); }
	public function getMinusType($left, $right, callable $cb) { $this->keep(__FUNCTION__, $cb, $left); return $this->inner->getMinusType($left, $right, $cb); }
	public function getMulType($left, $right, callable $cb) { $this->keep(__FUNCTION__, $cb, $right); return $this->inner->getMulType($left, $right, $cb); }
	public function getArrayType($expr, callable $cb) { $this->keep(__FUNCTION__, $cb, $expr->items[0]->value); return $this->inner->getArrayType($expr, $cb); }
	public function getClassConstFetchTypeByReflection($class, $constantName, $classReflection, callable $cb) { $this->keep(__FUNCTION__, $cb, $class); return $this->inner->getClassConstFetchTypeByReflection($class, $constantName, $classReflection, $cb); }
	public function resolveEqualType($left, $right) { return $this->inner->resolveEqualType($left, $right); }
	public function resolveIdenticalType($left, $right) { return $this->inner->resolveIdenticalType($left, $right); }
	public function resolveConcatType($left, $right) { return $this->inner->resolveConcatType($left, $right); }

};
$ieCalls = [
	'UnaryMinusHandler' => static fn ($storage) => (new \PHPStanTurbo\UnaryMinusHandler($ieKeeping, $ieFactory, $ieDefault))->processExpr($ieNodeScopeResolver, $ieStmt, new \PhpParser\Node\Expr\UnaryMinus(new \PhpParser\Node\Expr\Variable('a')), $ieScope, $storage, new \PHPStan\Analyser\NoopNodeCallback(), \PHPStan\Analyser\ExpressionContext::createDeep()),
	'UnaryPlusHandler' => static fn ($storage) => (new \PHPStanTurbo\UnaryPlusHandler($ieKeeping, $ieFactory, $ieDefault))->processExpr($ieNodeScopeResolver, $ieStmt, new \PhpParser\Node\Expr\UnaryPlus(new \PhpParser\Node\Expr\Variable('n')), $ieScope, $storage, new \PHPStan\Analyser\NoopNodeCallback(), \PHPStan\Analyser\ExpressionContext::createDeep()),
	'BitwiseNotHandler' => static fn ($storage) => (new \PHPStanTurbo\BitwiseNotHandler($ieKeeping, $ieFactory, $ieDefault))->processExpr($ieNodeScopeResolver, $ieStmt, new \PhpParser\Node\Expr\BitwiseNot(new \PhpParser\Node\Expr\Variable('a')), $ieScope, $storage, new \PHPStan\Analyser\NoopNodeCallback(), \PHPStan\Analyser\ExpressionContext::createDeep()),
	'ArrayHandler' => static fn ($storage) => (new \PHPStanTurbo\ArrayHandler($ieKeeping, $ieFactory))->processExpr($ieNodeScopeResolver, $ieStmt, new \PhpParser\Node\Expr\Array_([new \PhpParser\Node\ArrayItem(new \PhpParser\Node\Expr\Variable('n')), new \PhpParser\Node\ArrayItem(new \PhpParser\Node\Scalar\Int_(5))]), $ieScope, $storage, new \PHPStan\Analyser\NoopNodeCallback(), \PHPStan\Analyser\ExpressionContext::createDeep()),
	'ClassConstFetchHandler' => static fn ($storage) => (new \PHPStanTurbo\ClassConstFetchHandler($ieKeeping, $ieFactory, $ieDefault))->processExpr($ieNodeScopeResolver, $ieStmt, new \PhpParser\Node\Expr\ClassConstFetch(new \PhpParser\Node\Expr\Variable('n'), 'FOO'), $ieScope, $storage, new \PHPStan\Analyser\NoopNodeCallback(), \PHPStan\Analyser\ExpressionContext::createDeep()),
	'BinaryOpHandler' => static fn ($storage) => (new \PHPStanTurbo\BinaryOpHandler($ieKeeping, $ieContainer->getByType(\PHPStan\Analyser\RicherScopeGetTypeHelper::class), $ieContainer->getByType(\PHPStan\Php\PhpVersion::class), $ieContainer->getByType(\PHPStan\Analyser\ExprHandler\Helper\ImplicitToStringCallHelper::class), $ieContainer->getByType(\PHPStan\Node\Printer\ExprPrinter::class), $ieIdentical, $ieContainer->getByType(\PHPStan\Analyser\ExprHandler\Helper\CountNarrowingHelper::class), $ieFactory, $ieDefault))->processExpr($ieNodeScopeResolver, $ieStmt, new \PhpParser\Node\Expr\BinaryOp\Minus(new \PhpParser\Node\Expr\Variable('a'), new \PhpParser\Node\Scalar\Int_(3)), $ieScope, $storage, new \PHPStan\Analyser\NoopNodeCallback(), \PHPStan\Analyser\ExpressionContext::createDeep()),
];
foreach ($ieCalls as $label => $call) {
	$storage = new \PHPStan\Analyser\ExpressionResultStorage();
	$ieScope->pushExpressionResultStorage($storage);
	try {
		$result = $call($storage);
		check(is_string($ieDescribe($result->getType())) && is_string($ieDescribe($result->getNativeType())), "escaping getTypeCallback ($label): the result resolves");
	} catch (\Throwable $e) {
		check(false, "escaping getTypeCallback ($label): " . get_class($e) . ': ' . $e->getMessage());
	} finally {
		$ieScope->popExpressionResultStorage();
	}
	unset($result, $storage);
}
// IncDecTypeHelper hands its closure out itself
$storage = new \PHPStan\Analyser\ExpressionResultStorage();
$ieScope->pushExpressionResultStorage($storage);
try {
	$ieVarExpr = new \PhpParser\Node\Expr\Variable('a');
	$ieVarResult = $ieNodeScopeResolver->processExprNode($ieStmt, $ieVarExpr, $ieScope, $storage, new \PHPStan\Analyser\NoopNodeCallback(), \PHPStan\Analyser\ExpressionContext::createDeep());
	foreach ([true, false] as $increment) {
		$ieTypeCallback = (new \PHPStanTurbo\IncDecTypeHelper($ieKeeping))->getTypeCallback($ieVarExpr, $ieVarResult, $increment);
		check(is_string($ieDescribe($ieTypeCallback(false))) && is_string($ieDescribe($ieTypeCallback(true))), 'escaping getTypeCallback (IncDecTypeHelper): the callback resolves');
	}
} finally {
	$ieScope->popExpressionResultStorage();
}
// OutputBufferHelper's addDelta() callback captures nothing; the PHP twin
// resolver then computes with the native level type, which the prefix does
// not let through — the callback is kept before that
try {
	(new \PHPStanTurbo\OutputBufferHelper($ieKeeping))->applyLevelDelta($ieNodeScopeResolver, $ieScope, 1);
} catch (\TypeError $e) {
	check(str_contains($e->getMessage(), 'PHPStanTurbo\\'), 'escaping getTypeCallback (OutputBufferHelper): ' . $e->getMessage());
}
unset($storage, $ieVarResult, $ieTypeCallback);
gc_collect_cycles();
$ieDeepStack(200);
check(count($ieKeeping->kept) === 17, 'escaping getTypeCallback: the keeping resolver was handed the callbacks (' . count($ieKeeping->kept) . ')');
foreach ($ieKeeping->kept as $i => [$method, $callback, $asked, $answered]) {
	$ieDeepStack(50);
	try {
		$again = $ieDescribe($callback($asked));
	} catch (\Throwable $e) {
		$again = get_class($e) . ': ' . $e->getMessage();
	}
	check($again === $answered, "escaping getTypeCallback #$i ($method): answered '$answered' inside the call, '$again' after it");
}

// the resolver's own `fn (Expr $expr): Type => $this->getType($expr, $context)`
// handed to OversizedArrayBuilder, and a PHP callable handed through
$ieBuilder = new class {

	/** @var list<callable> */
	public array $kept = [];

	public function build(\PhpParser\Node\Expr\Array_ $expr, callable $getTypeCallback): \PHPStan\Type\Type
	{
		$this->kept[] = $getTypeCallback;
		// a type of the side under test: the prefixed native resolver computes
		// with it
		return new \PHPStanTurbo\MixedType();
	}

};
// the operator extensions (GMP, BcMath) are PHP code over PHP types, which the
// prefixed native types cannot enter: registries without extensions
$ieNoOperatorExtensions = new class {

	public function callOperatorTypeSpecifyingExtensions(\PhpParser\Node\Expr\BinaryOp $expr, \PHPStan\Type\Type $leftType, \PHPStan\Type\Type $rightType): ?\PHPStan\Type\Type
	{
		return null;
	}

	public function callUnaryOperatorTypeSpecifyingExtensions(string $operatorSigil, \PHPStan\Type\Type $operandType): ?\PHPStan\Type\Type
	{
		return null;
	}

};
$ieResolver = new \PHPStanTurbo\InitializerExprTypeResolver(
	$ieContainer->getByType(\PHPStan\Analyser\ConstantResolver::class),
	$ieContainer->getByType(\PHPStan\Reflection\ReflectionProvider\ReflectionProviderProvider::class),
	$ieContainer->getByType(\PHPStan\Php\PhpVersion::class),
	$ieNoOperatorExtensions,
	$ieNoOperatorExtensions,
	$ieBuilder,
	false,
);
$ieOversized = new \PhpParser\Node\Expr\Array_(array_map(static fn (int $i) => new \PhpParser\Node\ArrayItem(new \PhpParser\Node\Scalar\Int_($i)), range(0, 300)));
$ieContext = \PHPStan\Reflection\InitializerExprContext::fromClass(\PHPStan\TrinaryLogic::class, null);
$ieResolver->getType($ieOversized, $ieContext);
$ieResolver->getType(new \PhpParser\Node\Expr\BinaryOp\Plus(new \PhpParser\Node\Scalar\Int_(1), new \PhpParser\Node\Expr\UnaryMinus($ieOversized)), $ieContext);
$iePhpCallable = static fn (\PhpParser\Node\Expr $expr): \PHPStan\Type\Type => new \PHPStanTurbo\IntegerType();
$ieResolver->getArrayType($ieOversized, $iePhpCallable);
check(count($ieBuilder->kept) === 3 && $ieBuilder->kept[2] === $iePhpCallable, 'escaping getTypeCallback (OversizedArrayBuilder): the builder was handed the callables, the PHP one itself');
$ieAsked = [
	new \PhpParser\Node\Scalar\Int_(7),
	new \PhpParser\Node\Scalar\MagicConst\Class_(),
	new \PhpParser\Node\Expr\BinaryOp\Mul(new \PhpParser\Node\Scalar\Int_(6), new \PhpParser\Node\Scalar\Int_(7)),
	new \PhpParser\Node\Expr\Array_([new \PhpParser\Node\ArrayItem(new \PhpParser\Node\Scalar\String_('x'), new \PhpParser\Node\Scalar\String_('k'))]),
];
$ieExpected = array_map(static fn ($expr) => $ieDescribe($ieResolver->getType($expr, $ieContext)), $ieAsked);
unset($ieResolver, $ieContext);
gc_collect_cycles();
$ieDeepStack(200);
foreach ([0, 1] as $keptIndex) {
	foreach ($ieAsked as $i => $expr) {
		$ieDeepStack(50);
		check($ieDescribe($ieBuilder->kept[$keptIndex]($expr)) === $ieExpected[$i], "escaping getTypeCallback (OversizedArrayBuilder #$keptIndex, expression $i): " . $ieExpected[$i]);
	}
}
try {
	$ieBuilder->kept[0]();
	check(false, 'escaping getTypeCallback: the kept closure requires its argument');
} catch (\ArgumentCountError $e) {
	check(str_starts_with($e->getMessage(), 'Too few arguments to function PHPStan\Reflection\InitializerExprTypeResolver::{closure}'), 'escaping getTypeCallback: ArgumentCountError message: ' . $e->getMessage());
}
try {
	$ieBuilder->kept[0]('nope');
	check(false, 'escaping getTypeCallback: the kept closure checks its argument');
} catch (\TypeError $e) {
	check(str_contains($e->getMessage(), 'must be of type PhpParser\Node\Expr, string given'), 'escaping getTypeCallback: TypeError message: ' . $e->getMessage());
}

if (isset($initializerEscapeStandalone)) {
	echo $failures === 0 ? "ALL OK\n" : "$failures FAILURES\n";
	exit($failures === 0 ? 0 : 1);
}

}

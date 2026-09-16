<?php declare(strict_types = 1);

/**
 * Differential test of the native MutatingScope against the PHP twin,
 * under the prefixed activation: PHPStanTurbo\MutatingScope is declared
 * next to PHPStan\Analyser\MutatingScope (which keeps its real name and
 * its PHP body there), so both sides live in one process. In a
 * production run the native class carries the real name instead.
 *
 * Realistic scopes come from a real NodeScopeResolver walk over
 * scope-family-fixture.php through the DI container; every distinct walk
 * scope the callback sees is rebuilt twice from its own constructor
 * arguments (read by reflection): once as the PHP twin, once as the
 * native class — both over a RecordingScopeFactory, so the
 * $this->scopeFactory->create(...) sites are compared by the exact
 * argument list they produce rather than by the scope they get back.
 * The native side's tables hold native ExpressionTypeHolders (the native
 * bodies read them through their slots; the PHP twin's typed returns
 * need PHP TrinaryLogic on its side), normalized before comparison.
 *
 * The three union-filtering member lookups a ported body dispatches
 * through $this over the walk's own PHP types (getMethodReflection() and
 * the two property lookups) are routed to the original walk scope by the
 * NativeScope test subclass on both sides — see the barrier below; their
 * native bodies are probed directly, with types of the side under test.
 *
 * The prefix is a type barrier: the engine collaborators the type
 * resolution core hands the walk scope to (NodeScopeResolver::processExprOnDemand(),
 * ExpressionResult::getTypeOnScope(), ClosureTypeResolver::getClosureType())
 * are typed with the real class name, which the prefixed native class is
 * not. Both sides therefore override the (non-final, dispatched)
 * toWalkScope() to answer the original walk scope — the native side must,
 * and the PHP side does the same so the two walks stay symmetric (the
 * PhpScope subclass); the native bodies are still observed
 * through their own toWalkScope() dispatch. A body that passes $this
 * itself (TemplateArgumentFrame::returnTypeOfCall() from
 * resolveScopeStateType(), `new ExpressionResultStorage()` for the
 * on-demand walk with no analysis in progress) cannot cross the barrier
 * under the prefix, and neither can a PHP twin of a shadowed class flowing
 * into a native body that requires the native class (ClassReflection's
 * getObjectType() answering the native ObjectType's ancestor lookup with
 * the PHP ObjectType): such an observation is recorded as a barrier hit
 * (Harness::BARRIER) and skipped, counted in the summary.
 *
 * Included by smoke.php (uses its check()); runnable alone too.
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
	$scopeFamilyStandalone = true;
}

}

namespace ScopeFamily {

use PhpParser\Node;
use PHPStan\Analyser\ConditionalExpressionHolder;
use PHPStan\Analyser\ExpressionTypeHolder;
use PHPStan\Analyser\InternalScopeFactory;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * An InternalScopeFactory that records every create() argument list and
 * hands back a fixed result; the parameter types are widened so a native
 * ClosureType or a native scope passes where the twin's interface names
 * the PHP classes.
 */
final class RecordingScopeFactory implements InternalScopeFactory
{

	/** @var list<array<int, mixed>> */
	public array $calls = [];

	public ?self $nodeCallbackScopeFactory = null;

	/**
	 * When set, create() builds a real scope of the side under test out of its
	 * arguments instead of answering with the canned $result: most bodies
	 * chain ($scope = $this->a()->b()), and a canned result cannot
	 * model a chain — nor the twin's ScopeOps::scopeWith(), which reaches the
	 * factory through duplicateWith() while the native one clones.
	 *
	 * @var (callable(array<int, mixed>): MutatingScope)|null
	 */
	public $builder = null;

	public function __construct(public MutatingScope $result)
	{
	}

	public function create(
		ScopeContext $context,
		bool $declareStrictTypes = false,
		$function = null,
		?string $namespace = null,
		array $expressionTypes = [],
		array $nativeExpressionTypes = [],
		array $conditionalExpressions = [],
		array $inClosureBindScopeClasses = [],
		$anonymousFunctionReflection = null,
		bool $inFirstLevelStatement = true,
		array $currentlyAssignedExpressions = [],
		array $currentlyAllowedUndefinedExpressions = [],
		array $inFunctionCallsStack = [],
		bool $afterExtractCall = false,
		$parentScope = null,
		bool $nativeTypesPromoted = false,
		$templateArgumentFrame = null,
		$templateArgumentConstraints = null,
	): MutatingScope
	{
		$this->calls[] = func_get_args();
		if ($this->builder === null) {
			return $this->result;
		}

		return ($this->builder)([
			$context, $declareStrictTypes, $function, $namespace, $expressionTypes, $nativeExpressionTypes,
			$conditionalExpressions, $inClosureBindScopeClasses, $anonymousFunctionReflection, $inFirstLevelStatement,
			$currentlyAssignedExpressions, $currentlyAllowedUndefinedExpressions, $inFunctionCallsStack,
			$afterExtractCall, $parentScope, $nativeTypesPromoted, $templateArgumentFrame, $templateArgumentConstraints,
		]);
	}

	public function toNodeCallbackScopeFactory(): InternalScopeFactory
	{
		return $this->nodeCallbackScopeFactory ??= new self($this->result);
	}

	public function toWalkScopeFactory(): InternalScopeFactory
	{
		return $this;
	}

}

/**
 * The native class under test, subclassed for the harness: it answers
 * toWalkScope() with the original walk scope, which the PHP collaborators
 * typed with the twin's class name accept, and routes the three
 * union-filtering member lookups there too (see the file comment). The
 * class itself carries the twin's interfaces.
 */
/**
 * Sets the NodeScopeResolver guard diagnostics the scope reads: the PHP twin
 * reads PHPStan\Analyser\NodeScopeResolver's statics, the native scope those
 * of the native NodeScopeResolver it is declared with (PHPStanTurbo\ under
 * the prefix).
 *
 * @param array<int, true> $realExprIds
 * @param array<int, true> $processedExprIds
 */
function setGuards(bool $newWorld, array $realExprIds, array $processedExprIds): void
{
	foreach ([\PHPStan\Analyser\NodeScopeResolver::class, 'PHPStanTurbo\\NodeScopeResolver'] as $class) {
		if (!class_exists($class, false)) {
			continue;
		}
		$class::$guardNewWorld = $newWorld;
		$class::$guardRealExprIds = $realExprIds;
		$class::$guardProcessedExprIds = $processedExprIds;
	}
}

function declareNativeScope(): void
{
	eval(
		"namespace ScopeFamily; final class NativeScope extends \\PHPStanTurbo\\MutatingScope {\n"
		. "\tpublic ?\\PHPStan\\Analyser\\MutatingScope \$twin = null;\n"
		. "\t/** the walk scope the engine collaborators accept (see the file comment); the native method declares the twin's return type */\n"
		. "\tpublic function toWalkScope(): \\PHPStan\\Analyser\\MutatingScope { return \$this->twin; }\n"
		. "\tpublic function parentToWalkScope(): \\PHPStanTurbo\\MutatingScope { return parent::toWalkScope(); }\n"
		// The three member lookups resolveScopeStateType() dispatches by name
		// over the walk's own (PHP) types: their union filter tests
		// `instanceof UnionType` against the native class, which a PHP
		// UnionType is not under the prefix, so a `Holder|null` property read
		// would answer with no reflection where the twin filters the null
		// away. Both sides therefore keep taking these through the original
		// walk scope; the native bodies are probed directly through the
		// native<Name>() accessors below, with types of the side under test.
		. "\tpublic function getMethodReflection(\\PHPStan\\Type\\Type \$typeWithMethod, string \$methodName): ?\\PHPStan\\Reflection\\ExtendedMethodReflection { return \$this->twin->getMethodReflection(\$typeWithMethod, \$methodName); }\n"
		. "\tpublic function getInstancePropertyReflection(\\PHPStan\\Type\\Type \$typeWithProperty, string \$propertyName): ?\\PHPStan\\Reflection\\ExtendedPropertyReflection { return \$this->twin->getInstancePropertyReflection(\$typeWithProperty, \$propertyName); }\n"
		. "\tpublic function getStaticPropertyReflection(\\PHPStan\\Type\\Type \$typeWithProperty, string \$propertyName): ?\\PHPStan\\Reflection\\ExtendedPropertyReflection { return \$this->twin->getStaticPropertyReflection(\$typeWithProperty, \$propertyName); }\n"
		. "\tpublic function nativeGetMethodReflection(\\PHPStan\\Type\\Type \$typeWithMethod, string \$methodName): ?\\PHPStan\\Reflection\\ExtendedMethodReflection { return parent::getMethodReflection(\$typeWithMethod, \$methodName); }\n"
		. "\tpublic function nativeGetInstancePropertyReflection(\\PHPStan\\Type\\Type \$typeWithProperty, string \$propertyName): ?\\PHPStan\\Reflection\\ExtendedPropertyReflection { return parent::getInstancePropertyReflection(\$typeWithProperty, \$propertyName); }\n"
		. "\tpublic function nativeGetStaticPropertyReflection(\\PHPStan\\Type\\Type \$typeWithProperty, string \$propertyName): ?\\PHPStan\\Reflection\\ExtendedPropertyReflection { return parent::getStaticPropertyReflection(\$typeWithProperty, \$propertyName); }\n"
		. "}"
	);
}

declareNativeScope();

/** The PHP side's counterpart: the same walk-scope delegation over the twin. */
final class PhpScope extends MutatingScope
{

	public ?MutatingScope $inner = null;

	public function toWalkScope(): MutatingScope
	{
		return $this->inner ?? $this;
	}

	public function parentToWalkScope(): MutatingScope
	{
		return parent::toWalkScope();
	}

	/** The PHP counterparts of NativeScope's native<Name>() accessors. */
	public function nativeGetMethodReflection(Type $typeWithMethod, string $methodName): ?\PHPStan\Reflection\ExtendedMethodReflection
	{
		return parent::getMethodReflection($typeWithMethod, $methodName);
	}

	public function nativeGetInstancePropertyReflection(Type $typeWithProperty, string $propertyName): ?\PHPStan\Reflection\ExtendedPropertyReflection
	{
		return parent::getInstancePropertyReflection($typeWithProperty, $propertyName);
	}

	public function nativeGetStaticPropertyReflection(Type $typeWithProperty, string $propertyName): ?\PHPStan\Reflection\ExtendedPropertyReflection
	{
		return parent::getStaticPropertyReflection($typeWithProperty, $propertyName);
	}

	/**
	 * The one private method of the twin the prefixed native bodies reach by
	 * name (specifyExpressionType() opens its working copy through the
	 * factory, which cannot answer with the prefixed class): they hand over
	 * the native TrinaryLogic singleton, which the twin's typed parameter
	 * rejects. A method-table lookup finds this declaration over the
	 * inherited one; the twin's body runs through a closure bound to its own
	 * scope. Both sides go through it, so nothing is one-sided.
	 */
	private function specifyExpressionTypeInPlace(Node\Expr $expr, Type $type, Type $nativeType, object $certainty): void
	{
		if ($certainty instanceof \PHPStanTurbo\TrinaryLogic) {
			$certainty = self::phpTrinary($certainty);
		}
		\Closure::bind(function () use ($expr, $type, $nativeType, $certainty): void {
			$this->specifyExpressionTypeInPlace($expr, $type, $nativeType, $certainty);
		}, $this, MutatingScope::class)();
	}

	/**
	 * The two public entries the prefixed native bodies reach with an argument
	 * of the prefixed class: the TrinaryLogic they build themselves and the
	 * scope they were handed. Parameter contravariance makes the widening
	 * legal; both sides go through these, so nothing is one-sided.
	 *
	 * @param list<string> $intertwinedPropagatedFrom
	 */
	public function assignVariable(string $variableName, Type $type, Type $nativeType, object $certainty, array $intertwinedPropagatedFrom = []): MutatingScope
	{
		return parent::assignVariable($variableName, $type, $nativeType, $certainty instanceof \PHPStanTurbo\TrinaryLogic ? self::phpTrinary($certainty) : $certainty, $intertwinedPropagatedFrom);
	}

	public function enterForeachKey(object $originalScope, Node\Expr $iteratee, Type $iterateeType, Type $nativeIterateeType, string $keyName): MutatingScope
	{
		return parent::enterForeachKey($originalScope instanceof MutatingScope ? $originalScope : $this, $iteratee, $iterateeType, $nativeIterateeType, $keyName);
	}

	private static function phpTrinary(\PHPStanTurbo\TrinaryLogic $certainty): \PHPStan\TrinaryLogic
	{
		return $certainty->yes()
			? \PHPStan\TrinaryLogic::createYes()
			: ($certainty->maybe() ? \PHPStan\TrinaryLogic::createMaybe() : \PHPStan\TrinaryLogic::createNo());
	}

}

/**
 * A deferred SpecifiedTypes augment for applySpecifiedTypes(): the interface
 * types evaluate() with the twin's class, which the prefixed native scope is
 * not — a widened parameter type (contravariance) lets both sides through.
 */
final class TestAugment implements \PHPStan\Analyser\DeferredSpecifiedTypesAugment
{

	public function __construct(private ?\PHPStan\Analyser\SpecifiedTypes $result)
	{
	}

	public function evaluate(object $scope): ?\PHPStan\Analyser\SpecifiedTypes
	{
		return $this->result;
	}

}

/**
 * A conditional-expression holder recipe: applySpecifiedTypes() calls
 * evaluate() by name, and the twin's ConditionalExpressionHolderRecipe is
 * final with a MutatingScope-typed parameter the prefixed scope cannot cross.
 */
final class TestRecipe
{

	/** @param array<string, array<string, object>> $result */
	public function __construct(private array $result)
	{
	}

	/** @return array<string, array<string, object>> */
	public function evaluate(object $scope): array
	{
		return $this->result;
	}

}

final class Harness
{

	/** an observation the prefix's type barrier cut short (see the file comment) */
	public const BARRIER = ['L', 'prefix type barrier'];

	/** @var array<string, string> */
	private array $classNorm;

	/**
	 * The expressions both sides share (the walk's own nodes, held by the
	 * rebuilt tables): a holder over one of them is compared by its
	 * identity, a holder over an expression a body just built by that
	 * expression's class and printed key.
	 *
	 * @var array<int, true>
	 */
	public array $sharedExprIds = [];

	/** The scope under test on this side: `$this` in a normalized value. */
	public ?object $currentScope = null;

	private \PHPStan\Node\Printer\ExprPrinter $exprPrinter;

	/** @param array<string, array{turboClass: string}> $manifest */
	public function __construct(array $manifest, \PHPStan\DependencyInjection\Container $container)
	{
		$this->classNorm = [];
		foreach ($manifest as $shadowedClass => $entry) {
			$this->classNorm[$entry['turboClass']] = $shadowedClass;
		}
		$this->classNorm[\PHPStanTurbo\MutatingScope::class] = MutatingScope::class;
		$this->classNorm[NativeScope::class] = MutatingScope::class;
		$this->classNorm[PhpScope::class] = MutatingScope::class;
		$this->exprPrinter = $container->getByType(\PHPStan\Node\Printer\ExprPrinter::class);
	}

	public function className(object $object): string
	{
		return strtr(get_class($object), $this->classNorm);
	}

	/** The 33 constructor arguments of a scope, by parameter name. */
	public function constructorArgs(MutatingScope $scope): array
	{
		$args = [];
		$reflection = new \ReflectionClass(MutatingScope::class);
		foreach ($reflection->getMethod('__construct')->getParameters() as $parameter) {
			$property = $reflection->getProperty($parameter->getName());
			$args[$parameter->getName()] = $property->getValue($scope);
		}

		return $args;
	}

	/** @param array<string, ExpressionTypeHolder> $table */
	public function nativeHolders(array $table): array
	{
		$result = [];
		foreach ($table as $key => $holder) {
			$certainty = $holder->getCertainty();
			$result[$key] = new \PHPStanTurbo\ExpressionTypeHolder(
				$holder->getExpr(),
				$holder->getType(),
				$certainty->yes() ? \PHPStanTurbo\TrinaryLogic::createYes() : ($certainty->maybe() ? \PHPStanTurbo\TrinaryLogic::createMaybe() : \PHPStanTurbo\TrinaryLogic::createNo()),
			);
		}

		return $result;
	}

	/**
	 * The native ConditionalExpressionHolders of a conditionalExpressions
	 * table: ScopeOps' native bodies read them through their slots, as they do
	 * the ExpressionTypeHolders.
	 *
	 * @param array<string, list<ConditionalExpressionHolder>> $table
	 */
	public function nativeConditionalExpressions(array $table): array
	{
		$result = [];
		foreach ($table as $exprString => $holders) {
			$converted = [];
			foreach ($holders as $key => $holder) {
				$converted[$key] = new \PHPStanTurbo\ConditionalExpressionHolder(
					$this->nativeHolders($holder->getConditionExpressionTypeHolders()),
					$this->nativeHolders(['x' => $holder->getTypeHolder()])['x'],
				);
			}
			$result[$exprString] = $converted;
		}

		return $result;
	}

	/** A comparable, side-independent rendering of any value. */
	public function norm(mixed $value, int $depth = 0): mixed
	{
		if (is_array($value)) {
			$out = [];
			foreach ($value as $k => $v) {
				$out[$k] = $this->norm($v, $depth + 1);
			}
			return $out;
		}
		if (!is_object($value)) {
			return $value;
		}
		if ($value instanceof ExpressionTypeHolder || $value instanceof \PHPStanTurbo\ExpressionTypeHolder) {
			return ['H', $this->exprRef($value->getExpr()), $this->norm($value->getType()), $this->norm($value->getCertainty())];
		}
		if ($value instanceof \PHPStan\TrinaryLogic || $value instanceof \PHPStanTurbo\TrinaryLogic) {
			return ['T', $value->describe()];
		}
		if ($value instanceof Type) {
			return ['Y', $this->className($value), $value->describe(VerbosityLevel::precise())];
		}
		if ($value instanceof ConditionalExpressionHolder || $value instanceof \PHPStanTurbo\ConditionalExpressionHolder) {
			return ['C', $value->getKey()];
		}
		if ($value instanceof MutatingScope || $value instanceof \PHPStanTurbo\MutatingScope) {
			if ($value === $this->currentScope) {
				return ['S', 'this'];
			}
			return ['S', $this->className($value), spl_object_id($value)];
		}
		if ($value instanceof \PHPStan\Analyser\ExpressionResult) {
			// an on-demand walk answers with a result over its own copy of the node
			return ['R', $this->norm($value->getExpr()), $value->canResolveOwnType()];
		}
		if ($value instanceof \PHPStan\Analyser\Generics\TemplateArgumentConstraints) {
			// each side builds its own empty constraints
			return ['TAC', $value->isEmpty()];
		}
		if ($value instanceof \PHPStan\Php\PhpVersions) {
			return ['PV', $this->norm($value->getType())];
		}
		if ($value instanceof ScopeContext) {
			// the enter* family builds a fresh one on each side
			return ['SC', $value->getFile(), $value->getClassReflection()?->getName(), $value->getTraitReflection()?->getName()];
		}
		if ($value instanceof \PHPStan\Analyser\SpecifiedTypes) {
			return ['ST', $this->norm($value->getSureTypes()), $this->norm($value->getSureNotTypes()), $value->shouldOverwrite(), $this->norm($value->getRootExpr())];
		}
		if ($value instanceof Node\Expr) {
			return ['X', $this->className($value), $this->key($value)];
		}
		if ($value instanceof \Throwable && getenv('SF_BARRIER_TRACE') !== false) {
			echo 'THROW: ', get_class($value), ': ', $value->getMessage(), "\n";
		}
		if ($value instanceof \TypeError && (
			preg_match('~must be of type \??PHPStan\\\\[A-Za-z\\\\]+, (PHPStanTurbo\\\\|ScopeFamily\\\\NativeScope)~', $value->getMessage()) === 1
			|| preg_match('~^phpstan_turbo: .*\\(\\) must return PHPStanTurbo\\\\~', $value->getMessage()) === 1
		)) {
			return self::BARRIER;
		}
		if ($value instanceof \PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection) {
			// the enter* family builds a fresh one on each side
			return [
				'FR',
				$this->className($value),
				$value->getName(),
				$this->norm($value->getParameters()),
				$value->isVariadic(),
				$this->norm($value->getReturnType()),
				$this->norm($value->getPhpDocReturnType()),
				$this->norm($value->getNativeReturnType()),
				$this->norm($value->getThrowType()),
				$value->isDeprecated()->describe(),
				$value->getDeprecatedDescription(),
				$value->isInternal()->describe(),
				$this->norm($value->isPure()),
				$value->acceptsNamedArguments()->describe(),
				$this->norm($value->getAttributes()),
				$value->getDocComment(),
			];
		}
		if ($value instanceof \PHPStan\Reflection\AttributeReflection) {
			return ['A', $value->getName(), $this->norm($value->getArgumentTypes())];
		}
		if ($value instanceof \PHPStan\Reflection\ParameterReflection) {
			// each side builds its own parameter reflections for the call stack
			return [
				'P',
				$value->getName(),
				$this->norm($value->getType()),
				$value->isOptional(),
				$value->isVariadic(),
				$value->passedByReference()->createsNewVariable(),
				$this->norm($value->getDefaultValue()),
				$value instanceof \PHPStan\Reflection\ExtendedParameterReflection ? $this->norm($value->getNativeType()) : null,
				$value instanceof \PHPStan\Reflection\ExtendedParameterReflection ? $this->norm($value->getAttributes()) : null,
			];
		}
		if ($value instanceof \Throwable) {
			return ['E', $this->className($value), strtr($value->getMessage(), $this->classNorm)];
		}

		return ['O', $this->className($value), spl_object_id($value)];
	}

	public function key(Node\Expr $expr): string
	{
		return $this->exprPrinter->printExpr($expr);
	}

	/** An expression shared by both sides by its identity, one a body built by its key. */
	public function exprRef(Node\Expr $expr): mixed
	{
		$id = spl_object_id($expr);

		return isset($this->sharedExprIds[$id]) ? $id : ['fresh', $this->className($expr), $this->key($expr)];
	}

}

}

namespace {

use ScopeFamily\Harness;
use ScopeFamily\NativeScope;
use ScopeFamily\PhpScope;
use ScopeFamily\RecordingScopeFactory;

$sfManifest = json_decode(file_get_contents(dirname(__DIR__, 2) . '/vendor/turbo-shadowed-classes.json'), true, 8, JSON_THROW_ON_ERROR);

// a container of its own: the fixture must be an analysed path for the
// reflection provider to find its classes
$sfFile = __DIR__ . '/scope-family-fixture.php';
$sfContainerFactory = new \PHPStan\DependencyInjection\ContainerFactory(dirname(__DIR__, 2));
$scContainer = $sfContainerFactory->create(sys_get_temp_dir() . '/phpstan-turbo-smoke-scope', [$sfContainerFactory->getConfigDirectory() . '/config.level8.neon', ...(PHP_VERSION_ID < 80400 ? [__DIR__ . '/php84-syntax.neon'] : [])], [$sfFile]);
$sfHarness = new Harness($sfManifest, $scContainer);

// ---- collect the walk scopes of a real analysis of the fixture ----
$sfResolver = $scContainer->getByType(\PHPStan\Analyser\NodeScopeResolver::class);
$sfResolver->setAnalysedFiles([$sfFile]);
$sfResolver->resetPerFileAnalysisState();
$sfScopeFactory = $scContainer->getByType(\PHPStan\Analyser\ScopeFactory::class);
/** @var array<int, array{\PHPStan\Analyser\MutatingScope, list<\PhpParser\Node>, \PHPStan\Analyser\ExpressionResultStorage|null}> $sfScopes */
$sfScopes = [];
$sfCallback = static function (\PhpParser\Node $node, \PHPStan\Analyser\Scope $scope) use (&$sfScopes): void {
	$walkScope = $scope->toWalkScope();
	$id = spl_object_id($walkScope);
	// the storage of the analysis in progress here, re-pushed when the
	// scope's type answers are observed after the walk
	$sfScopes[$id] ??= [$walkScope, [], $walkScope->getCurrentExpressionResultStorage()];
	if ($node instanceof \PhpParser\Node\Expr && count($sfScopes[$id][1]) < 6) {
		$sfScopes[$id][1][] = $node;
	}
};
$sfResolver->processNodes(
	$scContainer->getService('defaultAnalysisParser')->parseFile($sfFile),
	$sfScopeFactory->create(\PHPStan\Analyser\ScopeContext::create($sfFile), $sfCallback),
	$sfCallback,
);
check(count($sfScopes) >= 30, 'scope-family: the fixture walk produced enough distinct scopes (' . count($sfScopes) . ')');

// one scope in a trait context, entered by hand (the walk enters traits
// through the using class)
$sfReflectionProvider = $scContainer->getByType(\PHPStan\Reflection\ReflectionProvider::class);
$sfTraitContext = \PHPStan\Analyser\ScopeContext::create($sfFile)
	->enterClass($sfReflectionProvider->getClass(\ScopeFamilyFixture\Holder::class))
	->enterTrait($sfReflectionProvider->getClass(\ScopeFamilyFixture\HelperTrait::class));
$sfTraitScope = $sfScopeFactory->create($sfTraitContext)->enterNamespace('ScopeFamilyFixture')->assignVariable('t', new \PHPStan\Type\IntegerType(), new \PHPStan\Type\IntegerType(), \PHPStan\TrinaryLogic::createYes());
$sfScopes[spl_object_id($sfTraitScope)] = [$sfTraitScope, [new \PhpParser\Node\Expr\Variable('t')], null];
// a class with custom serialization, for rememberConstructorScope()
$sfCustomScope = $sfScopeFactory->create(\PHPStan\Analyser\ScopeContext::create($sfFile)->enterClass($sfReflectionProvider->getClass(\ScopeFamilyFixture\Custom::class)))
	->enterNamespace('ScopeFamilyFixture')
	->assignVariable('this', new \PHPStan\Type\ObjectType(\ScopeFamilyFixture\Custom::class), new \PHPStan\Type\ObjectType(\ScopeFamilyFixture\Custom::class), \PHPStan\TrinaryLogic::createYes())
	->assignExpression(new \PhpParser\Node\Expr\PropertyFetch(new \PhpParser\Node\Expr\Variable('this'), 'value'), new \PHPStan\Type\Constant\ConstantIntegerType(5), new \PHPStan\Type\IntegerType())
	->assignExpression(new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('class_exists'), [new \PhpParser\Node\Arg(new \PhpParser\Node\Scalar\String_('X'))]), new \PHPStan\Type\Constant\ConstantBooleanType(true), new \PHPStan\Type\BooleanType())
	->assignExpression(new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('ANSWER')), new \PHPStan\Type\Constant\ConstantIntegerType(42), new \PHPStan\Type\IntegerType());
$sfScopes[spl_object_id($sfCustomScope)] = [$sfCustomScope, [new \PhpParser\Node\Expr\Variable('this')], null];
// tracked qualified and unqualified function calls, for the
// afterClearstatcacheCall() / afterOpenSslCall() key matching
$sfTrue = new \PHPStan\Type\Constant\ConstantBooleanType(true);
$sfBool = new \PHPStan\Type\BooleanType();
$sfCallsScope = $sfScopeFactory->create(\PHPStan\Analyser\ScopeContext::create($sfFile))
	->assignExpression(new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name\FullyQualified('file_exists'), [new \PhpParser\Node\Arg(new \PhpParser\Node\Expr\Variable('p'))]), $sfTrue, $sfBool)
	->assignExpression(new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('is_dir'), [new \PhpParser\Node\Arg(new \PhpParser\Node\Expr\Variable('p'))]), $sfTrue, $sfBool)
	->assignExpression(new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('is_writeable_not'), [new \PhpParser\Node\Arg(new \PhpParser\Node\Expr\Variable('p'))]), $sfTrue, $sfBool)
	->assignExpression(new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name\FullyQualified('openssl_error_string')), new \PHPStan\Type\StringType(), new \PHPStan\Type\StringType())
	->assignExpression(new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name\FullyQualified('class_exists'), [new \PhpParser\Node\Arg(new \PhpParser\Node\Scalar\String_('Nope'))]), new \PHPStan\Type\Constant\ConstantBooleanType(false), $sfBool)
	// expressionTypeIsUnchangeable(): a qualified existence check that holds,
	// over a constant string, with no variable inside
	->assignExpression(new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name\FullyQualified('class_exists'), [new \PhpParser\Node\Arg(new \PhpParser\Node\Scalar\String_('ScopeFamilyFixture\\Holder'))]), $sfTrue, $sfBool)
	->assignExpression(new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name\FullyQualified('interface_exists'), [new \PhpParser\Node\Arg(new \PhpParser\Node\Expr\Variable('p'))]), $sfTrue, $sfBool);
$sfScopes[spl_object_id($sfCallsScope)] = [$sfCallsScope, [new \PhpParser\Node\Expr\Variable('p')], null];
// two scopes tracking PHP_VERSION_ID: getPhpVersion() then answers with the
// walk's own (PHP) type on both sides, which is what makes the variadic
// parameter shapes of getFunctionType() comparable under the prefix — over a
// native fallback type (a native ConstantIntegerType / IntegerRangeType) the
// PHP IntegerRangeType::isSuperTypeOf() inside PhpVersions answers "no"
// whatever the version is
// a scope inside a class_exists() call: isInClassExists()'s stack scan
$sfClassExistsScope = $sfScopeFactory->create(\PHPStan\Analyser\ScopeContext::create($sfFile))
	->pushInFunctionCall($sfReflectionProvider->getFunction(new \PhpParser\Node\Name('class_exists'), null), null, false);
$sfScopes[spl_object_id($sfClassExistsScope)] = [$sfClassExistsScope, [new \PhpParser\Node\Expr\Variable('x')], null];
// PHP_VERSION_ID as the overall analysable range: getPhpVersion() ignores it
// and falls back to the configured version. The range is each side's own
// class — the native body's `instanceof IntegerRangeType` is the native class
// under the prefix, where a PHP twin range would not be recognized
$sfOverallVersionFetch = new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('PHP_VERSION_ID'));
$sfOverallVersionScope = $sfScopeFactory->create(\PHPStan\Analyser\ScopeContext::create($sfFile))
	->assignExpression($sfOverallVersionFetch, \PHPStan\Type\IntegerRangeType::fromInterval(\PHPStan\Analyser\ConstantResolver::PHP_MIN_ANALYZABLE_VERSION_ID, null), new \PHPStan\Type\IntegerType());
$sfScopes[spl_object_id($sfOverallVersionScope)] = [$sfOverallVersionScope, [$sfOverallVersionFetch], null];
$sfOverallVersionTables = static function (string $side) use ($sfHarness, $sfOverallVersionFetch): array {
	$native = $side === 'native';
	$range = $native
		? \PHPStanTurbo\IntegerRangeType::fromInterval(\PHPStan\Analyser\ConstantResolver::PHP_MIN_ANALYZABLE_VERSION_ID, null)
		: \PHPStan\Type\IntegerRangeType::fromInterval(\PHPStan\Analyser\ConstantResolver::PHP_MIN_ANALYZABLE_VERSION_ID, null);
	$holder = $native
		? new \PHPStanTurbo\ExpressionTypeHolder($sfOverallVersionFetch, $range, \PHPStanTurbo\TrinaryLogic::createYes())
		: new \PHPStan\Analyser\ExpressionTypeHolder($sfOverallVersionFetch, $range, \PHPStan\TrinaryLogic::createYes());
	$table = [$sfHarness->key($sfOverallVersionFetch) => $holder];

	return [$table, $table, []];
};
foreach ([80500, 70400] as $sfVersionId) {
	$sfVersionScope = $sfScopeFactory->create(\PHPStan\Analyser\ScopeContext::create($sfFile)->enterClass($sfReflectionProvider->getClass(\ScopeFamilyFixture\Holder::class)))
		->assignExpression(new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('PHP_VERSION_ID')), new \PHPStan\Type\Constant\ConstantIntegerType($sfVersionId), new \PHPStan\Type\IntegerType());
	$sfScopes[spl_object_id($sfVersionScope)] = [$sfVersionScope, [new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('PHP_VERSION_ID'))], null];
}
// a scope whose tables carry types built on each side's own classes: under
// the prefix a PHP twin Type's describe() rejects the native VerbosityLevel
// singleton, so getClosureScopeCacheKey() over the walk's (PHP) types is a
// barrier hit — these tables are that method's real coverage (the VirtualNode
// skip, the root filter, the parameter stack)
$sfSyntheticScope = $sfScopeFactory->create(\PHPStan\Analyser\ScopeContext::create($sfFile));
$sfScopes[spl_object_id($sfSyntheticScope)] = [$sfSyntheticScope, [new \PhpParser\Node\Expr\Variable('a'), new \PhpParser\Node\Expr\Variable('b')], null];
// the expressions are shared by both sides (the holders are compared by
// their expression's identity), the types and holders are each side's own
$sfSyntheticExprs = [
	[new \PhpParser\Node\Expr\Variable('a'), \PHPStan\Type\IntegerType::class, [], true],
	[new \PhpParser\Node\Expr\Variable('b'), \PHPStan\Type\Constant\ConstantStringType::class, ['x'], false],
	[new \PhpParser\Node\Expr\PropertyFetch(new \PhpParser\Node\Expr\Variable('a'), 'p'), \PHPStan\Type\ArrayType::class, [\PHPStan\Type\IntegerType::class, \PHPStan\Type\StringType::class], true],
	[new \PhpParser\Node\Expr\ArrayDimFetch(new \PhpParser\Node\Expr\Variable('b'), new \PhpParser\Node\Scalar\Int_(0)), \PHPStan\Type\Constant\ConstantIntegerType::class, [5], true],
	// no ObjectType: its cache-level description embeds static::class, which the prefix renames
	[new \PhpParser\Node\Expr\Variable('ab'), \PHPStan\Type\BooleanType::class, [], true],
	[new \PHPStan\Node\Expr\PropertyInitializationExpr('p'), \PHPStan\Type\NullType::class, [], true],
	// the ArrayDimFetch arm of specifyExpressionTypeInPlace() tests the dim type
	// against ConstantIntegerType/ConstantStringType and the var type against
	// MixedType — each side's own classes, so both must come from the tables
	// (getScopeStateType() answers a tracked Variable from the holder)
	[new \PhpParser\Node\Expr\Variable('arr'), \PHPStan\Type\ArrayType::class, [\PHPStan\Type\IntegerType::class, \PHPStan\Type\StringType::class], true],
	[new \PhpParser\Node\Expr\Variable('i'), \PHPStan\Type\Constant\ConstantIntegerType::class, [0], true],
	[new \PhpParser\Node\Expr\Variable('k'), \PHPStan\Type\Constant\ConstantStringType::class, ['k'], true],
	[new \PhpParser\Node\Expr\Variable('m'), \PHPStan\Type\MixedType::class, [], true],
	// a tracked static expression (invalidateStaticExpressions() drops it) and a
	// tracked method call on $a (invalidateMethodsOnExpression() drops that)
	[new \PhpParser\Node\Expr\StaticPropertyFetch(new \PhpParser\Node\Name('Holder'), 'shared'), \PHPStan\Type\IntegerType::class, [], true],
	[new \PhpParser\Node\Expr\MethodCall(new \PhpParser\Node\Expr\Variable('a'), 'm'), \PHPStan\Type\StringType::class, [], true],
];
$sfNativeOnlyExpr = new \PhpParser\Node\Expr\Variable('nativeOnly');
$sfSyntheticTables = static function (string $side) use ($sfHarness, $sfSyntheticExprs, $sfNativeOnlyExpr): array {
	$native = $side === 'native';
	$type = static function (string $phpClass, mixed ...$ctorArgs) use ($native): \PHPStan\Type\Type {
		$class = $native ? 'PHPStanTurbo\\' . substr($phpClass, strrpos($phpClass, '\\') + 1) : $phpClass;
		return new $class(...$ctorArgs);
	};
	$holder = static fn (\PhpParser\Node\Expr $expr, \PHPStan\Type\Type $t, bool $yes = true): object => $native
		? new \PHPStanTurbo\ExpressionTypeHolder($expr, $t, $yes ? \PHPStanTurbo\TrinaryLogic::createYes() : \PHPStanTurbo\TrinaryLogic::createMaybe())
		: new \PHPStan\Analyser\ExpressionTypeHolder($expr, $t, $yes ? \PHPStan\TrinaryLogic::createYes() : \PHPStan\TrinaryLogic::createMaybe());
	$string = $type(\PHPStan\Type\StringType::class);
	$tables = [];
	foreach ($sfSyntheticExprs as [$expr, $typeClass, $ctorArgs, $yes]) {
		// a class-string argument is a nested type
		$ctorArgs = array_map(static fn (mixed $arg): mixed => is_string($arg) && class_exists($arg) ? $type($arg) : $arg, $ctorArgs);
		$tables[$sfHarness->key($expr)] = $holder($expr, $type($typeClass, ...$ctorArgs), $yes);
	}
	// one entry whose native flavour is wider than its phpdoc one: the readers
	// that pick a flavour (getStateType() on a native-promoted scope,
	// addTypeToExpression()) are indistinguishable over two equal tables
	$nativeTables = $tables;
	$nativeTables[$sfHarness->key($sfSyntheticExprs[1][0])] = $holder($sfSyntheticExprs[1][0], $string, false);
	// the same expression node on both sides (a holder is compared by its
	// expression's identity), only the type differs
	$arrExpr = $sfSyntheticExprs[6][0];
	$nativeTables[$sfHarness->key($arrExpr)] = $holder($arrExpr, $type(\PHPStan\Type\ArrayType::class, $type(\PHPStan\Type\IntegerType::class), $type(\PHPStan\Type\IntegerType::class)));
	// an entry the native table tracks and the phpdoc one does not: the
	// current-type fallback must not overwrite a tracked native flavour
	$nativeTables[$sfHarness->key($sfNativeOnlyExpr)] = $holder($sfNativeOnlyExpr, $string);
	$parameter = $native
		? new \PHPStanTurbo\NativeParameterReflection('p', false, $string, \PHPStan\Reflection\PassedByReference::createNo(), false, null)
		: new \PHPStan\Reflection\Native\NativeParameterReflection('p', false, $string, \PHPStan\Reflection\PassedByReference::createNo(), false, null);

	return [$tables, $nativeTables, [[null, null], [null, $parameter]]];
};

// a scope bound to closure scope classes, followed by a plain one: the
// second one's $other is this one, which is what
// restoreOriginalScopeAfterClosureBind() / restoreThis() read
$sfBindScope = $sfScopeFactory->create(\PHPStan\Analyser\ScopeContext::create($sfFile)->enterClass($sfReflectionProvider->getClass(\ScopeFamilyFixture\Holder::class)))
	->enterNamespace('ScopeFamilyFixture')
	->assignVariable('this', new \PHPStan\Type\ObjectType(\ScopeFamilyFixture\Holder::class), new \PHPStan\Type\ObjectType(\ScopeFamilyFixture\Holder::class), \PHPStan\TrinaryLogic::createYes())
	->assignExpression(new \PhpParser\Node\Expr\PropertyFetch(new \PhpParser\Node\Expr\Variable('this'), 'name'), new \PHPStan\Type\Constant\ConstantStringType('n'), new \PHPStan\Type\StringType())
	->assignVariable('other', new \PHPStan\Type\IntegerType(), new \PHPStan\Type\IntegerType(), \PHPStan\TrinaryLogic::createYes())
	->withClosureBindScopeClasses(['ScopeFamilyFixture\\Holder', 'Other']);
$sfScopes[spl_object_id($sfBindScope)] = [$sfBindScope, [new \PhpParser\Node\Expr\Variable('this')], null];
$sfAfterBindScope = $sfScopeFactory->create(\PHPStan\Analyser\ScopeContext::create($sfFile))
	->assignVariable('x', new \PHPStan\Type\IntegerType(), new \PHPStan\Type\IntegerType(), \PHPStan\TrinaryLogic::createYes());
$sfScopes[spl_object_id($sfAfterBindScope)] = [$sfAfterBindScope, [new \PhpParser\Node\Expr\Variable('x')], null];

// a scope in the middle of an assignment: the currently-* tables the
// expression-assign family reads (an empty one cannot tell exitExpressionAssign()
// or isInWriteExpressionAssign() apart from a no-op)
$sfAssignedPropertyFetch = new \PhpParser\Node\Expr\PropertyFetch(new \PhpParser\Node\Expr\Variable('this'), 'name');
$sfAssignedDimFetch = new \PhpParser\Node\Expr\ArrayDimFetch(new \PhpParser\Node\Expr\Variable('items'), new \PhpParser\Node\Scalar\Int_(0));
$sfAssignScope = $sfScopeFactory->create(\PHPStan\Analyser\ScopeContext::create($sfFile)->enterClass($sfReflectionProvider->getClass(\ScopeFamilyFixture\Holder::class)))
	->enterNamespace('ScopeFamilyFixture')
	->assignVariable('this', new \PHPStan\Type\ObjectType(\ScopeFamilyFixture\Holder::class), new \PHPStan\Type\ObjectType(\ScopeFamilyFixture\Holder::class), \PHPStan\TrinaryLogic::createYes())
	->assignVariable('items', new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()), new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()), \PHPStan\TrinaryLogic::createYes())
	->enterExpressionAssign($sfAssignedPropertyFetch)
	->enterExpressionAssign($sfAssignedDimFetch, false)
	->setAllowedUndefinedExpression($sfAssignedDimFetch);
$sfScopes[spl_object_id($sfAssignScope)] = [$sfAssignScope, [$sfAssignedPropertyFetch, $sfAssignedDimFetch], null];

// a scope carrying conditional expressions keyed on names the probe closure
// uses, on one it does not, and one whose condition is not a use: the three
// arms of enterAnonymousFunctionWithoutReflection()'s conditional filter
$sfCondHolder = static fn (string $conditionKey, string $conditionName, string $targetName): \PHPStan\Analyser\ConditionalExpressionHolder => new \PHPStan\Analyser\ConditionalExpressionHolder(
	[$conditionKey => \PHPStan\Analyser\ExpressionTypeHolder::createYes(new \PhpParser\Node\Expr\Variable($conditionName), new \PHPStan\Type\IntegerType())],
	\PHPStan\Analyser\ExpressionTypeHolder::createYes(new \PhpParser\Node\Expr\Variable($targetName), new \PHPStan\Type\StringType()),
);
$sfConditionalScope = $sfScopeFactory->create(\PHPStan\Analyser\ScopeContext::create($sfFile))
	->assignVariable('p', new \PHPStan\Type\IntegerType(), new \PHPStan\Type\IntegerType(), \PHPStan\TrinaryLogic::createYes())
	->assignVariable('byRefUse', new \PHPStan\Type\IntegerType(), new \PHPStan\Type\IntegerType(), \PHPStan\TrinaryLogic::createYes())
	->addConditionalExpressions('$p', [$sfCondHolder('$byRefUse', 'byRefUse', 'p'), $sfCondHolder('$notAUse', 'notAUse', 'p')])
	->addConditionalExpressions('$notAUse', [$sfCondHolder('$p', 'p', 'notAUse')]);
$sfScopes[spl_object_id($sfConditionalScope)] = [$sfConditionalScope, [new \PhpParser\Node\Expr\Variable('p')], null];

// the scopes whose tables are built per side (each side's own Type classes)
$sfScopes[spl_object_id($sfSyntheticScope)][3] = $sfSyntheticTables;
$sfScopes[spl_object_id($sfOverallVersionScope)][3] = $sfOverallVersionTables;
$sfPromotedArgs = $sfHarness->constructorArgs($sfSyntheticScope);
$sfPromotedArgs['nativeTypesPromoted'] = true;
$sfPromotedScope = new \PHPStan\Analyser\MutatingScope(...array_values($sfPromotedArgs));
$sfScopes[spl_object_id($sfPromotedScope)] = [$sfPromotedScope, [new \PhpParser\Node\Expr\Variable('a'), new \PhpParser\Node\Expr\Variable('b')], null, $sfSyntheticTables];

// two adjacent scopes for the conditional bookkeeping and the merges: their
// tables AND their conditional expressions are built per side, so every type
// a native body combines is a native one (a foreign class is an atom to the
// native TypeCombinator). The second one's $other is the first.
$sfSideType = static function (string $side, string $phpClass, mixed ...$ctorArgs): \PHPStan\Type\Type {
	$class = $side === 'native' ? 'PHPStanTurbo\\' . substr($phpClass, strrpos($phpClass, '\\') + 1) : $phpClass;

	return new $class(...$ctorArgs);
};
$sfSideHolder = static function (string $side, \PhpParser\Node\Expr $expr, \PHPStan\Type\Type $type, string $certainty = 'yes'): object {
	if ($side === 'native') {
		return new \PHPStanTurbo\ExpressionTypeHolder($expr, $type, $certainty === 'yes' ? \PHPStanTurbo\TrinaryLogic::createYes() : ($certainty === 'maybe' ? \PHPStanTurbo\TrinaryLogic::createMaybe() : \PHPStanTurbo\TrinaryLogic::createNo()));
	}

	return new \PHPStan\Analyser\ExpressionTypeHolder($expr, $type, $certainty === 'yes' ? \PHPStan\TrinaryLogic::createYes() : ($certainty === 'maybe' ? \PHPStan\TrinaryLogic::createMaybe() : \PHPStan\TrinaryLogic::createNo()));
};
$sfSideConditional = static function (string $side, array $conditions, object $typeHolder): object {
	return $side === 'native'
		? new \PHPStanTurbo\ConditionalExpressionHolder($conditions, $typeHolder)
		: new \PHPStan\Analyser\ConditionalExpressionHolder($conditions, $typeHolder);
};
/** @param list<object> $holders */
$sfByKey = static function (array $holders): array {
	$result = [];
	foreach ($holders as $holder) {
		$result[$holder->getKey()] = $holder;
	}

	return $result;
};
$sfMergeG = new \PhpParser\Node\Expr\Variable('g');
$sfMergeT2 = new \PhpParser\Node\Expr\Variable('t2');
$sfMergeT3 = new \PhpParser\Node\Expr\Variable('t3');
$sfMergeT4 = new \PhpParser\Node\Expr\Variable('t4');
$sfMergeT5 = new \PhpParser\Node\Expr\Variable('t5');
$sfMergeD = new \PhpParser\Node\Expr\Variable('d');
// a class-constant fetch that resolves to its declared value:
// withoutPreciseClassConstantFetches() drops it from the differing keys, and
// the late-bound static:: one it keeps
$sfMergeConst = new \PhpParser\Node\Expr\ClassConstFetch(new \PhpParser\Node\Name('Holder'), new \PhpParser\Node\Identifier('SOME'));
$sfMergeStaticConst = new \PhpParser\Node\Expr\ClassConstFetch(new \PhpParser\Node\Name('static'), new \PhpParser\Node\Identifier('SOME'));
// Memcached::HAVE_JSON is a configured dynamic class constant, so it stays a differing key
$sfMergeDynamicConst = new \PhpParser\Node\Expr\ClassConstFetch(new \PhpParser\Node\Name\FullyQualified('Memcached'), new \PhpParser\Node\Identifier('HAVE_JSON'));
$sfMergeProp = new \PhpParser\Node\Expr\PropertyFetch(new \PhpParser\Node\Expr\Variable('t4'), 'name');
$sfMergeInit = new \PHPStan\Node\Expr\PropertyInitializationExpr('merged');
$sfMergeTablesA = static function (string $side) use ($sfHarness, $sfSideType, $sfSideHolder, $sfMergeG, $sfMergeT2, $sfMergeT3, $sfMergeT5, $sfMergeD, $sfMergeConst, $sfMergeStaticConst, $sfMergeDynamicConst, $sfMergeInit): array {
	$table = [
		$sfHarness->key($sfMergeG) => $sfSideHolder($side, $sfMergeG, $sfSideType($side, \PHPStan\Type\IntegerType::class)),
		// int against a string consequent: the intersection with the existing
		// type is not the consequent type
		$sfHarness->key($sfMergeT2) => $sfSideHolder($side, $sfMergeT2, $sfSideType($side, \PHPStan\Type\IntegerType::class), 'maybe'),
		$sfHarness->key($sfMergeT3) => $sfSideHolder($side, $sfMergeT3, $sfSideType($side, \PHPStan\Type\StringType::class)),
		$sfHarness->key($sfMergeT5) => $sfSideHolder($side, $sfMergeT5, $sfSideType($side, \PHPStan\Type\StringType::class)),
		$sfHarness->key($sfMergeD) => $sfSideHolder($side, $sfMergeD, $sfSideType($side, \PHPStan\Type\Constant\ConstantIntegerType::class, 1)),
		$sfHarness->key($sfMergeConst) => $sfSideHolder($side, $sfMergeConst, $sfSideType($side, \PHPStan\Type\Constant\ConstantIntegerType::class, 5)),
		$sfHarness->key($sfMergeStaticConst) => $sfSideHolder($side, $sfMergeStaticConst, $sfSideType($side, \PHPStan\Type\Constant\ConstantIntegerType::class, 5)),
		$sfHarness->key($sfMergeDynamicConst) => $sfSideHolder($side, $sfMergeDynamicConst, $sfSideType($side, \PHPStan\Type\Constant\ConstantIntegerType::class, 5)),
		$sfHarness->key($sfMergeInit) => $sfSideHolder($side, $sfMergeInit, $sfSideType($side, \PHPStan\Type\NullType::class), 'maybe'),
	];

	return [$table, $table, []];
};
$sfMergeConditionalsA = static function (string $side) use ($sfHarness, $sfSideType, $sfSideHolder, $sfSideConditional, $sfByKey, $sfMergeG, $sfMergeT2, $sfMergeT3, $sfMergeT4, $sfMergeT5, $sfMergeD): array {
	$guard = [$sfHarness->key($sfMergeG) => $sfSideHolder($side, $sfMergeG, $sfSideType($side, \PHPStan\Type\IntegerType::class))];

	return [
		// two matching holders of differing certainty: the batch intersects
		// their types and takes their extreme identity
		$sfHarness->key($sfMergeT2) => $sfByKey([
			$sfSideConditional($side, $guard, $sfSideHolder($side, $sfMergeT2, $sfSideType($side, \PHPStan\Type\StringType::class))),
			$sfSideConditional($side, $guard, $sfSideHolder($side, $sfMergeT2, $sfSideType($side, \PHPStan\Type\Constant\ConstantStringType::class, 'x'), 'maybe')),
		]),
		// a No consequent: the target is dropped from the scope
		$sfHarness->key($sfMergeT3) => $sfByKey([
			$sfSideConditional($side, $guard, $sfSideHolder($side, $sfMergeT3, $sfSideType($side, \PHPStan\Type\StringType::class), 'no')),
		]),
		// a target the scope does not track: the consequent holder is taken as is
		$sfHarness->key($sfMergeT4) => $sfByKey([
			$sfSideConditional($side, $guard, $sfSideHolder($side, $sfMergeT4, $sfSideType($side, \PHPStan\Type\StringType::class))),
		]),
		// a Yes-tracked target under a Maybe consequence: maxMin keeps Yes
		$sfHarness->key($sfMergeT5) => $sfByKey([
			$sfSideConditional($side, $guard, $sfSideHolder($side, $sfMergeT5, $sfSideType($side, \PHPStan\Type\StringType::class))),
			$sfSideConditional($side, $guard, $sfSideHolder($side, $sfMergeT5, $sfSideType($side, \PHPStan\Type\Constant\ConstantStringType::class, 'y'), 'maybe')),
		]),
		// the same guard set as the other scope's holder for the same target:
		// mergeSameGuardConditionalExpressions() unions the two consequents
		$sfHarness->key($sfMergeD) => $sfByKey([
			$sfSideConditional($side, $guard, $sfSideHolder($side, $sfMergeD, $sfSideType($side, \PHPStan\Type\Constant\ConstantIntegerType::class, 1))),
			// a guard set that merely contains the other scope's: the two must
			// match exactly to be merged
			$sfSideConditional($side, $guard + [$sfHarness->key($sfMergeT3) => $sfSideHolder($side, $sfMergeT3, $sfSideType($side, \PHPStan\Type\StringType::class))], $sfSideHolder($side, $sfMergeD, $sfSideType($side, \PHPStan\Type\Constant\ConstantIntegerType::class, 4))),
		]),
	];
};
$sfMergeTablesB = static function (string $side) use ($sfHarness, $sfSideType, $sfSideHolder, $sfMergeG, $sfMergeT2, $sfMergeD, $sfMergeInit): array {
	$table = [
		$sfHarness->key($sfMergeG) => $sfSideHolder($side, $sfMergeG, $sfSideType($side, \PHPStan\Type\StringType::class)),
		$sfHarness->key($sfMergeT2) => $sfSideHolder($side, $sfMergeT2, $sfSideType($side, \PHPStan\Type\StringType::class), 'maybe'),
		$sfHarness->key($sfMergeD) => $sfSideHolder($side, $sfMergeD, $sfSideType($side, \PHPStan\Type\Constant\ConstantIntegerType::class, 2)),
		$sfHarness->key($sfMergeInit) => $sfSideHolder($side, $sfMergeInit, $sfSideType($side, \PHPStan\Type\NullType::class)),
	];

	return [$table, $table, []];
};
$sfMergeConditionalsB = static function (string $side) use ($sfHarness, $sfSideType, $sfSideHolder, $sfSideConditional, $sfByKey, $sfMergeG, $sfMergeT2, $sfMergeT3, $sfMergeT4, $sfMergeD, $sfMergeProp): array {
	$ourGuard = [$sfHarness->key($sfMergeG) => $sfSideHolder($side, $sfMergeG, $sfSideType($side, \PHPStan\Type\StringType::class))];
	$theirGuard = [$sfHarness->key($sfMergeG) => $sfSideHolder($side, $sfMergeG, $sfSideType($side, \PHPStan\Type\IntegerType::class))];

	// a guard the other branch's state still allows: such a holder is rescued
	// only when the other branch already satisfies its consequent
	$possibleGuard = [$sfHarness->key($sfMergeG) => $sfSideHolder($side, $sfMergeG, $sfSideType($side, \PHPStan\Type\Constant\ConstantIntegerType::class, 3))];

	return [
		// a guard the other branch's flat state makes impossible:
		// preserveVacuousConditionalExpressions() rescues the holder
		$sfHarness->key($sfMergeT2) => $sfByKey([
			$sfSideConditional($side, $ourGuard, $sfSideHolder($side, $sfMergeT2, $sfSideType($side, \PHPStan\Type\IntegerType::class))),
			// the other branch tracks $t2 with a weaker certainty than this
			// consequent asks for: not rescued
			$sfSideConditional($side, $possibleGuard, $sfSideHolder($side, $sfMergeT2, $sfSideType($side, \PHPStan\Type\IntegerType::class))),
		]),
		// already satisfied in the other branch: rescued
		$sfHarness->key($sfMergeT3) => $sfByKey([
			$sfSideConditional($side, $possibleGuard, $sfSideHolder($side, $sfMergeT3, $sfSideType($side, \PHPStan\Type\StringType::class))),
			// an ErrorType consequent is a subtype of everything and would
			// always look satisfied: never rescued
			$sfSideConditional($side, $possibleGuard, $sfSideHolder($side, $sfMergeT3, $sfSideType($side, \PHPStan\Type\ErrorType::class))),
		]),
		// a No consequent survives the impossible-guard rescue only over a
		// plain variable
		$sfHarness->key($sfMergeT4) => $sfByKey([
			$sfSideConditional($side, $ourGuard, $sfSideHolder($side, $sfMergeT4, $sfSideType($side, \PHPStan\Type\StringType::class), 'no')),
			$sfSideConditional($side, $ourGuard, $sfSideHolder($side, $sfMergeProp, $sfSideType($side, \PHPStan\Type\StringType::class), 'no')),
		]),
		$sfHarness->key($sfMergeD) => $sfByKey([
			$sfSideConditional($side, $theirGuard, $sfSideHolder($side, $sfMergeD, $sfSideType($side, \PHPStan\Type\Constant\ConstantIntegerType::class, 2))),
			// the same guard set under a weaker certainty: not merged
			$sfSideConditional($side, $theirGuard, $sfSideHolder($side, $sfMergeD, $sfSideType($side, \PHPStan\Type\Constant\ConstantIntegerType::class, 6), 'maybe')),
		]),
	];
};
// a non-empty TemplateArgumentConstraints: addTemplateArgumentConstraints()
// answers with $this for an empty one, so only this makes mergeWith()'s
// pass-through of the other scope's constraints observable
$sfNonEmptyConstraints = (new \ReflectionClass(\PHPStan\Analyser\Generics\TemplateArgumentConstraints::class))->newInstanceWithoutConstructor();
(new \ReflectionProperty(\PHPStan\Analyser\Generics\TemplateArgumentConstraints::class, 'fact'))->setValue($sfNonEmptyConstraints, [new \stdClass(), null, null, false]);
$sfMergeScopeA = $sfScopeFactory->create(\PHPStan\Analyser\ScopeContext::create($sfFile));
$sfScopes[spl_object_id($sfMergeScopeA)] = [$sfMergeScopeA, [$sfMergeG, $sfMergeT2], null, $sfMergeTablesA, $sfMergeConditionalsA];
// afterExtractCall differs between the two: the merge ANDs them
$sfMergeScopeB = $sfScopeFactory->create(\PHPStan\Analyser\ScopeContext::create($sfFile))->afterExtractCall();
$sfScopes[spl_object_id($sfMergeScopeB)] = [$sfMergeScopeB, [$sfMergeG, $sfMergeT2], null, $sfMergeTablesB, $sfMergeConditionalsB];

// two more adjacent scopes for the generalization: every arm of
// generalizeType() needs both inputs in one side's classes, so their tables
// are built per side too. The second one's $other is the first, and both
// directions are probed.
$sfSideCombinator = static fn (string $side): string => $side === 'native' ? 'PHPStanTurbo\\TypeCombinator' : \PHPStan\Type\TypeCombinator::class;
$sfSideUnion = static fn (string $side, \PHPStan\Type\Type ...$types): \PHPStan\Type\Type => ($sfSideCombinator($side) . '::union')(...$types);
$sfSideIntersect = static fn (string $side, \PHPStan\Type\Type ...$types): \PHPStan\Type\Type => ($sfSideCombinator($side) . '::intersect')(...$types);
$sfSideRange = static fn (string $side, ?int $min, ?int $max): \PHPStan\Type\Type => ($side === 'native' ? 'PHPStanTurbo\\IntegerRangeType' : \PHPStan\Type\IntegerRangeType::class)::fromInterval($min, $max);
/** @param array<string, \PHPStan\Type\Type> $pairs */
$sfSideConstantArray = static function (string $side, array $pairs) use ($sfSideType): \PHPStan\Type\Type {
	$builder = ($side === 'native' ? 'PHPStanTurbo\\ConstantArrayTypeBuilder' : \PHPStan\Type\Constant\ConstantArrayTypeBuilder::class)::createEmpty();
	foreach ($pairs as $key => $value) {
		$builder->setOffsetValueType($sfSideType($side, \PHPStan\Type\Constant\ConstantStringType::class, $key), $value);
	}

	return $builder->getArray();
};
/** @param array<string, \PHPStan\Type\Type> $pairs */
$sfSideSealedArray = static function (string $side, array $pairs) use ($sfSideType): \PHPStan\Type\Type {
	$keyTypes = [];
	$valueTypes = [];
	foreach ($pairs as $key => $value) {
		$keyTypes[] = $sfSideType($side, \PHPStan\Type\Constant\ConstantStringType::class, $key);
		$valueTypes[] = $value;
	}
	$never = $sfSideType($side, \PHPStan\Type\NeverType::class, true);

	return new ($side === 'native' ? 'PHPStanTurbo\\ConstantArrayType' : \PHPStan\Type\Constant\ConstantArrayType::class)($keyTypes, $valueTypes, [0], [], null, [$never, $never]);
};
$sfGeneralizeVars = [];
foreach (['gInt', 'gIntGreater', 'gIntNeither', 'gIntBoth', 'gRange', 'gRangeSmaller', 'gRangeOpen', 'gString', 'gFloat', 'gBool', 'gScalarMix', 'gShape', 'gShapeKeys', 'gArray', 'gDeep', 'gList', 'gBenevolent', 'gAccessory', 'gSame', 'gRoot', 'gRefTarget', 'gRefAlias', 'gRefAssigned', 'gShapeSize', 'gSealed', 'gAccessoryArray', 'gListUnion'] as $sfGeneralizeName) {
	$sfGeneralizeVars[$sfGeneralizeName] = new \PhpParser\Node\Expr\Variable($sfGeneralizeName);
}
// a longer key over $gRoot: once $gRoot generalizes,
// ScopeOps::shouldInvalidateExpression() drops this one from the result
$sfGeneralizeDim = new \PhpParser\Node\Expr\ArrayDimFetch($sfGeneralizeVars['gRoot'], new \PhpParser\Node\Scalar\Int_(0));
// a reference created before the loop: generalizeWithVariableState() seeds the
// writable set with the intertwined variable and both aliased roots
$sfGeneralizeIntertwined = new \PHPStan\Node\Expr\IntertwinedVariableByReferenceWithExpr('gRefTarget', $sfGeneralizeVars['gRefAlias'], $sfGeneralizeVars['gRefAssigned']);
$sfGeneralizeTables = static function (bool $first) use ($sfHarness, $sfSideType, $sfSideHolder, $sfSideUnion, $sfSideIntersect, $sfSideRange, $sfSideConstantArray, $sfSideSealedArray, $sfGeneralizeVars, $sfGeneralizeDim, $sfGeneralizeIntertwined): callable {
	return static function (string $side) use ($first, $sfHarness, $sfSideType, $sfSideHolder, $sfSideUnion, $sfSideIntersect, $sfSideRange, $sfSideConstantArray, $sfSideSealedArray, $sfGeneralizeVars, $sfGeneralizeDim, $sfGeneralizeIntertwined): array {
		$int = $sfSideType($side, \PHPStan\Type\IntegerType::class);
		$string = $sfSideType($side, \PHPStan\Type\StringType::class);
		$constInt = static fn (int $value): \PHPStan\Type\Type => $sfSideType($side, \PHPStan\Type\Constant\ConstantIntegerType::class, $value);
		$constString = static fn (string $value): \PHPStan\Type\Type => $sfSideType($side, \PHPStan\Type\Constant\ConstantStringType::class, $value);
		$array = static fn (\PHPStan\Type\Type $key, \PHPStan\Type\Type $value): \PHPStan\Type\Type => $sfSideType($side, \PHPStan\Type\ArrayType::class, $key, $value);
		$types = $first
			? [
				// the constant-integer arm: a wider max, a lower min, neither, both
				'gInt' => $constInt(1),
				'gIntGreater' => $sfSideUnion($side, $constInt(1), $constInt(3)),
				'gIntNeither' => $sfSideUnion($side, $constInt(1), $constInt(9)),
				'gIntBoth' => $sfSideUnion($side, $constInt(2), $constInt(3)),
				// the integer-range arm
				'gRange' => $sfSideRange($side, 0, 10),
				'gRangeSmaller' => $sfSideRange($side, 0, 10),
				'gRangeOpen' => $sfSideRange($side, null, 10),
				// the constant scalar arms (generalize(moreSpecific()))
				'gString' => $constString('a'),
				'gFloat' => $sfSideType($side, \PHPStan\Type\Constant\ConstantFloatType::class, 1.0),
				'gBool' => $sfSideType($side, \PHPStan\Type\Constant\ConstantBooleanType::class, true),
				// one bucket empty on each side
				'gScalarMix' => $constString('a'),
				// the constant-array arms: the same key set (the builder path)
				// and a differing one (the sealed-shape path)
				'gShape' => $sfSideConstantArray($side, ['a' => $constInt(1), 'b' => $constInt(2)]),
				'gShapeKeys' => $sfSideConstantArray($side, ['a' => $constInt(1)]),
				// the general-array arm, its nesting-depth guard and its accessories
				'gArray' => $array($int, $string),
				'gDeep' => $array($int, $array($int, $array($int, $array($int, $string)))),
				'gRoot' => $array($int, $array($int, $array($int, $array($int, $string)))),
				'gRefTarget' => $array($int, $array($int, $array($int, $array($int, $string)))),
				'gRefAlias' => $array($int, $array($int, $array($int, $array($int, $string)))),
				'gRefAssigned' => $array($int, $array($int, $array($int, $array($int, $string)))),
				'gList' => $sfSideIntersect($side, $array($int, $int), $sfSideType($side, \PHPStan\Type\Accessory\AccessoryArrayListType::class), $sfSideType($side, \PHPStan\Type\Accessory\NonEmptyArrayType::class)),
				// a BenevolentUnion input is re-wrapped at the end
				'gBenevolent' => $sfSideType($side, \PHPStan\Type\BenevolentUnionType::class, [$int, $string]),
				// TypeUtils::getAccessoryTypes($a) is read off the FIRST argument
				'gAccessory' => $sfSideIntersect($side, $string, $sfSideType($side, \PHPStan\Type\Accessory\AccessoryNonEmptyStringType::class)),
				// equal on both sides: generalizeType() answers with $a
				// a shape whose size range does not cover the other's: the
				// literal-shape arm needs both the key types to match AND the
				// size comparison to hold
				'gShapeSize' => $sfSideConstantArray($side, ['a' => $constInt(1), 'b' => $constInt(2)]),
				// explicitly sealed shapes (the builder only seals under
				// bleeding edge): the literal key/value union arm
				'gSealed' => $sfSideSealedArray($side, ['a' => $constInt(1)]),
				// accessories are read off the FIRST argument only
				'gAccessoryArray' => $sfSideIntersect($side, $array($int, $string), $sfSideType($side, \PHPStan\Type\Accessory\NonEmptyArrayType::class)),
				'gListUnion' => $sfSideUnion($side, $sfSideIntersect($side, $array($int, $int), $sfSideType($side, \PHPStan\Type\Accessory\AccessoryArrayListType::class)), $sfSideIntersect($side, $array($int, $string), $sfSideType($side, \PHPStan\Type\Accessory\AccessoryArrayListType::class))),
				'gSame' => $int,
			]
			: [
				'gInt' => $constInt(5),
				'gIntGreater' => $constInt(5),
				'gIntNeither' => $constInt(5),
				'gIntBoth' => $sfSideUnion($side, $constInt(1), $constInt(9)),
				'gRange' => $sfSideRange($side, 0, 20),
				'gRangeSmaller' => $sfSideRange($side, -5, 10),
				'gRangeOpen' => $sfSideRange($side, 0, null),
				'gString' => $constString('b'),
				'gFloat' => $sfSideType($side, \PHPStan\Type\Constant\ConstantFloatType::class, 2.5),
				'gBool' => $sfSideType($side, \PHPStan\Type\Constant\ConstantBooleanType::class, false),
				'gScalarMix' => $constInt(1),
				'gShape' => $sfSideConstantArray($side, ['a' => $constString('x'), 'b' => $constInt(7)]),
				'gShapeKeys' => $sfSideConstantArray($side, ['a' => $constInt(1), 'b' => $constInt(2)]),
				'gArray' => $array($int, $int),
				'gDeep' => $array($int, $array($int, $int)),
				'gRoot' => $array($int, $array($int, $int)),
				'gRefTarget' => $array($int, $array($int, $int)),
				'gRefAlias' => $array($int, $array($int, $int)),
				'gRefAssigned' => $array($int, $array($int, $int)),
				'gList' => $sfSideIntersect($side, $array($int, $string), $sfSideType($side, \PHPStan\Type\Accessory\AccessoryArrayListType::class), $sfSideType($side, \PHPStan\Type\Accessory\NonEmptyArrayType::class)),
				'gBenevolent' => $string,
				'gAccessory' => $constString('x'),
				'gShapeSize' => (static function () use ($side, $sfSideType, $constInt): \PHPStan\Type\Type {
					$builder = ($side === 'native' ? 'PHPStanTurbo\\ConstantArrayTypeBuilder' : \PHPStan\Type\Constant\ConstantArrayTypeBuilder::class)::createEmpty();
					$builder->setOffsetValueType($sfSideType($side, \PHPStan\Type\Constant\ConstantStringType::class, 'a'), $constInt(1));
					$builder->setOffsetValueType($sfSideType($side, \PHPStan\Type\Constant\ConstantStringType::class, 'b'), $constInt(2), true);

					return $builder->getArray();
				})(),
				'gSealed' => $sfSideSealedArray($side, ['a' => $constInt(1), 'b' => $constInt(2)]),
				'gAccessoryArray' => $array($int, $int),
				'gListUnion' => $sfSideIntersect($side, $array($int, $sfSideType($side, \PHPStan\Type\FloatType::class)), $sfSideType($side, \PHPStan\Type\Accessory\AccessoryArrayListType::class)),
				'gSame' => $int,
			];
		$table = [];
		foreach ($types as $name => $type) {
			$table[$sfHarness->key($sfGeneralizeVars[$name])] = $sfSideHolder($side, $sfGeneralizeVars[$name], $type);
		}
		$table[$sfHarness->key($sfGeneralizeDim)] = $sfSideHolder($side, $sfGeneralizeDim, $first ? $string : $int);
		$table[$sfHarness->key($sfGeneralizeIntertwined)] = $sfSideHolder($side, $sfGeneralizeIntertwined, $first ? $string : $int);

		return [$table, $table, []];
	};
};
$sfGeneralizeScopeA = $sfScopeFactory->create(\PHPStan\Analyser\ScopeContext::create($sfFile));
$sfScopes[spl_object_id($sfGeneralizeScopeA)] = [$sfGeneralizeScopeA, [$sfGeneralizeVars['gInt'], $sfGeneralizeVars['gShape']], null, $sfGeneralizeTables(true)];
$sfGeneralizeScopeB = $sfScopeFactory->create(\PHPStan\Analyser\ScopeContext::create($sfFile));
$sfScopes[spl_object_id($sfGeneralizeScopeB)] = [$sfGeneralizeScopeB, [$sfGeneralizeVars['gInt'], $sfGeneralizeVars['gShape']], null, $sfGeneralizeTables(false)];
// the same tables as the first one again: generalizeType() is not symmetric
// (the size comparison, the open range bounds and the accessory types are all
// read off the FIRST argument), and only the receiving scope's factory is the
// one in builder mode - `$other->generalizeWith($scope)` would answer with the
// previous iteration's canned scope. This third scope's $other is the second,
// so the A-against-B direction runs on a receiver that can derive.
$sfGeneralizeScopeC = $sfScopeFactory->create(\PHPStan\Analyser\ScopeContext::create($sfFile));
$sfScopes[spl_object_id($sfGeneralizeScopeC)] = [$sfGeneralizeScopeC, [$sfGeneralizeVars['gInt'], $sfGeneralizeVars['gShape']], null, $sfGeneralizeTables(true)];

// ---- rebuild each scope on both sides and compare method by method ----
$sfObservations = ['php' => [], 'native' => []];
$sfProbeVariables = ['argc', 'argv', 'undefinedVariable', '_GET', 'this', 'GLOBALS'];
$sfProbeConstants = [
	new \PhpParser\Node\Name('PHP_VERSION'),
	new \PhpParser\Node\Name\FullyQualified('PHP_EOL'),
	new \PhpParser\Node\Name('ANSWER'),
	new \PhpParser\Node\Name('DEFINITELY_NOT_DEFINED_XYZ'),
	new \PhpParser\Node\Name('__COMPILER_HALT_OFFSET__'),
];
$sfSampleCount = 0;
$sfBarrierHits = 0;
// a protected method (findSettledStoredResult) called from a closure bound
// to the scope's own class
$sfProtected = static fn (object $scope, string $method, mixed ...$a): mixed => \Closure::bind(fn () => $this->$method(...$a), $scope, get_class($scope))();
// a property of any scope object, whichever class in its hierarchy declares it
// (the native class declares the twin's properties with the twin's visibility,
// so neither side is readable from one bound Closure scope)
$sfProp = static function (object $scope, string $name): mixed {
	$class = new \ReflectionClass($scope);
	while (!$class->hasProperty($name)) {
		$class = $class->getParentClass();
	}

	return $class->getProperty($name)->getValue($scope);
};
// the mutable state of the scope the RecordingScopeFactory answers with: the
// chaining bodies specify types in place on it, so each producer's run is
// undone before the next one (and before the other side's pass)
$sfMutableProps = ['expressionTypes', 'nativeExpressionTypes', 'conditionalExpressions', 'resolvedTypes'];
$sfSnapshot = static function (object $scope, ?array $props = null) use ($sfProp, $sfMutableProps): array {
	$state = [];
	foreach ($props ?? $sfMutableProps as $name) {
		$state[$name] = $sfProp($scope, $name);
	}

	return $state;
};
$sfRestore = static function (object $scope, array $state): void {
	$class = new \ReflectionClass($scope);
	foreach ($state as $name => $value) {
		$declaring = $class;
		while (!$declaring->hasProperty($name)) {
			$declaring = $declaring->getParentClass();
		}
		$declaring->getProperty($name)->setValue($scope, $value);
	}
};
// the observable state of a scope a chaining producer answered: the scopes
// ScopeOps::scopeWith() clones never reach the factory, so their argument
// lists are no observable — and a raw scope normalizes to its object id
$sfScopeDigest = static function (mixed $result, object $scope, object $dummy) use ($sfHarness, $sfProp): mixed {
	if (!$result instanceof \PHPStan\Analyser\MutatingScope && !$result instanceof \PHPStanTurbo\MutatingScope) {
		return $sfHarness->norm($result);
	}
	$parentScope = $sfProp($result, 'parentScope');

	return [
		'result' => $result === $scope ? 'this' : ($result === $dummy ? 'factory result' : 'derived'),
		'class' => $sfHarness->className($result),
		'expressionTypes' => $sfHarness->norm($sfProp($result, 'expressionTypes')),
		'nativeExpressionTypes' => $sfHarness->norm($sfProp($result, 'nativeExpressionTypes')),
		'conditionalExpressions' => array_map(static fn (array $holders): array => array_keys($holders), $sfProp($result, 'conditionalExpressions')),
		'inClosureBindScopeClasses' => $sfProp($result, 'inClosureBindScopeClasses'),
		'anonymousFunctionReflection' => $sfHarness->norm($sfProp($result, 'anonymousFunctionReflection')),
		'inFirstLevelStatement' => $sfProp($result, 'inFirstLevelStatement'),
		'currentlyAssignedExpressions' => $sfProp($result, 'currentlyAssignedExpressions'),
		'currentlyAllowedUndefinedExpressions' => $sfProp($result, 'currentlyAllowedUndefinedExpressions'),
		'inFunctionCallsStack' => $sfHarness->norm($sfProp($result, 'inFunctionCallsStack')),
		'afterExtractCall' => $sfProp($result, 'afterExtractCall'),
		'parentScope' => $parentScope === null ? null : $sfHarness->className($parentScope),
		'nativeTypesPromoted' => $sfProp($result, 'nativeTypesPromoted'),
		'namespace' => $sfProp($result, 'namespace'),
		'templateArgumentConstraints' => $sfHarness->norm($sfProp($result, 'templateArgumentConstraints')),
		'resolvedTypes' => array_keys($sfProp($result, 'resolvedTypes')),
	];
};
$sfProbeNames = [
	new \PhpParser\Node\Name('self'),
	new \PhpParser\Node\Name('static'),
	new \PhpParser\Node\Name('parent'),
	new \PhpParser\Node\Name('Holder'),
	new \PhpParser\Node\Name\FullyQualified('ScopeFamilyFixture\\Holder'),
	new \PhpParser\Node\Name('Nope\\Missing'),
];
$sfProbeValues = [1, 'a', 1.5, true, null, [1, 'a' => 2]];
// the in-function-call stack entries and the classes of the enter* family
// (built once: both sides push the same objects)
$sfPushReflection = $sfReflectionProvider->getFunction(new \PhpParser\Node\Name('strlen'), null);
$sfPushParameter = new \PHPStan\Reflection\Native\NativeParameterReflection('p', false, new \PHPStan\Type\StringType(), \PHPStan\Reflection\PassedByReference::createNo(), false, null);
$sfHolderReflection = $sfReflectionProvider->getClass(\ScopeFamilyFixture\Holder::class);
$sfCustomReflection = $sfReflectionProvider->getClass(\ScopeFamilyFixture\Custom::class);
$sfTraitReflection = $sfReflectionProvider->getClass(\ScopeFamilyFixture\HelperTrait::class);
$sfBaseReflection = $sfReflectionProvider->getClass(\ScopeFamilyFixture\Base::class);
$sfChildReflection = $sfReflectionProvider->getClass(\ScopeFamilyFixture\Child::class);
// the members of the visibility queries: a public and a private one
// of the class the fixture's scopes live in, and the protected / private /
// asymmetrically-writable ones of a small hierarchy
$sfPublicProperty = $sfHolderReflection->getNativeProperty('name');
$sfPrivateProperty = $sfHolderReflection->getNativeProperty('inner');
$sfProtectedProperty = $sfBaseReflection->getNativeProperty('protectedCounter');
$sfPrivateSetProperty = $sfBaseReflection->getNativeProperty('tag');
$sfPublicMethod = $sfHolderReflection->getNativeMethod('read');
$sfProtectedMethod = $sfBaseReflection->getNativeMethod('protectedMethod');
$sfChildProtectedMethod = $sfChildReflection->getNativeMethod('protectedMethod');
$sfChildProtectedProperty = $sfChildReflection->getNativeProperty('childCounter');
$sfChildProtectedConstant = $sfChildReflection->getConstant('CHILD_PROTECTED_CONST');
$sfPrivateMethod = $sfBaseReflection->getNativeMethod('privateMethod');
$sfPublicConstant = $sfBaseReflection->getConstant('PUBLIC_CONST');
$sfProtectedConstant = $sfBaseReflection->getConstant('PROTECTED_CONST');
$sfPrivateConstant = $sfBaseReflection->getConstant('PRIVATE_CONST');
$sfProbeClassNames = ['ScopeFamilyFixture\\Holder', '\\ScopeFamilyFixture\\Holder', 'Nope\\Missing', 'X', 'Nope'];
$sfProbeFunctionNames = ['strlen', '\\strlen', 'nope_missing', '\\nope_missing'];
// the function-like nodes of the enter* family
$sfEmptyTemplateTypeMap = \PHPStan\Type\Generic\TemplateTypeMap::createEmpty();
$sfProbeMethodParams = [
	// the attribute argument is resolved through InitializerExprContext, whose
	// class name getParameterAttributes() fills in
	new \PhpParser\Node\Param(new \PhpParser\Node\Expr\Variable('a'), null, new \PhpParser\Node\Identifier('int'), false, false, [], 0, [
		new \PhpParser\Node\AttributeGroup([new \PhpParser\Node\Attribute(new \PhpParser\Node\Name\FullyQualified('Attribute'), [new \PhpParser\Node\Arg(new \PhpParser\Node\Scalar\MagicConst\Class_())])]),
	]),
	new \PhpParser\Node\Param(new \PhpParser\Node\Expr\Variable('b'), new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('null')), new \PhpParser\Node\Identifier('string')),
	new \PhpParser\Node\Param(new \PhpParser\Node\Expr\Variable('c'), new \PhpParser\Node\Scalar\Int_(3), new \PhpParser\Node\Name('static')),
	// no native type: a conditional @param type survives TypehintHelper::decideType() only here
	new \PhpParser\Node\Param(new \PhpParser\Node\Expr\Variable('d')),
	new \PhpParser\Node\Param(new \PhpParser\Node\Expr\Variable('e')),
];
$sfProbeVariadicParams = array_merge($sfProbeMethodParams, [
	new \PhpParser\Node\Param(new \PhpParser\Node\Expr\Variable('rest'), null, new \PhpParser\Node\Identifier('string'), false, true),
]);
$sfProbeClassMethod = new \PhpParser\Node\Stmt\ClassMethod(new \PhpParser\Node\Identifier('doIt'), ['params' => $sfProbeMethodParams, 'returnType' => new \PhpParser\Node\Name('static'), 'stmts' => []]);
$sfProbeVariadicClassMethod = new \PhpParser\Node\Stmt\ClassMethod(new \PhpParser\Node\Identifier('doItVariadic'), ['params' => $sfProbeVariadicParams, 'returnType' => new \PhpParser\Node\Identifier('string'), 'stmts' => []]);
$sfProbeStaticClassMethod = new \PhpParser\Node\Stmt\ClassMethod(new \PhpParser\Node\Identifier('doItStatic'), ['flags' => \PhpParser\Modifiers::STATIC, 'params' => $sfProbeMethodParams, 'returnType' => new \PhpParser\Node\Identifier('void'), 'stmts' => []]);
$sfProbeFunction = new \PhpParser\Node\Stmt\Function_(new \PhpParser\Node\Identifier('doItFn'), ['params' => $sfProbeMethodParams, 'stmts' => []]);
// the anonymous- and arrow-function nodes: a use list mixing a
// by-value capture of a variable the scope knows, one it does not, and a
// by-ref capture (each arm of enterAnonymousFunctionWithoutReflection())
$sfProbeClosureUses = [
	new \PhpParser\Node\ClosureUse(new \PhpParser\Node\Expr\Variable('p')),
	new \PhpParser\Node\ClosureUse(new \PhpParser\Node\Expr\Variable('neverDefinedUse')),
	new \PhpParser\Node\ClosureUse(new \PhpParser\Node\Expr\Variable('byRefUse'), true),
];
$sfProbeClosureParams = [
	new \PhpParser\Node\Param(new \PhpParser\Node\Expr\Variable('a'), null, new \PhpParser\Node\Identifier('int')),
	new \PhpParser\Node\Param(new \PhpParser\Node\Expr\Variable('b'), new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('null')), new \PhpParser\Node\Identifier('string')),
];
$sfProbeClosure = new \PhpParser\Node\Expr\Closure(['params' => $sfProbeClosureParams, 'uses' => $sfProbeClosureUses, 'stmts' => []]);
$sfProbeStaticClosure = new \PhpParser\Node\Expr\Closure(['static' => true, 'params' => $sfProbeClosureParams, 'uses' => $sfProbeClosureUses, 'stmts' => []]);
// untyped parameters: getFunctionType() answers a PHP mixed on both sides, so
// intersectButNotNever() stays on one side's classes and the callable-parameter
// arms are comparable under the prefix
$sfProbeUntypedParams = [
	new \PhpParser\Node\Param(new \PhpParser\Node\Expr\Variable('u')),
	new \PhpParser\Node\Param(new \PhpParser\Node\Expr\Variable('v')),
];
$sfProbeUntypedClosure = new \PhpParser\Node\Expr\Closure(['params' => $sfProbeUntypedParams, 'uses' => [], 'stmts' => []]);
$sfProbeUntypedVariadicClosure = new \PhpParser\Node\Expr\Closure(['params' => array_merge($sfProbeUntypedParams, [
	new \PhpParser\Node\Param(new \PhpParser\Node\Expr\Variable('w'), null, null, false, true),
]), 'uses' => [], 'stmts' => []]);
$sfProbeArrowFunction = new \PhpParser\Node\Expr\ArrowFunction(['params' => $sfProbeClosureParams, 'expr' => new \PhpParser\Node\Expr\Variable('a')]);
$sfProbeStaticArrowFunction = new \PhpParser\Node\Expr\ArrowFunction(['static' => true, 'params' => $sfProbeClosureParams, 'expr' => new \PhpParser\Node\Expr\Variable('a')]);
$sfProbeVariadicArrowFunction = new \PhpParser\Node\Expr\ArrowFunction(['params' => array_merge($sfProbeClosureParams, [
	new \PhpParser\Node\Param(new \PhpParser\Node\Expr\Variable('rest'), null, new \PhpParser\Node\Identifier('string'), false, true),
]), 'expr' => new \PhpParser\Node\Expr\Variable('a')]);
$sfPushVariadicParameter = new \PHPStan\Reflection\Native\NativeParameterReflection('rest', true, new \PHPStan\Type\IntegerType(), \PHPStan\Reflection\PassedByReference::createNo(), true, null);
$sfProbeGetHook = new \PhpParser\Node\PropertyHook(new \PhpParser\Node\Identifier('get'), null, ['params' => []]);
$sfProbeSetHook = new \PhpParser\Node\PropertyHook(new \PhpParser\Node\Identifier('set'), null, ['params' => []]);
$sfProbeSetHookWithParam = new \PhpParser\Node\PropertyHook(new \PhpParser\Node\Identifier('set'), null, ['params' => [new \PhpParser\Node\Param(new \PhpParser\Node\Expr\Variable('v'), null, new \PhpParser\Node\Identifier('string'))]]);
$sfProbeUnknownHook = new \PhpParser\Node\PropertyHook(new \PhpParser\Node\Identifier('nope'), null, ['params' => []]);
// the parameters and type nodes of the function-like family
$sfProbeParams = [
	new \PhpParser\Node\Param(new \PhpParser\Node\Expr\Variable('x')),
	new \PhpParser\Node\Param(new \PhpParser\Node\Expr\Variable('x'), new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('null')), new \PhpParser\Node\Identifier('int')),
	new \PhpParser\Node\Param(new \PhpParser\Node\Expr\Variable('x'), new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('NULL')), new \PhpParser\Node\Identifier('string')),
	new \PhpParser\Node\Param(new \PhpParser\Node\Expr\Variable('x'), new \PhpParser\Node\Scalar\Int_(1), new \PhpParser\Node\Identifier('int')),
	new \PhpParser\Node\Param(new \PhpParser\Node\Expr\Variable('x'), null, new \PhpParser\Node\Identifier('string'), false, true),
];
$sfProbeTypeNodes = [
	null,
	new \PhpParser\Node\Identifier('int'),
	new \PhpParser\Node\Identifier('static'),
	new \PhpParser\Node\Name('self'),
	new \PhpParser\Node\Name('static'),
	new \PhpParser\Node\Name('parent'),
	new \PhpParser\Node\Name('Holder'),
	new \PhpParser\Node\Name\FullyQualified('ScopeFamilyFixture\\Holder'),
	new \PhpParser\Node\NullableType(new \PhpParser\Node\Identifier('int')),
];
/** @var array<string, MutatingScope|null> the previous scope of each side, the $other of the two-scope methods */
$sfPrevious = ['php' => null, 'native' => null];
foreach ($sfScopes as $sfId => [$sfWalkScope, $sfExprs, $sfStorage]) {
	$sfArgs = $sfHarness->constructorArgs($sfWalkScope);
	$sfPerSideTables = $sfScopes[$sfId][3] ?? null;
	$sfPerSideConditionals = $sfScopes[$sfId][4] ?? null;
	$sfSampleCount++;
	foreach (['php', 'native'] as $side) {
		$observe = static function (string $label, callable $fn) use (&$sfObservations, $side, $sfHarness, $sfId): void {
			try {
				$sfObservations[$side][$sfId][$label] = $sfHarness->norm($fn());
			} catch (\Throwable $e) {
				$sfObservations[$side][$sfId][$label] = $sfHarness->norm($e);
			}
		};

		$dummy = $sfWalkScope;
		$factory = new RecordingScopeFactory($dummy);
		$args = $sfArgs;
		$args['scopeFactory'] = $factory;
		if ($sfPerSideTables !== null) {
			[$args['expressionTypes'], $args['nativeExpressionTypes'], $args['inFunctionCallsStack']] = $sfPerSideTables($side);
		} elseif ($side === 'native') {
			$args['expressionTypes'] = $sfHarness->nativeHolders($args['expressionTypes']);
			$args['nativeExpressionTypes'] = $sfHarness->nativeHolders($args['nativeExpressionTypes']);
		}
		if ($sfPerSideConditionals !== null) {
			$args['conditionalExpressions'] = $sfPerSideConditionals($side);
		} elseif ($side === 'native') {
			$args['conditionalExpressions'] = $sfHarness->nativeConditionalExpressions($args['conditionalExpressions']);
		}
		if ($side === 'native') {
			$scope = new NativeScope(...array_values($args));
			$scope->twin = $sfWalkScope;
		} else {
			$scope = new PhpScope(...array_values($args));
			$scope->inner = $sfWalkScope;
		}
		$other = $sfPrevious[$side] ?? $scope;
		$sfPrevious[$side] = $scope;
		$sfHarness->currentScope = $scope;
		$sfShared = [];
		foreach ([$args['expressionTypes'], $args['nativeExpressionTypes']] as $sfTable) {
			foreach ($sfTable as $sfHolder) {
				$sfShared[spl_object_id($sfHolder->getExpr())] = true;
			}
		}
		$sfHarness->sharedExprIds = $sfShared;

		// the plain getters
		foreach ([
			'getFile', 'getFileDescription', 'isDeclareStrictTypes', 'isInClass', 'isInTrait', 'getClassReflection', 'getTraitReflection',
			'getFunction', 'getFunctionName', 'getNamespace', 'getParentScope', 'canAnyVariableExist', 'isInAnonymousFunction',
			'getAnonymousFunctionReflection', 'getAnonymousFunctionReturnType', 'isInFirstLevelStatement', 'getDefinedVariables',
			'getMaybeDefinedVariables', 'getExprPrinter', 'getCurrentTemplateArgumentFrame', 'getTemplateArgumentConstraints',
			'getCurrentExpressionResultStorage', 'getFunctionCallStack', 'getFunctionCallStackWithParameters', 'isInClosureBind',
		] as $method) {
			$observe($method, static fn () => $scope->$method());
		}
		$observe('toWalkScope identity', static fn () => $scope->parentToWalkScope() === $scope);
		$observe('toWalkScope delegation', static fn () => $scope->toWalkScope() === $sfWalkScope);
		$observe('toMutatingScope identity', static fn () => $scope->toMutatingScope() === $scope);

		// variables
		$variables = array_unique(array_merge($scope->getDefinedVariables(), $scope->getMaybeDefinedVariables(), $sfProbeVariables));
		foreach ($variables as $variable) {
			$observe("hasVariableType($variable)", static fn () => $scope->hasVariableType($variable));
			$observe("getVariableType($variable)", static fn () => $scope->getVariableType($variable));
		}

		// expressions the walk asked about here, and the tracked ones
		$exprs = $sfExprs;
		foreach (array_slice($args['expressionTypes'], 0, 12) as $holder) {
			$exprs[] = $holder->getExpr();
		}
		$exprs[] = new \PhpParser\Node\Expr\MethodCall(new \PhpParser\Node\Expr\Variable('this'), 'read');
		$exprs[] = new \PhpParser\Node\Expr\PropertyFetch(new \PhpParser\Node\Expr\Variable('this'), 'name');
		$exprs[] = new \PhpParser\Node\Expr\PropertyFetch(new \PhpParser\Node\Expr\PropertyFetch(new \PhpParser\Node\Expr\Variable('this'), 'inner'), 'name');
		$exprs[] = new \PhpParser\Node\Expr\PropertyFetch(new \PhpParser\Node\Expr\Variable('other'), 'name');
		$exprs[] = new \PhpParser\Node\Expr\NullsafePropertyFetch(new \PhpParser\Node\Expr\Variable('this'), 'name');
		$exprs[] = new \PhpParser\Node\Expr\NullsafeMethodCall(new \PhpParser\Node\Expr\Variable('this'), 'read');
		$exprs[] = new \PhpParser\Node\Expr\ArrayDimFetch(new \PhpParser\Node\Expr\Variable('items'), new \PhpParser\Node\Scalar\Int_(0));
		// untracked, over the per-side tables' differing $arr entry: the flavour
		// getStateType() picks follows the scope's nativeTypesPromoted
		$exprs[] = new \PhpParser\Node\Expr\ArrayDimFetch(new \PhpParser\Node\Expr\Variable('arr'), new \PhpParser\Node\Expr\Variable('i'));
		$exprs[] = new \PhpParser\Node\Expr\StaticPropertyFetch(new \PhpParser\Node\Name('self'), 'nope');
		$exprs[] = new \PhpParser\Node\Expr\ClassConstFetch(new \PhpParser\Node\Name('Holder'), 'class');
		$exprs[] = new \PhpParser\Node\Scalar\String_('literal');
		$exprs[] = new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('PHP_EOL'));
		$exprs[] = new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('strlen'), [new \PhpParser\Node\Arg(new \PhpParser\Node\Scalar\String_('x'))]);
		$exprs[] = new \PhpParser\Node\Expr\Match_(new \PhpParser\Node\Expr\Variable('x'), [new \PhpParser\Node\MatchArm(null, new \PhpParser\Node\Scalar\Int_(1))]);
		$exprs[] = new \PhpParser\Node\Expr\Closure(['params' => [], 'stmts' => []]);

		// ---- the type resolution core, with the walk's storage
		// (or a fresh one for a hand-built scope) as the analysis in progress
		$stack = $args['expressionResultStorageStack'];
		$stack->push($sfStorage ?? new \PHPStan\Analyser\ExpressionResultStorage());
		try {
			foreach ($exprs as $i => $expr) {
				$key = $sfHarness->key($expr);
				$observe("getNodeKey#$i $key", static fn () => $scope->getNodeKey($expr));
				$observe("hasExpressionType#$i $key", static fn () => $scope->hasExpressionType($expr));
				if ($scope->hasExpressionType($expr)->yes()) {
					$observe("getTrackedExpressionType#$i $key", static fn () => $scope->getTrackedExpressionType($expr));
				}
				$observe("findPossiblyImpureCallDescriptions#$i $key", static fn () => $scope->findPossiblyImpureCallDescriptions($expr));
				if ($expr instanceof \PhpParser\Node\Expr\PropertyFetch) {
					$observe("isReadonlyPropertyFetch(this)#$i $key", static fn () => $scope->isReadonlyPropertyFetch($expr, true));
					$observe("isReadonlyPropertyFetch(any)#$i $key", static fn () => $scope->isReadonlyPropertyFetch($expr, false));
				}
				$observe("getType#$i $key", static fn () => $scope->getType($expr));
				$observe("getType again#$i $key", static fn () => $scope->getType($expr));
				$observe("getScopeType#$i $key", static fn () => $scope->getScopeType($expr));
				$observe("getNativeType#$i $key", static fn () => $scope->getNativeType($expr));
				$observe("getScopeNativeType#$i $key", static fn () => $scope->getScopeNativeType($expr));
				$observe("getKeepVoidType#$i $key", static fn () => $scope->getKeepVoidType($expr));
				$observe("obtainResultForNode#$i $key", static fn () => $scope->obtainResultForNode($expr));
				$observe("findSettledStoredResult#$i $key", static fn () => $sfProtected($scope, 'findSettledStoredResult', $expr));
				$observe("specifyTypesOfNewWorldHandlerNode(truthy)#$i $key", static fn () => $scope->specifyTypesOfNewWorldHandlerNode($expr, \PHPStan\Analyser\TypeSpecifierContext::createTruthy()));
				$observe("specifyTypesOfNewWorldHandlerNode(falsey)#$i $key", static fn () => $scope->specifyTypesOfNewWorldHandlerNode($expr, \PHPStan\Analyser\TypeSpecifierContext::createFalsey()));
				// ---- the state readers and the currently-* queries
				$observe("getStateType#$i $key", static fn () => $scope->getStateType($expr));
				$observe("isInExpressionAssign#$i $key", static fn () => $scope->isInExpressionAssign($expr));
				$observe("isInWriteExpressionAssign#$i $key", static fn () => $scope->isInWriteExpressionAssign($expr));
				$observe("isUndefinedExpressionAllowed#$i $key", static fn () => $scope->isUndefinedExpressionAllowed($expr));
			}

			// the getType() memo (a public property of the twin)
			$observe('resolvedTypes keys', static fn () => array_keys($scope->resolvedTypes));

			// the guard diagnostics: a real, unprocessed node
			foreach ([$exprs[0], end($exprs)] as $i => $guardExpr) {
				$observe("getType under guard#$i", static function () use ($scope, $guardExpr) {
					\ScopeFamily\setGuards(true, [spl_object_id($guardExpr) => true], []);
					try {
						return $scope->getType($guardExpr);
					} finally {
						\ScopeFamily\setGuards(false, [], []);
					}
				});
				$observe("obtainResultForNode under guard#$i", static function () use ($scope, $guardExpr) {
					\ScopeFamily\setGuards(true, [spl_object_id($guardExpr) => true], []);
					try {
						return $scope->obtainResultForNode($guardExpr);
					} finally {
						\ScopeFamily\setGuards(false, [], []);
					}
				});
			}
			$observe('getType under guard, processed', static function () use ($scope, $exprs) {
				$guardExpr = $exprs[0];
				\ScopeFamily\setGuards(true, [spl_object_id($guardExpr) => true], [spl_object_id($guardExpr) => true]);
				try {
					return $scope->getType($guardExpr);
				} finally {
					\ScopeFamily\setGuards(false, [], []);
				}
			});

			// the closure cache key
			$definedRoots = array_map(static fn (string $v): string => '$' . $v, array_slice($scope->getDefinedVariables(), 0, 2));
			$observe('getClosureScopeCacheKey()', static fn () => $scope->getClosureScopeCacheKey());
			$observe('getClosureScopeCacheKey([])', static fn () => $scope->getClosureScopeCacheKey([]));
			$observe('getClosureScopeCacheKey([$this])', static fn () => $scope->getClosureScopeCacheKey(['$this']));
			$observe('getClosureScopeCacheKey(defined)', static fn () => $scope->getClosureScopeCacheKey($definedRoots));
			$observe('getClosureScopeCacheKey(prefix)', static fn () => $scope->getClosureScopeCacheKey(['$t', '$o', '$']));

			// names and values
			foreach ($sfProbeNames as $i => $name) {
				$observe("resolveName#$i " . $name->toString(), static fn () => $scope->resolveName($name));
				$observe("resolveTypeByName#$i " . $name->toString(), static fn () => $scope->resolveTypeByName($name));
			}
			foreach ($sfProbeValues as $i => $value) {
				$observe("getTypeFromValue#$i", static fn () => $scope->getTypeFromValue($value));
			}

			// ---- the in-function-call stack and the enter* family
			foreach ($sfProbeClassNames as $i => $className) {
				$observe("isInClassExists#$i $className", static fn () => $scope->isInClassExists($className));
			}
			foreach ($sfProbeFunctionNames as $i => $functionName) {
				$observe("isInFunctionExists#$i $functionName", static fn () => $scope->isInFunctionExists($functionName));
			}
			$observe('getPhpVersion', static fn () => $scope->getPhpVersion());
			foreach ($sfProbeParams as $i => $parameter) {
				$observe("isParameterValueNullable#$i", static fn () => $scope->isParameterValueNullable($parameter));
			}
			// the variadic shapes only where PHP_VERSION_ID is tracked (see the
			// scopes built for it above)
			// ... and only where getPhpVersion() answers with that tracked type
			// itself (the overall-range scope falls back to a native type, which
			// the PHP PhpVersions does not recognize)
			$sfPhpVersionIdFetch = new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('PHP_VERSION_ID'));
			$sfTracksPhpVersionId = $scope->hasExpressionType($sfPhpVersionIdFetch)->yes()
				&& $scope->getPhpVersion()->getType() === $scope->getType($sfPhpVersionIdFetch);
			foreach ($sfProbeTypeNodes as $i => $typeNode) {
				foreach ([[false, false], [true, false], [false, true], [true, true]] as $j => [$nullable, $variadic]) {
					if ($variadic && !$sfTracksPhpVersionId) {
						continue;
					}
					// an implicit mixed item type is the twin's `list` and the native
					// IntersectionType's `list<mixed>` under the prefix: its
					// `$valueType instanceof MixedType` test sees the PHP MixedType the
					// PHP InitializerExprTypeResolver built (one class under a real name)
					if ($variadic && $scope->getFunctionType($typeNode, $nullable, false)->describe(\PHPStan\Type\VerbosityLevel::precise()) === 'mixed') {
						continue;
					}
					$observe("getFunctionType#$i/$j", static fn () => $scope->getFunctionType($typeNode, $nullable, $variadic));
				}
			}
			// $functionScope->resolvedTypes = $this->resolvedTypes, on the
			// factory's result (restored: the factory answers with the walk scope)
			$observe('in-function-call memo', static function () use ($scope, $dummy) {
				$saved = $dummy->resolvedTypes;
				try {
					$dummy->resolvedTypes = [];
					$scope->pushInFunctionCall(null, null, true);
					$remembered = array_keys($dummy->resolvedTypes);
					$dummy->resolvedTypes = [];
					$scope->pushInFunctionCall(null, null, false);
					$forgotten = array_keys($dummy->resolvedTypes);
					$dummy->resolvedTypes = [];
					$scope->popInFunctionCall();
					$popped = array_keys($dummy->resolvedTypes);

					return [$remembered, $forgotten, $popped];
				} finally {
					$dummy->resolvedTypes = $saved;
				}
			});

			// the storage stack
			$extraStorage = new \PHPStan\Analyser\ExpressionResultStorage();
			$observe('push/pop ExpressionResultStorage', static function () use ($scope, $extraStorage) {
				$before = $scope->getCurrentExpressionResultStorage();
				$scope->pushExpressionResultStorage($extraStorage);
				$pushed = $scope->getCurrentExpressionResultStorage() === $extraStorage;
				$scope->popExpressionResultStorage();
				return [$pushed, $scope->getCurrentExpressionResultStorage() === $before];
			});

			// the two-scope methods
			$observe('getDifferingVariableRoots(self)', static fn () => $scope->getDifferingVariableRoots($scope));
			$observe('getDifferingVariableRoots(other)', static fn () => $scope->getDifferingVariableRoots($other));
			$observe('getDifferingVariableRoots(other, reversed)', static fn () => $other->getDifferingVariableRoots($scope));

			// the scope-producing methods: the factory's recorded argument
			// lists, and whether the result is the factory's or $this
			$emptyConstraints = \PHPStan\Analyser\Generics\TemplateArgumentConstraints::createEmpty();
			$producers = [
				'enterDeclareStrictTypes' => static fn () => $scope->enterDeclareStrictTypes(),
				'rememberConstructorScope' => static fn () => $scope->rememberConstructorScope(),
				'afterExtractCall' => static fn () => $scope->afterExtractCall(),
				'afterClearstatcacheCall' => static fn () => $scope->afterClearstatcacheCall(),
				'afterOpenSslCall(openssl_encrypt)' => static fn () => $scope->afterOpenSslCall('openssl_encrypt'),
				'afterOpenSslCall(openssl_nope)' => static fn () => $scope->afterOpenSslCall('openssl_nope'),
				'invalidateVolatileExpressions' => static fn () => $scope->invalidateVolatileExpressions(),
				'invalidateExistenceCheckExpressions(class_exists)' => static fn () => $scope->invalidateExistenceCheckExpressions(['class_exists'], null),
				'invalidateExistenceCheckExpressions(function_exists,nope_missing)' => static fn () => $scope->invalidateExistenceCheckExpressions(['function_exists'], 'nope_missing'),
				'withAnonymousFunctionReflection' => static fn () => $scope->withAnonymousFunctionReflection($side === 'native' ? new \PHPStanTurbo\ClosureType() : new \PHPStan\Type\ClosureType()),
				'toNodeCallbackScope' => static fn () => $scope->toNodeCallbackScope(),
				'toNodeCallbackScope again' => static fn () => $scope->toNodeCallbackScope(),
				'duplicateWith' => static fn () => $scope->duplicateWith($args['expressionTypes'], $args['nativeExpressionTypes'], $args['conditionalExpressions'], ['$x' => true], ['$y' => true], [], !$args['inFirstLevelStatement'], true),
				'withoutMemoizedTypes' => static fn () => $scope->withoutMemoizedTypes(),
				'withTemplateArgumentFrame' => static fn () => $scope->withTemplateArgumentFrame($args['templateArgumentFrame']),
				'withTemplateArgumentConstraints(same)' => static fn () => $scope->withTemplateArgumentConstraints($args['templateArgumentConstraints']),
				'withTemplateArgumentConstraints(other)' => static function () use ($scope, $args, $emptyConstraints, $sfHarness) {
					$constraints = $args['templateArgumentConstraints'] === null ? $emptyConstraints : null;
					$clone = $scope->withTemplateArgumentConstraints($constraints);
					return [
						'is this' => $clone === $scope,
						'class' => $sfHarness->className($clone),
						'constraints' => $clone->getTemplateArgumentConstraints() === $constraints,
						'frame kept' => $clone->getCurrentTemplateArgumentFrame() === $scope->getCurrentTemplateArgumentFrame(),
						'variables kept' => $clone->getDefinedVariables() === $scope->getDefinedVariables(),
						'callback scope memo reset' => $clone->toNodeCallbackScope() !== $scope->toNodeCallbackScope(),
					];
				},
				'addTemplateArgumentConstraints(null)' => static fn () => $scope->addTemplateArgumentConstraints(null),
				'addTemplateArgumentConstraints(empty)' => static fn () => $scope->addTemplateArgumentConstraints($emptyConstraints),
				'doNotTreatPhpDocTypesAsCertain' => static fn () => $scope->doNotTreatPhpDocTypesAsCertain(),
				'doNotTreatPhpDocTypesAsCertain again' => static fn () => $scope->doNotTreatPhpDocTypesAsCertain(),
				'withRecordedStatementDelta(other, this)' => static fn () => $scope->withRecordedStatementDelta($other, $scope),
				'withRecordedStatementDelta(this, other)' => static fn () => $scope->withRecordedStatementDelta($scope, $other),
				'pushInFunctionCall(null)' => static fn () => $scope->pushInFunctionCall(null, null, false),
				'pushInFunctionCall(strlen, p)' => static fn () => $scope->pushInFunctionCall($sfPushReflection, $sfPushParameter, false),
				'pushInFunctionCall(strlen, remember)' => static function () use ($scope, $dummy, $sfPushReflection) {
					$saved = $dummy->resolvedTypes;
					try {
						return $scope->pushInFunctionCall($sfPushReflection, null, true);
					} finally {
						$dummy->resolvedTypes = $saved;
					}
				},
				'popInFunctionCall' => static function () use ($scope, $dummy) {
					$saved = $dummy->resolvedTypes;
					try {
						return $scope->popInFunctionCall();
					} finally {
						$dummy->resolvedTypes = $saved;
					}
				},
				'enterClass(Holder)' => static fn () => $scope->enterClass($sfHolderReflection),
				'enterClass(Custom)' => static fn () => $scope->enterClass($sfCustomReflection),
				'enterTrait(HelperTrait)' => static fn () => $scope->enterTrait($sfTraitReflection),
				'enterTrait(Holder)' => static fn () => $scope->enterTrait($sfHolderReflection),
				'enterClassMethod' => static fn () => $scope->enterClassMethod($sfProbeClassMethod, $sfEmptyTemplateTypeMap, ['a' => new \PHPStan\Type\IntegerType(), 'b' => new \PHPStan\Type\StringType(), 'd' => new \PHPStan\Type\StaticType($sfHolderReflection)], new \PHPStan\Type\StaticType($sfHolderReflection), new \PHPStan\Type\ObjectType(\Throwable::class), 'gone', true, false, false),
				'enterClassMethod(conditional)' => static function () use ($scope, $side, $sfProbeClassMethod, $sfEmptyTemplateTypeMap) {
					// the conditional wrapper is each side's own class (the native
					// enterFunctionLike() tests `instanceof ConditionalTypeForParameter`
					// against the native class, which under the prefix a PHP twin is
					// not); its inner types stay PHP ones — they meet the parameter's
					// own (PHP) type in TypeCombinator::intersect()
					$conditional = $side === 'native'
						? new \PHPStanTurbo\ConditionalTypeForParameter('$e', new \PHPStanTurbo\StringType(), new \PHPStanTurbo\IntegerType(), new \PHPStanTurbo\NullType(), false)
						: new \PHPStan\Type\ConditionalTypeForParameter('$e', new \PHPStan\Type\StringType(), new \PHPStan\Type\IntegerType(), new \PHPStan\Type\NullType(), false);
					$string = $side === 'native' ? new \PHPStanTurbo\StringType() : new \PHPStan\Type\StringType();
					return $scope->enterClassMethod($sfProbeClassMethod, $sfEmptyTemplateTypeMap, ['d' => $conditional, 'e' => $string], null, null, null, false, false, false);
				},
				'enterClassMethod(conditional, negated)' => static function () use ($scope, $side, $sfProbeClassMethod, $sfEmptyTemplateTypeMap) {
					$conditional = $side === 'native'
						? new \PHPStanTurbo\ConditionalTypeForParameter('$e', new \PHPStanTurbo\StringType(), new \PHPStanTurbo\IntegerType(), new \PHPStanTurbo\NullType(), true)
						: new \PHPStan\Type\ConditionalTypeForParameter('$e', new \PHPStan\Type\StringType(), new \PHPStan\Type\IntegerType(), new \PHPStan\Type\NullType(), true);
					$string = $side === 'native' ? new \PHPStanTurbo\StringType() : new \PHPStan\Type\StringType();
					return $scope->enterClassMethod($sfProbeClassMethod, $sfEmptyTemplateTypeMap, ['d' => $conditional, 'e' => $string], null, null, null, false, false, false);
				},
				'enterClassMethod(conditional, unknown target)' => static function () use ($scope, $side, $sfProbeClassMethod, $sfEmptyTemplateTypeMap) {
					$conditional = $side === 'native'
						? new \PHPStanTurbo\ConditionalTypeForParameter('$nope', new \PHPStanTurbo\StringType(), new \PHPStanTurbo\IntegerType(), new \PHPStanTurbo\NullType(), false)
						: new \PHPStan\Type\ConditionalTypeForParameter('$nope', new \PHPStan\Type\StringType(), new \PHPStan\Type\IntegerType(), new \PHPStan\Type\NullType(), false);
					$string = $side === 'native' ? new \PHPStanTurbo\StringType() : new \PHPStan\Type\StringType();
					return $scope->enterClassMethod($sfProbeClassMethod, $sfEmptyTemplateTypeMap, ['d' => $conditional, 'e' => $string], null, null, null, false, false, false);
				},
				'enterClassMethod(static)' => static fn () => $scope->enterClassMethod($sfProbeStaticClassMethod, $sfEmptyTemplateTypeMap, [], null, null, null, false, true, true, true, false, null, new \PHPStan\Type\ObjectWithoutClassType(), 'doc', ['a' => new \PHPStan\Type\IntegerType()], ['a' => true], [], true),
				'enterFunction' => static fn () => $scope->enterFunction($sfProbeFunction, $sfEmptyTemplateTypeMap, ['a' => new \PHPStan\Type\IntegerType()], new \PHPStan\Type\StringType(), null, null, false, false),
				'enterPropertyHook(get)' => static fn () => $scope->enterPropertyHook($sfProbeGetHook, 'name', new \PhpParser\Node\Identifier('string'), new \PHPStan\Type\StringType(), [], null, null, false, null, null),
				'enterPropertyHook(set)' => static fn () => $scope->enterPropertyHook($sfProbeSetHook, 'name', new \PhpParser\Node\Identifier('string'), new \PHPStan\Type\StringType(), [], null, 'gone', true, false, 'doc'),
				'enterPropertyHook(set, param)' => static fn () => $scope->enterPropertyHook($sfProbeSetHookWithParam, 'name', new \PhpParser\Node\Identifier('string'), new \PHPStan\Type\StringType(), [], null, null, false, true, null),
				'enterPropertyHook(set, param typed)' => static fn () => $scope->enterPropertyHook($sfProbeSetHookWithParam, 'name', new \PhpParser\Node\Identifier('string'), new \PHPStan\Type\StringType(), ['v' => new \PHPStan\Type\Constant\ConstantStringType('x')], null, null, false, null, null),
				'enterPropertyHook(get, no type)' => static fn () => $scope->enterPropertyHook($sfProbeGetHook, 'name', null, null, [], null, null, false, true, null),
				'enterPropertyHook(nope)' => static fn () => $scope->enterPropertyHook($sfProbeUnknownHook, 'name', null, null, [], null, null, false, null, null),
				'enterNamespace(Foo)' => static fn () => $scope->enterNamespace('Foo\\Bar'),
				'enterNamespace()' => static fn () => $scope->enterNamespace(''),
				'enterClosureBind(null)' => static fn () => $scope->enterClosureBind(null, null, []),
				'enterClosureBind(static)' => static fn () => $scope->enterClosureBind(new \PHPStan\Type\ObjectType(\ScopeFamilyFixture\Holder::class), new \PHPStan\Type\ObjectWithoutClassType(), ['static']),
				'enterClosureBind(Holder)' => static fn () => $scope->enterClosureBind(new \PHPStan\Type\ObjectType(\ScopeFamilyFixture\Holder::class), null, ['ScopeFamilyFixture\\Holder', 'Other']),
				'restoreOriginalScopeAfterClosureBind(other)' => static fn () => $scope->restoreOriginalScopeAfterClosureBind($other),
				'restoreOriginalScopeAfterClosureBind(this)' => static fn () => $scope->restoreOriginalScopeAfterClosureBind($scope),
				'restoreThis(other)' => static fn () => $scope->restoreThis($other),
				'restoreThis(this)' => static fn () => $scope->restoreThis($scope),
				'enterClosureCall' => static fn () => $scope->enterClosureCall(new \PHPStan\Type\ObjectType(\ScopeFamilyFixture\Holder::class), new \PHPStan\Type\ObjectType(\ScopeFamilyFixture\Custom::class)),
				'withClosureBindScopeClasses' => static fn () => $scope->withClosureBindScopeClasses(['ScopeFamilyFixture\\Holder']),
				'withClosureBindScopeClasses([])' => static fn () => $scope->withClosureBindScopeClasses([]),
			];
			if ($sfTracksPhpVersionId) {
				// the variadic parameter shapes (see getFunctionType above)
				$producers['enterClassMethod(variadic)'] = static fn () => $scope->enterClassMethod($sfProbeVariadicClassMethod, $sfEmptyTemplateTypeMap, [], null, null, null, false, false, false);
			}
			foreach ($producers as $label => $fn) {
				$before = count($factory->calls);
				$observe($label, static function () use ($fn, $scope, $dummy) {
					$result = $fn();
					return $result === $scope ? 'this' : ($result === $dummy ? 'factory result' : $result);
				});
				// a producer the barrier cut short never reached its create() call:
				// its argument lists are that same barrier hit, not a difference
				$barrier = $sfObservations[$side][$sfId][$label] === Harness::BARRIER;
				$calls = $barrier ? Harness::BARRIER : array_slice($factory->calls, $before);
				$observe("$label calls", static fn () => $calls);
				$callbackFactoryCalls = $barrier ? Harness::BARRIER : ($factory->nodeCallbackScopeFactory?->calls ?? []);
				$observe("$label callback-factory calls", static fn () => $callbackFactoryCalls);
			}

			// ---- the anonymous/arrow-function entries, the
			// assignment and invalidation family, the specification machinery.
			// Their results are observed as the resulting scope's state (a
			// digest): invalidateExpression() & co. answer with a
			// ScopeOps::scopeWith() clone that never reaches the factory, and a
			// raw scope normalizes to its object id. Every run is undone on
			// $dummy afterwards - the in-place specification writes into it.
			$scopeClass = $side === 'native' ? \PHPStanTurbo\MutatingScope::class : \PHPStan\Analyser\MutatingScope::class;
			$type = static fn (string $phpClass, mixed ...$args): \PHPStan\Type\Type => new ($side === 'native' ? 'PHPStanTurbo\\' . substr($phpClass, strrpos($phpClass, '\\') + 1) : $phpClass)(...$args);
			$yes = \PHPStan\TrinaryLogic::createYes();
			$maybe = \PHPStan\TrinaryLogic::createMaybe();
			$int = $type(\PHPStan\Type\IntegerType::class);
			$string = $type(\PHPStan\Type\StringType::class);
			$never = $type(\PHPStan\Type\NeverType::class);
			$thisVar = new \PhpParser\Node\Expr\Variable('this');
			$assignVar = new \PhpParser\Node\Expr\Variable('assignedHere');
			$propFetch = new \PhpParser\Node\Expr\PropertyFetch(new \PhpParser\Node\Expr\Variable('this'), 'name');
			// Holder::$inner is private: the only fetch isPrivatePropertyOfDifferentClass() gets past its visibility guard
			$privatePropFetch = new \PhpParser\Node\Expr\PropertyFetch(new \PhpParser\Node\Expr\Variable('this'), 'inner');
			// $a carries a tracked method call in the per-side tables:
			// assignExpression() on a property of it invalidates that call
			$trackedReceiverPropFetch = new \PhpParser\Node\Expr\PropertyFetch(new \PhpParser\Node\Expr\Variable('a'), 'p');
			// untracked over $arr, whose native flavour is array<int, int> and
			// whose phpdoc one array<int, string>: the readers that pick a
			// flavour answer differently here
			$differingDimFetch = new \PhpParser\Node\Expr\ArrayDimFetch(new \PhpParser\Node\Expr\Variable('arr'), new \PhpParser\Node\Expr\Variable('i'));
			$staticPropFetch = new \PhpParser\Node\Expr\StaticPropertyFetch(new \PhpParser\Node\Name('Holder'), 'shared');
			$dimFetch = new \PhpParser\Node\Expr\ArrayDimFetch(new \PhpParser\Node\Expr\Variable('items'), new \PhpParser\Node\Scalar\Int_(0));
			$iteratee = new \PhpParser\Node\Expr\Variable('items');
			$arrayType = $type(\PHPStan\Type\ArrayType::class, $type(\PHPStan\Type\IntegerType::class), $type(\PHPStan\Type\StringType::class));
			$chainingRuns = [
				'intersectButNotNever(int, string)' => static fn () => ($scopeClass . '::intersectButNotNever')($int, $string),
				'intersectButNotNever(int, int)' => static fn () => ($scopeClass . '::intersectButNotNever')($int, $int),
				'intersectButNotNever(?int, int)' => static fn () => ($scopeClass . '::intersectButNotNever')((($side === 'native' ? 'PHPStanTurbo\\TypeCombinator' : \PHPStan\Type\TypeCombinator::class) . '::addNull')($int), $int),
				'enterExpressionAssign' => static fn () => $scope->enterExpressionAssign($propFetch),
				'enterExpressionAssign(not plain)' => static fn () => $scope->enterExpressionAssign($propFetch, false),
				'exitExpressionAssign' => static fn () => $scope->exitExpressionAssign($propFetch),
				'setAllowedUndefinedExpression' => static fn () => $scope->setAllowedUndefinedExpression($dimFetch),
				'setAllowedUndefinedExpression(static prop)' => static fn () => $scope->setAllowedUndefinedExpression($staticPropFetch),
				'unsetAllowedUndefinedExpression' => static fn () => $scope->unsetAllowedUndefinedExpression($dimFetch),
				'specifyExpressionType(var, yes)' => static fn () => $scope->specifyExpressionType($assignVar, $int, $int, $yes),
				'specifyExpressionType(var, maybe)' => static fn () => $scope->specifyExpressionType($assignVar, $int, $string, $maybe),
				'specifyExpressionType(scalar noop)' => static fn () => $scope->specifyExpressionType(new \PhpParser\Node\Scalar\Int_(1), $int, $int, $yes),
				'specifyExpressionType(null noop)' => static fn () => $scope->specifyExpressionType(new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('null')), $int, $int, $yes),
				'specifyExpressionType(is_file false noop)' => static fn () => $scope->specifyExpressionType(new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('is_file'), [new \PhpParser\Node\Arg(new \PhpParser\Node\Scalar\String_('x'))]), $type(\PHPStan\Type\Constant\ConstantBooleanType::class, false), $type(\PHPStan\Type\Constant\ConstantBooleanType::class, false), $yes),
				'specifyExpressionType(alwaysRemembered)' => static fn () => $scope->specifyExpressionType(new \PHPStan\Node\Expr\AlwaysRememberedExpr($assignVar, $int, $int), $int, $int, $yes),
				'assignExpression(var)' => static fn () => $scope->assignExpression($assignVar, $int, $string),
				'assignExpression(this)' => static fn () => $scope->assignExpression($thisVar, $int, $int),
				'assignExpression(property)' => static fn () => $scope->assignExpression($propFetch, $int, $int),
				'assignExpression(static property)' => static fn () => $scope->assignExpression($staticPropFetch, $int, $int),
				'assignExpression(dim fetch)' => static fn () => $scope->assignExpression($dimFetch, $int, $int),
				'assignExpression(property of tracked receiver)' => static fn () => $scope->assignExpression($trackedReceiverPropFetch, $int, $int),
				'assignVariable(yes)' => static fn () => $scope->assignVariable('assignedHere', $int, $string, $yes),
				'assignVariable(maybe)' => static fn () => $scope->assignVariable('assignedHere', $int, $string, $maybe),
				'assignVariable(this)' => static fn () => $scope->assignVariable('this', $int, $int, $yes),
				'assignVariable(propagated)' => static fn () => $scope->assignVariable('assignedHere', $int, $int, $yes, ['assignedHere', 'other']),
				// TypeUtils::findThisType() tests `instanceof ThisType` against
				// each side's own class, so the probe's $this type must be that
				// side's too (the walk's own type is a PHP one on both sides)
				'assignInitializedProperty(this)' => static fn () => $scope->assignInitializedProperty($type(\PHPStan\Type\ThisType::class, $sfHolderReflection), 'name'),
				'assignInitializedProperty(unknown property)' => static fn () => $scope->assignInitializedProperty($type(\PHPStan\Type\ThisType::class, $sfHolderReflection), 'neverDeclared'),
				'assignInitializedProperty(int)' => static fn () => $scope->assignInitializedProperty($int, 'name'),
				'invalidateExpression(var)' => static fn () => $scope->invalidateExpression($thisVar),
				'invalidateExpression(var, more characters)' => static fn () => $scope->invalidateExpression($thisVar, true),
				'invalidateExpression(var, keep property fetches)' => static fn () => $scope->invalidateExpression($thisVar, false, null, true),
				'invalidateExpression(property)' => static fn () => $scope->invalidateExpression($propFetch),
				'invalidateExpression(property, invalidating class)' => static fn () => $scope->invalidateExpression($propFetch, false, $sfHolderReflection),
				'invalidateExpression(unknown)' => static fn () => $scope->invalidateExpression(new \PhpParser\Node\Expr\Variable('neverTracked')),
				'isPrivatePropertyOfDifferentClass(property)' => static fn () => $scope->isPrivatePropertyOfDifferentClass($propFetch, $sfHolderReflection),
				'isPrivatePropertyOfDifferentClass(static property)' => static fn () => $scope->isPrivatePropertyOfDifferentClass($staticPropFetch, $sfCustomReflection),
				'isPrivatePropertyOfDifferentClass(var)' => static fn () => $scope->isPrivatePropertyOfDifferentClass($thisVar, $sfHolderReflection),
				'isPrivatePropertyOfDifferentClass(private, same class)' => static fn () => $scope->isPrivatePropertyOfDifferentClass($privatePropFetch, $sfHolderReflection),
				'isPrivatePropertyOfDifferentClass(private, other class)' => static fn () => $scope->isPrivatePropertyOfDifferentClass($privatePropFetch, $sfCustomReflection),
				'addTypeToExpression(var)' => static fn () => $scope->addTypeToExpression($assignVar, $int),
				'addTypeToExpression(this)' => static fn () => $scope->addTypeToExpression($thisVar, $int),
				// the per-side tables track $b and $arr with a wider native
				// flavour: the readers that pick one are blind over equal tables
				'addTypeToExpression(differing flavours)' => static fn () => $scope->addTypeToExpression($differingDimFetch, $string),
				'removeTypeFromExpression(differing flavours)' => static fn () => $scope->removeTypeFromExpression($differingDimFetch, $int),
				'specifyExpressionType(differing flavours)' => static fn () => $scope->specifyExpressionType($differingDimFetch, $string, $int, $yes),
				'removeTypeFromExpression(var)' => static fn () => $scope->removeTypeFromExpression($assignVar, $string),
				'removeTypeFromExpression(never)' => static fn () => $scope->removeTypeFromExpression($assignVar, $never),
				'enterCatchType(null)' => static fn () => $scope->enterCatchType($type(\PHPStan\Type\ObjectType::class, \Throwable::class), null),
				'enterCatchType(e)' => static fn () => $scope->enterCatchType($type(\PHPStan\Type\ObjectType::class, \LogicException::class), 'e'),
				'enterCatchType(not throwable)' => static fn () => $scope->enterCatchType($type(\PHPStan\Type\ObjectType::class, \stdClass::class), 'e'),
				'enterCatchType(interface)' => static fn () => $scope->enterCatchType($type(\PHPStan\Type\ObjectType::class, \Countable::class), 'e'),
				'enterMatch(variable cond)' => static fn () => $scope->enterMatch(new \PhpParser\Node\Expr\Match_(new \PhpParser\Node\Expr\Variable('x'), [new \PhpParser\Node\MatchArm(null, new \PhpParser\Node\Scalar\Int_(1))]), $int, $int),
				'enterMatch(scalar cond)' => static fn () => $scope->enterMatch(new \PhpParser\Node\Expr\Match_(new \PhpParser\Node\Scalar\Int_(2), [new \PhpParser\Node\MatchArm(null, new \PhpParser\Node\Scalar\Int_(1))]), $int, $int),
				'enterMatch(call cond)' => static fn () => $scope->enterMatch(new \PhpParser\Node\Expr\Match_(new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('strlen'), [new \PhpParser\Node\Arg(new \PhpParser\Node\Scalar\String_('x'))]), [new \PhpParser\Node\MatchArm(null, new \PhpParser\Node\Scalar\Int_(1))]), $int, $string),
				'enterMatch(remembered cond)' => static fn () => $scope->enterMatch(new \PhpParser\Node\Expr\Match_(new \PHPStan\Node\Expr\AlwaysRememberedExpr(new \PhpParser\Node\Expr\Variable('y'), $int, $int), [new \PhpParser\Node\MatchArm(null, new \PhpParser\Node\Scalar\Int_(1))]), $int, $int),
				'enterMatch(cond node)' => static function () use ($scope, $int, $string, $sfHarness) {
					$matchNode = new \PhpParser\Node\Expr\Match_(new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('strlen'), [new \PhpParser\Node\Arg(new \PhpParser\Node\Scalar\String_('x'))]), [new \PhpParser\Node\MatchArm(null, new \PhpParser\Node\Scalar\Int_(1))]);
					$scope->enterMatch($matchNode, $int, $string);

					return [$sfHarness->className($matchNode->cond), $sfHarness->key($matchNode->cond)];
				},
				'enterForeachKey' => static fn () => $scope->enterForeachKey($other, $iteratee, $arrayType, $arrayType, 'k'),
				'enterForeachKey(not array)' => static fn () => $scope->enterForeachKey($other, $iteratee, $string, $string, 'k'),
				'enterForeach' => static fn () => $scope->enterForeach($other, $iteratee, $arrayType, $arrayType, 'v', null, false),
				'enterForeach(key)' => static fn () => $scope->enterForeach($other, $iteratee, $arrayType, $arrayType, 'v', 'k', false),
				'enterForeach(by ref)' => static fn () => $scope->enterForeach($other, $iteratee, $arrayType, $arrayType, 'v', null, true),
				'enterForeach(by ref, key)' => static fn () => $scope->enterForeach($other, $iteratee, $arrayType, $arrayType, 'v', 'k', true),
				'enterForeach(constant array by ref)' => static fn () => $scope->enterForeach($other, $iteratee, \PHPStan\Type\Constant\ConstantArrayTypeBuilder::createEmpty()->getArray(), $arrayType, 'v', 'k', true),
				'enterAnonymousFunctionWithoutReflection' => static fn () => $scope->enterAnonymousFunctionWithoutReflection($sfProbeClosure, null, null),
				'enterAnonymousFunctionWithoutReflection(static)' => static fn () => $scope->enterAnonymousFunctionWithoutReflection($sfProbeStaticClosure, null, null),
				'enterAnonymousFunctionWithoutReflection(callable params)' => static fn () => $scope->enterAnonymousFunctionWithoutReflection($sfProbeClosure, [$sfPushParameter], [$sfPushParameter]),
				'enterAnonymousFunctionWithoutReflection(empty callable params)' => static fn () => $scope->enterAnonymousFunctionWithoutReflection($sfProbeClosure, [], []),
				'enterAnonymousFunctionWithoutReflection(untyped, variadic tail)' => static fn () => $scope->enterAnonymousFunctionWithoutReflection($sfProbeUntypedClosure, [$sfPushVariadicParameter], null),
				'enterAnonymousFunctionWithoutReflection(untyped, no callable params)' => static fn () => $scope->enterAnonymousFunctionWithoutReflection($sfProbeUntypedClosure, [], null),
				'enterAnonymousFunctionWithoutReflection(untyped variadic param)' => static fn () => $scope->enterAnonymousFunctionWithoutReflection($sfProbeUntypedVariadicClosure, [$sfPushVariadicParameter], null),
				'enterArrowFunctionWithoutReflection' => static fn () => $scope->enterArrowFunctionWithoutReflection($sfProbeArrowFunction, null, null),
				'enterArrowFunctionWithoutReflection(static)' => static fn () => $scope->enterArrowFunctionWithoutReflection($sfProbeStaticArrowFunction, null, null),
				'enterArrowFunctionWithoutReflection(callable params)' => static fn () => $scope->enterArrowFunctionWithoutReflection($sfProbeArrowFunction, [$sfPushParameter], null),
				'enterArrowFunctionWithoutReflection(variadic callable params)' => static fn () => $scope->enterArrowFunctionWithoutReflection($sfProbeVariadicArrowFunction, [$sfPushParameter, $sfPushVariadicParameter], null),
				// the ClosureTypeResolver's MutatingScope parameter is the real
				// class name: these two cross the prefix type barrier
				'enterAnonymousFunction' => static fn () => $scope->enterAnonymousFunction($sfProbeClosure, null),
				'enterArrowFunction' => static fn () => $scope->enterArrowFunction($sfProbeArrowFunction, null),
			];
			// the ArrayDimFetch arm of specifyExpressionTypeInPlace() tests the
			// dim and var types against each side's own classes - only the
			// scope whose tables are built per side can answer them that way
			if ($scope->hasExpressionType(new \PhpParser\Node\Expr\Variable('i'))->yes()) {
				$chainingRuns['specifyExpressionType(dim fetch, int dim)'] = static fn () => $scope->specifyExpressionType(new \PhpParser\Node\Expr\ArrayDimFetch(new \PhpParser\Node\Expr\Variable('arr'), new \PhpParser\Node\Expr\Variable('i')), $string, $string, $yes);
				$chainingRuns['specifyExpressionType(dim fetch, string dim)'] = static fn () => $scope->specifyExpressionType(new \PhpParser\Node\Expr\ArrayDimFetch(new \PhpParser\Node\Expr\Variable('arr'), new \PhpParser\Node\Expr\Variable('k')), $string, $string, $yes);
				$chainingRuns['specifyExpressionType(dim fetch, mixed var)'] = static fn () => $scope->specifyExpressionType(new \PhpParser\Node\Expr\ArrayDimFetch(new \PhpParser\Node\Expr\Variable('m'), new \PhpParser\Node\Expr\Variable('i')), $string, $string, $yes);
				$chainingRuns['specifyExpressionType(dim fetch, string var)'] = static fn () => $scope->specifyExpressionType(new \PhpParser\Node\Expr\ArrayDimFetch(new \PhpParser\Node\Expr\Variable('b'), new \PhpParser\Node\Expr\Variable('i')), $string, $string, $yes);
				$chainingRuns['specifyExpressionType(dim fetch, inc dim)'] = static fn () => $scope->specifyExpressionType(new \PhpParser\Node\Expr\ArrayDimFetch(new \PhpParser\Node\Expr\Variable('arr'), new \PhpParser\Node\Expr\PreInc(new \PhpParser\Node\Expr\Variable('i'))), $string, $string, $yes);
			}

			// ---- the narrowing application, the conditional-expression
			// bookkeeping and the scope merges. The same digest observable
			// as the chaining runs, but every run is undone on the scope
			// under test as well: the batch's conditional bookkeeping
			// (processConditionalExpressionsAfterSpecifying) writes into the
			// scope it was applied on when no derivation intervened.
			$sfKey = static fn (\PhpParser\Node\Expr $expr): string => $sfHarness->key($expr);
			$specified = static fn (array $sure = [], array $sureNot = []): \PHPStan\Analyser\SpecifiedTypes => new \PHPStan\Analyser\SpecifiedTypes($sure, $sureNot);
			// the alternative-form entries only SpecifiedTypes::intersectWith() produces
			$withAlternatives = static function (\PHPStan\Analyser\SpecifiedTypes $types, array $alternatives): \PHPStan\Analyser\SpecifiedTypes {
				$clone = clone $types;
				(new \ReflectionProperty(\PHPStan\Analyser\SpecifiedTypes::class, 'alternativeTypes'))->setValue($clone, $alternatives);

				return $clone;
			};
			// PropertyInitializationExpr is the one Expr class with no ExprHandler:
			// TypeSpecifier::specifyTypesInCondition() then takes the default
			// narrowing on both sides (with a handler it dispatches
			// specifyTypesOfNewWorldHandlerNode() only on a real MutatingScope,
			// which the prefixed native class is not). A first-class callable
			// returns before the scope is consulted at all.
			// Only the truthy direction is comparable: its `mixed minus falsey()`
			// goes through MixedType::subtract(), while the falsey direction's
			// `mixed minus truthy()` asks the TypeCombinator to decompose a PHP
			// union, which the native one (the native scope's) treats as atomic.
			// A native truthy/falsey mix-up still shows: it would answer never.
			$initializationExpr = new \PHPStan\Node\Expr\PropertyInitializationExpr('p');
			$firstClassCallable = new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('strlen'), [new \PhpParser\Node\VariadicPlaceholder()]);
			$issetExpr = new \PHPStan\Node\IssetExpr($assignVar);
			$issetTrackedExpr = new \PHPStan\Node\IssetExpr($thisVar);
			$byRefUseVar = new \PhpParser\Node\Expr\Variable('byRefUse');
			$conditionalHolder = static function (string $conditionKey, \PhpParser\Node\Expr $conditionExpr, \PHPStan\Type\Type $conditionType, \PhpParser\Node\Expr $targetExpr, \PHPStan\Type\Type $targetType) use ($side, $sfHarness): object {
				$holder = new \PHPStan\Analyser\ConditionalExpressionHolder(
					[$conditionKey => \PHPStan\Analyser\ExpressionTypeHolder::createYes($conditionExpr, $conditionType)],
					\PHPStan\Analyser\ExpressionTypeHolder::createYes($targetExpr, $targetType),
				);

				return $side === 'native' ? $sfHarness->nativeConditionalExpressions(['x' => ['k' => $holder]])['x']['k'] : $holder;
			};
			$newConditionalHolder = $conditionalHolder('$byRefUse', $byRefUseVar, $int, $assignVar, $string);
			$narrowingRuns = [
				'filterByTruthyValue(property initialization)' => static fn () => $scope->filterByTruthyValue($initializationExpr),
				'filterByTruthyValue(first-class callable)' => static fn () => $scope->filterByTruthyValue($firstClassCallable),
				'filterByFalseyValue(first-class callable)' => static fn () => $scope->filterByFalseyValue($firstClassCallable),
				'applySpecifiedTypes(empty)' => static fn () => $scope->applySpecifiedTypes($specified()),
				'applySpecifiedTypes(sure, untracked variable)' => static fn () => $scope->applySpecifiedTypes($specified([$sfKey($assignVar) => [$assignVar, $int]])),
				'applySpecifiedTypes(sure, this)' => static fn () => $scope->applySpecifiedTypes($specified([$sfKey($thisVar) => [$thisVar, $int]])),
				'applySpecifiedTypes(sure, dim fetch)' => static fn () => $scope->applySpecifiedTypes($specified([$sfKey($dimFetch) => [$dimFetch, $string]])),
				'applySpecifiedTypes(sure not, this)' => static fn () => $scope->applySpecifiedTypes($specified([], [$sfKey($thisVar) => [$thisVar, $string]])),
				'applySpecifiedTypes(sure not, untracked variable)' => static fn () => $scope->applySpecifiedTypes($specified([], [$sfKey($assignVar) => [$assignVar, $string]])),
				'applySpecifiedTypes(sure not, never)' => static fn () => $scope->applySpecifiedTypes($specified([], [$sfKey($thisVar) => [$thisVar, $never]])),
				'applySpecifiedTypes(overwrite)' => static fn () => $scope->applySpecifiedTypes($specified([$sfKey($assignVar) => [$assignVar, $int]])->setAlwaysOverwriteTypes()),
				'applySpecifiedTypes(overwrite, tracked)' => static fn () => $scope->applySpecifiedTypes($specified([$sfKey($thisVar) => [$thisVar, $int]])->setAlwaysOverwriteTypes()),
				// the three expression shapes the batch never specifies, and the
				// unary minus of something else, which it does. Overwriting: the
				// unary minus resolves to a union of the walk's (PHP) types,
				// which a narrowing intersection would hand to the native
				// TypeCombinator as an atom
				'applySpecifiedTypes(scalar, array, unary minus)' => static fn () => $scope->applySpecifiedTypes($specified([
					'1' => [new \PhpParser\Node\Scalar\Int_(1), $int],
					'[]' => [new \PhpParser\Node\Expr\Array_([]), $int],
					'-1' => [new \PhpParser\Node\Expr\UnaryMinus(new \PhpParser\Node\Scalar\Int_(1)), $int],
					'-$assignedHere' => [new \PhpParser\Node\Expr\UnaryMinus($assignVar), $int],
				])->setAlwaysOverwriteTypes()),
				'applySpecifiedTypes(isset, sure)' => static fn () => $scope->applySpecifiedTypes($specified([$sfKey($issetExpr) => [$issetExpr, $int]])),
				'applySpecifiedTypes(isset, sure not)' => static fn () => $scope->applySpecifiedTypes($specified([], [$sfKey($issetExpr) => [$issetExpr, $int]])),
				'applySpecifiedTypes(isset, tracked, sure)' => static fn () => $scope->applySpecifiedTypes($specified([$sfKey($issetTrackedExpr) => [$issetTrackedExpr, $int]])),
				'applySpecifiedTypes(isset, tracked, sure not)' => static fn () => $scope->applySpecifiedTypes($specified([], [$sfKey($issetTrackedExpr) => [$issetTrackedExpr, $int]])),
				'applySpecifiedTypes(alternative, sure terms)' => static fn () => $scope->applySpecifiedTypes($withAlternatives($specified(), [$sfKey($thisVar) => [$thisVar, [[$int, null], [$string, null]]]])),
				'applySpecifiedTypes(alternative, current minus)' => static fn () => $scope->applySpecifiedTypes($withAlternatives($specified(), [$sfKey($thisVar) => [$thisVar, [[null, $string]]]])),
				'applySpecifiedTypes(alternative, untracked current)' => static fn () => $scope->applySpecifiedTypes($withAlternatives($specified(), [$sfKey($assignVar) => [$assignVar, [[null, $string]]]])),
				'applySpecifiedTypes(alternative, mixed terms)' => static fn () => $scope->applySpecifiedTypes($withAlternatives($specified(), [$sfKey($thisVar) => [$thisVar, [[$int, $string], [null, null]]]])),
				'applySpecifiedTypes(new conditional holders)' => static fn () => $scope->applySpecifiedTypes($specified([$sfKey($assignVar) => [$assignVar, $int]])->setNewConditionalExpressionHolders(['$assignedHere' => ['k' => $newConditionalHolder]])),
				'applySpecifiedTypes(recipe)' => static fn () => $scope->applySpecifiedTypes($specified()->setConditionalExpressionHolderRecipes([new \ScopeFamily\TestRecipe(['$assignedHere' => ['k' => $newConditionalHolder]])])),
				'applySpecifiedTypes(recipe over existing key)' => static fn () => $scope->applySpecifiedTypes($specified()
					->setNewConditionalExpressionHolders(['$assignedHere' => ['k' => $newConditionalHolder]])
					->setConditionalExpressionHolderRecipes([new \ScopeFamily\TestRecipe(['$assignedHere' => ['other' => $newConditionalHolder]])])),
				'applySpecifiedTypes(deferred augment)' => static fn () => $scope->applySpecifiedTypes($specified([$sfKey($thisVar) => [$thisVar, $int]])->withDeferredAugment(new \ScopeFamily\TestAugment($specified([$sfKey($assignVar) => [$assignVar, $string]])))),
				'applySpecifiedTypes(deferred augment, null)' => static fn () => $scope->applySpecifiedTypes($specified()->withDeferredAugment(new \ScopeFamily\TestAugment(null))),
				'applySpecifiedTypes(nested deferred augment)' => static fn () => $scope->applySpecifiedTypes($specified()->withDeferredAugment(
					new \ScopeFamily\TestAugment($specified([$sfKey($assignVar) => [$assignVar, $string]])->withDeferredAugment(new \ScopeFamily\TestAugment($specified([$sfKey($dimFetch) => [$dimFetch, $int]])))),
				)),
				// matches the conditional expressions the conditional scope carries
				'applySpecifiedTypes(matching conditional)' => static fn () => $scope->applySpecifiedTypes($specified([$sfKey($byRefUseVar) => [$byRefUseVar, $int]])),
				'applySpecifiedTypes(matching conditional, sure not)' => static fn () => $scope->applySpecifiedTypes($specified([], [$sfKey($byRefUseVar) => [$byRefUseVar, $string]])),
				'addConditionalExpressions(new key)' => static fn () => $scope->addConditionalExpressions('$freshKey', [$newConditionalHolder]),
				'addConditionalExpressions(existing key)' => static fn () => $scope->addConditionalExpressions('$p', [$newConditionalHolder]),
				'addConditionalExpressions(empty)' => static fn () => $scope->addConditionalExpressions('$freshKey', []),
				'exitFirstLevelStatements' => static function () use ($scope, $sfHarness, $dummy) {
					$first = $scope->exitFirstLevelStatements();
					$second = $scope->exitFirstLevelStatements();

					return [
						'memoized' => $first === $second,
						'is this' => $first === $scope,
						'class' => $sfHarness->className($first),
						'first level' => $first->isInFirstLevelStatement(),
						'memo' => array_keys($first->resolvedTypes),
					];
				},
				'mergeWith(null)' => static fn () => $scope->mergeWith(null),
				'mergeWith(this)' => static fn () => $scope->mergeWith($scope),
				'mergeWith(other)' => static fn () => $scope->mergeWith($other),
				'mergeWith(other, preserve vacuous)' => static fn () => $scope->mergeWith($other, true),
				'mergeWith(other with constraints)' => static fn () => $scope->mergeWith($other->withTemplateArgumentConstraints($sfNonEmptyConstraints)),
				'mergeInitializedProperties(this)' => static fn () => $scope->mergeInitializedProperties($scope),
				'mergeInitializedProperties(other)' => static fn () => $scope->mergeInitializedProperties($other),
				'processFinallyScope(other, this)' => static fn () => $scope->processFinallyScope($other, $scope),
				'processFinallyScope(this, other)' => static fn () => $scope->processFinallyScope($scope, $other),
				'processFinallyScope(other, other)' => static fn () => $scope->processFinallyScope($other, $other),
			];
			// the merge scopes carry conditional expressions guarded on $g: a
			// specification that matches their guard drives
			// processConditionalExpressionsAfterSpecifying()
			$gVar = new \PhpParser\Node\Expr\Variable('g');
			if ($scope->hasExpressionType($gVar)->yes()) {
				// the batch entry is keyed on the guard but carries a `null`
				// ConstFetch, whose specification is a no-op: the batch then
				// never derives a scope, so the conditional bookkeeping runs on
				// the scope under test itself (an unpublished working copy is
				// the PHP class on both sides - the factory's return type names
				// it - and would take the native body's private call by name)
				$noopExpr = new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('null'));
				$narrowingRuns['applySpecifiedTypes(conditional guard)'] = static fn () => $scope->applySpecifiedTypes($specified([$sfKey($gVar) => [$noopExpr, $scope->getTrackedExpressionType($gVar)]]));
				// a narrower specification: only the supertype pass matches
				$narrowingRuns['applySpecifiedTypes(conditional guard, supertype)'] = static fn () => $scope->applySpecifiedTypes($specified([$sfKey($gVar) => [$noopExpr, $type(\PHPStan\Type\Constant\ConstantIntegerType::class, 3)]]));
				$narrowingRuns['applySpecifiedTypes(conditional guard, sure not)'] = static fn () => $scope->applySpecifiedTypes($specified([], [$sfKey($gVar) => [$noopExpr, $type(\PHPStan\Type\Constant\ConstantIntegerType::class, 3)]]));
				// the batch's holders join the scope's existing entry for the
				// same expression rather than replacing it
				$narrowingRuns['applySpecifiedTypes(new holders over existing conditionals)'] = static fn () => $scope->applySpecifiedTypes($specified()->setNewConditionalExpressionHolders(['$t2' => ['k' => $newConditionalHolder]]));
			}
			// the narrowing that really combines types can only be compared over
			// the per-side tables: a type of this side meeting the walk's (PHP)
			// one goes through the native TypeCombinator, which treats a foreign
			// class as atomic
			if ($scope->hasExpressionType(new \PhpParser\Node\Expr\Variable('i'))->yes()) {
				$aVar = new \PhpParser\Node\Expr\Variable('a');
				$bVar = new \PhpParser\Node\Expr\Variable('b');
				$mVar = new \PhpParser\Node\Expr\Variable('m');
				$arrVar = new \PhpParser\Node\Expr\Variable('arr');
				$narrowingRuns['applySpecifiedTypes(sure, tracked int)'] = static fn () => $scope->applySpecifiedTypes($specified([$sfKey($aVar) => [$aVar, $int]]));
				$narrowingRuns['applySpecifiedTypes(sure, tracked int against string)'] = static fn () => $scope->applySpecifiedTypes($specified([$sfKey($aVar) => [$aVar, $string]]));
				$narrowingRuns['applySpecifiedTypes(sure, tracked mixed)'] = static fn () => $scope->applySpecifiedTypes($specified([$sfKey($mVar) => [$mVar, $string]]));
				$narrowingRuns['applySpecifiedTypes(sure not, tracked mixed)'] = static fn () => $scope->applySpecifiedTypes($specified([], [$sfKey($mVar) => [$mVar, $string]]));
				$narrowingRuns['applySpecifiedTypes(sure not, tracked int)'] = static fn () => $scope->applySpecifiedTypes($specified([], [$sfKey($aVar) => [$aVar, $string]]));
				// $b and $arr are wider in the native table than in the phpdoc one
				$narrowingRuns['applySpecifiedTypes(sure, differing flavours)'] = static fn () => $scope->applySpecifiedTypes($specified([$sfKey($bVar) => [$bVar, $string]]));
				$narrowingRuns['applySpecifiedTypes(sure not, differing flavours)'] = static fn () => $scope->applySpecifiedTypes($specified([], [$sfKey($arrVar) => [$arrVar, $arrayType]]));
				$narrowingRuns['applySpecifiedTypes(overwrite, tracked)'] = static fn () => $scope->applySpecifiedTypes($specified([$sfKey($aVar) => [$aVar, $string]])->setAlwaysOverwriteTypes());
				// the usort(): shorter keys first, sure before sure-not
				$narrowingRuns['applySpecifiedTypes(sort order)'] = static fn () => $scope->applySpecifiedTypes($specified(
					[$sfKey($aVar) => [$aVar, $int], $sfKey($mVar) => [$mVar, $string]],
					[$sfKey($bVar) => [$bVar, $string], $sfKey($arrVar) => [$arrVar, $arrayType]],
				));
				$narrowingRuns['applySpecifiedTypes(alternative, tracked terms)'] = static fn () => $scope->applySpecifiedTypes($withAlternatives($specified(), [$sfKey($mVar) => [$mVar, [[$int, null], [null, $string]]]]));
				// the native flavour of an alternative entry reads the native current type
				$narrowingRuns['applySpecifiedTypes(alternative, differing flavours)'] = static fn () => $scope->applySpecifiedTypes($withAlternatives($specified(), [$sfKey($arrVar) => [$arrVar, [[null, $string]]]]));
				// only the native table tracks $nativeOnly: the current-type
				// fallback must leave that flavour alone
				$nativeOnlyVar = new \PhpParser\Node\Expr\Variable('nativeOnly');
				$narrowingRuns['applySpecifiedTypes(sure, native-only tracked)'] = static fn () => $scope->applySpecifiedTypes($specified([$sfKey($nativeOnlyVar) => [$nativeOnlyVar, $int]]));
				// the batch sort's tie-break: at equal key length a sure
				// specification runs before a sure-not one, so the unset of
				// $m happens after it was narrowed
				$narrowingRuns['applySpecifiedTypes(sure before sure not at equal length)'] = static fn () => $scope->applySpecifiedTypes($specified(
					[$sfKey($mVar) => [$mVar, $string]],
					['ZZ' => [new \PHPStan\Node\IssetExpr($mVar), $int]],
				));
				$narrowingRuns['applySpecifiedTypes(isset, tracked, sure)'] = static fn () => $scope->applySpecifiedTypes($specified([$sfKey(new \PHPStan\Node\IssetExpr($aVar)) => [new \PHPStan\Node\IssetExpr($aVar), $int]]));
			}


			// ---- the closure and loop scopes, the generalization, the scope
			// comparison, the member-access queries and the remaining
			// readers. Same digest observable as the two groups above.
			$combinator = $side === 'native' ? 'PHPStanTurbo\\TypeCombinator' : \PHPStan\Type\TypeCombinator::class;
			$union = static fn (\PHPStan\Type\Type ...$types): \PHPStan\Type\Type => ($combinator . '::union')(...$types);
			// a reflection lookup's result by what it names, not by its identity
			$reflectionDigest = static function (mixed $reflection) use ($sfHarness): mixed {
				if (!is_object($reflection)) {
					return $reflection;
				}
				$digest = ['RF', $sfHarness->className($reflection)];
				if (method_exists($reflection, 'getName')) {
					$digest[] = $reflection->getName();
				}
				if (method_exists($reflection, 'getDeclaringClass')) {
					$digest[] = $reflection->getDeclaringClass()->getName();
				}

				return $digest;
			};
			// the union filters of the member lookups only reach their
			// `instanceof UnionType` arm over a union of THIS side's classes
			$iterableUnion = $union($arrayType, $string);
			$scalarUnion = $union($int, $string);
			$holderUnion = $union($type(\PHPStan\Type\ObjectType::class, \ScopeFamilyFixture\Holder::class), $type(\PHPStan\Type\NullType::class));
			// an interface member answers Maybe to hasMethod()/hasProperty():
			// the filter predicate keeps only the Yes ones
			$maybeUnion = $union($type(\PHPStan\Type\ObjectType::class, \ScopeFamilyFixture\Holder::class), $type(\PHPStan\Type\ObjectType::class, \Countable::class));
			$holderType = $type(\PHPStan\Type\ObjectType::class, \ScopeFamilyFixture\Holder::class);
			// a use the scopes track, one they do not, and the keys of the
			// generalize pair whose two types generalizeType() widens rather
			// than merely unites
			$byRefUses = [
				new \PhpParser\Node\ClosureUse(new \PhpParser\Node\Expr\Variable('g'), true),
				new \PhpParser\Node\ClosureUse(new \PhpParser\Node\Expr\Variable('neverDefinedUse'), true),
				new \PhpParser\Node\ClosureUse(new \PhpParser\Node\Expr\Variable('gDeep'), true),
				new \PhpParser\Node\ClosureUse(new \PhpParser\Node\Expr\Variable('gShapeKeys'), true),
				new \PhpParser\Node\ClosureUse(new \PhpParser\Node\Expr\Variable('gArray'), true),
				new \PhpParser\Node\ClosureUse(new \PhpParser\Node\Expr\Variable('gList'), true),
				new \PhpParser\Node\ClosureUse(new \PhpParser\Node\Expr\Variable('gAccessory'), true),
				new \PhpParser\Node\ClosureUse(new \PhpParser\Node\Expr\Variable('gBenevolent'), true),
			];
			$closureLoopRuns = [
				'processClosureScope(no uses)' => static fn () => $scope->processClosureScope($scope, null, []),
				'processClosureScope(this, null)' => static fn () => $scope->processClosureScope($scope, null, $byRefUses),
				'processClosureScope(other, null)' => static fn () => $scope->processClosureScope($other, null, $byRefUses),
				'processClosureScope(this, other)' => static fn () => $scope->processClosureScope($scope, $other, $byRefUses),
				'processClosureScope(other, this)' => static fn () => $scope->processClosureScope($other, $scope, $byRefUses),
				'processAlwaysIterableForeachScopeWithoutPollute(this)' => static fn () => $scope->processAlwaysIterableForeachScopeWithoutPollute($scope),
				'processAlwaysIterableForeachScopeWithoutPollute(other)' => static fn () => $scope->processAlwaysIterableForeachScopeWithoutPollute($other),
				'generalizeWith(this)' => static fn () => $scope->generalizeWith($scope),
				'generalizeWith(other)' => static fn () => $scope->generalizeWith($other),
				'generalizeWith(other, nothing writable)' => static fn () => $scope->generalizeWith($other, []),
				'generalizeWith(other, writable)' => static fn () => $scope->generalizeWith($other, ['g' => true, 'a' => true, 'out' => true]),
				'generalizeWith(other, reversed)' => static fn () => $other->generalizeWith($scope),
				// addTemplateArgumentConstraints() answers with $this for an
				// empty (or null) set, so only a non-empty one makes
				// generalizeWith()'s second half observable
				'generalizeWith(other with constraints)' => static fn () => $scope->generalizeWith($other->withTemplateArgumentConstraints($sfNonEmptyConstraints)),
				'equals(this)' => static fn () => $scope->equals($scope),
				'equals(other)' => static fn () => $scope->equals($other),
				'equals(other, reversed)' => static fn () => $other->equals($scope),
				// a clone that differs from the scope in exactly one of the
				// three things equals() compares
				'equals(clone)' => static fn () => $scope->equals(clone $scope),
				'equals(clone, native types only)' => static function () use ($scope, $sfRestore) {
					$clone = clone $scope;
					$sfRestore($clone, ['nativeExpressionTypes' => []]);

					return [$scope->equals($clone), $clone->equals($scope)];
				},
				'equals(clone, one certainty)' => static function () use ($scope, $side, $sfProp, $sfRestore) {
					$clone = clone $scope;
					$types = $sfProp($clone, 'expressionTypes');
					if ($types === []) {
						return 'no expression types';
					}
					$key = array_key_first($types);
					$holder = $types[$key];
					$weaker = $holder->getCertainty()->yes();
					$types[$key] = $side === 'native'
						? new \PHPStanTurbo\ExpressionTypeHolder($holder->getExpr(), $holder->getType(), $weaker ? \PHPStanTurbo\TrinaryLogic::createMaybe() : \PHPStanTurbo\TrinaryLogic::createYes())
						: new \PHPStan\Analyser\ExpressionTypeHolder($holder->getExpr(), $holder->getType(), $weaker ? \PHPStan\TrinaryLogic::createMaybe() : \PHPStan\TrinaryLogic::createYes());
					$sfRestore($clone, ['expressionTypes' => $types, 'nativeExpressionTypes' => $types]);

					return [$scope->equals($clone), $clone->equals($scope)];
				},
				'equals(clone, one conditional holder fewer)' => static function () use ($scope, $sfProp, $sfRestore) {
					$clone = clone $scope;
					$conditionals = $sfProp($clone, 'conditionalExpressions');
					foreach ($conditionals as $key => $holders) {
						if (count($holders) <= 1) {
							continue;
						}
						array_pop($conditionals[$key]);
						$sfRestore($clone, ['conditionalExpressions' => $conditionals]);

						return [$scope->equals($clone), $clone->equals($scope)];
					}

					return 'no multi-holder conditional';
				},
				'debug' => static fn () => $scope->debug(),
				'canAccessProperty(public)' => static fn () => $scope->canAccessProperty($sfPublicProperty),
				'canAccessProperty(private, Holder)' => static fn () => $scope->canAccessProperty($sfPrivateProperty),
				'canReadProperty(public)' => static fn () => $scope->canReadProperty($sfPublicProperty),
				'canReadProperty(protected, Base)' => static fn () => $scope->canReadProperty($sfProtectedProperty),
				// a protected member of a subclass: only the last arm of the
				// closure (the declaring class being a subclass of the scope's)
				// answers for a scope inside Base
				'canReadProperty(protected, Child)' => static fn () => $scope->canReadProperty($sfChildProtectedProperty),
				'canWriteProperty(public)' => static fn () => $scope->canWriteProperty($sfPublicProperty),
				'canWriteProperty(protected, Base)' => static fn () => $scope->canWriteProperty($sfProtectedProperty),
				'canWriteProperty(private set)' => static fn () => $scope->canWriteProperty($sfPrivateSetProperty),
				'canCallMethod(public)' => static fn () => $scope->canCallMethod($sfPublicMethod),
				'canCallMethod(protected, Base)' => static fn () => $scope->canCallMethod($sfProtectedMethod),
				'canCallMethod(protected, Child)' => static fn () => $scope->canCallMethod($sfChildProtectedMethod),
				'canCallMethod(private, Base)' => static fn () => $scope->canCallMethod($sfPrivateMethod),
				'canAccessConstant(public)' => static fn () => $scope->canAccessConstant($sfPublicConstant),
				'canAccessConstant(protected)' => static fn () => $scope->canAccessConstant($sfProtectedConstant),
				'canAccessConstant(private)' => static fn () => $scope->canAccessConstant($sfPrivateConstant),
				'canAccessConstant(protected, Child)' => static fn () => $scope->canAccessConstant($sfChildProtectedConstant),
				'filterTypeWithMethod(union, read)' => static fn () => $scope->filterTypeWithMethod($holderUnion, 'read'),
				'filterTypeWithMethod(union, nope)' => static fn () => $scope->filterTypeWithMethod($holderUnion, 'nope'),
				'filterTypeWithMethod(object, read)' => static fn () => $scope->filterTypeWithMethod($holderType, 'read'),
				'filterTypeWithMethod(object, nope)' => static fn () => $scope->filterTypeWithMethod($holderType, 'nope'),
				'filterTypeWithMethod(scalar union)' => static fn () => $scope->filterTypeWithMethod($scalarUnion, 'read'),
				'filterTypeWithMethod(maybe union)' => static fn () => $scope->filterTypeWithMethod($maybeUnion, 'read'),
				'getPropertyReflection(maybe union)' => static fn () => $reflectionDigest($scope->getPropertyReflection($maybeUnion, 'name')),
				'getInstancePropertyReflection(maybe union)' => static fn () => $reflectionDigest($scope->nativeGetInstancePropertyReflection($maybeUnion, 'name')),
				'getMethodReflection(union, read)' => static fn () => $reflectionDigest($scope->nativeGetMethodReflection($holderUnion, 'read')),
				'getMethodReflection(object, read)' => static fn () => $reflectionDigest($scope->nativeGetMethodReflection($holderType, 'read')),
				'getMethodReflection(object, nope)' => static fn () => $reflectionDigest($scope->nativeGetMethodReflection($holderType, 'nope')),
				'getNakedMethod(union, read)' => static fn () => $reflectionDigest($scope->getNakedMethod($holderUnion, 'read')),
				'getNakedMethod(object, nope)' => static fn () => $reflectionDigest($scope->getNakedMethod($holderType, 'nope')),
				'getPropertyReflection(union, name)' => static fn () => $reflectionDigest($scope->getPropertyReflection($holderUnion, 'name')),
				'getPropertyReflection(object, name)' => static fn () => $reflectionDigest($scope->getPropertyReflection($holderType, 'name')),
				'getPropertyReflection(object, nope)' => static fn () => $reflectionDigest($scope->getPropertyReflection($holderType, 'nope')),
				'getInstancePropertyReflection(union, name)' => static fn () => $reflectionDigest($scope->nativeGetInstancePropertyReflection($holderUnion, 'name')),
				'getInstancePropertyReflection(object, name)' => static fn () => $reflectionDigest($scope->nativeGetInstancePropertyReflection($holderType, 'name')),
				'getInstancePropertyReflection(object, nope)' => static fn () => $reflectionDigest($scope->nativeGetInstancePropertyReflection($holderType, 'nope')),
				'getStaticPropertyReflection(union, name)' => static fn () => $reflectionDigest($scope->nativeGetStaticPropertyReflection($holderUnion, 'name')),
				'getStaticPropertyReflection(object, nope)' => static fn () => $reflectionDigest($scope->nativeGetStaticPropertyReflection($holderType, 'nope')),
				'getConstantReflection(union, PUBLIC_CONST)' => static fn () => $reflectionDigest($scope->getConstantReflection($holderUnion, 'PUBLIC_CONST')),
				'getConstantReflection(object, nope)' => static fn () => $reflectionDigest($scope->getConstantReflection($holderType, 'NOPE_CONST')),
				'getConstantExplicitTypeFromConfig(PHP_EOL)' => static fn () => $scope->getConstantExplicitTypeFromConfig('PHP_EOL', $string),
				'getConstantExplicitTypeFromConfig(unknown)' => static fn () => $scope->getConstantExplicitTypeFromConfig('NOPE_XYZ', $int),
				'getConstantExplicitTypeFromConfig(dynamic, constant value)' => static fn () => $scope->getConstantExplicitTypeFromConfig('PHP_VERSION', $type(\PHPStan\Type\Constant\ConstantStringType::class, '8.5.0')),
				'getConstantExplicitTypeFromConfig(unknown, constant value)' => static fn () => $scope->getConstantExplicitTypeFromConfig('NOPE_XYZ', $type(\PHPStan\Type\Constant\ConstantStringType::class, '8.5.0')),
				'getIterableKeyType(union)' => static fn () => $scope->getIterableKeyType($iterableUnion),
				'getIterableValueType(union)' => static fn () => $scope->getIterableValueType($iterableUnion),
				'getIterableKeyType(non-iterable union)' => static fn () => $scope->getIterableKeyType($scalarUnion),
				'getIterableValueType(non-iterable union)' => static fn () => $scope->getIterableValueType($scalarUnion),
				'getIterableKeyType(array)' => static fn () => $scope->getIterableKeyType($arrayType),
				'getIterableValueType(array)' => static fn () => $scope->getIterableValueType($arrayType),
				'node callback' => static function () use ($scope, $sfHarness, $sfProp, $sfRestore) {
					$saved = $sfProp($scope, 'nodeCallback');
					$seen = [];
					$callback = static function (\PhpParser\Node $node, object $answerer) use (&$seen, $sfHarness): void {
						$seen[] = [$sfHarness->className($node), $sfHarness->className($answerer)];
					};
					try {
						$sfRestore($scope, ['nodeCallback' => $callback]);
						$scope->invokeNodeCallback(new \PhpParser\Node\Expr\Variable('cb'));
						$scope->emitCollectedData('ScopeFamily\\SomeCollector', ['x' => 1]);

						return $seen;
					} finally {
						$sfRestore($scope, ['nodeCallback' => $saved]);
					}
				},
				'invokeNodeCallback(no callback)' => static function () use ($scope, $sfProp, $sfRestore) {
					$saved = $sfProp($scope, 'nodeCallback');
					try {
						$sfRestore($scope, ['nodeCallback' => null]);
						$scope->invokeNodeCallback(new \PhpParser\Node\Expr\Variable('cb'));

						return 'no throw';
					} finally {
						$sfRestore($scope, ['nodeCallback' => $saved]);
					}
				},
				'emitCollectedData(no callback)' => static function () use ($scope, $sfProp, $sfRestore) {
					$saved = $sfProp($scope, 'nodeCallback');
					try {
						$sfRestore($scope, ['nodeCallback' => null]);
						$scope->emitCollectedData('ScopeFamily\\SomeCollector', null);

						return 'no throw';
					} finally {
						$sfRestore($scope, ['nodeCallback' => $saved]);
					}
				},
			];

			// the factory builds a real scope of this side for the chaining runs:
			// the chained bodies derive scope from scope, and the twin's
			// ScopeOps::scopeWith() reaches the factory (duplicateWith) where
			// the native one clones — the two agree on the scope they produce,
			// not on how many create() calls it took, so the resulting scope's
			// state is the observable here, not the argument lists
			// always the PHP-side class: InternalScopeFactory::create() is typed
			// with the twin's class name, which the prefixed native class is not
			// — so the native side's chain continues through PHP scopes from the
			// first create() on (the same barrier toWalkScope() crosses)
			$factory->builder = static function (array $createArgs) use ($args, $factory, $sfWalkScope): \PHPStan\Analyser\MutatingScope {
				$ctor = $args;
				$ctor['scopeFactory'] = $factory;
				foreach ([
					'context', 'declareStrictTypes', 'function', 'namespace', 'expressionTypes', 'nativeExpressionTypes',
					'conditionalExpressions', 'inClosureBindScopeClasses', 'anonymousFunctionReflection', 'inFirstLevelStatement',
					'currentlyAssignedExpressions', 'currentlyAllowedUndefinedExpressions', 'inFunctionCallsStack',
					'afterExtractCall', 'parentScope', 'nativeTypesPromoted', 'templateArgumentFrame', 'templateArgumentConstraints',
				] as $i => $name) {
					$ctor[$name] = $createArgs[$i];
				}
				// the one native collaborator a chaining body builds itself: the
				// twin's constructor is typed with the real class name
				if ($ctor['anonymousFunctionReflection'] instanceof \PHPStanTurbo\ClosureType) {
					$ctor['anonymousFunctionReflection'] = new \PHPStan\Type\ClosureType();
				}
				// $this as the new scope's parent: the twin's typed parameter
				// rejects the prefixed class, and the walk scope stands in (the
				// digest renders a parent by its class name)
				if ($ctor['parentScope'] instanceof \PHPStanTurbo\MutatingScope) {
					$ctor['parentScope'] = $sfWalkScope;
				}
				$built = new PhpScope(...array_values($ctor));
				$built->inner = $sfWalkScope;

				return $built;
			};
			// the scope under test is restored too from the narrowing runs on: the
			// narrowing batch's conditional bookkeeping writes into it, and
			// exitFirstLevelStatements() memoizes on it
			$sfRestoredScopeProps = array_merge($sfMutableProps, ['scopeOutOfFirstLevelStatement']);
			try {
				foreach ($chainingRuns as $label => $fn) {
					$state = $sfSnapshot($dummy);
					$observe($label, static fn () => $sfScopeDigest($fn(), $scope, $dummy));
					$sfRestore($dummy, $state);
				}
				foreach ($narrowingRuns as $label => $fn) {
					$state = $sfSnapshot($dummy);
					$scopeState = $sfSnapshot($scope, $sfRestoredScopeProps);
					$observe($label, static fn () => $sfScopeDigest($fn(), $scope, $dummy));
					$sfRestore($dummy, $state);
					$sfRestore($scope, $scopeState);
				}
				foreach ($closureLoopRuns as $label => $fn) {
					$state = $sfSnapshot($dummy);
					$scopeState = $sfSnapshot($scope, $sfRestoredScopeProps);
					$observe($label, static fn () => $sfScopeDigest($fn(), $scope, $dummy));
					$sfRestore($dummy, $state);
					$sfRestore($scope, $scopeState);
				}
			} finally {
				$factory->builder = null;
			}
		} finally {
			$stack->pop();
		}
	}
}

if (getenv('SF_DUMP_LABEL') !== false) {
	$sfDumpLabel = getenv('SF_DUMP_LABEL');
	foreach ($sfObservations['php'] as $sfId => $sfPhpObservations) {
		if (!array_key_exists($sfDumpLabel, $sfPhpObservations)) {
			continue;
		}
		echo "scope $sfId\n  php:    ", json_encode($sfPhpObservations[$sfDumpLabel]), "\n  native: ", json_encode($sfObservations['native'][$sfId][$sfDumpLabel] ?? '<missing>'), "\n";
	}
}
foreach ($sfObservations['php'] as $sfId => $sfPhpObservations) {
	$sfNativeObservations = $sfObservations['native'][$sfId] ?? [];
	foreach ($sfPhpObservations as $label => $expected) {
		$actual = array_key_exists($label, $sfNativeObservations) ? $sfNativeObservations[$label] : '<missing>';
		if ($actual === Harness::BARRIER && $expected !== Harness::BARRIER) {
			$sfBarrierHits++;
			if (getenv('SF_BARRIER_DEBUG') !== false) {
				echo "BARRIER: (scope $sfId) $label\n";
			}
			continue;
		}
		check($expected === $actual, sprintf('MutatingScope parity (scope %d) %s: %s vs %s', $sfId, $label, json_encode($expected), json_encode($actual)));
	}
	check(array_keys($sfPhpObservations) === array_keys($sfNativeObservations), "MutatingScope parity (scope $sfId): the same observations on both sides");
}
$sfObservationCount = array_sum(array_map('count', $sfObservations['php']));
check($sfObservationCount > 2000, "scope-family: enough observations ($sfObservationCount over $sfSampleCount scopes)");
// the barrier is a known cost of the prefix; it must stay a small share
check($sfBarrierHits < $sfObservationCount / 20, "scope-family: the prefix type barrier cut $sfBarrierHits of $sfObservationCount observations short");

if (isset($scopeFamilyStandalone)) {
	echo $failures === 0 ? "ALL OK ($sfObservationCount observations over $sfSampleCount scopes, $sfBarrierHits barrier hits skipped)\n" : "$failures FAILURES\n";
	exit($failures === 0 ? 0 : 1);
}

}

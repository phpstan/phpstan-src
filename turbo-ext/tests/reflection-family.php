<?php declare(strict_types = 1);

/**
 * Differential test of the native ClassReflection against the PHP twin,
 * under the prefixed activation: PHPStanTurbo\ClassReflection is declared
 * next to PHPStan\Reflection\ClassReflection (which keeps its real name
 * and its PHP body there), so both sides live in one process. In a
 * production run the native class carries the real name instead.
 *
 * Real class reflections come from the DI container's reflection provider
 * over reflection-family-fixture.php (interfaces, traits, enums, generics
 * with @extends/@implements, abstract/final, attributes, inherited
 * members, readonly, legacy constructor) and a handful of built-in classes
 * with stubs; generic instances and final-keyword overrides are derived
 * through withTypes()/withVariances()/asFinal(). Every sample is rebuilt
 * from its own constructor arguments (read by reflection) three times:
 * the PHP twin under test, a delegate twin (which the duck collaborators
 * below hand to the PHP-typed extension points in place of the native
 * object), and the native class itself.
 *
 * The twin is final and every PHP collaborator types its parameters with
 * it, so the native object cannot be handed to them under the prefix. The
 * native side therefore gets duck-typed collaborators: the extension
 * registry provider is a recorder-free stand-in whose extensions forward
 * every call to the real extension with the native $this swapped for the
 * delegate twin, and the class-map key of the static
 * UniversalObjectCratesClassReflectionExtension::isUniversalObjectCrate()
 * is remapped to a stand-in doing the same for the duration of the test.
 * Everything else (the reflection provider's answers, the adapter, the
 * tags, the Type kernel) is shared as it is; results are compared through
 * a side-independent normalization, and the memo slots of both objects
 * are compared at the end of each sample.
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
	$reflectionFamilyStandalone = true;
}

}

namespace ReflectionFamily {

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\Php\UniversalObjectCratesClassReflectionExtension;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * The native $this of a sample maps to its delegate twin: the PHP
 * collaborators type their ClassReflection parameters with the final twin
 * class, which the native object is not under the prefix.
 */
final class Twins
{

	/** @var array<int, ClassReflection> */
	private static array $byNativeId = [];

	public static function register(object $native, ClassReflection $delegate): void
	{
		self::$byNativeId[spl_object_id($native)] = $delegate;
	}

	public static function of(mixed $classReflection): mixed
	{
		if ($classReflection instanceof \PHPStanTurbo\ClassReflection) {
			return self::$byNativeId[spl_object_id($classReflection)] ?? $classReflection;
		}

		return $classReflection;
	}

}

/**
 * A class reflection extension of any kind, called on the delegate twin
 * (the methods are declared: the native side resolves them through the
 * class's function table, never through __call()).
 */
final class DuckExtension
{

	public function __construct(private object $real)
	{
	}

	public function hasMethod(mixed $classReflection, string $methodName): bool
	{
		return $this->real->hasMethod(Twins::of($classReflection), $methodName);
	}

	public function getMethod(mixed $classReflection, string $methodName): mixed
	{
		return $this->real->getMethod(Twins::of($classReflection), $methodName);
	}

	public function hasNativeMethod(mixed $classReflection, string $methodName): bool
	{
		return $this->real->hasNativeMethod(Twins::of($classReflection), $methodName);
	}

	public function getNativeMethod(mixed $classReflection, string $methodName): mixed
	{
		return $this->real->getNativeMethod(Twins::of($classReflection), $methodName);
	}

	public function hasProperty(mixed $classReflection, string $propertyName): bool
	{
		return $this->real->hasProperty(Twins::of($classReflection), $propertyName);
	}

	public function getProperty(mixed $classReflection, string $propertyName, mixed ...$rest): mixed
	{
		return $this->real->getProperty(Twins::of($classReflection), $propertyName, ...$rest);
	}

	public function getNativeProperty(mixed $classReflection, string $propertyName): mixed
	{
		return $this->real->getNativeProperty(Twins::of($classReflection), $propertyName);
	}

	public function hasInstanceProperty(mixed $classReflection, string $propertyName): bool
	{
		return $this->real->hasInstanceProperty(Twins::of($classReflection), $propertyName);
	}

	public function getInstanceProperty(mixed $classReflection, string $propertyName): mixed
	{
		return $this->real->getInstanceProperty(Twins::of($classReflection), $propertyName);
	}

	public function hasStaticProperty(mixed $classReflection, string $propertyName): bool
	{
		return $this->real->hasStaticProperty(Twins::of($classReflection), $propertyName);
	}

	public function getStaticProperty(mixed $classReflection, string $propertyName): mixed
	{
		return $this->real->getStaticProperty(Twins::of($classReflection), $propertyName);
	}

	public function supports(mixed $classReflection): bool
	{
		return $this->real->supports(Twins::of($classReflection));
	}

	/** @return array<\PHPStan\Type\Type> */
	public function getAllowedSubTypes(mixed $classReflection): array
	{
		return $this->real->getAllowedSubTypes(Twins::of($classReflection));
	}

}

final class DuckRegistry
{

	public function __construct(private \PHPStan\Reflection\ClassReflectionExtensionRegistry $real)
	{
	}

	public function getPhpClassReflectionExtension(): DuckExtension
	{
		return new DuckExtension($this->real->getPhpClassReflectionExtension());
	}

	/** @return list<DuckExtension> */
	public function getPropertiesClassReflectionExtensions(): array
	{
		return array_map(static fn (object $e): DuckExtension => new DuckExtension($e), $this->real->getPropertiesClassReflectionExtensions());
	}

	/** @return list<DuckExtension> */
	public function getMethodsClassReflectionExtensions(): array
	{
		return array_map(static fn (object $e): DuckExtension => new DuckExtension($e), $this->real->getMethodsClassReflectionExtensions());
	}

	public function getRequireExtendsPropertyClassReflectionExtension(): DuckExtension
	{
		return new DuckExtension($this->real->getRequireExtendsPropertyClassReflectionExtension());
	}

	public function getRequireExtendsMethodsClassReflectionExtension(): DuckExtension
	{
		return new DuckExtension($this->real->getRequireExtendsMethodsClassReflectionExtension());
	}

	/** @return list<DuckExtension> */
	public function getAllowedSubTypesClassReflectionExtensions(): array
	{
		return array_map(static fn (object $e): DuckExtension => new DuckExtension($e), $this->real->getAllowedSubTypesClassReflectionExtensions());
	}

}

/** The native side's classReflectionExtensionRegistryProvider. */
final class DuckRegistryProvider
{

	public function __construct(private \PHPStan\DependencyInjection\Reflection\ClassReflectionExtensionRegistryProvider $real)
	{
	}

	public function getRegistry(): DuckRegistry
	{
		return new DuckRegistry($this->real->getRegistry());
	}

}

/** The class-map stand-in for the universal object crates check. */
final class DuckUniversalObjectCrates
{

	public static function isUniversalObjectCrate(ReflectionProvider $reflectionProvider, mixed $classReflection): bool
	{
		return UniversalObjectCratesClassReflectionExtension::isUniversalObjectCrate($reflectionProvider, Twins::of($classReflection));
	}

}

/**
 * The class-map stand-in for ParserNodeTypeToPHPStanType: the native
 * TypehintHelper hands its resolve() the selfClass it was called with,
 * which is the native $this for a class constant's native type.
 */
final class DuckParserNodeTypeToPHPStanType
{

	public static function resolve($type, mixed $classReflection): \PHPStan\Type\Type
	{
		return \PHPStan\Type\ParserNodeTypeToPHPStanType::resolve($type, Twins::of($classReflection));
	}

}

/**
 * The native side's phpDocInheritanceResolver: resolvePhpDocForConstant()
 * takes the declaring class, which is the native $this for a constant the
 * class itself declares.
 */
final class DuckPhpDocInheritanceResolver
{

	public function __construct(private \PHPStan\PhpDoc\PhpDocInheritanceResolver $real)
	{
	}

	public function resolvePhpDocForConstant(mixed $declaringClass, string $constantName, ?\PHPStan\PhpDoc\ResolvedPhpDocBlock $currentResolvedPhpDoc): ?\PHPStan\PhpDoc\ResolvedPhpDocBlock
	{
		return $this->real->resolvePhpDocForConstant(Twins::of($declaringClass), $constantName, $currentResolvedPhpDoc);
	}

}

/**
 * The native side's classReflectionFactory: withTypes()/withVariances()
 * hand it the maps their native bodies build, which are the native
 * TemplateTypeMap / TemplateTypeVarianceMap under the prefix (the twin's
 * are the PHP ones its create() types). Rebuilding from getTypes() /
 * getVariances() is exact for these two call sites — the maps the twin
 * passes never carry lower-bound types.
 */
final class DuckClassReflectionFactory
{

	public function __construct(private \PHPStan\Reflection\ClassReflectionFactory $real)
	{
	}

	public function create(
		string $displayName,
		\ReflectionClass $reflection,
		?string $anonymousFilename,
		mixed $resolvedTemplateTypeMap,
		?\Closure $stubPhpDocBlockCallback,
		?string $extraCacheKey = null,
		mixed $resolvedCallSiteVarianceMap = null,
		?bool $finalByKeywordOverride = null,
	): ClassReflection
	{
		if ($resolvedTemplateTypeMap instanceof \PHPStanTurbo\TemplateTypeMap) {
			$resolvedTemplateTypeMap = new TemplateTypeMap($resolvedTemplateTypeMap->getTypes());
		}
		if ($resolvedCallSiteVarianceMap instanceof \PHPStanTurbo\TemplateTypeVarianceMap) {
			$resolvedCallSiteVarianceMap = new TemplateTypeVarianceMap($resolvedCallSiteVarianceMap->getVariances());
		}

		return $this->real->create($displayName, $reflection, $anonymousFilename, $resolvedTemplateTypeMap, $stubPhpDocBlockCallback, $extraCacheKey, $resolvedCallSiteVarianceMap, $finalByKeywordOverride);
	}

}

/** A reflected type rendered for an eval()'d declaration: class names fully qualified, self/static the declaring class. */
function qualifyType(string $type, string $selfClass = ClassReflection::class): string
{
	$builtin = ['string', 'int', 'bool', 'array', 'void', 'null', 'mixed', 'float', 'callable', 'iterable', 'object', 'never', 'false', 'true'];
	return preg_replace_callback('~[A-Za-z_][A-Za-z0-9_\\\\]*~', static function (array $m) use ($builtin, $selfClass): string {
		if (in_array(strtolower($m[0]), $builtin, true)) {
			return $m[0];
		}
		if (in_array(strtolower($m[0]), ['self', 'static'], true)) {
			return '\\' . $selfClass;
		}
		return '\\' . $m[0];
	}, $type);
}

/**
 * Declares a stand-in for a class-map class the native bodies instantiate
 * with the native $this: it builds the real object with the class
 * reflections of $swapIndexes swapped for their twins and delegates every
 * public method to it, implementing the real class's interfaces. The
 * harness normalizes its name back to the real class, so the observations
 * stay side-independent.
 *
 * @param list<int> $swapIndexes constructor positions holding a class reflection
 */
function declareStandIn(string $realClass, string $standInName, array $swapIndexes): void
{
	$real = new \ReflectionClass($realClass);
	$params = [];
	$args = [];
	foreach ($real->getMethod('__construct')->getParameters() as $i => $parameter) {
		$swap = in_array($i, $swapIndexes, true);
		$param = $swap ? 'mixed ' : ($parameter->hasType() ? qualifyType((string) $parameter->getType(), $realClass) . ' ' : '');
		$param .= '$' . $parameter->getName();
		if ($parameter->isDefaultValueAvailable()) {
			$param .= ' = ' . var_export($parameter->getDefaultValue(), true);
		}
		$params[] = $param;
		$args[] = $swap ? sprintf('\ReflectionFamily\Twins::of($%s)', $parameter->getName()) : '$' . $parameter->getName();
	}

	$methods = '';
	foreach ($real->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
		if ($method->isStatic() || $method->isConstructor()) {
			continue;
		}
		$methodParams = [];
		$methodArgs = [];
		foreach ($method->getParameters() as $parameter) {
			$param = $parameter->hasType() ? qualifyType((string) $parameter->getType(), $realClass) . ' ' : '';
			$param .= $parameter->isVariadic() ? '...' : '';
			$param .= '$' . $parameter->getName();
			if ($parameter->isDefaultValueAvailable()) {
				$param .= ' = ' . var_export($parameter->getDefaultValue(), true);
			}
			$methodParams[] = $param;
			$methodArgs[] = ($parameter->isVariadic() ? '...' : '') . '$' . $parameter->getName();
		}
		$returnType = $method->hasReturnType() ? (string) $method->getReturnType() : '';
		$methods .= sprintf(
			"\tpublic function %s(%s)%s { %s\$this->real->%s(%s); }\n",
			$method->getName(),
			implode(', ', $methodParams),
			$returnType === '' ? '' : ': ' . qualifyType($returnType, $realClass),
			$returnType === 'void' ? '' : 'return ',
			$method->getName(),
			implode(', ', $methodArgs),
		);
	}

	$interfaces = $real->getInterfaceNames();
	eval(sprintf(
		"namespace ReflectionFamily; final class %s%s {\n\tprivate \\%s \$real;\n\tpublic function __construct(%s) { \$this->real = new \\%s(%s); }\n%s}",
		$standInName,
		$interfaces === [] ? '' : ' implements \\' . implode(', \\', $interfaces),
		$realClass,
		implode(', ', $params),
		$realClass,
		implode(', ', $args),
		$methods,
	));
}

declareStandIn(\PHPStan\Reflection\EnumCaseReflection::class, 'DuckEnumCaseReflection', [0]);
declareStandIn(\PHPStan\Reflection\RealClassClassConstantReflection::class, 'DuckRealClassClassConstantReflection', [1]);

final class Harness
{

	/** @var array<string, string> */
	private array $classNorm;

	/** @param array<string, array{turboClass: string}> $manifest */
	public function __construct(array $manifest, private \PHPStan\PhpDoc\TypeNodeResolver $typeNodeResolver)
	{
		$this->classNorm = [];
		foreach ($manifest as $shadowedClass => $entry) {
			$this->classNorm[$entry['turboClass']] = $shadowedClass;
		}
		$this->classNorm[\PHPStanTurbo\ClassReflection::class] = ClassReflection::class;
		$this->classNorm[DuckEnumCaseReflection::class] = \PHPStan\Reflection\EnumCaseReflection::class;
		$this->classNorm[DuckRealClassClassConstantReflection::class] = \PHPStan\Reflection\RealClassClassConstantReflection::class;
	}


	public function className(object $object): string
	{
		return strtr(get_class($object), $this->classNorm);
	}

	/** The 19 constructor arguments of a class reflection, by parameter name. */
	public function constructorArgs(ClassReflection $classReflection): array
	{
		$args = [];
		$reflection = new \ReflectionClass(ClassReflection::class);
		foreach ($reflection->getMethod('__construct')->getParameters() as $parameter) {
			$args[$parameter->getName()] = $reflection->getProperty($parameter->getName())->getValue($classReflection);
		}

		return $args;
	}

	/** A comparable, side-independent rendering of any value. */
	public function norm(mixed $value): mixed
	{
		if (is_array($value)) {
			$out = [];
			foreach ($value as $k => $v) {
				$out[$k] = $this->norm($v);
			}
			return $out;
		}
		if (!is_object($value)) {
			return $value;
		}
		if ($value instanceof ClassReflection || $value instanceof \PHPStanTurbo\ClassReflection) {
			return ['R', $this->className($value), $value->getDisplayName()];
		}
		if ($value instanceof \ReflectionClass || $value instanceof \ReflectionMethod || $value instanceof \ReflectionProperty || $value instanceof \ReflectionClassConstant) {
			return ['B', get_class($value), $value->getName()];
		}
		if ($value instanceof MethodReflection) {
			return ['M', $this->className($value), $value->getName(), $value->getDeclaringClass()->getName()];
		}
		if ($value instanceof PropertyReflection) {
			return ['P', $this->className($value), $value->getDeclaringClass()->getName(), $value->isStatic(), $value->isPrivate()];
		}
		if ($value instanceof \PHPStan\Reflection\ClassConstantReflection) {
			return ['K', $this->className($value), $value->getName(), $value->getDeclaringClass()->getName()];
		}
		if ($value instanceof Type) {
			return ['Y', $this->className($value), $value->describe(VerbosityLevel::precise())];
		}
		if ($value instanceof TemplateTypeMap || $value instanceof \PHPStanTurbo\TemplateTypeMap) {
			return ['TM', $this->norm($value->getTypes())];
		}
		if ($value instanceof TemplateTypeVarianceMap || $value instanceof \PHPStanTurbo\TemplateTypeVarianceMap) {
			return ['VM', array_map(static fn (TemplateTypeVariance $v): string => $v->describe(), $value->getVariances())];
		}
		if ($value instanceof \PHPStan\PhpDoc\ResolvedPhpDocBlock) {
			return ['D', $this->className($value)];
		}
		if ($this->className($value) === \PHPStan\Reflection\EnumCaseReflection::class) {
			return ['EC', $value->getName(), $value->getDeclaringEnum()->getName(), $this->norm($value->getBackingValueType()), $value->isDeprecated()->describe(), $value->getDeprecatedDescription(), $this->norm($value->getAttributes())];
		}
		if ($value instanceof \PHPStan\Reflection\AttributeReflection) {
			return ['A', $value->getName(), $this->norm($value->getArgumentTypes())];
		}
		if ($value instanceof \PHPStan\Type\TypeAlias) {
			return ['TA', $this->norm($value->resolve($this->typeNodeResolver))];
		}
		if ($value instanceof \PHPStan\PhpDoc\Tag\TemplateTag) {
			return ['TT', $value->getName(), $this->norm($value->getBound()), $this->norm($value->getDefault()), $value->getVariance()->describe()];
		}
		if ($value instanceof \PHPStan\PhpDoc\Tag\PropertyTag) {
			return ['PT', $this->norm($value->getReadableType()), $this->norm($value->getWritableType()), $value->isReadable(), $value->isWritable()];
		}
		if ($value instanceof \PHPStan\PhpDoc\Tag\MethodTag) {
			return ['MT', $this->norm($value->getReturnType()), $value->isStatic(), $this->norm(array_keys($value->getParameters()))];
		}
		if ($value instanceof \Closure) {
			return ['F'];
		}
		if ($value instanceof \Throwable) {
			return ['E', $this->className($value), strtr($value->getMessage(), $this->classNorm)];
		}
		if (method_exists($value, 'getType')) {
			return ['G', $this->className($value), $this->norm($value->getType())];
		}

		return ['O', $this->className($value)];
	}

}

}

namespace {

use ReflectionFamily\DuckRegistryProvider;
use ReflectionFamily\DuckUniversalObjectCrates;
use ReflectionFamily\Harness;
use ReflectionFamily\Twins;

$rfManifest = json_decode(file_get_contents(dirname(__DIR__, 2) . '/vendor/turbo-shadowed-classes.json'), true, 8, JSON_THROW_ON_ERROR);
$rfClassMap = require dirname(__DIR__, 2) . '/vendor/turbo-class-map.php';

// a container of its own: the fixture must be an analysed path for the
// reflection provider to find its classes
$rfFile = __DIR__ . '/reflection-family-fixture.php';
$rfContainerFactory = new \PHPStan\DependencyInjection\ContainerFactory(dirname(__DIR__, 2));
$rfContainer = $rfContainerFactory->create(sys_get_temp_dir() . '/phpstan-turbo-smoke-reflection', [$rfContainerFactory->getConfigDirectory() . '/config.level8.neon'], [$rfFile]);
$rfHarness = new Harness($rfManifest, $rfContainer->getByType(\PHPStan\PhpDoc\TypeNodeResolver::class));
$rfReflectionProvider = $rfContainer->getByType(\PHPStan\Reflection\ReflectionProvider::class);
$rfScopeFactory = $rfContainer->getByType(\PHPStan\Analyser\ScopeFactory::class);
$rfRegistryProvider = $rfContainer->getByType(\PHPStan\DependencyInjection\Reflection\ClassReflectionExtensionRegistryProvider::class);

// the class-map classes the native bodies hand the native $this to: routed
// to the stand-ins for the duration of the test
\PHPStanTurbo\Runtime::configure([
	'universalObjectCratesClassReflectionExtension' => DuckUniversalObjectCrates::class,
	'enumCaseReflection' => \ReflectionFamily\DuckEnumCaseReflection::class,
	'realClassClassConstantReflection' => \ReflectionFamily\DuckRealClassClassConstantReflection::class,
	'parserNodeTypeToPHPStanType' => \ReflectionFamily\DuckParserNodeTypeToPHPStanType::class,
]);

// ---- the samples: the fixture's classes, built-ins with stubs, derived reflections ----
/** @var array<string, \PHPStan\Reflection\ClassReflection> $rfSamples */
$rfSamples = [];
foreach ([
	'ReflectionFamilyFixture\Shape', 'ReflectionFamilyFixture\HasName', 'ReflectionFamilyFixture\Labeled', 'ReflectionFamilyFixture\Repo', 'ReflectionFamilyFixture\RequiresBase',
	'ReflectionFamilyFixture\Greets', 'ReflectionFamilyFixture\Nested',
	'ReflectionFamilyFixture\Base', 'ReflectionFamilyFixture\Circle', 'ReflectionFamilyFixture\Plain', 'ReflectionFamilyFixture\Legacy',
	'ReflectionFamilyFixture\Dynamic', 'ReflectionFamilyFixture\DynamicChild', 'ReflectionFamilyFixture\Magic', 'ReflectionFamilyFixture\Frozen',
	'ReflectionFamilyFixture\Suit', 'ReflectionFamilyFixture\Pure',
	'ReflectionFamilyFixture\Box', 'ReflectionFamilyFixture\CircleBox', 'ReflectionFamilyFixture\BoxOf', 'ReflectionFamilyFixture\CircleRepo', 'ReflectionFamilyFixture\ShapeRepo',
	'ReflectionFamilyFixture\WithProps', 'ReflectionFamilyFixture\Marker', 'ReflectionFamilyFixture\DocFinal', 'ReflectionFamilyFixture\Old',
	'ReflectionFamilyFixture\Aliases', 'ReflectionFamilyFixture\ImportsAliases', 'ReflectionFamilyFixture\HasConstants', 'ReflectionFamilyFixture\ConstantsHolder',
	'ReflectionFamilyFixture\Mixed_', 'ReflectionFamilyFixture\MixinHolder', 'ReflectionFamilyFixture\SealedBase', 'ReflectionFamilyFixture\ImmutableChild',
	'ReflectionFamilyFixture\DefaultFlags', 'ReflectionFamilyFixture\NamedFlags', 'ReflectionFamilyFixture\Decorated', 'ReflectionFamilyFixture\Cards',
	'ReflectionFamilyFixture\Documented', 'ReflectionFamilyFixture\UsesDocumented',
	'ArrayObject', 'ArrayAccess', 'Countable', 'Traversable', 'Iterator', 'IteratorAggregate', 'Attribute', 'stdClass', 'BackedEnum', 'UnitEnum',
	'Exception', 'Throwable', 'Closure', 'DateTimeImmutable', 'SplObjectStorage', 'WeakMap', 'ReflectionClass',
] as $rfName) {
	check($rfReflectionProvider->hasClass($rfName), "reflection-family: $rfName is known to the reflection provider");
	if (!$rfReflectionProvider->hasClass($rfName)) {
		continue;
	}
	$rfSamples[$rfName] = $rfReflectionProvider->getClass($rfName);
}
$rfCircle = new \PHPStan\Type\ObjectType('ReflectionFamilyFixture\Circle');
$rfSamples['Box<Circle,int>'] = $rfSamples['ReflectionFamilyFixture\Box']->withTypes([$rfCircle, new \PHPStan\Type\IntegerType()]);
$rfSamples['Box<Circle,int> covariant'] = $rfSamples['Box<Circle,int>']->withVariances([\PHPStan\Type\Generic\TemplateTypeVariance::createCovariant(), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()]);
$rfSamples['BoxOf<string>'] = $rfSamples['ReflectionFamilyFixture\BoxOf']->withTypes([new \PHPStan\Type\StringType()]);
$rfSamples['Repo<Circle>'] = $rfSamples['ReflectionFamilyFixture\Repo']->withTypes([$rfCircle]);
$rfSamples['Circle<>'] = $rfSamples['ReflectionFamilyFixture\Circle']->withTypes([]);
$rfSamples['Plain final'] = $rfSamples['ReflectionFamilyFixture\Plain']->asFinal();
$rfSamples['Plain non-final'] = $rfSamples['ReflectionFamilyFixture\Plain']->removeFinalKeywordOverride();
$rfSamples['ArrayObject<int,string>'] = $rfSamples['ArrayObject']->withTypes([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]);
$rfSamples['ConstantsHolder<Circle>'] = $rfSamples['ReflectionFamilyFixture\ConstantsHolder']->withTypes([$rfCircle]);
$rfSamples['MixinHolder<int>'] = $rfSamples['ReflectionFamilyFixture\MixinHolder']->withTypes([new \PHPStan\Type\IntegerType()]);
$rfSamples['ImmutableChild final'] = $rfSamples['ReflectionFamilyFixture\ImmutableChild']->asFinal();

$rfProbeMethods = ['__construct', '__get', '__set', '__isset', 'area', 'greet', 'secret', 'make', 'base', 'hidden', 'label', 'getIterator', 'count', 'name', 'find', 'extra', 'cases', 'from', 'tryFrom', 'offsetGet', 'getMessage', 'nope', '123', 'Legacy'];
$rfProbeProperties = ['x', 'pub', 'priv', 'count', 'fromTrait', 'a', 's', 'p', 'c', 'v', 'value', 'name', 'nope', 'message', '123'];
$rfProbeTraits = ['ReflectionFamilyFixture\Greets', 'ReflectionFamilyFixture\Nested', 'ReflectionFamilyFixture\Documented', 'Nope\Missing'];
$rfProbeConstants = ['DEFAULT', 'LIMIT', 'NAME', 'INHERITED', 'DOCUMENTED', 'TEMPLATED', 'OLD', 'TYPED', 'FIRST', 'nope', 'TARGET_CLASS'];
$rfProbeEnumCases = ['Hearts', 'Spades', 'A', 'B', 'Nope'];
$rfProbeClasses = ['ReflectionFamilyFixture\Base', 'ReflectionFamilyFixture\Shape', 'ReflectionFamilyFixture\Circle', 'ReflectionFamilyFixture\Box', 'ReflectionFamilyFixture\HasName', 'ReflectionFamilyFixture\Dynamic', 'Countable', 'Traversable', 'IteratorAggregate', 'stdClass', 'Throwable', 'Nope\Missing'];
// the memo slots the ported methods own (a slot an unported method fills
// lands in the delegate twin on the native side) and the constructor's
// value slots; $subclasses is compared as a subset — the crate check's
// callback into the PHP twin's is() fills it on that side only
$rfMemoSlots = [
	'methods', 'properties', 'instanceProperties', 'staticProperties', 'constants', 'enumCases', 'classHierarchyDistances',
	'deprecatedDescription', 'isDeprecated', 'isGeneric', 'isInternal', 'isFinal', 'isImmutable', 'hasConsistentConstructor', 'acceptsNamedArguments',
	'templateTypeMap', 'activeTemplateTypeMap', 'defaultCallSiteVarianceMap', 'callSiteVarianceMap', 'ancestors', 'cacheKey', 'filename',
	'reflectionDocComment', 'stubPhpDocBlock', 'resolvedPhpDocBlock', 'traitContextResolvedPhpDocBlock',
	'cachedInterfaces', 'cachedParentClass', 'typeAliases', 'hasMethodCache', 'hasPropertyCache', 'hasInstancePropertyCache', 'hasStaticPropertyCache', 'name',
	'displayName', 'reflection', 'anonymousFilename', 'resolvedTemplateTypeMap', 'stubPhpDocBlockCallback', 'extraCacheKey', 'resolvedCallSiteVarianceMap', 'finalByKeywordOverride',
];

// ---- settle the shared state first ----
// The twin's hasInstanceProperty() writes its last two answers into
// $hasPropertyCache (not the instance cache): a require-extends probe
// reaching the provider's reflection of the required class through an
// ObjectType overwrites that class's hasProperty() memo mid-sequence, so
// the side probing first would see a different answer than the side
// probing second. Both sides are ported faithfully; the shared state is
// settled here so the comparison sees one answer.
foreach ($rfSamples as $rfOriginal) {
	foreach ($rfProbeProperties as $property) {
		$rfOriginal->hasProperty($property);
		$rfOriginal->hasInstanceProperty($property);
		$rfOriginal->hasStaticProperty($property);
	}
}

// ---- rebuild each sample on both sides and compare method by method ----
$rfObservations = ['php' => [], 'native' => []];
$rfSubclassesMemo = ['php' => [], 'native' => []];
$rfKeepAlive = [];
$rfSampleCount = 0;
foreach ($rfSamples as $rfLabel => $rfOriginal) {
	$rfArgs = $rfHarness->constructorArgs($rfOriginal);
	$rfSampleCount++;
	$rfOutOfClassScope = new \PHPStan\Analyser\OutOfClassScope();
	// a trait is entered through a class using it
	$rfInClassContext = $rfOriginal->isTrait()
		? \PHPStan\Analyser\ScopeContext::create($rfFile)->enterClass($rfSamples['ReflectionFamilyFixture\Circle'])->enterTrait($rfOriginal)
		: \PHPStan\Analyser\ScopeContext::create($rfFile)->enterClass($rfOriginal);
	$rfInClassScope = $rfScopeFactory->create($rfInClassContext);
	$rfOtherScope = $rfScopeFactory->create(\PHPStan\Analyser\ScopeContext::create($rfFile)->enterClass($rfSamples['ReflectionFamilyFixture\Plain']));
	foreach (['php', 'native'] as $side) {
		$observe = static function (string $label, callable $fn) use (&$rfObservations, $side, $rfHarness, $rfLabel): void {
			try {
				$rfObservations[$side][$rfLabel][$label] = $rfHarness->norm($fn());
			} catch (\Throwable $e) {
				$rfObservations[$side][$rfLabel][$label] = $rfHarness->norm($e);
			}
		};

		$args = $rfArgs;
		if ($side === 'native') {
			$delegate = new \PHPStan\Reflection\ClassReflection(...array_values($args));
			$args['classReflectionExtensionRegistryProvider'] = new DuckRegistryProvider($rfRegistryProvider);
			$args['phpDocInheritanceResolver'] = new \ReflectionFamily\DuckPhpDocInheritanceResolver($args['phpDocInheritanceResolver']);
			$args['classReflectionFactory'] = new \ReflectionFamily\DuckClassReflectionFactory($args['classReflectionFactory']);
			$classReflection = new \PHPStanTurbo\ClassReflection(...array_values($args));
			Twins::register($classReflection, $delegate);
			$rfKeepAlive[] = $classReflection;
			$rfKeepAlive[] = $delegate;
			$nativeReflection = new \ReflectionClass(\PHPStanTurbo\ClassReflection::class);
		} else {
			$classReflection = new \PHPStan\Reflection\ClassReflection(...array_values($args));
			$nativeReflection = new \ReflectionClass(\PHPStan\Reflection\ClassReflection::class);
		}

		// the plain getters, twice each: the memoized answer must equal the computed one
		foreach ([
			'getNativeReflection', 'getFileName', 'getName', 'getDisplayName', 'getCacheKey', 'isAbstract', 'isInterface', 'isTrait', 'isEnum', 'getClassTypeDescription', 'isReadOnly', 'isBackedEnum', 'isClass', 'isAnonymous',
			'hasFinalByKeywordOverride', 'isFinalByKeyword', 'isFinal', 'isGeneric', 'allowsDynamicProperties', 'hasConstructor', 'getConstructor',
			'getParentClass', 'getParents', 'getImmediateInterfaces', 'getInterfaces', 'getClassHierarchyDistances',
			'getBackedEnumType', 'getEnumCases', 'getParentClassesNames', 'getTypeAliases', 'getDeprecatedDescription', 'isDeprecated',
			'isBuiltin', 'isInternal', 'isImmutable', 'hasConsistentConstructor', 'acceptsNamedArguments', 'isAttributeClass', 'getAttributeClassFlags',
			'getAttributes', 'getObjectType', 'getTemplateTypeMap', 'getActiveTemplateTypeMap', 'getPossiblyIncompleteActiveTemplateTypeMap',
			'getCallSiteVarianceMap', 'getResolvedPhpDoc', 'getExtendsTags', 'getImplementsTags', 'getTemplateTags', 'getMixinTags',
			'getRequireExtendsTags', 'getRequireImplementsTags', 'getSealedTags', 'getPropertyTags', 'getMethodTags',
			'getAncestors', 'getResolvedMixinTypes', 'getAllowedSubTypes',
		] as $method) {
			$observe($method, static fn () => $classReflection->$method());
			$observe("$method again", static fn () => $classReflection->$method());
		}
		$observe('getDisplayName(false)', static fn () => $classReflection->getDisplayName(false));
		$observe('getDisplayName(true)', static fn () => $classReflection->getDisplayName(true));

		// members
		foreach ($rfProbeMethods as $method) {
			$observe("hasMethod($method)", static fn () => $classReflection->hasMethod($method));
			$observe("hasNativeMethod($method)", static fn () => $classReflection->hasNativeMethod($method));
			$observe("getMethod($method, out of class)", static fn () => $classReflection->getMethod($method, $rfOutOfClassScope));
			$observe("getMethod($method, in class)", static fn () => $classReflection->getMethod($method, $rfInClassScope));
			$observe("getMethod($method, other class)", static fn () => $classReflection->getMethod($method, $rfOtherScope));
			$observe("getNativeMethod($method)", static fn () => $classReflection->getNativeMethod($method));
			$observe("hasMethod($method) again", static fn () => $classReflection->hasMethod($method));
		}
		foreach ($rfProbeProperties as $property) {
			$observe("hasProperty($property)", static fn () => $classReflection->hasProperty($property));
			$observe("hasInstanceProperty($property)", static fn () => $classReflection->hasInstanceProperty($property));
			$observe("hasStaticProperty($property)", static fn () => $classReflection->hasStaticProperty($property));
			$observe("hasNativeProperty($property)", static fn () => $classReflection->hasNativeProperty($property));
			$observe("getProperty($property, out of class)", static fn () => $classReflection->getProperty($property, $rfOutOfClassScope));
			$observe("getProperty($property, in class)", static fn () => $classReflection->getProperty($property, $rfInClassScope));
			$observe("getInstanceProperty($property, in class)", static fn () => $classReflection->getInstanceProperty($property, $rfInClassScope));
			$observe("getInstanceProperty($property, other class)", static fn () => $classReflection->getInstanceProperty($property, $rfOtherScope));
			$observe("getStaticProperty($property)", static fn () => $classReflection->getStaticProperty($property));
			$observe("getNativeProperty($property)", static fn () => $classReflection->getNativeProperty($property));
			$observe("hasInstanceProperty($property) again", static fn () => $classReflection->hasInstanceProperty($property));
		}

		// class relations
		foreach ($rfProbeClasses as $className) {
			$observe("is($className)", static fn () => $classReflection->is($className));
			$observe("isSubclassOf($className)", static fn () => $classReflection->isSubclassOf($className));
			$observe("implementsInterface($className)", static fn () => $classReflection->implementsInterface($className));
			if (isset($rfSamples[$className])) {
				$observe("isSubclassOfClass($className)", static fn () => $classReflection->isSubclassOfClass($rfSamples[$className]));
				$observe("isSubclassOfClass($className) again", static fn () => $classReflection->isSubclassOfClass($rfSamples[$className]));
			}
		}
		$observe('isSubclassOfClass(Plain final)', static fn () => $classReflection->isSubclassOfClass($rfSamples['Plain final']));
		$observe('isSubclassOfClass(Box<Circle,int>)', static fn () => $classReflection->isSubclassOfClass($rfSamples['Box<Circle,int>']));

		// traits, ancestors, constants, enum cases
		$observe('getTraits(false)', static fn () => $classReflection->getTraits());
		$observe('getTraits(true)', static fn () => $classReflection->getTraits(true));
		$observe('getTraits(true) again', static fn () => $classReflection->getTraits(true));
		foreach ($rfProbeTraits as $traitName) {
			$observe("hasTraitUse($traitName)", static fn () => $classReflection->hasTraitUse($traitName));
		}
		foreach ($rfProbeClasses as $className) {
			$observe("getAncestorWithClassName($className)", static fn () => $classReflection->getAncestorWithClassName($className));
		}
		foreach ($rfProbeConstants as $constantName) {
			$observe("hasConstant($constantName)", static fn () => $classReflection->hasConstant($constantName));
			$observe("getConstant($constantName)", static fn () => $classReflection->getConstant($constantName));
			$observe("getConstant($constantName) again", static fn () => $classReflection->getConstant($constantName));
			$observe("getConstantPhpDocType($constantName)", static fn () => $classReflection->getConstantPhpDocType($constantName));
		}
		foreach ($rfProbeEnumCases as $caseName) {
			$observe("hasEnumCase($caseName)", static fn () => $classReflection->hasEnumCase($caseName));
			$observe("getEnumCase($caseName)", static fn () => $classReflection->getEnumCase($caseName));
		}

		// the generics machinery
		$observe('typeMapToList(getTemplateTypeMap())', static fn () => $classReflection->typeMapToList($classReflection->getTemplateTypeMap()));
		$observe('typeMapToList(empty)', static fn () => $classReflection->typeMapToList(TemplateTypeMap::createEmpty()));
		$observe('varianceMapToList(getCallSiteVarianceMap())', static fn () => $classReflection->varianceMapToList($classReflection->getCallSiteVarianceMap()));
		$observe('varianceMapToList(empty)', static fn () => $classReflection->varianceMapToList(TemplateTypeVarianceMap::createEmpty()));
		$observe('typeMapFromList([])', static fn () => $classReflection->typeMapFromList([]));
		$observe('typeMapFromList([Circle])', static fn () => $classReflection->typeMapFromList([new \PHPStan\Type\ObjectType('ReflectionFamilyFixture\Circle')]));
		$observe('typeMapFromList([Circle,int])', static fn () => $classReflection->typeMapFromList([new \PHPStan\Type\ObjectType('ReflectionFamilyFixture\Circle'), new \PHPStan\Type\IntegerType()]));
		$observe('varianceMapFromList([])', static fn () => $classReflection->varianceMapFromList([]));
		$observe('varianceMapFromList([covariant])', static fn () => $classReflection->varianceMapFromList([TemplateTypeVariance::createCovariant()]));
		$observe('withTypes([])', static fn () => $classReflection->withTypes([]));
		$observe('withTypes([Circle,int])', static fn () => $classReflection->withTypes([new \PHPStan\Type\ObjectType('ReflectionFamilyFixture\Circle'), new \PHPStan\Type\IntegerType()]));
		$observe('withVariances([covariant])', static fn () => $classReflection->withVariances([TemplateTypeVariance::createCovariant()]));
		$observe('asFinal', static fn () => $classReflection->asFinal());
		$observe('withoutFinalByKeywordOverride', static fn () => $classReflection->withoutFinalByKeywordOverride());
		$observe('removeFinalKeywordOverride', static fn () => $classReflection->removeFinalKeywordOverride());
		$observe('getTraitContextResolvedPhpDoc(Circle)', static fn () => $classReflection->getTraitContextResolvedPhpDoc($rfSamples['ReflectionFamilyFixture\Circle']));
		$observe('getTraitContextResolvedPhpDoc(Greets)', static fn () => $classReflection->getTraitContextResolvedPhpDoc($rfSamples['ReflectionFamilyFixture\Greets']));
		$observe('getTraitContextResolvedPhpDoc(UsesDocumented)', static fn () => $classReflection->getTraitContextResolvedPhpDoc($rfSamples['ReflectionFamilyFixture\UsesDocumented']));

		// the memo state after all of the above, then after the eviction
		$memo = static function () use ($classReflection, $nativeReflection, $rfMemoSlots, $rfHarness): array {
			$state = [];
			foreach ($rfMemoSlots as $slot) {
				try {
					$state[$slot] = $rfHarness->norm($nativeReflection->getProperty($slot)->getValue($classReflection));
				} catch (\Throwable $e) {
					$state[$slot] = $rfHarness->norm($e);
				}
			}
			return $state;
		};
		$observe('memo', $memo);
		$subclasses = static fn (): array => $rfHarness->norm($nativeReflection->getProperty('subclasses')->getValue($classReflection));
		$rfSubclassesMemo[$side][$rfLabel] = $subclasses();
		$observe('evictPrivateSymbols', static fn () => $classReflection->evictPrivateSymbols());
		$observe('memo after eviction', $memo);
	}
}

// the class map back to the generated one
\PHPStanTurbo\Runtime::configure([
	'universalObjectCratesClassReflectionExtension' => $rfClassMap['universalObjectCratesClassReflectionExtension'],
	'enumCaseReflection' => $rfClassMap['enumCaseReflection'],
	'realClassClassConstantReflection' => $rfClassMap['realClassClassConstantReflection'],
	'parserNodeTypeToPHPStanType' => $rfClassMap['parserNodeTypeToPHPStanType'],
]);

foreach ($rfObservations['php'] as $rfLabel => $rfPhpObservations) {
	$rfNativeObservations = $rfObservations['native'][$rfLabel] ?? [];
	foreach ($rfPhpObservations as $label => $expected) {
		$actual = array_key_exists($label, $rfNativeObservations) ? $rfNativeObservations[$label] : '<missing>';
		check($expected === $actual, sprintf('ClassReflection parity (%s) %s: %s vs %s', $rfLabel, $label, json_encode($expected), json_encode($actual)));
	}
	check(array_keys($rfPhpObservations) === array_keys($rfNativeObservations), "ClassReflection parity ($rfLabel): the same observations on both sides");
	foreach ($rfSubclassesMemo['native'][$rfLabel] ?? [] as $cacheKey => $isSubclass) {
		check(($rfSubclassesMemo['php'][$rfLabel][$cacheKey] ?? '<missing>') === $isSubclass, sprintf('ClassReflection parity (%s) subclasses memo[%s]: %s vs %s', $rfLabel, $cacheKey, json_encode($rfSubclassesMemo['php'][$rfLabel][$cacheKey] ?? '<missing>'), json_encode($isSubclass)));
	}
}
$rfObservationCount = array_sum(array_map('count', $rfObservations['php']));
check($rfObservationCount > 5000, "reflection-family: enough observations ($rfObservationCount over $rfSampleCount samples)");

if (isset($reflectionFamilyStandalone)) {
	echo $failures === 0 ? "ALL OK ($rfObservationCount observations over $rfSampleCount samples)\n" : "$failures FAILURES\n";
	exit($failures === 0 ? 0 : 1);
}

}

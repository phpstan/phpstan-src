<?php declare(strict_types = 1);

/**
 * Differential test of the native PhpClassReflectionExtension against the
 * PHP twin, under the prefixed activation: PHPStanTurbo\PhpClassReflectionExtension
 * is declared next to PHPStan\Reflection\Php\PhpClassReflectionExtension,
 * so both sides live in one process.
 *
 * Both sides are constructed with NAMED arguments from the collaborators of
 * the container's own instance (read by reflection) — the named-argument
 * construction is itself the check that the native arginfo carries the
 * twin's parameter names. Real ClassReflections come from the DI
 * container's reflection provider over
 * php-class-reflection-family-fixture.php (inherited, trait, magic
 * __get/__call, promoted, hooked, attributed, enum, interface members) plus
 * a handful of built-in classes whose methods come from the signature map
 * and the stub files.
 *
 * Every public method's answer is compared through a side-independent
 * description of the returned reflection (its whole public surface, down to
 * every variant's parameters), and the memo behaviour is compared too:
 * repeated calls must return the identical object on both sides, and a
 * small member-cache limit must evict the same keys.
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
	$pcreStandalone = true;
}

}

namespace PhpClassReflectionFamily {

use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\VerbosityLevel;

/** Side-independent description of any value a member reflection exposes. */
final class Describer
{

	public function __construct(private int $maxDepth = 4)
	{
	}

	/** @return mixed */
	public function describe(mixed $value, int $depth = 0)
	{
		if ($value === null || is_bool($value) || is_int($value) || is_float($value) || is_string($value)) {
			return $value;
		}
		if (is_array($value)) {
			if ($depth >= $this->maxDepth) {
				return 'array(' . count($value) . ')';
			}
			$out = [];
			foreach ($value as $key => $item) {
				$out[$key] = $this->describe($item, $depth + 1);
			}
			return $out;
		}
		if ($value instanceof \PHPStan\Type\Type) {
			return 'type:' . $value->describe(VerbosityLevel::precise());
		}
		if ($value instanceof \PHPStan\TrinaryLogic) {
			return 'tri:' . $value->describe();
		}
		if ($value instanceof ClassReflection) {
			return 'class:' . $value->getName();
		}
		// the native value classes the native side creates under the prefix
		// (a native function variant's empty resolved map) describe alike
		if ($value instanceof \PHPStan\Type\Generic\TemplateTypeMap || $value instanceof \PHPStanTurbo\TemplateTypeMap) {
			return 'map:' . implode(',', array_map(
				static fn (string $name, \PHPStan\Type\Type $type): string => $name . '=' . $type->describe(VerbosityLevel::precise()),
				array_keys($value->getTypes()),
				array_values($value->getTypes()),
			));
		}
		if ($value instanceof \PHPStan\Reflection\PassedByReference) {
			return 'byRef:' . ($value->no() ? 'no' : ($value->createsNewVariable() ? 'new' : 'readsWrites'));
		}
		if ($value instanceof \PHPStan\Reflection\Assertions) {
			return [
				'asserts' => count($value->getAsserts()),
				'ifTrue' => count($value->getAssertsIfTrue()),
				'ifFalse' => count($value->getAssertsIfFalse()),
			];
		}
		if ($value instanceof \PHPStan\PhpDoc\ResolvedPhpDocBlock) {
			return 'phpdoc:' . ($value->hasPhpDocString() ? $value->getPhpDocString() : '(none)');
		}
		if ($value instanceof \PHPStan\Reflection\AttributeReflection) {
			return 'attr:' . $value->getName() . '(' . implode(',', array_map(
				static fn (string $name, \PHPStan\Type\Type $type): string => $name . '=' . $type->describe(VerbosityLevel::precise()),
				array_keys($value->getArgumentTypes()),
				array_values($value->getArgumentTypes()),
			)) . ')';
		}
		if ($value instanceof \PHPStan\Reflection\ParameterAllowedConstants) {
			return 'allowedConstants';
		}
		if ($value instanceof \PHPStan\Reflection\ExtendedParametersAcceptor) {
			return $this->describeVariant($value, $depth);
		}
		if ($value instanceof \PHPStan\Reflection\ExtendedParameterReflection) {
			return $this->describeParameter($value, $depth);
		}
		if ($value instanceof \PHPStan\Reflection\ExtendedMethodReflection) {
			return $this->describeMethod($value, $depth);
		}
		if ($value instanceof \PHPStan\Reflection\ExtendedPropertyReflection) {
			return $this->describeProperty($value, $depth);
		}
		if (is_object($value)) {
			return 'object:' . $this->normalizeClass(get_class($value));
		}

		return get_debug_type($value);
	}

	/** @return array<string, mixed> */
	public function describeProperty(\PHPStan\Reflection\ExtendedPropertyReflection $property, int $depth = 0): array
	{
		if ($depth >= $this->maxDepth) {
			return ['property' => $property->getName()];
		}

		return [
			'class' => $this->normalizeClass(get_class($property)),
			'name' => $property->getName(),
			'declaringClass' => $property->getDeclaringClass()->getName(),
			'docComment' => $property->getDocComment(),
			'static' => $property->isStatic(),
			'private' => $property->isPrivate(),
			'public' => $property->isPublic(),
			'readableType' => $property->getReadableType()->describe(VerbosityLevel::precise()),
			'writableType' => $property->getWritableType()->describe(VerbosityLevel::precise()),
			'hasPhpDocType' => $property->hasPhpDocType(),
			'phpDocType' => $property->getPhpDocType()->describe(VerbosityLevel::precise()),
			'hasNativeType' => $property->hasNativeType(),
			'nativeType' => $property->getNativeType()->describe(VerbosityLevel::precise()),
			'readable' => $property->isReadable(),
			'writable' => $property->isWritable(),
			'canChangeTypeAfterAssignment' => $property->canChangeTypeAfterAssignment(),
			'deprecated' => $property->isDeprecated()->describe(),
			'deprecatedDescription' => $property->getDeprecatedDescription(),
			'internal' => $property->isInternal()->describe(),
			'abstract' => $property->isAbstract()->describe(),
			'finalByKeyword' => $property->isFinalByKeyword()->describe(),
			'final' => $property->isFinal()->describe(),
			'virtual' => $property->isVirtual()->describe(),
			'hasGetHook' => $property->hasHook('get'),
			'hasSetHook' => $property->hasHook('set'),
			'getHook' => $property->hasHook('get') ? $this->describeMethod($property->getHook('get'), $depth + 1) : null,
			'setHook' => $property->hasHook('set') ? $this->describeMethod($property->getHook('set'), $depth + 1) : null,
			'protectedSet' => $property->isProtectedSet(),
			'privateSet' => $property->isPrivateSet(),
			'attributes' => $this->describe($property->getAttributes(), $depth + 1),
			'dummy' => $property->isDummy()->describe(),
		];
	}

	/** @return array<string, mixed> */
	public function describeMethod(\PHPStan\Reflection\ExtendedMethodReflection $method, int $depth = 0): array
	{
		if ($depth >= $this->maxDepth) {
			return ['method' => $method->getName()];
		}

		$namedVariants = $method->getNamedArgumentsVariants();

		return [
			'class' => $this->normalizeClass(get_class($method)),
			'name' => $method->getName(),
			'declaringClass' => $method->getDeclaringClass()->getName(),
			'docComment' => $method->getDocComment(),
			'static' => $method->isStatic(),
			'private' => $method->isPrivate(),
			'public' => $method->isPublic(),
			'deprecated' => $method->isDeprecated()->describe(),
			'deprecatedDescription' => $method->getDeprecatedDescription(),
			'final' => $method->isFinal()->describe(),
			'finalByKeyword' => $method->isFinalByKeyword()->describe(),
			'internal' => $method->isInternal()->describe(),
			'throwType' => $method->getThrowType() === null ? null : $method->getThrowType()->describe(VerbosityLevel::precise()),
			'hasSideEffects' => $method->hasSideEffects()->describe(),
			'pure' => $method->isPure()->describe(),
			'acceptsNamedArguments' => $method->acceptsNamedArguments()->describe(),
			'returnsByReference' => $method->returnsByReference()->describe(),
			'abstract' => $method->isAbstract() instanceof \PHPStan\TrinaryLogic ? $method->isAbstract()->describe() : $method->isAbstract(),
			'builtin' => $method->isBuiltin() instanceof \PHPStan\TrinaryLogic ? $method->isBuiltin()->describe() : $method->isBuiltin(),
			'selfOutType' => $method->getSelfOutType() === null ? null : $method->getSelfOutType()->describe(VerbosityLevel::precise()),
			'asserts' => $this->describe($method->getAsserts(), $depth + 1),
			'attributes' => $this->describe($method->getAttributes(), $depth + 1),
			'pureUnlessCallableIsImpureParameters' => $method->getPureUnlessCallableIsImpureParameters(),
			'mustUseReturnValue' => $method->mustUseReturnValue()->describe(),
			'resolvedPhpDoc' => $this->describe($method->getResolvedPhpDoc(), $depth + 1),
			'variants' => array_map(fn ($variant) => $this->describeVariant($variant, $depth + 1), $method->getVariants()),
			'namedVariants' => $namedVariants === null ? null : array_map(fn ($variant) => $this->describeVariant($variant, $depth + 1), $namedVariants),
		];
	}

	/** @return array<string, mixed> */
	public function describeVariant(\PHPStan\Reflection\ExtendedParametersAcceptor $variant, int $depth = 0): array
	{
		return [
			'templateTypeMap' => $this->describe($variant->getTemplateTypeMap(), $depth + 1),
			'resolvedTemplateTypeMap' => $this->describe($variant->getResolvedTemplateTypeMap(), $depth + 1),
			'variadic' => $variant->isVariadic(),
			'returnType' => $variant->getReturnType()->describe(VerbosityLevel::precise()),
			'phpDocReturnType' => $variant->getPhpDocReturnType()->describe(VerbosityLevel::precise()),
			'nativeReturnType' => $variant->getNativeReturnType()->describe(VerbosityLevel::precise()),
			'parameters' => array_map(fn ($parameter) => $this->describeParameter($parameter, $depth + 1), $variant->getParameters()),
		];
	}

	/** @return array<string, mixed> */
	public function describeParameter(\PHPStan\Reflection\ExtendedParameterReflection $parameter, int $depth = 0): array
	{
		return [
			'class' => $this->normalizeClass(get_class($parameter)),
			'name' => $parameter->getName(),
			'optional' => $parameter->isOptional(),
			'type' => $parameter->getType()->describe(VerbosityLevel::precise()),
			'phpDocType' => $parameter->getPhpDocType()->describe(VerbosityLevel::precise()),
			'hasNativeType' => $parameter->hasNativeType(),
			'nativeType' => $parameter->getNativeType()->describe(VerbosityLevel::precise()),
			'byRef' => $this->describe($parameter->passedByReference(), $depth + 1),
			'variadic' => $parameter->isVariadic(),
			'defaultValue' => $parameter->getDefaultValue() === null ? null : $parameter->getDefaultValue()->describe(VerbosityLevel::precise()),
			'outType' => $parameter->getOutType() === null ? null : $parameter->getOutType()->describe(VerbosityLevel::precise()),
			'immediatelyInvokedCallable' => $parameter->isImmediatelyInvokedCallable()->describe(),
			'closureThisType' => $parameter->getClosureThisType() === null ? null : $parameter->getClosureThisType()->describe(VerbosityLevel::precise()),
			'attributes' => $this->describe($parameter->getAttributes(), $depth + 1),
			'allowedConstants' => $parameter->getAllowedConstants() === null ? null : 'allowedConstants',
			'pureUnlessCallableIsImpure' => $parameter->isPureUnlessCallableIsImpureParameter()->describe(),
		];
	}

	/**
	 * A native class declared under the prefix by the prefixed activation
	 * (the native extension instantiating a shadowed value class) is compared
	 * as its twin, named by the manifest.
	 */
	public function normalizeClass(string $class): string
	{
		if (!str_starts_with($class, 'PHPStanTurbo\\')) {
			return $class;
		}
		static $twins = null;
		if ($twins === null) {
			$twins = [];
			$manifest = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/vendor/turbo-shadowed-classes.json'), true);
			foreach (is_array($manifest) ? $manifest : [] as $twin => $entry) {
				$twins[$entry['turboClass']] = $twin;
			}
		}
		return $twins[$class] ?? substr($class, strlen('PHPStanTurbo\\'));
	}

}

}

namespace {

use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\Php\PhpClassReflectionExtension;

$pcreRoot = dirname(__DIR__, 2);
$pcreFixture = __DIR__ . '/php-class-reflection-family-fixture.php';
require_once $pcreFixture;

$pcreContainerFactory = new \PHPStan\DependencyInjection\ContainerFactory($pcreRoot);
$pcreContainer = $pcreContainerFactory->create(
	sys_get_temp_dir() . '/phpstan-turbo-smoke-php-class-reflection',
	[$pcreContainerFactory->getConfigDirectory() . '/config.level8.neon'],
	[$pcreFixture],
);
$pcreReflectionProvider = $pcreContainer->getByType(\PHPStan\Reflection\ReflectionProvider::class);
$pcreReal = $pcreContainer->getByType(PhpClassReflectionExtension::class);

// the collaborators of the container's own instance, by the constructor's
// parameter names: both sides are built from exactly the same services
$pcreCollaborators = [];
$pcreRealReflection = new ReflectionObject($pcreReal);
foreach ([
	'scopeFactory', 'phpDocsResolver', 'nodeScopeResolver', 'methodReflectionFactory',
	'phpDocInheritanceResolver', 'deprecationProvider', 'annotationsMethodsClassReflectionExtension',
	'annotationsPropertiesClassReflectionExtension', 'signatureMapProvider', 'parser',
	'stubPhpDocProvider', 'reflectionProviderProvider', 'fileTypeMapper',
	'attributeReflectionFactory', 'allowedConstantsMapProvider', 'phpVersion',
] as $pcreName) {
	$pcreProperty = $pcreRealReflection->getProperty($pcreName);
	$pcreCollaborators[$pcreName] = $pcreProperty->getValue($pcreReal);
}

/** @return array{PhpClassReflectionExtension, \PHPStanTurbo\PhpClassReflectionExtension} */
$pcreBuildSides = static function (bool $infer, int $memberCacheKeysMax, ?\PHPStan\Parser\Parser $parser = null) use ($pcreCollaborators): array {
	$make = static fn (string $class) => new $class(
		scopeFactory: $pcreCollaborators['scopeFactory'],
		phpDocsResolver: $pcreCollaborators['phpDocsResolver'],
		nodeScopeResolver: $pcreCollaborators['nodeScopeResolver'],
		methodReflectionFactory: $pcreCollaborators['methodReflectionFactory'],
		phpDocInheritanceResolver: $pcreCollaborators['phpDocInheritanceResolver'],
		deprecationProvider: $pcreCollaborators['deprecationProvider'],
		annotationsMethodsClassReflectionExtension: $pcreCollaborators['annotationsMethodsClassReflectionExtension'],
		annotationsPropertiesClassReflectionExtension: $pcreCollaborators['annotationsPropertiesClassReflectionExtension'],
		signatureMapProvider: $pcreCollaborators['signatureMapProvider'],
		parser: $parser ?? $pcreCollaborators['parser'],
		stubPhpDocProvider: $pcreCollaborators['stubPhpDocProvider'],
		reflectionProviderProvider: $pcreCollaborators['reflectionProviderProvider'],
		fileTypeMapper: $pcreCollaborators['fileTypeMapper'],
		attributeReflectionFactory: $pcreCollaborators['attributeReflectionFactory'],
		allowedConstantsMapProvider: $pcreCollaborators['allowedConstantsMapProvider'],
		inferPrivatePropertyTypeFromConstructor: $infer,
		phpVersion: $pcreCollaborators['phpVersion'],
		memberCacheKeysMax: $memberCacheKeysMax,
	);

	return [$make(PhpClassReflectionExtension::class), $make(\PHPStanTurbo\PhpClassReflectionExtension::class)];
};

$pcreDescriber = new \PhpClassReflectionFamily\Describer();
// an in-class scope makes getProperty() key its cache by both classes and
// gives createProperty() a scope whose canReadProperty()/getClassReflection()
// steer the annotation branch
$pcreScopeFactory = $pcreCollaborators['scopeFactory'];
$pcreBaseScope = $pcreScopeFactory->create(\PHPStan\Analyser\ScopeContext::create($pcreFixture));
$pcreScopes = [
	'outOfClass' => new OutOfClassScope(),
	'inChild' => $pcreBaseScope->enterClass($pcreReflectionProvider->getClass('PhpClassReflectionFamilyFixture\\FixtureChild')),
	'inBase' => $pcreBaseScope->enterClass($pcreReflectionProvider->getClass('PhpClassReflectionFamilyFixture\\FixtureAnnotated')),
];

$pcreClasses = [
	'PhpClassReflectionFamilyFixture\FixtureChild',
	'PhpClassReflectionFamilyFixture\FixtureBase',
	'PhpClassReflectionFamilyFixture\FixturePureEnum',
	'PhpClassReflectionFamilyFixture\FixtureBackedEnum',
	'PhpClassReflectionFamilyFixture\FixtureInterface',
	'PhpClassReflectionFamilyFixture\FixtureImmutable',
	'PhpClassReflectionFamilyFixture\FixtureAnnotated',
	'PhpClassReflectionFamilyFixture\FixtureAnnotatedStrict',
	'PhpClassReflectionFamilyFixture\FixtureTrait',
	'Exception',
	'ArrayObject',
	'DateTimeImmutable',
	'SplObjectStorage',
	'UnitEnum',
	'BackedEnum',
	'Closure',
];
$pcreMembers = [
	'fromTrait', 'traitMethod', 'aliasedMethod', 'renamedMethod', 'deprecatedProperty',
	'internalProperty', 'attributed', 'inferredFromConstructor', 'publicButPrivate',
	'publicButProtected', 'promoted', 'promotedProtected', 'hooked', 'rich', 'fluent',
	'fromInterface', 'magicRead', 'magicWrite', 'magicMethod', 'withClosureThis',
	'__construct', '__get', '__call', 'name', 'value', 'cases', 'from', 'tryFrom',
	'label', 'getMessage', 'getCode', 'getPrevious', 'count', 'offsetGet', 'offsetSet',
	'format', 'modify', 'attach', 'fromCallable', 'bindTo', 'nonExistentMember',
	// the adapter's `$name === ''` early return, and a spelling the
	// lowercased-name memo has to normalize
	'', 'GETMESSAGE', 'TraitMethod',
];

// {{{ every method's answer on every (class, member) pair
$pcreObservations = [];
foreach (['php', 'native'] as $pcreSide) {
	[$pcrePhpSide, $pcreNativeSide] = $pcreBuildSides(true, 4096);
	$pcreExtension = $pcreSide === 'php' ? $pcrePhpSide : $pcreNativeSide;
	$pcreRows = [];
	foreach ($pcreClasses as $pcreClassName) {
		if (!$pcreReflectionProvider->hasClass($pcreClassName)) {
			$pcreRows[$pcreClassName] = 'unknown class';
			continue;
		}
		$pcreClassReflection = $pcreReflectionProvider->getClass($pcreClassName);
		foreach ($pcreMembers as $pcreMember) {
			$pcreRow = [];
			foreach ([
				'hasProperty' => static fn () => $pcreExtension->hasProperty($pcreClassReflection, $pcreMember),
				'hasMethod' => static fn () => $pcreExtension->hasMethod($pcreClassReflection, $pcreMember),
				'hasNativeMethod' => static fn () => $pcreExtension->hasNativeMethod($pcreClassReflection, $pcreMember),
			] as $pcreLabel => $pcreCall) {
				try {
					$pcreRow[$pcreLabel] = $pcreCall();
				} catch (\Throwable $e) {
					$pcreRow[$pcreLabel] = 'throws ' . $pcreDescriber->normalizeClass(get_class($e)) . ': ' . $e->getMessage();
				}
			}
			foreach ([
				'getNativeProperty' => static fn () => $pcreDescriber->describeProperty($pcreExtension->getNativeProperty($pcreClassReflection, $pcreMember)),
				'getProperty' => static fn () => $pcreDescriber->describeProperty($pcreExtension->getProperty($pcreClassReflection, $pcreMember, $pcreScopes['outOfClass'])),
				'getProperty inChild' => static fn () => $pcreDescriber->describeProperty($pcreExtension->getProperty($pcreClassReflection, $pcreMember, $pcreScopes['inChild'])),
				'getProperty inBase' => static fn () => $pcreDescriber->describeProperty($pcreExtension->getProperty($pcreClassReflection, $pcreMember, $pcreScopes['inBase'])),
				'getMethod' => static fn () => $pcreDescriber->describeMethod($pcreExtension->getMethod($pcreClassReflection, $pcreMember)),
				'getNativeMethod' => static fn () => $pcreDescriber->describeMethod($pcreExtension->getNativeMethod($pcreClassReflection, $pcreMember)),
			] as $pcreLabel => $pcreCall) {
				try {
					$pcreRow[$pcreLabel] = $pcreCall();
				} catch (\Throwable $e) {
					$pcreRow[$pcreLabel] = 'throws ' . $pcreDescriber->normalizeClass(get_class($e)) . ': ' . $e->getMessage();
				}
			}
			$pcreRows[$pcreClassName . '::' . $pcreMember] = $pcreRow;
		}

		// createUserlandMethodReflection on the class's own native methods
		foreach (['traitMethod', 'rich', 'fromInterface', '__construct'] as $pcreMethodName) {
			try {
				$pcreNative = $pcreClassReflection->getNativeReflection();
				if (!$pcreNative->hasMethod($pcreMethodName)) {
					continue;
				}
				$pcreRows[$pcreClassName . '#userland#' . $pcreMethodName] = $pcreDescriber->describeMethod(
					$pcreExtension->createUserlandMethodReflection(
						$pcreClassReflection,
						$pcreClassReflection,
						$pcreNative->getMethod($pcreMethodName),
						null,
					),
				);
			} catch (\Throwable $e) {
				$pcreRows[$pcreClassName . '#userland#' . $pcreMethodName] = 'throws ' . $pcreDescriber->normalizeClass(get_class($e)) . ': ' . $e->getMessage();
			}
		}
	}

	// memoization: repeated calls hand back the identical object
	$pcreMemoClass = $pcreReflectionProvider->getClass('PhpClassReflectionFamilyFixture\FixtureChild');
	$pcreRows['#memo#nativeProperty'] = $pcreExtension->getNativeProperty($pcreMemoClass, 'attributed') === $pcreExtension->getNativeProperty($pcreMemoClass, 'attributed');
	$pcreRows['#memo#property'] = $pcreExtension->getProperty($pcreMemoClass, 'attributed', $pcreScopes['outOfClass']) === $pcreExtension->getProperty($pcreMemoClass, 'attributed', $pcreScopes['outOfClass']);
	$pcreRows['#memo#method'] = $pcreExtension->getMethod($pcreMemoClass, 'rich') === $pcreExtension->getMethod($pcreMemoClass, 'rich');
	$pcreRows['#memo#nativeMethod'] = $pcreExtension->getNativeMethod($pcreMemoClass, 'rich') === $pcreExtension->getNativeMethod($pcreMemoClass, 'rich');
	// the case-insensitive alias the twin also stores under the requested spelling
	$pcreRows['#memo#methodCase'] = $pcreExtension->getMethod($pcreMemoClass, 'RICH') === $pcreExtension->getMethod($pcreMemoClass, 'rich');

	$pcreObservations[$pcreSide] = $pcreRows;
}

/** The first differing path inside two descriptions, or null. */
$pcreFirstDifference = static function ($expected, $actual, string $path = '') use (&$pcreFirstDifference): ?string {
	if (is_array($expected) && is_array($actual)) {
		foreach ($expected as $key => $value) {
			if (!array_key_exists($key, $actual)) {
				return $path . '/' . $key . ': missing natively';
			}
			$deeper = $pcreFirstDifference($value, $actual[$key], $path . '/' . $key);
			if ($deeper !== null) {
				return $deeper;
			}
		}
		foreach ($actual as $key => $value) {
			if (!array_key_exists($key, $expected)) {
				return $path . '/' . $key . ': only natively';
			}
		}

		return null;
	}
	if ($expected === $actual) {
		return null;
	}

	return sprintf('%s: PHP %s, native %s', $path, json_encode($expected), json_encode($actual));
};

foreach ($pcreObservations['php'] as $pcreKey => $pcreExpected) {
	$pcreActual = $pcreObservations['native'][$pcreKey] ?? '(missing)';
	$pcreDifference = $pcreFirstDifference($pcreExpected, $pcreActual);
	check($pcreDifference === null, 'PhpClassReflectionExtension ' . $pcreKey . ' ' . ($pcreDifference ?? ''));
}
check(count($pcreObservations['php']) === count($pcreObservations['native']), 'PhpClassReflectionExtension: both sides observed the same number of rows');
// }}}

// {{{ the shared member-cache LRU: the same keys survive, the same are evicted
$pcreEvictionObservations = [];
foreach (['php', 'native'] as $pcreSide) {
	[$pcrePhpSide, $pcreNativeSide] = $pcreBuildSides(false, 2);
	$pcreExtension = $pcreSide === 'php' ? $pcrePhpSide : $pcreNativeSide;
	$pcreEvictionClasses = [];
	foreach (['PhpClassReflectionFamilyFixture\FixtureChild', 'Exception', 'ArrayObject', 'DateTimeImmutable'] as $pcreClassName) {
		$pcreEvictionClasses[$pcreClassName] = $pcreReflectionProvider->getClass($pcreClassName);
	}
	$pcreFirst = [];
	foreach ($pcreEvictionClasses as $pcreClassName => $pcreClassReflection) {
		$pcreMethodName = $pcreClassName === 'PhpClassReflectionFamilyFixture\FixtureChild' ? 'rich' : 'getIterator';
		if (!$pcreClassReflection->getNativeReflection()->hasMethod($pcreMethodName)) {
			$pcreMethodName = $pcreClassReflection->getNativeReflection()->getMethods()[0]->getName();
		}
		$pcreFirst[$pcreClassName] = [$pcreMethodName, $pcreExtension->getNativeMethod($pcreClassReflection, $pcreMethodName)];
	}
	$pcreState = [];
	foreach ($pcreFirst as $pcreClassName => [$pcreMethodName, $pcreFirstResult]) {
		// with a limit of 2 the two oldest keys were evicted: those classes
		// recompute (a different object), the two newest are memo hits
		$pcreState[$pcreClassName] = $pcreExtension->getNativeMethod($pcreEvictionClasses[$pcreClassName], $pcreMethodName) === $pcreFirstResult;
	}
	$pcreCacheReflection = new ReflectionObject($pcreExtension);
	foreach (['nativeMethods', 'methodsIncludingAnnotations', 'nativeProperties', 'propertiesIncludingAnnotations'] as $pcreCacheName) {
		$pcreCacheProperty = $pcreCacheReflection->getProperty($pcreCacheName);
		$pcreState['#keys#' . $pcreCacheName] = array_keys($pcreCacheProperty->getValue($pcreExtension));
	}
	$pcreOrderProperty = $pcreCacheReflection->getProperty('memberCacheOrder');
	$pcreOrder = $pcreOrderProperty->getValue($pcreExtension);
	$pcreState['#lru#count'] = $pcreOrder->count();
	$pcreState['#lru#keys'] = array_keys($pcreOrder->all());
	$pcreEvictionObservations[$pcreSide] = $pcreState;
}
check(
	$pcreEvictionObservations['php'] === $pcreEvictionObservations['native'],
	sprintf(
		'PhpClassReflectionExtension member-cache eviction: PHP %s, native %s',
		json_encode($pcreEvictionObservations['php']),
		json_encode($pcreEvictionObservations['native']),
	),
);
// }}}

// {{{ an inference that throws: the twin removes the class's in-process
// marker on the normal return only, so a later ask infers nothing for it
$pcreThrowObservations = [];
foreach (['php', 'native'] as $pcreSide) {
	$pcreThrowingParser = new class ($pcreCollaborators['parser']) implements \PHPStan\Parser\Parser {

		public bool $armed = true;

		public function __construct(private \PHPStan\Parser\Parser $inner)
		{
		}

		public function parseFile(string $file): array
		{
			if ($this->armed) {
				$this->armed = false;
				throw new \RuntimeException('parser failed');
			}
			return $this->inner->parseFile($file);
		}

		public function parseString(string $sourceCode): array
		{
			return $this->inner->parseString($sourceCode);
		}

	};
	[$pcrePhpSide, $pcreNativeSide] = $pcreBuildSides(true, 4096, $pcreThrowingParser);
	$pcreExtension = $pcreSide === 'php' ? $pcrePhpSide : $pcreNativeSide;
	$pcreThrowClass = $pcreReflectionProvider->getClass('PhpClassReflectionFamilyFixture\FixtureBase');
	foreach (['first', 'second'] as $pcreAttempt) {
		try {
			$pcreThrowObservations[$pcreSide][$pcreAttempt] = $pcreDescriber->describeProperty($pcreExtension->getProperty($pcreThrowClass, 'inferredFromConstructor', $pcreScopes['outOfClass']));
		} catch (\Throwable $e) {
			$pcreThrowObservations[$pcreSide][$pcreAttempt] = 'throws ' . $pcreDescriber->normalizeClass(get_class($e)) . ': ' . $e->getMessage();
		}
		$pcreThrowObservations[$pcreSide][$pcreAttempt . ' in process'] = (new ReflectionProperty($pcreExtension, 'inferClassConstructorPropertyTypesInProcess'))->getValue($pcreExtension);
	}
}
check(
	$pcreThrowObservations['php'] === $pcreThrowObservations['native'],
	'PhpClassReflectionExtension inference after a throwing one: ' . ($pcreFirstDifference($pcreThrowObservations['php'], $pcreThrowObservations['native']) ?? ''),
);
// }}}

if (isset($pcreStandalone)) {
	echo $failures === 0 ? "ALL OK\n" : "$failures failure(s)\n";
	exit($failures === 0 ? 0 : 1);
}

}

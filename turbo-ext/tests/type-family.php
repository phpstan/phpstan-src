<?php declare(strict_types = 1);

/**
 * Observations of the shadowed Type classes under their real names, the
 * input of smoke.php's differential for the Type ports: run once as the
 * PHP twins (`php`) and once with the native classes activated in their
 * place (`native` — Runtime::activateShadowing() with the manifest, the
 * way TurboExtensionEnabler runs it in production), printing one JSON
 * object of observations; smoke.php runs both and requires them to be
 * identical.
 *
 * A Type never acts alone: its results flow into the PHP compound types
 * (UnionType::isSubTypeOf() and friends) and back through `self`-typed
 * statics such as IsSuperTypeOfResult::extremeIdentity(), so the prefixed
 * side-by-side declaration of tests/activate-prefixed.php cannot serve
 * here — a native result object meeting the PHP result class there is a
 * TypeError. Keeping a whole Type graph on one implementation per process
 * is what makes the comparison exact.
 *
 *   php -d extension=.../phpstan_turbo.so tests/type-family.php php
 *   php -d extension=.../phpstan_turbo.so tests/type-family.php native
 */

$root = dirname(__DIR__, 2);
$mode = $argv[1] ?? '';
if (!in_array($mode, ['php', 'native'], true)) {
	fwrite(STDERR, "usage: type-family.php php|native\n");
	exit(2);
}
if (!extension_loaded('phpstan_turbo')) {
	fwrite(STDERR, "the phpstan_turbo extension is not loaded\n");
	exit(2);
}

require_once $root . '/vendor/autoload.php';

$manifestFile = $root . '/vendor/turbo-shadowed-classes.json';
$classMapFile = $root . '/vendor/turbo-class-map.php';
if (!is_file($manifestFile) || !is_file($classMapFile)) {
	fwrite(STDERR, "vendor/turbo-shadowed-classes.json or vendor/turbo-class-map.php does not exist — run composer dump-autoload first\n");
	exit(2);
}
$manifest = json_decode(file_get_contents($manifestFile), true, 8, JSON_THROW_ON_ERROR);

if ($mode === 'native') {
	$twinFiles = [];
	foreach ($manifest as $className => $entry) {
		$twinFiles[$className] = $root . '/' . $entry['php'];
	}
	\PHPStanTurbo\Runtime::configure(require $classMapFile);
	\PHPStanTurbo\Runtime::activateShadowing($twinFiles);
}

$observations = [];

// which implementation answered: smoke.php holds the php run to false and
// the native run to true, so the two sets can never be one implementation
// compared against itself
foreach ([\PHPStan\Type\BooleanType::class, \PHPStan\Type\Constant\ConstantBooleanType::class, \PHPStan\Type\IntegerType::class, \PHPStan\Type\Constant\ConstantIntegerType::class, \PHPStan\Type\IntegerRangeType::class, \PHPStan\Type\StringType::class, \PHPStan\Type\Constant\ConstantStringType::class, \PHPStan\Type\ClassStringType::class, \PHPStan\Type\Generic\GenericClassStringType::class] as $typeClass) {
	$observations["native $typeClass"] = (new ReflectionMethod($typeClass, 'describe'))->isInternal();
}

// a Type-valued result: class name plus precise description; results,
// trinaries and PHPDoc nodes by their description
$view = static function (mixed $v) use (&$view): mixed {
	if ($v instanceof \PHPStan\Type\Type) {
		return [get_class($v), $v->describe(\PHPStan\Type\VerbosityLevel::precise())];
	}
	if ($v instanceof \PHPStan\TrinaryLogic) {
		return $v->describe();
	}
	if ($v instanceof \PHPStan\Type\IsSuperTypeOfResult || $v instanceof \PHPStan\Type\AcceptsResult) {
		return [$v->result->describe(), $v->reasons];
	}
	if ($v instanceof \PHPStan\PhpDocParser\Ast\Type\TypeNode) {
		return (string) $v;
	}
	if ($v instanceof \PHPStan\Type\Generic\TemplateTypeMap) {
		return count($v->getTypes());
	}
	if ($v instanceof \PHPStan\Type\ClassNameToObjectTypeResult) {
		return [$view($v->type), $v->uncertainty];
	}
	if (is_array($v)) {
		return array_map($view, $v);
	}
	if (is_object($v)) {
		return get_class($v);
	}
	return $v;
};

// ---- BooleanType / ConstantBooleanType ----
$boolPhpVersion = new \PHPStan\Php\PhpVersion(80400);
$boolOthers = static fn (string $bool, string $constBool): array => [
	'bool' => new $bool(),
	'true' => new $constBool(true),
	'false' => new $constBool(false),
	'int' => new \PHPStan\Type\IntegerType(),
	'int1' => new \PHPStan\Type\Constant\ConstantIntegerType(1),
	'mixed' => new \PHPStan\Type\MixedType(),
	'null' => new \PHPStan\Type\NullType(),
	'string' => new \PHPStan\Type\StringType(),
	'string0' => new \PHPStan\Type\Constant\ConstantStringType('0'),
	'union' => new \PHPStan\Type\UnionType([new \PHPStan\Type\Constant\ConstantBooleanType(true), new \PHPStan\Type\NullType()]),
	'never' => new \PHPStan\Type\NeverType(),
	'array' => new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()),
];
{
	$boolClass = \PHPStan\Type\BooleanType::class;
	$constBoolClass = \PHPStan\Type\Constant\ConstantBooleanType::class;
	$r = [];
	$others = $boolOthers($boolClass, $constBoolClass);
	foreach (['bool' => new $boolClass(), 'true' => new $constBoolClass(true), 'false' => new $constBoolClass(false)] as $name => $subject) {
		$r["$name instanceof"] = [$subject instanceof \PHPStan\Type\Type, $subject instanceof $boolClass, $subject instanceof \PHPStan\Type\ConstantScalarType];
		foreach (['typeOnly' => \PHPStan\Type\VerbosityLevel::typeOnly(), 'value' => \PHPStan\Type\VerbosityLevel::value(), 'precise' => \PHPStan\Type\VerbosityLevel::precise(), 'cache' => \PHPStan\Type\VerbosityLevel::cache()] as $levelName => $level) {
			$r["$name describe $levelName"] = $subject->describe($level);
		}
		foreach ($others as $otherName => $other) {
			$r["$name isSuperTypeOf $otherName"] = $view($subject->isSuperTypeOf($other));
			$r["$name accepts $otherName"] = $view($subject->accepts($other, true));
			$r["$name accepts-loose $otherName"] = $view($subject->accepts($other, false));
			$r["$name equals $otherName"] = $subject->equals($other);
			$r["$name tryRemove $otherName"] = $view($subject->tryRemove($other));
			$r["$name looseCompare $otherName"] = $view($subject->looseCompare($other, $boolPhpVersion));
			$r["$name isSmallerThan $otherName"] = $view($subject->isSmallerThan($other, $boolPhpVersion));
			$r["$name isSmallerThanOrEqual $otherName"] = $view($subject->isSmallerThanOrEqual($other, $boolPhpVersion));
			$r["$name traverseSimultaneously $otherName"] = $view($subject->traverseSimultaneously($other, static fn ($a, $b) => $a));
			$r["$name getOffsetValueType $otherName"] = $view($subject->getOffsetValueType($other));
			$r["$name hasOffsetValueType $otherName"] = $view($subject->hasOffsetValueType($other));
			$r["$name exponentiate $otherName"] = $view($subject->exponentiate($other));
		}
		foreach (['toBoolean', 'toNumber', 'toInteger', 'toFloat', 'toString', 'toArray', 'toArrayKey', 'toBitwiseNotType', 'toAbsoluteNumber', 'toGetClassResultType', 'toObjectTypeForInstanceofCheck',
			'isTrue', 'isFalse', 'isBoolean', 'isScalar', 'isNull', 'isInteger', 'isFloat', 'isString', 'isNumericString', 'isNonEmptyString', 'isNonFalsyString', 'isLiteralString', 'isLowercaseString', 'isUppercaseString', 'isClassString', 'isVoid',
			'isConstantValue', 'isConstantScalarValue', 'getConstantScalarTypes', 'getConstantScalarValues', 'getFiniteTypes', 'isObject', 'isEnum', 'getArrays', 'getConstantArrays', 'getConstantStrings', 'getReferencedClasses', 'getObjectClassNames', 'getObjectClassReflections',
			'getClassStringType', 'getClassStringObjectType', 'getObjectTypeOrClassStringObjectType', 'canAccessProperties', 'canCallMethods', 'canAccessConstants', 'isIterable', 'isIterableAtLeastOnce', 'getArraySize', 'getIterableKeyType', 'getFirstIterableKeyType', 'getLastIterableKeyType',
			'getIterableValueType', 'getFirstIterableValueType', 'getLastIterableValueType', 'isArray', 'isConstantArray', 'isOversizedArray', 'isList', 'isOffsetAccessible', 'isOffsetAccessLegal', 'getKeysArray', 'getValuesArray', 'flipArray', 'popArray', 'shiftArray', 'shuffleArray',
			'makeListMaybe', 'makeAllArrayKeysOptional', 'filterArrayRemovingFalsey', 'getEnumCases', 'getEnumCaseObject', 'isCallable', 'isCloneable', 'toPhpDocNode', 'getReferencedTemplateTypes'] as $method) {
			if ($method === 'getReferencedTemplateTypes') {
				$r["$name $method"] = $view($subject->$method(\PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()));
				continue;
			}
			$r["$name $method"] = $view($subject->$method());
		}
		foreach (['getSmallerType', 'getSmallerOrEqualType', 'getGreaterType', 'getGreaterOrEqualType'] as $method) {
			$r["$name $method"] = $view($subject->$method($boolPhpVersion));
		}
		foreach ([\PHPStan\Type\GeneralizePrecision::lessSpecific(), \PHPStan\Type\GeneralizePrecision::moreSpecific(), \PHPStan\Type\GeneralizePrecision::templateArgument()] as $i => $precision) {
			$r["$name generalize $i"] = $view($subject->generalize($precision));
		}
		$r["$name toCoercedArgumentType"] = [$view($subject->toCoercedArgumentType(true)), $view($subject->toCoercedArgumentType(false))];
		$r["$name traverse identity"] = $subject->traverse(static fn ($t) => $t) === $subject;
		$r["$name inferTemplateTypes"] = $view($subject->inferTemplateTypes($others['int']));
		$r["$name getTemplateType"] = $view($subject->getTemplateType('Foo', 'T'));
		$r["$name hasProperty"] = $view($subject->hasProperty('x'));
		$r["$name hasMethod"] = $view($subject->hasMethod('x'));
		$r["$name hasConstant"] = $view($subject->hasConstant('X'));
		$r["$name setOffsetValueType"] = $view($subject->setOffsetValueType(null, $others['int']));
		$r["$name unsetOffset"] = $view($subject->unsetOffset($others['int']));
		$r["$name mapValueType"] = $view($subject->mapValueType(static fn ($t) => $t));
		$r["$name mapKeyType"] = $view($subject->mapKeyType(static fn ($t) => $t));
		$r["$name changeKeyCaseArray"] = $view($subject->changeKeyCaseArray(null));
		$r["$name toObjectTypeForIsACheck"] = $view($subject->toObjectTypeForIsACheck($others['mixed'], true, true));
		foreach (['getProperty', 'getMethod', 'getConstant'] as $method) {
			try {
				$args = $method === 'getConstant' ? ['X'] : ['x', new \PHPStan\Analyser\OutOfClassScope()];
				$subject->$method(...$args);
				$r["$name $method"] = 'no throw';
			} catch (\PHPStan\ShouldNotHappenException $e) {
				$r["$name $method"] = 'ShouldNotHappenException';
			}
		}
		if ($subject instanceof \PHPStan\Type\ConstantScalarType) {
			$r["$name getValue"] = $subject->getValue();
		}
	}
	$r['true equals constant'] = (new $constBoolClass(true))->equals(new $constBoolClass(true));
	$r['true equals false'] = (new $constBoolClass(true))->equals(new $constBoolClass(false));
	foreach ($r as $key => $value) {
		$observations["bool $key"] = $value;
	}
}


// ---- IntegerType / ConstantIntegerType / IntegerRangeType ----
$intPhpVersions = [new \PHPStan\Php\PhpVersion(70400), new \PHPStan\Php\PhpVersion(80400)];
$intOthers = static fn (string $int, string $constInt, string $range): array => [
	'int' => new $int(),
	'int0' => new $constInt(0),
	'int1' => new $constInt(1),
	'int-1' => new $constInt(-1),
	'intMax' => new $constInt(PHP_INT_MAX),
	'intMin' => new $constInt(PHP_INT_MIN),
	'range0-10' => $range::fromInterval(0, 10),
	'range-5-5' => $range::fromInterval(-5, 5),
	'range3-7' => $range::fromInterval(3, 7),
	'range20-30' => $range::fromInterval(20, 30),
	'rangeMin-0' => $range::fromInterval(null, 0),
	'range1-max' => $range::fromInterval(1, null),
	'rangeMin--1' => $range::fromInterval(null, -1),
	'rangeBig' => $range::fromInterval(PHP_INT_MIN + 1, PHP_INT_MAX - 1),
	'rangeTop' => $range::fromInterval(PHP_INT_MAX - 3, PHP_INT_MAX),
	'rangeBottom' => $range::fromInterval(PHP_INT_MIN, PHP_INT_MIN + 3),
	'float' => new \PHPStan\Type\FloatType(),
	'float0' => new \PHPStan\Type\Constant\ConstantFloatType(0.0),
	'float2.5' => new \PHPStan\Type\Constant\ConstantFloatType(2.5),
	'float-2.5' => new \PHPStan\Type\Constant\ConstantFloatType(-2.5),
	'floatHuge' => new \PHPStan\Type\Constant\ConstantFloatType(1e30),
	'bool' => new \PHPStan\Type\BooleanType(),
	'true' => new \PHPStan\Type\Constant\ConstantBooleanType(true),
	'false' => new \PHPStan\Type\Constant\ConstantBooleanType(false),
	'mixed' => new \PHPStan\Type\MixedType(),
	'null' => new \PHPStan\Type\NullType(),
	'string' => new \PHPStan\Type\StringType(),
	'string0' => new \PHPStan\Type\Constant\ConstantStringType('0'),
	'stringFoo' => new \PHPStan\Type\Constant\ConstantStringType('foo'),
	'numericString' => new \PHPStan\Type\IntersectionType([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryNumericStringType()]),
	'union' => new \PHPStan\Type\UnionType([new $constInt(1), new $constInt(2), new $constInt(3)]),
	'unionMixed' => new \PHPStan\Type\UnionType([new $constInt(5), new \PHPStan\Type\NullType()]),
	'unionRanges' => new \PHPStan\Type\UnionType([$range::fromInterval(0, 5), $range::fromInterval(8, 10)]),
	'never' => new \PHPStan\Type\NeverType(),
	'array' => new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()),
	'emptyArray' => new \PHPStan\Type\Constant\ConstantArrayType([], []),
	'object' => new \PHPStan\Type\ObjectType(\stdClass::class),
];
{
	$intClass = \PHPStan\Type\IntegerType::class;
	$constIntClass = \PHPStan\Type\Constant\ConstantIntegerType::class;
	$rangeClass = \PHPStan\Type\IntegerRangeType::class;
	$r = [];
	$others = $intOthers($intClass, $constIntClass, $rangeClass);
	$subjects = [
		'int' => new $intClass(),
		'const0' => new $constIntClass(0),
		'const1' => new $constIntClass(1),
		'const-1' => new $constIntClass(-1),
		'constMax' => new $constIntClass(PHP_INT_MAX),
		'constMin' => new $constIntClass(PHP_INT_MIN),
		'range0-10' => $rangeClass::fromInterval(0, 10),
		'range-5-5' => $rangeClass::fromInterval(-5, 5),
		'range3-7' => $rangeClass::fromInterval(3, 7),
		'rangeMin-0' => $rangeClass::fromInterval(null, 0),
		'range1-max' => $rangeClass::fromInterval(1, null),
		'rangeMin--1' => $rangeClass::fromInterval(null, -1),
		'range2-max' => $rangeClass::fromInterval(2, null),
		'rangeBig' => $rangeClass::fromInterval(PHP_INT_MIN + 1, PHP_INT_MAX - 1),
		'rangeTop' => $rangeClass::fromInterval(PHP_INT_MAX - 3, PHP_INT_MAX),
		'rangeBottom' => $rangeClass::fromInterval(PHP_INT_MIN, PHP_INT_MIN + 3),
		'rangeMin-max-1' => $rangeClass::fromInterval(null, PHP_INT_MAX - 1),
	];
	foreach ($subjects as $name => $subject) {
		$r["$name class"] = $view($subject);
		$r["$name instanceof"] = [$subject instanceof \PHPStan\Type\Type, $subject instanceof $intClass, $subject instanceof \PHPStan\Type\ConstantScalarType, $subject instanceof \PHPStan\Type\CompoundType];
		foreach (['typeOnly' => \PHPStan\Type\VerbosityLevel::typeOnly(), 'value' => \PHPStan\Type\VerbosityLevel::value(), 'precise' => \PHPStan\Type\VerbosityLevel::precise(), 'cache' => \PHPStan\Type\VerbosityLevel::cache()] as $levelName => $level) {
			$r["$name describe $levelName"] = $subject->describe($level);
		}
		foreach ($others as $otherName => $other) {
			$r["$name isSuperTypeOf $otherName"] = $view($subject->isSuperTypeOf($other));
			$r["$name accepts $otherName"] = $view($subject->accepts($other, true));
			$r["$name accepts-loose $otherName"] = $view($subject->accepts($other, false));
			$r["$name equals $otherName"] = $subject->equals($other);
			$r["$name tryRemove $otherName"] = $view($subject->tryRemove($other));
			foreach ($intPhpVersions as $vi => $phpVersion) {
				$r["$name looseCompare $otherName $vi"] = $view($subject->looseCompare($other, $phpVersion));
				$r["$name isSmallerThan $otherName $vi"] = $view($subject->isSmallerThan($other, $phpVersion));
				$r["$name isSmallerThanOrEqual $otherName $vi"] = $view($subject->isSmallerThanOrEqual($other, $phpVersion));
			}
			$r["$name traverseSimultaneously $otherName"] = $view($subject->traverseSimultaneously($other, static fn ($a, $b) => $a));
			$r["$name getOffsetValueType $otherName"] = $view($subject->getOffsetValueType($other));
			$r["$name hasOffsetValueType $otherName"] = $view($subject->hasOffsetValueType($other));
			try {
				$r["$name exponentiate $otherName"] = $view($subject->exponentiate($other));
			} catch (\Throwable $e) {
				$r["$name exponentiate $otherName"] = get_class($e);
			}
			if ($subject instanceof \PHPStan\Type\CompoundType) {
				$r["$name isSubTypeOf $otherName"] = $view($subject->isSubTypeOf($other));
				$r["$name isAcceptedBy $otherName"] = $view($subject->isAcceptedBy($other, true));
				$r["$name isAcceptedBy-loose $otherName"] = $view($subject->isAcceptedBy($other, false));
				foreach ($intPhpVersions as $vi => $phpVersion) {
					$r["$name isGreaterThan $otherName $vi"] = $view($subject->isGreaterThan($other, $phpVersion));
					$r["$name isGreaterThanOrEqual $otherName $vi"] = $view($subject->isGreaterThanOrEqual($other, $phpVersion));
				}
				$r["$name tryUnion $otherName"] = $view($subject->tryUnion($other));
				$r["$name tryIntersect $otherName"] = $view($subject->tryIntersect($other));
			}
		}
		foreach (['toBoolean', 'toNumber', 'toInteger', 'toFloat', 'toString', 'toArray', 'toArrayKey', 'toBitwiseNotType', 'toAbsoluteNumber', 'toGetClassResultType', 'toObjectTypeForInstanceofCheck',
			'isTrue', 'isFalse', 'isBoolean', 'isScalar', 'isNull', 'isInteger', 'isFloat', 'isString', 'isNumericString', 'isNonEmptyString', 'isNonFalsyString', 'isLiteralString', 'isLowercaseString', 'isUppercaseString', 'isClassString', 'isVoid',
			'isConstantValue', 'isConstantScalarValue', 'getConstantScalarTypes', 'getConstantScalarValues', 'getFiniteTypes', 'isObject', 'isEnum', 'getArrays', 'getConstantArrays', 'getConstantStrings', 'getReferencedClasses', 'getObjectClassNames', 'getObjectClassReflections',
			'getClassStringType', 'getClassStringObjectType', 'getObjectTypeOrClassStringObjectType', 'canAccessProperties', 'canCallMethods', 'canAccessConstants', 'isIterable', 'isIterableAtLeastOnce', 'getArraySize', 'getIterableKeyType', 'getFirstIterableKeyType', 'getLastIterableKeyType',
			'getIterableValueType', 'getFirstIterableValueType', 'getLastIterableValueType', 'isArray', 'isConstantArray', 'isOversizedArray', 'isList', 'isOffsetAccessible', 'isOffsetAccessLegal', 'getKeysArray', 'getValuesArray', 'flipArray', 'popArray', 'shiftArray', 'shuffleArray',
			'makeListMaybe', 'makeAllArrayKeysOptional', 'filterArrayRemovingFalsey', 'getEnumCases', 'getEnumCaseObject', 'isCallable', 'isCloneable', 'toPhpDocNode', 'getReferencedTemplateTypes', 'hasTemplateOrLateResolvableType'] as $method) {
			if ($method === 'getReferencedTemplateTypes') {
				$r["$name $method"] = $view($subject->$method(\PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()));
				continue;
			}
			$r["$name $method"] = $view($subject->$method());
		}
		foreach (['getSmallerType', 'getSmallerOrEqualType', 'getGreaterType', 'getGreaterOrEqualType'] as $method) {
			$r["$name $method"] = $view($subject->$method($intPhpVersions[1]));
		}
		foreach ([\PHPStan\Type\GeneralizePrecision::lessSpecific(), \PHPStan\Type\GeneralizePrecision::moreSpecific(), \PHPStan\Type\GeneralizePrecision::templateArgument()] as $i => $precision) {
			$r["$name generalize $i"] = $view($subject->generalize($precision));
		}
		$r["$name toCoercedArgumentType"] = [$view($subject->toCoercedArgumentType(true)), $view($subject->toCoercedArgumentType(false))];
		$r["$name traverse identity"] = $subject->traverse(static fn ($t) => $t) === $subject;
		$r["$name inferTemplateTypes"] = $view($subject->inferTemplateTypes($others['int']));
		$r["$name getTemplateType"] = $view($subject->getTemplateType('Foo', 'T'));
		$r["$name hasProperty"] = $view($subject->hasProperty('x'));
		$r["$name hasMethod"] = $view($subject->hasMethod('x'));
		$r["$name hasConstant"] = $view($subject->hasConstant('X'));
		$r["$name setOffsetValueType"] = $view($subject->setOffsetValueType(null, $others['int']));
		$r["$name unsetOffset"] = $view($subject->unsetOffset($others['int']));
		$r["$name toObjectTypeForIsACheck"] = $view($subject->toObjectTypeForIsACheck($others['mixed'], true, true));
		foreach (['getProperty', 'getMethod', 'getConstant'] as $method) {
			try {
				$args = $method === 'getConstant' ? ['X'] : ['x', new \PHPStan\Analyser\OutOfClassScope()];
				$subject->$method(...$args);
				$r["$name $method"] = 'no throw';
			} catch (\PHPStan\ShouldNotHappenException $e) {
				$r["$name $method"] = 'ShouldNotHappenException';
			}
		}
		if ($subject instanceof \PHPStan\Type\ConstantScalarType) {
			$r["$name getValue"] = $subject->getValue();
		}
		if ($subject instanceof $rangeClass) {
			$r["$name getMin/getMax"] = [$subject->getMin(), $subject->getMax()];
			foreach ([0, 1, -1, 5, -5, PHP_INT_MAX, PHP_INT_MIN, 1000] as $amount) {
				$r["$name shift $amount"] = $view($subject->shift($amount));
			}
		}
	}
	// the factories, over int and float bounds
	foreach ([[0, 10], [10, 0], [5, 5], [null, 5], [5, null], [null, null], [PHP_INT_MIN, PHP_INT_MIN], [PHP_INT_MAX, PHP_INT_MAX], [null, PHP_INT_MIN], [PHP_INT_MAX, null], [PHP_INT_MIN, PHP_INT_MAX], [-3, 3]] as $i => [$min, $max]) {
		foreach ([0, 1, -1, 3, PHP_INT_MAX, PHP_INT_MIN] as $shift) {
			try {
				$r["fromInterval $i shift $shift"] = $view($rangeClass::fromInterval($min, $max, $shift));
			} catch (\TypeError $e) {
				$r["fromInterval $i shift $shift"] = 'TypeError';
			}
		}
	}
	foreach ([0, 1, -1, 42, PHP_INT_MAX, PHP_INT_MIN, PHP_INT_MAX - 1, PHP_INT_MIN + 1, 0.0, 0.5, -0.5, 2.5, -2.5, 1e30, -1e30, 9.2233720368547758E+18, -9.2233720368547758E+18, 9.2e18, -9.2e18, (float) PHP_INT_MAX, (float) PHP_INT_MIN, NAN, INF, -INF] as $value) {
		$key = is_float($value) ? var_export($value, true) : (string) $value;
		foreach (['createAllSmallerThan', 'createAllSmallerThanOrEqualTo', 'createAllGreaterThan', 'createAllGreaterThanOrEqualTo'] as $factory) {
			$r["$factory $key"] = @$view($rangeClass::$factory($value));
		}
	}
	foreach (['5', true, null] as $value) {
		foreach (['createAllSmallerThan', 'createAllGreaterThanOrEqualTo'] as $factory) {
			try {
				$r["$factory " . var_export($value, true)] = $view($rangeClass::$factory($value));
			} catch (\TypeError $e) {
				$r["$factory " . var_export($value, true)] = 'TypeError';
			}
		}
	}
	$r['const equals const'] = [(new $constIntClass(3))->equals(new $constIntClass(3)), (new $constIntClass(3))->equals(new $constIntClass(4))];
	$r['range equals range'] = [$rangeClass::fromInterval(1, 9)->equals($rangeClass::fromInterval(1, 9)), $rangeClass::fromInterval(1, 9)->equals($rangeClass::fromInterval(1, 8)), $rangeClass::fromInterval(null, 9)->equals($rangeClass::fromInterval(null, 9))];
	try {
		new $rangeClass(1, 2);
		$r['private constructor'] = 'no throw';
	} catch (\Error $e) {
		$r['private constructor'] = get_class($e);
	}
	// an uninitialized instance: every typed-slot read raises the same Error
	$uninitialized = (new \ReflectionClass($constIntClass))->newInstanceWithoutConstructor();
	try {
		$uninitialized->getValue();
		$r['uninitialized getValue'] = 'no throw';
	} catch (\Error $e) {
		$r['uninitialized getValue'] = [get_class($e), $e->getMessage()];
	}
	$uninitializedRange = (new \ReflectionClass($rangeClass))->newInstanceWithoutConstructor();
	try {
		$uninitializedRange->describe(\PHPStan\Type\VerbosityLevel::precise());
		$r['uninitialized describe'] = 'no throw';
	} catch (\Error $e) {
		$r['uninitialized describe'] = [get_class($e), $e->getMessage()];
	}
	// a PHP subclass overriding what the natives call through $this
	$template = new \PHPStan\Type\Generic\TemplateIntegerType(
		\PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('foo'),
		new \PHPStan\Type\Generic\TemplateTypeParameterStrategy(),
		\PHPStan\Type\Generic\TemplateTypeVariance::createInvariant(),
		'T',
		new $intClass(),
		null,
	);
	$r['template describe'] = $template->describe(\PHPStan\Type\VerbosityLevel::precise());
	$r['template isSuperTypeOf const'] = $view($template->isSuperTypeOf($others['int1']));
	$r['range isSuperTypeOf template'] = $view($subjects['range0-10']->isSuperTypeOf($template));
	$r['const isSuperTypeOf template'] = $view($subjects['const1']->isSuperTypeOf($template));
	$r['range tryUnion template'] = $view($subjects['range0-10']->tryUnion($template));
	foreach ($r as $key => $value) {
		$observations["int $key"] = $value;
	}
}


// ---- StringType / ConstantStringType / ClassStringType / GenericClassStringType ----
// a reflection provider behind ReflectionProviderStaticAccessor, as the
// class-string and callable queries need one (an existing class name, a
// known function, a static method); the PhpVersion accessor for isCallable()
$stringContainer = (new \PHPStan\DependencyInjection\ContainerFactory($root))->create(sys_get_temp_dir() . '/phpstan-turbo-type-family', [], []);
$stringReflectionProvider = $stringContainer->getByType(\PHPStan\Reflection\ReflectionProvider::class);
\PHPStan\Reflection\ReflectionProviderStaticAccessor::registerInstance($stringReflectionProvider);
\PHPStan\Reflection\PhpVersionStaticAccessor::registerInstance($stringContainer->getByType(\PHPStan\Php\PhpVersion::class));
$stringPhpVersions = [new \PHPStan\Php\PhpVersion(70400), new \PHPStan\Php\PhpVersion(80400)];
$stringOthers = static fn (string $string, string $constString, string $classString, string $genericClassString): array => [
	'string' => new $string(),
	'stringEmpty' => new $constString(''),
	'stringAbc' => new $constString('abc'),
	'string0' => new $constString('0'),
	'string123' => new $constString('123'),
	'stringTrinary' => new $constString(\PHPStan\TrinaryLogic::class),
	'stringTrinaryClass' => new $constString(\PHPStan\TrinaryLogic::class, true),
	'stringStrlen' => new $constString('strlen'),
	'classString' => new $classString(),
	'genericTrinary' => new $genericClassString(new \PHPStan\Type\ObjectType(\PHPStan\TrinaryLogic::class)),
	'genericType' => new $genericClassString(new \PHPStan\Type\ObjectType(\PHPStan\Type\Type::class)),
	'genericNonexistent' => new $genericClassString(new \PHPStan\Type\ObjectType('NonexistentClass')),
	'genericMixed' => new $genericClassString(new \PHPStan\Type\MixedType()),
	'genericObject' => new $genericClassString(new \PHPStan\Type\ObjectWithoutClassType()),
	'genericStatic' => new $genericClassString(new \PHPStan\Type\StaticType($stringReflectionProvider->getClass(\PHPStan\TrinaryLogic::class))),
	'genericTemplate' => new $genericClassString(\PHPStan\Type\Generic\TemplateTypeFactory::create(\PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('foo'), 'T', new \PHPStan\Type\ObjectType(\PHPStan\Type\Type::class), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant())),
	'genericUnion' => new $genericClassString(new \PHPStan\Type\UnionType([new \PHPStan\Type\ObjectType(\PHPStan\TrinaryLogic::class), new \PHPStan\Type\ObjectType(\PHPStan\Type\VerbosityLevel::class)])),
	'nonEmptyString' => new \PHPStan\Type\IntersectionType([new $string(), new \PHPStan\Type\Accessory\AccessoryNonEmptyStringType()]),
	'numericString' => new \PHPStan\Type\IntersectionType([new $string(), new \PHPStan\Type\Accessory\AccessoryNumericStringType()]),
	'literalString' => new \PHPStan\Type\IntersectionType([new $string(), new \PHPStan\Type\Accessory\AccessoryLiteralStringType()]),
	'accessoryNonEmpty' => new \PHPStan\Type\Accessory\AccessoryNonEmptyStringType(),
	'int' => new \PHPStan\Type\IntegerType(),
	'int0' => new \PHPStan\Type\Constant\ConstantIntegerType(0),
	'int1' => new \PHPStan\Type\Constant\ConstantIntegerType(1),
	'int2' => new \PHPStan\Type\Constant\ConstantIntegerType(2),
	'int-1' => new \PHPStan\Type\Constant\ConstantIntegerType(-1),
	'int-4' => new \PHPStan\Type\Constant\ConstantIntegerType(-4),
	'int5' => new \PHPStan\Type\Constant\ConstantIntegerType(5),
	'range0-1' => \PHPStan\Type\IntegerRangeType::fromInterval(0, 1),
	'range0-10' => \PHPStan\Type\IntegerRangeType::fromInterval(0, 10),
	'range-2-1' => \PHPStan\Type\IntegerRangeType::fromInterval(-2, 1),
	'range5-max' => \PHPStan\Type\IntegerRangeType::fromInterval(5, null),
	'float' => new \PHPStan\Type\FloatType(),
	'float2.5' => new \PHPStan\Type\Constant\ConstantFloatType(2.5),
	'bool' => new \PHPStan\Type\BooleanType(),
	'true' => new \PHPStan\Type\Constant\ConstantBooleanType(true),
	'false' => new \PHPStan\Type\Constant\ConstantBooleanType(false),
	'mixed' => new \PHPStan\Type\MixedType(),
	'null' => new \PHPStan\Type\NullType(),
	'union' => new \PHPStan\Type\UnionType([new $constString('a'), new $constString('b')]),
	'unionMixed' => new \PHPStan\Type\UnionType([new $constString('abc'), new \PHPStan\Type\NullType()]),
	'unionInts' => new \PHPStan\Type\UnionType([new \PHPStan\Type\Constant\ConstantIntegerType(0), new \PHPStan\Type\Constant\ConstantIntegerType(2)]),
	'never' => new \PHPStan\Type\NeverType(),
	'array' => new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()),
	'emptyArray' => new \PHPStan\Type\Constant\ConstantArrayType([], []),
	'object' => new \PHPStan\Type\ObjectType(\stdClass::class),
	'objectTrinary' => new \PHPStan\Type\ObjectType(\PHPStan\TrinaryLogic::class),
	'objectWithToString' => new \PHPStan\Type\ObjectType(\Exception::class),
	'objectWithoutClass' => new \PHPStan\Type\ObjectWithoutClassType(),
];
{
	$stringClass = \PHPStan\Type\StringType::class;
	$constStringClass = \PHPStan\Type\Constant\ConstantStringType::class;
	$classStringClass = \PHPStan\Type\ClassStringType::class;
	$genericClassStringClass = \PHPStan\Type\Generic\GenericClassStringType::class;
	$r = [];
	$others = $stringOthers($stringClass, $constStringClass, $classStringClass, $genericClassStringClass);
	$longString = str_repeat('abcdefghij', 10);
	$subjects = [
		'string' => new $stringClass(),
		'constEmpty' => new $constStringClass(''),
		'constAbc' => new $constStringClass('abc'),
		'const0' => new $constStringClass('0'),
		'const123' => new $constStringClass('123'),
		'const1e3' => new $constStringClass('1e3'),
		'constSpace1' => new $constStringClass(' 1'),
		'const2.5' => new $constStringClass('2.5'),
		'constFooBar' => new $constStringClass('Foo\\Bar'),
		'constTrinary' => new $constStringClass(\PHPStan\TrinaryLogic::class),
		'constTrinaryClass' => new $constStringClass(\PHPStan\TrinaryLogic::class, true),
		'constStrlen' => new $constStringClass('strlen'),
		'constStaticMethod' => new $constStringClass(\PHPStan\TrinaryLogic::class . '::createYes'),
		'constInstanceMethod' => new $constStringClass(\PHPStan\TrinaryLogic::class . '::yes'),
		'constMissingMethod' => new $constStringClass(\PHPStan\TrinaryLogic::class . '::nonexistent'),
		'constUnknownClassMethod' => new $constStringClass('Nonexistent\\Foo::bar'),
		'constQuotes' => new $constStringClass("with\"quotes\\and\nnewline"),
		'constLong' => new $constStringClass($longString),
		'constUpper' => new $constStringClass('ABC'),
		'constMixedCase' => new $constStringClass('Abc'),
		'constUtf8' => new $constStringClass('příliš žluťoučký kůň úpěl ďábelské ódy'),
		'constInvalidUtf8' => new $constStringClass(str_repeat("\xff", 30)),
		'classString' => new $classStringClass(),
		'genericTrinary' => new $genericClassStringClass(new \PHPStan\Type\ObjectType(\PHPStan\TrinaryLogic::class)),
		'genericType' => new $genericClassStringClass(new \PHPStan\Type\ObjectType(\PHPStan\Type\Type::class)),
		'genericNonexistent' => new $genericClassStringClass(new \PHPStan\Type\ObjectType('NonexistentClass')),
		'genericMixed' => new $genericClassStringClass(new \PHPStan\Type\MixedType()),
		'genericStatic' => new $genericClassStringClass(new \PHPStan\Type\StaticType($stringReflectionProvider->getClass(\PHPStan\TrinaryLogic::class))),
		'genericTemplate' => new $genericClassStringClass(\PHPStan\Type\Generic\TemplateTypeFactory::create(\PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('foo'), 'T', new \PHPStan\Type\ObjectType(\PHPStan\Type\Type::class), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant())),
		'genericUnion' => new $genericClassStringClass(new \PHPStan\Type\UnionType([new \PHPStan\Type\ObjectType(\PHPStan\TrinaryLogic::class), new \PHPStan\Type\ObjectType(\PHPStan\Type\VerbosityLevel::class)])),
	];
	$outOfClassScope = new \PHPStan\Analyser\OutOfClassScope();
	foreach ($subjects as $name => $subject) {
		$r["$name class"] = $view($subject);
		$r["$name instanceof"] = [$subject instanceof \PHPStan\Type\Type, $subject instanceof $stringClass, $subject instanceof $classStringClass, $subject instanceof \PHPStan\Type\ConstantScalarType];
		foreach (['typeOnly' => \PHPStan\Type\VerbosityLevel::typeOnly(), 'value' => \PHPStan\Type\VerbosityLevel::value(), 'precise' => \PHPStan\Type\VerbosityLevel::precise(), 'cache' => \PHPStan\Type\VerbosityLevel::cache()] as $levelName => $level) {
			$r["$name describe $levelName"] = $subject->describe($level);
			// the memoized description must read back the same
			$r["$name describe $levelName again"] = $subject->describe($level);
		}
		foreach ($others as $otherName => $other) {
			$r["$name isSuperTypeOf $otherName"] = $view($subject->isSuperTypeOf($other));
			$r["$name accepts $otherName"] = $view($subject->accepts($other, true));
			$r["$name accepts-loose $otherName"] = $view($subject->accepts($other, false));
			$r["$name equals $otherName"] = $subject->equals($other);
			$r["$name tryRemove $otherName"] = $view($subject->tryRemove($other));
			foreach ($stringPhpVersions as $vi => $phpVersion) {
				$r["$name looseCompare $otherName $vi"] = $view($subject->looseCompare($other, $phpVersion));
				$r["$name isSmallerThan $otherName $vi"] = $view($subject->isSmallerThan($other, $phpVersion));
				$r["$name isSmallerThanOrEqual $otherName $vi"] = $view($subject->isSmallerThanOrEqual($other, $phpVersion));
			}
			$r["$name traverseSimultaneously $otherName"] = $view($subject->traverseSimultaneously($other, static fn ($a, $b) => $a));
			$r["$name traverseSimultaneously-right $otherName"] = $view($subject->traverseSimultaneously($other, static fn ($a, $b) => $b));
			$r["$name getOffsetValueType $otherName"] = $view($subject->getOffsetValueType($other));
			$r["$name hasOffsetValueType $otherName"] = $view($subject->hasOffsetValueType($other));
			$r["$name setOffsetValueType $otherName"] = $view($subject->setOffsetValueType($other, $others['stringAbc']));
			$r["$name setOffsetValueType-x $otherName"] = $view($subject->setOffsetValueType($other, new $constStringClass('x')));
			$r["$name setOffsetValueType-int $otherName"] = $view($subject->setOffsetValueType($other, $others['int1']));
			$r["$name setOffsetValueType-array $otherName"] = $view($subject->setOffsetValueType($other, $others['array']));
			$r["$name setExistingOffsetValueType $otherName"] = $view($subject->setExistingOffsetValueType($other, new $constStringClass('x')));
			$r["$name unsetOffset $otherName"] = $view($subject->unsetOffset($other));
			$r["$name inferTemplateTypes $otherName"] = $view($subject->inferTemplateTypes($other));
			$r["$name toObjectTypeForIsACheck $otherName"] = [$view($subject->toObjectTypeForIsACheck($other, true, true)), $view($subject->toObjectTypeForIsACheck($other, false, true)), $view($subject->toObjectTypeForIsACheck($other, true, false)), $view($subject->toObjectTypeForIsACheck($other, false, false))];
			try {
				$r["$name exponentiate $otherName"] = $view($subject->exponentiate($other));
			} catch (\Throwable $e) {
				$r["$name exponentiate $otherName"] = get_class($e);
			}
		}
		foreach (['toBoolean', 'toNumber', 'toInteger', 'toFloat', 'toString', 'toArray', 'toArrayKey', 'toBitwiseNotType', 'toAbsoluteNumber', 'toGetClassResultType', 'toObjectTypeForInstanceofCheck',
			'isTrue', 'isFalse', 'isBoolean', 'isScalar', 'isNull', 'isInteger', 'isFloat', 'isString', 'isNumericString', 'isDecimalIntegerString', 'isNonEmptyString', 'isNonFalsyString', 'isLiteralString', 'isLowercaseString', 'isUppercaseString', 'isClassString', 'isVoid',
			'isConstantValue', 'isConstantScalarValue', 'getConstantScalarTypes', 'getConstantScalarValues', 'getFiniteTypes', 'isObject', 'isEnum', 'getArrays', 'getConstantArrays', 'getConstantStrings', 'getReferencedClasses', 'getObjectClassNames', 'getObjectClassReflections',
			'getClassStringType', 'getClassStringObjectType', 'getObjectTypeOrClassStringObjectType', 'canAccessProperties', 'canCallMethods', 'canAccessConstants', 'isIterable', 'isIterableAtLeastOnce', 'getArraySize', 'getIterableKeyType', 'getFirstIterableKeyType', 'getLastIterableKeyType',
			'getIterableValueType', 'getFirstIterableValueType', 'getLastIterableValueType', 'isArray', 'isConstantArray', 'isOversizedArray', 'isList', 'isOffsetAccessible', 'isOffsetAccessLegal', 'getKeysArray', 'getValuesArray', 'flipArray', 'popArray', 'shiftArray', 'shuffleArray',
			'makeListMaybe', 'makeAllArrayKeysOptional', 'filterArrayRemovingFalsey', 'getEnumCases', 'getEnumCaseObject', 'isCallable', 'isCloneable', 'toPhpDocNode', 'getReferencedTemplateTypes', 'hasTemplateOrLateResolvableType'] as $method) {
			if ($method === 'getReferencedTemplateTypes') {
				foreach ([\PHPStan\Type\Generic\TemplateTypeVariance::createInvariant(), \PHPStan\Type\Generic\TemplateTypeVariance::createCovariant(), \PHPStan\Type\Generic\TemplateTypeVariance::createContravariant()] as $vi => $variance) {
					$r["$name $method $vi"] = $view($subject->$method($variance));
				}
				continue;
			}
			$r["$name $method"] = $view($subject->$method());
		}
		foreach (['getSmallerType', 'getSmallerOrEqualType', 'getGreaterType', 'getGreaterOrEqualType'] as $method) {
			$r["$name $method"] = $view($subject->$method($stringPhpVersions[1]));
		}
		foreach ([\PHPStan\Type\GeneralizePrecision::lessSpecific(), \PHPStan\Type\GeneralizePrecision::moreSpecific(), \PHPStan\Type\GeneralizePrecision::templateArgument()] as $i => $precision) {
			$r["$name generalize $i"] = $view($subject->generalize($precision));
		}
		$r["$name toCoercedArgumentType"] = [$view($subject->toCoercedArgumentType(true)), $view($subject->toCoercedArgumentType(false))];
		$r["$name traverse identity"] = $subject->traverse(static fn ($t) => $t) === $subject;
		$r["$name traverse replaced"] = $view($subject->traverse(static fn ($t) => new \PHPStan\Type\ObjectType(\stdClass::class)));
		$r["$name getTemplateType"] = $view($subject->getTemplateType('Foo', 'T'));
		$r["$name hasProperty"] = $view($subject->hasProperty('x'));
		$r["$name hasMethod"] = $view($subject->hasMethod('x'));
		$r["$name hasConstant"] = [$view($subject->hasConstant('X')), $view($subject->hasConstant('YES'))];
		$r["$name setOffsetValueType null"] = [$view($subject->setOffsetValueType(null, $others['stringAbc'])), $view($subject->setOffsetValueType(null, $others['int1'], false))];
		$r["$name mapValueType"] = $view($subject->mapValueType(static fn ($t) => $t));
		$r["$name mapKeyType"] = $view($subject->mapKeyType(static fn ($t) => $t));
		$r["$name changeKeyCaseArray"] = $view($subject->changeKeyCaseArray(null));
		foreach (['getProperty', 'getMethod', 'getConstant'] as $method) {
			foreach (['x', 'YES'] as $memberName) {
				try {
					$args = $method === 'getConstant' ? [$memberName] : [$memberName, $outOfClassScope];
					$r["$name $method $memberName"] = $view($subject->$method(...$args));
				} catch (\PHPStan\ShouldNotHappenException $e) {
					$r["$name $method $memberName"] = 'ShouldNotHappenException';
				} catch (\Throwable $e) {
					$r["$name $method $memberName"] = [get_class($e), $e->getMessage()];
				}
			}
		}
		try {
			$acceptors = $subject->getCallableParametersAcceptors($outOfClassScope);
			$r["$name getCallableParametersAcceptors"] = array_map(static fn ($acceptor) => [get_class($acceptor), $acceptor->getReturnType()->describe(\PHPStan\Type\VerbosityLevel::precise()), count($acceptor->getParameters())], $acceptors);
		} catch (\PHPStan\ShouldNotHappenException $e) {
			$r["$name getCallableParametersAcceptors"] = 'ShouldNotHappenException';
		}
		if ($subject instanceof \PHPStan\Type\ConstantScalarType) {
			$r["$name getValue"] = $subject->getValue();
		}
		if ($subject instanceof $constStringClass) {
			$r["$name append"] = [$view($subject->append(new $constStringClass('xyz'))), $view($subject->append(new $constStringClass('')))];
			// the array-key memo must read back the same
			$r["$name toArrayKey again"] = $view($subject->toArrayKey());
		}
		if ($subject instanceof $genericClassStringClass) {
			$r["$name getGenericType"] = $view($subject->getGenericType());
		}
	}
	$r['const equals const'] = [(new $constStringClass('a'))->equals(new $constStringClass('a')), (new $constStringClass('a'))->equals(new $constStringClass('b')), (new $constStringClass('a'))->equals(new $constStringClass('a', true))];
	$r['generic equals generic'] = [$subjects['genericTrinary']->equals(new $genericClassStringClass(new \PHPStan\Type\ObjectType(\PHPStan\TrinaryLogic::class))), $subjects['genericTrinary']->equals($subjects['genericType']), $subjects['genericTrinary']->equals($subjects['classString'])];
	// an uninitialized instance: every typed-slot read raises the same Error
	foreach ([$constStringClass, $genericClassStringClass] as $uninitializedClass) {
		$uninitialized = (new \ReflectionClass($uninitializedClass))->newInstanceWithoutConstructor();
		foreach (['describe' => [\PHPStan\Type\VerbosityLevel::precise()], 'isSuperTypeOf' => [$others['int']], 'getConstantStrings' => [], 'isCallable' => [], 'toPhpDocNode' => []] as $method => $args) {
			try {
				$uninitialized->$method(...$args);
				$r["uninitialized $uninitializedClass $method"] = 'no throw';
			} catch (\Error $e) {
				$r["uninitialized $uninitializedClass $method"] = [get_class($e), $e->getMessage()];
			}
		}
	}
	// a PHP subclass overriding what the natives call through $this
	$templateConstant = new \PHPStan\Type\Generic\TemplateConstantStringType(
		\PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('foo'),
		new \PHPStan\Type\Generic\TemplateTypeParameterStrategy(),
		\PHPStan\Type\Generic\TemplateTypeVariance::createInvariant(),
		'T',
		new $constStringClass('abc'),
		null,
	);
	$r['template describe'] = $templateConstant->describe(\PHPStan\Type\VerbosityLevel::precise());
	$r['template isSuperTypeOf const'] = $view($templateConstant->isSuperTypeOf($others['stringAbc']));
	$r['template toArrayKey'] = $view($templateConstant->toArrayKey());
	$r['template generalize'] = $view($templateConstant->generalize(\PHPStan\Type\GeneralizePrecision::moreSpecific()));
	$r['const isSuperTypeOf template'] = $view($subjects['constAbc']->isSuperTypeOf($templateConstant));
	$r['string isSuperTypeOf template'] = $view($subjects['string']->isSuperTypeOf($templateConstant));
	$r['generic accepts template'] = $view($subjects['genericTrinary']->accepts($templateConstant, true));
	$anonymous = new class ('abc') extends \PHPStan\Type\Constant\ConstantStringType {

		public function getValue(): string
		{
			return 'overridden';
		}

	};
	$r['anonymous isNumericString'] = $view($anonymous->isNumericString());
	$r['anonymous isNonEmptyString'] = $view($anonymous->isNonEmptyString());
	$r['anonymous append'] = $view($anonymous->append(new $constStringClass('!')));
	$r['anonymous generalize'] = $view($anonymous->generalize(\PHPStan\Type\GeneralizePrecision::moreSpecific()));
	$r['string tryRemove anonymous'] = $view($subjects['string']->tryRemove($anonymous));
	$r['const isSuperTypeOf anonymous'] = $view($subjects['constAbc']->isSuperTypeOf($anonymous));
	foreach ($r as $key => $value) {
		$observations["string $key"] = $value;
	}
}


// observations holding bytes that are not UTF-8 (the invalid-UTF-8 subject's
// descriptions) go out base64-encoded so json_encode() keeps every byte
$encodable = static function (mixed $v) use (&$encodable): mixed {
	if (is_string($v) && !mb_check_encoding($v, 'UTF-8')) {
		return 'base64:' . base64_encode($v);
	}
	if (is_array($v)) {
		return array_map($encodable, $v);
	}
	return $v;
};

echo json_encode($encodable($observations), JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION), "\n";

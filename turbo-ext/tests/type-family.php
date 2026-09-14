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
foreach ([\PHPStan\Type\BooleanType::class, \PHPStan\Type\Constant\ConstantBooleanType::class, \PHPStan\Type\IntegerType::class, \PHPStan\Type\Constant\ConstantIntegerType::class, \PHPStan\Type\IntegerRangeType::class, \PHPStan\Type\StringType::class, \PHPStan\Type\Constant\ConstantStringType::class, \PHPStan\Type\ClassStringType::class, \PHPStan\Type\Generic\GenericClassStringType::class, \PHPStan\Type\FloatType::class, \PHPStan\Type\Constant\ConstantFloatType::class, \PHPStan\Type\NullType::class, \PHPStan\Type\VoidType::class, \PHPStan\Type\NeverType::class, \PHPStan\Type\MixedType::class, \PHPStan\Type\StrictMixedType::class, \PHPStan\Type\ObjectWithoutClassType::class, \PHPStan\Type\StaticType::class, \PHPStan\Type\ThisType::class, \PHPStan\Type\Generic\GenericStaticType::class, \PHPStan\Type\ObjectShapeType::class, \PHPStan\Type\NonexistentParentClassType::class, \PHPStan\Type\ArrayType::class, \PHPStan\Type\Accessory\NonEmptyArrayType::class, \PHPStan\Type\Accessory\AccessoryArrayListType::class, \PHPStan\Type\Accessory\OversizedArrayType::class, \PHPStan\Type\Accessory\HasOffsetType::class, \PHPStan\Type\Accessory\HasOffsetValueType::class, \PHPStan\Type\Accessory\AccessoryNumericStringType::class, \PHPStan\Type\Accessory\AccessoryNonEmptyStringType::class, \PHPStan\Type\Accessory\AccessoryNonFalsyStringType::class, \PHPStan\Type\Accessory\AccessoryLiteralStringType::class, \PHPStan\Type\Accessory\AccessoryLowercaseStringType::class, \PHPStan\Type\Accessory\AccessoryUppercaseStringType::class, \PHPStan\Type\Accessory\AccessoryDecimalIntegerStringType::class, \PHPStan\Type\Accessory\HasMethodType::class, \PHPStan\Type\Accessory\HasPropertyType::class, \PHPStan\Type\ObjectType::class, \PHPStan\Type\Generic\GenericObjectType::class, \PHPStan\Type\Enum\EnumCaseObjectType::class, \PHPStan\Type\IterableType::class, \PHPStan\Type\CallableType::class, \PHPStan\Type\ClosureType::class] as $typeClass) {
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


// ---- FloatType / ConstantFloatType / NullType / VoidType ----
// the string family's reflection provider and PhpVersion accessors stay
// registered from the section above (looseCompare() against objects with
// __toString() and LooseComparisonHelper consult them)
$floatPhpVersions = [new \PHPStan\Php\PhpVersion(70400), new \PHPStan\Php\PhpVersion(80400)];
$floatOthers = static fn (string $float, string $constFloat, string $null, string $void): array => [
	'float' => new $float(),
	'float0' => new $constFloat(0.0),
	'float-0' => new $constFloat(-0.0),
	'float1' => new $constFloat(1.0),
	'float1.5' => new $constFloat(1.5),
	'float-2.5' => new $constFloat(-2.5),
	'floatHuge' => new $constFloat(1e30),
	'floatNan' => new $constFloat(NAN),
	'floatInf' => new $constFloat(INF),
	'floatNegInf' => new $constFloat(-INF),
	'floatIntMax' => new $constFloat((float) PHP_INT_MAX),
	'null' => new $null(),
	'void' => new $void(),
	'int' => new \PHPStan\Type\IntegerType(),
	'int0' => new \PHPStan\Type\Constant\ConstantIntegerType(0),
	'int1' => new \PHPStan\Type\Constant\ConstantIntegerType(1),
	'int-1' => new \PHPStan\Type\Constant\ConstantIntegerType(-1),
	'intMax' => new \PHPStan\Type\Constant\ConstantIntegerType(PHP_INT_MAX),
	'range0-10' => \PHPStan\Type\IntegerRangeType::fromInterval(0, 10),
	'rangeMin--1' => \PHPStan\Type\IntegerRangeType::fromInterval(null, -1),
	'bool' => new \PHPStan\Type\BooleanType(),
	'true' => new \PHPStan\Type\Constant\ConstantBooleanType(true),
	'false' => new \PHPStan\Type\Constant\ConstantBooleanType(false),
	'string' => new \PHPStan\Type\StringType(),
	'stringEmpty' => new \PHPStan\Type\Constant\ConstantStringType(''),
	'string0' => new \PHPStan\Type\Constant\ConstantStringType('0'),
	'string1.5' => new \PHPStan\Type\Constant\ConstantStringType('1.5'),
	'stringAbc' => new \PHPStan\Type\Constant\ConstantStringType('abc'),
	'stringNull' => new \PHPStan\Type\Constant\ConstantStringType('null'),
	'numericString' => new \PHPStan\Type\IntersectionType([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryNumericStringType()]),
	'mixed' => new \PHPStan\Type\MixedType(),
	'mixedNotNull' => new \PHPStan\Type\MixedType(subtractedType: new $null()),
	'union' => new \PHPStan\Type\UnionType([new $constFloat(1.5), new $null()]),
	'unionFloats' => new \PHPStan\Type\UnionType([new $constFloat(1.0), new $constFloat(2.0)]),
	'unionInts' => new \PHPStan\Type\UnionType([new \PHPStan\Type\Constant\ConstantIntegerType(0), new \PHPStan\Type\Constant\ConstantIntegerType(2)]),
	'never' => new \PHPStan\Type\NeverType(),
	'array' => new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()),
	'emptyArray' => new \PHPStan\Type\Constant\ConstantArrayType([], []),
	'nonEmptyArray' => new \PHPStan\Type\Constant\ConstantArrayType([new \PHPStan\Type\Constant\ConstantIntegerType(0)], [new $null()], [1], [], \PHPStan\TrinaryLogic::createYes()),
	'object' => new \PHPStan\Type\ObjectType(\stdClass::class),
	'objectWithToString' => new \PHPStan\Type\ObjectType(\Exception::class),
	'objectWithoutClass' => new \PHPStan\Type\ObjectWithoutClassType(),
];
{
	$floatClass = \PHPStan\Type\FloatType::class;
	$constFloatClass = \PHPStan\Type\Constant\ConstantFloatType::class;
	$nullClass = \PHPStan\Type\NullType::class;
	$voidClass = \PHPStan\Type\VoidType::class;
	$r = [];
	$others = $floatOthers($floatClass, $constFloatClass, $nullClass, $voidClass);
	$subjects = [
		'float' => new $floatClass(),
		'const0' => new $constFloatClass(0.0),
		'const-0' => new $constFloatClass(-0.0),
		'const1' => new $constFloatClass(1.0),
		'const1.5' => new $constFloatClass(1.5),
		'const-2.5' => new $constFloatClass(-2.5),
		'const0.1' => new $constFloatClass(0.1),
		'const0.3' => new $constFloatClass(0.1 + 0.2),
		'const1e15' => new $constFloatClass(1e15),
		'const1e17' => new $constFloatClass(1e17),
		'const1e-7' => new $constFloatClass(1e-7),
		'const1e30' => new $constFloatClass(1e30),
		'const-1e30' => new $constFloatClass(-1e30),
		'constNan' => new $constFloatClass(NAN),
		'constInf' => new $constFloatClass(INF),
		'constNegInf' => new $constFloatClass(-INF),
		'constIntMax' => new $constFloatClass((float) PHP_INT_MAX),
		'constIntMin' => new $constFloatClass((float) PHP_INT_MIN),
		'constTiny' => new $constFloatClass(5e-324),
		'constFloatMax' => new $constFloatClass(PHP_FLOAT_MAX),
		'constFromInt' => new $constFloatClass(3),
		'null' => new $nullClass(),
		'void' => new $voidClass(),
	];
	$outOfClassScope = new \PHPStan\Analyser\OutOfClassScope();
	foreach ($subjects as $name => $subject) {
		$r["$name class"] = $view($subject);
		$r["$name instanceof"] = [$subject instanceof \PHPStan\Type\Type, $subject instanceof $floatClass, $subject instanceof $nullClass, $subject instanceof $voidClass, $subject instanceof \PHPStan\Type\ConstantScalarType, $subject instanceof \PHPStan\Type\CompoundType];
		foreach (['typeOnly' => \PHPStan\Type\VerbosityLevel::typeOnly(), 'value' => \PHPStan\Type\VerbosityLevel::value(), 'precise' => \PHPStan\Type\VerbosityLevel::precise(), 'cache' => \PHPStan\Type\VerbosityLevel::cache()] as $levelName => $level) {
			$r["$name describe $levelName"] = $subject->describe($level);
		}
		foreach ($others as $otherName => $other) {
			$r["$name isSuperTypeOf $otherName"] = $view($subject->isSuperTypeOf($other));
			$r["$name accepts $otherName"] = $view($subject->accepts($other, true));
			$r["$name accepts-loose $otherName"] = $view($subject->accepts($other, false));
			$r["$name equals $otherName"] = $subject->equals($other);
			$r["$name tryRemove $otherName"] = $view($subject->tryRemove($other));
			foreach ($floatPhpVersions as $vi => $phpVersion) {
				$r["$name looseCompare $otherName $vi"] = $view($subject->looseCompare($other, $phpVersion));
				$r["$name isSmallerThan $otherName $vi"] = $view($subject->isSmallerThan($other, $phpVersion));
				$r["$name isSmallerThanOrEqual $otherName $vi"] = $view($subject->isSmallerThanOrEqual($other, $phpVersion));
			}
			$r["$name traverseSimultaneously $otherName"] = $view($subject->traverseSimultaneously($other, static fn ($a, $b) => $a));
			$r["$name traverseSimultaneously-right $otherName"] = $view($subject->traverseSimultaneously($other, static fn ($a, $b) => $b));
			$r["$name getOffsetValueType $otherName"] = $view($subject->getOffsetValueType($other));
			$r["$name hasOffsetValueType $otherName"] = $view($subject->hasOffsetValueType($other));
			// a float offset goes through the PHP ConstantArrayTypeBuilder's (int) cast, whose out-of-range diagnostics are silenced like the ones above
			$r["$name setOffsetValueType $otherName"] = [$view(@$subject->setOffsetValueType($other, $others['int1'])), $view(@$subject->setOffsetValueType($other, $others['stringAbc'], false))];
			$r["$name setExistingOffsetValueType $otherName"] = $view($subject->setExistingOffsetValueType($other, $others['int1']));
			$r["$name unsetOffset $otherName"] = $view($subject->unsetOffset($other));
			$r["$name inferTemplateTypes $otherName"] = $view($subject->inferTemplateTypes($other));
			try {
				$r["$name exponentiate $otherName"] = $view($subject->exponentiate($other));
			} catch (\Throwable $e) {
				$r["$name exponentiate $otherName"] = get_class($e);
			}
		}
		// the (int) casts of a NAN, an infinity or an out-of-range float
		// raise the engine's warning on both sides; silenced so the
		// observations stay the last line of stdout
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
			$r["$name $method"] = $view(@$subject->$method());
		}
		foreach (['getSmallerType', 'getSmallerOrEqualType', 'getGreaterType', 'getGreaterOrEqualType'] as $method) {
			foreach ($floatPhpVersions as $vi => $phpVersion) {
				$r["$name $method $vi"] = $view(@$subject->$method($phpVersion));
			}
		}
		foreach ([\PHPStan\Type\GeneralizePrecision::lessSpecific(), \PHPStan\Type\GeneralizePrecision::moreSpecific(), \PHPStan\Type\GeneralizePrecision::templateArgument()] as $i => $precision) {
			$r["$name generalize $i"] = $view($subject->generalize($precision));
		}
		$r["$name toCoercedArgumentType"] = [$view($subject->toCoercedArgumentType(true)), $view(@$subject->toCoercedArgumentType(false))];
		$r["$name traverse identity"] = $subject->traverse(static fn ($t) => $t) === $subject;
		$r["$name traverse replaced"] = $view($subject->traverse(static fn ($t) => new \PHPStan\Type\ObjectType(\stdClass::class)));
		$r["$name getTemplateType"] = $view($subject->getTemplateType('Foo', 'T'));
		$r["$name hasProperty"] = $view($subject->hasProperty('x'));
		$r["$name hasMethod"] = $view($subject->hasMethod('x'));
		$r["$name hasConstant"] = $view($subject->hasConstant('X'));
		$r["$name setOffsetValueType null"] = [$view($subject->setOffsetValueType(null, $others['int1'])), $view($subject->setOffsetValueType(null, $others['stringAbc'], false)), $view($subject->setOffsetValueType(null, $others['float1.5'], unionValues: true))];
		$r["$name mapValueType"] = $view($subject->mapValueType(static fn ($t) => $t));
		$r["$name mapKeyType"] = $view($subject->mapKeyType(static fn ($t) => $t));
		$r["$name changeKeyCaseArray"] = $view($subject->changeKeyCaseArray(null));
		$r["$name toClassConstantType"] = $view($subject->toClassConstantType($stringReflectionProvider));
		$r["$name toObjectTypeForIsACheck"] = [$view($subject->toObjectTypeForIsACheck($others['mixed'], true, true)), $view($subject->toObjectTypeForIsACheck($others['object'], false, false))];
		foreach (['getProperty', 'getMethod', 'getConstant'] as $method) {
			try {
				$args = $method === 'getConstant' ? ['X'] : ['x', $outOfClassScope];
				$subject->$method(...$args);
				$r["$name $method"] = 'no throw';
			} catch (\PHPStan\ShouldNotHappenException $e) {
				$r["$name $method"] = 'ShouldNotHappenException';
			}
		}
		try {
			$subject->getCallableParametersAcceptors($outOfClassScope);
			$r["$name getCallableParametersAcceptors"] = 'no throw';
		} catch (\PHPStan\ShouldNotHappenException $e) {
			$r["$name getCallableParametersAcceptors"] = 'ShouldNotHappenException';
		}
		if ($subject instanceof \PHPStan\Type\ConstantScalarType) {
			$r["$name getValue"] = $subject->getValue();
		}
	}
	// equality over the float edge cases: -0.0 equals 0.0, NAN equals NAN
	foreach ([[0.0, -0.0], [0.0, 0.0], [1.5, 1.5], [1.5, 1.6], [NAN, NAN], [NAN, 1.0], [INF, INF], [INF, -INF], [1e30, 1e30]] as $i => [$a, $b]) {
		$r["const equals const $i"] = [(new $constFloatClass($a))->equals(new $constFloatClass($b)), $view((new $constFloatClass($a))->isSuperTypeOf(new $constFloatClass($b))), $view((new $constFloatClass($a))->accepts(new $constFloatClass($b), true))];
	}
	$r['null equals null'] = [(new $nullClass())->equals(new $nullClass()), (new $voidClass())->equals(new $voidClass()), (new $nullClass())->equals(new $voidClass())];
	// the ini precision the twin restores after describing
	$r['ini precision'] = ini_get('precision');
	// an uninitialized instance: every typed-slot read raises the same Error
	$uninitialized = (new \ReflectionClass($constFloatClass))->newInstanceWithoutConstructor();
	foreach (['getValue' => [], 'describe' => [\PHPStan\Type\VerbosityLevel::precise()], 'equals' => [$others['float1.5']], 'isSuperTypeOf' => [$others['float1.5']], 'toString' => [], 'toInteger' => [], 'toBoolean' => [], 'getFiniteTypes' => [], 'toPhpDocNode' => [], 'getSmallerType' => [$floatPhpVersions[1]]] as $method => $args) {
		try {
			$uninitialized->$method(...$args);
			$r["uninitialized $method"] = 'no throw';
		} catch (\Error $e) {
			$r["uninitialized $method"] = [get_class($e), $e->getMessage()];
		}
	}
	// the typed constructor parameter: an int coerces, a string does not
	foreach ([3, '3', null] as $value) {
		try {
			$r['construct ' . var_export($value, true)] = $view(new $constFloatClass($value));
		} catch (\TypeError $e) {
			$r['construct ' . var_export($value, true)] = 'TypeError';
		}
	}
	// PHP subclasses overriding what the natives call through $this
	$templateFloat = new \PHPStan\Type\Generic\TemplateFloatType(
		\PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('foo'),
		new \PHPStan\Type\Generic\TemplateTypeParameterStrategy(),
		\PHPStan\Type\Generic\TemplateTypeVariance::createInvariant(),
		'T',
		new $floatClass(),
		null,
	);
	$templateNull = new \PHPStan\Type\Generic\TemplateNullType(
		\PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('foo'),
		new \PHPStan\Type\Generic\TemplateTypeParameterStrategy(),
		\PHPStan\Type\Generic\TemplateTypeVariance::createInvariant(),
		'U',
		new $nullClass(),
		null,
	);
	foreach (['templateFloat' => $templateFloat, 'templateNull' => $templateNull] as $templateName => $template) {
		$r["$templateName describe"] = $template->describe(\PHPStan\Type\VerbosityLevel::precise());
		$r["$templateName equals self"] = [$template->equals($template), $template->equals($subjects['float']), $subjects['float']->equals($template), $subjects['null']->equals($template)];
		foreach (['float', 'const1.5', 'null', 'void'] as $subjectName) {
			$r["$templateName isSuperTypeOf $subjectName"] = $view($template->isSuperTypeOf($subjects[$subjectName]));
			$r["$subjectName isSuperTypeOf $templateName"] = $view($subjects[$subjectName]->isSuperTypeOf($template));
			$r["$subjectName accepts $templateName"] = $view($subjects[$subjectName]->accepts($template, true));
			$r["$subjectName tryRemove $templateName"] = $view($subjects[$subjectName]->tryRemove($template));
			foreach ($floatPhpVersions as $vi => $phpVersion) {
				$r["$subjectName looseCompare $templateName $vi"] = $view($subjects[$subjectName]->looseCompare($template, $phpVersion));
				$r["$subjectName isSmallerThan $templateName $vi"] = $view($subjects[$subjectName]->isSmallerThan($template, $phpVersion));
			}
		}
		$r["$templateName toCoercedArgumentType"] = [$view($template->toCoercedArgumentType(true)), $view($template->toCoercedArgumentType(false))];
		$r["$templateName toAbsoluteNumber"] = $view($template->toAbsoluteNumber());
		$r["$templateName toFloat"] = $view($template->toFloat());
		$r["$templateName generalize"] = $view($template->generalize(\PHPStan\Type\GeneralizePrecision::moreSpecific()));
	}
	$anonymous = new class (2.5) extends \PHPStan\Type\Constant\ConstantFloatType {

		public function getValue(): float
		{
			return 7.25;
		}

		public function toInteger(): \PHPStan\Type\Type
		{
			return new \PHPStan\Type\Constant\ConstantIntegerType(42);
		}

	};
	$r['anonymous describe'] = $anonymous->describe(\PHPStan\Type\VerbosityLevel::precise());
	$r['anonymous getConstantScalarValues'] = $view($anonymous->getConstantScalarValues());
	$r['anonymous toCoercedArgumentType'] = [$view($anonymous->toCoercedArgumentType(true)), $view($anonymous->toCoercedArgumentType(false))];
	$r['anonymous toArrayKey'] = $view($anonymous->toArrayKey());
	$r['anonymous toBoolean'] = $view($anonymous->toBoolean());
	$r['anonymous equals'] = [$anonymous->equals(new $constFloatClass(2.5)), $anonymous->equals(new $constFloatClass(7.25)), (new $constFloatClass(2.5))->equals($anonymous)];
	$r['anonymous isSuperTypeOf'] = [$view($anonymous->isSuperTypeOf(new $constFloatClass(2.5))), $view((new $constFloatClass(2.5))->isSuperTypeOf($anonymous)), $view($subjects['float']->isSuperTypeOf($anonymous))];
	foreach ($floatPhpVersions as $vi => $phpVersion) {
		$r["anonymous isSmallerThan $vi"] = [$view($anonymous->isSmallerThan($others['int1'], $phpVersion)), $view($others['int1']->isSmallerThan($anonymous, $phpVersion)), $view($subjects['null']->isSmallerThan($anonymous, $phpVersion))];
		$r["anonymous getSmallerType $vi"] = $view($anonymous->getSmallerType($phpVersion));
	}
	$anonymousNull = new class extends \PHPStan\Type\NullType {

		public function toNumber(): \PHPStan\Type\Type
		{
			return new \PHPStan\Type\Constant\ConstantIntegerType(-5);
		}

		public function getValue()
		{
			return null;
		}

	};
	$r['anonymousNull toAbsoluteNumber'] = $view($anonymousNull->toAbsoluteNumber());
	$r['anonymousNull toInteger'] = $view($anonymousNull->toInteger());
	$r['anonymousNull toFloat'] = $view($anonymousNull->toFloat());
	$r['anonymousNull getConstantScalarValues'] = $view($anonymousNull->getConstantScalarValues());
	$r['anonymousNull looseCompare emptyArray'] = $view($anonymousNull->looseCompare($others['emptyArray'], $floatPhpVersions[1]));
	$r['null isSuperTypeOf anonymousNull'] = [$view($subjects['null']->isSuperTypeOf($anonymousNull)), $subjects['null']->equals($anonymousNull), $view($subjects['void']->accepts($anonymousNull, true))];
	foreach ($r as $key => $value) {
		$observations["float $key"] = $value;
	}
}

// ---- NeverType / MixedType / StrictMixedType ----
// the PHP subclasses over the native parents come along: ErrorType (a PHP
// constructor calling parent::__construct(), overriding describe(),
// subtract(), getIterableValueType()), NonAcceptingNeverType, and the
// template types over MixedType / StrictMixedType
$mixedPhpVersions = [new \PHPStan\Php\PhpVersion(70400), new \PHPStan\Php\PhpVersion(80400)];
$mixedTemplateScope = \PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('foo');
$mixedOthers = static fn (string $never, string $mixed, string $strictMixed): array => [
	'never' => new $never(),
	'neverExplicit' => new $never(true),
	'nonAcceptingNever' => new \PHPStan\Type\NonAcceptingNeverType(),
	'mixed' => new $mixed(),
	'mixedExplicit' => new $mixed(true),
	'mixedMinusInt' => new $mixed(false, new \PHPStan\Type\IntegerType()),
	'mixedMinusNull' => new $mixed(false, new \PHPStan\Type\NullType()),
	'mixedExplicitMinusUnion' => new $mixed(true, new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()])),
	'strictMixed' => new $strictMixed(),
	'error' => new \PHPStan\Type\ErrorType(),
	'templateMixed' => \PHPStan\Type\Generic\TemplateTypeFactory::create($mixedTemplateScope, 'T', null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
	'templateStrictMixed' => new \PHPStan\Type\Generic\TemplateStrictMixedType($mixedTemplateScope, new \PHPStan\Type\Generic\TemplateTypeParameterStrategy(), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant(), 'T', new $strictMixed(), null),
	'templateInt' => \PHPStan\Type\Generic\TemplateTypeFactory::create($mixedTemplateScope, 'T', new \PHPStan\Type\IntegerType(), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
	'int' => new \PHPStan\Type\IntegerType(),
	'int0' => new \PHPStan\Type\Constant\ConstantIntegerType(0),
	'int1' => new \PHPStan\Type\Constant\ConstantIntegerType(1),
	'range0-10' => \PHPStan\Type\IntegerRangeType::fromInterval(0, 10),
	'float' => new \PHPStan\Type\FloatType(),
	'bool' => new \PHPStan\Type\BooleanType(),
	'true' => new \PHPStan\Type\Constant\ConstantBooleanType(true),
	'false' => new \PHPStan\Type\Constant\ConstantBooleanType(false),
	'null' => new \PHPStan\Type\NullType(),
	'string' => new \PHPStan\Type\StringType(),
	'stringAbc' => new \PHPStan\Type\Constant\ConstantStringType('abc'),
	'stringEmpty' => new \PHPStan\Type\Constant\ConstantStringType(''),
	'classString' => new \PHPStan\Type\ClassStringType(),
	'nonEmptyString' => new \PHPStan\Type\IntersectionType([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryNonEmptyStringType()]),
	'union' => new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]),
	'unionNullable' => new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\NullType()]),
	'array' => new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()),
	'list' => new \PHPStan\Type\IntersectionType([new \PHPStan\Type\ArrayType(\PHPStan\Type\IntegerRangeType::createAllGreaterThanOrEqualTo(0), new \PHPStan\Type\MixedType()), new \PHPStan\Type\Accessory\AccessoryArrayListType()]),
	'emptyArray' => new \PHPStan\Type\Constant\ConstantArrayType([], []),
	'object' => new \PHPStan\Type\ObjectType(\stdClass::class),
	'objectWithoutClass' => new \PHPStan\Type\ObjectWithoutClassType(),
	'arrayAccess' => new \PHPStan\Type\ObjectType(\ArrayAccess::class),
	'callable' => new \PHPStan\Type\CallableType(),
	'iterable' => new \PHPStan\Type\IterableType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()),
	'void' => new \PHPStan\Type\VoidType(),
	'scalars' => new \PHPStan\Type\UnionType([new \PHPStan\Type\BooleanType(), new \PHPStan\Type\FloatType(), new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]),
	'falsey' => \PHPStan\Type\StaticTypeFactory::falsey(),
	'truthy' => \PHPStan\Type\StaticTypeFactory::truthy(),
	'castsToZero' => new \PHPStan\Type\UnionType([new \PHPStan\Type\NullType(), new \PHPStan\Type\Constant\ConstantBooleanType(false), new \PHPStan\Type\Constant\ConstantIntegerType(0), new \PHPStan\Type\Constant\ConstantArrayType([], []), new \PHPStan\Type\StringType(), new \PHPStan\Type\FloatType()]),
	'castsToEmptyString' => new \PHPStan\Type\UnionType([new \PHPStan\Type\NullType(), new \PHPStan\Type\Constant\ConstantBooleanType(false), new \PHPStan\Type\Constant\ConstantStringType('')]),
	'castsToFalsyString' => new \PHPStan\Type\UnionType([new \PHPStan\Type\NullType(), new \PHPStan\Type\Constant\ConstantBooleanType(false), new \PHPStan\Type\Constant\ConstantStringType(''), new \PHPStan\Type\Constant\ConstantFloatType(0.0), new \PHPStan\Type\Constant\ConstantStringType('0'), new \PHPStan\Type\Constant\ConstantIntegerType(0)]),
	'offsetAccessibles' => new \PHPStan\Type\UnionType([new \PHPStan\Type\StringType(), new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()), new \PHPStan\Type\ObjectType(\ArrayAccess::class)]),
];
{
	$neverClass = \PHPStan\Type\NeverType::class;
	$mixedClass = \PHPStan\Type\MixedType::class;
	$strictMixedClass = \PHPStan\Type\StrictMixedType::class;
	$r = [];
	$others = $mixedOthers($neverClass, $mixedClass, $strictMixedClass);
	$subjects = [
		'never' => new $neverClass(),
		'neverExplicit' => new $neverClass(true),
		'neverReason' => new $neverClass(false, 'because'),
		'nonAcceptingNever' => new \PHPStan\Type\NonAcceptingNeverType(),
		'mixed' => new $mixedClass(),
		'mixedExplicit' => new $mixedClass(true),
		'mixedMinusInt' => new $mixedClass(false, new \PHPStan\Type\IntegerType()),
		'mixedMinusNull' => new $mixedClass(false, new \PHPStan\Type\NullType()),
		'mixedMinusNever' => new $mixedClass(false, new $neverClass()),
		'mixedMinusFalsey' => new $mixedClass(false, \PHPStan\Type\StaticTypeFactory::falsey()),
		'mixedMinusCastsToZero' => new $mixedClass(false, $others['castsToZero']),
		'mixedMinusCastsToEmptyString' => new $mixedClass(false, $others['castsToEmptyString']),
		'mixedMinusCastsToFalsyString' => new $mixedClass(false, $others['castsToFalsyString']),
		'mixedMinusOffsetAccessibles' => new $mixedClass(false, $others['offsetAccessibles']),
		'mixedMinusObject' => new $mixedClass(false, new \PHPStan\Type\ObjectWithoutClassType()),
		'mixedMinusArray' => new $mixedClass(false, $others['array']),
		'mixedMinusList' => new $mixedClass(false, $others['list']),
		'mixedMinusIterable' => new $mixedClass(false, $others['iterable']),
		'mixedMinusCallable' => new $mixedClass(false, $others['callable']),
		'mixedMinusScalars' => new $mixedClass(false, $others['scalars']),
		'mixedMinusString' => new $mixedClass(false, new \PHPStan\Type\StringType()),
		'mixedMinusNonEmptyString' => new $mixedClass(false, $others['nonEmptyString']),
		'mixedMinusClassString' => new $mixedClass(false, new \PHPStan\Type\ClassStringType()),
		'mixedMinusMixedMinusInt' => new $mixedClass(false, new $mixedClass(false, new \PHPStan\Type\IntegerType())),
		'mixedExplicitMinusUnion' => new $mixedClass(true, new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()])),
		'strictMixed' => new $strictMixedClass(),
		'error' => new \PHPStan\Type\ErrorType(),
		'errorReason' => new \PHPStan\Type\ErrorType('why'),
		'templateMixed' => $others['templateMixed'],
		'templateStrictMixed' => $others['templateStrictMixed'],
	];
	$outOfClassScope = new \PHPStan\Analyser\OutOfClassScope();
	foreach ($subjects as $name => $subject) {
		$r["$name class"] = $view($subject);
		$r["$name instanceof"] = [$subject instanceof \PHPStan\Type\Type, $subject instanceof $neverClass, $subject instanceof $mixedClass, $subject instanceof $strictMixedClass, $subject instanceof \PHPStan\Type\CompoundType, $subject instanceof \PHPStan\Type\SubtractableType];
		foreach (['typeOnly' => \PHPStan\Type\VerbosityLevel::typeOnly(), 'value' => \PHPStan\Type\VerbosityLevel::value(), 'precise' => \PHPStan\Type\VerbosityLevel::precise(), 'cache' => \PHPStan\Type\VerbosityLevel::cache()] as $levelName => $level) {
			$r["$name describe $levelName"] = $subject->describe($level);
		}
		foreach ($others as $otherName => $other) {
			$r["$name isSuperTypeOf $otherName"] = $view($subject->isSuperTypeOf($other));
			$r["$name accepts $otherName"] = $view($subject->accepts($other, true));
			$r["$name accepts-loose $otherName"] = $view($subject->accepts($other, false));
			$r["$name equals $otherName"] = $subject->equals($other);
			$r["$name tryRemove $otherName"] = $view($subject->tryRemove($other));
			foreach ($mixedPhpVersions as $vi => $phpVersion) {
				$r["$name looseCompare $otherName $vi"] = $view($subject->looseCompare($other, $phpVersion));
				$r["$name isSmallerThan $otherName $vi"] = $view($subject->isSmallerThan($other, $phpVersion));
				$r["$name isSmallerThanOrEqual $otherName $vi"] = $view($subject->isSmallerThanOrEqual($other, $phpVersion));
				$r["$name isGreaterThan $otherName $vi"] = $view($subject->isGreaterThan($other, $phpVersion));
				$r["$name isGreaterThanOrEqual $otherName $vi"] = $view($subject->isGreaterThanOrEqual($other, $phpVersion));
			}
			$r["$name isSubTypeOf $otherName"] = $view($subject->isSubTypeOf($other));
			$r["$name isAcceptedBy $otherName"] = $view($subject->isAcceptedBy($other, true));
			$r["$name isAcceptedBy-loose $otherName"] = $view($subject->isAcceptedBy($other, false));
			$r["$name traverseSimultaneously $otherName"] = $view($subject->traverseSimultaneously($other, static fn ($a, $b) => $b));
			$r["$name getOffsetValueType $otherName"] = $view($subject->getOffsetValueType($other));
			$r["$name hasOffsetValueType $otherName"] = $view($subject->hasOffsetValueType($other));
			$r["$name setOffsetValueType $otherName"] = $view($subject->setOffsetValueType($other, $others['int1']));
			$r["$name setExistingOffsetValueType $otherName"] = $view($subject->setExistingOffsetValueType($other, $others['int1']));
			$r["$name unsetOffset $otherName"] = $view($subject->unsetOffset($other));
			$r["$name exponentiate $otherName"] = $view($subject->exponentiate($other));
			$r["$name inferTemplateTypes $otherName"] = $view($subject->inferTemplateTypes($other));
			$r["$name fillKeysArray $otherName"] = $view($subject->fillKeysArray($other));
			$r["$name intersectKeyArray $otherName"] = $view($subject->intersectKeyArray($other));
			$r["$name truncateListToSize $otherName"] = $view($subject->truncateListToSize($other));
			$r["$name searchArray $otherName"] = [$view($subject->searchArray($other)), $view($subject->searchArray($other, \PHPStan\TrinaryLogic::createYes()))];
			$r["$name chunkArray $otherName"] = $view($subject->chunkArray($other, \PHPStan\TrinaryLogic::createNo()));
			$r["$name sliceArray $otherName"] = $view($subject->sliceArray($other, $other, \PHPStan\TrinaryLogic::createMaybe()));
			$r["$name spliceArray $otherName"] = $view($subject->spliceArray($other, $other, $other));
			$r["$name getKeysArrayFiltered $otherName"] = $view($subject->getKeysArrayFiltered($other, \PHPStan\TrinaryLogic::createYes()));
			$r["$name toObjectTypeForIsACheck $otherName"] = [$view($subject->toObjectTypeForIsACheck($other, true, true)), $view($subject->toObjectTypeForIsACheck($other, false, true)), $view($subject->toObjectTypeForIsACheck($other, true, false)), $view($subject->toObjectTypeForIsACheck($other, false, false))];
			if ($subject instanceof $mixedClass) {
				$r["$name subtract $otherName"] = $view($subject->subtract($other));
				$r["$name changeSubtractedType $otherName"] = $view($subject->changeSubtractedType($other));
				$r["$name describeSubtractedType $otherName"] = [$subject->describeSubtractedType($other, \PHPStan\Type\VerbosityLevel::precise()), $subject->describeSubtractedType($other, \PHPStan\Type\VerbosityLevel::typeOnly())];
				if ($other instanceof $mixedClass) {
					$r["$name isSuperTypeOfMixed $otherName"] = $view($subject->isSuperTypeOfMixed($other));
				}
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
			$r["$name $method"] = $view($subject->$method($mixedPhpVersions[1]));
		}
		foreach ([\PHPStan\Type\GeneralizePrecision::lessSpecific(), \PHPStan\Type\GeneralizePrecision::moreSpecific(), \PHPStan\Type\GeneralizePrecision::templateArgument()] as $i => $precision) {
			$r["$name generalize $i"] = $view($subject->generalize($precision));
		}
		$r["$name toCoercedArgumentType"] = [$view($subject->toCoercedArgumentType(true)), $view($subject->toCoercedArgumentType(false))];
		$r["$name traverse identity"] = $subject->traverse(static fn ($t) => $t) === $subject;
		$r["$name traverse replaced"] = $view($subject->traverse(static fn ($t) => new \PHPStan\Type\ObjectType(\stdClass::class)));
		$r["$name getTemplateType"] = $view($subject->getTemplateType('Foo', 'T'));
		$r["$name hasProperty"] = $view($subject->hasProperty('x'));
		$r["$name hasInstanceProperty"] = $view($subject->hasInstanceProperty('x'));
		$r["$name hasStaticProperty"] = $view($subject->hasStaticProperty('x'));
		$r["$name hasMethod"] = $view($subject->hasMethod('x'));
		$r["$name hasConstant"] = $view($subject->hasConstant('X'));
		$r["$name setOffsetValueType null"] = [$view($subject->setOffsetValueType(null, $others['int1'])), $view($subject->setOffsetValueType(null, $others['int1'], false))];
		$r["$name mapValueType"] = [$view($subject->mapValueType(static fn ($t) => $t)), $view($subject->mapValueType(static fn ($t) => new \PHPStan\Type\ObjectType(\stdClass::class)))];
		$r["$name mapKeyType"] = [$view($subject->mapKeyType(static fn ($t) => $t)), $view($subject->mapKeyType(static fn ($t) => new \PHPStan\Type\IntegerType()))];
		$r["$name changeKeyCaseArray"] = [$view($subject->changeKeyCaseArray(null)), $view($subject->changeKeyCaseArray(CASE_LOWER))];
		$r["$name reverseArray"] = $view($subject->reverseArray(\PHPStan\TrinaryLogic::createYes()));
		$r["$name toClassConstantType"] = $view($subject->toClassConstantType($stringReflectionProvider));
		foreach (['getProperty', 'getInstanceProperty', 'getStaticProperty', 'getMethod', 'getConstant'] as $method) {
			try {
				$args = $method === 'getConstant' ? ['x'] : ['x', $outOfClassScope];
				$member = $subject->$method(...$args);
				$r["$name $method"] = [get_class($member), $member->getName(), $member->getDeclaringClass()->getName()];
			} catch (\PHPStan\ShouldNotHappenException $e) {
				$r["$name $method"] = 'ShouldNotHappenException';
			}
		}
		foreach (['getUnresolvedPropertyPrototype', 'getUnresolvedInstancePropertyPrototype', 'getUnresolvedStaticPropertyPrototype', 'getUnresolvedMethodPrototype'] as $method) {
			try {
				$prototype = $subject->$method('x', $outOfClassScope);
				$transformed = $method === 'getUnresolvedMethodPrototype' ? $prototype->getTransformedMethod() : $prototype->getTransformedProperty();
				$naive = $method === 'getUnresolvedMethodPrototype' ? $prototype->getNakedMethod() : $prototype->getNakedProperty();
				$withStatic = $prototype->doNotResolveTemplateTypeMapToBounds();
				$r["$name $method"] = [get_class($prototype), get_class($transformed), $transformed->getName(), get_class($naive), get_class($withStatic)];
			} catch (\PHPStan\ShouldNotHappenException $e) {
				$r["$name $method"] = 'ShouldNotHappenException';
			}
		}
		try {
			$acceptors = $subject->getCallableParametersAcceptors($outOfClassScope);
			$r["$name getCallableParametersAcceptors"] = array_map(static fn ($acceptor) => [get_class($acceptor), $acceptor->getReturnType()->describe(\PHPStan\Type\VerbosityLevel::precise()), count($acceptor->getParameters())], $acceptors);
		} catch (\PHPStan\ShouldNotHappenException $e) {
			$r["$name getCallableParametersAcceptors"] = 'ShouldNotHappenException';
		}
		if ($subject instanceof $neverClass) {
			$r["$name isExplicit/getReason"] = [$subject->isExplicit(), $subject->getReason()];
		}
		if ($subject instanceof $mixedClass) {
			$r["$name isExplicitMixed"] = $subject->isExplicitMixed();
			$r["$name getSubtractedType"] = $view($subject->getSubtractedType());
			$r["$name getTypeWithoutSubtractedType"] = $view($subject->getTypeWithoutSubtractedType());
			$r["$name changeSubtractedType null"] = $view($subject->changeSubtractedType(null));
			$r["$name describeSubtractedType null"] = $subject->describeSubtractedType(null, \PHPStan\Type\VerbosityLevel::precise());
			$r["$name subtract own subtracted"] = $view($subject->subtract($subject->getSubtractedType() ?? new \PHPStan\Type\NullType()));
		}
		if ($subject instanceof \PHPStan\Type\ErrorType) {
			$r["$name error getReason"] = $subject->getReason();
		}
	}
	// the family through the compound types and the combinator, the way the
	// analysis exercises it
	foreach (['mixed', 'mixedExplicit', 'mixedMinusInt', 'mixedMinusNull', 'mixedExplicitMinusUnion', 'never', 'strictMixed', 'error'] as $name) {
		$subject = $subjects[$name];
		foreach (['int', 'null', 'union', 'unionNullable', 'mixed', 'never', 'array', 'object', 'string', 'callable'] as $otherName) {
			$other = $others[$otherName];
			$r["combinator union $name $otherName"] = $view(\PHPStan\Type\TypeCombinator::union($subject, $other));
			$r["combinator intersect $name $otherName"] = $view(\PHPStan\Type\TypeCombinator::intersect($subject, $other));
			$r["combinator remove $name $otherName"] = $view(\PHPStan\Type\TypeCombinator::remove($subject, $other));
			$r["combinator remove-reverse $name $otherName"] = $view(\PHPStan\Type\TypeCombinator::remove($other, $subject));
			$r["combinator removeNull $name"] = $view(\PHPStan\Type\TypeCombinator::removeNull($subject));
			$r["combinator addNull $name"] = $view(\PHPStan\Type\TypeCombinator::addNull($subject));
			$r["union isSuperTypeOf $name $otherName"] = $view($others['unionNullable']->isSuperTypeOf($subject));
			$r["union accepts $name $otherName"] = $view($others['unionNullable']->accepts($subject, true));
			$r["other isSuperTypeOf $name $otherName"] = $view($other->isSuperTypeOf($subject));
			$r["other accepts $name $otherName"] = $view($other->accepts($subject, true));
		}
	}
	$r['mixed equals mixed'] = [(new $mixedClass())->equals(new $mixedClass()), (new $mixedClass())->equals(new $mixedClass(true)), (new $mixedClass(false, new \PHPStan\Type\IntegerType()))->equals(new $mixedClass(false, new \PHPStan\Type\IntegerType())), (new $mixedClass(false, new \PHPStan\Type\IntegerType()))->equals(new $mixedClass(false, new \PHPStan\Type\StringType())), (new $mixedClass())->equals(new \PHPStan\Type\ErrorType()), (new \PHPStan\Type\ErrorType())->equals(new $mixedClass())];
	$r['never equals never'] = [(new $neverClass())->equals(new $neverClass(true)), (new $neverClass())->equals(new \PHPStan\Type\NonAcceptingNeverType()), (new \PHPStan\Type\NonAcceptingNeverType())->equals(new $neverClass())];
	// the constructor by named arguments
	$r['mixed named subtractedType'] = $view(new $mixedClass(subtractedType: new \PHPStan\Type\NullType()));
	$r['never named reason'] = (new $neverClass(reason: 'named'))->getReason();
	// an uninitialized instance: every typed-slot read raises the same Error
	foreach ([$neverClass, $mixedClass] as $uninitializedClass) {
		$uninitialized = (new \ReflectionClass($uninitializedClass))->newInstanceWithoutConstructor();
		foreach (['describe' => [\PHPStan\Type\VerbosityLevel::precise()], 'describeTypeOnly' => [\PHPStan\Type\VerbosityLevel::typeOnly()], 'isSuperTypeOf' => [$others['int']], 'equals' => [$uninitialized], 'isNull' => [], 'toBoolean' => [], 'subtract' => [$others['int']], 'getSubtractedType' => [], 'isExplicitMixed' => [], 'isExplicit' => [], 'getReason' => [], 'toPhpDocNode' => []] as $method => $args) {
			$realMethod = $method === 'describeTypeOnly' ? 'describe' : $method;
			if (!method_exists($uninitialized, $realMethod)) {
				continue;
			}
			try {
				$r["uninitialized $uninitializedClass $method"] = $view($uninitialized->$realMethod(...$args));
			} catch (\Error $e) {
				$r["uninitialized $uninitializedClass $method"] = [get_class($e), $e->getMessage()];
			}
		}
	}
	// a PHP subclass overriding what the natives call through $this
	$anonymousMixed = new class (false, new \PHPStan\Type\IntegerType()) extends \PHPStan\Type\MixedType {

		public function isArray(): \PHPStan\TrinaryLogic
		{
			return \PHPStan\TrinaryLogic::createNo();
		}

		public function isObject(): \PHPStan\TrinaryLogic
		{
			return \PHPStan\TrinaryLogic::createYes();
		}

		public function getClassStringType(): \PHPStan\Type\Type
		{
			return new \PHPStan\Type\Constant\ConstantStringType('overridden');
		}

		public function describeSubtractedType(?\PHPStan\Type\Type $subtractedType, \PHPStan\Type\VerbosityLevel $level): string
		{
			return '<overridden>';
		}

		public function isSuperTypeOf(\PHPStan\Type\Type $type): \PHPStan\Type\IsSuperTypeOfResult
		{
			return \PHPStan\Type\IsSuperTypeOfResult::createNo(['anonymous']);
		}

	};
	$r['anonymous mixed describe'] = [$anonymousMixed->describe(\PHPStan\Type\VerbosityLevel::precise()), $anonymousMixed->describe(\PHPStan\Type\VerbosityLevel::cache())];
	$r['anonymous mixed getKeysArray'] = $view($anonymousMixed->getKeysArray());
	$r['anonymous mixed toGetClassResultType'] = $view($anonymousMixed->toGetClassResultType());
	$r['anonymous mixed isConstantArray'] = $view($anonymousMixed->isConstantArray());
	$r['anonymous mixed tryRemove'] = $view($anonymousMixed->tryRemove($others['int']));
	$r['anonymous mixed isAcceptedBy'] = $view($anonymousMixed->isAcceptedBy($others['int'], true));
	$r['anonymous mixed getObjectTypeOrClassStringObjectType'] = $view($anonymousMixed->getObjectTypeOrClassStringObjectType());
	$r['anonymous mixed equals'] = [$anonymousMixed->equals(new $mixedClass(false, new \PHPStan\Type\IntegerType())), (new $mixedClass(false, new \PHPStan\Type\IntegerType()))->equals($anonymousMixed)];
	$r['mixed isSuperTypeOf anonymous'] = $view($subjects['mixedMinusInt']->isSuperTypeOf($anonymousMixed));
	$r['mixed isSubTypeOf anonymous'] = $view($subjects['mixedMinusInt']->isSubTypeOf($anonymousMixed));
	$anonymousNever = new class extends \PHPStan\Type\NeverType {

		public function isSubTypeOf(\PHPStan\Type\Type $otherType): \PHPStan\Type\IsSuperTypeOfResult
		{
			return \PHPStan\Type\IsSuperTypeOfResult::createMaybe();
		}

		public function getKeysArray(): \PHPStan\Type\Type
		{
			return new \PHPStan\Type\IntegerType();
		}

	};
	$r['anonymous never isAcceptedBy'] = $view($anonymousNever->isAcceptedBy($others['int'], true));
	$r['anonymous never getKeysArrayFiltered'] = $view($anonymousNever->getKeysArrayFiltered($others['int'], \PHPStan\TrinaryLogic::createYes()));
	$r['anonymous never isExplicit'] = $anonymousNever->isExplicit();
	foreach ($r as $key => $value) {
		$observations["mixed $key"] = $value;
	}
}


// ---- ObjectWithoutClassType / StaticType / ThisType / GenericStaticType / ObjectShapeType / NonexistentParentClassType ----
// the object family over real class reflections (the string section's
// reflection provider stays registered): a final class, a non-final one,
// an interface, generic classes (a parent/child pair for changeBaseClass()),
// an enum (allowed subtypes), a universal object crate (stdClass) and a
// class with public readonly properties (AcceptsResult) for the shapes;
// the PHP subclasses over the native parents come along
// (TemplateObjectWithoutClassType, TemplateObjectShapeType, anonymous ones)
$objectPhpVersions = [new \PHPStan\Php\PhpVersion(70400), new \PHPStan\Php\PhpVersion(80400)];
$objectTemplateScope = \PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('foo');
$objectReflection = static fn (string $className): \PHPStan\Reflection\ClassReflection => $stringReflectionProvider->getClass($className);
$objectOutOfClassScope = new \PHPStan\Analyser\OutOfClassScope();
// a scope inside a class: transformStaticType() rebases `static` onto it
// (a final class pins it, a non-final one keeps it under the recursion guard)
$objectInClassScope = static fn (\PHPStan\Reflection\ClassReflection $classReflection): \PHPStan\Reflection\ClassMemberAccessAnswerer => new class ($classReflection) implements \PHPStan\Reflection\ClassMemberAccessAnswerer {

	public function __construct(private \PHPStan\Reflection\ClassReflection $classReflection)
	{
	}

	public function isInClass(): bool
	{
		return true;
	}

	public function getClassReflection(): ?\PHPStan\Reflection\ClassReflection
	{
		return $this->classReflection;
	}

	public function canAccessProperty(\PHPStan\Reflection\PropertyReflection $propertyReflection): bool
	{
		return true;
	}

	public function canReadProperty(\PHPStan\Reflection\ExtendedPropertyReflection $propertyReflection): bool
	{
		return true;
	}

	public function canWriteProperty(\PHPStan\Reflection\ExtendedPropertyReflection $propertyReflection): bool
	{
		return true;
	}

	public function canCallMethod(\PHPStan\Reflection\MethodReflection $methodReflection): bool
	{
		return true;
	}

	public function canAccessConstant(\PHPStan\Reflection\ClassConstantReflection $constantReflection): bool
	{
		return true;
	}

};
$objectScopes = [
	'outOfClass' => $objectOutOfClassScope,
	'inTrinary' => $objectInClassScope($objectReflection(\PHPStan\TrinaryLogic::class)),
	'inException' => $objectInClassScope($objectReflection(\Exception::class)),
	'inRuntimeException' => $objectInClassScope($objectReflection(\RuntimeException::class)),
];
// a member reflection: class, name, declaring class and the type it carries
$objectMember = static function (mixed $member) use ($view): mixed {
	if ($member instanceof \PHPStan\Reflection\ExtendedPropertyReflection) {
		return [get_class($member), $member->getName(), $member->getDeclaringClass()->getName(), $view($member->getReadableType()), $member->isPublic(), $member->isStatic()];
	}
	if ($member instanceof \PHPStan\Reflection\ExtendedMethodReflection) {
		$variant = $member->getOnlyVariant();
		return [get_class($member), $member->getName(), $member->getDeclaringClass()->getName(), $view($variant->getReturnType()), count($variant->getParameters())];
	}
	if ($member instanceof \PHPStan\Reflection\ClassConstantReflection) {
		return [get_class($member), $member->getName(), $member->getDeclaringClass()->getName()];
	}
	return $view($member);
};
$objectOthers = static fn (string $object, string $static, string $thisType, string $genericStatic, string $shape, string $parent): array => [
	'object' => new $object(),
	'objectMinusStd' => new $object(new \PHPStan\Type\ObjectType(\stdClass::class)),
	'objectMinusException' => new $object(new \PHPStan\Type\ObjectType(\Exception::class)),
	'objectMinusNever' => new $object(new \PHPStan\Type\NeverType()),
	'objectMinusUnion' => new $object(new \PHPStan\Type\UnionType([new \PHPStan\Type\ObjectType(\stdClass::class), new \PHPStan\Type\ObjectType(\Exception::class)])),
	'templateObject' => new \PHPStan\Type\Generic\TemplateObjectWithoutClassType($objectTemplateScope, new \PHPStan\Type\Generic\TemplateTypeParameterStrategy(), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant(), 'T', new $object(), null),
	'stdClass' => new \PHPStan\Type\ObjectType(\stdClass::class),
	'exception' => new \PHPStan\Type\ObjectType(\Exception::class),
	'runtimeException' => new \PHPStan\Type\ObjectType(\RuntimeException::class),
	'trinary' => new \PHPStan\Type\ObjectType(\PHPStan\TrinaryLogic::class),
	'acceptsResult' => new \PHPStan\Type\ObjectType(\PHPStan\Type\AcceptsResult::class),
	'assertTag' => new \PHPStan\Type\ObjectType(\PHPStan\PhpDoc\Tag\AssertTag::class),
	'typedTag' => new \PHPStan\Type\ObjectType(\PHPStan\PhpDoc\Tag\TypedTag::class),
	'arrayObject' => new \PHPStan\Type\ObjectType(\ArrayObject::class),
	'genericArrayObject' => new \PHPStan\Type\Generic\GenericObjectType(\ArrayObject::class, [new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]),
	'cachingIterator' => new \PHPStan\Type\ObjectType(\CachingIterator::class),
	'intervalBoundary' => new \PHPStan\Type\ObjectType('Random\\IntervalBoundary'),
	'static' => new $static($objectReflection(\PHPStan\TrinaryLogic::class)),
	'staticException' => new $static($objectReflection(\Exception::class)),
	'staticMinusRuntime' => new $static($objectReflection(\Exception::class), new \PHPStan\Type\ObjectType(\RuntimeException::class)),
	'staticAssertTag' => new $static($objectReflection(\PHPStan\PhpDoc\Tag\AssertTag::class)),
	'staticTypedTag' => new $static($objectReflection(\PHPStan\PhpDoc\Tag\TypedTag::class)),
	'staticArrayObject' => new $static($objectReflection(\ArrayObject::class)),
	'staticEnum' => new $static($objectReflection('Random\\IntervalBoundary')),
	'this' => new $thisType($objectReflection(\PHPStan\TrinaryLogic::class)),
	'thisException' => new $thisType($objectReflection(\Exception::class)),
	'thisMinusRuntime' => new $thisType($objectReflection(\Exception::class), new \PHPStan\Type\ObjectType(\RuntimeException::class)),
	'thisAssertTag' => new $thisType($objectReflection(\PHPStan\PhpDoc\Tag\AssertTag::class)),
	'genericStatic' => new $genericStatic($objectReflection(\ArrayObject::class), [new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()], null, [\PHPStan\Type\Generic\TemplateTypeVariance::createInvariant(), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()]),
	'genericStaticMinus' => new $genericStatic($objectReflection(\ArrayObject::class), [new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()], new \PHPStan\Type\ObjectType(\stdClass::class), [\PHPStan\Type\Generic\TemplateTypeVariance::createCovariant(), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()]),
	'genericStaticIterator' => new $genericStatic($objectReflection(\IteratorIterator::class), [new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType(), new \PHPStan\Type\ObjectType(\Iterator::class)], null, [\PHPStan\Type\Generic\TemplateTypeVariance::createInvariant(), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant(), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()]),
	'genericStaticNonGeneric' => new $genericStatic($objectReflection(\Exception::class), [new \PHPStan\Type\IntegerType()], null, [\PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()]),
	'shape' => new $shape(['a' => new \PHPStan\Type\IntegerType(), 'b' => new \PHPStan\Type\StringType()], ['b']),
	'shapeRequired' => new $shape(['a' => new \PHPStan\Type\IntegerType(), 'b' => new \PHPStan\Type\StringType()], []),
	'shapeA' => new $shape(['a' => new \PHPStan\Type\IntegerType()], []),
	'shapeInt' => new $shape([1 => new \PHPStan\Type\IntegerType(), 'x' => new \PHPStan\Type\StringType()], [1]),
	'shapeEmpty' => new $shape([], []),
	'shapeResult' => new $shape(['result' => new \PHPStan\Type\ObjectType(\PHPStan\TrinaryLogic::class), 'reasons' => new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType())], []),
	'shapeResultOptional' => new $shape(['result' => new \PHPStan\Type\ObjectType(\PHPStan\TrinaryLogic::class), 'reasons' => new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()), 'missing' => new \PHPStan\Type\IntegerType()], ['reasons', 'missing']),
	'shapeWrong' => new $shape(['result' => new \PHPStan\Type\IntegerType()], []),
	'shapeTemplate' => new $shape(['a' => \PHPStan\Type\Generic\TemplateTypeFactory::create($objectTemplateScope, 'T', null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant())], []),
	'templateShape' => new \PHPStan\Type\Generic\TemplateObjectShapeType($objectTemplateScope, new \PHPStan\Type\Generic\TemplateTypeParameterStrategy(), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant(), 'T', new $shape(['a' => new \PHPStan\Type\IntegerType()], []), null),
	'hasPropertyA' => new \PHPStan\Type\Accessory\HasPropertyType('a'),
	'hasPropertyB' => new \PHPStan\Type\Accessory\HasPropertyType('b'),
	'objectWithA' => new \PHPStan\Type\IntersectionType([new $object(), new \PHPStan\Type\Accessory\HasPropertyType('a')]),
	'parent' => new $parent(),
	'mixed' => new \PHPStan\Type\MixedType(),
	'never' => new \PHPStan\Type\NeverType(),
	'int' => new \PHPStan\Type\IntegerType(),
	'int1' => new \PHPStan\Type\Constant\ConstantIntegerType(1),
	'string' => new \PHPStan\Type\StringType(),
	'stringAbc' => new \PHPStan\Type\Constant\ConstantStringType('abc'),
	'null' => new \PHPStan\Type\NullType(),
	'classString' => new \PHPStan\Type\ClassStringType(),
	'genericClassString' => new \PHPStan\Type\Generic\GenericClassStringType(new $static($objectReflection(\PHPStan\TrinaryLogic::class))),
	'union' => new \PHPStan\Type\UnionType([new \PHPStan\Type\ObjectType(\stdClass::class), new \PHPStan\Type\NullType()]),
	'unionObjects' => new \PHPStan\Type\UnionType([new \PHPStan\Type\ObjectType(\stdClass::class), new \PHPStan\Type\ObjectType(\Exception::class)]),
	'templateT' => \PHPStan\Type\Generic\TemplateTypeFactory::create($objectTemplateScope, 'T', new \PHPStan\Type\ObjectType(\Exception::class), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
	'callable' => new \PHPStan\Type\CallableType(),
	'closure' => new \PHPStan\Type\ObjectType(\Closure::class),
	'array' => new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()),
	'iterable' => new \PHPStan\Type\IterableType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()),
];
{
	$objectClass = \PHPStan\Type\ObjectWithoutClassType::class;
	$staticClass = \PHPStan\Type\StaticType::class;
	$thisClass = \PHPStan\Type\ThisType::class;
	$genericStaticClass = \PHPStan\Type\Generic\GenericStaticType::class;
	$shapeClass = \PHPStan\Type\ObjectShapeType::class;
	$parentClass = \PHPStan\Type\NonexistentParentClassType::class;
	$r = [];
	$others = $objectOthers($objectClass, $staticClass, $thisClass, $genericStaticClass, $shapeClass, $parentClass);
	$subjects = [];
	foreach (['object', 'objectMinusStd', 'objectMinusException', 'objectMinusNever', 'objectMinusUnion', 'templateObject', 'static', 'staticException', 'staticMinusRuntime', 'staticAssertTag', 'staticTypedTag', 'staticArrayObject', 'staticEnum', 'this', 'thisException', 'thisMinusRuntime', 'thisAssertTag', 'genericStatic', 'genericStaticMinus', 'genericStaticIterator', 'genericStaticNonGeneric', 'shape', 'shapeRequired', 'shapeA', 'shapeInt', 'shapeEmpty', 'shapeResult', 'shapeResultOptional', 'shapeWrong', 'shapeTemplate', 'templateShape', 'parent'] as $subjectName) {
		$subjects[$subjectName] = $others[$subjectName];
	}
	$subjects['staticEnumMinusCase'] = $subjects['staticEnum']->subtract(new \PHPStan\Type\Enum\EnumCaseObjectType('Random\\IntervalBoundary', 'ClosedOpen'));
	$subjects['thisEnumMinusCase'] = (new $thisClass($objectReflection('Random\\IntervalBoundary')))->subtract(new \PHPStan\Type\Enum\EnumCaseObjectType('Random\\IntervalBoundary', 'ClosedOpen'));
	$memberNames = ['x', 'a', 'b', 'result', 'reasons', 'withType', 'yes', 'and', 'message', 'YES'];
	foreach ($subjects as $name => $subject) {
		$r["$name class"] = $view($subject);
		$r["$name instanceof"] = [$subject instanceof \PHPStan\Type\Type, $subject instanceof $objectClass, $subject instanceof $staticClass, $subject instanceof $thisClass, $subject instanceof $genericStaticClass, $subject instanceof $shapeClass, $subject instanceof $parentClass, $subject instanceof \PHPStan\Type\SubtractableType, $subject instanceof \PHPStan\Type\TypeWithClassName, $subject instanceof \PHPStan\Type\CompoundType];
		foreach (['typeOnly' => \PHPStan\Type\VerbosityLevel::typeOnly(), 'value' => \PHPStan\Type\VerbosityLevel::value(), 'precise' => \PHPStan\Type\VerbosityLevel::precise(), 'cache' => \PHPStan\Type\VerbosityLevel::cache()] as $levelName => $level) {
			$r["$name describe $levelName"] = $subject->describe($level);
		}
		foreach ($others as $otherName => $other) {
			$r["$name isSuperTypeOf $otherName"] = $view($subject->isSuperTypeOf($other));
			$r["$name accepts $otherName"] = $view($subject->accepts($other, true));
			$r["$name accepts-loose $otherName"] = $view($subject->accepts($other, false));
			$r["$name equals $otherName"] = $subject->equals($other);
			$r["$name tryRemove $otherName"] = $view($subject->tryRemove($other));
			foreach ($objectPhpVersions as $vi => $phpVersion) {
				$r["$name looseCompare $otherName $vi"] = $view($subject->looseCompare($other, $phpVersion));
				$r["$name isSmallerThan $otherName $vi"] = $view($subject->isSmallerThan($other, $phpVersion));
				$r["$name isSmallerThanOrEqual $otherName $vi"] = $view($subject->isSmallerThanOrEqual($other, $phpVersion));
			}
			$r["$name traverseSimultaneously $otherName"] = $view($subject->traverseSimultaneously($other, static fn ($a, $b) => $a));
			$r["$name traverseSimultaneously-right $otherName"] = $view($subject->traverseSimultaneously($other, static fn ($a, $b) => $b));
			$r["$name getOffsetValueType $otherName"] = $view($subject->getOffsetValueType($other));
			$r["$name hasOffsetValueType $otherName"] = $view($subject->hasOffsetValueType($other));
			$r["$name setOffsetValueType $otherName"] = $view($subject->setOffsetValueType($other, $others['int1']));
			$r["$name setExistingOffsetValueType $otherName"] = $view($subject->setExistingOffsetValueType($other, $others['int1']));
			$r["$name unsetOffset $otherName"] = $view($subject->unsetOffset($other));
			$r["$name inferTemplateTypes $otherName"] = $view($subject->inferTemplateTypes($other));
			$r["$name toObjectTypeForIsACheck $otherName"] = [$view($subject->toObjectTypeForIsACheck($other, true, true)), $view($subject->toObjectTypeForIsACheck($other, false, true)), $view($subject->toObjectTypeForIsACheck($other, true, false)), $view($subject->toObjectTypeForIsACheck($other, false, false))];
			try {
				$r["$name exponentiate $otherName"] = $view($subject->exponentiate($other));
			} catch (\Throwable $e) {
				$r["$name exponentiate $otherName"] = get_class($e);
			}
			if ($subject instanceof \PHPStan\Type\SubtractableType) {
				$r["$name subtract $otherName"] = $view($subject->subtract($other));
				$r["$name changeSubtractedType $otherName"] = $view($subject->changeSubtractedType($other));
				if ($subject instanceof $objectClass) {
					$r["$name describeSubtractedType $otherName"] = [$subject->describeSubtractedType($other, \PHPStan\Type\VerbosityLevel::precise()), $subject->describeSubtractedType($other, \PHPStan\Type\VerbosityLevel::typeOnly())];
				}
			}
			if ($subject instanceof \PHPStan\Type\CompoundType) {
				$r["$name isSubTypeOf $otherName"] = $view($subject->isSubTypeOf($other));
				$r["$name isAcceptedBy $otherName"] = $view($subject->isAcceptedBy($other, true));
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
			$r["$name $method"] = $view($subject->$method($objectPhpVersions[1]));
		}
		foreach ([\PHPStan\Type\GeneralizePrecision::lessSpecific(), \PHPStan\Type\GeneralizePrecision::moreSpecific(), \PHPStan\Type\GeneralizePrecision::templateArgument()] as $i => $precision) {
			$r["$name generalize $i"] = $view($subject->generalize($precision));
		}
		$r["$name toCoercedArgumentType"] = [$view($subject->toCoercedArgumentType(true)), $view($subject->toCoercedArgumentType(false))];
		$r["$name traverse identity"] = $subject->traverse(static fn ($t) => $t) === $subject;
		$r["$name traverse replaced"] = $view($subject->traverse(static fn ($t) => new \PHPStan\Type\ObjectType(\stdClass::class)));
		$r["$name traverse int"] = $view($subject->traverse(static fn ($t) => new \PHPStan\Type\IntegerType()));
		$r["$name getTemplateType"] = $view($subject->getTemplateType('Foo', 'T'));
		$r["$name setOffsetValueType null"] = [$view($subject->setOffsetValueType(null, $others['int1'])), $view($subject->setOffsetValueType(null, $others['int1'], false))];
		$r["$name mapValueType"] = [$view($subject->mapValueType(static fn ($t) => $t)), $view($subject->mapValueType(static fn ($t) => new \PHPStan\Type\ObjectType(\stdClass::class)))];
		$r["$name mapKeyType"] = $view($subject->mapKeyType(static fn ($t) => $t));
		$r["$name changeKeyCaseArray"] = [$view($subject->changeKeyCaseArray(null)), $view($subject->changeKeyCaseArray(CASE_LOWER))];
		$r["$name reverseArray"] = $view($subject->reverseArray(\PHPStan\TrinaryLogic::createYes()));
		$r["$name searchArray"] = [$view($subject->searchArray($others['int1'])), $view($subject->searchArray($others['int1'], \PHPStan\TrinaryLogic::createYes()))];
		$r["$name chunkArray"] = $view($subject->chunkArray($others['int1'], \PHPStan\TrinaryLogic::createNo()));
		$r["$name sliceArray"] = $view($subject->sliceArray($others['int1'], $others['int1'], \PHPStan\TrinaryLogic::createMaybe()));
		$r["$name spliceArray"] = $view($subject->spliceArray($others['int1'], $others['int1'], $others['array']));
		$r["$name getKeysArrayFiltered"] = $view($subject->getKeysArrayFiltered($others['int'], \PHPStan\TrinaryLogic::createYes()));
		$r["$name fillKeysArray"] = $view($subject->fillKeysArray($others['int']));
		$r["$name intersectKeyArray"] = $view($subject->intersectKeyArray($others['array']));
		$r["$name truncateListToSize"] = $view($subject->truncateListToSize($others['int1']));
		$r["$name toClassConstantType"] = $view($subject->toClassConstantType($stringReflectionProvider));
		foreach ($memberNames as $memberName) {
			$r["$name hasProperty $memberName"] = $view($subject->hasProperty($memberName));
			$r["$name hasInstanceProperty $memberName"] = $view($subject->hasInstanceProperty($memberName));
			$r["$name hasStaticProperty $memberName"] = $view($subject->hasStaticProperty($memberName));
			$r["$name hasMethod $memberName"] = $view($subject->hasMethod($memberName));
			$r["$name hasConstant $memberName"] = $view($subject->hasConstant($memberName));
			foreach ($objectScopes as $scopeName => $scope) {
				foreach (['getProperty', 'getInstanceProperty', 'getStaticProperty', 'getMethod', 'getConstant'] as $method) {
					try {
						$args = $method === 'getConstant' ? [$memberName] : [$memberName, $scope];
						$r["$name $method $memberName $scopeName"] = $objectMember($subject->$method(...$args));
					} catch (\PHPStan\ShouldNotHappenException $e) {
						$r["$name $method $memberName $scopeName"] = 'ShouldNotHappenException';
					} catch (\Throwable $e) {
						$r["$name $method $memberName $scopeName"] = [get_class($e), $e->getMessage()];
					}
					if ($method === 'getConstant') {
						break;
					}
				}
				foreach (['getUnresolvedPropertyPrototype', 'getUnresolvedInstancePropertyPrototype', 'getUnresolvedStaticPropertyPrototype', 'getUnresolvedMethodPrototype'] as $method) {
					try {
						$prototype = $subject->$method($memberName, $scope);
						$transformed = $method === 'getUnresolvedMethodPrototype' ? $prototype->getTransformedMethod() : $prototype->getTransformedProperty();
						$naked = $method === 'getUnresolvedMethodPrototype' ? $prototype->getNakedMethod() : $prototype->getNakedProperty();
						$withStatic = $prototype->doNotResolveTemplateTypeMapToBounds();
						$r["$name $method $memberName $scopeName"] = [get_class($prototype), $objectMember($transformed), $objectMember($naked), get_class($withStatic)];
					} catch (\PHPStan\ShouldNotHappenException $e) {
						$r["$name $method $memberName $scopeName"] = 'ShouldNotHappenException';
					} catch (\Throwable $e) {
						$r["$name $method $memberName $scopeName"] = [get_class($e), $e->getMessage()];
					}
				}
			}
		}
		// the method cache: the same reflection object comes back
		try {
			$r["$name getMethod cached"] = [$subject->getMethod('yes', $objectOutOfClassScope) === $subject->getMethod('yes', $objectOutOfClassScope), $subject->getMethod('yes', $objectScopes['inTrinary']) === $subject->getMethod('yes', $objectScopes['inTrinary'])];
		} catch (\Throwable $e) {
			$r["$name getMethod cached"] = get_class($e);
		}
		try {
			$acceptors = $subject->getCallableParametersAcceptors($objectOutOfClassScope);
			$r["$name getCallableParametersAcceptors"] = array_map(static fn ($acceptor) => [get_class($acceptor), $acceptor->getReturnType()->describe(\PHPStan\Type\VerbosityLevel::precise()), count($acceptor->getParameters())], $acceptors);
		} catch (\PHPStan\ShouldNotHappenException $e) {
			$r["$name getCallableParametersAcceptors"] = 'ShouldNotHappenException';
		}
		if ($subject instanceof \PHPStan\Type\SubtractableType) {
			$r["$name getSubtractedType"] = $view($subject->getSubtractedType());
			$r["$name getTypeWithoutSubtractedType"] = $view($subject->getTypeWithoutSubtractedType());
			$r["$name changeSubtractedType null"] = $view($subject->changeSubtractedType(null));
		}
		if ($subject instanceof $objectClass) {
			$r["$name describeSubtractedType null"] = $subject->describeSubtractedType(null, \PHPStan\Type\VerbosityLevel::precise());
		}
		if ($subject instanceof $staticClass) {
			$r["$name getClassName"] = $subject->getClassName();
			$r["$name getClassReflection"] = $subject->getClassReflection()->getName();
			$r["$name getStaticObjectType"] = [$view($subject->getStaticObjectType()), $subject->getStaticObjectType() === $subject->getStaticObjectType()];
			foreach ([\PHPStan\TrinaryLogic::class, \Exception::class, \Throwable::class, \RuntimeException::class, \ArrayObject::class, \IteratorAggregate::class, \Countable::class, \IteratorIterator::class, \CachingIterator::class, \Iterator::class, \stdClass::class, 'Random\\IntervalBoundary', \UnitEnum::class] as $className) {
				$r["$name getAncestorWithClassName $className"] = $view($subject->getAncestorWithClassName($className));
				$r["$name changeBaseClass $className"] = $view($subject->changeBaseClass($objectReflection($className)));
			}
		}
		if ($subject instanceof $genericStaticClass) {
			$r["$name getTypes/getVariances"] = [$view($subject->getTypes()), array_map(static fn (\PHPStan\Type\Generic\TemplateTypeVariance $v): string => $v->describe(), $subject->getVariances())];
		}
		if ($subject instanceof $shapeClass) {
			$r["$name getProperties/getOptionalProperties"] = [$view($subject->getProperties()), $subject->getOptionalProperties()];
			foreach (['a', 'b', 'x', 'reasons', '1'] as $propertyName) {
				$r["$name makePropertyRequired $propertyName"] = [$view($subject->makePropertyRequired($propertyName)), $subject->makePropertyRequired($propertyName) === $subject];
			}
		}
	}
	// the family through the compound types and the combinator, the way the
	// analysis exercises it
	foreach (['object', 'objectMinusStd', 'static', 'staticMinusRuntime', 'this', 'genericStatic', 'shape', 'shapeInt', 'parent', 'staticEnum'] as $name) {
		$subject = $subjects[$name];
		foreach (['stdClass', 'exception', 'trinary', 'null', 'union', 'unionObjects', 'mixed', 'never', 'object', 'static', 'this', 'shape', 'hasPropertyA', 'objectWithA', 'templateT', 'intervalBoundary'] as $otherName) {
			$other = $others[$otherName];
			$r["combinator union $name $otherName"] = $view(\PHPStan\Type\TypeCombinator::union($subject, $other));
			$r["combinator intersect $name $otherName"] = $view(\PHPStan\Type\TypeCombinator::intersect($subject, $other));
			$r["combinator remove $name $otherName"] = $view(\PHPStan\Type\TypeCombinator::remove($subject, $other));
			$r["combinator remove-reverse $name $otherName"] = $view(\PHPStan\Type\TypeCombinator::remove($other, $subject));
			$r["union isSuperTypeOf $name $otherName"] = $view($others['union']->isSuperTypeOf($subject));
			$r["union accepts $name $otherName"] = $view($others['union']->accepts($subject, true));
			$r["other isSuperTypeOf $name $otherName"] = $view($other->isSuperTypeOf($subject));
			$r["other accepts $name $otherName"] = $view($other->accepts($subject, true));
		}
		$r["combinator removeNull $name"] = $view(\PHPStan\Type\TypeCombinator::removeNull($subject));
		$r["combinator addNull $name"] = $view(\PHPStan\Type\TypeCombinator::addNull($subject));
	}
	// the constructors' validation and named arguments
	try {
		new $genericStaticClass($objectReflection(\ArrayObject::class), [], null, []);
		$r['genericStatic zero types'] = 'no throw';
	} catch (\PHPStan\ShouldNotHappenException $e) {
		$r['genericStatic zero types'] = [get_class($e), $e->getMessage()];
	}
	$r['object named subtractedType'] = $view(new $objectClass(subtractedType: new \PHPStan\Type\ObjectType(\stdClass::class)));
	$r['static named'] = $view(new $staticClass(subtractedType: new \PHPStan\Type\ObjectType(\RuntimeException::class), classReflection: $objectReflection(\Exception::class)));
	$r['this named'] = $view(new $thisClass(subtractedType: null, classReflection: $objectReflection(\Exception::class)));
	$r['shape named'] = $view(new $shapeClass(optionalProperties: ['a'], properties: ['a' => new \PHPStan\Type\IntegerType()]));
	// an uninitialized instance: every typed-slot read raises the same Error
	foreach ([$objectClass, $staticClass, $thisClass, $genericStaticClass, $shapeClass] as $uninitializedClass) {
		$uninitialized = (new \ReflectionClass($uninitializedClass))->newInstanceWithoutConstructor();
		foreach (['describe' => [\PHPStan\Type\VerbosityLevel::precise()], 'isSuperTypeOf' => [$others['int']], 'equals' => [$uninitialized], 'getSubtractedType' => [], 'subtract' => [$others['int']], 'getClassName' => [], 'getClassReflection' => [], 'getStaticObjectType' => [], 'getProperties' => [], 'getOptionalProperties' => [], 'hasInstanceProperty' => ['a'], 'getTypes' => [], 'getVariances' => [], 'toPhpDocNode' => [], 'getMethod' => ['x', $objectOutOfClassScope], 'hasTemplateOrLateResolvableType' => [], 'traverse' => [static fn ($t) => $t]] as $method => $args) {
			if (!method_exists($uninitialized, $method)) {
				continue;
			}
			try {
				$r["uninitialized $uninitializedClass $method"] = $view($uninitialized->$method(...$args));
			} catch (\Error $e) {
				$r["uninitialized $uninitializedClass $method"] = [get_class($e), $e->getMessage()];
			}
		}
	}
	// PHP subclasses overriding what the natives call through $this
	$anonymousStatic = new class ($objectReflection(\Exception::class), new \PHPStan\Type\ObjectType(\RuntimeException::class)) extends \PHPStan\Type\StaticType {

		public function getStaticObjectType(): \PHPStan\Type\ObjectType
		{
			return new \PHPStan\Type\ObjectType(\LogicException::class);
		}

		public function getSubtractedType(): ?\PHPStan\Type\Type
		{
			return new \PHPStan\Type\ObjectType(\DomainException::class);
		}

		public function getClassStringType(): \PHPStan\Type\Type
		{
			return new \PHPStan\Type\Constant\ConstantStringType('overridden');
		}

	};
	$r['anonymous static describe'] = $anonymousStatic->describe(\PHPStan\Type\VerbosityLevel::precise());
	$r['anonymous static isSuperTypeOf'] = [$view($anonymousStatic->isSuperTypeOf($others['exception'])), $view($anonymousStatic->isSuperTypeOf($others['static'])), $view($subjects['staticException']->isSuperTypeOf($anonymousStatic)), $view($subjects['thisException']->isSuperTypeOf($anonymousStatic))];
	$r['anonymous static equals'] = [$anonymousStatic->equals($subjects['staticException']), $subjects['staticException']->equals($anonymousStatic)];
	$r['anonymous static accepts'] = [$view($anonymousStatic->accepts($subjects['staticException'], true)), $view($subjects['staticException']->accepts($anonymousStatic, true))];
	$r['anonymous static toGetClassResultType'] = $view($anonymousStatic->toGetClassResultType());
	$r['anonymous static toClassConstantType'] = $view($anonymousStatic->toClassConstantType($stringReflectionProvider));
	$r['anonymous static getAncestorWithClassName'] = [$view($anonymousStatic->getAncestorWithClassName(\Exception::class)), $view($anonymousStatic->getAncestorWithClassName(\LogicException::class))];
	$r['anonymous static tryRemove'] = $view($anonymousStatic->tryRemove($others['exception']));
	$r['anonymous static subtract'] = $view($anonymousStatic->subtract($others['stdClass']));
	$r['anonymous static traverse'] = [$anonymousStatic->traverse(static fn ($t) => $t) === $anonymousStatic, $view($anonymousStatic->traverse(static fn ($t) => new \PHPStan\Type\IntegerType()))];
	$r['anonymous static getMethod'] = $objectMember($anonymousStatic->getMethod('getMessage', $objectOutOfClassScope));
	$r['anonymous static hasProperty'] = $view($anonymousStatic->hasProperty('message'));
	$anonymousThis = new class ($objectReflection(\PHPStan\PhpDoc\Tag\AssertTag::class)) extends \PHPStan\Type\ThisType {

		public function changeBaseClass(\PHPStan\Reflection\ClassReflection $classReflection): \PHPStan\Type\StaticType
		{
			return new \PHPStan\Type\StaticType($classReflection);
		}

	};
	$r['anonymous this describe'] = $anonymousThis->describe(\PHPStan\Type\VerbosityLevel::precise());
	$r['anonymous this getMethod withType'] = [$objectMember($anonymousThis->getMethod('withType', $objectOutOfClassScope)), $objectMember($anonymousThis->getMethod('withType', $objectScopes['inException']))];
	$r['anonymous this getAncestorWithClassName'] = $view($anonymousThis->getAncestorWithClassName(\PHPStan\PhpDoc\Tag\TypedTag::class));
	$r['anonymous this changeSubtractedType'] = $view($anonymousThis->changeSubtractedType($others['stdClass']));
	$r['anonymous this isSuperTypeOf'] = [$view($anonymousThis->isSuperTypeOf($others['assertTag'])), $view($subjects['thisAssertTag']->isSuperTypeOf($anonymousThis)), $view($subjects['staticAssertTag']->isSuperTypeOf($anonymousThis))];
	$anonymousObject = new class (new \PHPStan\Type\ObjectType(\stdClass::class)) extends \PHPStan\Type\ObjectWithoutClassType {

		public function describeSubtractedType(?\PHPStan\Type\Type $subtractedType, \PHPStan\Type\VerbosityLevel $level): string
		{
			return '<overridden>';
		}

		public function isSuperTypeOf(\PHPStan\Type\Type $type): \PHPStan\Type\IsSuperTypeOfResult
		{
			return \PHPStan\Type\IsSuperTypeOfResult::createNo(['anonymous']);
		}

		public function subtract(\PHPStan\Type\Type $type): \PHPStan\Type\Type
		{
			return new \PHPStan\Type\IntegerType();
		}

	};
	$r['anonymous object describe'] = [$anonymousObject->describe(\PHPStan\Type\VerbosityLevel::precise()), $anonymousObject->describe(\PHPStan\Type\VerbosityLevel::typeOnly())];
	$r['anonymous object tryRemove'] = [$view($anonymousObject->tryRemove($others['int'])), $view($anonymousObject->tryRemove($others['exception']))];
	$r['anonymous object exponentiate'] = $view($anonymousObject->exponentiate($others['exception']));
	$r['anonymous object equals'] = [$anonymousObject->equals($subjects['objectMinusStd']), $subjects['objectMinusStd']->equals($anonymousObject), $subjects['object']->equals($anonymousObject)];
	$r['object isSuperTypeOf anonymous'] = [$view($subjects['object']->isSuperTypeOf($anonymousObject)), $view($subjects['objectMinusStd']->isSuperTypeOf($anonymousObject)), $view($subjects['shape']->isSuperTypeOf($anonymousObject))];
	$anonymousShape = new class (['a' => new \PHPStan\Type\IntegerType()], []) extends \PHPStan\Type\ObjectShapeType {

		public function hasInstanceProperty(string $propertyName): \PHPStan\TrinaryLogic
		{
			return $propertyName === 'a' ? \PHPStan\TrinaryLogic::createMaybe() : \PHPStan\TrinaryLogic::createNo();
		}

		public function isSuperTypeOf(\PHPStan\Type\Type $type): \PHPStan\Type\IsSuperTypeOfResult
		{
			return \PHPStan\Type\IsSuperTypeOfResult::createMaybe();
		}

	};
	$r['anonymous shape hasProperty'] = $view($anonymousShape->hasProperty('a'));
	$r['anonymous shape getProperty'] = $objectMember($anonymousShape->getProperty('a', $objectOutOfClassScope));
	$r['anonymous shape exponentiate'] = $view($anonymousShape->exponentiate($others['int']));
	$r['anonymous shape equals'] = [$anonymousShape->equals($subjects['shapeA']), $subjects['shapeA']->equals($anonymousShape)];
	$r['shape isSuperTypeOf anonymous'] = [$view($subjects['shapeA']->isSuperTypeOf($anonymousShape)), $view($subjects['shape']->accepts($anonymousShape, true)), $view($subjects['object']->isSuperTypeOf($anonymousShape))];
	foreach ($r as $key => $value) {
		$observations["object $key"] = $value;
	}
}

// ---- ArrayType / NonEmptyArrayType / AccessoryArrayListType / OversizedArrayType / HasOffsetType / HasOffsetValueType ----
// the string family's reflection provider and PhpVersion accessors stay
// registered from the section above (ObjectType members consult them); the
// PHP compound types (UnionType, IntersectionType, ConstantArrayType,
// TemplateArrayType over the native ArrayType) come along
$arrayPhpVersions = [new \PHPStan\Php\PhpVersion(70400), new \PHPStan\Php\PhpVersion(80400)];
$arrayTemplateScope = \PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('foo');
$arrayTemplateT = \PHPStan\Type\Generic\TemplateTypeFactory::create($arrayTemplateScope, 'T', null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
$arrayTemplateK = \PHPStan\Type\Generic\TemplateTypeFactory::create($arrayTemplateScope, 'K', new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
$arrayTemplateV = \PHPStan\Type\Generic\TemplateTypeFactory::create($arrayTemplateScope, 'V', new \PHPStan\Type\IntegerType(), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
$arrayConstShape = static function (array $entries, array $optionalKeys = []): \PHPStan\Type\Constant\ConstantArrayType {
	$keyTypes = [];
	$valueTypes = [];
	foreach ($entries as $key => $valueType) {
		$keyTypes[] = is_int($key) ? new \PHPStan\Type\Constant\ConstantIntegerType($key) : new \PHPStan\Type\Constant\ConstantStringType($key);
		$valueTypes[] = $valueType;
	}
	return new \PHPStan\Type\Constant\ConstantArrayType($keyTypes, $valueTypes, [count($keyTypes)], $optionalKeys);
};
$arrayUnsealedShape = static function (array $entries, \PHPStan\Type\Type $keyType, \PHPStan\Type\Type $valueType) use ($arrayConstShape): \PHPStan\Type\Type {
	$builder = \PHPStan\Type\Constant\ConstantArrayTypeBuilder::createFromConstantArray($arrayConstShape($entries));
	$builder->makeUnsealed($keyType, $valueType);
	return $builder->getArray();
};
$arrayOthers = static fn (string $array, string $nonEmpty, string $list, string $oversized, string $hasOffset, string $hasOffsetValue): array => [
	'int' => new \PHPStan\Type\IntegerType(),
	'int0' => new \PHPStan\Type\Constant\ConstantIntegerType(0),
	'int1' => new \PHPStan\Type\Constant\ConstantIntegerType(1),
	'int5' => new \PHPStan\Type\Constant\ConstantIntegerType(5),
	'int-1' => new \PHPStan\Type\Constant\ConstantIntegerType(-1),
	'intMax' => new \PHPStan\Type\Constant\ConstantIntegerType(PHP_INT_MAX),
	'range0-max' => \PHPStan\Type\IntegerRangeType::fromInterval(0, null),
	'range1-max' => \PHPStan\Type\IntegerRangeType::fromInterval(1, null),
	'range0-3' => \PHPStan\Type\IntegerRangeType::fromInterval(0, 3),
	'range2-4' => \PHPStan\Type\IntegerRangeType::fromInterval(2, 4),
	'range3-max' => \PHPStan\Type\IntegerRangeType::fromInterval(3, null),
	'range0-300' => \PHPStan\Type\IntegerRangeType::fromInterval(0, 300),
	'range300-max' => \PHPStan\Type\IntegerRangeType::fromInterval(300, null),
	'string' => new \PHPStan\Type\StringType(),
	'stringA' => new \PHPStan\Type\Constant\ConstantStringType('a'),
	'stringAbc' => new \PHPStan\Type\Constant\ConstantStringType('abc'),
	'stringUpper' => new \PHPStan\Type\Constant\ConstantStringType('ABC'),
	'stringEmpty' => new \PHPStan\Type\Constant\ConstantStringType(''),
	'string0' => new \PHPStan\Type\Constant\ConstantStringType('0'),
	'string123' => new \PHPStan\Type\Constant\ConstantStringType('123'),
	'nonEmptyString' => new \PHPStan\Type\IntersectionType([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryNonEmptyStringType()]),
	'numericString' => new \PHPStan\Type\IntersectionType([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryNumericStringType()]),
	'classString' => new \PHPStan\Type\ClassStringType(),
	'float' => new \PHPStan\Type\FloatType(),
	'float1.5' => new \PHPStan\Type\Constant\ConstantFloatType(1.5),
	'bool' => new \PHPStan\Type\BooleanType(),
	'true' => new \PHPStan\Type\Constant\ConstantBooleanType(true),
	'false' => new \PHPStan\Type\Constant\ConstantBooleanType(false),
	'null' => new \PHPStan\Type\NullType(),
	'mixed' => new \PHPStan\Type\MixedType(),
	'mixedExplicit' => new \PHPStan\Type\MixedType(true),
	'mixedMinusArray' => new \PHPStan\Type\MixedType(false, new $array(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType())),
	'strictMixed' => new \PHPStan\Type\StrictMixedType(),
	'never' => new \PHPStan\Type\NeverType(),
	'error' => new \PHPStan\Type\ErrorType(),
	'union' => new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]),
	'unionNullable' => new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\NullType()]),
	'unionConsts' => new \PHPStan\Type\UnionType([new \PHPStan\Type\Constant\ConstantIntegerType(1), new \PHPStan\Type\Constant\ConstantIntegerType(2)]),
	'unionArrays' => new \PHPStan\Type\UnionType([new $array(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()), new \PHPStan\Type\NullType()]),
	'benevolent' => new \PHPStan\Type\BenevolentUnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]),
	'array' => new $array(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()),
	'arrayIntString' => new $array(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()),
	'arrayStringInt' => new $array(new \PHPStan\Type\StringType(), new \PHPStan\Type\IntegerType()),
	'arrayIntInt' => new $array(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\IntegerType()),
	'arrayNested' => new $array(new \PHPStan\Type\IntegerType(), new $array(new \PHPStan\Type\StringType(), new \PHPStan\Type\IntegerType())),
	'arrayTemplates' => new $array($arrayTemplateK, $arrayTemplateV),
	'arrayTemplateValue' => new $array(new \PHPStan\Type\IntegerType(), $arrayTemplateT),
	'list' => new \PHPStan\Type\IntersectionType([new $array(\PHPStan\Type\IntegerRangeType::createAllGreaterThanOrEqualTo(0), new \PHPStan\Type\StringType()), new $list()]),
	'nonEmptyArray' => new \PHPStan\Type\IntersectionType([new $array(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()), new $nonEmpty()]),
	'nonEmptyList' => new \PHPStan\Type\IntersectionType([new $array(\PHPStan\Type\IntegerRangeType::createAllGreaterThanOrEqualTo(0), new \PHPStan\Type\IntegerType()), new $nonEmpty(), new $list()]),
	'oversizedArray' => new \PHPStan\Type\IntersectionType([new $array(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()), new $oversized()]),
	'arrayWithOffsetA' => new \PHPStan\Type\IntersectionType([new $array(new \PHPStan\Type\StringType(), new \PHPStan\Type\IntegerType()), new $hasOffsetValue(new \PHPStan\Type\Constant\ConstantStringType('a'), new \PHPStan\Type\IntegerType()), new $nonEmpty()]),
	'arrayWithOffset0' => new \PHPStan\Type\IntersectionType([new $array(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()), new $hasOffset(new \PHPStan\Type\Constant\ConstantIntegerType(0)), new $nonEmpty()]),
	'emptyArray' => new \PHPStan\Type\Constant\ConstantArrayType([], []),
	'constArray' => $arrayConstShape(['a' => new \PHPStan\Type\IntegerType()]),
	'constArrayInts' => $arrayConstShape([0 => new \PHPStan\Type\Constant\ConstantStringType('x'), 1 => new \PHPStan\Type\Constant\ConstantStringType('y')]),
	'constArrayOptional' => $arrayConstShape(['a' => new \PHPStan\Type\IntegerType(), 'b' => new \PHPStan\Type\StringType()], [0]),
	'constArrayNested' => $arrayConstShape(['a' => $arrayConstShape(['b' => new \PHPStan\Type\IntegerType()])]),
	'constArrayUnsealed' => $arrayUnsealedShape(['a' => new \PHPStan\Type\IntegerType()], new \PHPStan\Type\StringType(), new \PHPStan\Type\IntegerType()),
	'object' => new \PHPStan\Type\ObjectType(\stdClass::class),
	'objectTrinary' => new \PHPStan\Type\ObjectType(\PHPStan\TrinaryLogic::class),
	'objectWithoutClass' => new \PHPStan\Type\ObjectWithoutClassType(),
	'arrayAccess' => new \PHPStan\Type\ObjectType(\ArrayAccess::class),
	'callable' => new \PHPStan\Type\CallableType(),
	'iterable' => new \PHPStan\Type\IterableType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()),
	'templateT' => $arrayTemplateT,
	'templateV' => $arrayTemplateV,
	'templateArray' => \PHPStan\Type\Generic\TemplateTypeFactory::create($arrayTemplateScope, 'A', new $array(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
	'nonEmpty' => new $nonEmpty(),
	'listAccessory' => new $list(),
	'oversized' => new $oversized(),
	'hasOffsetA' => new $hasOffset(new \PHPStan\Type\Constant\ConstantStringType('a')),
	'hasOffset0' => new $hasOffset(new \PHPStan\Type\Constant\ConstantIntegerType(0)),
	'hasOffsetValueA' => new $hasOffsetValue(new \PHPStan\Type\Constant\ConstantStringType('a'), new \PHPStan\Type\IntegerType()),
	'hasOffsetValueAString' => new $hasOffsetValue(new \PHPStan\Type\Constant\ConstantStringType('a'), new \PHPStan\Type\StringType()),
	'hasOffsetValue0' => new $hasOffsetValue(new \PHPStan\Type\Constant\ConstantIntegerType(0), new \PHPStan\Type\Constant\ConstantStringType('x')),
	'hasOffsetValue1' => new $hasOffsetValue(new \PHPStan\Type\Constant\ConstantIntegerType(1), new \PHPStan\Type\Constant\ConstantStringType('y')),
	'falsey' => \PHPStan\Type\StaticTypeFactory::falsey(),
];
{
	$arrayClass = \PHPStan\Type\ArrayType::class;
	$nonEmptyClass = \PHPStan\Type\Accessory\NonEmptyArrayType::class;
	$listClass = \PHPStan\Type\Accessory\AccessoryArrayListType::class;
	$oversizedClass = \PHPStan\Type\Accessory\OversizedArrayType::class;
	$hasOffsetClass = \PHPStan\Type\Accessory\HasOffsetType::class;
	$hasOffsetValueClass = \PHPStan\Type\Accessory\HasOffsetValueType::class;
	$r = [];
	$others = $arrayOthers($arrayClass, $nonEmptyClass, $listClass, $oversizedClass, $hasOffsetClass, $hasOffsetValueClass);
	$subjects = [
		'arrayMixed' => new $arrayClass(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()),
		'arrayExplicitMixed' => new $arrayClass(new \PHPStan\Type\MixedType(true), new \PHPStan\Type\MixedType(true)),
		'arrayIntString' => new $arrayClass(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()),
		'arrayStringObject' => new $arrayClass(new \PHPStan\Type\StringType(), new \PHPStan\Type\ObjectType(\PHPStan\TrinaryLogic::class)),
		'arrayStringInt' => new $arrayClass(new \PHPStan\Type\StringType(), new \PHPStan\Type\IntegerType()),
		'arrayNested' => new $arrayClass(new \PHPStan\Type\IntegerType(), new $arrayClass(new \PHPStan\Type\StringType(), new \PHPStan\Type\IntegerType())),
		'arrayListKeys' => new $arrayClass(\PHPStan\Type\IntegerRangeType::createAllGreaterThanOrEqualTo(0), new \PHPStan\Type\MixedType()),
		'arrayRangeKeys' => new $arrayClass(\PHPStan\Type\IntegerRangeType::fromInterval(0, 3), new \PHPStan\Type\StringType()),
		'arrayConstKeys' => new $arrayClass(new \PHPStan\Type\UnionType([new \PHPStan\Type\Constant\ConstantIntegerType(1), new \PHPStan\Type\Constant\ConstantIntegerType(2)]), new \PHPStan\Type\StringType()),
		'arrayMaxKey' => new $arrayClass(new \PHPStan\Type\Constant\ConstantIntegerType(PHP_INT_MAX), new \PHPStan\Type\StringType()),
		'arrayUnionKeys' => new $arrayClass(new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\Constant\ConstantStringType('a')]), new \PHPStan\Type\IntegerType()),
		'arrayStringKeys' => new $arrayClass(new \PHPStan\Type\UnionType([new \PHPStan\Type\Constant\ConstantStringType('Ab'), new \PHPStan\Type\Constant\ConstantStringType('cD')]), new \PHPStan\Type\IntegerType()),
		'arrayAccessoryStringKeys' => new $arrayClass(new \PHPStan\Type\IntersectionType([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryNonEmptyStringType(), new \PHPStan\Type\Accessory\AccessoryNumericStringType()]), new \PHPStan\Type\IntegerType()),
		'arrayNonFalsyStringKeys' => new $arrayClass(new \PHPStan\Type\IntersectionType([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryNonFalsyStringType()]), new \PHPStan\Type\IntegerType()),
		'arrayBenevolent' => new $arrayClass(new \PHPStan\Type\BenevolentUnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]), new \PHPStan\Type\MixedType()),
		'arrayStrictMixed' => new $arrayClass(new \PHPStan\Type\StrictMixedType(), new \PHPStan\Type\StrictMixedType()),
		'arrayNever' => new $arrayClass(new \PHPStan\Type\NeverType(), new \PHPStan\Type\NeverType()),
		'arrayNeverKey' => new $arrayClass(new \PHPStan\Type\NeverType(), new \PHPStan\Type\IntegerType()),
		'arrayErrorItem' => new $arrayClass(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\ErrorType()),
		'arrayConstArrayItem' => new $arrayClass(new \PHPStan\Type\IntegerType(), $arrayConstShape(['a' => new \PHPStan\Type\IntegerType()])),
		'arrayTemplates' => new $arrayClass($arrayTemplateK, $arrayTemplateV),
		'arrayTemplateMixedKey' => new $arrayClass($arrayTemplateT, new \PHPStan\Type\StringType()),
		'arrayCallableItems' => new $arrayClass(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\UnionType([new \PHPStan\Type\StringType(), new \PHPStan\Type\ObjectWithoutClassType()])),
		'templateArray' => new \PHPStan\Type\Generic\TemplateArrayType($arrayTemplateScope, new \PHPStan\Type\Generic\TemplateTypeParameterStrategy(), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant(), 'T', new $arrayClass(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()), null),
		'nonEmpty' => new $nonEmptyClass(),
		'list' => new $listClass(),
		'oversized' => new $oversizedClass(),
		'hasOffsetA' => new $hasOffsetClass(new \PHPStan\Type\Constant\ConstantStringType('a')),
		'hasOffsetAbc' => new $hasOffsetClass(new \PHPStan\Type\Constant\ConstantStringType('Abc')),
		'hasOffset0' => new $hasOffsetClass(new \PHPStan\Type\Constant\ConstantIntegerType(0)),
		'hasOffset1' => new $hasOffsetClass(new \PHPStan\Type\Constant\ConstantIntegerType(1)),
		'hasOffsetValueA' => new $hasOffsetValueClass(new \PHPStan\Type\Constant\ConstantStringType('a'), new \PHPStan\Type\IntegerType()),
		'hasOffsetValueAbcString' => new $hasOffsetValueClass(new \PHPStan\Type\Constant\ConstantStringType('Abc'), new \PHPStan\Type\Constant\ConstantStringType('x')),
		'hasOffsetValue0' => new $hasOffsetValueClass(new \PHPStan\Type\Constant\ConstantIntegerType(0), new \PHPStan\Type\Constant\ConstantStringType('x')),
		'hasOffsetValue0Int1' => new $hasOffsetValueClass(new \PHPStan\Type\Constant\ConstantIntegerType(0), new \PHPStan\Type\Constant\ConstantIntegerType(1)),
		'hasOffsetValue1Nullable' => new $hasOffsetValueClass(new \PHPStan\Type\Constant\ConstantIntegerType(1), new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\NullType()])),
		'hasOffsetValueFalse' => new $hasOffsetValueClass(new \PHPStan\Type\Constant\ConstantStringType('f'), new \PHPStan\Type\Constant\ConstantBooleanType(false)),
		'hasOffsetValueTemplate' => new $hasOffsetValueClass(new \PHPStan\Type\Constant\ConstantStringType('t'), $arrayTemplateT),
		'nonEmptyList' => new \PHPStan\Type\IntersectionType([new $arrayClass(\PHPStan\Type\IntegerRangeType::createAllGreaterThanOrEqualTo(0), new \PHPStan\Type\StringType()), new $nonEmptyClass(), new $listClass()]),
		'arrayWithOffsetValue' => new \PHPStan\Type\IntersectionType([new $arrayClass(new \PHPStan\Type\StringType(), new \PHPStan\Type\IntegerType()), new $hasOffsetValueClass(new \PHPStan\Type\Constant\ConstantStringType('a'), new \PHPStan\Type\IntegerType()), new $nonEmptyClass()]),
		'arrayWithOffset' => new \PHPStan\Type\IntersectionType([new $arrayClass(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()), new $hasOffsetClass(new \PHPStan\Type\Constant\ConstantIntegerType(0)), new $nonEmptyClass()]),
		'oversizedArray' => new \PHPStan\Type\IntersectionType([new $arrayClass(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()), new $oversizedClass()]),
	];
	$outOfClassScope = new \PHPStan\Analyser\OutOfClassScope();
	$identity = static fn ($t) => $t;
	$toObject = static fn ($t) => new \PHPStan\Type\ObjectType(\stdClass::class);
	$toNever = static fn ($t) => new \PHPStan\Type\NeverType();
	foreach ($subjects as $name => $subject) {
		$r["$name class"] = $view($subject);
		$r["$name instanceof"] = [$subject instanceof \PHPStan\Type\Type, $subject instanceof $arrayClass, $subject instanceof \PHPStan\Type\CompoundType, $subject instanceof \PHPStan\Type\Accessory\AccessoryType, $subject instanceof $nonEmptyClass, $subject instanceof $listClass, $subject instanceof $oversizedClass, $subject instanceof $hasOffsetClass, $subject instanceof $hasOffsetValueClass];
		foreach (['typeOnly' => \PHPStan\Type\VerbosityLevel::typeOnly(), 'value' => \PHPStan\Type\VerbosityLevel::value(), 'precise' => \PHPStan\Type\VerbosityLevel::precise(), 'cache' => \PHPStan\Type\VerbosityLevel::cache()] as $levelName => $level) {
			$r["$name describe $levelName"] = $subject->describe($level);
		}
		foreach ($others as $otherName => $other) {
			$r["$name isSuperTypeOf $otherName"] = $view($subject->isSuperTypeOf($other));
			$r["$name accepts $otherName"] = $view($subject->accepts($other, true));
			$r["$name accepts-loose $otherName"] = $view($subject->accepts($other, false));
			$r["$name equals $otherName"] = $subject->equals($other);
			$r["$name tryRemove $otherName"] = $view($subject->tryRemove($other));
			foreach ($arrayPhpVersions as $vi => $phpVersion) {
				$r["$name looseCompare $otherName $vi"] = $view($subject->looseCompare($other, $phpVersion));
				$r["$name isSmallerThan $otherName $vi"] = $view($subject->isSmallerThan($other, $phpVersion));
				$r["$name isSmallerThanOrEqual $otherName $vi"] = $view($subject->isSmallerThanOrEqual($other, $phpVersion));
			}
			$r["$name traverseSimultaneously $otherName"] = $view($subject->traverseSimultaneously($other, static fn ($a, $b) => $a));
			$r["$name traverseSimultaneously-right $otherName"] = $view($subject->traverseSimultaneously($other, static fn ($a, $b) => $b));
			$r["$name getOffsetValueType $otherName"] = $view($subject->getOffsetValueType($other));
			$r["$name hasOffsetValueType $otherName"] = $view($subject->hasOffsetValueType($other));
			try {
				$r["$name setOffsetValueType $otherName"] = [$view($subject->setOffsetValueType($other, $others['int1'])), $view($subject->setOffsetValueType($other, $others['stringAbc'], false)), $view($subject->setOffsetValueType($other, $others['constArray']))];
			} catch (\PHPStan\ShouldNotHappenException $e) {
				$r["$name setOffsetValueType $otherName"] = 'ShouldNotHappenException';
			}
			$r["$name setExistingOffsetValueType $otherName"] = [$view($subject->setExistingOffsetValueType($other, $others['int1'])), $view($subject->setExistingOffsetValueType($other, $others['constArrayOptional'])), $view($subject->setExistingOffsetValueType($other, $others['constArrayNested']))];
			$r["$name unsetOffset $otherName"] = $view($subject->unsetOffset($other));
			$r["$name inferTemplateTypes $otherName"] = $view($subject->inferTemplateTypes($other));
			$r["$name fillKeysArray $otherName"] = $view($subject->fillKeysArray($other));
			$r["$name intersectKeyArray $otherName"] = $view($subject->intersectKeyArray($other));
			$r["$name truncateListToSize $otherName"] = $view($subject->truncateListToSize($other));
			$r["$name searchArray $otherName"] = [$view($subject->searchArray($other)), $view($subject->searchArray($other, \PHPStan\TrinaryLogic::createYes())), $view($subject->searchArray($other, \PHPStan\TrinaryLogic::createNo()))];
			$r["$name chunkArray $otherName"] = [$view($subject->chunkArray($other, \PHPStan\TrinaryLogic::createNo())), $view($subject->chunkArray($other, \PHPStan\TrinaryLogic::createYes()))];
			$r["$name sliceArray $otherName"] = [$view($subject->sliceArray($other, $other, \PHPStan\TrinaryLogic::createMaybe())), $view($subject->sliceArray($others['int0'], $other, \PHPStan\TrinaryLogic::createNo())), $view($subject->sliceArray($other, $others['null'], \PHPStan\TrinaryLogic::createYes())), $view($subject->sliceArray($other, $others['range1-max'], \PHPStan\TrinaryLogic::createYes()))];
			$r["$name spliceArray $otherName"] = [$view($subject->spliceArray($other, $other, $other)), $view($subject->spliceArray($others['int0'], $others['null'], $other)), $view($subject->spliceArray($others['int1'], $others['int0'], $other))];
			$r["$name getKeysArrayFiltered $otherName"] = $view($subject->getKeysArrayFiltered($other, \PHPStan\TrinaryLogic::createYes()));
			$r["$name exponentiate $otherName"] = $view($subject->exponentiate($other));
			if ($subject instanceof \PHPStan\Type\CompoundType) {
				$r["$name isSubTypeOf $otherName"] = $view($subject->isSubTypeOf($other));
				$r["$name isAcceptedBy $otherName"] = $view($subject->isAcceptedBy($other, true));
				$r["$name isAcceptedBy-loose $otherName"] = $view($subject->isAcceptedBy($other, false));
				foreach ($arrayPhpVersions as $vi => $phpVersion) {
					$r["$name isGreaterThan $otherName $vi"] = $view($subject->isGreaterThan($other, $phpVersion));
					$r["$name isGreaterThanOrEqual $otherName $vi"] = $view($subject->isGreaterThanOrEqual($other, $phpVersion));
				}
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
			// the memoized answers (isList, getIterableKeyType) must read back the same
			$r["$name $method again"] = $view($subject->$method());
		}
		foreach (['getSmallerType', 'getSmallerOrEqualType', 'getGreaterType', 'getGreaterOrEqualType'] as $method) {
			$r["$name $method"] = $view($subject->$method($arrayPhpVersions[1]));
		}
		foreach ([\PHPStan\Type\GeneralizePrecision::lessSpecific(), \PHPStan\Type\GeneralizePrecision::moreSpecific(), \PHPStan\Type\GeneralizePrecision::templateArgument()] as $i => $precision) {
			$r["$name generalize $i"] = $view($subject->generalize($precision));
		}
		$r["$name toCoercedArgumentType"] = [$view($subject->toCoercedArgumentType(true)), $view($subject->toCoercedArgumentType(false))];
		$r["$name traverse identity"] = $subject->traverse($identity) === $subject;
		$r["$name traverse replaced"] = $view($subject->traverse($toObject));
		$r["$name traverse never"] = $view($subject->traverse($toNever));
		$r["$name traverseSimultaneously never"] = $view($subject->traverseSimultaneously($others['arrayIntString'], $toNever));
		$r["$name getTemplateType"] = $view($subject->getTemplateType('Foo', 'T'));
		$r["$name hasProperty"] = $view($subject->hasProperty('x'));
		$r["$name hasInstanceProperty"] = $view($subject->hasInstanceProperty('x'));
		$r["$name hasStaticProperty"] = $view($subject->hasStaticProperty('x'));
		$r["$name hasMethod"] = $view($subject->hasMethod('x'));
		$r["$name hasConstant"] = $view($subject->hasConstant('X'));
		$r["$name setOffsetValueType null"] = [$view($subject->setOffsetValueType(null, $others['int1'])), $view($subject->setOffsetValueType(null, $others['stringAbc'], false)), $view($subject->setOffsetValueType(null, $others['constArray'], unionValues: true))];
		$r["$name mapValueType"] = [$view($subject->mapValueType($identity)), $view($subject->mapValueType($toObject))];
		$r["$name mapKeyType"] = [$view($subject->mapKeyType($identity)), $view($subject->mapKeyType(static fn ($t) => new \PHPStan\Type\StringType()))];
		$r["$name changeKeyCaseArray"] = [$view($subject->changeKeyCaseArray(null)), $view($subject->changeKeyCaseArray(CASE_LOWER)), $view($subject->changeKeyCaseArray(CASE_UPPER))];
		$r["$name reverseArray"] = [$view($subject->reverseArray(\PHPStan\TrinaryLogic::createYes())), $view($subject->reverseArray(\PHPStan\TrinaryLogic::createNo()))];
		$r["$name toClassConstantType"] = $view($subject->toClassConstantType($stringReflectionProvider));
		$r["$name toObjectTypeForIsACheck"] = [$view($subject->toObjectTypeForIsACheck($others['mixed'], true, true)), $view($subject->toObjectTypeForIsACheck($others['object'], false, false))];
		foreach (['getProperty', 'getInstanceProperty', 'getStaticProperty', 'getMethod', 'getConstant'] as $method) {
			try {
				$args = $method === 'getConstant' ? ['x'] : ['x', $outOfClassScope];
				$member = $subject->$method(...$args);
				$r["$name $method"] = [get_class($member), $member->getName(), $member->getDeclaringClass()->getName()];
			} catch (\Throwable $e) {
				// the PHP intersection subjects throw on an unresolvable member
				$r["$name $method"] = get_class($e);
			}
		}
		foreach (['getUnresolvedPropertyPrototype', 'getUnresolvedInstancePropertyPrototype', 'getUnresolvedStaticPropertyPrototype', 'getUnresolvedMethodPrototype'] as $method) {
			try {
				$prototype = $subject->$method('x', $outOfClassScope);
				$transformed = $method === 'getUnresolvedMethodPrototype' ? $prototype->getTransformedMethod() : $prototype->getTransformedProperty();
				$naive = $method === 'getUnresolvedMethodPrototype' ? $prototype->getNakedMethod() : $prototype->getNakedProperty();
				$withStatic = $prototype->doNotResolveTemplateTypeMapToBounds();
				$r["$name $method"] = [get_class($prototype), get_class($transformed), $transformed->getName(), get_class($naive), get_class($withStatic)];
			} catch (\Throwable $e) {
				$r["$name $method"] = get_class($e);
			}
		}
		try {
			$acceptors = $subject->getCallableParametersAcceptors($outOfClassScope);
			$r["$name getCallableParametersAcceptors"] = array_map(static fn ($acceptor) => [get_class($acceptor), $acceptor->getReturnType()->describe(\PHPStan\Type\VerbosityLevel::precise()), count($acceptor->getParameters())], $acceptors);
		} catch (\Throwable $e) {
			$r["$name getCallableParametersAcceptors"] = get_class($e);
		}
		if ($subject instanceof $arrayClass) {
			$r["$name getKeyType/getItemType"] = [$view($subject->getKeyType()), $view($subject->getItemType())];
			$r["$name generalizeValues"] = $view($subject->generalizeValues());
		}
		if ($subject instanceof \PHPStan\Type\Accessory\AccessoryType) {
			$r["$name getDefaultBaseType"] = $view($subject->getDefaultBaseType());
		}
		if ($subject instanceof $hasOffsetClass || $subject instanceof $hasOffsetValueClass) {
			$r["$name getOffsetType"] = $view($subject->getOffsetType());
		}
		if ($subject instanceof $hasOffsetValueClass) {
			$r["$name getValueType"] = $view($subject->getValueType());
		}
	}
	// the family through the compound types and the combinator, the way the
	// analysis exercises it
	foreach (['arrayMixed', 'arrayIntString', 'arrayStringInt', 'arrayListKeys', 'arrayConstKeys', 'nonEmpty', 'list', 'oversized', 'hasOffsetA', 'hasOffset0', 'hasOffsetValueA', 'hasOffsetValue0', 'templateArray'] as $name) {
		$subject = $subjects[$name];
		foreach (['int', 'null', 'union', 'unionNullable', 'mixed', 'never', 'array', 'arrayIntString', 'arrayStringInt', 'list', 'nonEmptyArray', 'nonEmptyList', 'oversizedArray', 'arrayWithOffsetA', 'arrayWithOffset0', 'emptyArray', 'constArray', 'constArrayInts', 'constArrayOptional', 'object', 'arrayAccess', 'string', 'callable', 'iterable', 'nonEmpty', 'listAccessory', 'oversized', 'hasOffsetA', 'hasOffset0', 'hasOffsetValueA', 'hasOffsetValue0', 'templateArray'] as $otherName) {
			$other = $others[$otherName];
			$r["combinator union $name $otherName"] = $view(\PHPStan\Type\TypeCombinator::union($subject, $other));
			$r["combinator intersect $name $otherName"] = $view(\PHPStan\Type\TypeCombinator::intersect($subject, $other));
			$r["combinator remove $name $otherName"] = $view(\PHPStan\Type\TypeCombinator::remove($subject, $other));
			$r["combinator remove-reverse $name $otherName"] = $view(\PHPStan\Type\TypeCombinator::remove($other, $subject));
			$r["union isSuperTypeOf $name $otherName"] = $view($others['unionArrays']->isSuperTypeOf($subject));
			$r["union accepts $name $otherName"] = $view($others['unionArrays']->accepts($subject, true));
			$r["other isSuperTypeOf $name $otherName"] = $view($other->isSuperTypeOf($subject));
			$r["other accepts $name $otherName"] = $view($other->accepts($subject, true));
			$r["other equals $name $otherName"] = $other->equals($subject);
		}
		$r["combinator removeNull $name"] = $view(\PHPStan\Type\TypeCombinator::removeNull($subject));
		$r["combinator addNull $name"] = $view(\PHPStan\Type\TypeCombinator::addNull($subject));
	}
	// equality over fresh instances
	$r['array equals array'] = [(new $arrayClass(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()))->equals(new $arrayClass(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType())), (new $arrayClass(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()))->equals(new $arrayClass(new \PHPStan\Type\StringType(), new \PHPStan\Type\StringType())), (new $arrayClass(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()))->equals($subjects['templateArray']), $subjects['templateArray']->equals(new $arrayClass(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()))];
	$r['hasOffset equals hasOffset'] = [(new $hasOffsetClass(new \PHPStan\Type\Constant\ConstantStringType('a')))->equals(new $hasOffsetClass(new \PHPStan\Type\Constant\ConstantStringType('a'))), (new $hasOffsetClass(new \PHPStan\Type\Constant\ConstantStringType('a')))->equals(new $hasOffsetClass(new \PHPStan\Type\Constant\ConstantIntegerType(0)))];
	$r['hasOffsetValue equals hasOffsetValue'] = [(new $hasOffsetValueClass(new \PHPStan\Type\Constant\ConstantStringType('a'), new \PHPStan\Type\IntegerType()))->equals(new $hasOffsetValueClass(new \PHPStan\Type\Constant\ConstantStringType('a'), new \PHPStan\Type\IntegerType())), (new $hasOffsetValueClass(new \PHPStan\Type\Constant\ConstantStringType('a'), new \PHPStan\Type\IntegerType()))->equals(new $hasOffsetValueClass(new \PHPStan\Type\Constant\ConstantStringType('a'), new \PHPStan\Type\StringType()))];
	// the typed constructor parameters: wrong classes and non-objects are TypeErrors (the message differs in its "called in" suffix, so only the class is observed)
	foreach ([[$arrayClass, [new \PHPStan\Type\IntegerType()]], [$arrayClass, ['x', new \PHPStan\Type\IntegerType()]], [$arrayClass, [new \PHPStan\Type\IntegerType(), null]], [$hasOffsetClass, [new \PHPStan\Type\IntegerType()]], [$hasOffsetClass, ['a']], [$hasOffsetValueClass, [new \PHPStan\Type\StringType(), new \PHPStan\Type\IntegerType()]], [$hasOffsetValueClass, [new \PHPStan\Type\Constant\ConstantIntegerType(0), 'x']], [$hasOffsetValueClass, [new \PHPStan\Type\Constant\ConstantIntegerType(0)]]] as $i => [$class, $args]) {
		try {
			$r["construct $i"] = $view(new $class(...$args));
		} catch (\TypeError $e) {
			$r["construct $i"] = 'TypeError';
		} catch (\ArgumentCountError $e) {
			$r["construct $i"] = 'ArgumentCountError';
		}
	}
	// the constructors by named arguments
	$r['array named'] = $view(new $arrayClass(itemType: new \PHPStan\Type\StringType(), keyType: new \PHPStan\Type\IntegerType()));
	$r['hasOffsetValue named'] = $view(new $hasOffsetValueClass(valueType: new \PHPStan\Type\StringType(), offsetType: new \PHPStan\Type\Constant\ConstantIntegerType(3)));
	// an uninitialized instance: every typed-slot read raises the same Error
	foreach ([$arrayClass, $hasOffsetClass, $hasOffsetValueClass] as $uninitializedClass) {
		$uninitialized = (new \ReflectionClass($uninitializedClass))->newInstanceWithoutConstructor();
		foreach (['describe' => [\PHPStan\Type\VerbosityLevel::precise()], 'isSuperTypeOf' => [$others['int']], 'equals' => [$uninitialized], 'getKeyType' => [], 'getItemType' => [], 'getOffsetType' => [], 'getValueType' => [], 'getIterableKeyType' => [], 'isList' => [], 'toPhpDocNode' => [], 'hasTemplateOrLateResolvableType' => [], 'getReferencedClasses' => [], 'isCallable' => [], 'unsetOffset' => [$others['int0']]] as $method => $args) {
			if (!method_exists($uninitialized, $method)) {
				continue;
			}
			try {
				$r["uninitialized $uninitializedClass $method"] = $view($uninitialized->$method(...$args));
			} catch (\Error $e) {
				$r["uninitialized $uninitializedClass $method"] = [get_class($e), $e->getMessage()];
			}
		}
	}
	// PHP subclasses overriding what the natives call through $this
	$anonymousArray = new class (new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()) extends \PHPStan\Type\ArrayType {

		protected function withTypes(\PHPStan\Type\Type $keyType, \PHPStan\Type\Type $itemType): \PHPStan\Type\ArrayType
		{
			return new \PHPStan\Type\ArrayType(new \PHPStan\Type\StringType(), $itemType);
		}

		public function getItemType(): \PHPStan\Type\Type
		{
			return new \PHPStan\Type\FloatType();
		}

		public function getIterableKeyType(): \PHPStan\Type\Type
		{
			return new \PHPStan\Type\Constant\ConstantIntegerType(7);
		}

		public function isCallable(): \PHPStan\TrinaryLogic
		{
			return \PHPStan\TrinaryLogic::createNo();
		}

		public function getKeysArray(): \PHPStan\Type\Type
		{
			return new \PHPStan\Type\NullType();
		}

	};
	foreach (['getValuesArray', 'getKeysArray', 'flipArray', 'shuffleArray', 'popArray', 'getFirstIterableKeyType', 'getLastIterableValueType', 'generalizeValues', 'filterArrayRemovingFalsey', 'isList', 'toPhpDocNode'] as $method) {
		$r["anonymous array $method"] = $view($anonymousArray->$method());
	}
	$r['anonymous array describe'] = $anonymousArray->describe(\PHPStan\Type\VerbosityLevel::precise());
	$r['anonymous array getKeysArrayFiltered'] = $view($anonymousArray->getKeysArrayFiltered($others['int'], \PHPStan\TrinaryLogic::createYes()));
	$r['anonymous array setOffsetValueType'] = [$view($anonymousArray->setOffsetValueType($others['int5'], $others['int1'])), $view($anonymousArray->setOffsetValueType($others['string'], $others['int1'])), $view($anonymousArray->setOffsetValueType(null, $others['int1']))];
	$r['anonymous array mapValueType'] = $view($anonymousArray->mapValueType($toObject));
	$r['anonymous array mapKeyType'] = $view($anonymousArray->mapKeyType($toObject));
	$r['anonymous array traverse'] = [$view($anonymousArray->traverse($identity)), $view($anonymousArray->traverse($toObject))];
	$r['anonymous array changeKeyCaseArray'] = $view($anonymousArray->changeKeyCaseArray(CASE_LOWER));
	$r['anonymous array sliceArray'] = $view($anonymousArray->sliceArray($others['int1'], $others['int1'], \PHPStan\TrinaryLogic::createNo()));
	$r['anonymous array spliceArray'] = $view($anonymousArray->spliceArray($others['int1'], $others['int1'], $others['constArray']));
	$r['anonymous array intersectKeyArray'] = [$view($anonymousArray->intersectKeyArray($others['arrayStringInt'])), $view($anonymousArray->intersectKeyArray($others['constArray']))];
	$r['anonymous array isSuperTypeOf'] = [$view($anonymousArray->isSuperTypeOf($others['arrayIntString'])), $view($subjects['arrayIntString']->isSuperTypeOf($anonymousArray)), $view($anonymousArray->isSuperTypeOf($others['constArrayInts']))];
	$r['anonymous array accepts'] = [$view($anonymousArray->accepts($others['arrayIntString'], true)), $view($subjects['arrayIntString']->accepts($anonymousArray, true))];
	$r['anonymous array equals'] = [$anonymousArray->equals($subjects['arrayIntString']), $subjects['arrayIntString']->equals($anonymousArray)];
	$r['anonymous array inferTemplateTypes'] = $view($subjects['arrayTemplates']->inferTemplateTypes($anonymousArray));
	$r['anonymous array searchArray'] = $view($anonymousArray->searchArray($others['int1'], \PHPStan\TrinaryLogic::createYes()));
	try {
		$r['anonymous array getCallableParametersAcceptors'] = $view($anonymousArray->getCallableParametersAcceptors($outOfClassScope));
	} catch (\PHPStan\ShouldNotHappenException $e) {
		$r['anonymous array getCallableParametersAcceptors'] = 'ShouldNotHappenException';
	}
	$r['anonymous array truncateListToSize'] = [$view($anonymousArray->truncateListToSize($others['range2-4'])), $view($anonymousArray->truncateListToSize($others['range3-max']))];
	$anonymousNonEmpty = new class extends \PHPStan\Type\Accessory\NonEmptyArrayType {

		public function equals(\PHPStan\Type\Type $type): bool
		{
			return $type instanceof \PHPStan\Type\IntegerType;
		}

		public function isSubTypeOf(\PHPStan\Type\Type $otherType): \PHPStan\Type\IsSuperTypeOfResult
		{
			return \PHPStan\Type\IsSuperTypeOfResult::createNo(['anonymous']);
		}

	};
	$r['anonymous nonEmpty isSuperTypeOf'] = [$view($anonymousNonEmpty->isSuperTypeOf($others['int'])), $view($anonymousNonEmpty->isSuperTypeOf($others['nonEmpty'])), $view($subjects['nonEmpty']->isSuperTypeOf($anonymousNonEmpty))];
	$r['anonymous nonEmpty isAcceptedBy'] = $view($anonymousNonEmpty->isAcceptedBy($others['array'], true));
	$r['anonymous nonEmpty equals'] = [$anonymousNonEmpty->equals($subjects['nonEmpty']), $subjects['nonEmpty']->equals($anonymousNonEmpty)];
	$anonymousList = new class extends \PHPStan\Type\Accessory\AccessoryArrayListType {

		public function getIterableKeyType(): \PHPStan\Type\Type
		{
			return new \PHPStan\Type\Constant\ConstantIntegerType(0);
		}

		public function hasOffsetValueType(\PHPStan\Type\Type $offsetType): \PHPStan\TrinaryLogic
		{
			return \PHPStan\TrinaryLogic::createYes();
		}

	};
	$r['anonymous list getLastIterableKeyType'] = $view($anonymousList->getLastIterableKeyType());
	$r['anonymous list unsetOffset'] = [$view($anonymousList->unsetOffset($others['int0'])), $view($anonymousList->unsetOffset($others['string']))];
	$r['anonymous list isSubTypeOf'] = [$view($anonymousList->isSubTypeOf($subjects['list'])), $view($subjects['list']->isSubTypeOf($anonymousList)), $view($subjects['list']->isSuperTypeOf($anonymousList))];
	$anonymousHasOffsetValue = new class (new \PHPStan\Type\Constant\ConstantStringType('a'), new \PHPStan\Type\IntegerType()) extends \PHPStan\Type\Accessory\HasOffsetValueType {

		public function getKeysArray(): \PHPStan\Type\Type
		{
			return new \PHPStan\Type\NullType();
		}

		public function getOffsetType(): \PHPStan\Type\Constant\ConstantStringType|\PHPStan\Type\Constant\ConstantIntegerType
		{
			return new \PHPStan\Type\Constant\ConstantStringType('overridden');
		}

	};
	$r['anonymous hasOffsetValue getKeysArrayFiltered'] = $view($anonymousHasOffsetValue->getKeysArrayFiltered($others['int'], \PHPStan\TrinaryLogic::createYes()));
	$r['anonymous hasOffsetValue tryRemove'] = [$view($subjects['hasOffsetValueA']->tryRemove($anonymousHasOffsetValue)), $view($anonymousHasOffsetValue->tryRemove($subjects['hasOffsetValueA'])), $view($subjects['arrayStringInt']->tryRemove($anonymousHasOffsetValue))];
	$r['anonymous hasOffsetValue equals'] = [$anonymousHasOffsetValue->equals($subjects['hasOffsetValueA']), $subjects['hasOffsetValueA']->equals($anonymousHasOffsetValue)];
	$r['anonymous hasOffsetValue describe'] = $anonymousHasOffsetValue->describe(\PHPStan\Type\VerbosityLevel::precise());
	foreach ($r as $key => $value) {
		$observations["array $key"] = $value;
	}
}


// ---- ObjectType / GenericObjectType / EnumCaseObjectType ----
// the reflection provider and PhpVersion accessors stay registered from the
// string section; the PHP subclasses over the native parents come along:
// TemplateObjectType, TemplateGenericObjectType (overriding recreate()),
// and anonymous subclasses overriding what the natives call through $this
$objectPhpVersions = [new \PHPStan\Php\PhpVersion(70400), new \PHPStan\Php\PhpVersion(80400)];
$objectTemplateScope = \PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('foo');
$objectEnum = \ObjectTypeEnums\FooEnum::class;
$objectBackedEnum = \Bug12512\FooBarEnum::class;
$objectOthers = static fn (string $object, string $generic, string $case): array => [
	'stdClass' => new $object(\stdClass::class),
	'exception' => new $object(\Exception::class),
	'throwable' => new $object(\Throwable::class),
	'runtimeException' => new $object(\RuntimeException::class),
	'traversable' => new $object(\Traversable::class),
	'iterator' => new $object(\Iterator::class),
	'countable' => new $object(\Countable::class),
	'arrayAccess' => new $object(\ArrayAccess::class),
	'arrayIterator' => new $object(\ArrayIterator::class),
	'dateTimeInterface' => new $object(\DateTimeInterface::class),
	'dateTime' => new $object(\DateTime::class),
	'closure' => new $object(\Closure::class),
	'trinary' => new $object(\PHPStan\TrinaryLogic::class),
	'typeInterface' => new $object(\PHPStan\Type\Type::class),
	'unknown' => new $object('NonexistentClass'),
	'throwableMinusException' => new $object(\Throwable::class, new $object(\Exception::class)),
	'enum' => new $object($objectEnum),
	'enumMinusFoo' => new $object($objectEnum, new $case($objectEnum, 'FOO')),
	'backedEnum' => new $object($objectBackedEnum),
	'genericArrayIterator' => new $generic(\ArrayIterator::class, [new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]),
	'genericArrayIteratorMixed' => new $generic(\ArrayIterator::class, [new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()]),
	'genericIteratorCovariant' => new $generic(\Iterator::class, [new \PHPStan\Type\MixedType(), new $object(\stdClass::class)], null, null, [\PHPStan\Type\Generic\TemplateTypeVariance::createCovariant(), \PHPStan\Type\Generic\TemplateTypeVariance::createCovariant()]),
	'genericTraversableOne' => new $generic(\Traversable::class, [new \PHPStan\Type\IntegerType()]),
	'genericUnknown' => new $generic('NonexistentClass', [new \PHPStan\Type\IntegerType()]),
	'caseFoo' => new $case($objectEnum, 'FOO'),
	'caseBar' => new $case($objectEnum, 'BAR'),
	'caseBacked' => new $case($objectBackedEnum, 'CASE_ONE'),
	'caseUnknown' => new $case('NonexistentEnum', 'X'),
	'static' => new \PHPStan\Type\StaticType($stringReflectionProvider->getClass(\PHPStan\TrinaryLogic::class)),
	'templateObject' => new \PHPStan\Type\Generic\TemplateObjectType($objectTemplateScope, new \PHPStan\Type\Generic\TemplateTypeParameterStrategy(), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant(), 'T', new $object(\Countable::class), null),
	'templateMixed' => \PHPStan\Type\Generic\TemplateTypeFactory::create($objectTemplateScope, 'U', null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
	'objectWithoutClass' => new \PHPStan\Type\ObjectWithoutClassType(),
	'objectWithoutClassMinusStdClass' => new \PHPStan\Type\ObjectWithoutClassType(new $object(\stdClass::class)),
	'closureType' => new \PHPStan\Type\ClosureType(),
	'callable' => new \PHPStan\Type\CallableType(),
	'int' => new \PHPStan\Type\IntegerType(),
	'string' => new \PHPStan\Type\StringType(),
	'stringStdClass' => new \PHPStan\Type\Constant\ConstantStringType(\stdClass::class),
	'classString' => new \PHPStan\Type\ClassStringType(),
	'genericClassString' => new \PHPStan\Type\Generic\GenericClassStringType(new $object(\Exception::class)),
	'mixed' => new \PHPStan\Type\MixedType(),
	'mixedMinusStdClass' => new \PHPStan\Type\MixedType(false, new $object(\stdClass::class)),
	'null' => new \PHPStan\Type\NullType(),
	'true' => new \PHPStan\Type\Constant\ConstantBooleanType(true),
	'never' => new \PHPStan\Type\NeverType(),
	'array' => new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()),
	'iterable' => new \PHPStan\Type\IterableType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()),
	'union' => new \PHPStan\Type\UnionType([new $object(\Exception::class), new $object(\Error::class)]),
	'unionNullable' => new \PHPStan\Type\UnionType([new $object(\stdClass::class), new \PHPStan\Type\NullType()]),
	'unionCases' => new \PHPStan\Type\UnionType([new $case($objectEnum, 'FOO'), new $case($objectEnum, 'BAR')]),
	'intersection' => new \PHPStan\Type\IntersectionType([new $object(\Countable::class), new $object(\Traversable::class)]),
];
{
	$objectClass = \PHPStan\Type\ObjectType::class;
	$genericClass = \PHPStan\Type\Generic\GenericObjectType::class;
	$caseClass = \PHPStan\Type\Enum\EnumCaseObjectType::class;
	$objectClass::resetCaches();
	$r = [];
	$others = $objectOthers($objectClass, $genericClass, $caseClass);
	$trinaryReflection = $stringReflectionProvider->getClass(\PHPStan\TrinaryLogic::class);
	$arrayIteratorReflection = $stringReflectionProvider->getClass(\ArrayIterator::class);
	$enumReflection = $stringReflectionProvider->getClass($objectEnum);
	$subjects = [
		'stdClass' => new $objectClass(\stdClass::class),
		'exception' => new $objectClass(\Exception::class),
		'throwable' => new $objectClass(\Throwable::class),
		'traversable' => new $objectClass(\Traversable::class),
		'iterator' => new $objectClass(\Iterator::class),
		'iteratorAggregate' => new $objectClass(\IteratorAggregate::class),
		'countable' => new $objectClass(\Countable::class),
		'arrayAccess' => new $objectClass(\ArrayAccess::class),
		'arrayIterator' => new $objectClass(\ArrayIterator::class),
		'arrayObject' => new $objectClass(\ArrayObject::class),
		'dateTimeInterface' => new $objectClass(\DateTimeInterface::class),
		'closure' => new $objectClass(\Closure::class),
		'simpleXml' => new $objectClass('SimpleXMLElement'),
		'gmp' => new $objectClass('GMP'),
		'curl' => new $objectClass('CurlHandle'),
		'trinary' => new $objectClass(\PHPStan\TrinaryLogic::class),
		'trinaryWithReflection' => new $objectClass(\PHPStan\TrinaryLogic::class, null, $trinaryReflection),
		'trinaryLowercase' => new $objectClass('phpstan\\trinarylogic'),
		'unionType' => new $objectClass(\PHPStan\Type\UnionType::class),
		'typeInterface' => new $objectClass(\PHPStan\Type\Type::class),
		'exceptionAsFinal' => new $objectClass(\Exception::class, null, $stringReflectionProvider->getClass(\Exception::class)->asFinal()),
		'unknown' => new $objectClass('NonexistentClass'),
		'throwableMinusException' => new $objectClass(\Throwable::class, new $objectClass(\Exception::class)),
		'stdClassMinusNever' => new $objectClass(\stdClass::class, new \PHPStan\Type\NeverType()),
		'enum' => new $objectClass($objectEnum),
		'enumWithReflection' => new $objectClass($objectEnum, null, $enumReflection),
		'enumMinusFoo' => new $objectClass($objectEnum, new $caseClass($objectEnum, 'FOO')),
		'enumMinusFooBar' => new $objectClass($objectEnum, new \PHPStan\Type\UnionType([new $caseClass($objectEnum, 'FOO'), new $caseClass($objectEnum, 'BAR')])),
		'backedEnum' => new $objectClass($objectBackedEnum),
		'unitEnum' => new $objectClass(\UnitEnum::class),
		'genericArrayIterator' => new $genericClass(\ArrayIterator::class, [new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]),
		'genericArrayIteratorMixed' => new $genericClass(\ArrayIterator::class, [new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()]),
		'genericArrayIteratorWithReflection' => new $genericClass(\ArrayIterator::class, [new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()], null, $arrayIteratorReflection->withTypes([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()])),
		'genericArrayIteratorMinus' => new $genericClass(\ArrayIterator::class, [new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()], new $objectClass(\stdClass::class)),
		'genericIteratorCovariant' => new $genericClass(\Iterator::class, [new \PHPStan\Type\MixedType(), new $objectClass(\stdClass::class)], null, null, [\PHPStan\Type\Generic\TemplateTypeVariance::createCovariant(), \PHPStan\Type\Generic\TemplateTypeVariance::createCovariant()]),
		'genericIteratorContravariant' => new $genericClass(\Iterator::class, [new \PHPStan\Type\IntegerType(), new $objectClass(\Exception::class)], null, null, [\PHPStan\Type\Generic\TemplateTypeVariance::createContravariant()]),
		'genericTraversableOne' => new $genericClass(\Traversable::class, [new \PHPStan\Type\IntegerType()]),
		'genericTraversableThree' => new $genericClass(\Traversable::class, [new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType(), new \PHPStan\Type\FloatType()]),
		'genericStdClass' => new $genericClass(\stdClass::class, [new \PHPStan\Type\IntegerType()]),
		'genericUnknown' => new $genericClass('NonexistentClass', [new \PHPStan\Type\IntegerType()]),
		'genericEnum' => new $genericClass($objectEnum, []),
		'genericTemplateArgument' => new $genericClass(\ArrayIterator::class, [new \PHPStan\Type\IntegerType(), \PHPStan\Type\Generic\TemplateTypeFactory::create($objectTemplateScope, 'T', null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant())]),
		'caseFoo' => new $caseClass($objectEnum, 'FOO'),
		'caseBarWithReflection' => new $caseClass($objectEnum, 'BAR', $enumReflection),
		'caseBacked' => new $caseClass($objectBackedEnum, 'CASE_ONE'),
		'caseUnknown' => new $caseClass('NonexistentEnum', 'X'),
		'caseUnknownCase' => new $caseClass($objectEnum, 'NOPE'),
		'caseNotEnum' => new $caseClass(\stdClass::class, 'FOO'),
		'templateObject' => new \PHPStan\Type\Generic\TemplateObjectType($objectTemplateScope, new \PHPStan\Type\Generic\TemplateTypeParameterStrategy(), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant(), 'T', new $objectClass(\Countable::class), null),
		'templateGeneric' => new \PHPStan\Type\Generic\TemplateGenericObjectType($objectTemplateScope, new \PHPStan\Type\Generic\TemplateTypeParameterStrategy(), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant(), 'T', new $genericClass(\ArrayIterator::class, [new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]), null),
	];
	$outOfClassScope = new \PHPStan\Analyser\OutOfClassScope();
	$inClassScope = new class ($trinaryReflection) implements \PHPStan\Reflection\ClassMemberAccessAnswerer {

		public function __construct(private \PHPStan\Reflection\ClassReflection $classReflection)
		{
		}

		public function isInClass(): bool
		{
			return true;
		}

		public function getClassReflection(): ?\PHPStan\Reflection\ClassReflection
		{
			return $this->classReflection;
		}

		public function canAccessProperty(\PHPStan\Reflection\PropertyReflection $propertyReflection): bool
		{
			return true;
		}

		public function canReadProperty(\PHPStan\Reflection\ExtendedPropertyReflection $propertyReflection): bool
		{
			return true;
		}

		public function canWriteProperty(\PHPStan\Reflection\ExtendedPropertyReflection $propertyReflection): bool
		{
			return true;
		}

		public function canCallMethod(\PHPStan\Reflection\MethodReflection $methodReflection): bool
		{
			return true;
		}

		public function canAccessConstant(\PHPStan\Reflection\ClassConstantReflection $constantReflection): bool
		{
			return true;
		}

	};
	// reflections and member reflections by their identity, not their class
	$ov = static function (mixed $v) use (&$ov, $view): mixed {
		if ($v instanceof \PHPStan\Reflection\ClassReflection) {
			return ['ClassReflection', $v->getName(), $v->getDisplayName(), $v->isGeneric(), array_keys($v->getActiveTemplateTypeMap()->getTypes()), $v->hasFinalByKeywordOverride(), $v->isFinalByKeyword()];
		}
		if ($v instanceof \PHPStan\Reflection\PropertyReflection || $v instanceof \PHPStan\Reflection\MethodReflection || $v instanceof \PHPStan\Reflection\ClassConstantReflection) {
			return [get_class($v), $v->getName(), $v->getDeclaringClass()->getName()];
		}
		if ($v instanceof \PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection) {
			return [get_class($v), $ov($v->getNakedProperty()), $ov($v->getTransformedProperty()), get_class($v->doNotResolveTemplateTypeMapToBounds()), $v->getTransformedProperty()->getReadableType()->describe(\PHPStan\Type\VerbosityLevel::precise())];
		}
		if ($v instanceof \PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection) {
			return [get_class($v), $ov($v->getNakedMethod()), $ov($v->getTransformedMethod()), get_class($v->doNotResolveTemplateTypeMapToBounds()), count($v->getTransformedMethod()->getVariants())];
		}
		if ($v instanceof \PHPStan\Reflection\Callables\CallableParametersAcceptor) {
			return [get_class($v), $v->getReturnType()->describe(\PHPStan\Type\VerbosityLevel::precise()), count($v->getParameters())];
		}
		if (is_array($v)) {
			return array_map($ov, $v);
		}
		return $view($v);
	};
	$attempt = static function (callable $probe) use ($ov): mixed {
		try {
			return $ov($probe());
		} catch (\Throwable $e) {
			return [get_class($e), $e->getMessage()];
		}
	};
	// the exception's class only: a TypeError raised by an internal
	// callback names the closure by its line in the twin
	$attemptClass = static function (callable $probe) use ($ov): mixed {
		try {
			return $ov($probe());
		} catch (\Throwable $e) {
			return get_class($e);
		}
	};
	$ancestorNames = [\stdClass::class, \Exception::class, \Throwable::class, \Traversable::class, \Iterator::class, \IteratorAggregate::class, \Countable::class, \ArrayAccess::class, \ArrayIterator::class, \PHPStan\TrinaryLogic::class, \PHPStan\Type\Type::class, 'NonexistentClass', $objectEnum, \UnitEnum::class, \DateTimeInterface::class, \Stringable::class, 'phpstan\\trinarylogic'];
	foreach ($subjects as $name => $subject) {
		$r["$name class"] = $view($subject);
		$r["$name instanceof"] = [$subject instanceof \PHPStan\Type\Type, $subject instanceof $objectClass, $subject instanceof $genericClass, $subject instanceof $caseClass, $subject instanceof \PHPStan\Type\TypeWithClassName, $subject instanceof \PHPStan\Type\SubtractableType, $subject instanceof \PHPStan\Type\CompoundType];
		foreach (['typeOnly' => \PHPStan\Type\VerbosityLevel::typeOnly(), 'value' => \PHPStan\Type\VerbosityLevel::value(), 'precise' => \PHPStan\Type\VerbosityLevel::precise(), 'cache' => \PHPStan\Type\VerbosityLevel::cache()] as $levelName => $level) {
			$r["$name describe $levelName"] = $subject->describe($level);
			// the memoized descriptions must read back the same
			$r["$name describe $levelName again"] = $subject->describe($level);
		}
		foreach ($others as $otherName => $other) {
			$r["$name isSuperTypeOf $otherName"] = $view($subject->isSuperTypeOf($other));
			$r["$name isSuperTypeOf $otherName again"] = $view($subject->isSuperTypeOf($other));
			$r["$name accepts $otherName"] = $attempt(static fn () => $subject->accepts($other, true));
			$r["$name accepts-loose $otherName"] = $attempt(static fn () => $subject->accepts($other, false));
			$r["$name equals $otherName"] = $subject->equals($other);
			$r["$name tryRemove $otherName"] = $view($subject->tryRemove($other));
			$r["$name subtract $otherName"] = $view($subject->subtract($other));
			$r["$name changeSubtractedType $otherName"] = $view($subject->changeSubtractedType($other));
			$r["$name describeSubtractedType $otherName"] = $subject->describeSubtractedType($other, \PHPStan\Type\VerbosityLevel::precise());
			foreach ($objectPhpVersions as $vi => $phpVersion) {
				$r["$name looseCompare $otherName $vi"] = $view($subject->looseCompare($other, $phpVersion));
				$r["$name isSmallerThan $otherName $vi"] = $view($subject->isSmallerThan($other, $phpVersion));
				$r["$name isSmallerThanOrEqual $otherName $vi"] = $view($subject->isSmallerThanOrEqual($other, $phpVersion));
			}
			$r["$name traverseSimultaneously $otherName"] = $view($subject->traverseSimultaneously($other, static fn ($a, $b) => $a));
			$r["$name traverseSimultaneously-right $otherName"] = $view($subject->traverseSimultaneously($other, static fn ($a, $b) => $b));
			$r["$name getOffsetValueType $otherName"] = $view($subject->getOffsetValueType($other));
			$r["$name hasOffsetValueType $otherName"] = $view($subject->hasOffsetValueType($other));
			$r["$name setOffsetValueType $otherName"] = [$view($subject->setOffsetValueType($other, $others['int'])), $view($subject->setOffsetValueType($other, $others['mixed'], false)), $view($subject->setOffsetValueType(null, $other))];
			$r["$name setExistingOffsetValueType $otherName"] = $view($subject->setExistingOffsetValueType($other, $others['int']));
			$r["$name unsetOffset $otherName"] = $view($subject->unsetOffset($other));
			$r["$name exponentiate $otherName"] = $attempt(static fn () => $subject->exponentiate($other));
			$r["$name inferTemplateTypes $otherName"] = $view($subject->inferTemplateTypes($other));
			$r["$name toObjectTypeForIsACheck $otherName"] = [$view($subject->toObjectTypeForIsACheck($other, true, true)), $view($subject->toObjectTypeForIsACheck($other, false, false))];
			if ($subject instanceof \PHPStan\Type\CompoundType) {
				$r["$name isSubTypeOf $otherName"] = $view($subject->isSubTypeOf($other));
				$r["$name isAcceptedBy $otherName"] = $view($subject->isAcceptedBy($other, true));
			}
		}
		foreach ($ancestorNames as $ancestorName) {
			$r["$name getAncestorWithClassName $ancestorName"] = $view($subject->getAncestorWithClassName($ancestorName));
			$r["$name getAncestorWithClassName $ancestorName again"] = $view($subject->getAncestorWithClassName($ancestorName));
			$r["$name isInstanceOf $ancestorName"] = $view($subject->isInstanceOf($ancestorName));
		}
		foreach (['toBoolean', 'toNumber', 'toInteger', 'toFloat', 'toString', 'toArray', 'toArrayKey', 'toBitwiseNotType', 'toAbsoluteNumber', 'toGetClassResultType', 'toObjectTypeForInstanceofCheck',
			'isTrue', 'isFalse', 'isBoolean', 'isScalar', 'isNull', 'isInteger', 'isFloat', 'isString', 'isNumericString', 'isDecimalIntegerString', 'isNonEmptyString', 'isNonFalsyString', 'isLiteralString', 'isLowercaseString', 'isUppercaseString', 'isClassString', 'isVoid',
			'isConstantValue', 'isConstantScalarValue', 'getConstantScalarTypes', 'getConstantScalarValues', 'getFiniteTypes', 'isObject', 'isEnum', 'getArrays', 'getConstantArrays', 'getConstantStrings', 'getReferencedClasses', 'getObjectClassNames', 'getObjectClassReflections',
			'getClassStringType', 'getClassStringObjectType', 'getObjectTypeOrClassStringObjectType', 'canAccessProperties', 'canCallMethods', 'canAccessConstants', 'isIterable', 'isIterableAtLeastOnce', 'getArraySize', 'getIterableKeyType', 'getFirstIterableKeyType', 'getLastIterableKeyType',
			'getIterableValueType', 'getFirstIterableValueType', 'getLastIterableValueType', 'isArray', 'isConstantArray', 'isOversizedArray', 'isList', 'isOffsetAccessible', 'isOffsetAccessLegal', 'getKeysArray', 'getValuesArray', 'flipArray', 'popArray', 'shiftArray', 'shuffleArray',
			'makeListMaybe', 'makeAllArrayKeysOptional', 'filterArrayRemovingFalsey', 'getEnumCases', 'getEnumCaseObject', 'isCallable', 'isCloneable', 'toPhpDocNode', 'getReferencedTemplateTypes', 'hasTemplateOrLateResolvableType',
			'getClassName', 'getClassReflection', 'getNakedClassReflection', 'getSubtractedType', 'getTypeWithoutSubtractedType', 'withoutFinalByKeywordOverride'] as $method) {
			if ($method === 'getReferencedTemplateTypes') {
				foreach ([\PHPStan\Type\Generic\TemplateTypeVariance::createInvariant(), \PHPStan\Type\Generic\TemplateTypeVariance::createCovariant(), \PHPStan\Type\Generic\TemplateTypeVariance::createContravariant()] as $vi => $variance) {
					$r["$name $method $vi"] = $ov($subject->$method($variance));
				}
				continue;
			}
			$r["$name $method"] = $attempt(static fn () => $subject->$method());
			$r["$name $method again"] = $attempt(static fn () => $subject->$method());
		}
		foreach (['getSmallerType', 'getSmallerOrEqualType', 'getGreaterType', 'getGreaterOrEqualType'] as $method) {
			$r["$name $method"] = $view($subject->$method($objectPhpVersions[1]));
		}
		foreach ([\PHPStan\Type\GeneralizePrecision::lessSpecific(), \PHPStan\Type\GeneralizePrecision::moreSpecific(), \PHPStan\Type\GeneralizePrecision::templateArgument()] as $i => $precision) {
			$r["$name generalize $i"] = $view($subject->generalize($precision));
		}
		$r["$name toCoercedArgumentType"] = [$view($subject->toCoercedArgumentType(true)), $view($subject->toCoercedArgumentType(false))];
		$r["$name toClassConstantType"] = $view($subject->toClassConstantType($stringReflectionProvider));
		$r["$name traverse identity"] = $subject->traverse(static fn ($t) => $t) === $subject;
		$r["$name traverse replaced"] = $view($subject->traverse(static fn ($t) => new $objectClass(\stdClass::class)));
		$r["$name traverse null"] = $attemptClass(static fn () => $subject->traverse(static fn ($t) => null));
		foreach ([[\Traversable::class, 'TKey'], [\Traversable::class, 'TValue'], [\Iterator::class, 'TValue'], [\ArrayIterator::class, 'TKey'], [\IteratorAggregate::class, 'TValue'], ['Foo', 'T'], [\ArrayIterator::class, 'Nope']] as [$ancestorClassName, $templateTypeName]) {
			$r["$name getTemplateType $ancestorClassName $templateTypeName"] = $view($subject->getTemplateType($ancestorClassName, $templateTypeName));
		}
		foreach (['name', 'value', 'x', 'message', 'storage'] as $propertyName) {
			$r["$name hasProperty $propertyName"] = $view($subject->hasProperty($propertyName));
			$r["$name hasInstanceProperty $propertyName"] = $view($subject->hasInstanceProperty($propertyName));
			$r["$name hasStaticProperty $propertyName"] = $view($subject->hasStaticProperty($propertyName));
			foreach (['out' => $outOfClassScope, 'in' => $inClassScope] as $scopeName => $scope) {
				foreach (['getProperty', 'getInstanceProperty', 'getStaticProperty', 'getUnresolvedPropertyPrototype', 'getUnresolvedInstancePropertyPrototype', 'getUnresolvedStaticPropertyPrototype'] as $method) {
					$r["$name $method $propertyName $scopeName"] = $attempt(static fn () => $subject->$method($propertyName, $scope));
					$r["$name $method $propertyName $scopeName again"] = $attempt(static fn () => $subject->$method($propertyName, $scope));
				}
			}
		}
		foreach (['count', 'current', 'key', 'getIterator', '__toString', '__invoke', 'offsetGet', 'offsetSet', 'x', 'yes', 'getMessage', 'cases', 'from'] as $methodName) {
			$r["$name hasMethod $methodName"] = $view($subject->hasMethod($methodName));
			foreach (['out' => $outOfClassScope, 'in' => $inClassScope] as $scopeName => $scope) {
				$r["$name getMethod $methodName $scopeName"] = $attempt(static fn () => $subject->getMethod($methodName, $scope));
				$r["$name getMethod $methodName $scopeName again"] = $attempt(static fn () => $subject->getMethod($methodName, $scope));
				$r["$name getUnresolvedMethodPrototype $methodName $scopeName"] = $attempt(static fn () => $subject->getUnresolvedMethodPrototype($methodName, $scope));
			}
		}
		foreach (['X', 'EQUAL_UNION_CLASSES', 'FOO', 'CASE_ONE'] as $constantName) {
			$r["$name hasConstant $constantName"] = $view($subject->hasConstant($constantName));
			$r["$name getConstant $constantName"] = $attempt(static fn () => $subject->getConstant($constantName));
		}
		foreach (['out' => $outOfClassScope, 'in' => $inClassScope] as $scopeName => $scope) {
			$r["$name getCallableParametersAcceptors $scopeName"] = $attempt(static fn () => $subject->getCallableParametersAcceptors($scope));
		}
		$r["$name mapValueType"] = $view($subject->mapValueType(static fn ($t) => $t));
		$r["$name changeKeyCaseArray"] = $view($subject->changeKeyCaseArray(null));
		if ($subject instanceof $genericClass) {
			$r["$name getTypes"] = $ov($subject->getTypes());
			$r["$name getVariances"] = $ov($subject->getVariances());
			$r["$name changeVariances"] = [$attemptClass(static fn () => $subject->changeVariances([\PHPStan\Type\Generic\TemplateTypeVariance::createContravariant()])), $view($subject->changeVariances([])), $subject->changeVariances([])->getVariances() === []];
		}
		if ($subject instanceof $caseClass) {
			$r["$name getEnumCaseName"] = $subject->getEnumCaseName();
			$r["$name getBackingValueType"] = $view($subject->getBackingValueType());
		}
		if ($subject instanceof \PHPStan\Type\Generic\TemplateType) {
			$r["$name template"] = [$subject->getName(), $view($subject->getBound()), $view($subject->getDefault()), $view($subject->toArgument()), $subject->isArgument()];
		}
	}
	// the family through the compound types and the combinator, the way the
	// analysis exercises it
	foreach (['stdClass', 'exception', 'throwable', 'throwableMinusException', 'enum', 'enumMinusFoo', 'genericArrayIterator', 'genericIteratorCovariant', 'caseFoo', 'caseBacked', 'templateObject', 'unknown'] as $name) {
		$subject = $subjects[$name];
		foreach (['stdClass', 'exception', 'runtimeException', 'throwable', 'iterator', 'enum', 'caseFoo', 'caseBar', 'unionCases', 'genericArrayIterator', 'genericIteratorCovariant', 'union', 'unionNullable', 'mixed', 'never', 'null', 'objectWithoutClass', 'intersection', 'static', 'templateObject'] as $otherName) {
			$other = $others[$otherName];
			$r["combinator union $name $otherName"] = $view(\PHPStan\Type\TypeCombinator::union($subject, $other));
			$r["combinator intersect $name $otherName"] = $view(\PHPStan\Type\TypeCombinator::intersect($subject, $other));
			$r["combinator remove $name $otherName"] = $view(\PHPStan\Type\TypeCombinator::remove($subject, $other));
			$r["combinator remove-reverse $name $otherName"] = $view(\PHPStan\Type\TypeCombinator::remove($other, $subject));
			$r["other isSuperTypeOf $name $otherName"] = $view($other->isSuperTypeOf($subject));
			$r["other accepts $name $otherName"] = $view($other->accepts($subject, true));
			$r["other equals $name $otherName"] = $other->equals($subject);
		}
		$r["combinator removeNull $name"] = $view(\PHPStan\Type\TypeCombinator::removeNull($subject));
		$r["combinator addNull $name"] = $view(\PHPStan\Type\TypeCombinator::addNull($subject));
	}
	// removing every case of an enum from its own type, one at a time
	$remaining = $subjects['enum'];
	foreach (['FOO', 'BAR', 'BAZ'] as $caseName) {
		$remaining = \PHPStan\Type\TypeCombinator::remove($remaining, new $caseClass($objectEnum, $caseName));
		$r["enum minus cases $caseName"] = $view($remaining);
	}
	$r['equal union classes'] = [$view($subjects['throwable']->tryRemove(new $objectClass(\Error::class))), $view($subjects['dateTimeInterface']->tryRemove(new $objectClass(\DateTime::class))), $view($subjects['dateTimeInterface']->tryRemove(new $objectClass(\DateTimeImmutable::class)))];
	$r['object equals object'] = [(new $objectClass(\stdClass::class))->equals(new $objectClass(\stdClass::class)), (new $objectClass(\stdClass::class))->equals(new $objectClass('stdclass')), (new $objectClass(\stdClass::class))->equals(new $genericClass(\stdClass::class, [])), (new $genericClass(\stdClass::class, []))->equals(new $objectClass(\stdClass::class)), (new $caseClass($objectEnum, 'FOO'))->equals(new $caseClass($objectEnum, 'FOO')), (new $caseClass($objectEnum, 'FOO'))->equals(new $objectClass($objectEnum))];
	// the constructors by named arguments
	$r['object named'] = $view(new $objectClass(className: \stdClass::class, classReflection: null));
	$r['object named subtracted'] = $view(new $objectClass(className: \Throwable::class, subtractedType: new $objectClass(\Exception::class)));
	$r['generic named'] = $view(new $genericClass(mainType: \ArrayIterator::class, types: [new \PHPStan\Type\IntegerType()], variances: [\PHPStan\Type\Generic\TemplateTypeVariance::createCovariant()]));
	$r['case named'] = $view(new $caseClass(className: $objectEnum, enumCaseName: 'BAZ'));
	// the readonly enum case name: a second constructor call fails
	try {
		$subjects['caseFoo']->__construct($objectEnum, 'BAR');
		$r['case reconstruct'] = 'no throw';
	} catch (\Error $e) {
		$r['case reconstruct'] = [get_class($e), $e->getMessage()];
	}
	// uninitialized instances: every typed-slot read raises the same Error
	foreach ([$objectClass, $genericClass, $caseClass] as $uninitializedClass) {
		$uninitialized = (new \ReflectionClass($uninitializedClass))->newInstanceWithoutConstructor();
		foreach (['describe' => [\PHPStan\Type\VerbosityLevel::precise()], 'describeTypeOnly' => [\PHPStan\Type\VerbosityLevel::typeOnly()], 'isSuperTypeOf' => [$others['int']], 'equals' => [$uninitialized], 'getClassName' => [], 'getSubtractedType' => [], 'getReferencedClasses' => [], 'getTypes' => [], 'getVariances' => [], 'getEnumCaseName' => [], 'toPhpDocNode' => [], 'hasTemplateOrLateResolvableType' => [], 'traverse' => [static fn ($t) => $t]] as $method => $args) {
			$realMethod = $method === 'describeTypeOnly' ? 'describe' : $method;
			if (!method_exists($uninitialized, $realMethod)) {
				continue;
			}
			try {
				$r["uninitialized $uninitializedClass $method"] = $view($uninitialized->$realMethod(...$args));
			} catch (\Error $e) {
				$r["uninitialized $uninitializedClass $method"] = [get_class($e), $e->getMessage()];
			}
		}
	}
	// PHP subclasses overriding what the natives call through $this
	$anonymousObject = new class (\Exception::class) extends \PHPStan\Type\ObjectType {

		public function getClassReflection(): ?\PHPStan\Reflection\ClassReflection
		{
			return null;
		}

		protected function describeAdditionalCacheKey(): string
		{
			return '<anonymous>';
		}

		public function isInstanceOf(string $className): \PHPStan\TrinaryLogic
		{
			return \PHPStan\TrinaryLogic::createYes();
		}

	};
	$r['anonymous object describe'] = [$anonymousObject->describe(\PHPStan\Type\VerbosityLevel::precise()), $anonymousObject->describe(\PHPStan\Type\VerbosityLevel::cache())];
	$r['anonymous object hasMethod'] = $view($anonymousObject->hasMethod('getMessage'));
	$r['anonymous object toNumber'] = $view($anonymousObject->toNumber());
	$r['anonymous object toBoolean'] = $view($anonymousObject->toBoolean());
	$r['anonymous object isIterable'] = $view($anonymousObject->isIterable());
	$r['anonymous object getArraySize'] = $view($anonymousObject->getArraySize());
	$r['anonymous object accepts'] = [$view($anonymousObject->accepts($others['closureType'], true)), $view($anonymousObject->accepts($others['runtimeException'], true))];
	$r['anonymous object isSuperTypeOf'] = [$view($anonymousObject->isSuperTypeOf($others['runtimeException'])), $view($subjects['exception']->isSuperTypeOf($anonymousObject)), $view($subjects['throwable']->isSuperTypeOf($anonymousObject))];
	$r['anonymous object getAncestorWithClassName'] = $view($anonymousObject->getAncestorWithClassName(\Throwable::class));
	$r['anonymous object getMethod'] = $attempt(static fn () => $anonymousObject->getMethod('getMessage', $outOfClassScope));
	$r['anonymous object equals'] = [$anonymousObject->equals(new $objectClass(\Exception::class)), (new $objectClass(\Exception::class))->equals($anonymousObject)];
	$anonymousGeneric = new class (\ArrayIterator::class, [new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]) extends \PHPStan\Type\Generic\GenericObjectType {

		public function getTypes(): array
		{
			return [new \PHPStan\Type\FloatType(), new \PHPStan\Type\FloatType()];
		}

		protected function recreate(string $className, array $types, ?\PHPStan\Type\Type $subtractedType, array $variances = []): \PHPStan\Type\Generic\GenericObjectType
		{
			return new \PHPStan\Type\Generic\GenericObjectType(\Iterator::class, $types, $subtractedType, null, $variances);
		}

	};
	$r['anonymous generic describe'] = [$anonymousGeneric->describe(\PHPStan\Type\VerbosityLevel::precise()), $anonymousGeneric->describe(\PHPStan\Type\VerbosityLevel::cache())];
	$r['anonymous generic traverse'] = $view($anonymousGeneric->traverse(static fn ($t) => new \PHPStan\Type\MixedType()));
	$r['anonymous generic changeVariances'] = $view($anonymousGeneric->changeVariances([\PHPStan\Type\Generic\TemplateTypeVariance::createCovariant()]));
	$r['anonymous generic inferTemplateTypes'] = $view($anonymousGeneric->inferTemplateTypes($subjects['genericArrayIterator']));
	$r['anonymous generic isSuperTypeOf'] = [$view($anonymousGeneric->isSuperTypeOf($subjects['genericArrayIterator'])), $view($subjects['genericArrayIterator']->isSuperTypeOf($anonymousGeneric))];
	$r['anonymous generic equals'] = [$anonymousGeneric->equals($subjects['genericArrayIterator']), $subjects['genericArrayIterator']->equals($anonymousGeneric)];
	$anonymousCase = new class ($objectEnum, 'FOO') extends \PHPStan\Type\Enum\EnumCaseObjectType {

		public function getEnumCaseName(): string
		{
			return 'OVERRIDDEN';
		}

		public function getSubtractedType(): ?\PHPStan\Type\Type
		{
			return new \PHPStan\Type\Enum\EnumCaseObjectType(\ObjectTypeEnums\FooEnum::class, 'BAR');
		}

	};
	$r['anonymous case describe'] = [$anonymousCase->describe(\PHPStan\Type\VerbosityLevel::precise()), $anonymousCase->describe(\PHPStan\Type\VerbosityLevel::cache())];
	$r['anonymous case toPhpDocNode'] = $view($anonymousCase->toPhpDocNode());
	$r['anonymous case isSuperTypeOf'] = [$view($anonymousCase->isSuperTypeOf($subjects['caseFoo'])), $view($anonymousCase->isSuperTypeOf($subjects['enum'])), $view($subjects['caseFoo']->isSuperTypeOf($anonymousCase)), $view($subjects['enum']->isSuperTypeOf($anonymousCase))];
	$r['anonymous case equals'] = [$anonymousCase->equals($subjects['caseFoo']), $subjects['caseFoo']->equals($anonymousCase)];
	$r['anonymous case getEnumCases'] = $view($anonymousCase->getEnumCases());
	// the static caches reset: the answers must not change
	$objectClass::resetCaches();
	$r['after reset isSuperTypeOf'] = $view($subjects['throwable']->isSuperTypeOf($others['exception']));
	$r['after reset getMethod'] = $attempt(static fn () => $subjects['trinary']->getMethod('yes', $outOfClassScope));
	$r['after reset getEnumCases'] = $view($subjects['enum']->getEnumCases());
	$r['after reset getAncestorWithClassName'] = $view((new $objectClass(\ArrayIterator::class))->getAncestorWithClassName(\Traversable::class));
	foreach ($r as $key => $value) {
		$observations["object $key"] = $value;
	}
}


// ---- IterableType / CallableType / ClosureType ----
// the reflection provider and PhpVersion accessors stay registered from the
// string section (the callable queries over strings and constant arrays
// consult them); the PHP TemplateIterableType over the native IterableType
// and anonymous subclasses overriding what the natives call through $this
// come along
$callablePhpVersions = [new \PHPStan\Php\PhpVersion(70400), new \PHPStan\Php\PhpVersion(80400)];
$callableTemplateScope = \PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('callableFoo');
$callableT = \PHPStan\Type\Generic\TemplateTypeFactory::create($callableTemplateScope, 'T', new \PHPStan\Type\ObjectType(\Countable::class), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
$callableU = \PHPStan\Type\Generic\TemplateTypeFactory::create($callableTemplateScope, 'U', null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
$callableParam = static fn (string $name, \PHPStan\Type\Type $type, bool $optional = false, ?\PHPStan\Reflection\PassedByReference $byRef = null, bool $variadic = false, ?\PHPStan\Type\Type $default = null): \PHPStan\Reflection\Native\NativeParameterReflection => new \PHPStan\Reflection\Native\NativeParameterReflection($name, $optional, $type, $byRef ?? \PHPStan\Reflection\PassedByReference::createNo(), $variadic, $default);
$callableAssertTag = static fn (string $if, string $parameter, \PHPStan\Type\Type $type, bool $negated = false): \PHPStan\PhpDoc\Tag\AssertTag => new \PHPStan\PhpDoc\Tag\AssertTag($if, $type, new \PHPStan\PhpDoc\Tag\AssertTagParameter($parameter, null, null), $negated, false, true);
$callableAssertions = \PHPStan\Reflection\Assertions::createFromAssertTags([$callableAssertTag(\PHPStan\PhpDoc\Tag\AssertTag::NULL, '$a', new \PHPStan\Type\StringType())]);
$callableAssertionsIfTrue = \PHPStan\Reflection\Assertions::createFromAssertTags([$callableAssertTag(\PHPStan\PhpDoc\Tag\AssertTag::IF_TRUE, '$a', new \PHPStan\Type\ObjectType(\Countable::class))]);
$callableAssertionsTemplate = \PHPStan\Reflection\Assertions::createFromAssertTags([$callableAssertTag(\PHPStan\PhpDoc\Tag\AssertTag::IF_TRUE, '$a', $callableT)]);
$callableTemplateTags = [
	'T' => new \PHPStan\PhpDoc\Tag\TemplateTag('T', new \PHPStan\Type\ObjectType(\Countable::class), null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
	'U' => new \PHPStan\PhpDoc\Tag\TemplateTag('U', new \PHPStan\Type\MixedType(), new \PHPStan\Type\IntegerType(), \PHPStan\Type\Generic\TemplateTypeVariance::createCovariant()),
];
$callableTemplateTypeMap = new \PHPStan\Type\Generic\TemplateTypeMap(['T' => $callableT, 'U' => $callableU]);
$callableResolvedTemplateTypeMap = new \PHPStan\Type\Generic\TemplateTypeMap(['T' => new \PHPStan\Type\ObjectType(\ArrayIterator::class)]);
$callableCallSiteVarianceMap = new \PHPStan\Type\Generic\TemplateTypeVarianceMap(['T' => \PHPStan\Type\Generic\TemplateTypeVariance::createCovariant()]);
$callableSubjects = static fn (): array => [
	'callable' => new \PHPStan\Type\CallableType(),
	'callablePure' => new \PHPStan\Type\CallableType(isPure: \PHPStan\TrinaryLogic::createYes()),
	'callableImpure' => new \PHPStan\Type\CallableType(isPure: \PHPStan\TrinaryLogic::createNo()),
	'callableEmpty' => new \PHPStan\Type\CallableType([], new \PHPStan\Type\VoidType()),
	'callableNullParams' => new \PHPStan\Type\CallableType(null, new \PHPStan\Type\StringType()),
	'callableParams' => new \PHPStan\Type\CallableType([$callableParam('a', new \PHPStan\Type\IntegerType()), $callableParam('b', new \PHPStan\Type\StringType(), true, null, false, new \PHPStan\Type\Constant\ConstantStringType('x')), $callableParam('c', new \PHPStan\Type\FloatType(), true, null, true)], new \PHPStan\Type\StringType()),
	'callableParamsCopy' => new \PHPStan\Type\CallableType([$callableParam('a', new \PHPStan\Type\IntegerType()), $callableParam('b', new \PHPStan\Type\StringType(), true, null, false, new \PHPStan\Type\Constant\ConstantStringType('x')), $callableParam('c', new \PHPStan\Type\FloatType(), true, null, true)], new \PHPStan\Type\StringType()),
	'callableParamsOtherDefault' => new \PHPStan\Type\CallableType([$callableParam('a', new \PHPStan\Type\IntegerType()), $callableParam('b', new \PHPStan\Type\StringType(), true, null, false, new \PHPStan\Type\Constant\ConstantStringType('y'))], new \PHPStan\Type\StringType()),
	'callableParamsNoDefault' => new \PHPStan\Type\CallableType([$callableParam('a', new \PHPStan\Type\IntegerType()), $callableParam('b', new \PHPStan\Type\StringType(), true)], new \PHPStan\Type\StringType()),
	'callableNonVariadic' => new \PHPStan\Type\CallableType([$callableParam('a', new \PHPStan\Type\IntegerType())], new \PHPStan\Type\IntegerType(), false),
	'callableByRef' => new \PHPStan\Type\CallableType([$callableParam('a', new \PHPStan\Type\IntegerType(), false, \PHPStan\Reflection\PassedByReference::createCreatesNewVariable())], new \PHPStan\Type\NullType()),
	'callableNoNames' => new \PHPStan\Type\CallableType([$callableParam('', new \PHPStan\Type\IntegerType())], new \PHPStan\Type\MixedType()),
	'callableTemplate' => new \PHPStan\Type\CallableType([$callableParam('a', $callableT), $callableParam('b', $callableU)], $callableT, true, $callableTemplateTypeMap, $callableResolvedTemplateTypeMap, $callableTemplateTags),
	'callableTemplateTagsOnly' => new \PHPStan\Type\CallableType([$callableParam('a', new \PHPStan\Type\IntegerType())], new \PHPStan\Type\IntegerType(), true, null, null, ['T' => $callableTemplateTags['T']]),
	'callableAsserts' => new \PHPStan\Type\CallableType([$callableParam('a', new \PHPStan\Type\MixedType())], new \PHPStan\Type\BooleanType(), true, null, null, [], null, $callableAssertionsIfTrue),
	'callableAssertsPlain' => new \PHPStan\Type\CallableType([$callableParam('a', new \PHPStan\Type\MixedType())], new \PHPStan\Type\BooleanType(), true, null, null, [], null, $callableAssertions),
	'callableAssertsTemplate' => new \PHPStan\Type\CallableType([$callableParam('a', new \PHPStan\Type\MixedType())], new \PHPStan\Type\BooleanType(), true, $callableTemplateTypeMap, null, $callableTemplateTags, null, $callableAssertionsTemplate),
	'callablePureParams' => new \PHPStan\Type\CallableType([$callableParam('a', new \PHPStan\Type\IntegerType())], new \PHPStan\Type\IntegerType(), true, null, null, [], \PHPStan\TrinaryLogic::createYes()),
	'closure' => new \PHPStan\Type\ClosureType(),
	'closurePure' => \PHPStan\Type\ClosureType::createPure(),
	'closureStatic' => new \PHPStan\Type\ClosureType(isStatic: \PHPStan\TrinaryLogic::createYes()),
	'closureStaticPure' => new \PHPStan\Type\ClosureType(impurePoints: [], isStatic: \PHPStan\TrinaryLogic::createYes()),
	'closureEmpty' => new \PHPStan\Type\ClosureType([], new \PHPStan\Type\VoidType(), impurePoints: []),
	'closureNullParams' => new \PHPStan\Type\ClosureType(null, new \PHPStan\Type\StringType()),
	'closureParams' => new \PHPStan\Type\ClosureType([$callableParam('a', new \PHPStan\Type\IntegerType()), $callableParam('b', new \PHPStan\Type\StringType(), true, null, false, new \PHPStan\Type\Constant\ConstantStringType('x')), $callableParam('c', new \PHPStan\Type\FloatType(), true, null, true)], new \PHPStan\Type\StringType(), true, $callableTemplateTypeMap, $callableResolvedTemplateTypeMap, $callableCallSiteVarianceMap, $callableTemplateTags, [\PHPStan\Reflection\Callables\SimpleThrowPoint::createImplicit(), \PHPStan\Reflection\Callables\SimpleThrowPoint::createExplicit(new \PHPStan\Type\ObjectType(\RuntimeException::class), false)], [new \PHPStan\Reflection\Callables\SimpleImpurePoint('functionCall', 'call to a callable', false)], [new \PHPStan\Node\InvalidateExprNode(new \PhpParser\Node\Expr\Variable('a'))], ['a', 'b'], \PHPStan\TrinaryLogic::createNo(), \PHPStan\TrinaryLogic::createYes(), $callableAssertionsIfTrue, \PHPStan\TrinaryLogic::createNo()),
	'closureParamsCopy' => new \PHPStan\Type\ClosureType([$callableParam('a', new \PHPStan\Type\IntegerType()), $callableParam('b', new \PHPStan\Type\StringType(), true, null, false, new \PHPStan\Type\Constant\ConstantStringType('x')), $callableParam('c', new \PHPStan\Type\FloatType(), true, null, true)], new \PHPStan\Type\StringType()),
	'closureCertainImpure' => new \PHPStan\Type\ClosureType([$callableParam('a', new \PHPStan\Type\IntegerType())], new \PHPStan\Type\VoidType(), impurePoints: [new \PHPStan\Reflection\Callables\SimpleImpurePoint('functionCall', 'certain', true), new \PHPStan\Reflection\Callables\SimpleImpurePoint('propertyAssign', 'uncertain', false)]),
	'closureStaticParams' => new \PHPStan\Type\ClosureType([$callableParam('a', new \PHPStan\Type\IntegerType())], new \PHPStan\Type\IntegerType(), isStatic: \PHPStan\TrinaryLogic::createYes()),
	'closureStaticParamsPure' => new \PHPStan\Type\ClosureType([$callableParam('a', new \PHPStan\Type\IntegerType())], new \PHPStan\Type\IntegerType(), impurePoints: [], isStatic: \PHPStan\TrinaryLogic::createYes()),
	'closureNonVariadic' => new \PHPStan\Type\ClosureType([$callableParam('a', new \PHPStan\Type\IntegerType())], new \PHPStan\Type\IntegerType(), false),
	'closureByRef' => new \PHPStan\Type\ClosureType([$callableParam('a', new \PHPStan\Type\IntegerType(), false, \PHPStan\Reflection\PassedByReference::createCreatesNewVariable())], new \PHPStan\Type\NullType()),
	'closureTemplate' => new \PHPStan\Type\ClosureType([$callableParam('a', $callableT), $callableParam('b', $callableU)], $callableT, true, $callableTemplateTypeMap, $callableResolvedTemplateTypeMap, null, $callableTemplateTags),
	'closureAsserts' => new \PHPStan\Type\ClosureType([$callableParam('a', new \PHPStan\Type\MixedType())], new \PHPStan\Type\BooleanType(), assertions: $callableAssertionsIfTrue),
	'closureAssertsTemplate' => new \PHPStan\Type\ClosureType([$callableParam('a', new \PHPStan\Type\MixedType())], new \PHPStan\Type\BooleanType(), true, $callableTemplateTypeMap, null, null, $callableTemplateTags, [], null, [], [], null, null, $callableAssertionsTemplate),
	'iterable' => new \PHPStan\Type\IterableType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()),
	'iterableExplicit' => new \PHPStan\Type\IterableType(new \PHPStan\Type\MixedType(true), new \PHPStan\Type\MixedType(true)),
	'iterableIntString' => new \PHPStan\Type\IterableType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()),
	'iterableIntStringCopy' => new \PHPStan\Type\IterableType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()),
	'iterableMixedString' => new \PHPStan\Type\IterableType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\StringType()),
	'iterableStringMixed' => new \PHPStan\Type\IterableType(new \PHPStan\Type\StringType(), new \PHPStan\Type\MixedType()),
	'iterableMixedMinusNull' => new \PHPStan\Type\IterableType(new \PHPStan\Type\MixedType(false, new \PHPStan\Type\NullType()), new \PHPStan\Type\MixedType()),
	'iterableTemplate' => new \PHPStan\Type\IterableType(new \PHPStan\Type\IntegerType(), $callableT),
	'iterableTemplateMixed' => new \PHPStan\Type\IterableType($callableU, $callableU),
	'iterableNever' => new \PHPStan\Type\IterableType(new \PHPStan\Type\NeverType(), new \PHPStan\Type\NeverType()),
	'iterableObjectKey' => new \PHPStan\Type\IterableType(new \PHPStan\Type\ObjectType(\stdClass::class), new \PHPStan\Type\IntegerType()),
	'templateIterable' => \PHPStan\Type\Generic\TemplateTypeFactory::create($callableTemplateScope, 'I', new \PHPStan\Type\IterableType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
];
$callableOthers = static fn (): array => [
	'string' => new \PHPStan\Type\StringType(),
	'stringStrlen' => new \PHPStan\Type\Constant\ConstantStringType('strlen'),
	'stringNope' => new \PHPStan\Type\Constant\ConstantStringType('nope'),
	'stringStaticMethod' => new \PHPStan\Type\Constant\ConstantStringType(\PHPStan\TrinaryLogic::class . '::createYes'),
	'classString' => new \PHPStan\Type\ClassStringType(),
	'nonEmptyString' => new \PHPStan\Type\IntersectionType([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryNonEmptyStringType()]),
	'int' => new \PHPStan\Type\IntegerType(),
	'int1' => new \PHPStan\Type\Constant\ConstantIntegerType(1),
	'float' => new \PHPStan\Type\FloatType(),
	'bool' => new \PHPStan\Type\BooleanType(),
	'true' => new \PHPStan\Type\Constant\ConstantBooleanType(true),
	'mixed' => new \PHPStan\Type\MixedType(),
	'explicitMixed' => new \PHPStan\Type\MixedType(true),
	'mixedMinusCallable' => new \PHPStan\Type\MixedType(false, new \PHPStan\Type\CallableType()),
	'mixedMinusIterable' => new \PHPStan\Type\MixedType(false, new \PHPStan\Type\IterableType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType())),
	'null' => new \PHPStan\Type\NullType(),
	'never' => new \PHPStan\Type\NeverType(),
	'array' => new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()),
	'arrayIntString' => new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()),
	'arrayStringInt' => new \PHPStan\Type\ArrayType(new \PHPStan\Type\StringType(), new \PHPStan\Type\IntegerType()),
	'listOfInt' => new \PHPStan\Type\IntersectionType([new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\IntegerType()), new \PHPStan\Type\Accessory\AccessoryArrayListType()]),
	'emptyArray' => new \PHPStan\Type\Constant\ConstantArrayType([], []),
	'constantArrayInts' => new \PHPStan\Type\Constant\ConstantArrayType([new \PHPStan\Type\Constant\ConstantIntegerType(0), new \PHPStan\Type\Constant\ConstantIntegerType(1)], [new \PHPStan\Type\Constant\ConstantIntegerType(1), new \PHPStan\Type\Constant\ConstantIntegerType(2)]),
	'constantArrayCallable' => new \PHPStan\Type\Constant\ConstantArrayType([new \PHPStan\Type\Constant\ConstantIntegerType(0), new \PHPStan\Type\Constant\ConstantIntegerType(1)], [new \PHPStan\Type\Constant\ConstantStringType(\PHPStan\TrinaryLogic::class), new \PHPStan\Type\Constant\ConstantStringType('createYes')]),
	'object' => new \PHPStan\Type\ObjectType(\stdClass::class),
	'objectClosure' => new \PHPStan\Type\ObjectType(\Closure::class),
	'objectTrinary' => new \PHPStan\Type\ObjectType(\PHPStan\TrinaryLogic::class),
	'objectTraversable' => new \PHPStan\Type\ObjectType(\Traversable::class),
	'objectIterator' => new \PHPStan\Type\ObjectType(\Iterator::class),
	'objectArrayIterator' => new \PHPStan\Type\ObjectType(\ArrayIterator::class),
	'objectCountable' => new \PHPStan\Type\ObjectType(\Countable::class),
	'genericTraversable' => new \PHPStan\Type\Generic\GenericObjectType(\Traversable::class, [new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]),
	'genericTraversableMixed' => new \PHPStan\Type\Generic\GenericObjectType(\Traversable::class, [new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()]),
	'genericIterator' => new \PHPStan\Type\Generic\GenericObjectType(\Iterator::class, [new \PHPStan\Type\StringType(), new \PHPStan\Type\IntegerType()]),
	'objectWithoutClass' => new \PHPStan\Type\ObjectWithoutClassType(),
	'unionCallableNull' => new \PHPStan\Type\UnionType([new \PHPStan\Type\CallableType(), new \PHPStan\Type\NullType()]),
	'unionArrayTraversable' => new \PHPStan\Type\UnionType([new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()), new \PHPStan\Type\ObjectType(\Traversable::class)]),
	'unionClosures' => new \PHPStan\Type\UnionType([new \PHPStan\Type\ClosureType([$callableParam('a', new \PHPStan\Type\IntegerType())], new \PHPStan\Type\IntegerType()), new \PHPStan\Type\ClosureType([$callableParam('a', new \PHPStan\Type\StringType())], new \PHPStan\Type\StringType())]),
	'intersectionCountableTraversable' => new \PHPStan\Type\IntersectionType([new \PHPStan\Type\ObjectType(\Countable::class), new \PHPStan\Type\ObjectType(\Traversable::class)]),
	'templateMixed' => $callableU,
	'templateCountable' => $callableT,
	'static' => new \PHPStan\Type\StaticType($stringReflectionProvider->getClass(\PHPStan\TrinaryLogic::class)),
	'range' => \PHPStan\Type\IntegerRangeType::fromInterval(0, 10),
	'callableIntToInt' => new \PHPStan\Type\CallableType([$callableParam('x', new \PHPStan\Type\IntegerType())], new \PHPStan\Type\IntegerType()),
	'callableMixedToMixed' => new \PHPStan\Type\CallableType([$callableParam('x', new \PHPStan\Type\MixedType())], new \PHPStan\Type\MixedType()),
	'closureIntToInt' => new \PHPStan\Type\ClosureType([$callableParam('x', new \PHPStan\Type\IntegerType())], new \PHPStan\Type\IntegerType()),
	'closureStringToString' => new \PHPStan\Type\ClosureType([$callableParam('x', new \PHPStan\Type\StringType())], new \PHPStan\Type\StringType()),
	'closureNoParamsInt' => new \PHPStan\Type\ClosureType([], new \PHPStan\Type\IntegerType()),
];
{
	$r = [];
	$subjects = $callableSubjects();
	$others = $callableOthers() + $callableSubjects();
	$outOfClassScope = new \PHPStan\Analyser\OutOfClassScope();
	// the richer view of the family's values: parameters, acceptors, tags,
	// throw and impure points, template maps and references, reflections
	$cv = static function (mixed $v) use (&$cv, $view): mixed {
		if ($v instanceof \PHPStan\Reflection\ParameterReflection) {
			return ['parameter', $v->getName(), $view($v->getType()), $v->isOptional(), $v->isVariadic(), $v->passedByReference()->no(), $v->passedByReference()->createsNewVariable(), $view($v->getDefaultValue())];
		}
		if ($v instanceof \PHPStan\Reflection\Callables\SimpleThrowPoint) {
			return ['throwPoint', $view($v->getType()), $v->isExplicit(), $v->canContainAnyThrowable()];
		}
		if ($v instanceof \PHPStan\Reflection\Callables\SimpleImpurePoint) {
			return ['impurePoint', $v->getIdentifier(), $v->getDescription(), $v->isCertain()];
		}
		if ($v instanceof \PHPStan\Node\InvalidateExprNode) {
			return ['invalidate', get_class($v->getExpr())];
		}
		if ($v instanceof \PHPStan\PhpDoc\Tag\TemplateTag) {
			return ['templateTag', $v->getName(), $view($v->getBound()), $view($v->getDefault()), $v->getVariance()->describe()];
		}
		if ($v instanceof \PHPStan\PhpDoc\Tag\AssertTag) {
			return ['assertTag', $v->getIf(), $v->getParameter()->describe(), $view($v->getType()), $v->isNegated(), $v->isEquality()];
		}
		if ($v instanceof \PHPStan\Reflection\Assertions) {
			return ['assertions', array_map($cv, $v->getAll())];
		}
		if ($v instanceof \PHPStan\Type\Generic\TemplateTypeMap) {
			return ['templateTypeMap', array_map($view, $v->getTypes())];
		}
		if ($v instanceof \PHPStan\Type\Generic\TemplateTypeVarianceMap) {
			return ['varianceMap', array_map(static fn (\PHPStan\Type\Generic\TemplateTypeVariance $variance): string => $variance->describe(), $v->getVariances())];
		}
		if ($v instanceof \PHPStan\Type\Generic\TemplateTypeReference) {
			return ['reference', $v->getType()->getName(), $v->getPositionVariance()->describe()];
		}
		if ($v instanceof \PHPStan\Reflection\Callables\CallableParametersAcceptor && !$v instanceof \PHPStan\Type\Type) {
			return ['acceptor', get_class($v), array_map($cv, $v->getParameters()), $view($v->getReturnType()), $v->isVariadic()];
		}
		if ($v instanceof \PHPStan\Reflection\ExtendedMethodReflection) {
			return ['method', get_class($v), $v->getName(), $v->getDeclaringClass()->getName(), array_map(static fn (\PHPStan\Reflection\ExtendedParametersAcceptor $variant): array => [array_map($cv, $variant->getParameters()), $view($variant->getReturnType())], $v->getVariants())];
		}
		if ($v instanceof \PHPStan\Reflection\ExtendedPropertyReflection) {
			return ['property', get_class($v), $v->getDeclaringClass()->getName(), $view($v->getReadableType())];
		}
		if ($v instanceof \PHPStan\Reflection\ClassConstantReflection) {
			return ['constant', get_class($v), $v->getName()];
		}
		if ($v instanceof \PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection || $v instanceof \PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection) {
			return ['prototype', get_class($v)];
		}
		if ($v instanceof \PHPStan\Reflection\ClassReflection) {
			return ['classReflection', $v->getName()];
		}
		if (is_array($v)) {
			return array_map($cv, $v);
		}
		return $view($v);
	};
	$attempt = static function (callable $probe) use ($cv): mixed {
		try {
			return $cv($probe());
		} catch (\Throwable $e) {
			return [get_class($e), $e->getMessage()];
		}
	};
	$levels = ['typeOnly' => \PHPStan\Type\VerbosityLevel::typeOnly(), 'value' => \PHPStan\Type\VerbosityLevel::value(), 'precise' => \PHPStan\Type\VerbosityLevel::precise(), 'cache' => \PHPStan\Type\VerbosityLevel::cache()];
	$variances = ['invariant' => \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant(), 'covariant' => \PHPStan\Type\Generic\TemplateTypeVariance::createCovariant(), 'contravariant' => \PHPStan\Type\Generic\TemplateTypeVariance::createContravariant(), 'static' => \PHPStan\Type\Generic\TemplateTypeVariance::createStatic()];
	$precisions = ['lessSpecific' => \PHPStan\Type\GeneralizePrecision::lessSpecific(), 'moreSpecific' => \PHPStan\Type\GeneralizePrecision::moreSpecific(), 'templateArgument' => \PHPStan\Type\GeneralizePrecision::templateArgument()];
	$compareOthers = ['int', 'null', 'mixed', 'object', 'callable', 'closure', 'iterable', 'array'];
	$unionCb = static fn (\PHPStan\Type\Type $a, \PHPStan\Type\Type $b): \PHPStan\Type\Type => \PHPStan\Type\TypeCombinator::union($a, $b);
	$toMixedCb = static fn (\PHPStan\Type\Type $t): \PHPStan\Type\Type => new \PHPStan\Type\MixedType();
	$identityCb = static fn (\PHPStan\Type\Type $t): \PHPStan\Type\Type => $t;
	$generalizeCb = static fn (\PHPStan\Type\Type $t): \PHPStan\Type\Type => $t->generalize(\PHPStan\Type\GeneralizePrecision::lessSpecific());
	foreach ($subjects as $name => $subject) {
		$r["$name instanceof"] = [$subject instanceof \PHPStan\Type\Type, $subject instanceof \PHPStan\Type\CompoundType, $subject instanceof \PHPStan\Reflection\Callables\CallableParametersAcceptor, $subject instanceof \PHPStan\Type\TypeWithClassName, $subject instanceof \PHPStan\Type\CallableType, $subject instanceof \PHPStan\Type\ClosureType, $subject instanceof \PHPStan\Type\IterableType, $subject instanceof \PHPStan\Type\Generic\TemplateType, get_class($subject)];
		foreach ($levels as $levelName => $level) {
			$r["$name describe $levelName"] = $subject->describe($level);
		}
		$r["$name toPhpDocNode"] = $attempt(static fn () => $subject->toPhpDocNode());
		foreach ($others as $otherName => $other) {
			$r["$name isSuperTypeOf $otherName"] = $attempt(static fn () => $subject->isSuperTypeOf($other));
			$r["$name accepts $otherName"] = [$attempt(static fn () => $subject->accepts($other, true)), $attempt(static fn () => $subject->accepts($other, false))];
			$r["$name equals $otherName"] = $subject->equals($other);
			$r["$name reverse isSuperTypeOf $otherName"] = $attempt(static fn () => $other->isSuperTypeOf($subject));
			$r["$name reverse accepts $otherName"] = $attempt(static fn () => $other->accepts($subject, true));
			$r["$name reverse equals $otherName"] = $other->equals($subject);
			$r["$name inferTemplateTypes $otherName"] = $attempt(static fn () => $subject->inferTemplateTypes($other));
			$r["$name tryRemove $otherName"] = $attempt(static fn () => $subject->tryRemove($other));
			$r["$name traverseSimultaneously $otherName"] = $attempt(static fn () => $subject->traverseSimultaneously($other, $unionCb));
			$r["$name hasOffsetValueType $otherName"] = $attempt(static fn () => $subject->hasOffsetValueType($other));
			if ($subject instanceof \PHPStan\Type\CompoundType) {
				$r["$name isSubTypeOf $otherName"] = $attempt(static fn () => $subject->isSubTypeOf($other));
				$r["$name isAcceptedBy $otherName"] = $attempt(static fn () => $subject->isAcceptedBy($other, true));
			}
			if ($subject instanceof \PHPStan\Type\IterableType) {
				$r["$name isSuperTypeOfMixed $otherName"] = $attempt(static fn () => $subject->isSuperTypeOfMixed($other));
			}
		}
		foreach ($compareOthers as $otherName) {
			$other = $others[$otherName];
			foreach ($callablePhpVersions as $phpVersion) {
				$v = $phpVersion->getVersionId();
				$r["$name looseCompare $otherName $v"] = $cv($subject->looseCompare($other, $phpVersion));
				if ($subject instanceof \PHPStan\Type\CompoundType) {
					$r["$name isGreaterThan $otherName $v"] = $cv($subject->isGreaterThan($other, $phpVersion));
					$r["$name isGreaterThanOrEqual $otherName $v"] = $cv($subject->isGreaterThanOrEqual($other, $phpVersion));
				}
				$r["$name isSmallerThan $otherName $v"] = $cv($subject->isSmallerThan($other, $phpVersion));
				$r["$name isSmallerThanOrEqual $otherName $v"] = $cv($subject->isSmallerThanOrEqual($other, $phpVersion));
			}
		}
		foreach ($variances as $varianceName => $variance) {
			$r["$name getReferencedTemplateTypes $varianceName"] = $attempt(static fn () => $subject->getReferencedTemplateTypes($variance));
		}
		foreach ($precisions as $precisionName => $precision) {
			$r["$name generalize $precisionName"] = $attempt(static fn () => $subject->generalize($precision));
		}
		$r["$name traverse toMixed"] = $attempt(static fn () => $subject->traverse($toMixedCb));
		$r["$name traverse identity"] = $attempt(static fn () => [$subject->traverse($identityCb) === $subject, $cv($subject->traverse($identityCb))]);
		$r["$name traverse generalize"] = $attempt(static fn () => $subject->traverse($generalizeCb));
		$r["$name getReferencedClasses"] = $attempt(static fn () => $subject->getReferencedClasses());
		$r["$name getObjectClassNames"] = $subject->getObjectClassNames();
		$r["$name getObjectClassReflections"] = $cv($subject->getObjectClassReflections());
		$r["$name getConstantStrings"] = $cv($subject->getConstantStrings());
		$r["$name hasTemplateOrLateResolvableType"] = $subject->hasTemplateOrLateResolvableType();
		foreach (['isNull', 'isConstantValue', 'isConstantScalarValue', 'isTrue', 'isFalse', 'isBoolean', 'isFloat', 'isInteger', 'isString', 'isNumericString', 'isDecimalIntegerString', 'isNonEmptyString', 'isNonFalsyString', 'isLiteralString', 'isLowercaseString', 'isUppercaseString', 'isClassString', 'isVoid', 'isScalar', 'isObject', 'isEnum', 'isIterable', 'isIterableAtLeastOnce', 'isArray', 'isConstantArray', 'isOversizedArray', 'isList', 'isOffsetAccessible', 'isOffsetAccessLegal', 'isCloneable', 'canAccessProperties', 'canCallMethods', 'canAccessConstants', 'isCallable'] as $trinaryMethod) {
			$r["$name $trinaryMethod"] = $cv($subject->$trinaryMethod());
		}
		foreach (['toNumber', 'toString', 'toInteger', 'toFloat', 'toAbsoluteNumber', 'toBitwiseNotType', 'toArray', 'toArrayKey', 'toBoolean', 'toGetClassResultType', 'toObjectTypeForInstanceofCheck', 'getEnumCases', 'getEnumCaseObject', 'getFiniteTypes', 'getConstantScalarTypes', 'getConstantScalarValues', 'getArrays', 'getConstantArrays', 'getClassStringObjectType', 'getObjectTypeOrClassStringObjectType', 'getClassStringType', 'getIterableKeyType', 'getFirstIterableKeyType', 'getLastIterableKeyType', 'getIterableValueType', 'getFirstIterableValueType', 'getLastIterableValueType', 'getArraySize', 'getKeysArray', 'getValuesArray', 'popArray', 'shiftArray', 'flipArray', 'shuffleArray', 'filterArrayRemovingFalsey', 'makeListMaybe', 'makeAllArrayKeysOptional'] as $unaryMethod) {
			$r["$name $unaryMethod"] = $attempt(static fn () => $subject->$unaryMethod());
		}
		$r["$name toCoercedArgumentType"] = [$attempt(static fn () => $subject->toCoercedArgumentType(true)), $attempt(static fn () => $subject->toCoercedArgumentType(false))];
		$r["$name toClassConstantType"] = $attempt(static fn () => $subject->toClassConstantType($stringReflectionProvider));
		$r["$name toObjectTypeForIsACheck"] = [$attempt(static fn () => $subject->toObjectTypeForIsACheck($others['object'], true, true)), $attempt(static fn () => $subject->toObjectTypeForIsACheck($others['object'], false, false))];
		$r["$name exponentiate"] = $attempt(static fn () => $subject->exponentiate($others['int']));
		$r["$name getOffsetValueType"] = $attempt(static fn () => $subject->getOffsetValueType($others['int']));
		$r["$name setOffsetValueType"] = [$attempt(static fn () => $subject->setOffsetValueType($others['int'], $others['string'])), $attempt(static fn () => $subject->setOffsetValueType(null, $others['string'], false))];
		$r["$name setExistingOffsetValueType"] = $attempt(static fn () => $subject->setExistingOffsetValueType($others['int'], $others['string']));
		$r["$name unsetOffset"] = $attempt(static fn () => $subject->unsetOffset($others['int']));
		$r["$name getKeysArrayFiltered"] = $attempt(static fn () => $subject->getKeysArrayFiltered($others['int'], \PHPStan\TrinaryLogic::createYes()));
		$r["$name searchArray"] = $attempt(static fn () => $subject->searchArray($others['int']));
		$r["$name getTemplateType"] = $attempt(static fn () => $subject->getTemplateType(\Closure::class, 'T'));
		foreach (['x', 'call', 'bindTo', '__invoke', 'fromCallable', 'nope'] as $member) {
			$r["$name hasProperty $member"] = $cv($subject->hasProperty($member));
			$r["$name hasInstanceProperty $member"] = $cv($subject->hasInstanceProperty($member));
			$r["$name hasStaticProperty $member"] = $cv($subject->hasStaticProperty($member));
			$r["$name hasMethod $member"] = $cv($subject->hasMethod($member));
			$r["$name hasConstant $member"] = $cv($subject->hasConstant($member));
			$r["$name getProperty $member"] = $attempt(static fn () => $subject->getProperty($member, $outOfClassScope));
			$r["$name getInstanceProperty $member"] = $attempt(static fn () => $subject->getInstanceProperty($member, $outOfClassScope));
			$r["$name getStaticProperty $member"] = $attempt(static fn () => $subject->getStaticProperty($member, $outOfClassScope));
			$r["$name getUnresolvedPropertyPrototype $member"] = $attempt(static fn () => $subject->getUnresolvedPropertyPrototype($member, $outOfClassScope));
			$r["$name getUnresolvedInstancePropertyPrototype $member"] = $attempt(static fn () => $subject->getUnresolvedInstancePropertyPrototype($member, $outOfClassScope));
			$r["$name getUnresolvedStaticPropertyPrototype $member"] = $attempt(static fn () => $subject->getUnresolvedStaticPropertyPrototype($member, $outOfClassScope));
			$r["$name getMethod $member"] = $attempt(static fn () => $subject->getMethod($member, $outOfClassScope));
			$r["$name getUnresolvedMethodPrototype $member"] = $attempt(static fn () => $subject->getUnresolvedMethodPrototype($member, $outOfClassScope));
			$r["$name getConstant $member"] = $attempt(static fn () => $subject->getConstant($member));
		}
		$r["$name getCallableParametersAcceptors"] = $attempt(static fn () => $subject->getCallableParametersAcceptors($outOfClassScope));
		if ($subject instanceof \PHPStan\Reflection\Callables\CallableParametersAcceptor) {
			$r["$name acceptor"] = [
				'throwPoints' => $cv($subject->getThrowPoints()),
				'impurePoints' => $cv($subject->getImpurePoints()),
				'invalidateExpressions' => $cv($subject->getInvalidateExpressions()),
				'usedVariables' => $subject->getUsedVariables(),
				'acceptsNamedArguments' => $cv($subject->acceptsNamedArguments()),
				'mustUseReturnValue' => $cv($subject->mustUseReturnValue()),
				'asserts' => $cv($subject->getAsserts()),
				'isStaticClosure' => $cv($subject->isStaticClosure()),
				'isPure' => $cv($subject->isPure()),
				'templateTypeMap' => $cv($subject->getTemplateTypeMap()),
				'resolvedTemplateTypeMap' => $cv($subject->getResolvedTemplateTypeMap()),
				'callSiteVarianceMap' => $cv($subject->getCallSiteVarianceMap()),
				'parameters' => $cv($subject->getParameters()),
				'isVariadic' => $subject->isVariadic(),
				'returnType' => $cv($subject->getReturnType()),
				'templateTags' => $cv($subject->getTemplateTags()),
				'isCommonCallable' => $subject->isCommonCallable(),
			];
		}
		if ($subject instanceof \PHPStan\Type\TypeWithClassName) {
			$r["$name withClassName"] = [$subject->getClassName(), $cv($subject->getClassReflection()), $cv($subject->getAncestorWithClassName(\Closure::class)), $cv($subject->getAncestorWithClassName(\stdClass::class))];
		}
		if ($subject instanceof \PHPStan\Type\IterableType) {
			$r["$name iterable"] = [$cv($subject->getKeyType()), $cv($subject->getItemType()), $cv($subject->toArrayOrTraversable())];
		}
		if ($subject instanceof \PHPStan\Type\Generic\TemplateType) {
			$r["$name template"] = [$subject->getName(), $cv($subject->getBound()), $subject->getVariance()->describe(), $cv($subject->toArgument()), $cv($subject->getDefault())];
		}
	}
	// the PHP subclasses over the native parents: what the natives call
	// through $this must reach the overrides
	$anonymousCallable = new class ([$callableParam('a', new \PHPStan\Type\IntegerType())], new \PHPStan\Type\StringType()) extends \PHPStan\Type\CallableType {

		public function getParameters(): array
		{
			return [new \PHPStan\Reflection\Native\NativeParameterReflection('z', false, new \PHPStan\Type\StringType(), \PHPStan\Reflection\PassedByReference::createNo(), false, null)];
		}

		public function getReturnType(): \PHPStan\Type\Type
		{
			return new \PHPStan\Type\IntegerType();
		}

		public function isPure(): \PHPStan\TrinaryLogic
		{
			return \PHPStan\TrinaryLogic::createYes();
		}

		public function isVariadic(): bool
		{
			return false;
		}

	};
	foreach ($levels as $levelName => $level) {
		$r["anonymous callable describe $levelName"] = $anonymousCallable->describe($level);
	}
	$r['anonymous callable toPhpDocNode'] = $cv($anonymousCallable->toPhpDocNode());
	$r['anonymous callable getImpurePoints'] = $cv($anonymousCallable->getImpurePoints());
	$r['anonymous callable traverse'] = $cv($anonymousCallable->traverse($identityCb));
	$r['anonymous callable traverseSimultaneously'] = $cv($anonymousCallable->traverseSimultaneously($subjects['callableParams'], $unionCb));
	$r['anonymous callable hasTemplateOrLateResolvableType'] = $anonymousCallable->hasTemplateOrLateResolvableType();
	foreach (['callable', 'callableParams', 'callablePureParams', 'callableTemplate', 'closureParams', 'iterableIntString'] as $otherName) {
		$r["anonymous callable isSuperTypeOf $otherName"] = $attempt(static fn () => $anonymousCallable->isSuperTypeOf($subjects[$otherName]));
		$r["anonymous callable accepts $otherName"] = $attempt(static fn () => $anonymousCallable->accepts($subjects[$otherName], true));
		$r["anonymous callable equals $otherName"] = [$anonymousCallable->equals($subjects[$otherName]), $subjects[$otherName]->equals($anonymousCallable)];
		$r["anonymous callable inferTemplateTypes $otherName"] = $attempt(static fn () => $anonymousCallable->inferTemplateTypes($subjects[$otherName]));
		$r["anonymous callable reverse isSuperTypeOf $otherName"] = $attempt(static fn () => $subjects[$otherName]->isSuperTypeOf($anonymousCallable));
	}
	foreach ($variances as $varianceName => $variance) {
		$r["anonymous callable getReferencedTemplateTypes $varianceName"] = $cv($anonymousCallable->getReferencedTemplateTypes($variance));
	}
	$anonymousClosure = new class ([$callableParam('a', new \PHPStan\Type\IntegerType())], new \PHPStan\Type\StringType()) extends \PHPStan\Type\ClosureType {

		public function getImpurePoints(): array
		{
			return [];
		}

		public function getReturnType(): \PHPStan\Type\Type
		{
			return new \PHPStan\Type\IntegerType();
		}

		public function getClassStringType(): \PHPStan\Type\Type
		{
			return new \PHPStan\Type\Constant\ConstantStringType('overridden');
		}

		public function getUnresolvedMethodPrototype(string $methodName, \PHPStan\Reflection\ClassMemberAccessAnswerer $scope): \PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection
		{
			return parent::getUnresolvedMethodPrototype('bindTo', $scope);
		}

	};
	foreach ($levels as $levelName => $level) {
		$r["anonymous closure describe $levelName"] = $anonymousClosure->describe($level);
	}
	$r['anonymous closure isPure'] = $cv($anonymousClosure->isPure());
	$r['anonymous closure toPhpDocNode'] = $cv($anonymousClosure->toPhpDocNode());
	$r['anonymous closure toGetClassResultType'] = $cv($anonymousClosure->toGetClassResultType());
	$r['anonymous closure getMethod'] = $attempt(static fn () => $anonymousClosure->getMethod('call', $outOfClassScope));
	$r['anonymous closure traverse'] = $cv($anonymousClosure->traverse($identityCb));
	$r['anonymous closure hasTemplateOrLateResolvableType'] = $anonymousClosure->hasTemplateOrLateResolvableType();
	foreach (['closure', 'closurePure', 'closureParams', 'closureTemplate', 'callableParams', 'iterableIntString'] as $otherName) {
		$r["anonymous closure isSuperTypeOf $otherName"] = $attempt(static fn () => $anonymousClosure->isSuperTypeOf($subjects[$otherName]));
		$r["anonymous closure accepts $otherName"] = $attempt(static fn () => $anonymousClosure->accepts($subjects[$otherName], true));
		$r["anonymous closure equals $otherName"] = [$anonymousClosure->equals($subjects[$otherName]), $subjects[$otherName]->equals($anonymousClosure)];
		$r["anonymous closure inferTemplateTypes $otherName"] = $attempt(static fn () => $anonymousClosure->inferTemplateTypes($subjects[$otherName]));
		$r["anonymous closure reverse isSuperTypeOf $otherName"] = $attempt(static fn () => $subjects[$otherName]->isSuperTypeOf($anonymousClosure));
	}
	foreach ($variances as $varianceName => $variance) {
		$r["anonymous closure getReferencedTemplateTypes $varianceName"] = $cv($anonymousClosure->getReferencedTemplateTypes($variance));
	}
	$anonymousIterable = new class (new \PHPStan\Type\IntegerType(), new \PHPStan\Type\MixedType()) extends \PHPStan\Type\IterableType {

		public function getItemType(): \PHPStan\Type\Type
		{
			return new \PHPStan\Type\StringType();
		}

		public function getIterableKeyType(): \PHPStan\Type\Type
		{
			return new \PHPStan\Type\Constant\ConstantIntegerType(0);
		}

	};
	foreach ($levels as $levelName => $level) {
		$r["anonymous iterable describe $levelName"] = $anonymousIterable->describe($level);
	}
	$r['anonymous iterable toPhpDocNode'] = $cv($anonymousIterable->toPhpDocNode());
	$r['anonymous iterable getIterableValueType'] = $cv($anonymousIterable->getIterableValueType());
	$r['anonymous iterable toArray'] = $cv($anonymousIterable->toArray());
	$r['anonymous iterable getReferencedClasses'] = $anonymousIterable->getReferencedClasses();
	$r['anonymous iterable traverse'] = $cv($anonymousIterable->traverse($identityCb));
	foreach (['iterable', 'iterableIntString', 'iterableTemplate', 'templateIterable', 'callableParams'] as $otherName) {
		$r["anonymous iterable isSuperTypeOf $otherName"] = $attempt(static fn () => $anonymousIterable->isSuperTypeOf($subjects[$otherName]));
		$r["anonymous iterable accepts $otherName"] = $attempt(static fn () => $anonymousIterable->accepts($subjects[$otherName], true));
		$r["anonymous iterable isSubTypeOf $otherName"] = $attempt(static fn () => $anonymousIterable->isSubTypeOf($subjects[$otherName]));
		$r["anonymous iterable equals $otherName"] = [$anonymousIterable->equals($subjects[$otherName]), $subjects[$otherName]->equals($anonymousIterable)];
		$r["anonymous iterable inferTemplateTypes $otherName"] = $attempt(static fn () => $anonymousIterable->inferTemplateTypes($subjects[$otherName]));
		$r["anonymous iterable tryRemove $otherName"] = $attempt(static fn () => $anonymousIterable->tryRemove($subjects[$otherName]));
	}
	foreach (['arrayIntString', 'genericTraversable', 'objectTraversable', 'unionArrayTraversable'] as $otherName) {
		$r["anonymous iterable accepts other $otherName"] = $attempt(static fn () => $anonymousIterable->accepts($others[$otherName], true));
		$r["anonymous iterable isSuperTypeOf other $otherName"] = $attempt(static fn () => $anonymousIterable->isSuperTypeOf($others[$otherName]));
		$r["anonymous iterable hasOffsetValueType other $otherName"] = $attempt(static fn () => $anonymousIterable->hasOffsetValueType($others[$otherName]));
	}
	foreach ($variances as $varianceName => $variance) {
		$r["anonymous iterable getReferencedTemplateTypes $varianceName"] = $cv($anonymousIterable->getReferencedTemplateTypes($variance));
	}
	// the constructors' named arguments and defaults
	$r['named callable'] = $cv(new \PHPStan\Type\CallableType(returnType: new \PHPStan\Type\IntegerType(), variadic: false));
	$r['named closure'] = $cv(new \PHPStan\Type\ClosureType(usedVariables: ['x'], acceptsNamedArguments: \PHPStan\TrinaryLogic::createNo()));
	$r['named closure usedVariables'] = (new \PHPStan\Type\ClosureType(usedVariables: ['x'], acceptsNamedArguments: \PHPStan\TrinaryLogic::createNo()))->getUsedVariables();
	$r['named iterable'] = $cv(new \PHPStan\Type\IterableType(itemType: new \PHPStan\Type\StringType(), keyType: new \PHPStan\Type\IntegerType()));
	$r['createPure'] = [get_class(\PHPStan\Type\ClosureType::createPure()), $cv(\PHPStan\Type\ClosureType::createPure()->isPure()), $cv(\PHPStan\Type\ClosureType::createPure()->getImpurePoints())];
	// the reflection of the declarations: properties in the twin's order
	foreach ([\PHPStan\Type\CallableType::class, \PHPStan\Type\ClosureType::class, \PHPStan\Type\IterableType::class] as $class) {
		$reflection = new \ReflectionClass($class);
		$r["reflection $class"] = [
			array_map(static fn (\ReflectionProperty $property): array => [$property->getName(), (string) $property->getType(), $property->isPrivate(), $property->hasDefaultValue()], $reflection->getProperties()),
			array_map(static fn (\ReflectionParameter $parameter): array => [$parameter->getName(), (string) $parameter->getType(), $parameter->isOptional(), $parameter->isDefaultValueAvailable() ? var_export($parameter->getDefaultValue(), true) : null], $reflection->getConstructor()->getParameters()),
			$reflection->isFinal(),
			(static function (array $names): array { sort($names); return $names; })(array_map('strtolower', $reflection->getInterfaceNames())),
		];
	}
	foreach ($r as $key => $value) {
		$observations["callable $key"] = $value;
	}
}


// observations holding bytes that are not UTF-8 (the invalid-UTF-8 subject's
// descriptions) go out base64-encoded so json_encode() keeps every byte; the
// non-finite floats (the NAN and infinity subjects' values) as their names
$encodable = static function (mixed $v) use (&$encodable): mixed {
	if (is_string($v) && !mb_check_encoding($v, 'UTF-8')) {
		return 'base64:' . base64_encode($v);
	}
	if (is_float($v) && !is_finite($v)) {
		return is_nan($v) ? 'float:NAN' : ($v > 0 ? 'float:INF' : 'float:-INF');
	}
	if (is_array($v)) {
		return array_map($encodable, $v);
	}
	return $v;
};

echo json_encode($encodable($observations), JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION), "\n";

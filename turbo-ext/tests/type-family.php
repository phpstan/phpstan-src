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
foreach ([\PHPStan\Type\BooleanType::class, \PHPStan\Type\Constant\ConstantBooleanType::class, \PHPStan\Type\IntegerType::class, \PHPStan\Type\Constant\ConstantIntegerType::class, \PHPStan\Type\IntegerRangeType::class, \PHPStan\Type\StringType::class, \PHPStan\Type\Constant\ConstantStringType::class, \PHPStan\Type\ClassStringType::class, \PHPStan\Type\Generic\GenericClassStringType::class, \PHPStan\Type\FloatType::class, \PHPStan\Type\Constant\ConstantFloatType::class, \PHPStan\Type\NullType::class, \PHPStan\Type\VoidType::class, \PHPStan\Type\NeverType::class, \PHPStan\Type\MixedType::class, \PHPStan\Type\StrictMixedType::class, \PHPStan\Type\ObjectWithoutClassType::class, \PHPStan\Type\StaticType::class, \PHPStan\Type\ThisType::class, \PHPStan\Type\Generic\GenericStaticType::class, \PHPStan\Type\ObjectShapeType::class, \PHPStan\Type\NonexistentParentClassType::class, \PHPStan\Type\ArrayType::class, \PHPStan\Type\Accessory\NonEmptyArrayType::class, \PHPStan\Type\Accessory\AccessoryArrayListType::class, \PHPStan\Type\Accessory\OversizedArrayType::class, \PHPStan\Type\Accessory\HasOffsetType::class, \PHPStan\Type\Accessory\HasOffsetValueType::class, \PHPStan\Type\Accessory\AccessoryNumericStringType::class, \PHPStan\Type\Accessory\AccessoryNonEmptyStringType::class, \PHPStan\Type\Accessory\AccessoryNonFalsyStringType::class, \PHPStan\Type\Accessory\AccessoryLiteralStringType::class, \PHPStan\Type\Accessory\AccessoryLowercaseStringType::class, \PHPStan\Type\Accessory\AccessoryUppercaseStringType::class, \PHPStan\Type\Accessory\AccessoryDecimalIntegerStringType::class, \PHPStan\Type\Accessory\HasMethodType::class, \PHPStan\Type\Accessory\HasPropertyType::class, \PHPStan\Type\ObjectType::class, \PHPStan\Type\Generic\GenericObjectType::class, \PHPStan\Type\Enum\EnumCaseObjectType::class, \PHPStan\Type\IterableType::class, \PHPStan\Type\CallableType::class, \PHPStan\Type\ClosureType::class, \PHPStan\Type\Constant\ConstantArrayType::class, \PHPStan\Type\UnionType::class, \PHPStan\Type\BenevolentUnionType::class, \PHPStan\Type\IntersectionType::class, \PHPStan\Type\ErrorType::class, \PHPStan\Type\CircularTypeAliasErrorType::class, \PHPStan\Type\Generic\AbsorbedTemplateArgumentType::class, \PHPStan\Type\NonAcceptingNeverType::class, \PHPStan\Type\StringAlwaysAcceptingObjectWithToStringType::class, \PHPStan\Type\StringNeverAcceptingObjectWithToStringType::class, \PHPStan\Type\ResourceType::class, \PHPStan\Type\KeyOfType::class, \PHPStan\Type\ValueOfType::class, \PHPStan\Type\OffsetAccessType::class, \PHPStan\Type\ClassConstantAccessType::class, \PHPStan\Type\NewObjectType::class, \PHPStan\Type\ConditionalType::class, \PHPStan\Type\ConditionalTypeForParameter::class, \PHPStan\Type\LateResolvableArrayShapeType::class, \PHPStan\Type\Generic\UnresolvedTemplateArgumentType::class, \PHPStan\Type\Generic\TemplateArrayType::class, \PHPStan\Type\Generic\TemplateBenevolentUnionType::class, \PHPStan\Type\Generic\TemplateBooleanType::class, \PHPStan\Type\Generic\TemplateConstantArrayType::class, \PHPStan\Type\Generic\TemplateConstantIntegerType::class, \PHPStan\Type\Generic\TemplateConstantStringType::class, \PHPStan\Type\Generic\TemplateFloatType::class, \PHPStan\Type\Generic\TemplateGenericObjectType::class, \PHPStan\Type\Generic\TemplateIntegerType::class, \PHPStan\Type\Generic\TemplateIntersectionType::class, \PHPStan\Type\Generic\TemplateIterableType::class, \PHPStan\Type\Generic\TemplateMixedType::class, \PHPStan\Type\Generic\TemplateNullType::class, \PHPStan\Type\Generic\TemplateObjectShapeType::class, \PHPStan\Type\Generic\TemplateObjectType::class, \PHPStan\Type\Generic\TemplateObjectWithoutClassType::class, \PHPStan\Type\Generic\TemplateStrictMixedType::class, \PHPStan\Type\Generic\TemplateStringType::class, \PHPStan\Type\Generic\TemplateUnionType::class, \PHPStan\Type\Generic\TemplateKeyOfType::class] as $typeClass) {
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


// ---- ConstantArrayType ----
// the array shapes, built directly and through ConstantArrayTypeBuilder,
// under both sealedness conventions (a null unsealed pair, the explicit
// never marker, real extras); the PHP TemplateConstantArrayType subclass
// (overriding recreate()) and an anonymous subclass overriding what the
// native calls through $this come along; the reflection provider and
// PhpVersion accessors stay registered from the string section
{
	$catClass = \PHPStan\Type\Constant\ConstantArrayType::class;
	$r = [];
	$others = $arrayOthers(\PHPStan\Type\ArrayType::class, \PHPStan\Type\Accessory\NonEmptyArrayType::class, \PHPStan\Type\Accessory\AccessoryArrayListType::class, \PHPStan\Type\Accessory\OversizedArrayType::class, \PHPStan\Type\Accessory\HasOffsetType::class, \PHPStan\Type\Accessory\HasOffsetValueType::class);
	$ci = static fn (int $i): \PHPStan\Type\Constant\ConstantIntegerType => new \PHPStan\Type\Constant\ConstantIntegerType($i);
	$cs = static fn (string $s): \PHPStan\Type\Constant\ConstantStringType => new \PHPStan\Type\Constant\ConstantStringType($s);
	$int = new \PHPStan\Type\IntegerType();
	$string = new \PHPStan\Type\StringType();
	$bool = new \PHPStan\Type\BooleanType();
	$float = new \PHPStan\Type\FloatType();
	$null = new \PHPStan\Type\NullType();
	$mixed = new \PHPStan\Type\MixedType();
	$object = new \PHPStan\Type\ObjectType(\PHPStan\TrinaryLogic::class);
	$never = new \PHPStan\Type\NeverType(true);
	$yes = \PHPStan\TrinaryLogic::createYes();
	$maybe = \PHPStan\TrinaryLogic::createMaybe();
	$no = \PHPStan\TrinaryLogic::createNo();
	$shape = static function (array $entries, array $optionalKeys = [], ?\PHPStan\TrinaryLogic $isList = null, ?array $unsealed = null, ?array $nextAutoIndexes = null) use ($catClass, $ci, $cs): \PHPStan\Type\Constant\ConstantArrayType {
		$keyTypes = [];
		$valueTypes = [];
		$max = -1;
		foreach ($entries as $key => $valueType) {
			$keyTypes[] = is_int($key) ? $ci($key) : $cs($key);
			$valueTypes[] = $valueType;
			if (is_int($key) && $key > $max) {
				$max = $key;
			}
		}
		return new $catClass($keyTypes, $valueTypes, $nextAutoIndexes ?? [$max + 1], $optionalKeys, $isList, $unsealed);
	};
	$built = static function (callable $fill): \PHPStan\Type\Type {
		$builder = \PHPStan\Type\Constant\ConstantArrayTypeBuilder::createEmpty();
		$fill($builder);
		return $builder->getArray();
	};
	$subjects = [
		'empty' => new $catClass([], []),
		'emptySealed' => new $catClass([], [], unsealed: [$never, $never]),
		'emptyUnsealedStringInt' => new $catClass([], [], unsealed: [$string, $int]),
		'emptyUnsealedMixed' => new $catClass([], [], unsealed: [$mixed, $mixed]),
		'emptyUnsealedIntKeys' => new $catClass([], [], unsealed: [$int, $string]),
		'emptyUnsealedBenevolent' => new $catClass([], [], unsealed: [new \PHPStan\Type\BenevolentUnionType([$int, $string]), $string]),
		'emptyUnsealedStrictMixed' => new $catClass([], [], unsealed: [new \PHPStan\Type\StrictMixedType(), $string]),
		'list2' => $shape([0 => $int, 1 => $string], [], $yes),
		'list2maybe' => $shape([0 => $int, 1 => $string], [], $maybe),
		'list2no' => $shape([0 => $int, 1 => $string]),
		'listOptionalTail' => $shape([0 => $int, 1 => $string], [1], $yes),
		'listOptionalMiddle' => $shape([0 => $int, 1 => $string, 2 => $float], [1, 2], $yes),
		'listAllOptional' => $shape([0 => $int, 1 => $string], [0, 1], $yes),
		'listBuilt' => $built(static function ($b) use ($int, $string, $bool): void {
			$b->setOffsetValueType(null, $int);
			$b->setOffsetValueType(null, $string);
			$b->setOffsetValueType(null, $bool, true);
		}),
		'strKeys' => $shape(['a' => $int, 'b' => $string]),
		'strKeysOptional' => $shape(['a' => $int, 'b' => $string], [1]),
		'strKeysAllOptional' => $shape(['a' => $int, 'b' => $string], [0, 1]),
		'mixedKeys' => $shape(['a' => $int, 0 => $string, 'b' => $bool]),
		'nonSeqInts' => $shape([1 => $string, 5 => $int, 3 => $bool]),
		'negativeInt' => $shape([-1 => $string, 0 => $int]),
		'nested' => $shape(['a' => $shape(['b' => $int]), 'c' => $shape([0 => $string, 1 => $int], [], $yes)]),
		'unionValues' => $shape(['a' => new \PHPStan\Type\UnionType([$int, $string]), 'b' => new \PHPStan\Type\UnionType([$bool, $null])]),
		'constValues' => $shape(['a' => $ci(1), 'b' => $cs('x'), 'c' => new \PHPStan\Type\Constant\ConstantBooleanType(false)]),
		'finite' => $shape([0 => new \PHPStan\Type\UnionType([$ci(1), $ci(2)]), 'a' => $bool]),
		'finiteOptional' => $shape([0 => new \PHPStan\Type\UnionType([$ci(1), $ci(2)]), 'a' => $bool], [1]),
		'callable' => $shape([0 => $object, 1 => $cs('createYes')]),
		'callableUnion' => $shape([0 => $object, 1 => new \PHPStan\Type\UnionType([$cs('createYes'), $cs('nope')])]),
		'callableClassString' => $shape([0 => new \PHPStan\Type\Constant\ConstantStringType(\PHPStan\TrinaryLogic::class, true), 1 => $cs('createYes')]),
		'callableOptional' => $shape([0 => $object, 1 => $cs('createYes')], [1]),
		'callableNonConstant' => $shape([0 => $object, 1 => $string]),
		'callableUnsealed' => $arrayUnsealedShape([0 => $object], \PHPStan\Type\IntegerRangeType::fromInterval(0, 1), new \PHPStan\Type\IntersectionType([$string, new \PHPStan\Type\Accessory\AccessoryNonFalsyStringType()])),
		'notCallable' => $shape([0 => $object, 1 => $cs('createYes'), 2 => $int]),
		'quotedKeys' => $shape(['a b' => $int, "it's" => $string, 'say "hi"' => $bool, '1a' => $int, 'Foo\\Bar' => $float, "\xff" => $int]),
		'numericStringKey' => new $catClass([$cs('1'), $cs('0')], [$int, $string]),
		'unsealedStrInt' => $arrayUnsealedShape(['a' => $int], $string, $int),
		'unsealedStrIntOptional' => $built(static function ($b) use ($int, $string, $cs): void {
			$b->setOffsetValueType($cs('a'), $int, true);
			$b->makeUnsealed($string, $int);
		}),
		'unsealedListTail' => $built(static function ($b) use ($int, $string): void {
			$b->setOffsetValueType(null, $int);
			$b->setOffsetValueType(null, $string);
			$b->makeUnsealed(\PHPStan\Type\IntegerRangeType::createAllGreaterThanOrEqualTo(0), $string);
		}),
		'unsealedMixed' => $arrayUnsealedShape(['a' => $int], $mixed, $mixed),
		'unsealedFiniteKey' => $arrayUnsealedShape(['a' => $int], new \PHPStan\Type\UnionType([$ci(0), $ci(1)]), $string),
		'unsealedIntKeys' => $arrayUnsealedShape([0 => $int, 1 => $string], $int, $bool),
		'unsealedConstKeys' => $arrayUnsealedShape(['a' => $int], new \PHPStan\Type\UnionType([$cs('b'), $cs('C')]), $float),
		'big' => $shape(array_combine(range(0, 11), array_fill(0, 12, $int)), [], $yes),
		'bigOptional' => $shape(array_combine(range(0, 11), array_fill(0, 12, $int)), range(1, 11), $yes),
		'bigStrings' => $shape(array_combine(array_map(static fn (int $i): string => 'k' . $i, range(0, 9)), array_fill(0, 10, $string)), [2, 4]),
		'templateValues' => $shape(['a' => $arrayTemplateT, 'b' => $arrayTemplateV]),
		'templateUnsealed' => $arrayUnsealedShape(['a' => $arrayTemplateV], $arrayTemplateK, $arrayTemplateT),
		'templateCat' => \PHPStan\Type\Generic\TemplateTypeFactory::create($arrayTemplateScope, 'C', $shape(['a' => $int, 'b' => $string], [1]), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
	];
	$others['catList2'] = $subjects['list2'];
	$others['catStrKeysOptional'] = $subjects['strKeysOptional'];
	$others['catUnsealedStrInt'] = $subjects['unsealedStrInt'];
	$others['catEmptySealed'] = $subjects['emptySealed'];
	$others['catUnsealedListTail'] = $subjects['unsealedListTail'];
	$others['catTemplateCat'] = $subjects['templateCat'];
	$others['stringB'] = $cs('b');
	$others['stringK3'] = $cs('k3');
	$others['int2'] = $ci(2);
	$others['int3'] = $ci(3);
	$others['int11'] = $ci(11);
	$others['int-3'] = $ci(-3);
	$others['unionKeys'] = new \PHPStan\Type\UnionType([$cs('a'), $ci(0)]);
	$others['unionInts'] = new \PHPStan\Type\UnionType([$ci(0), $ci(5)]);
	$others['range1-3'] = \PHPStan\Type\IntegerRangeType::fromInterval(1, 3);
	$pairNames = ['int', 'int0', 'int1', 'int2', 'int3', 'int5', 'int11', 'int-1', 'int-3', 'intMax', 'range0-max', 'range1-3', 'range0-3', 'string', 'stringA', 'stringB', 'stringAbc', 'stringK3', 'stringEmpty', 'string0', 'string123', 'nonEmptyString', 'numericString', 'float', 'bool', 'true', 'false', 'null', 'mixed', 'strictMixed', 'never', 'union', 'unionKeys', 'unionInts', 'unionConsts', 'benevolent', 'array', 'arrayIntString', 'arrayStringInt', 'list', 'nonEmptyArray', 'nonEmptyList', 'oversizedArray', 'arrayWithOffsetA', 'arrayWithOffset0', 'emptyArray', 'constArray', 'constArrayInts', 'constArrayOptional', 'constArrayNested', 'constArrayUnsealed', 'catList2', 'catStrKeysOptional', 'catUnsealedStrInt', 'catEmptySealed', 'catUnsealedListTail', 'catTemplateCat', 'object', 'objectTrinary', 'callable', 'iterable', 'templateT', 'templateArray', 'nonEmpty', 'listAccessory', 'hasOffsetA', 'hasOffset0', 'hasOffsetValueA', 'hasOffsetValueAString', 'hasOffsetValue0', 'hasOffsetValue1', 'falsey'];
	$viewR = static function (mixed $v) use (&$viewR, $view): mixed {
		if ($v instanceof \PHPStan\Type\IsSuperTypeOfResult) {
			return [$v->result->describe(), $v->reasons, $v->getReasons()];
		}
		if ($v instanceof \PHPStan\Type\Constant\ConstantArrayTypeAndMethod) {
			return [$v->isUnknown(), $v->getCertainty()->describe(), $v->isUnknown() ? null : [$view($v->getType()), $v->getMethod()]];
		}
		if ($v instanceof \PHPStan\Type\Generic\TemplateTypeMap) {
			return array_map($view, $v->getTypes());
		}
		if (is_array($v)) {
			return array_map($viewR, $v);
		}
		return $view($v);
	};
	$outOfClassScope = new \PHPStan\Analyser\OutOfClassScope();
	$identity = static fn ($t) => $t;
	$toObject = static fn ($t) => new \PHPStan\Type\ObjectType(\stdClass::class);
	$toNever = static fn ($t) => new \PHPStan\Type\NeverType();
	$toUnionWithNull = static fn ($t) => \PHPStan\Type\TypeCombinator::addNull($t);
	$levels = ['typeOnly' => \PHPStan\Type\VerbosityLevel::typeOnly(), 'value' => \PHPStan\Type\VerbosityLevel::value(), 'precise' => \PHPStan\Type\VerbosityLevel::precise(), 'cache' => \PHPStan\Type\VerbosityLevel::cache()];
	$catch = static function (callable $probe) use ($viewR): mixed {
		try {
			return $viewR($probe());
		} catch (\TypeError | \ArgumentCountError $e) {
			return get_class($e);
		} catch (\Throwable $e) {
			return [get_class($e), $e->getMessage()];
		}
	};
	foreach ($subjects as $name => $subject) {
		$r["$name class"] = get_class($subject);
		foreach ($levels as $levelName => $level) {
			$r["$name describe $levelName"] = $subject->describe($level);
		}
		foreach (['isSealed', 'isUnsealed', 'getUnsealedTypes', 'dropUnsealedTypes', 'getConstantArrays', 'getReferencedClasses', 'getIterableKeyType', 'getIterableValueType', 'getKeyType', 'getItemType', 'isConstantValue', 'getNextAutoIndexes', 'getOptionalKeys', 'getAllArrays', 'getKeyTypes', 'getValueTypes', 'sortKeys', 'isCallable', 'findTypeAndMethodNames',
			'popArray', 'shiftArray', 'shuffleArray', 'flipArray', 'isIterableAtLeastOnce', 'getArraySize', 'getFirstIterableKeyType', 'getLastIterableKeyType', 'getFirstIterableValueType', 'getLastIterableValueType', 'isConstantArray', 'isList', 'toBoolean', 'toInteger', 'toFloat', 'generalizeValues', 'getKeysArray', 'getValuesArray', 'makeList', 'makeListMaybe', 'makeAllArrayKeysOptional', 'filterArrayRemovingFalsey', 'toPhpDocNode', 'getFiniteTypes', 'hasTemplateOrLateResolvableType',
			'toNumber', 'toString', 'toArray', 'toArrayKey', 'toBitwiseNotType', 'toAbsoluteNumber', 'toGetClassResultType', 'toObjectTypeForInstanceofCheck', 'isTrue', 'isFalse', 'isBoolean', 'isScalar', 'isNull', 'isInteger', 'isFloat', 'isString', 'isNumericString', 'isDecimalIntegerString', 'isNonEmptyString', 'isNonFalsyString', 'isLiteralString', 'isLowercaseString', 'isUppercaseString', 'isClassString', 'isVoid',
			'isConstantScalarValue', 'getConstantScalarTypes', 'getConstantScalarValues', 'isObject', 'isEnum', 'getArrays', 'getConstantStrings', 'getObjectClassNames', 'getObjectClassReflections', 'getClassStringType', 'getClassStringObjectType', 'getObjectTypeOrClassStringObjectType', 'canAccessProperties', 'canCallMethods', 'canAccessConstants', 'isIterable', 'isArray', 'isOversizedArray', 'isOffsetAccessible', 'isOffsetAccessLegal', 'getEnumCases', 'getEnumCaseObject', 'isCloneable'] as $method) {
			$r["$name $method"] = $catch(static fn () => $subject->$method());
			// the memoized answers must read back the same
			$r["$name $method again"] = $catch(static fn () => $subject->$method());
		}
		$r["$name getReferencedTemplateTypes"] = array_map(static fn ($variance) => $viewR($subject->getReferencedTemplateTypes($variance)), [\PHPStan\Type\Generic\TemplateTypeVariance::createInvariant(), \PHPStan\Type\Generic\TemplateTypeVariance::createCovariant(), \PHPStan\Type\Generic\TemplateTypeVariance::createContravariant()]);
		foreach ([-1, 0, 1, 2, 3, 11] as $i) {
			$r["$name isOptionalKey $i"] = $subject->isOptionalKey($i);
		}
		foreach ([\PHPStan\Type\GeneralizePrecision::lessSpecific(), \PHPStan\Type\GeneralizePrecision::moreSpecific(), \PHPStan\Type\GeneralizePrecision::templateArgument()] as $i => $precision) {
			$r["$name generalize $i"] = $viewR($subject->generalize($precision));
		}
		$r["$name toCoercedArgumentType"] = [$viewR($subject->toCoercedArgumentType(true)), $viewR($subject->toCoercedArgumentType(false))];
		$r["$name traverse identity"] = $subject->traverse($identity) === $subject;
		$r["$name traverse replaced"] = $viewR($subject->traverse($toObject));
		$r["$name traverse never"] = $viewR($subject->traverse($toNever));
		$r["$name traverse nullable"] = $viewR($subject->traverse($toUnionWithNull));
		$r["$name mapValueType"] = [$viewR($subject->mapValueType($identity)), $viewR($subject->mapValueType($toObject)), $subject->mapValueType($identity) === $subject];
		$r["$name mapKeyType"] = [$subject->mapKeyType($identity) === $subject, $viewR($subject->mapKeyType($toObject))];
		$r["$name changeKeyCaseArray"] = [$viewR($subject->changeKeyCaseArray(null)), $viewR($subject->changeKeyCaseArray(CASE_LOWER)), $viewR($subject->changeKeyCaseArray(CASE_UPPER))];
		$r["$name reverseArray"] = [$viewR($subject->reverseArray($yes)), $viewR($subject->reverseArray($no)), $viewR($subject->reverseArray($maybe))];
		$r["$name getSmallerType"] = $viewR($subject->getSmallerType($arrayPhpVersions[1]));
		$r["$name getGreaterOrEqualType"] = $viewR($subject->getGreaterOrEqualType($arrayPhpVersions[1]));
		$r["$name getCallableParametersAcceptors"] = $catch(static fn () => array_map(static fn ($acceptor) => [get_class($acceptor), $acceptor->getReturnType()->describe(\PHPStan\Type\VerbosityLevel::precise()), count($acceptor->getParameters())], $subject->getCallableParametersAcceptors($outOfClassScope)));
		$r["$name getKeysArrayFiltered"] = $viewR($subject->getKeysArrayFiltered($int, $yes));
		$r["$name exponentiate"] = $viewR($subject->exponentiate($int));
		$r["$name getTemplateType"] = $viewR($subject->getTemplateType('Foo', 'T'));
		$r["$name hasMethod"] = $viewR($subject->hasMethod('x'));
		$r["$name toClassConstantType"] = $viewR($subject->toClassConstantType($stringReflectionProvider));
		foreach (['int1', 'int2', 'int5', 'int', 'range1-max', 'range1-3', 'unionConsts', 'string'] as $lengthName) {
			$r["$name chunkArray $lengthName"] = [$viewR($subject->chunkArray($others[$lengthName], $yes)), $viewR($subject->chunkArray($others[$lengthName], $no))];
		}
		foreach (['int1', 'int2', 'int5', 'int11', 'range0-3', 'range2-4', 'range3-max', 'range0-300', 'range300-max', 'int', 'string', 'int0'] as $sizeName) {
			$r["$name truncateListToSize $sizeName"] = $viewR($subject->truncateListToSize($others[$sizeName]));
		}
		foreach (['int-3', 'int-1', 'int0', 'int1', 'int2', 'int3', 'int'] as $offsetName) {
			foreach (['null', 'int0', 'int1', 'int2', 'int-1', 'int'] as $lengthName) {
				$r["$name sliceArray $offsetName $lengthName"] = [$viewR($subject->sliceArray($others[$offsetName], $others[$lengthName], $yes)), $viewR($subject->sliceArray($others[$offsetName], $others[$lengthName], $no))];
			}
		}
		foreach (['int0', 'int1', 'int-1', 'int'] as $offsetName) {
			foreach (['int0', 'int1', 'null', 'int-1'] as $lengthName) {
				foreach (['constArray', 'constArrayInts', 'emptyArray', 'list', 'arrayIntString', 'string', 'catList2'] as $replacementName) {
					$r["$name spliceArray $offsetName $lengthName $replacementName"] = $viewR($subject->spliceArray($others[$offsetName], $others[$lengthName], $others[$replacementName]));
				}
			}
		}
		foreach ($pairNames as $otherName) {
			$other = $others[$otherName];
			$r["$name isSuperTypeOf $otherName"] = $viewR($subject->isSuperTypeOf($other));
			$r["$name accepts $otherName"] = $viewR($subject->accepts($other, true));
			$r["$name accepts-loose $otherName"] = $viewR($subject->accepts($other, false));
			$r["$name equals $otherName"] = $subject->equals($other);
			$r["$name tryRemove $otherName"] = $viewR($subject->tryRemove($other));
			$r["$name looseCompare $otherName"] = $viewR($subject->looseCompare($other, $arrayPhpVersions[1]));
			$r["$name isSmallerThan $otherName"] = $viewR($subject->isSmallerThan($other, $arrayPhpVersions[1]));
			$r["$name isSmallerThanOrEqual $otherName"] = $viewR($subject->isSmallerThanOrEqual($other, $arrayPhpVersions[1]));
			$r["$name hasOffsetValueType $otherName"] = $viewR($subject->hasOffsetValueType($other));
			$r["$name getOffsetValueType $otherName"] = $viewR($subject->getOffsetValueType($other));
			$r["$name setOffsetValueType $otherName"] = [$viewR($subject->setOffsetValueType($other, $int)), $viewR($subject->setOffsetValueType($other, $string, false)), $viewR($subject->setOffsetValueType($other, $bool, unionValues: true))];
			$r["$name setExistingOffsetValueType $otherName"] = $viewR($subject->setExistingOffsetValueType($other, $float));
			$r["$name unsetOffset $otherName"] = [$viewR($subject->unsetOffset($other)), $viewR($subject->unsetOffset($other, true)), $subject->unsetOffset($other) === $subject];
			$r["$name makeOffsetRequired $otherName"] = [$viewR($subject->makeOffsetRequired($other)), $subject->makeOffsetRequired($other) === $subject];
			$r["$name searchArray $otherName"] = [$viewR($subject->searchArray($other)), $viewR($subject->searchArray($other, $yes)), $viewR($subject->searchArray($other, $no)), $viewR($subject->searchArray($other, strict: $maybe))];
			$r["$name intersectKeyArray $otherName"] = $viewR($subject->intersectKeyArray($other));
			$r["$name fillKeysArray $otherName"] = $viewR($subject->fillKeysArray($other));
			$r["$name inferTemplateTypes $otherName"] = $viewR($subject->inferTemplateTypes($other));
			$r["$name traverseSimultaneously $otherName"] = [$viewR($subject->traverseSimultaneously($other, static fn ($a, $b) => $a)), $viewR($subject->traverseSimultaneously($other, static fn ($a, $b) => $b)), $subject->traverseSimultaneously($other, static fn ($a, $b) => $a) === $subject];
			$r["$name combinator union $otherName"] = $viewR(\PHPStan\Type\TypeCombinator::union($subject, $other));
			$r["$name combinator intersect $otherName"] = $viewR(\PHPStan\Type\TypeCombinator::intersect($subject, $other));
			$r["$name combinator remove $otherName"] = $viewR(\PHPStan\Type\TypeCombinator::remove($subject, $other));
			$r["$name combinator remove-reverse $otherName"] = $viewR(\PHPStan\Type\TypeCombinator::remove($other, $subject));
			$r["$name other isSuperTypeOf $otherName"] = $viewR($other->isSuperTypeOf($subject));
			$r["$name other accepts $otherName"] = $viewR($other->accepts($subject, true));
			$r["$name other equals $otherName"] = $other->equals($subject);
			$r["$name other tryRemove $otherName"] = $viewR($other->tryRemove($subject));
		}
		$r["$name combinator removeNull"] = $viewR(\PHPStan\Type\TypeCombinator::removeNull($subject));
		$r["$name combinator addNull"] = $viewR(\PHPStan\Type\TypeCombinator::addNull($subject));
		foreach ($subjects as $otherName => $otherSubject) {
			$r["$name isKeysSupersetOf $otherName"] = $subject->isKeysSupersetOf($otherSubject);
			$r["$name mergeWith $otherName"] = $viewR($subject->mergeWith($otherSubject));
			$r["$name isSuperTypeOf-subject $otherName"] = $viewR($subject->isSuperTypeOf($otherSubject));
			$r["$name accepts-subject $otherName"] = $viewR($subject->accepts($otherSubject, true));
			$r["$name equals-subject $otherName"] = $subject->equals($otherSubject);
			$r["$name union-subject $otherName"] = $viewR(\PHPStan\Type\TypeCombinator::union($subject, $otherSubject));
			$r["$name intersect-subject $otherName"] = $viewR(\PHPStan\Type\TypeCombinator::intersect($subject, $otherSubject));
		}
	}
	// the statics
	foreach (['a', 'a b', '_x', '1a', 'Foo\\Bar', '\\Foo', 'ab-c', '-ab', "\xff\xfe", '', 'ÄÖ', "it's", 'a"b', 'a.b', 'x1'] as $i => $identifier) {
		$r["isValidIdentifier $i"] = $catClass::isValidIdentifier($identifier);
	}
	foreach (['int1', 'int-1', 'range0-3', 'range3-max', 'range0-max', 'int', 'string', 'unionConsts'] as $sizeName) {
		$r["extractTruncateListBounds $sizeName"] = $catClass::extractTruncateListBounds($others[$sizeName]);
	}
	// the typed constructor parameters and the count assertion
	foreach ([[[$ci(0)], []], [[], [$int]], [[$ci(0)], [$int], [0], [], $int], [[$ci(0)], [$int], 'x'], [[$ci(0)], [$int], [0], [], null, 'x'], [[$ci(0)], [$int], [0], [], $yes, null], [[$ci(0)], [$int], [], [0], $maybe, [$never, $never]], [[$ci(0)], [$int], [0], [], null, [$string, $int]]] as $i => $args) {
		// a mismatched pair only where assertions run: otherwise it is a
		// contract violation whose failure is not compared
		$r["construct $i"] = count($args[0]) !== count($args[1]) && ini_get('zend.assertions') !== '1' ? 'assertions off' : $catch(static fn () => new $catClass(...$args));
	}
	// malformed contents (a one-element unsealed pair, non-Type keys) construct on both sides; their later failures are contract violations whose messages are not compared
	$r['construct malformed'] = [get_class(new $catClass([$ci(0)], [$int], [0], [], null, [$int])), get_class(new $catClass(['a'], ['b']))];
	$r['construct named'] = $viewR(new $catClass(valueTypes: [$int], keyTypes: [$ci(0)], isList: $yes, unsealed: [$int, $string]));
	$r['construct named optional'] = $viewR(new $catClass([$ci(0)], [$int], optionalKeys: [0]));
	// an uninitialized instance: every typed-slot read raises the same Error
	$uninitialized = (new \ReflectionClass($catClass))->newInstanceWithoutConstructor();
	foreach (['describe' => [\PHPStan\Type\VerbosityLevel::precise()], 'isSuperTypeOf' => [$int], 'accepts' => [$int, true], 'equals' => [$uninitialized], 'getKeyType' => [], 'getItemType' => [], 'getIterableKeyType' => [], 'getIterableValueType' => [], 'isList' => [], 'isUnsealed' => [], 'toPhpDocNode' => [], 'hasTemplateOrLateResolvableType' => [], 'getReferencedClasses' => [], 'isCallable' => [], 'unsetOffset' => [$others['int0']], 'getArraySize' => [], 'getOptionalKeys' => [], 'getNextAutoIndexes' => [], 'getUnsealedTypes' => [], 'getAllArrays' => [], 'sortKeys' => [], 'popArray' => [], 'getKeysArray' => [], 'generalizeValues' => [], 'isIterableAtLeastOnce' => [], 'hasOffsetValueType' => [$others['int0']], 'getOffsetValueType' => [$others['int0']], 'isOptionalKey' => [0], 'getFiniteTypes' => [], 'flipArray' => [], 'makeList' => []] as $method => $args) {
		$r["uninitialized $method"] = $catch(static fn () => $uninitialized->$method(...$args));
	}
	// a PHP subclass overriding what the native calls through $this
	$anonymousShape = new class ([$cs('a'), $ci(0), $cs('b')], [$int, $string, $bool], [1], [2]) extends \PHPStan\Type\Constant\ConstantArrayType {

		public function isOptionalKey(int $i): bool
		{
			return $i === 0;
		}

		public function isUnsealed(): \PHPStan\TrinaryLogic
		{
			return \PHPStan\TrinaryLogic::createNo();
		}

		public function getIterableValueType(): \PHPStan\Type\Type
		{
			return new \PHPStan\Type\FloatType();
		}

		protected function recreate(array $keyTypes, array $valueTypes, array $nextAutoIndexes, array $optionalKeys, ?\PHPStan\TrinaryLogic $isList, ?array $unsealed): \PHPStan\Type\Constant\ConstantArrayType
		{
			return new \PHPStan\Type\Constant\ConstantArrayType($keyTypes, $valueTypes, $nextAutoIndexes, $optionalKeys, \PHPStan\TrinaryLogic::createMaybe(), $unsealed);
		}

		public function getValuesArray(): \PHPStan\Type\Constant\ConstantArrayType
		{
			return new \PHPStan\Type\Constant\ConstantArrayType([new \PHPStan\Type\Constant\ConstantIntegerType(0)], [new \PHPStan\Type\NullType()]);
		}

	};
	foreach (['getKeyType', 'getItemType', 'getFirstIterableValueType', 'getLastIterableKeyType', 'getArraySize', 'isIterableAtLeastOnce', 'isSealed', 'isConstantValue', 'getAllArrays', 'sortKeys', 'popArray', 'shiftArray', 'shuffleArray', 'flipArray', 'getKeysArray', 'getValuesArray', 'generalizeValues', 'makeList', 'makeListMaybe', 'makeAllArrayKeysOptional', 'dropUnsealedTypes', 'toPhpDocNode', 'getFiniteTypes', 'isCallable', 'toBoolean', 'toInteger', 'filterArrayRemovingFalsey'] as $method) {
		$r["anonymous shape $method"] = $catch(static fn () => $anonymousShape->$method());
	}
	foreach ($levels as $levelName => $level) {
		$r["anonymous shape describe $levelName"] = $anonymousShape->describe($level);
	}
	foreach (['int0', 'int1', 'stringA', 'stringB', 'string', 'int', 'constArray', 'catList2', 'catStrKeysOptional', 'arrayStringInt', 'mixed'] as $otherName) {
		$other = $others[$otherName];
		$r["anonymous shape isSuperTypeOf $otherName"] = $viewR($anonymousShape->isSuperTypeOf($other));
		$r["anonymous shape accepts $otherName"] = $viewR($anonymousShape->accepts($other, true));
		$r["anonymous shape hasOffsetValueType $otherName"] = $viewR($anonymousShape->hasOffsetValueType($other));
		$r["anonymous shape getOffsetValueType $otherName"] = $viewR($anonymousShape->getOffsetValueType($other));
		$r["anonymous shape unsetOffset $otherName"] = [$viewR($anonymousShape->unsetOffset($other)), $viewR($anonymousShape->unsetOffset($other, true))];
		$r["anonymous shape makeOffsetRequired $otherName"] = $viewR($anonymousShape->makeOffsetRequired($other));
		$r["anonymous shape searchArray $otherName"] = $viewR($anonymousShape->searchArray($other));
		$r["anonymous shape intersectKeyArray $otherName"] = $viewR($anonymousShape->intersectKeyArray($other));
		$r["anonymous shape tryRemove $otherName"] = $viewR($anonymousShape->tryRemove($other));
		$r["anonymous shape other isSuperTypeOf $otherName"] = $viewR($other->isSuperTypeOf($anonymousShape));
		$r["anonymous shape union $otherName"] = $viewR(\PHPStan\Type\TypeCombinator::union($anonymousShape, $other));
	}
	foreach ($subjects as $otherName => $otherSubject) {
		$r["anonymous shape isKeysSupersetOf $otherName"] = [$anonymousShape->isKeysSupersetOf($otherSubject), $otherSubject->isKeysSupersetOf($anonymousShape)];
		$r["anonymous shape mergeWith $otherName"] = [$viewR($anonymousShape->mergeWith($otherSubject)), $viewR($otherSubject->mergeWith($anonymousShape))];
		$r["anonymous shape equals $otherName"] = [$anonymousShape->equals($otherSubject), $otherSubject->equals($anonymousShape)];
	}
	$r['anonymous shape sliceArray'] = [$viewR($anonymousShape->sliceArray($others['int1'], $others['int2'], $no)), $viewR($anonymousShape->sliceArray($others['int-1'], $others['null'], $yes))];
	$r['anonymous shape spliceArray'] = $viewR($anonymousShape->spliceArray($others['int1'], $others['int1'], $others['constArray']));
	$r['anonymous shape chunkArray'] = [$viewR($anonymousShape->chunkArray($others['int2'], $no)), $viewR($anonymousShape->chunkArray($others['int'], $yes))];
	$r['anonymous shape truncateListToSize'] = $viewR($anonymousShape->truncateListToSize($others['range1-3']));
	$r['anonymous shape traverse'] = [$viewR($anonymousShape->traverse($identity)), $viewR($anonymousShape->traverse($toObject))];
	$r['anonymous shape mapValueType'] = $viewR($anonymousShape->mapValueType($toObject));
	$r['anonymous shape changeKeyCaseArray'] = $viewR($anonymousShape->changeKeyCaseArray(CASE_UPPER));
	$r['anonymous shape generalize'] = [$viewR($anonymousShape->generalize(\PHPStan\Type\GeneralizePrecision::moreSpecific())), $viewR($anonymousShape->generalize(\PHPStan\Type\GeneralizePrecision::templateArgument()))];
	$r['anonymous shape inferTemplateTypes'] = $viewR($subjects['templateValues']->inferTemplateTypes($anonymousShape));
	$r['anonymous shape getCallableParametersAcceptors'] = $catch(static fn () => $anonymousShape->getCallableParametersAcceptors($outOfClassScope));
	foreach ($r as $key => $value) {
		$observations["constantArray $key"] = $value;
	}
}


// ---- UnionType / BenevolentUnionType / IntersectionType ----
// the compound family over everything above: unions of scalars, nullable
// unions, unions of constants (the FiniteTypeSet shortcuts), unions holding
// generic objects, benevolent unions, the PHP TemplateUnionType /
// TemplateBenevolentUnionType / TemplateIntersectionType over the native
// parents, intersections of a string with accessories, of arrays with the
// list/non-empty/offset accessories, of objects with HasMethodType /
// HasPropertyType, callable arrays and strings, and unions of
// intersections; the string family's reflection provider stays registered
$compoundPhpVersions = [new \PHPStan\Php\PhpVersion(70400), new \PHPStan\Php\PhpVersion(80400)];
$compoundScope = \PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('compound');
$compoundTemplateT = \PHPStan\Type\Generic\TemplateTypeFactory::create($compoundScope, 'T', null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
$compoundTemplateObject = \PHPStan\Type\Generic\TemplateTypeFactory::create($compoundScope, 'O', new \PHPStan\Type\ObjectType(\stdClass::class), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
$compoundConstShape = static function (array $entries, array $optionalKeys = []): \PHPStan\Type\Constant\ConstantArrayType {
	$keyTypes = [];
	$valueTypes = [];
	foreach ($entries as $key => $valueType) {
		$keyTypes[] = is_int($key) ? new \PHPStan\Type\Constant\ConstantIntegerType($key) : new \PHPStan\Type\Constant\ConstantStringType($key);
		$valueTypes[] = $valueType;
	}
	return new \PHPStan\Type\Constant\ConstantArrayType($keyTypes, $valueTypes, [count($keyTypes)], $optionalKeys);
};
$compoundOthers = static fn (string $union, string $benevolent, string $intersection): array => [
	'int' => new \PHPStan\Type\IntegerType(),
	'int0' => new \PHPStan\Type\Constant\ConstantIntegerType(0),
	'int1' => new \PHPStan\Type\Constant\ConstantIntegerType(1),
	'int2' => new \PHPStan\Type\Constant\ConstantIntegerType(2),
	'int-1' => new \PHPStan\Type\Constant\ConstantIntegerType(-1),
	'range0-max' => \PHPStan\Type\IntegerRangeType::fromInterval(0, null),
	'range1-max' => \PHPStan\Type\IntegerRangeType::fromInterval(1, null),
	'range0-3' => \PHPStan\Type\IntegerRangeType::fromInterval(0, 3),
	'string' => new \PHPStan\Type\StringType(),
	'stringA' => new \PHPStan\Type\Constant\ConstantStringType('a'),
	'stringB' => new \PHPStan\Type\Constant\ConstantStringType('b'),
	'stringAbc' => new \PHPStan\Type\Constant\ConstantStringType('abc'),
	'stringEmpty' => new \PHPStan\Type\Constant\ConstantStringType(''),
	'string123' => new \PHPStan\Type\Constant\ConstantStringType('123'),
	'stringTrinary' => new \PHPStan\Type\Constant\ConstantStringType(\PHPStan\TrinaryLogic::class),
	'stringStrlen' => new \PHPStan\Type\Constant\ConstantStringType('strlen'),
	'nonEmptyString' => new $intersection([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryNonEmptyStringType()]),
	'numericString' => new $intersection([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryNumericStringType()]),
	'classString' => new \PHPStan\Type\ClassStringType(),
	'float' => new \PHPStan\Type\FloatType(),
	'float1.5' => new \PHPStan\Type\Constant\ConstantFloatType(1.5),
	'bool' => new \PHPStan\Type\BooleanType(),
	'true' => new \PHPStan\Type\Constant\ConstantBooleanType(true),
	'false' => new \PHPStan\Type\Constant\ConstantBooleanType(false),
	'null' => new \PHPStan\Type\NullType(),
	'mixed' => new \PHPStan\Type\MixedType(),
	'mixedExplicit' => new \PHPStan\Type\MixedType(true),
	'mixedMinusNull' => new \PHPStan\Type\MixedType(false, new \PHPStan\Type\NullType()),
	'strictMixed' => new \PHPStan\Type\StrictMixedType(),
	'never' => new \PHPStan\Type\NeverType(),
	'error' => new \PHPStan\Type\ErrorType(),
	'union' => new $union([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]),
	'unionNullable' => new $union([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\NullType()]),
	'unionConsts' => new $union([new \PHPStan\Type\Constant\ConstantIntegerType(1), new \PHPStan\Type\Constant\ConstantIntegerType(2)]),
	'unionConstStrings' => new $union([new \PHPStan\Type\Constant\ConstantStringType('a'), new \PHPStan\Type\Constant\ConstantStringType('b')]),
	'unionConstMixed' => new $union([new \PHPStan\Type\Constant\ConstantIntegerType(1), new \PHPStan\Type\Constant\ConstantStringType('a'), new \PHPStan\Type\NullType()]),
	'unionBools' => new $union([new \PHPStan\Type\Constant\ConstantBooleanType(true), new \PHPStan\Type\Constant\ConstantBooleanType(false)]),
	'unionArrays' => new $union([new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()), new \PHPStan\Type\NullType()]),
	'unionObjects' => new $union([new \PHPStan\Type\ObjectType(\stdClass::class), new \PHPStan\Type\ObjectType(\PHPStan\TrinaryLogic::class)]),
	'benevolent' => new $benevolent([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]),
	'benevolentNullable' => new $benevolent([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType(), new \PHPStan\Type\NullType()]),
	'array' => new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()),
	'arrayIntString' => new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()),
	'arrayStringInt' => new \PHPStan\Type\ArrayType(new \PHPStan\Type\StringType(), new \PHPStan\Type\IntegerType()),
	'list' => new $intersection([new \PHPStan\Type\ArrayType(\PHPStan\Type\IntegerRangeType::createAllGreaterThanOrEqualTo(0), new \PHPStan\Type\StringType()), new \PHPStan\Type\Accessory\AccessoryArrayListType()]),
	'nonEmptyArray' => new $intersection([new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()), new \PHPStan\Type\Accessory\NonEmptyArrayType()]),
	'nonEmptyList' => new $intersection([new \PHPStan\Type\ArrayType(\PHPStan\Type\IntegerRangeType::createAllGreaterThanOrEqualTo(0), new \PHPStan\Type\IntegerType()), new \PHPStan\Type\Accessory\NonEmptyArrayType(), new \PHPStan\Type\Accessory\AccessoryArrayListType()]),
	'arrayWithOffsetA' => new $intersection([new \PHPStan\Type\ArrayType(new \PHPStan\Type\StringType(), new \PHPStan\Type\IntegerType()), new \PHPStan\Type\Accessory\HasOffsetValueType(new \PHPStan\Type\Constant\ConstantStringType('a'), new \PHPStan\Type\IntegerType()), new \PHPStan\Type\Accessory\NonEmptyArrayType()]),
	'emptyArray' => new \PHPStan\Type\Constant\ConstantArrayType([], []),
	'constArray' => $compoundConstShape(['a' => new \PHPStan\Type\IntegerType()]),
	'constArrayInts' => $compoundConstShape([0 => new \PHPStan\Type\Constant\ConstantStringType('x'), 1 => new \PHPStan\Type\Constant\ConstantStringType('y')]),
	'constArrayOptional' => $compoundConstShape(['a' => new \PHPStan\Type\IntegerType(), 'b' => new \PHPStan\Type\StringType()], [0]),
	'object' => new \PHPStan\Type\ObjectType(\stdClass::class),
	'objectTrinary' => new \PHPStan\Type\ObjectType(\PHPStan\TrinaryLogic::class),
	'objectDateTimeInterface' => new \PHPStan\Type\ObjectType(\DateTimeInterface::class),
	'objectThrowable' => new \PHPStan\Type\ObjectType(\Throwable::class),
	'objectWithoutClass' => new \PHPStan\Type\ObjectWithoutClassType(),
	'genericArrayObject' => new \PHPStan\Type\Generic\GenericObjectType(\ArrayObject::class, [new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]),
	'callable' => new \PHPStan\Type\CallableType(),
	'closure' => new \PHPStan\Type\ClosureType([], new \PHPStan\Type\IntegerType()),
	'iterable' => new \PHPStan\Type\IterableType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()),
	'templateT' => $compoundTemplateT,
	'templateObject' => $compoundTemplateObject,
	'nonEmpty' => new \PHPStan\Type\Accessory\NonEmptyArrayType(),
	'listAccessory' => new \PHPStan\Type\Accessory\AccessoryArrayListType(),
	'hasOffset0' => new \PHPStan\Type\Accessory\HasOffsetType(new \PHPStan\Type\Constant\ConstantIntegerType(0)),
	'hasMethodFoo' => new \PHPStan\Type\Accessory\HasMethodType('foo'),
	'hasPropertyBar' => new \PHPStan\Type\Accessory\HasPropertyType('bar'),
	'falsey' => \PHPStan\Type\StaticTypeFactory::falsey(),
];
{
	$unionClass = \PHPStan\Type\UnionType::class;
	$benevolentClass = \PHPStan\Type\BenevolentUnionType::class;
	$intersectionClass = \PHPStan\Type\IntersectionType::class;
	$r = [];
	$others = $compoundOthers($unionClass, $benevolentClass, $intersectionClass);
	$subjects = [
		'intString' => new $unionClass([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]),
		'nullableInt' => new $unionClass([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\NullType()]),
		'nullableObject' => new $unionClass([new \PHPStan\Type\ObjectType(\stdClass::class), new \PHPStan\Type\NullType()]),
		'scalars' => new $unionClass([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\FloatType(), new \PHPStan\Type\StringType(), new \PHPStan\Type\BooleanType()]),
		'consts' => new $unionClass([new \PHPStan\Type\Constant\ConstantIntegerType(1), new \PHPStan\Type\Constant\ConstantIntegerType(2), new \PHPStan\Type\Constant\ConstantStringType('a')]),
		'constStringsNullable' => new $unionClass([new \PHPStan\Type\Constant\ConstantStringType('a'), new \PHPStan\Type\Constant\ConstantStringType('b'), new \PHPStan\Type\NullType()]),
		'bools' => new $unionClass([new \PHPStan\Type\Constant\ConstantBooleanType(true), new \PHPStan\Type\Constant\ConstantBooleanType(false)]),
		'constWithString' => new $unionClass([new \PHPStan\Type\Constant\ConstantIntegerType(1), new \PHPStan\Type\StringType()]),
		'duplicateConsts' => new $unionClass([new \PHPStan\Type\Constant\ConstantIntegerType(1), new \PHPStan\Type\Constant\ConstantIntegerType(1)]),
		'normalized' => new $unionClass([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()], true),
		'objects' => new $unionClass([new \PHPStan\Type\ObjectType(\stdClass::class), new \PHPStan\Type\ObjectType(\PHPStan\TrinaryLogic::class)]),
		'dateTimes' => new $unionClass([new \PHPStan\Type\ObjectType(\DateTimeImmutable::class), new \PHPStan\Type\ObjectType(\DateTime::class)]),
		'throwables' => new $unionClass([new \PHPStan\Type\ObjectType(\Error::class), new \PHPStan\Type\ObjectType(\Exception::class), new \PHPStan\Type\NullType()]),
		'generics' => new $unionClass([new \PHPStan\Type\Generic\GenericObjectType(\ArrayObject::class, [new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]), new \PHPStan\Type\Generic\GenericObjectType(\ArrayIterator::class, [new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()])]),
		'callables' => new $unionClass([new \PHPStan\Type\CallableType(), new \PHPStan\Type\ClosureType([], new \PHPStan\Type\IntegerType()), new \PHPStan\Type\NullType()]),
		'arrays' => new $unionClass([new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()), $compoundConstShape(['a' => new \PHPStan\Type\IntegerType()])]),
		'arraysNullable' => new $unionClass([new \PHPStan\Type\ArrayType(new \PHPStan\Type\StringType(), new \PHPStan\Type\IntegerType()), new \PHPStan\Type\NullType()]),
		'intersections' => new $unionClass([new $intersectionClass([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryNonEmptyStringType()]), new $intersectionClass([new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()), new \PHPStan\Type\Accessory\NonEmptyArrayType()])]),
		'templateMember' => new $unionClass([$compoundTemplateObject, new \PHPStan\Type\NullType()]),
		'templateMembers' => new $unionClass([$compoundTemplateT, $compoundTemplateObject, new \PHPStan\Type\IntegerType()]),
		'stringsMany' => new $unionClass(array_map(static fn (int $i) => new \PHPStan\Type\Constant\ConstantStringType('s' . $i), range(1, 40))),
		'benevolent' => new $benevolentClass([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]),
		'benevolentNullable' => new $benevolentClass([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType(), new \PHPStan\Type\NullType()]),
		'benevolentArrays' => new $benevolentClass([new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()), $compoundConstShape(['a' => new \PHPStan\Type\IntegerType()])]),
		'benevolentConsts' => new $benevolentClass([new \PHPStan\Type\Constant\ConstantIntegerType(1), new \PHPStan\Type\Constant\ConstantStringType('a')]),
		'benevolentObjects' => new $benevolentClass([new \PHPStan\Type\ObjectType(\stdClass::class), new \PHPStan\Type\StringType()]),
		'templateUnion' => \PHPStan\Type\Generic\TemplateTypeFactory::create($compoundScope, 'U', new $unionClass([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
		'templateBenevolentUnion' => \PHPStan\Type\Generic\TemplateTypeFactory::create($compoundScope, 'B', new $benevolentClass([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
		'nonEmptyString' => new $intersectionClass([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryNonEmptyStringType()]),
		'nonFalsyNonEmptyString' => new $intersectionClass([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryNonEmptyStringType(), new \PHPStan\Type\Accessory\AccessoryNonFalsyStringType()]),
		'numericLowercase' => new $intersectionClass([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryNumericStringType(), new \PHPStan\Type\Accessory\AccessoryLowercaseStringType(), new \PHPStan\Type\Accessory\AccessoryUppercaseStringType()]),
		'literalDecimal' => new $intersectionClass([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryLiteralStringType(), new \PHPStan\Type\Accessory\AccessoryDecimalIntegerStringType()]),
		'list' => new $intersectionClass([new \PHPStan\Type\ArrayType(\PHPStan\Type\IntegerRangeType::createAllGreaterThanOrEqualTo(0), new \PHPStan\Type\IntegerType()), new \PHPStan\Type\Accessory\AccessoryArrayListType()]),
		'listOfMixed' => new $intersectionClass([new \PHPStan\Type\ArrayType(\PHPStan\Type\IntegerRangeType::createAllGreaterThanOrEqualTo(0), new \PHPStan\Type\MixedType()), new \PHPStan\Type\Accessory\AccessoryArrayListType()]),
		'nonEmptyList' => new $intersectionClass([new \PHPStan\Type\ArrayType(\PHPStan\Type\IntegerRangeType::createAllGreaterThanOrEqualTo(0), new \PHPStan\Type\IntegerType()), new \PHPStan\Type\Accessory\NonEmptyArrayType(), new \PHPStan\Type\Accessory\AccessoryArrayListType()]),
		'nonEmptyArray' => new $intersectionClass([new \PHPStan\Type\ArrayType(new \PHPStan\Type\StringType(), new \PHPStan\Type\IntegerType()), new \PHPStan\Type\Accessory\NonEmptyArrayType()]),
		'nonEmptyMixedArray' => new $intersectionClass([new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()), new \PHPStan\Type\Accessory\NonEmptyArrayType()]),
		'listWithOffsets' => new $intersectionClass([new \PHPStan\Type\ArrayType(\PHPStan\Type\IntegerRangeType::createAllGreaterThanOrEqualTo(0), new \PHPStan\Type\StringType()), new \PHPStan\Type\Accessory\AccessoryArrayListType(), new \PHPStan\Type\Accessory\HasOffsetValueType(new \PHPStan\Type\Constant\ConstantIntegerType(0), new \PHPStan\Type\Constant\ConstantStringType('x')), new \PHPStan\Type\Accessory\HasOffsetType(new \PHPStan\Type\Constant\ConstantIntegerType(1)), new \PHPStan\Type\Accessory\NonEmptyArrayType()]),
		'arrayWithOffsetA' => new $intersectionClass([new \PHPStan\Type\ArrayType(new \PHPStan\Type\StringType(), new \PHPStan\Type\IntegerType()), new \PHPStan\Type\Accessory\HasOffsetValueType(new \PHPStan\Type\Constant\ConstantStringType('a'), new \PHPStan\Type\IntegerType()), new \PHPStan\Type\Accessory\NonEmptyArrayType()]),
		'oversized' => new $intersectionClass([new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()), new \PHPStan\Type\Accessory\OversizedArrayType()]),
		'constShapeNonEmpty' => new $intersectionClass([$compoundConstShape(['a' => new \PHPStan\Type\IntegerType(), 'b' => new \PHPStan\Type\StringType()], [0, 1]), new \PHPStan\Type\Accessory\NonEmptyArrayType()]),
		'constShapeList' => new $intersectionClass([$compoundConstShape([0 => new \PHPStan\Type\IntegerType(), 1 => new \PHPStan\Type\StringType()], [1]), new \PHPStan\Type\Accessory\AccessoryArrayListType(), new \PHPStan\Type\Accessory\NonEmptyArrayType()]),
		'objectWithMethod' => new $intersectionClass([new \PHPStan\Type\ObjectWithoutClassType(), new \PHPStan\Type\Accessory\HasMethodType('foo')]),
		'objectWithProperty' => new $intersectionClass([new \PHPStan\Type\ObjectType(\stdClass::class), new \PHPStan\Type\Accessory\HasPropertyType('bar')]),
		'objectWithBoth' => new $intersectionClass([new \PHPStan\Type\ObjectType(\PHPStan\TrinaryLogic::class), new \PHPStan\Type\Accessory\HasMethodType('yes'), new \PHPStan\Type\Accessory\HasPropertyType('bar')]),
		'callableArray' => new $intersectionClass([new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()), new \PHPStan\Type\CallableType()]),
		'callableString' => new $intersectionClass([new \PHPStan\Type\StringType(), new \PHPStan\Type\CallableType()]),
		'callableObject' => new $intersectionClass([new \PHPStan\Type\ObjectWithoutClassType(), new \PHPStan\Type\CallableType()]),
		'templateArrayList' => new $intersectionClass([new \PHPStan\Type\Generic\TemplateArrayType($compoundScope, new \PHPStan\Type\Generic\TemplateTypeParameterStrategy(), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant(), 'A', new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()), null), new \PHPStan\Type\Accessory\AccessoryArrayListType(), new \PHPStan\Type\Accessory\NonEmptyArrayType()]),
		'templateIntersection' => \PHPStan\Type\Generic\TemplateTypeFactory::create($compoundScope, 'I', new $intersectionClass([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryNonEmptyStringType()]), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
	];
	$compoundOutOfClassScope = new \PHPStan\Analyser\OutOfClassScope();
	$identity = static fn ($t) => $t;
	$toObject = static fn ($t) => new \PHPStan\Type\ObjectType(\stdClass::class);
	$toNever = static fn ($t) => new \PHPStan\Type\NeverType();
	$keepScalars = static fn (\PHPStan\Type\Type $t): bool => $t->isScalar()->yes();
	$keepNone = static fn (\PHPStan\Type\Type $t): bool => false;
	$probeOthers = $others + ['self:intString' => $subjects['intString'], 'self:consts' => $subjects['consts'], 'self:benevolent' => $subjects['benevolent'], 'self:templateUnion' => $subjects['templateUnion'], 'self:nonEmptyString' => $subjects['nonEmptyString'], 'self:nonEmptyList' => $subjects['nonEmptyList'], 'self:listWithOffsets' => $subjects['listWithOffsets'], 'self:callableArray' => $subjects['callableArray'], 'self:objectWithMethod' => $subjects['objectWithMethod'], 'self:templateIntersection' => $subjects['templateIntersection'], 'self:intersections' => $subjects['intersections']];
	foreach ($subjects as $name => $subject) {
		$r["$name class"] = $view($subject);
		$r["$name instanceof"] = [$subject instanceof \PHPStan\Type\Type, $subject instanceof \PHPStan\Type\CompoundType, $subject instanceof $unionClass, $subject instanceof $benevolentClass, $subject instanceof $intersectionClass, $subject instanceof \PHPStan\Type\Generic\TemplateType];
		$r["$name getTypes"] = $view($subject->getTypes());
		foreach (['typeOnly' => \PHPStan\Type\VerbosityLevel::typeOnly(), 'value' => \PHPStan\Type\VerbosityLevel::value(), 'precise' => \PHPStan\Type\VerbosityLevel::precise(), 'cache' => \PHPStan\Type\VerbosityLevel::cache()] as $levelName => $level) {
			$r["$name describe $levelName"] = $subject->describe($level);
			// the per-level cache must read back the same
			$r["$name describe $levelName again"] = $subject->describe($level);
		}
		foreach ($probeOthers as $otherName => $other) {
			$r["$name isSuperTypeOf $otherName"] = $view($subject->isSuperTypeOf($other));
			$r["$name accepts $otherName"] = $view($subject->accepts($other, true));
			$r["$name accepts-loose $otherName"] = $view($subject->accepts($other, false));
			$r["$name equals $otherName"] = $subject->equals($other);
			$r["$name tryRemove $otherName"] = $view($subject->tryRemove($other));
			$r["$name isSubTypeOf $otherName"] = $view($subject->isSubTypeOf($other));
			$r["$name isAcceptedBy $otherName"] = $view($subject->isAcceptedBy($other, true));
			$r["$name isAcceptedBy-loose $otherName"] = $view($subject->isAcceptedBy($other, false));
			foreach ($compoundPhpVersions as $vi => $phpVersion) {
				$r["$name looseCompare $otherName $vi"] = $view($subject->looseCompare($other, $phpVersion));
				$r["$name isSmallerThan $otherName $vi"] = $view($subject->isSmallerThan($other, $phpVersion));
				$r["$name isSmallerThanOrEqual $otherName $vi"] = $view($subject->isSmallerThanOrEqual($other, $phpVersion));
				$r["$name isGreaterThan $otherName $vi"] = $view($subject->isGreaterThan($other, $phpVersion));
				$r["$name isGreaterThanOrEqual $otherName $vi"] = $view($subject->isGreaterThanOrEqual($other, $phpVersion));
			}
			$r["$name traverseSimultaneously $otherName"] = $view($subject->traverseSimultaneously($other, static fn ($a, $b) => $a));
			$r["$name traverseSimultaneously-right $otherName"] = $view($subject->traverseSimultaneously($other, static fn ($a, $b) => $b));
			$r["$name getOffsetValueType $otherName"] = $view($subject->getOffsetValueType($other));
			$r["$name getOffsetValueType $otherName again"] = $view($subject->getOffsetValueType($other));
			$r["$name hasOffsetValueType $otherName"] = $view($subject->hasOffsetValueType($other));
			$r["$name hasOffsetValueType $otherName again"] = $view($subject->hasOffsetValueType($other));
			try {
				$r["$name setOffsetValueType $otherName"] = [$view($subject->setOffsetValueType($other, $others['int1'])), $view($subject->setOffsetValueType($other, $others['stringAbc'], false)), $view($subject->setOffsetValueType($other, $others['constArray']))];
			} catch (\PHPStan\ShouldNotHappenException $e) {
				$r["$name setOffsetValueType $otherName"] = 'ShouldNotHappenException';
			}
			$r["$name setExistingOffsetValueType $otherName"] = $view($subject->setExistingOffsetValueType($other, $others['int1']));
			$r["$name unsetOffset $otherName"] = $view($subject->unsetOffset($other));
			$r["$name inferTemplateTypes $otherName"] = $view($subject->inferTemplateTypes($other));
			$r["$name inferTemplateTypesOn $otherName"] = $view($subject->inferTemplateTypesOn($other));
			$r["$name fillKeysArray $otherName"] = $view($subject->fillKeysArray($other));
			$r["$name intersectKeyArray $otherName"] = $view($subject->intersectKeyArray($other));
			$r["$name truncateListToSize $otherName"] = $view($subject->truncateListToSize($other));
			$r["$name searchArray $otherName"] = [$view($subject->searchArray($other)), $view($subject->searchArray($other, \PHPStan\TrinaryLogic::createYes()))];
			$r["$name chunkArray $otherName"] = [$view($subject->chunkArray($other, \PHPStan\TrinaryLogic::createNo())), $view($subject->chunkArray($other, \PHPStan\TrinaryLogic::createYes()))];
			$r["$name sliceArray $otherName"] = [$view($subject->sliceArray($other, $other, \PHPStan\TrinaryLogic::createMaybe())), $view($subject->sliceArray($others['int0'], $other, \PHPStan\TrinaryLogic::createNo())), $view($subject->sliceArray($others['int0'], $others['range1-max'], \PHPStan\TrinaryLogic::createYes()))];
			$r["$name spliceArray $otherName"] = [$view($subject->spliceArray($other, $other, $other)), $view($subject->spliceArray($others['int1'], $others['int0'], $other))];
			$r["$name getKeysArrayFiltered $otherName"] = $view($subject->getKeysArrayFiltered($other, \PHPStan\TrinaryLogic::createYes()));
			$r["$name exponentiate $otherName"] = $view($subject->exponentiate($other));
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
			try {
				$r["$name $method"] = $view($subject->$method());
				// the memoized answers (isNull, isCallable, isList, getFiniteTypes, ...) must read back the same
				$r["$name $method again"] = $view($subject->$method());
			} catch (\TypeError $e) {
				// a union of constant booleans: toBoolean() hands back $this, which its return type refuses
				$r["$name $method"] = ['TypeError', $e->getMessage()];
			}
		}
		foreach (['getSmallerType', 'getSmallerOrEqualType', 'getGreaterType', 'getGreaterOrEqualType'] as $method) {
			$r["$name $method"] = $view($subject->$method($compoundPhpVersions[1]));
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
		$r["$name hasProperty"] = [$view($subject->hasProperty('x')), $view($subject->hasProperty('bar'))];
		$r["$name hasInstanceProperty"] = $view($subject->hasInstanceProperty('bar'));
		$r["$name hasStaticProperty"] = $view($subject->hasStaticProperty('bar'));
		$r["$name hasMethod"] = [$view($subject->hasMethod('x')), $view($subject->hasMethod('foo')), $view($subject->hasMethod('yes'))];
		$r["$name hasConstant"] = [$view($subject->hasConstant('X')), $view($subject->hasConstant('YES'))];
		$r["$name setOffsetValueType null"] = [$view($subject->setOffsetValueType(null, $others['int1'])), $view($subject->setOffsetValueType(null, $others['stringAbc'], false)), $view($subject->setOffsetValueType(null, $others['constArray'], unionValues: true))];
		$r["$name mapValueType"] = [$view($subject->mapValueType($identity)), $view($subject->mapValueType($toObject))];
		$r["$name mapKeyType"] = [$view($subject->mapKeyType($identity)), $view($subject->mapKeyType(static fn ($t) => new \PHPStan\Type\StringType()))];
		$r["$name changeKeyCaseArray"] = [$view($subject->changeKeyCaseArray(null)), $view($subject->changeKeyCaseArray(CASE_LOWER)), $view($subject->changeKeyCaseArray(CASE_UPPER))];
		$r["$name reverseArray"] = [$view($subject->reverseArray(\PHPStan\TrinaryLogic::createYes())), $view($subject->reverseArray(\PHPStan\TrinaryLogic::createNo()))];
		$r["$name toClassConstantType"] = $view($subject->toClassConstantType($stringReflectionProvider));
		$r["$name toObjectTypeForIsACheck"] = [$view($subject->toObjectTypeForIsACheck($others['mixed'], true, true)), $view($subject->toObjectTypeForIsACheck($others['object'], false, false))];
		foreach (['getProperty', 'getInstanceProperty', 'getStaticProperty', 'getMethod', 'getConstant'] as $method) {
			foreach (['x', 'bar', 'foo', 'yes', 'YES'] as $memberName) {
				try {
					$args = $method === 'getConstant' ? [$memberName] : [$memberName, $compoundOutOfClassScope];
					$member = $subject->$method(...$args);
					$r["$name $method $memberName"] = [get_class($member), $member->getName(), $member->getDeclaringClass()->getName()];
				} catch (\Throwable $e) {
					$r["$name $method $memberName"] = [get_class($e), $e->getMessage()];
				}
			}
		}
		foreach (['getUnresolvedPropertyPrototype', 'getUnresolvedInstancePropertyPrototype', 'getUnresolvedStaticPropertyPrototype', 'getUnresolvedMethodPrototype'] as $method) {
			foreach (['x', 'bar', 'foo', 'yes'] as $memberName) {
				try {
					$prototype = $subject->$method($memberName, $compoundOutOfClassScope);
					$transformed = $method === 'getUnresolvedMethodPrototype' ? $prototype->getTransformedMethod() : $prototype->getTransformedProperty();
					$naive = $method === 'getUnresolvedMethodPrototype' ? $prototype->getNakedMethod() : $prototype->getNakedProperty();
					$withStatic = $prototype->doNotResolveTemplateTypeMapToBounds();
					$r["$name $method $memberName"] = [get_class($prototype), get_class($transformed), $transformed->getName(), get_class($naive), get_class($withStatic)];
				} catch (\Throwable $e) {
					$r["$name $method $memberName"] = [get_class($e), $e->getMessage()];
				}
			}
		}
		try {
			$acceptors = $subject->getCallableParametersAcceptors($compoundOutOfClassScope);
			$r["$name getCallableParametersAcceptors"] = array_map(static fn ($acceptor) => [get_class($acceptor), $acceptor->getReturnType()->describe(\PHPStan\Type\VerbosityLevel::precise()), count($acceptor->getParameters())], $acceptors);
		} catch (\Throwable $e) {
			$r["$name getCallableParametersAcceptors"] = get_class($e);
		}
		if ($subject instanceof $unionClass) {
			$r["$name isNormalized"] = $subject->isNormalized();
			$finiteTypeSet = $subject->getFiniteTypeSet();
			$r["$name getFiniteTypeSet"] = $finiteTypeSet === null ? null : [get_class($finiteTypeSet), $finiteTypeSet->isComplete(), array_keys($finiteTypeSet->getMembers()), $view($finiteTypeSet->getOthers())];
			$r["$name getFiniteTypeSet again"] = $subject->getFiniteTypeSet() === $finiteTypeSet;
			$r["$name filterTypes"] = [$view($subject->filterTypes($keepScalars)), $subject->filterTypes(static fn ($t) => true) === $subject, $view($subject->filterTypes($keepNone))];
		}
	}
	// the family through the combinator, the way the analysis exercises it
	foreach (['intString', 'nullableInt', 'consts', 'constStringsNullable', 'objects', 'intersections', 'benevolent', 'benevolentConsts', 'templateUnion', 'nonEmptyString', 'nonEmptyList', 'listWithOffsets', 'arrayWithOffsetA', 'oversized', 'objectWithMethod', 'callableArray', 'templateIntersection'] as $name) {
		$subject = $subjects[$name];
		foreach (['int', 'int1', 'null', 'string', 'stringA', 'union', 'unionNullable', 'unionConsts', 'unionConstMixed', 'benevolent', 'mixed', 'never', 'array', 'arrayIntString', 'list', 'nonEmptyArray', 'nonEmptyList', 'arrayWithOffsetA', 'constArray', 'object', 'objectWithoutClass', 'callable', 'iterable', 'nonEmpty', 'listAccessory', 'hasMethodFoo', 'hasPropertyBar', 'templateT', 'falsey'] as $otherName) {
			$other = $others[$otherName];
			$r["combinator union $name $otherName"] = $view(\PHPStan\Type\TypeCombinator::union($subject, $other));
			$r["combinator intersect $name $otherName"] = $view(\PHPStan\Type\TypeCombinator::intersect($subject, $other));
			$r["combinator remove $name $otherName"] = $view(\PHPStan\Type\TypeCombinator::remove($subject, $other));
			$r["combinator remove-reverse $name $otherName"] = $view(\PHPStan\Type\TypeCombinator::remove($other, $subject));
			$r["other isSuperTypeOf $name $otherName"] = $view($other->isSuperTypeOf($subject));
			$r["other accepts $name $otherName"] = $view($other->accepts($subject, true));
			$r["other equals $name $otherName"] = $other->equals($subject);
			$r["other tryRemove $name $otherName"] = $view($other->tryRemove($subject));
		}
		$r["combinator removeNull $name"] = $view(\PHPStan\Type\TypeCombinator::removeNull($subject));
		$r["combinator addNull $name"] = $view(\PHPStan\Type\TypeCombinator::addNull($subject));
		$r["combinator containsNull $name"] = \PHPStan\Type\TypeCombinator::containsNull($subject);
	}
	// the unions among themselves: the finite-set shortcuts of accepts()/isSuperTypeOf()/equals()/tryRemove()
	foreach (['intString', 'nullableInt', 'consts', 'constStringsNullable', 'bools', 'constWithString', 'duplicateConsts', 'stringsMany', 'benevolentConsts', 'templateUnion', 'templateBenevolentUnion'] as $name) {
		foreach (['intString', 'nullableInt', 'consts', 'constStringsNullable', 'bools', 'constWithString', 'duplicateConsts', 'stringsMany', 'benevolentConsts', 'templateUnion', 'templateBenevolentUnion'] as $otherName) {
			$subject = $subjects[$name];
			$other = $subjects[$otherName];
			$r["union-union isSuperTypeOf $name $otherName"] = $view($subject->isSuperTypeOf($other));
			$r["union-union accepts $name $otherName"] = $view($subject->accepts($other, true));
			$r["union-union equals $name $otherName"] = $subject->equals($other);
			$r["union-union tryRemove $name $otherName"] = $view($subject->tryRemove($other));
			$r["union-union isSubTypeOf $name $otherName"] = $view($subject->isSubTypeOf($other));
			$r["union-union isAcceptedBy $name $otherName"] = $view($subject->isAcceptedBy($other, true));
		}
	}
	// equality over fresh instances, and the class constant
	$r['union equals union'] = [(new $unionClass([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]))->equals(new $unionClass([new \PHPStan\Type\StringType(), new \PHPStan\Type\IntegerType()])), (new $unionClass([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]))->equals(new $benevolentClass([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()])), (new $benevolentClass([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]))->equals(new $unionClass([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]))];
	$r['intersection equals intersection'] = [(new $intersectionClass([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryNonEmptyStringType()]))->equals(new $intersectionClass([new \PHPStan\Type\Accessory\AccessoryNonEmptyStringType(), new \PHPStan\Type\StringType()])), (new $intersectionClass([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryNonEmptyStringType()]))->equals(new $intersectionClass([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryNumericStringType()]))];
	$r['union EQUAL_UNION_CLASSES'] = $unionClass::EQUAL_UNION_CLASSES;
	// the constructors: fewer than two members and a nested union throw, named arguments work
	foreach ([[$unionClass, [[new \PHPStan\Type\IntegerType()]]], [$unionClass, [[]]], [$unionClass, [[new \PHPStan\Type\IntegerType(), new $unionClass([new \PHPStan\Type\StringType(), new \PHPStan\Type\NullType()])]]], [$unionClass, [[new \PHPStan\Type\IntegerType(), $subjects['templateUnion']]]], [$benevolentClass, [[new \PHPStan\Type\IntegerType()]]], [$benevolentClass, [[new \PHPStan\Type\IntegerType(), new $unionClass([new \PHPStan\Type\StringType(), new \PHPStan\Type\NullType()])]]], [$intersectionClass, [[new \PHPStan\Type\StringType()]]], [$intersectionClass, [[]]], [$unionClass, ['x']], [$intersectionClass, [null]]] as $i => [$class, $args]) {
		try {
			$r["construct $i"] = $view(new $class(...$args));
		} catch (\PHPStan\ShouldNotHappenException $e) {
			$r["construct $i"] = ['ShouldNotHappenException', $e->getMessage()];
		} catch (\TypeError $e) {
			$r["construct $i"] = 'TypeError';
		}
	}
	$r['union named'] = [$view(new $unionClass(normalized: true, types: [new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()])), (new $unionClass(normalized: true, types: [new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]))->isNormalized()];
	$r['benevolent named'] = [$view(new $benevolentClass(normalized: true, types: [new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()])), (new $benevolentClass(types: [new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]))->isNormalized()];
	$r['intersection named'] = $view(new $intersectionClass(types: [new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryNonEmptyStringType()]));
	// an uninitialized instance: every typed-slot read raises the same Error
	foreach ([$unionClass, $benevolentClass, $intersectionClass] as $uninitializedClass) {
		$uninitialized = (new \ReflectionClass($uninitializedClass))->newInstanceWithoutConstructor();
		foreach (['describe' => [\PHPStan\Type\VerbosityLevel::precise()], 'isSuperTypeOf' => [$others['int']], 'equals' => [$uninitialized], 'getTypes' => [], 'isNormalized' => [], 'getFiniteTypeSet' => [], 'getIterableKeyType' => [], 'isList' => [], 'isNull' => [], 'toPhpDocNode' => [], 'hasTemplateOrLateResolvableType' => [], 'getReferencedClasses' => [], 'isCallable' => [], 'unsetOffset' => [$others['int0']], 'tryRemove' => [$others['int0']], 'inferTemplateTypes' => [$others['int0']]] as $method => $args) {
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
	// PHP subclasses overriding what the natives call through $this: the
	// protected unionResults()/unionTypes()/pickFromTypes() of a union (the
	// callables the parent hands them), and the public methods a template
	// type overrides
	$anonymousUnion = new class ([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType(), new \PHPStan\Type\NullType()]) extends \PHPStan\Type\UnionType {

		protected function unionResults(callable $getResult): \PHPStan\TrinaryLogic
		{
			$results = [];
			foreach ($this->getTypes() as $type) {
				$results[] = $getResult($type);
			}
			return \PHPStan\TrinaryLogic::maxMin(...$results);
		}

		protected function unionTypes(callable $getType): \PHPStan\Type\Type
		{
			$types = [];
			foreach ($this->getTypes() as $type) {
				$types[] = $getType($type);
			}
			return \PHPStan\Type\TypeCombinator::union(...array_reverse($types));
		}

		protected function pickFromTypes(callable $getValues, callable $criteria): array
		{
			$values = [];
			foreach ($this->getTypes() as $type) {
				foreach ($getValues($type) as $value) {
					$values[] = $value;
				}
				$values[] = $criteria($type);
			}
			return $values;
		}

		public function describe(\PHPStan\Type\VerbosityLevel $level): string
		{
			return 'anonymous(' . parent::describe($level) . ')';
		}

		public function isEnum(): \PHPStan\TrinaryLogic
		{
			return \PHPStan\TrinaryLogic::createMaybe();
		}

		public function getEnumCases(): array
		{
			return [];
		}

		public function isInteger(): \PHPStan\TrinaryLogic
		{
			return \PHPStan\TrinaryLogic::createYes();
		}

		protected function getSortedTypes(): array
		{
			return array_reverse(parent::getSortedTypes());
		}

	};
	foreach (['isObject', 'isEnum', 'isNull', 'isInteger', 'isString', 'isCallable', 'isIterable', 'isArray', 'getIterableValueType', 'toBoolean', 'toNumber', 'toString', 'toArrayKey', 'getKeysArray', 'getObjectClassNames', 'getArrays', 'getConstantStrings', 'getEnumCases', 'getEnumCaseObject', 'getConstantScalarTypes', 'getFiniteTypes', 'toPhpDocNode'] as $method) {
		$r["anonymous union $method"] = $view($anonymousUnion->$method());
	}
	foreach (['typeOnly' => \PHPStan\Type\VerbosityLevel::typeOnly(), 'value' => \PHPStan\Type\VerbosityLevel::value(), 'precise' => \PHPStan\Type\VerbosityLevel::precise()] as $levelName => $level) {
		$r["anonymous union describe $levelName"] = $anonymousUnion->describe($level);
	}
	foreach (['int', 'null', 'stringA', 'union', 'unionConsts', 'benevolent', 'objectDateTimeInterface', 'mixed', 'list'] as $otherName) {
		$other = $others[$otherName];
		$r["anonymous union isSuperTypeOf $otherName"] = $view($anonymousUnion->isSuperTypeOf($other));
		$r["anonymous union accepts $otherName"] = $view($anonymousUnion->accepts($other, true));
		$r["anonymous union isSubTypeOf $otherName"] = $view($anonymousUnion->isSubTypeOf($other));
		$r["anonymous union isAcceptedBy $otherName"] = $view($anonymousUnion->isAcceptedBy($other, true));
		$r["anonymous union tryRemove $otherName"] = $view($anonymousUnion->tryRemove($other));
		$r["anonymous union hasOffsetValueType $otherName"] = $view($anonymousUnion->hasOffsetValueType($other));
		$r["anonymous union getOffsetValueType $otherName"] = $view($anonymousUnion->getOffsetValueType($other));
		$r["anonymous union looseCompare $otherName"] = $view($anonymousUnion->looseCompare($other, $compoundPhpVersions[1]));
		$r["anonymous union isGreaterThan $otherName"] = $view($anonymousUnion->isGreaterThan($other, $compoundPhpVersions[1]));
		$r["anonymous union other isSuperTypeOf $otherName"] = $view($other->isSuperTypeOf($anonymousUnion));
		$r["anonymous union other accepts $otherName"] = $view($other->accepts($anonymousUnion, true));
	}
	try {
		$r['anonymous union getProperty'] = $anonymousUnion->getProperty('x', $compoundOutOfClassScope);
	} catch (\Throwable $e) {
		$r['anonymous union getProperty'] = [get_class($e), $e->getMessage()];
	}
	$anonymousBenevolent = new class ([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]) extends \PHPStan\Type\BenevolentUnionType {

		public function getTypes(): array
		{
			return array_reverse(parent::getTypes());
		}

		protected function unionResults(callable $getResult): \PHPStan\TrinaryLogic
		{
			return \PHPStan\TrinaryLogic::createMaybe();
		}

	};
	foreach (['isObject', 'isNull', 'isInteger', 'isString', 'isCallable', 'isArray', 'getIterableValueType', 'toBoolean', 'toString', 'toArrayKey', 'getObjectClassNames', 'getConstantScalarTypes', 'getFiniteTypes', 'toPhpDocNode', 'getArrays'] as $method) {
		$r["anonymous benevolent $method"] = $view($anonymousBenevolent->$method());
	}
	$r['anonymous benevolent describe'] = $anonymousBenevolent->describe(\PHPStan\Type\VerbosityLevel::precise());
	$r['anonymous benevolent traverse'] = [$view($anonymousBenevolent->traverse($identity)), $view($anonymousBenevolent->traverse($toObject))];
	$r['anonymous benevolent filterTypes'] = $view($anonymousBenevolent->filterTypes($keepScalars));
	$r['anonymous benevolent tryRemove'] = [$view($anonymousBenevolent->tryRemove($others['int'])), $view($anonymousBenevolent->tryRemove($others['null']))];
	$r['anonymous benevolent isAcceptedBy'] = $view($anonymousBenevolent->isAcceptedBy($others['string'], true));
	$r['anonymous benevolent getOffsetValueType'] = $view($anonymousBenevolent->getOffsetValueType($others['int0']));
	$anonymousIntersection = new class ([new \PHPStan\Type\ArrayType(\PHPStan\Type\IntegerRangeType::createAllGreaterThanOrEqualTo(0), new \PHPStan\Type\StringType()), new \PHPStan\Type\Accessory\AccessoryArrayListType(), new \PHPStan\Type\Accessory\NonEmptyArrayType()]) extends \PHPStan\Type\IntersectionType {

		public function isList(): \PHPStan\TrinaryLogic
		{
			return \PHPStan\TrinaryLogic::createNo();
		}

		public function isCallable(): \PHPStan\TrinaryLogic
		{
			return \PHPStan\TrinaryLogic::createYes();
		}

		public function equals(\PHPStan\Type\Type $type): bool
		{
			return $type instanceof \PHPStan\Type\IntegerType;
		}

		public function describe(\PHPStan\Type\VerbosityLevel $level): string
		{
			return 'anonymous(' . parent::describe($level) . ')';
		}

	};
	foreach (['isList', 'isArray', 'isConstantArray', 'isIterableAtLeastOnce', 'getArraySize', 'getIterableKeyType', 'getIterableValueType', 'getValuesArray', 'popArray', 'shiftArray', 'shuffleArray', 'getConstantArrays', 'toPhpDocNode', 'toArrayKey', 'isNonEmptyString'] as $method) {
		$r["anonymous intersection $method"] = $view($anonymousIntersection->$method());
	}
	foreach (['typeOnly' => \PHPStan\Type\VerbosityLevel::typeOnly(), 'value' => \PHPStan\Type\VerbosityLevel::value(), 'precise' => \PHPStan\Type\VerbosityLevel::precise()] as $levelName => $level) {
		$r["anonymous intersection describe $levelName"] = $anonymousIntersection->describe($level);
	}
	foreach (['int', 'int0', 'int1', 'stringA', 'list', 'nonEmptyList', 'arrayIntString', 'mixed', 'callable'] as $otherName) {
		$other = $others[$otherName];
		$r["anonymous intersection isSuperTypeOf $otherName"] = $view($anonymousIntersection->isSuperTypeOf($other));
		$r["anonymous intersection accepts $otherName"] = $view($anonymousIntersection->accepts($other, true));
		$r["anonymous intersection isSubTypeOf $otherName"] = $view($anonymousIntersection->isSubTypeOf($other));
		$r["anonymous intersection isAcceptedBy $otherName"] = $view($anonymousIntersection->isAcceptedBy($other, true));
		$r["anonymous intersection hasOffsetValueType $otherName"] = $view($anonymousIntersection->hasOffsetValueType($other));
		$r["anonymous intersection getOffsetValueType $otherName"] = $view($anonymousIntersection->getOffsetValueType($other));
		$r["anonymous intersection setOffsetValueType $otherName"] = $view($anonymousIntersection->setOffsetValueType($other, $others['stringA']));
		$r["anonymous intersection sliceArray $otherName"] = $view($anonymousIntersection->sliceArray($others['int0'], $other, \PHPStan\TrinaryLogic::createNo()));
	}
	try {
		$r['anonymous intersection getCallableParametersAcceptors'] = $view($anonymousIntersection->getCallableParametersAcceptors($compoundOutOfClassScope));
	} catch (\Throwable $e) {
		$r['anonymous intersection getCallableParametersAcceptors'] = get_class($e);
	}
	try {
		$r['anonymous intersection getConstant'] = $anonymousIntersection->getConstant('X');
	} catch (\Throwable $e) {
		$r['anonymous intersection getConstant'] = [get_class($e), $e->getMessage()];
	}
	foreach ($r as $key => $value) {
		$observations["compound $key"] = $value;
	}
}


// ---- VerbosityLevel::getRecommendedLevelByType() over generics ----
// The invariant-template traversal of the recommended level: a generic
// subject's describe() reaches the PHP TypeProjectionHelper with the level
// in hand, which only the real-name declaration lets through — so the
// generic cases live here rather than in smoke.php's prefixed section.
{
	$r = [];
	$arrayObjectReflection = $stringReflectionProvider->getClass(\ArrayObject::class);
	$int = new \PHPStan\Type\IntegerType();
	$string = new \PHPStan\Type\StringType();
	$constString = new \PHPStan\Type\Constant\ConstantStringType('foo');
	$lowercase = new \PHPStan\Type\IntersectionType([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryLowercaseStringType()]);
	$arrayObject = new \PHPStan\Type\Generic\GenericObjectType(\ArrayObject::class, [$int, $string], null, $arrayObjectReflection);
	$covariantList = new \PHPStan\Type\Generic\GenericObjectType(\ArrayObject::class, [$int, $string], null, $arrayObjectReflection, [\PHPStan\Type\Generic\TemplateTypeVariance::createCovariant(), \PHPStan\Type\Generic\TemplateTypeVariance::createCovariant()]);
	$traversable = new \PHPStan\Type\Generic\GenericObjectType(\Traversable::class, [$int, $string], null, $stringReflectionProvider->getClass(\Traversable::class));
	$unionWithGeneric = new \PHPStan\Type\UnionType([$arrayObject, new \PHPStan\Type\NullType()]);
	$cases = [
		'ArrayObject vs string' => [$arrayObject, $string],
		'ArrayObject vs constant string' => [$arrayObject, $constString],
		'ArrayObject vs lowercase' => [$arrayObject, $lowercase],
		'ArrayObject alone' => [$arrayObject, null],
		'ArrayObject vs ArrayObject' => [$arrayObject, $arrayObject],
		'call-site covariant ArrayObject vs constant string' => [$covariantList, $constString],
		'Traversable (covariant templates) vs constant string' => [$traversable, $constString],
		'ArrayObject|null vs constant string' => [$unionWithGeneric, $constString],
		'constant string vs ArrayObject' => [$constString, $arrayObject],
		'string vs ArrayObject' => [$string, $arrayObject],
	];
	foreach ($cases as $label => [$accepting, $accepted]) {
		$r[$label] = \PHPStan\Type\VerbosityLevel::getRecommendedLevelByType($accepting, $accepted)->getLevelValue();
	}
	foreach ($r as $key => $value) {
		$observations["recommended level $key"] = $value;
	}
}

// ---- ErrorType / CircularTypeAliasErrorType / AbsorbedTemplateArgumentType / NonAcceptingNeverType / StringAlwaysAcceptingObjectWithToStringType / StringNeverAcceptingObjectWithToStringType / ResourceType ----
// the small classes: the error family over the native MixedType (the
// $reason slot after the parent's two, describe() through parent::, the
// inherited constructor of the two children), the non-accepting never over
// the native NeverType (parent::__construct(true)), the two string children
// whose isSuperTypeOf()/accepts() ask the reflection provider (registered
// above by the string family, still in place) for a native __toString(),
// and the stateless resource type; PHP subclasses over each native parent
// come along
$smallPhpVersion = new \PHPStan\Php\PhpVersion(80400);
$smallScope = \PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('small');
$smallOthers = static fn (): array => [
	'int' => new \PHPStan\Type\IntegerType(),
	'int0' => new \PHPStan\Type\Constant\ConstantIntegerType(0),
	'float' => new \PHPStan\Type\FloatType(),
	'bool' => new \PHPStan\Type\BooleanType(),
	'true' => new \PHPStan\Type\Constant\ConstantBooleanType(true),
	'null' => new \PHPStan\Type\NullType(),
	'string' => new \PHPStan\Type\StringType(),
	'stringAbc' => new \PHPStan\Type\Constant\ConstantStringType('abc'),
	'stringEmpty' => new \PHPStan\Type\Constant\ConstantStringType(''),
	'nonEmptyString' => new \PHPStan\Type\IntersectionType([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryNonEmptyStringType()]),
	'classString' => new \PHPStan\Type\ClassStringType(),
	'stringAlways' => new \PHPStan\Type\StringAlwaysAcceptingObjectWithToStringType(),
	'stringNever' => new \PHPStan\Type\StringNeverAcceptingObjectWithToStringType(),
	'mixed' => new \PHPStan\Type\MixedType(),
	'mixedExplicit' => new \PHPStan\Type\MixedType(true),
	'mixedMinusInt' => new \PHPStan\Type\MixedType(false, new \PHPStan\Type\IntegerType()),
	'strictMixed' => new \PHPStan\Type\StrictMixedType(),
	'never' => new \PHPStan\Type\NeverType(),
	'neverExplicit' => new \PHPStan\Type\NeverType(true),
	'nonAcceptingNever' => new \PHPStan\Type\NonAcceptingNeverType(),
	'error' => new \PHPStan\Type\ErrorType(),
	'errorReason' => new \PHPStan\Type\ErrorType('because'),
	'circular' => new \PHPStan\Type\CircularTypeAliasErrorType(),
	'absorbed' => new \PHPStan\Type\Generic\AbsorbedTemplateArgumentType(),
	'resource' => new \PHPStan\Type\ResourceType(),
	'templateT' => \PHPStan\Type\Generic\TemplateTypeFactory::create($smallScope, 'T', null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
	'templateInt' => \PHPStan\Type\Generic\TemplateTypeFactory::create($smallScope, 'T', new \PHPStan\Type\IntegerType(), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
	'object' => new \PHPStan\Type\ObjectType(\stdClass::class),
	'exception' => new \PHPStan\Type\ObjectType(\Exception::class),
	'throwable' => new \PHPStan\Type\ObjectType(\Throwable::class),
	'unknownClass' => new \PHPStan\Type\ObjectType('SmallTypeFamily\\DoesNotExist'),
	'unionExceptionError' => new \PHPStan\Type\UnionType([new \PHPStan\Type\ObjectType(\Exception::class), new \PHPStan\Type\ObjectType(\Error::class)]),
	'unionExceptionStd' => new \PHPStan\Type\UnionType([new \PHPStan\Type\ObjectType(\Exception::class), new \PHPStan\Type\ObjectType(\stdClass::class)]),
	'unionExceptionUnknown' => new \PHPStan\Type\UnionType([new \PHPStan\Type\ObjectType(\Exception::class), new \PHPStan\Type\ObjectType('SmallTypeFamily\\DoesNotExist')]),
	'unionStringException' => new \PHPStan\Type\UnionType([new \PHPStan\Type\StringType(), new \PHPStan\Type\ObjectType(\Exception::class)]),
	'objectWithoutClass' => new \PHPStan\Type\ObjectWithoutClassType(),
	'union' => new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]),
	'unionNullable' => new \PHPStan\Type\UnionType([new \PHPStan\Type\StringType(), new \PHPStan\Type\NullType()]),
	'array' => new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()),
	'emptyArray' => new \PHPStan\Type\Constant\ConstantArrayType([], []),
	'callable' => new \PHPStan\Type\CallableType(),
	'closure' => new \PHPStan\Type\ClosureType(),
	'iterable' => new \PHPStan\Type\IterableType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()),
	'void' => new \PHPStan\Type\VoidType(),
];
{
	$r = [];
	$others = $smallOthers();
	$subjects = [
		'error' => new \PHPStan\Type\ErrorType(),
		'errorReason' => new \PHPStan\Type\ErrorType('because'),
		'circular' => new \PHPStan\Type\CircularTypeAliasErrorType(),
		'circularReason' => new \PHPStan\Type\CircularTypeAliasErrorType('circular'),
		'absorbed' => new \PHPStan\Type\Generic\AbsorbedTemplateArgumentType(),
		'absorbedReason' => new \PHPStan\Type\Generic\AbsorbedTemplateArgumentType('absorbed'),
		'nonAcceptingNever' => new \PHPStan\Type\NonAcceptingNeverType(),
		'stringAlways' => new \PHPStan\Type\StringAlwaysAcceptingObjectWithToStringType(),
		'stringNever' => new \PHPStan\Type\StringNeverAcceptingObjectWithToStringType(),
		'resource' => new \PHPStan\Type\ResourceType(),
	];
	foreach ($subjects as $name => $subject) {
		$reflection = new ReflectionClass($subject);
		$r["$name class"] = [get_class($subject), $reflection->isFinal(), $reflection->getParentClass() === false ? null : $reflection->getParentClass()->getName(), $reflection->getConstructor()?->getNumberOfParameters()];
		$r["$name instanceof"] = [$subject instanceof \PHPStan\Type\Type, $subject instanceof \PHPStan\Type\MixedType, $subject instanceof \PHPStan\Type\ErrorType, $subject instanceof \PHPStan\Type\NeverType, $subject instanceof \PHPStan\Type\StringType, $subject instanceof \PHPStan\Type\CompoundType, $subject instanceof \PHPStan\Type\SubtractableType, $subject instanceof \PHPStan\Type\ConstantScalarType];
		foreach (['typeOnly' => \PHPStan\Type\VerbosityLevel::typeOnly(), 'value' => \PHPStan\Type\VerbosityLevel::value(), 'precise' => \PHPStan\Type\VerbosityLevel::precise(), 'cache' => \PHPStan\Type\VerbosityLevel::cache()] as $levelName => $level) {
			$r["$name describe $levelName"] = $subject->describe($level);
		}
		if ($subject instanceof \PHPStan\Type\ErrorType) {
			$r["$name getReason"] = $subject->getReason();
			$r["$name isExplicitMixed"] = $subject->isExplicitMixed();
			$r["$name getSubtractedType"] = $view($subject->getSubtractedType());
			$r["$name getTypeWithoutSubtractedType"] = $view($subject->getTypeWithoutSubtractedType());
			$r["$name changeSubtractedType"] = $view($subject->changeSubtractedType($others['int']));
		}
		if ($subject instanceof \PHPStan\Type\NeverType) {
			$r["$name isExplicit"] = [$subject->isExplicit(), $subject->getReason()];
		}
		foreach ($others as $otherName => $other) {
			$r["$name isSuperTypeOf $otherName"] = $view($subject->isSuperTypeOf($other));
			$r["$name accepts $otherName"] = [$view($subject->accepts($other, true)), $view($subject->accepts($other, false))];
			$r["$name equals $otherName"] = [$subject->equals($other), $other->equals($subject)];
			$r["$otherName isSuperTypeOf $name"] = $view($other->isSuperTypeOf($subject));
			$r["$otherName accepts $name"] = [$view($other->accepts($subject, true)), $view($other->accepts($subject, false))];
			$r["$name tryRemove $otherName"] = $view($subject->tryRemove($other));
			$r["$name looseCompare $otherName"] = $view($subject->looseCompare($other, $smallPhpVersion));
			$r["$name union $otherName"] = $view(\PHPStan\Type\TypeCombinator::union($subject, $other));
			$r["$name intersect $otherName"] = $view(\PHPStan\Type\TypeCombinator::intersect($subject, $other));
			$r["$name remove $otherName"] = $view(\PHPStan\Type\TypeCombinator::remove($subject, $other));
			if ($subject instanceof \PHPStan\Type\CompoundType) {
				$r["$name isSubTypeOf $otherName"] = $view($subject->isSubTypeOf($other));
				$r["$name isAcceptedBy $otherName"] = [$view($subject->isAcceptedBy($other, true)), $view($subject->isAcceptedBy($other, false))];
			}
			if ($subject instanceof \PHPStan\Type\SubtractableType) {
				$r["$name subtract $otherName"] = $view($subject->subtract($other));
			}
		}
		$r["$name getIterableKeyType"] = $view($subject->getIterableKeyType());
		$r["$name getIterableValueType"] = $view($subject->getIterableValueType());
		$r["$name toArray"] = $view($subject->toArray());
		$r["$name toString"] = $view($subject->toString());
		$r["$name toInteger"] = $view($subject->toInteger());
		$r["$name toFloat"] = $view($subject->toFloat());
		$r["$name toNumber"] = $view($subject->toNumber());
		$r["$name toArrayKey"] = $view($subject->toArrayKey());
		$r["$name toBoolean"] = $view($subject->toBoolean());
		$r["$name toAbsoluteNumber"] = $view($subject->toAbsoluteNumber());
		$r["$name toBitwiseNotType"] = $view($subject->toBitwiseNotType());
		$r["$name toPhpDocNode"] = $view($subject->toPhpDocNode());
		$r["$name toCoercedArgumentType"] = [$view($subject->toCoercedArgumentType(true)), $view($subject->toCoercedArgumentType(false))];
		$r["$name exponentiate"] = $view($subject->exponentiate($others['int']));
		$r["$name generalize"] = [$view($subject->generalize(\PHPStan\Type\GeneralizePrecision::lessSpecific())), $view($subject->generalize(\PHPStan\Type\GeneralizePrecision::moreSpecific()))];
		$r["$name traverse"] = $view($subject->traverse(static fn (\PHPStan\Type\Type $t): \PHPStan\Type\Type => $t));
		$r["$name queries"] = $view([$subject->isScalar(), $subject->isOffsetAccessLegal(), $subject->isOffsetAccessible(), $subject->isString(), $subject->isObject(), $subject->isNull(), $subject->isArray(), $subject->isCallable(), $subject->isIterable(), $subject->isVoid(), $subject->isConstantValue(), $subject->isCloneable(), $subject->isEnum(), $subject->getFiniteTypes(), $subject->getConstantStrings(), $subject->getReferencedClasses(), $subject->getObjectClassNames(), $subject->getEnumCases(), $subject->hasTemplateOrLateResolvableType()]);
	}
	// the uninitialized-slot reads the twins' typed properties raise
	foreach (['error' => \PHPStan\Type\ErrorType::class, 'circular' => \PHPStan\Type\CircularTypeAliasErrorType::class, 'absorbed' => \PHPStan\Type\Generic\AbsorbedTemplateArgumentType::class, 'nonAcceptingNever' => \PHPStan\Type\NonAcceptingNeverType::class] as $name => $class) {
		$uninitialized = (new ReflectionClass($class))->newInstanceWithoutConstructor();
		foreach (['getReason', 'describe', 'isSuperTypeOf'] as $method) {
			try {
				$r["$name uninitialized $method"] = $view($method === 'getReason' ? $uninitialized->getReason() : ($method === 'describe' ? $uninitialized->describe(\PHPStan\Type\VerbosityLevel::precise()) : $uninitialized->isSuperTypeOf($others['int'])));
			} catch (\Throwable $e) {
				$r["$name uninitialized $method"] = [get_class($e), $e->getMessage()];
			}
		}
	}
	// a repeated constructor call overwrites the slots in place
	$reconstructed = new \PHPStan\Type\ErrorType('first');
	$reconstructed->__construct('second');
	$r['error reconstruct'] = [$reconstructed->getReason(), $reconstructed->describe(\PHPStan\Type\VerbosityLevel::cache())];
	// PHP subclasses over the native parents: what the natives call through
	// $this / what parent:: keeps bound to the subclass
	$anonymousError = new class ('anonymous') extends \PHPStan\Type\ErrorType {

		public function getReason(): ?string
		{
			return 'overridden';
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
	$r['anonymous error getReason'] = $anonymousError->getReason();
	$r['anonymous error describe'] = [$anonymousError->describe(\PHPStan\Type\VerbosityLevel::typeOnly()), $anonymousError->describe(\PHPStan\Type\VerbosityLevel::precise()), $anonymousError->describe(\PHPStan\Type\VerbosityLevel::cache())];
	$r['anonymous error subtract'] = $view($anonymousError->subtract($others['int']));
	$r['anonymous error getIterableValueType'] = $view($anonymousError->getIterableValueType());
	$r['anonymous error isAcceptedBy'] = $view($anonymousError->isAcceptedBy($others['int'], true));
	$r['anonymous error equals'] = [$anonymousError->equals($subjects['error']), $subjects['error']->equals($anonymousError), $subjects['absorbed']->equals($anonymousError), $subjects['circular']->equals($anonymousError)];
	$r['error isSuperTypeOf anonymous'] = $view($subjects['errorReason']->isSuperTypeOf($anonymousError));
	$anonymousNonAccepting = new class extends \PHPStan\Type\NonAcceptingNeverType {

		public function isSubTypeOf(\PHPStan\Type\Type $otherType): \PHPStan\Type\IsSuperTypeOfResult
		{
			return \PHPStan\Type\IsSuperTypeOfResult::createMaybe();
		}

	};
	$r['anonymous nonAcceptingNever isAcceptedBy'] = $view($anonymousNonAccepting->isAcceptedBy($others['int'], true));
	$r['anonymous nonAcceptingNever isExplicit'] = $anonymousNonAccepting->isExplicit();
	$r['anonymous nonAcceptingNever isSuperTypeOf'] = [$view($anonymousNonAccepting->isSuperTypeOf($subjects['nonAcceptingNever'])), $view($subjects['nonAcceptingNever']->isSuperTypeOf($anonymousNonAccepting)), $view($others['never']->isSuperTypeOf($anonymousNonAccepting))];
	$anonymousStringAlways = new class extends \PHPStan\Type\StringAlwaysAcceptingObjectWithToStringType {

		public function hasOffsetValueType(\PHPStan\Type\Type $offsetType): \PHPStan\TrinaryLogic
		{
			return \PHPStan\TrinaryLogic::createNo();
		}

		public function getObjectClassNames(): array
		{
			return [\Exception::class];
		}

	};
	$r['anonymous stringAlways getOffsetValueType'] = $view($anonymousStringAlways->getOffsetValueType($others['int']));
	$r['anonymous stringAlways isSuperTypeOf'] = [$view($anonymousStringAlways->isSuperTypeOf($others['exception'])), $view($subjects['stringAlways']->isSuperTypeOf($anonymousStringAlways)), $view($subjects['stringNever']->isSuperTypeOf($anonymousStringAlways))];
	$r['anonymous stringAlways accepts'] = [$view($anonymousStringAlways->accepts($others['string'], true)), $view($subjects['stringAlways']->accepts($anonymousStringAlways, true)), $view($subjects['stringNever']->accepts($anonymousStringAlways, false))];
	$anonymousResource = new class extends \PHPStan\Type\ResourceType {

		public function describe(\PHPStan\Type\VerbosityLevel $level): string
		{
			return 'anonymous-resource';
		}

	};
	$r['anonymous resource toArray'] = $view($anonymousResource->toArray());
	$r['anonymous resource equals'] = [$anonymousResource->equals($subjects['resource']), $subjects['resource']->equals($anonymousResource)];
	$r['anonymous resource isSuperTypeOf'] = [$view($anonymousResource->isSuperTypeOf($subjects['resource'])), $view($subjects['resource']->isSuperTypeOf($anonymousResource))];
	$r['anonymous resource accepts'] = [$view($anonymousResource->accepts($subjects['resource'], true)), $view($subjects['resource']->accepts($anonymousResource, true))];
	foreach ($r as $key => $value) {
		$observations["small $key"] = $value;
	}
}


// ---- TypeUtils ----
// the static helpers over the whole family: the class-mapped lookups
// (getConstantIntegers(), getIntegerRanges(), getAccessoryTypes()) through
// unions and intersections, the benevolent/strict union conversions (the
// PHP TemplateBenevolentUnionType included), flattenTypes() over unions and
// the power sets of constant arrays (and its bail-out), findThisType() /
// findCallableType() / getHasPropertyTypes() through compound types,
// containsTemplateType() via TypeTraverser and resolveLateResolvableTypes()
// over key-of / value-of / conditional types; the string family's
// reflection provider stays registered
$observations['native ' . \PHPStan\Type\TypeUtils::class] = (new ReflectionMethod(\PHPStan\Type\TypeUtils::class, 'flattenTypes'))->isInternal();
{
	$r = [];
	$utilsScope = \PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('utils');
	$utilsTemplateT = \PHPStan\Type\Generic\TemplateTypeFactory::create($utilsScope, 'T', null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
	$utilsTemplateInt = \PHPStan\Type\Generic\TemplateTypeFactory::create($utilsScope, 'I', new \PHPStan\Type\IntegerType(), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
	$utilsTemplateUnion = \PHPStan\Type\Generic\TemplateTypeFactory::create($utilsScope, 'U', new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
	$utilsTemplateBenevolent = \PHPStan\Type\Generic\TemplateTypeFactory::create($utilsScope, 'B', new \PHPStan\Type\BenevolentUnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
	$utilsThis = new \PHPStan\Type\ThisType($stringReflectionProvider->getClass(\Exception::class));
	$utilsShape = static function (array $entries, array $optionalKeys = []): \PHPStan\Type\Constant\ConstantArrayType {
		$keyTypes = [];
		$valueTypes = [];
		foreach ($entries as $key => $valueType) {
			$keyTypes[] = is_int($key) ? new \PHPStan\Type\Constant\ConstantIntegerType($key) : new \PHPStan\Type\Constant\ConstantStringType($key);
			$valueTypes[] = $valueType;
		}
		return new \PHPStan\Type\Constant\ConstantArrayType($keyTypes, $valueTypes, [count($keyTypes)], $optionalKeys);
	};
	$utilsWideShape = static function (int $keys, int $optional) use ($utilsShape): \PHPStan\Type\Constant\ConstantArrayType {
		$entries = [];
		for ($i = 0; $i < $keys; $i++) {
			$entries["k$i"] = new \PHPStan\Type\IntegerType();
		}
		return $utilsShape($entries, range(0, $optional - 1));
	};
	$int = new \PHPStan\Type\IntegerType();
	$string = new \PHPStan\Type\StringType();
	$subjects = [
		'int' => $int,
		'int0' => new \PHPStan\Type\Constant\ConstantIntegerType(0),
		'string' => $string,
		'stringStrlen' => new \PHPStan\Type\Constant\ConstantStringType('strlen'),
		'range0-10' => \PHPStan\Type\IntegerRangeType::fromInterval(0, 10),
		'mixed' => new \PHPStan\Type\MixedType(),
		'never' => new \PHPStan\Type\NeverType(),
		'error' => new \PHPStan\Type\ErrorType(),
		'null' => new \PHPStan\Type\NullType(),
		'unionInts' => new \PHPStan\Type\UnionType([new \PHPStan\Type\Constant\ConstantIntegerType(1), new \PHPStan\Type\Constant\ConstantIntegerType(2)]),
		'unionIntString' => new \PHPStan\Type\UnionType([new \PHPStan\Type\Constant\ConstantIntegerType(1), $string]),
		'unionRanges' => new \PHPStan\Type\UnionType([\PHPStan\Type\IntegerRangeType::fromInterval(0, 10), \PHPStan\Type\IntegerRangeType::fromInterval(20, 30)]),
		'unionRangeInt' => new \PHPStan\Type\UnionType([\PHPStan\Type\IntegerRangeType::fromInterval(0, 10), new \PHPStan\Type\Constant\ConstantIntegerType(20)]),
		'unionScalars' => new \PHPStan\Type\UnionType([$int, $string, new \PHPStan\Type\FloatType()]),
		'unionNullable' => new \PHPStan\Type\UnionType([$int, new \PHPStan\Type\NullType()]),
		'benevolent' => new \PHPStan\Type\BenevolentUnionType([$int, $string]),
		'benevolentInts' => new \PHPStan\Type\BenevolentUnionType([new \PHPStan\Type\Constant\ConstantIntegerType(1), new \PHPStan\Type\Constant\ConstantIntegerType(2)]),
		'templateT' => $utilsTemplateT,
		'templateInt' => $utilsTemplateInt,
		'templateUnion' => $utilsTemplateUnion,
		'templateBenevolent' => $utilsTemplateBenevolent,
		'unionWithTemplate' => new \PHPStan\Type\UnionType([$int, $utilsTemplateT]),
		'arrayOfTemplate' => new \PHPStan\Type\ArrayType($int, $utilsTemplateT),
		'genericOfTemplate' => new \PHPStan\Type\Generic\GenericObjectType(\ArrayIterator::class, [$int, $utilsTemplateT]),
		'genericOfInt' => new \PHPStan\Type\Generic\GenericObjectType(\ArrayIterator::class, [$int, $string]),
		'nonEmptyString' => new \PHPStan\Type\IntersectionType([$string, new \PHPStan\Type\Accessory\AccessoryNonEmptyStringType()]),
		'literalNonEmptyString' => new \PHPStan\Type\IntersectionType([$string, new \PHPStan\Type\Accessory\AccessoryNonEmptyStringType(), new \PHPStan\Type\Accessory\AccessoryLiteralStringType()]),
		'unionNonEmptyStringInt' => new \PHPStan\Type\UnionType([new \PHPStan\Type\IntersectionType([$string, new \PHPStan\Type\Accessory\AccessoryNonEmptyStringType()]), $int]),
		'nonEmptyArray' => new \PHPStan\Type\IntersectionType([new \PHPStan\Type\ArrayType($int, $string), new \PHPStan\Type\Accessory\NonEmptyArrayType()]),
		'list' => new \PHPStan\Type\IntersectionType([new \PHPStan\Type\ArrayType(\PHPStan\Type\IntegerRangeType::createAllGreaterThanOrEqualTo(0), $string), new \PHPStan\Type\Accessory\AccessoryArrayListType()]),
		'shape' => $utilsShape(['a' => $int, 'b' => $string]),
		'shapeOptional' => $utilsShape(['a' => $int, 'b' => $string, 'c' => new \PHPStan\Type\BooleanType()], [1, 2]),
		'shapeNonEmpty' => new \PHPStan\Type\IntersectionType([$utilsShape(['a' => $int, 'b' => $string], [0, 1]), new \PHPStan\Type\Accessory\NonEmptyArrayType()]),
		'shapeList' => new \PHPStan\Type\IntersectionType([$utilsShape([0 => $int, 1 => $string], [1]), new \PHPStan\Type\Accessory\AccessoryArrayListType()]),
		'unionShapes' => new \PHPStan\Type\UnionType([$utilsShape(['a' => $int], [0]), $utilsShape(['b' => $string], [0])]),
		'unionShapeInt' => new \PHPStan\Type\UnionType([$utilsShape(['a' => $int, 'b' => $string], [1]), $int]),
		'shapeWide14' => $utilsWideShape(14, 14),
		'shapeWide15' => $utilsWideShape(15, 15),
		'shapeWide21' => $utilsWideShape(21, 21),
		'unionWideShapes' => new \PHPStan\Type\UnionType([$utilsWideShape(8, 8), $utilsWideShape(8, 8)]),
		'shapeOfTemplate' => $utilsShape(['a' => $utilsTemplateT]),
		'this' => $utilsThis,
		'unionThisInt' => new \PHPStan\Type\UnionType([$int, $utilsThis]),
		'intersectionThisObject' => new \PHPStan\Type\IntersectionType([$utilsThis, new \PHPStan\Type\Accessory\HasMethodType('foo')]),
		'unionOfIntersectionThis' => new \PHPStan\Type\UnionType([new \PHPStan\Type\NullType(), new \PHPStan\Type\IntersectionType([$utilsThis, new \PHPStan\Type\Accessory\HasMethodType('foo')])]),
		'object' => new \PHPStan\Type\ObjectType(\stdClass::class),
		'callable' => new \PHPStan\Type\CallableType(),
		'closure' => new \PHPStan\Type\ClosureType(),
		'closureObject' => new \PHPStan\Type\ObjectType(\Closure::class),
		'unionIntCallable' => new \PHPStan\Type\UnionType([$int, new \PHPStan\Type\CallableType()]),
		'unionIntClosure' => new \PHPStan\Type\UnionType([$int, new \PHPStan\Type\ClosureType()]),
		'hasProperty' => new \PHPStan\Type\Accessory\HasPropertyType('foo'),
		'objectHasProperty' => new \PHPStan\Type\IntersectionType([new \PHPStan\Type\ObjectType(\stdClass::class), new \PHPStan\Type\Accessory\HasPropertyType('foo')]),
		'objectHasProperties' => new \PHPStan\Type\IntersectionType([new \PHPStan\Type\ObjectType(\stdClass::class), new \PHPStan\Type\Accessory\HasPropertyType('foo'), new \PHPStan\Type\Accessory\HasPropertyType('bar')]),
		'unionHasProperty' => new \PHPStan\Type\UnionType([new \PHPStan\Type\IntersectionType([new \PHPStan\Type\ObjectType(\stdClass::class), new \PHPStan\Type\Accessory\HasPropertyType('foo')]), new \PHPStan\Type\ObjectType(\Exception::class)]),
		'keyOfShape' => new \PHPStan\Type\KeyOfType($utilsShape(['a' => $int, 'b' => $string])),
		'valueOfShape' => new \PHPStan\Type\ValueOfType($utilsShape(['a' => $int, 'b' => $string])),
		'keyOfTemplate' => new \PHPStan\Type\KeyOfType($utilsTemplateT),
		'valueOfTemplateInt' => new \PHPStan\Type\ValueOfType(new \PHPStan\Type\ArrayType($int, $utilsTemplateInt)),
		'conditional' => new \PHPStan\Type\ConditionalType($int, $int, $string, new \PHPStan\Type\BooleanType(), false),
		'conditionalTemplate' => new \PHPStan\Type\ConditionalType($utilsTemplateT, $int, $string, new \PHPStan\Type\BooleanType(), false),
		'unionKeyOfInt' => new \PHPStan\Type\UnionType([new \PHPStan\Type\KeyOfType($utilsShape(['a' => $int, 'b' => $string])), $int]),
		'arrayOfKeyOfTemplate' => new \PHPStan\Type\ArrayType($int, new \PHPStan\Type\KeyOfType($utilsTemplateT)),
	];
	foreach ($subjects as $name => $subject) {
		$r["getConstantIntegers $name"] = $view(\PHPStan\Type\TypeUtils::getConstantIntegers($subject));
		$r["getIntegerRanges $name"] = $view(\PHPStan\Type\TypeUtils::getIntegerRanges($subject));
		$r["toBenevolentUnion $name"] = $view(\PHPStan\Type\TypeUtils::toBenevolentUnion($subject));
		$r["toStrictUnion $name"] = $view(\PHPStan\Type\TypeUtils::toStrictUnion($subject));
		$r["flattenTypes $name"] = $view(\PHPStan\Type\TypeUtils::flattenTypes($subject));
		$r["findThisType $name"] = $view(\PHPStan\Type\TypeUtils::findThisType($subject));
		$r["findCallableType $name"] = $view(\PHPStan\Type\TypeUtils::findCallableType($subject));
		$r["getHasPropertyTypes $name"] = $view(\PHPStan\Type\TypeUtils::getHasPropertyTypes($subject));
		$r["getAccessoryTypes $name"] = $view(\PHPStan\Type\TypeUtils::getAccessoryTypes($subject));
		$r["containsTemplateType $name"] = \PHPStan\Type\TypeUtils::containsTemplateType($subject);
		$r["resolveLateResolvableTypes $name"] = [$view(\PHPStan\Type\TypeUtils::resolveLateResolvableTypes($subject)), $view(\PHPStan\Type\TypeUtils::resolveLateResolvableTypes($subject, true)), $view(\PHPStan\Type\TypeUtils::resolveLateResolvableTypes($subject, false))];
	}
	// identity: the helpers hand the argument back where the twin does
	$r['identity'] = [
		\PHPStan\Type\TypeUtils::toBenevolentUnion($subjects['benevolent']) === $subjects['benevolent'],
		\PHPStan\Type\TypeUtils::toBenevolentUnion($subjects['int']) === $subjects['int'],
		\PHPStan\Type\TypeUtils::toStrictUnion($subjects['unionIntString']) === $subjects['unionIntString'],
		\PHPStan\Type\TypeUtils::toStrictUnion($subjects['int']) === $subjects['int'],
		\PHPStan\Type\TypeUtils::flattenTypes($subjects['int'])[0] === $subjects['int'],
		\PHPStan\Type\TypeUtils::flattenTypes($subjects['shapeWide15'])[0] === $subjects['shapeWide15'],
		\PHPStan\Type\TypeUtils::findThisType($subjects['this']) === $subjects['this'],
		\PHPStan\Type\TypeUtils::findThisType($subjects['unionThisInt']) === $utilsThis,
		\PHPStan\Type\TypeUtils::findCallableType($subjects['callable']) === $subjects['callable'],
		\PHPStan\Type\TypeUtils::getHasPropertyTypes($subjects['hasProperty'])[0] === $subjects['hasProperty'],
		\PHPStan\Type\TypeUtils::getConstantIntegers($subjects['int0'])[0] === $subjects['int0'],
		\PHPStan\Type\TypeUtils::resolveLateResolvableTypes($subjects['int']) === $subjects['int'],
	];
	// a PHP subclass of the shadowed union over the native parent
	$anonymousUnion = new class ([new \PHPStan\Type\Constant\ConstantIntegerType(5), new \PHPStan\Type\Accessory\HasPropertyType('anon')]) extends \PHPStan\Type\UnionType {

		public function getTypes(): array
		{
			return [new \PHPStan\Type\Constant\ConstantIntegerType(7), new \PHPStan\Type\Accessory\HasPropertyType('overridden')];
		}

	};
	$r['anonymous union getConstantIntegers'] = $view(\PHPStan\Type\TypeUtils::getConstantIntegers($anonymousUnion));
	$r['anonymous union getHasPropertyTypes'] = $view(\PHPStan\Type\TypeUtils::getHasPropertyTypes($anonymousUnion));
	$r['anonymous union toStrictUnion'] = $view(\PHPStan\Type\TypeUtils::toStrictUnion($anonymousUnion));
	$r['anonymous union flattenTypes'] = $view(\PHPStan\Type\TypeUtils::flattenTypes($anonymousUnion));
	foreach ($r as $key => $value) {
		$observations["utils $key"] = $value;
	}
}


// ---- TypehintHelper ----
// the native/PHPDoc type decision over the whole family: the reflection
// types come from the container's reflection provider (the BetterReflection
// adapters TypehintHelper names) reflecting tests/type-family-typehint-fixture.php
// — named, nullable, union, intersection, DNF, self/static (with the fixture
// as the self class), mixed, iterable, array, callable, void, never, a
// variadic parameter — and no reflection type at all; the PHPDoc types cover
// the branches of decideType() (a never/void/error/mixed PHPDoc, arrays over
// an iterable native type, unions the PHPDoc type covers partially, template
// types resolved to bounds, callables); a core reflection type takes the
// "Unexpected type" throw; the string family's reflection provider stays
// registered
$observations['native ' . \PHPStan\Type\TypehintHelper::class] = (new ReflectionMethod(\PHPStan\Type\TypehintHelper::class, 'decideType'))->isInternal();
require_once __DIR__ . '/type-family-typehint-fixture.php';
{
	$r = [];
	$hintScope = \PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('hint');
	$hintTemplateT = \PHPStan\Type\Generic\TemplateTypeFactory::create($hintScope, 'T', null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
	$hintTemplateInt = \PHPStan\Type\Generic\TemplateTypeFactory::create($hintScope, 'I', new \PHPStan\Type\IntegerType(), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
	$hintTemplateObject = \PHPStan\Type\Generic\TemplateTypeFactory::create($hintScope, 'O', new \PHPStan\Type\ObjectType(\Exception::class), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
	$hintFixture = $stringReflectionProvider->getClass(\PHPStanTurboTests\TypehintFixture::class);
	$hintNative = $hintFixture->getNativeReflection();
	$hintReflectionTypes = ['none' => null];
	foreach ($hintNative->getMethods() as $hintMethod) {
		if (!str_starts_with($hintMethod->getName(), 'returns')) {
			continue;
		}
		$hintReflectionTypes[$hintMethod->getName()] = $hintMethod->getReturnType();
	}
	$hintReflectionTypes['variadicParameter'] = $hintNative->getMethod('takesVariadic')->getParameters()[0]->getType();
	$hintReflectionTypes['nullableArrayParameter'] = $hintNative->getMethod('takesNullableArray')->getParameters()[0]->getType();
	$int = new \PHPStan\Type\IntegerType();
	$string = new \PHPStan\Type\StringType();
	$mixed = new \PHPStan\Type\MixedType();
	$hintPhpDocTypes = [
		'none' => null,
		'int' => $int,
		'string' => $string,
		'nullableInt' => new \PHPStan\Type\UnionType([$int, new \PHPStan\Type\NullType()]),
		'null' => new \PHPStan\Type\NullType(),
		'bool' => new \PHPStan\Type\BooleanType(),
		'true' => new \PHPStan\Type\Constant\ConstantBooleanType(true),
		'int1' => new \PHPStan\Type\Constant\ConstantIntegerType(1),
		'unionIntString' => new \PHPStan\Type\UnionType([$int, $string]),
		'unionIntStringNull' => new \PHPStan\Type\UnionType([$int, $string, new \PHPStan\Type\NullType()]),
		'unionIntFloat' => new \PHPStan\Type\UnionType([$int, new \PHPStan\Type\FloatType()]),
		'benevolent' => new \PHPStan\Type\BenevolentUnionType([$int, $string]),
		'arrayMixedKey' => new \PHPStan\Type\ArrayType($mixed, $string),
		'arrayIntKey' => new \PHPStan\Type\ArrayType($int, $string),
		'arrayMixedKeyOrNull' => new \PHPStan\Type\UnionType([new \PHPStan\Type\ArrayType($mixed, $string), new \PHPStan\Type\NullType()]),
		'unionArrays' => new \PHPStan\Type\UnionType([new \PHPStan\Type\ArrayType($mixed, $int), new \PHPStan\Type\ArrayType($int, $string)]),
		'unionArrayInt' => new \PHPStan\Type\UnionType([new \PHPStan\Type\ArrayType($mixed, $int), $int]),
		'list' => new \PHPStan\Type\IntersectionType([new \PHPStan\Type\ArrayType(\PHPStan\Type\IntegerRangeType::createAllGreaterThanOrEqualTo(0), $string), new \PHPStan\Type\Accessory\AccessoryArrayListType()]),
		'constantArray' => new \PHPStan\Type\Constant\ConstantArrayType([new \PHPStan\Type\Constant\ConstantStringType('a')], [$int], [1]),
		'iterable' => new \PHPStan\Type\IterableType($mixed, $mixed),
		'iterableOfStrings' => new \PHPStan\Type\IterableType($int, $string),
		'callable' => new \PHPStan\Type\CallableType(),
		'callableTyped' => new \PHPStan\Type\CallableType([], $int, false),
		'closure' => new \PHPStan\Type\ClosureType(),
		'neverExplicit' => new \PHPStan\Type\NeverType(true),
		'neverImplicit' => new \PHPStan\Type\NeverType(),
		'error' => new \PHPStan\Type\ErrorType(),
		'mixed' => $mixed,
		'mixedExplicit' => new \PHPStan\Type\MixedType(true),
		'mixedMinusNull' => new \PHPStan\Type\MixedType(false, new \PHPStan\Type\NullType()),
		'void' => new \PHPStan\Type\VoidType(),
		'templateT' => $hintTemplateT,
		'templateInt' => $hintTemplateInt,
		'templateObject' => $hintTemplateObject,
		'exception' => new \PHPStan\Type\ObjectType(\Exception::class),
		'runtimeException' => new \PHPStan\Type\ObjectType(\RuntimeException::class),
		'stdClass' => new \PHPStan\Type\ObjectType(\stdClass::class),
		'fixture' => new \PHPStan\Type\ObjectType(\PHPStanTurboTests\TypehintFixture::class),
		'arrayIterator' => new \PHPStan\Type\ObjectType(\ArrayIterator::class),
		'genericArrayIterator' => new \PHPStan\Type\Generic\GenericObjectType(\ArrayIterator::class, [$int, $string]),
		'nonEmptyString' => new \PHPStan\Type\IntersectionType([$string, new \PHPStan\Type\Accessory\AccessoryNonEmptyStringType()]),
		'objectWithoutClass' => new \PHPStan\Type\ObjectWithoutClassType(),
	];
	foreach ($hintReflectionTypes as $reflectionName => $reflectionType) {
		$r["reflection $reflectionName"] = $reflectionType === null ? null : [get_class($reflectionType), (string) $reflectionType];
		foreach ($hintPhpDocTypes as $docName => $phpDocType) {
			$r["decideTypeFromReflection $reflectionName $docName"] = $view(\PHPStan\Type\TypehintHelper::decideTypeFromReflection($reflectionType, $phpDocType, $hintFixture));
			if ($reflectionName === 'none' || $reflectionName === 'variadicParameter') {
				$r["decideTypeFromReflection $reflectionName $docName variadic"] = $view(\PHPStan\Type\TypehintHelper::decideTypeFromReflection($reflectionType, $phpDocType, $hintFixture, true));
			}
		}
		$r["decideTypeFromReflection $reflectionName no self class"] = $view(\PHPStan\Type\TypehintHelper::decideTypeFromReflection($reflectionType));
	}
	$hintNativeTypes = [
		'int' => $int,
		'string' => $string,
		'nullableInt' => new \PHPStan\Type\UnionType([$int, new \PHPStan\Type\NullType()]),
		'unionIntString' => new \PHPStan\Type\UnionType([$int, $string]),
		'unionIntStringNull' => new \PHPStan\Type\UnionType([$int, $string, new \PHPStan\Type\NullType()]),
		'benevolent' => new \PHPStan\Type\BenevolentUnionType([$int, $string]),
		'mixed' => $mixed,
		'mixedExplicit' => new \PHPStan\Type\MixedType(true),
		'iterable' => new \PHPStan\Type\IterableType($mixed, $mixed),
		'nullableIterable' => new \PHPStan\Type\UnionType([new \PHPStan\Type\IterableType($mixed, $mixed), new \PHPStan\Type\NullType()]),
		'array' => new \PHPStan\Type\ArrayType($mixed, $mixed),
		'callable' => new \PHPStan\Type\CallableType(),
		'exception' => new \PHPStan\Type\ObjectType(\Exception::class),
		'never' => new \PHPStan\Type\NeverType(true),
		'null' => new \PHPStan\Type\NullType(),
		'void' => new \PHPStan\Type\VoidType(),
		'bool' => new \PHPStan\Type\BooleanType(),
		'objectWithoutClass' => new \PHPStan\Type\ObjectWithoutClassType(),
	];
	foreach ($hintNativeTypes as $typeName => $type) {
		foreach ($hintPhpDocTypes as $docName => $phpDocType) {
			$r["decideType $typeName $docName"] = $view(\PHPStan\Type\TypehintHelper::decideType($type, $phpDocType));
		}
	}
	// identity: the argument comes back where the twin hands it back
	$r['identity'] = [
		\PHPStan\Type\TypehintHelper::decideType($hintNativeTypes['int'], null) === $hintNativeTypes['int'],
		\PHPStan\Type\TypehintHelper::decideType($hintNativeTypes['benevolent'], $hintPhpDocTypes['int']) === $hintNativeTypes['benevolent'],
		\PHPStan\Type\TypehintHelper::decideType($hintNativeTypes['int'], $hintPhpDocTypes['int1']) === $hintPhpDocTypes['int1'],
		\PHPStan\Type\TypehintHelper::decideType($hintNativeTypes['int'], $hintPhpDocTypes['error']) === $hintNativeTypes['int'],
		\PHPStan\Type\TypehintHelper::decideType($hintNativeTypes['mixed'], $hintPhpDocTypes['neverExplicit']) === $hintPhpDocTypes['neverExplicit'],
		\PHPStan\Type\TypehintHelper::decideType($hintNativeTypes['mixed'], $hintPhpDocTypes['void']) === $hintPhpDocTypes['void'],
		\PHPStan\Type\TypehintHelper::decideTypeFromReflection(null, $hintPhpDocTypes['int']) === $hintPhpDocTypes['int'],
		\PHPStan\Type\TypehintHelper::decideTypeFromReflection(null, $hintPhpDocTypes['arrayIntKey'], null, true) === $hintPhpDocTypes['arrayIntKey']->getItemType(),
	];
	// a core reflection type is not the adapter the twin names
	foreach (['returnsInt', 'returnsIntOrString', 'returnsIntersection'] as $hintMethodName) {
		try {
			$r["core reflection $hintMethodName"] = $view(\PHPStan\Type\TypehintHelper::decideTypeFromReflection((new ReflectionMethod(\PHPStanTurboTests\TypehintFixture::class, $hintMethodName))->getReturnType()));
		} catch (\Throwable $e) {
			$r["core reflection $hintMethodName"] = [get_class($e), $e->getMessage()];
		}
	}
	// a PHP subclass of the shadowed union / array over the native parents
	$anonymousHintUnion = new class ([$int, $string]) extends \PHPStan\Type\UnionType {

		public function getTypes(): array
		{
			return [new \PHPStan\Type\Constant\ConstantIntegerType(1), new \PHPStan\Type\NullType()];
		}

	};
	$anonymousHintArray = new class (new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()) extends \PHPStan\Type\ArrayType {

		public function getKeyType(): \PHPStan\Type\Type
		{
			return new \PHPStan\Type\MixedType();
		}

	};
	$r['anonymous union decideType'] = [$view(\PHPStan\Type\TypehintHelper::decideType($anonymousHintUnion, $hintPhpDocTypes['int1'])), $view(\PHPStan\Type\TypehintHelper::decideType($hintNativeTypes['iterable'], $anonymousHintUnion))];
	$r['anonymous array decideType'] = [$view(\PHPStan\Type\TypehintHelper::decideType($hintNativeTypes['iterable'], $anonymousHintArray)), $view(\PHPStan\Type\TypehintHelper::decideType($hintNativeTypes['nullableIterable'], new \PHPStan\Type\UnionType([$anonymousHintArray, $int])))];
	foreach ($r as $key => $value) {
		$observations["typehint $key"] = $value;
	}
}


// ---- TypeCombinator ----
// the static combinator over a broad matrix of the family above: every pair
// of subjects unioned, intersected and subtracted, the null/falsey/truthy
// helpers per subject, a sample of triples, the wide unions (the >16-member
// dedup, the constant-array count limit with its same-signature collapse
// and the list-variant fold), enum cases against their enum, template
// unions and arrays, subtracted types, offset accessories against constant
// arrays, object shapes against HasPropertyType; the compound section's
// reflection provider stays registered
$observations['native PHPStan\Type\TypeCombinator'] = (new ReflectionMethod(\PHPStan\Type\TypeCombinator::class, 'union'))->isInternal();
{
	$tc = \PHPStan\Type\TypeCombinator::class;
	$r = [];
	$unionClass = \PHPStan\Type\UnionType::class;
	$benevolentClass = \PHPStan\Type\BenevolentUnionType::class;
	$intersectionClass = \PHPStan\Type\IntersectionType::class;
	$subjects = $compoundOthers($unionClass, $benevolentClass, $intersectionClass);
	$unsealedShape = static function (array $entries, \PHPStan\Type\Type $keyType, \PHPStan\Type\Type $valueType, array $optionalKeys = []): \PHPStan\Type\Type {
		$builder = \PHPStan\Type\Constant\ConstantArrayTypeBuilder::createEmpty();
		$i = 0;
		foreach ($entries as $key => $entryType) {
			$builder->setOffsetValueType(is_int($key) ? new \PHPStan\Type\Constant\ConstantIntegerType($key) : new \PHPStan\Type\Constant\ConstantStringType($key), $entryType, in_array($i, $optionalKeys, true));
			$i++;
		}
		$builder->makeUnsealed($keyType, $valueType);
		return $builder->getArray();
	};
	$subjects += [
		'range5-10' => \PHPStan\Type\IntegerRangeType::fromInterval(5, 10),
		'rangeMin-0' => \PHPStan\Type\IntegerRangeType::fromInterval(null, 0),
		'range2-4' => \PHPStan\Type\IntegerRangeType::fromInterval(2, 4),
		'int3' => new \PHPStan\Type\Constant\ConstantIntegerType(3),
		'string0' => new \PHPStan\Type\Constant\ConstantStringType('0'),
		'stringUpper' => new \PHPStan\Type\Constant\ConstantStringType('ABC'),
		'nonFalsyString' => new $intersectionClass([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryNonEmptyStringType(), new \PHPStan\Type\Accessory\AccessoryNonFalsyStringType()]),
		'nonFalsyLowercase' => new $intersectionClass([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryNonEmptyStringType(), new \PHPStan\Type\Accessory\AccessoryNonFalsyStringType(), new \PHPStan\Type\Accessory\AccessoryLowercaseStringType()]),
		'nonEmptyUppercase' => new $intersectionClass([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryNonEmptyStringType(), new \PHPStan\Type\Accessory\AccessoryUppercaseStringType()]),
		'decimalIntString' => new $intersectionClass([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryDecimalIntegerStringType()]),
		'nonDecimalNumericString' => new $intersectionClass([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryNumericStringType(), new \PHPStan\Type\Accessory\AccessoryDecimalIntegerStringType(true)]),
		'literalString' => new $intersectionClass([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryLiteralStringType()]),
		'enum' => new \PHPStan\Type\ObjectType('Random\\IntervalBoundary'),
		'enumCaseClosedOpen' => new \PHPStan\Type\Enum\EnumCaseObjectType('Random\\IntervalBoundary', 'ClosedOpen'),
		'enumCaseOpenClosed' => new \PHPStan\Type\Enum\EnumCaseObjectType('Random\\IntervalBoundary', 'OpenClosed'),
		'enumCasesUnion' => new $unionClass([new \PHPStan\Type\Enum\EnumCaseObjectType('Random\\IntervalBoundary', 'ClosedClosed'), new \PHPStan\Type\Enum\EnumCaseObjectType('Random\\IntervalBoundary', 'OpenOpen')]),
		'enumMinusCase' => (new \PHPStan\Type\ObjectType('Random\\IntervalBoundary'))->tryRemove(new \PHPStan\Type\Enum\EnumCaseObjectType('Random\\IntervalBoundary', 'ClosedOpen')),
		'mixedMinusInt' => new \PHPStan\Type\MixedType(false, new \PHPStan\Type\IntegerType()),
		'mixedMinusIntString' => new \PHPStan\Type\MixedType(false, new $unionClass([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()])),
		'objectMinusStd' => new \PHPStan\Type\ObjectWithoutClassType(new \PHPStan\Type\ObjectType(\stdClass::class)),
		'throwableMinusError' => new \PHPStan\Type\ObjectType(\Throwable::class, new \PHPStan\Type\ObjectType(\Error::class)),
		'exception' => new \PHPStan\Type\ObjectType(\Exception::class),
		'errorObject' => new \PHPStan\Type\ObjectType(\Error::class),
		'templateUnion' => \PHPStan\Type\Generic\TemplateTypeFactory::create($compoundScope, 'U', new $unionClass([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
		'templateBenevolentUnion' => \PHPStan\Type\Generic\TemplateTypeFactory::create($compoundScope, 'B', new $benevolentClass([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
		'templateArray' => \PHPStan\Type\Generic\TemplateTypeFactory::create($compoundScope, 'A', new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
		'templateArrayInt' => \PHPStan\Type\Generic\TemplateTypeFactory::create($compoundScope, 'A', new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\IntegerType()), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
		'templateMixed' => \PHPStan\Type\Generic\TemplateTypeFactory::create($compoundScope, 'M', new \PHPStan\Type\MixedType(), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
		'constShapeOptionalB' => $compoundConstShape(['a' => new \PHPStan\Type\IntegerType(), 'b' => new \PHPStan\Type\StringType()], [1]),
		'constShapeAB' => $compoundConstShape(['a' => new \PHPStan\Type\StringType(), 'b' => new \PHPStan\Type\IntegerType()]),
		'constShapeC' => $compoundConstShape(['c' => new \PHPStan\Type\BooleanType()]),
		'constShapeAOptional' => $compoundConstShape(['a' => new \PHPStan\Type\IntegerType()], [0]),
		'constListInts' => $compoundConstShape([0 => new \PHPStan\Type\IntegerType(), 1 => new \PHPStan\Type\IntegerType()]),
		'constListOptionalTail' => $compoundConstShape([0 => new \PHPStan\Type\IntegerType(), 1 => new \PHPStan\Type\StringType()], [1]),
		'unsealedShape' => $unsealedShape(['k' => new \PHPStan\Type\IntegerType()], new \PHPStan\Type\StringType(), new \PHPStan\Type\IntegerType()),
		'unsealedShapeOptional' => $unsealedShape(['k' => new \PHPStan\Type\IntegerType(), 'l' => new \PHPStan\Type\StringType()], new \PHPStan\Type\StringType(), new \PHPStan\Type\MixedType(), [1]),
		'unsealedEmpty' => $unsealedShape([], new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()),
		'hasOffsetValueA' => new \PHPStan\Type\Accessory\HasOffsetValueType(new \PHPStan\Type\Constant\ConstantStringType('a'), new \PHPStan\Type\IntegerType()),
		'hasOffsetValueAString' => new \PHPStan\Type\Accessory\HasOffsetValueType(new \PHPStan\Type\Constant\ConstantStringType('a'), new \PHPStan\Type\StringType()),
		'hasOffsetValueZero' => new \PHPStan\Type\Accessory\HasOffsetValueType(new \PHPStan\Type\Constant\ConstantIntegerType(0), new \PHPStan\Type\Constant\ConstantStringType('x')),
		'hasOffsetA' => new \PHPStan\Type\Accessory\HasOffsetType(new \PHPStan\Type\Constant\ConstantStringType('a')),
		'arrayWithOffsetAString' => new $intersectionClass([new \PHPStan\Type\ArrayType(new \PHPStan\Type\StringType(), new \PHPStan\Type\MixedType()), new \PHPStan\Type\Accessory\HasOffsetValueType(new \PHPStan\Type\Constant\ConstantStringType('a'), new \PHPStan\Type\StringType()), new \PHPStan\Type\Accessory\NonEmptyArrayType()]),
		'listWithOffsets' => new $intersectionClass([new \PHPStan\Type\ArrayType(\PHPStan\Type\IntegerRangeType::createAllGreaterThanOrEqualTo(0), new \PHPStan\Type\StringType()), new \PHPStan\Type\Accessory\AccessoryArrayListType(), new \PHPStan\Type\Accessory\HasOffsetValueType(new \PHPStan\Type\Constant\ConstantIntegerType(0), new \PHPStan\Type\Constant\ConstantStringType('x')), new \PHPStan\Type\Accessory\HasOffsetType(new \PHPStan\Type\Constant\ConstantIntegerType(1)), new \PHPStan\Type\Accessory\NonEmptyArrayType()]),
		'oversized' => new $intersectionClass([new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()), new \PHPStan\Type\Accessory\OversizedArrayType()]),
		'objectShape' => new \PHPStan\Type\ObjectShapeType(['bar' => new \PHPStan\Type\IntegerType(), 'baz' => new \PHPStan\Type\StringType()], ['bar']),
		'objectShapeRequired' => new \PHPStan\Type\ObjectShapeType(['bar' => new \PHPStan\Type\IntegerType()], []),
		'hasPropertyBaz' => new \PHPStan\Type\Accessory\HasPropertyType('baz'),
		'iterableIntString' => new \PHPStan\Type\IterableType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()),
		'iterableStringInt' => new \PHPStan\Type\IterableType(new \PHPStan\Type\StringType(), new \PHPStan\Type\IntegerType()),
		'classStringStd' => new \PHPStan\Type\Generic\GenericClassStringType(new \PHPStan\Type\ObjectType(\stdClass::class)),
		'classStringThrowable' => new \PHPStan\Type\Generic\GenericClassStringType(new \PHPStan\Type\ObjectType(\Throwable::class)),
		'unionOfIntersections' => new $unionClass([new $intersectionClass([new \PHPStan\Type\ArrayType(new \PHPStan\Type\StringType(), new \PHPStan\Type\IntegerType()), new \PHPStan\Type\Accessory\HasOffsetValueType(new \PHPStan\Type\Constant\ConstantStringType('a'), new \PHPStan\Type\IntegerType())]), new $intersectionClass([new \PHPStan\Type\ArrayType(new \PHPStan\Type\StringType(), new \PHPStan\Type\IntegerType()), new \PHPStan\Type\Accessory\HasOffsetValueType(new \PHPStan\Type\Constant\ConstantStringType('a'), new \PHPStan\Type\StringType())])]),
		'unionOfShapes' => new $unionClass([$compoundConstShape(['a' => new \PHPStan\Type\IntegerType()]), $compoundConstShape(['b' => new \PHPStan\Type\StringType()])]),
		'unionRanges' => new $unionClass([\PHPStan\Type\IntegerRangeType::fromInterval(0, 3), \PHPStan\Type\IntegerRangeType::fromInterval(10, 20)]),
		'nullableString' => new $unionClass([new \PHPStan\Type\StringType(), new \PHPStan\Type\NullType()]),
		'truthy' => \PHPStan\Type\StaticTypeFactory::truthy(),
	];

	// every pair: union, intersect, remove
	foreach ($subjects as $aName => $a) {
		foreach ($subjects as $bName => $b) {
			$r["union $aName $bName"] = $view($tc::union($a, $b));
			$r["intersect $aName $bName"] = $view($tc::intersect($a, $b));
			$r["remove $aName $bName"] = $view($tc::remove($a, $b));
		}
	}

	// the single-type helpers
	foreach ($subjects as $name => $type) {
		$r["removeNull $name"] = $view($tc::removeNull($type));
		$r["addNull $name"] = $view($tc::addNull($type));
		$r["containsNull $name"] = $tc::containsNull($type);
		$r["removeFalsey $name"] = $view($tc::removeFalsey($type));
		$r["removeTruthy $name"] = $view($tc::removeTruthy($type));
		$r["union single $name"] = $view($tc::union($type));
		$r["intersect single $name"] = $view($tc::intersect($type));
		$r["doUnion single $name"] = $view($tc::doUnion($type));
		$r["doIntersect single $name"] = $view($tc::doIntersect($type));
		$r["countConstantArrayValueTypes $name"] = $tc::countConstantArrayValueTypes([$type]);
	}

	// a sample of triples, and the unmemoized bodies over the pairs of it
	$tripleNames = ['int', 'int1', 'range0-3', 'string', 'stringA', 'stringEmpty', 'string0', 'nonEmptyString', 'nonFalsyString', 'null', 'mixed', 'mixedMinusInt', 'never', 'union', 'benevolent', 'array', 'list', 'nonEmptyArray', 'constArray', 'constShapeOptionalB', 'hasOffsetValueA', 'object', 'objectWithoutClass', 'enum', 'enumCaseClosedOpen', 'templateT', 'templateUnion', 'iterable', 'callable', 'true', 'false'];
	foreach ($tripleNames as $aName) {
		foreach ($tripleNames as $bName) {
			$r["doUnion $aName $bName"] = $view($tc::doUnion($subjects[$aName], $subjects[$bName]));
			$r["doIntersect $aName $bName"] = $view($tc::doIntersect($subjects[$aName], $subjects[$bName]));
			$r["doRemove $aName $bName"] = $view($tc::doRemove($subjects[$aName], $subjects[$bName]));
			foreach ($tripleNames as $cName) {
				$r["union3 $aName $bName $cName"] = $view($tc::union($subjects[$aName], $subjects[$bName], $subjects[$cName]));
				$r["intersect3 $aName $bName $cName"] = $view($tc::intersect($subjects[$aName], $subjects[$bName], $subjects[$cName]));
			}
		}
	}

	// the empty operations
	$r['union none'] = $view($tc::union());
	$r['intersect none'] = $view($tc::intersect());
	$r['countConstantArrayValueTypes none'] = $tc::countConstantArrayValueTypes([]);
	$tc::clearCache();
	$r['clearCache'] = 'no throw';

	// wide unions: more than 16 members (the description dedup), the
	// constant-array count limit (same-signature records collapse
	// losslessly, differing shapes generalize), and the list-variant fold
	$manyStrings = array_map(static fn (int $i) => new \PHPStan\Type\Constant\ConstantStringType('s' . $i), range(1, 20));
	$r['union many strings'] = $view($tc::union(...$manyStrings, ...[new \PHPStan\Type\IntegerType(), new \PHPStan\Type\Constant\ConstantStringType('s3')]));
	$r['union many strings with string'] = $view($tc::union(...$manyStrings, ...[new \PHPStan\Type\StringType()]));
	$manyInts = array_map(static fn (int $i) => new \PHPStan\Type\Constant\ConstantIntegerType($i), range(1, 30));
	$r['union many ints'] = $view($tc::union(...$manyInts));
	$r['union many ints with range'] = $view($tc::union(...$manyInts, ...[\PHPStan\Type\IntegerRangeType::fromInterval(25, 40)]));
	$r['intersect many ints with union'] = $view($tc::intersect($tc::union(...$manyInts), new $unionClass([new \PHPStan\Type\Constant\ConstantIntegerType(2), new \PHPStan\Type\Constant\ConstantIntegerType(31)])));
	$manyObjects = array_map(static fn (string $class) => new \PHPStan\Type\ObjectType($class), [\stdClass::class, \DateTime::class, \DateTimeImmutable::class, \DateTimeInterface::class, \Exception::class, \Error::class, \Throwable::class, \ArrayObject::class, \ArrayIterator::class, \Iterator::class, \Traversable::class, \Countable::class, \Stringable::class, \Closure::class, \Generator::class, \SplStack::class, \SplQueue::class, \WeakMap::class]);
	$r['union many objects'] = $view($tc::union(...$manyObjects));
	$r['intersect many objects'] = $view($tc::intersect(...$manyObjects));
	$sameShapeRecords = array_map(static fn (int $i) => $compoundConstShape(['id' => new \PHPStan\Type\Constant\ConstantIntegerType($i), 'name' => new \PHPStan\Type\Constant\ConstantStringType('n' . $i), 'flag' => new \PHPStan\Type\Constant\ConstantBooleanType($i % 2 === 0)]), range(1, 100));
	$r['union same-shape records'] = $view($tc::union(...$sameShapeRecords));
	$r['countConstantArrayValueTypes same-shape records'] = $tc::countConstantArrayValueTypes($sameShapeRecords);
	$differingShapes = array_map(static fn (int $i) => $compoundConstShape(array_combine(array_map(static fn (int $k) => 'k' . $k, range($i, $i + 3)), array_map(static fn (int $k) => new \PHPStan\Type\Constant\ConstantIntegerType($k), range($i, $i + 3)))), range(1, 80));
	$r['union differing shapes'] = $view($tc::union(...$differingShapes));
	$r['union differing shapes with list'] = $view($tc::union(...$differingShapes, ...[$subjects['list']]));
	$nestedShapes = array_map(static fn (int $i) => $compoundConstShape(['row' => $compoundConstShape(['a' => new \PHPStan\Type\Constant\ConstantIntegerType($i), 'b' => $compoundConstShape([])]), 'k' . ($i % 7) => new \PHPStan\Type\Constant\ConstantStringType('v' . $i)]), range(1, 90));
	$r['union nested shapes'] = $view($tc::union(...$nestedShapes));
	$listVariants = [];
	for ($i = 1; $i <= 25; $i++) {
		$entries = [];
		for ($k = 0; $k < $i; $k++) {
			$entries[$k] = new \PHPStan\Type\Constant\ConstantIntegerType($k * $i);
		}
		$listVariants[] = $compoundConstShape($entries);
	}
	$r['union list variants'] = $view($tc::union(...$listVariants));
	$r['union list variants with empty'] = $view($tc::union(...$listVariants, ...[$compoundConstShape([])]));
	$r['union list variants with general'] = $view($tc::union(...$listVariants, ...[new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType())]));
	$optionalVariants = array_map(static fn (int $i) => $compoundConstShape(['a' => new \PHPStan\Type\IntegerType(), 'b' => new \PHPStan\Type\Constant\ConstantIntegerType($i)], $i % 2 === 0 ? [1] : []), range(1, 12));
	$r['union optional variants'] = $view($tc::union(...$optionalVariants));
	$r['union optional variants with empty'] = $view($tc::union(...$optionalVariants, ...[$compoundConstShape([])]));
	$r['union unsealed shapes'] = $view($tc::union($subjects['unsealedShape'], $subjects['unsealedShapeOptional'], $subjects['unsealedEmpty'], $compoundConstShape([])));
	$r['union shapes and general'] = $view($tc::union($subjects['constShapeAB'], $subjects['constShapeC'], $subjects['arrayStringInt'], $subjects['templateArray']));
	$r['union template arrays'] = $view($tc::union($subjects['templateArray'], $subjects['templateArrayInt']));
	$r['union enum cases'] = $view($tc::union($subjects['enumCaseClosedOpen'], $subjects['enumCaseOpenClosed'], new \PHPStan\Type\Enum\EnumCaseObjectType('Random\\IntervalBoundary', 'ClosedClosed'), new \PHPStan\Type\Enum\EnumCaseObjectType('Random\\IntervalBoundary', 'OpenOpen')));
	$r['remove enum cases'] = $view($tc::remove($subjects['enum'], $tc::union($subjects['enumCaseClosedOpen'], $subjects['enumCaseOpenClosed'])));
	$r['remove all enum cases'] = $view($tc::remove($subjects['enum'], $tc::union($subjects['enumCaseClosedOpen'], $subjects['enumCaseOpenClosed'], $subjects['enumCasesUnion'])));
	$r['union benevolent members'] = $view($tc::union(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType(), $subjects['benevolent']));
	$r['union benevolent nullable'] = $view($tc::union($subjects['benevolentNullable'], $subjects['benevolent']));
	$r['intersect benevolent with consts'] = $view($tc::intersect($subjects['benevolent'], $subjects['unionConstMixed']));
	$r['intersect template union with int'] = $view($tc::intersect($subjects['templateUnion'], new \PHPStan\Type\IntegerType()));
	$r['intersect template benevolent with int'] = $view($tc::intersect($subjects['templateBenevolentUnion'], new \PHPStan\Type\IntegerType()));
	$r['intersect unions of unions'] = $view($tc::intersect($subjects['unionArrays'], $subjects['unionConstMixed'], $subjects['templateUnion']));
	$r['intersect offsets and shape'] = $view($tc::intersect($subjects['constShapeOptionalB'], new \PHPStan\Type\Accessory\HasOffsetType(new \PHPStan\Type\Constant\ConstantStringType('b')), $subjects['nonEmpty']));
	$r['intersect offset values and shape'] = $view($tc::intersect($subjects['constShapeAB'], $subjects['hasOffsetValueAString'], $subjects['listAccessory']));
	$r['intersect shape with property'] = $view($tc::intersect($subjects['objectShape'], $subjects['hasPropertyBar'], $subjects['hasPropertyBaz']));
	$r['intersect many offset values'] = $view($tc::intersect($subjects['array'], ...array_map(static fn (int $i) => new \PHPStan\Type\Accessory\HasOffsetValueType(new \PHPStan\Type\Constant\ConstantIntegerType($i), new \PHPStan\Type\IntegerType()), range(0, 40))));
	$r['intersect many offsets'] = $view($tc::intersect($subjects['array'], ...array_map(static fn (int $i) => new \PHPStan\Type\Accessory\HasOffsetType(new \PHPStan\Type\Constant\ConstantIntegerType($i)), range(0, 10))));
	$r['intersect accessories only'] = $view($tc::intersect($subjects['nonEmpty'], $subjects['listAccessory'], $subjects['hasOffset0']));
	$r['intersect string accessories only'] = $view($tc::intersect(new \PHPStan\Type\Accessory\AccessoryNonEmptyStringType(), new \PHPStan\Type\Accessory\AccessoryLowercaseStringType()));
	$r['union subtracted mixed'] = $view($tc::union($subjects['mixedMinusInt'], $subjects['mixedMinusIntString'], $subjects['string']));
	$r['union subtracted objects'] = $view($tc::union($subjects['objectMinusStd'], $subjects['throwableMinusError'], $subjects['errorObject']));
	$r['intersect subtracted objects'] = $view($tc::intersect($subjects['objectMinusStd'], $subjects['throwableMinusError'], $subjects['objectThrowable']));
	$r['remove subtracted'] = $view($tc::remove($subjects['mixedMinusInt'], $subjects['string']));
	$r['remove from union of shapes'] = $view($tc::remove($subjects['unionOfShapes'], $subjects['constShapeC']));
	$r['remove ranges'] = $view($tc::remove($subjects['unionRanges'], $subjects['range2-4']));
	$r['remove union from union'] = $view($tc::remove($subjects['unionConstMixed'], $subjects['unionConsts']));
	$r['union iterables'] = $view($tc::union($subjects['iterableIntString'], $subjects['iterableStringInt'], $subjects['arrayIntString']));
	$r['intersect iterables'] = $view($tc::intersect($subjects['iterableIntString'], $subjects['arrayIntString'], $subjects['list']));
	$r['intersect class strings'] = $view($tc::intersect($subjects['classStringStd'], $subjects['classStringThrowable'], $subjects['classString']));
	$r['union empty and non-empty strings'] = $view($tc::union($subjects['stringEmpty'], $subjects['nonFalsyLowercase'], $subjects['string0']));
	$r['union zero and non-falsy'] = $view($tc::union($subjects['string0'], $subjects['nonFalsyString']));
	$r['union decimal and non-decimal'] = $view($tc::union($subjects['decimalIntString'], $subjects['nonDecimalNumericString']));

	foreach ($r as $key => $value) {
		$observations["combinator $key"] = $value;
	}
}


// ---- TemplateTypeVariance ----
// isValidVariance() over every variance, strict and not, on pairs of
// template types (same/different scope and name), mixed, benevolent unions,
// never and plain types — the reasons carry the class scope of $templateType.
{
	$r = [];
	$classScope = \PHPStan\Type\Generic\TemplateTypeScope::createWithClass('Foo');
	$functionScope = \PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('foo');
	$int = new \PHPStan\Type\IntegerType();
	$string = new \PHPStan\Type\StringType();
	$constInt = new \PHPStan\Type\Constant\ConstantIntegerType(1);
	$intOrString = new \PHPStan\Type\UnionType([$int, $string]);
	$mixed = new \PHPStan\Type\MixedType();
	$explicitMixed = new \PHPStan\Type\MixedType(true);
	$never = new \PHPStan\Type\NeverType();
	$benevolent = new \PHPStan\Type\BenevolentUnionType([$int, $string]);
	$benevolentFloat = new \PHPStan\Type\BenevolentUnionType([new \PHPStan\Type\FloatType(), new \PHPStan\Type\NullType()]);
	$stdClass = new \PHPStan\Type\ObjectType(\stdClass::class);
	$tClass = \PHPStan\Type\Generic\TemplateTypeFactory::create($classScope, 'T', null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
	$tClassAgain = \PHPStan\Type\Generic\TemplateTypeFactory::create($classScope, 'T', null, \PHPStan\Type\Generic\TemplateTypeVariance::createCovariant());
	$uClass = \PHPStan\Type\Generic\TemplateTypeFactory::create($classScope, 'U', $int, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
	$tFunction = \PHPStan\Type\Generic\TemplateTypeFactory::create($functionScope, 'T', null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
	$tObject = \PHPStan\Type\Generic\TemplateTypeFactory::create($classScope, 'TObj', $stdClass, \PHPStan\Type\Generic\TemplateTypeVariance::createCovariant());
	$subjects = [
		'int' => $int,
		'string' => $string,
		'const int' => $constInt,
		'int|string' => $intOrString,
		'mixed' => $mixed,
		'explicit mixed' => $explicitMixed,
		'never' => $never,
		'benevolent int|string' => $benevolent,
		'benevolent float|null' => $benevolentFloat,
		'stdClass' => $stdClass,
		'T of class' => $tClass,
		'T of class again' => $tClassAgain,
		'U of class' => $uClass,
		'T of function' => $tFunction,
		'TObj of class' => $tObject,
	];
	$variances = [
		'invariant' => \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant(),
		'covariant' => \PHPStan\Type\Generic\TemplateTypeVariance::createCovariant(),
		'contravariant' => \PHPStan\Type\Generic\TemplateTypeVariance::createContravariant(),
		'bivariant' => \PHPStan\Type\Generic\TemplateTypeVariance::createBivariant(),
		'static' => \PHPStan\Type\Generic\TemplateTypeVariance::createStatic(),
	];
	foreach ([['class', $tClass], ['function', $tFunction]] as [$templateLabel, $templateType]) {
		foreach ($variances as $varianceLabel => $variance) {
			foreach ([false, true] as $strict) {
				foreach ($subjects as $aLabel => $a) {
					foreach ($subjects as $bLabel => $b) {
						try {
							$r[sprintf('%s %s %s: %s vs %s', $templateLabel, $varianceLabel, $strict ? 'strict' : 'loose', $aLabel, $bLabel)] = $view($variance->isValidVariance($templateType, $a, $b, $strict));
						} catch (\Throwable $e) {
							$r[sprintf('%s %s %s: %s vs %s', $templateLabel, $varianceLabel, $strict ? 'strict' : 'loose', $aLabel, $bLabel)] = [get_class($e), $e->getMessage()];
						}
					}
				}
			}
		}
	}
	foreach ($r as $key => $value) {
		$observations["template variance $key"] = $value;
	}
	$observations['native ' . \PHPStan\Type\Generic\TemplateTypeVariance::class] = (new ReflectionMethod(\PHPStan\Type\Generic\TemplateTypeVariance::class, 'compose'))->isInternal();
}


// ---- TemplateTypeVarianceMap / TemplateTypeMap ----
// The maps the generic types hand out under the real names: inference on a
// generic object produces the map, its set operations combine the inferred
// types through TypeCombinator, resolveToBounds() replaces the templates.
{
	$r = [];
	$classScope = \PHPStan\Type\Generic\TemplateTypeScope::createWithClass('Foo');
	$int = new \PHPStan\Type\IntegerType();
	$string = new \PHPStan\Type\StringType();
	$t = \PHPStan\Type\Generic\TemplateTypeFactory::create($classScope, 'T', null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
	$u = \PHPStan\Type\Generic\TemplateTypeFactory::create($classScope, 'U', $int, \PHPStan\Type\Generic\TemplateTypeVariance::createCovariant(), null, new \PHPStan\Type\Constant\ConstantIntegerType(5));
	$arrayObjectReflection = $stringReflectionProvider->getClass(\ArrayObject::class);
	$generic = new \PHPStan\Type\Generic\GenericObjectType(\ArrayObject::class, [$t, $u], null, $arrayObjectReflection);
	$concrete = new \PHPStan\Type\Generic\GenericObjectType(\ArrayObject::class, [$int, $string], null, $arrayObjectReflection);
	$inferred = $generic->inferTemplateTypes($concrete);
	$arrayInferred = (new \PHPStan\Type\ArrayType($t, $u))->inferTemplateTypes(new \PHPStan\Type\Constant\ConstantArrayType([new \PHPStan\Type\Constant\ConstantIntegerType(0)], [$string]));
	$maps = [
		'inferred' => $inferred,
		'array inferred' => $arrayInferred,
		'declared' => new \PHPStan\Type\Generic\TemplateTypeMap(['T' => $t, 'U' => $u]),
		'lower' => new \PHPStan\Type\Generic\TemplateTypeMap(['T' => $int], ['T' => $string, 'U' => $int]),
		'disjoint lower' => new \PHPStan\Type\Generic\TemplateTypeMap([], ['T' => $int, 'U' => $string, 'V' => $string]),
		'empty' => \PHPStan\Type\Generic\TemplateTypeMap::createEmpty(),
	];
	foreach ($maps as $label => $map) {
		$r["$label class"] = get_class($map);
		$r["$label types"] = $view($map->getTypes());
		$r["$label count"] = $map->count();
		$r["$label resolveToBounds"] = $view($map->resolveToBounds()->getTypes());
		$r["$label resolveToBounds identity"] = $map->resolveToBounds() === $map->resolveToBounds();
		$r["$label convertToLowerBoundTypes"] = $view($map->convertToLowerBoundTypes()->getTypes());
		foreach ($maps as $otherLabel => $other) {
			$r["$label union $otherLabel"] = $view($map->union($other)->getTypes());
			$r["$label benevolentUnion $otherLabel"] = $view($map->benevolentUnion($other)->getTypes());
			$r["$label intersect $otherLabel"] = $view($map->intersect($other)->getTypes());
			$r["$label union $otherLabel lower bounds"] = $view($map->union($other)->convertToLowerBoundTypes()->getTypes());
			$r["$label intersect $otherLabel lower bounds"] = $view($map->intersect($other)->convertToLowerBoundTypes()->getTypes());
		}
	}
	$r['generic variances'] = $view($generic->getVariances());
	$r['generic referenced template types'] = $view(array_map(static fn (\PHPStan\Type\Generic\TemplateTypeReference $ref): array => [$ref->getType()->getName(), $ref->getPositionVariance()->describe()], $generic->getReferencedTemplateTypes(\PHPStan\Type\Generic\TemplateTypeVariance::createCovariant())));
	$r['callable variance map'] = $view((new \PHPStan\Type\ClosureType())->getCallSiteVarianceMap()->getVariances());
	$genericStatic = new \PHPStan\Type\Generic\GenericStaticType($arrayObjectReflection, [$int, $string], null, []);
	$r['generic static changed template types'] = $view($genericStatic->changeSubtractedType(null));
	foreach ($r as $key => $value) {
		$observations["template map $key"] = $value;
	}
	$observations['native ' . \PHPStan\Type\Generic\TemplateTypeVarianceMap::class] = (new ReflectionMethod(\PHPStan\Type\Generic\TemplateTypeVarianceMap::class, 'getVariance'))->isInternal();
	$observations['native ' . \PHPStan\Type\Generic\TemplateTypeMap::class] = (new ReflectionMethod(\PHPStan\Type\Generic\TemplateTypeMap::class, 'union'))->isInternal();
	// the scopes and references the template types carry under the real names
	$r = [];
	$r['T scope'] = $t->getScope()->describe();
	$r['T scope equals U scope'] = $t->getScope()->equals($u->getScope());
	$r['T scope equals function scope'] = $t->getScope()->equals(\PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('foo'));
	$r['T scope equals anonymous'] = $t->getScope()->equals(\PHPStan\Type\Generic\TemplateTypeScope::createWithAnonymousFunction());
	$r['T describe precise'] = $t->describe(\PHPStan\Type\VerbosityLevel::precise());
	$r['T argument scope'] = $t->toArgument()->getScope()->describe();
	$r['generic referenced classes'] = $view(array_map(static fn (\PHPStan\Type\Generic\TemplateTypeReference $ref): array => [get_class($ref), $ref->getType()->describe(\PHPStan\Type\VerbosityLevel::precise()), $ref->getPositionVariance()->describe()], (new \PHPStan\Type\ArrayType($t, $u))->getReferencedTemplateTypes(\PHPStan\Type\Generic\TemplateTypeVariance::createContravariant())));
	foreach ($r as $key => $value) {
		$observations["template scope $key"] = $value;
	}
	$observations['native ' . \PHPStan\Type\Generic\TemplateTypeScope::class] = (new ReflectionMethod(\PHPStan\Type\Generic\TemplateTypeScope::class, 'equals'))->isInternal();
	$observations['native ' . \PHPStan\Type\Generic\TemplateTypeReference::class] = (new ReflectionMethod(\PHPStan\Type\Generic\TemplateTypeReference::class, 'getType'))->isInternal();
}


// ---- TemplateTypeHelper ----
// The traversals over compounds carrying template types under the real
// names: resolveTemplateTypes() over standins (plain, error, unresolved
// argument, absent), call-site variances and position variances, the
// bound/default resolutions, toArgument() over callables owning their
// templates, removeFinalByKeywordOverrides() and the generalization.
{
	$r = [];
	$classScope = \PHPStan\Type\Generic\TemplateTypeScope::createWithClass('Foo');
	$functionScope = \PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('foo');
	$int = new \PHPStan\Type\IntegerType();
	$string = new \PHPStan\Type\StringType();
	$t = \PHPStan\Type\Generic\TemplateTypeFactory::create($classScope, 'T', null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
	$u = \PHPStan\Type\Generic\TemplateTypeFactory::create($classScope, 'U', $int, \PHPStan\Type\Generic\TemplateTypeVariance::createCovariant(), null, new \PHPStan\Type\Constant\ConstantIntegerType(5));
	$k = \PHPStan\Type\Generic\TemplateTypeFactory::create($classScope, 'K', new \PHPStan\Type\UnionType([$int, $string]), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
	$f = \PHPStan\Type\Generic\TemplateTypeFactory::create($functionScope, 'F', new \PHPStan\Type\ObjectType(\stdClass::class), \PHPStan\Type\Generic\TemplateTypeVariance::createContravariant());
	$a = \PHPStan\Type\Generic\TemplateTypeFactory::create(\PHPStan\Type\Generic\TemplateTypeScope::createWithAnonymousFunction(), 'A', null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
	$arrayObjectReflection = $stringReflectionProvider->getClass(\ArrayObject::class);
	$finalStd = new \PHPStan\Type\ObjectType(\stdClass::class, null, $stringReflectionProvider->getClass(\stdClass::class)->asFinal());
	$ownedClosure = new \PHPStan\Type\ClosureType([new \PHPStan\Reflection\Native\NativeParameterReflection('x', false, $t, \PHPStan\Reflection\PassedByReference::createNo(), false, null)], $u, false, new \PHPStan\Type\Generic\TemplateTypeMap(['T' => $t, 'U' => $u]));
	$subjects = [
		'T' => $t,
		'U' => $u,
		'array<T, U>' => new \PHPStan\Type\ArrayType($t, $u),
		'array<K, F>' => new \PHPStan\Type\ArrayType($k, $f),
		'ArrayObject<T, U>' => new \PHPStan\Type\Generic\GenericObjectType(\ArrayObject::class, [$t, $u], null, $arrayObjectReflection),
		'T|U|null' => new \PHPStan\Type\UnionType([$t, $u, new \PHPStan\Type\NullType()]),
		'Closure(T): U' => new \PHPStan\Type\ClosureType([new \PHPStan\Reflection\Native\NativeParameterReflection('x', false, $t, \PHPStan\Reflection\PassedByReference::createNo(), false, null)], $u, false),
		'owning Closure(T): U' => $ownedClosure,
		'array<A, T>' => new \PHPStan\Type\ArrayType($a, $t),
		'final stdClass' => $finalStd,
		'array<int, final stdClass>' => new \PHPStan\Type\ArrayType($int, $finalStd),
		'int' => $int,
	];
	$standins = [
		'plain' => new \PHPStan\Type\Generic\TemplateTypeMap(['T' => $string, 'U' => new \PHPStan\Type\Constant\ConstantIntegerType(1), 'K' => $int, 'F' => new \PHPStan\Type\ObjectType(\ArrayObject::class)]),
		'error' => new \PHPStan\Type\Generic\TemplateTypeMap(['T' => new \PHPStan\Type\ErrorType(), 'U' => $string]),
		'unresolved' => new \PHPStan\Type\Generic\TemplateTypeMap(['T' => new \PHPStan\Type\Generic\UnresolvedTemplateArgumentType(new \PhpParser\Node\Expr\Variable('x'), $t, $string), 'U' => $int]),
		'empty' => \PHPStan\Type\Generic\TemplateTypeMap::createEmpty(),
	];
	$callSiteVariances = [
		'none' => \PHPStan\Type\Generic\TemplateTypeVarianceMap::createEmpty(),
		'T covariant' => new \PHPStan\Type\Generic\TemplateTypeVarianceMap(['T' => \PHPStan\Type\Generic\TemplateTypeVariance::createCovariant(), 'U' => \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()]),
		'T contravariant' => new \PHPStan\Type\Generic\TemplateTypeVarianceMap(['T' => \PHPStan\Type\Generic\TemplateTypeVariance::createContravariant(), 'U' => \PHPStan\Type\Generic\TemplateTypeVariance::createContravariant(), 'F' => \PHPStan\Type\Generic\TemplateTypeVariance::createCovariant()]),
		'bivariant' => new \PHPStan\Type\Generic\TemplateTypeVarianceMap(['T' => \PHPStan\Type\Generic\TemplateTypeVariance::createBivariant(), 'U' => \PHPStan\Type\Generic\TemplateTypeVariance::createBivariant()]),
	];
	$positions = [
		'invariant' => \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant(),
		'covariant' => \PHPStan\Type\Generic\TemplateTypeVariance::createCovariant(),
		'contravariant' => \PHPStan\Type\Generic\TemplateTypeVariance::createContravariant(),
	];
	foreach ($subjects as $label => $subject) {
		foreach (['resolveToBounds', 'resolveToDefaults', 'toArgument', 'removeFinalByKeywordOverrides'] as $method) {
			$result = \PHPStan\Type\Generic\TemplateTypeHelper::$method($subject);
			$r["$method $label"] = $view($result);
			$r["$method $label identity"] = $result === $subject;
		}
		foreach ($standins as $standinLabel => $standin) {
			foreach ($callSiteVariances as $varianceLabel => $variances) {
				foreach ($positions as $positionLabel => $position) {
					foreach ([false, true] as $keep) {
						$result = \PHPStan\Type\Generic\TemplateTypeHelper::resolveTemplateTypes($subject, $standin, $variances, $position, $keep);
						$r[sprintf('resolveTemplateTypes %s / %s / %s / %s%s', $label, $standinLabel, $varianceLabel, $positionLabel, $keep ? ' keep' : '')] = [$view($result), $result === $subject];
					}
				}
			}
		}
	}
	$generalizeCases = [
		'constant int' => new \PHPStan\Type\Constant\ConstantIntegerType(1),
		'constant string' => new \PHPStan\Type\Constant\ConstantStringType('foo'),
		'int' => $int,
		'constant array' => new \PHPStan\Type\Constant\ConstantArrayType([new \PHPStan\Type\Constant\ConstantIntegerType(0)], [$string]),
		'true' => new \PHPStan\Type\Constant\ConstantBooleanType(true),
		'stdClass' => new \PHPStan\Type\ObjectType(\stdClass::class),
	];
	foreach (['T' => $t, 'U' => $u, 'K' => $k, 'F' => $f] as $label => $templateType) {
		foreach ($generalizeCases as $caseLabel => $case) {
			$r["generalizeInferredTemplateType $label / $caseLabel"] = $view(\PHPStan\Type\Generic\TemplateTypeHelper::generalizeInferredTemplateType($templateType, $case));
		}
	}
	foreach ($r as $key => $value) {
		$observations["template helper $key"] = $value;
	}
	$observations['native ' . \PHPStan\Type\Generic\TemplateTypeHelper::class] = (new ReflectionMethod(\PHPStan\Type\Generic\TemplateTypeHelper::class, 'resolveToBounds'))->isInternal();
}


// ---- KeyOfType / ValueOfType / OffsetAccessType / ClassConstantAccessType / NewObjectType / ConditionalType / ConditionalTypeForParameter / LateResolvableArrayShapeType / UnresolvedTemplateArgumentType ----
// the late-resolvable family over the shared LateResolvableTypeTrait
// registrar (resolve() memoized in the trait's $result slot, the hundred
// forwards to the resolved type, isSuperTypeOfDefault() held to maybe while
// unresolvable, the CompoundType reversals), each class's own resolution:
// key-of/value-of over arrays, shapes, enums and templates, offset access,
// class constant access through the reflection provider (still registered
// from the string section), new<>, the conditional types with their
// normalized branches, the late-resolvable array shape collapsing into a
// ConstantArrayType (or an ErrorType), and the observation-pass marker
// delegating to its initial type or the template's bound; PHP subclasses
// over the two non-final natives come along
$latePhpVersion = new \PHPStan\Php\PhpVersion(80400);
$lateScope = \PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('late');
$lateOutOfClassScope = new \PHPStan\Analyser\OutOfClassScope();
$lateTemplate = static fn (string $name, ?\PHPStan\Type\Type $bound, ?\PHPStan\Type\Type $default = null): \PHPStan\Type\Type => \PHPStan\Type\Generic\TemplateTypeFactory::create($lateScope, $name, $bound, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant(), null, $default);
$lateT = $lateTemplate('T', null);
$lateTArray = $lateTemplate('TArray', new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()));
$lateTKey = $lateTemplate('TKey', new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]));
$lateTString = $lateTemplate('TString', new \PHPStan\Type\StringType());
$lateTInt = $lateTemplate('TInt', new \PHPStan\Type\IntegerType());
$lateTBackedEnum = $lateTemplate('TBackedEnum', new \PHPStan\Type\ObjectType(\BackedEnum::class));
$lateTEnum = $lateTemplate('TEnum', new \PHPStan\Type\ObjectType($objectEnum));
$lateTObject = $lateTemplate('TObject', new \PHPStan\Type\ObjectType(\Exception::class));
$lateTShapeNode = $lateTemplate('TShapeNode', new \PHPStan\Type\ObjectType(\PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode::class));
$lateTDefault = $lateTemplate('TDefault', new \PHPStan\Type\StringType(), new \PHPStan\Type\Constant\ConstantStringType('dflt'));
$lateShape = new \PHPStan\Type\Constant\ConstantArrayType([new \PHPStan\Type\Constant\ConstantStringType('a'), new \PHPStan\Type\Constant\ConstantStringType('b')], [new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()], [2], [1]);
$lateList = new \PHPStan\Type\IntersectionType([new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()), new \PHPStan\Type\Accessory\AccessoryArrayListType()]);
$lateShapeNodeStatic = new \PHPStan\Type\StaticType($stringReflectionProvider->getClass(\PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode::class));
$lateShapeNodeObject = new \PHPStan\Type\ObjectType(\PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode::class);
$lateSiteA = new \PhpParser\Node\Expr\Variable('a');
$lateSiteB = new \PhpParser\Node\Expr\Variable('b');
$lateSubjects = static fn (): array => [
	'keyOfArray' => new \PHPStan\Type\KeyOfType(new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType())),
	'keyOfShape' => new \PHPStan\Type\KeyOfType($lateShape),
	'keyOfList' => new \PHPStan\Type\KeyOfType($lateList),
	'keyOfT' => new \PHPStan\Type\KeyOfType($lateT),
	'keyOfTArray' => new \PHPStan\Type\KeyOfType($lateTArray),
	'keyOfMixed' => new \PHPStan\Type\KeyOfType(new \PHPStan\Type\MixedType()),
	'keyOfInt' => new \PHPStan\Type\KeyOfType(new \PHPStan\Type\IntegerType()),
	'keyOfKeyOfShape' => new \PHPStan\Type\KeyOfType(new \PHPStan\Type\KeyOfType($lateShape)),
	'keyOfEnum' => new \PHPStan\Type\KeyOfType(new \PHPStan\Type\ObjectType($objectEnum)),
	'valueOfArray' => new \PHPStan\Type\ValueOfType(new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType())),
	'valueOfShape' => new \PHPStan\Type\ValueOfType($lateShape),
	'valueOfEnum' => new \PHPStan\Type\ValueOfType(new \PHPStan\Type\ObjectType($objectEnum)),
	'valueOfBackedEnum' => new \PHPStan\Type\ValueOfType(new \PHPStan\Type\ObjectType($objectBackedEnum)),
	'valueOfEnumCase' => new \PHPStan\Type\ValueOfType(new \PHPStan\Type\Enum\EnumCaseObjectType($objectBackedEnum, 'CASE_ONE')),
	'valueOfTBackedEnum' => new \PHPStan\Type\ValueOfType($lateTBackedEnum),
	'valueOfTEnum' => new \PHPStan\Type\ValueOfType($lateTEnum),
	'valueOfT' => new \PHPStan\Type\ValueOfType($lateT),
	'valueOfMixed' => new \PHPStan\Type\ValueOfType(new \PHPStan\Type\MixedType()),
	'valueOfString' => new \PHPStan\Type\ValueOfType(new \PHPStan\Type\StringType()),
	'offsetShapeA' => new \PHPStan\Type\OffsetAccessType($lateShape, new \PHPStan\Type\Constant\ConstantStringType('a')),
	'offsetShapeMissing' => new \PHPStan\Type\OffsetAccessType($lateShape, new \PHPStan\Type\Constant\ConstantStringType('missing')),
	'offsetArrayInt' => new \PHPStan\Type\OffsetAccessType(new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()), new \PHPStan\Type\IntegerType()),
	'offsetTArrayTKey' => new \PHPStan\Type\OffsetAccessType($lateTArray, $lateTKey),
	'offsetShapeTKey' => new \PHPStan\Type\OffsetAccessType($lateShape, $lateTKey),
	'offsetStringInt' => new \PHPStan\Type\OffsetAccessType(new \PHPStan\Type\StringType(), new \PHPStan\Type\IntegerType()),
	'offsetMixedInt' => new \PHPStan\Type\OffsetAccessType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\IntegerType()),
	'constantStaticKindList' => new \PHPStan\Type\ClassConstantAccessType($lateShapeNodeStatic, 'KIND_LIST'),
	'constantObjectKindArray' => new \PHPStan\Type\ClassConstantAccessType($lateShapeNodeObject, 'KIND_ARRAY'),
	'constantMissing' => new \PHPStan\Type\ClassConstantAccessType($lateShapeNodeObject, 'KIND_NONEXISTENT'),
	'constantTShapeNode' => new \PHPStan\Type\ClassConstantAccessType($lateTShapeNode, 'KIND_LIST'),
	'constantOnInt' => new \PHPStan\Type\ClassConstantAccessType(new \PHPStan\Type\IntegerType(), 'KIND_LIST'),
	'newException' => new \PHPStan\Type\NewObjectType(new \PHPStan\Type\ObjectType(\Exception::class)),
	'newClassString' => new \PHPStan\Type\NewObjectType(new \PHPStan\Type\Generic\GenericClassStringType(new \PHPStan\Type\ObjectType(\Exception::class))),
	'newTObject' => new \PHPStan\Type\NewObjectType($lateTObject),
	'newT' => new \PHPStan\Type\NewObjectType($lateT),
	'newString' => new \PHPStan\Type\NewObjectType(new \PHPStan\Type\StringType()),
	'newMixed' => new \PHPStan\Type\NewObjectType(new \PHPStan\Type\MixedType()),
	'condIntIsInt' => new \PHPStan\Type\ConditionalType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType(), new \PHPStan\Type\BooleanType(), false),
	'condIntIsIntNegated' => new \PHPStan\Type\ConditionalType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType(), new \PHPStan\Type\BooleanType(), true),
	'condStringIsInt' => new \PHPStan\Type\ConditionalType(new \PHPStan\Type\StringType(), new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType(), new \PHPStan\Type\BooleanType(), false),
	'condUnionIsInt' => new \PHPStan\Type\ConditionalType(new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]), new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType(), new \PHPStan\Type\BooleanType(), false),
	'condTIsInt' => new \PHPStan\Type\ConditionalType($lateT, new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType(), new \PHPStan\Type\BooleanType(), false),
	'condTIsIntSubject' => new \PHPStan\Type\ConditionalType($lateT, new \PHPStan\Type\IntegerType(), $lateT, new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), $lateT), false),
	'condTIsIntSubjectNegated' => new \PHPStan\Type\ConditionalType($lateT, new \PHPStan\Type\IntegerType(), $lateT, new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), $lateT), true),
	'condTKeyIsInt' => new \PHPStan\Type\ConditionalType($lateTKey, new \PHPStan\Type\IntegerType(), $lateTKey, $lateTKey, false),
	'condTIntIsInt' => new \PHPStan\Type\ConditionalType($lateTInt, new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType(), new \PHPStan\Type\BooleanType(), false),
	'condTStringIsInt' => new \PHPStan\Type\ConditionalType($lateTString, new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType(), new \PHPStan\Type\BooleanType(), false),
	'condIntIsT' => new \PHPStan\Type\ConditionalType(new \PHPStan\Type\IntegerType(), $lateT, new \PHPStan\Type\StringType(), new \PHPStan\Type\BooleanType(), false),
	'condParamIsInt' => new \PHPStan\Type\ConditionalTypeForParameter('$x', new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType(), new \PHPStan\Type\BooleanType(), false),
	'condParamIsIntNegated' => new \PHPStan\Type\ConditionalTypeForParameter('$x', new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType(), new \PHPStan\Type\BooleanType(), true),
	'condParamYIsT' => new \PHPStan\Type\ConditionalTypeForParameter('$y', $lateT, new \PHPStan\Type\StringType(), new \PHPStan\Type\NullType(), false),
	'shapeTKeyInt' => \PHPStan\Type\LateResolvableArrayShapeType::create([[$lateTKey, new \PHPStan\Type\IntegerType(), false]], null, \PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode::KIND_ARRAY),
	'shapeTKeyIntOptionalUnsealedMixed' => \PHPStan\Type\LateResolvableArrayShapeType::create([[$lateTKey, new \PHPStan\Type\IntegerType(), true], [null, new \PHPStan\Type\StringType(), false]], [null, new \PHPStan\Type\MixedType()], \PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode::KIND_ARRAY),
	'shapeTKeyUnsealedString' => \PHPStan\Type\LateResolvableArrayShapeType::create([[$lateTKey, new \PHPStan\Type\IntegerType(), false], [new \PHPStan\Type\Constant\ConstantStringType('b'), new \PHPStan\Type\StringType(), false]], [null, new \PHPStan\Type\StringType()], \PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode::KIND_NON_EMPTY_ARRAY),
	'shapeTKeyUnsealedTKeyBool' => \PHPStan\Type\LateResolvableArrayShapeType::create([[$lateTKey, new \PHPStan\Type\IntegerType(), false]], [$lateTKey, new \PHPStan\Type\BooleanType()], \PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode::KIND_ARRAY),
	'shapeListTIntKey' => \PHPStan\Type\LateResolvableArrayShapeType::create([[$lateTInt, new \PHPStan\Type\StringType(), false]], null, \PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode::KIND_LIST),
	'shapeNonEmptyListTIntKeyUnsealed' => \PHPStan\Type\LateResolvableArrayShapeType::create([[$lateTInt, new \PHPStan\Type\StringType(), false]], [null, new \PHPStan\Type\StringType()], \PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode::KIND_NON_EMPTY_LIST),
	'shapeTObjectKey' => \PHPStan\Type\LateResolvableArrayShapeType::create([[$lateTObject, new \PHPStan\Type\IntegerType(), false]], null, \PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode::KIND_ARRAY),
	'shapeUnsealedTObjectKey' => \PHPStan\Type\LateResolvableArrayShapeType::create([[new \PHPStan\Type\Constant\ConstantStringType('a'), new \PHPStan\Type\IntegerType(), false]], [$lateTObject, new \PHPStan\Type\IntegerType()], \PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode::KIND_ARRAY),
	'shapeTKeyAndConstKeys' => \PHPStan\Type\LateResolvableArrayShapeType::create([[new \PHPStan\Type\Constant\ConstantIntegerType(0), new \PHPStan\Type\IntegerType(), false], [$lateTKey, new \PHPStan\Type\StringType(), false], [new \PHPStan\Type\Constant\ConstantStringType('class-name'), new \PHPStan\Type\BooleanType(), true]], [new \PHPStan\Type\UnionType([new \PHPStan\Type\Constant\ConstantIntegerType(0), new \PHPStan\Type\Constant\ConstantStringType('x')]), new \PHPStan\Type\FloatType()], \PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode::KIND_ARRAY),
	'shapeTDefaultKey' => \PHPStan\Type\LateResolvableArrayShapeType::create([[$lateTDefault, new \PHPStan\Type\IntegerType(), false]], null, \PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode::KIND_ARRAY),
	'unresolvedNull' => new \PHPStan\Type\Generic\UnresolvedTemplateArgumentType($lateSiteA, $lateTObject, null),
	'unresolvedInt' => new \PHPStan\Type\Generic\UnresolvedTemplateArgumentType($lateSiteA, $lateT, new \PHPStan\Type\IntegerType()),
	'unresolvedIntSiteB' => new \PHPStan\Type\Generic\UnresolvedTemplateArgumentType($lateSiteB, $lateT, new \PHPStan\Type\IntegerType()),
	'unresolvedDefault' => new \PHPStan\Type\Generic\UnresolvedTemplateArgumentType($lateSiteA, $lateTDefault, null),
	'unresolvedArrayOfT' => new \PHPStan\Type\Generic\UnresolvedTemplateArgumentType($lateSiteA, $lateT, new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), $lateT)),
	'unresolvedEnum' => new \PHPStan\Type\Generic\UnresolvedTemplateArgumentType($lateSiteB, $lateTEnum, new \PHPStan\Type\ObjectType($objectEnum)),
];
$lateOthers = static fn (array $subjects): array => [
	'int' => new \PHPStan\Type\IntegerType(),
	'int1' => new \PHPStan\Type\Constant\ConstantIntegerType(1),
	'string' => new \PHPStan\Type\StringType(),
	'stringA' => new \PHPStan\Type\Constant\ConstantStringType('a'),
	'stringList' => new \PHPStan\Type\Constant\ConstantStringType('list'),
	'bool' => new \PHPStan\Type\BooleanType(),
	'null' => new \PHPStan\Type\NullType(),
	'never' => new \PHPStan\Type\NeverType(),
	'mixed' => new \PHPStan\Type\MixedType(),
	'union' => new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]),
	'array' => new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()),
	'shape' => $lateShape,
	'exception' => new \PHPStan\Type\ObjectType(\Exception::class),
	'enum' => new \PHPStan\Type\ObjectType($objectEnum),
	'templateT' => $lateT,
	'templateTKey' => $lateTKey,
	'keyOfShape' => $subjects['keyOfShape'],
	'valueOfShape' => $subjects['valueOfShape'],
	'condTIsInt' => $subjects['condTIsInt'],
	'unresolvedInt' => $subjects['unresolvedInt'],
];
{
	$r = [];
	$subjects = $lateSubjects();
	$others = $lateOthers($subjects);
	$lateView = static function (mixed $v) use ($view): mixed {
		$v = $view($v);
		// the site's spl_object_id (the marker's cache-level description)
		// depends on the allocation order, which differs per implementation
		return is_string($v) ? preg_replace('~unresolved#\d+~', 'unresolved#N', $v) : (is_array($v) ? array_map(static fn ($e) => is_string($e) ? preg_replace('~unresolved#\d+~', 'unresolved#N', $e) : $e, $v) : $v);
	};
	$attempt = static function (callable $body) use ($lateView): mixed {
		try {
			return $lateView($body());
		} catch (\Throwable $e) {
			return [get_class($e), preg_replace('~#\d+~', '#N', $e->getMessage())];
		}
	};
	$identity = static fn (\PHPStan\Type\Type $t): \PHPStan\Type\Type => $t;
	$resolveTemplates = static fn (\PHPStan\Type\Type $t): \PHPStan\Type\Type => $t instanceof \PHPStan\Type\Generic\TemplateType ? \PHPStan\Type\Generic\TemplateTypeHelper::resolveToBounds($t) : $t->traverse(static fn (\PHPStan\Type\Type $i): \PHPStan\Type\Type => $i instanceof \PHPStan\Type\Generic\TemplateType ? \PHPStan\Type\Generic\TemplateTypeHelper::resolveToBounds($i) : $i);
	$toInt = static fn (\PHPStan\Type\Type $t): \PHPStan\Type\Type => new \PHPStan\Type\IntegerType();
	foreach ($subjects as $name => $subject) {
		$reflection = new ReflectionClass($subject);
		$r["$name class"] = [get_class($subject), $reflection->isFinal(), $reflection->getInterfaceNames(), $subject instanceof \PHPStan\Type\CompoundType, $subject instanceof \PHPStan\Type\LateResolvableType];
		foreach (['typeOnly' => \PHPStan\Type\VerbosityLevel::typeOnly(), 'value' => \PHPStan\Type\VerbosityLevel::value(), 'precise' => \PHPStan\Type\VerbosityLevel::precise(), 'cache' => \PHPStan\Type\VerbosityLevel::cache()] as $levelName => $level) {
			$r["$name describe $levelName"] = $attempt(static fn () => $subject->describe($level));
		}
		if ($subject instanceof \PHPStan\Type\LateResolvableType) {
			$r["$name isResolvable"] = $subject->isResolvable();
			$r["$name resolve"] = $attempt(static fn () => $subject->resolve());
			$r["$name resolve again"] = $attempt(static fn () => $subject->resolve() === $subject->resolve());
		}
		$r["$name hasTemplateOrLateResolvableType"] = $subject->hasTemplateOrLateResolvableType();
		$r["$name getReferencedClasses"] = $lateView($subject->getReferencedClasses());
		$r["$name getReferencedTemplateTypes"] = $lateView($subject->getReferencedTemplateTypes(\PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()));
		$r["$name toPhpDocNode"] = $attempt(static fn () => $subject->toPhpDocNode());
		$r["$name traverse identity"] = $attempt(static fn () => $subject->traverse($identity) === $subject);
		$r["$name traverse resolveTemplates"] = $attempt(static fn () => [$subject->traverse($resolveTemplates), $subject->traverse($resolveTemplates) === $subject]);
		$r["$name traverse toInt"] = $attempt(static fn () => $subject->traverse($toInt));
		$r["$name generalize"] = [$attempt(static fn () => $subject->generalize(\PHPStan\Type\GeneralizePrecision::lessSpecific())), $attempt(static fn () => $subject->generalize(\PHPStan\Type\GeneralizePrecision::moreSpecific()))];
		foreach ($others as $otherName => $other) {
			$r["$name isSuperTypeOf $otherName"] = $attempt(static fn () => $subject->isSuperTypeOf($other));
			$r["$name accepts $otherName"] = $attempt(static fn () => [$subject->accepts($other, true), $subject->accepts($other, false)]);
			$r["$name equals $otherName"] = $attempt(static fn () => [$subject->equals($other), $other->equals($subject)]);
			$r["$otherName isSuperTypeOf $name"] = $attempt(static fn () => $other->isSuperTypeOf($subject));
			$r["$otherName accepts $name"] = $attempt(static fn () => [$other->accepts($subject, true), $other->accepts($subject, false)]);
			$r["$name isSubTypeOf $otherName"] = $attempt(static fn () => $subject->isSubTypeOf($other));
			$r["$name isAcceptedBy $otherName"] = $attempt(static fn () => [$subject->isAcceptedBy($other, true), $subject->isAcceptedBy($other, false)]);
			$r["$name tryRemove $otherName"] = $attempt(static fn () => $subject->tryRemove($other));
			$r["$name union $otherName"] = $attempt(static fn () => \PHPStan\Type\TypeCombinator::union($subject, $other));
			$r["$name intersect $otherName"] = $attempt(static fn () => \PHPStan\Type\TypeCombinator::intersect($subject, $other));
			$r["$name remove $otherName"] = $attempt(static fn () => \PHPStan\Type\TypeCombinator::remove($subject, $other));
			$r["$name traverseSimultaneously $otherName"] = $attempt(static fn () => [$subject->traverseSimultaneously($other, static fn ($a, $b) => $b), $subject->traverseSimultaneously($other, static fn ($a, $b) => $a) === $subject]);
			$r["$name isGreaterThan $otherName"] = $attempt(static fn () => [$subject->isGreaterThan($other, $latePhpVersion), $subject->isGreaterThanOrEqual($other, $latePhpVersion), $subject->isSmallerThan($other, $latePhpVersion), $subject->isSmallerThanOrEqual($other, $latePhpVersion)]);
			$r["$name looseCompare $otherName"] = $attempt(static fn () => $subject->looseCompare($other, $latePhpVersion));
			$r["$name hasOffsetValueType $otherName"] = $attempt(static fn () => [$subject->hasOffsetValueType($other), $subject->getOffsetValueType($other)]);
			$r["$name inferTemplateTypes $otherName"] = $attempt(static fn () => $subject->inferTemplateTypes($other));
		}
		foreach (['toBoolean', 'toNumber', 'toInteger', 'toFloat', 'toString', 'toArray', 'toArrayKey', 'toBitwiseNotType', 'toAbsoluteNumber', 'toGetClassResultType', 'toObjectTypeForInstanceofCheck',
			'isTrue', 'isFalse', 'isBoolean', 'isScalar', 'isNull', 'isInteger', 'isFloat', 'isString', 'isNumericString', 'isDecimalIntegerString', 'isNonEmptyString', 'isNonFalsyString', 'isLiteralString', 'isLowercaseString', 'isUppercaseString', 'isClassString', 'isVoid',
			'isConstantValue', 'isConstantScalarValue', 'getConstantScalarTypes', 'getConstantScalarValues', 'getFiniteTypes', 'isObject', 'isEnum', 'getArrays', 'getConstantArrays', 'getConstantStrings', 'getObjectClassNames', 'getObjectClassReflections',
			'getClassStringType', 'getClassStringObjectType', 'getObjectTypeOrClassStringObjectType', 'canAccessProperties', 'canCallMethods', 'canAccessConstants', 'isIterable', 'isIterableAtLeastOnce', 'getArraySize', 'getIterableKeyType', 'getFirstIterableKeyType', 'getLastIterableKeyType',
			'getIterableValueType', 'getFirstIterableValueType', 'getLastIterableValueType', 'isArray', 'isConstantArray', 'isOversizedArray', 'isList', 'isOffsetAccessible', 'isOffsetAccessLegal', 'getKeysArray', 'getValuesArray', 'flipArray', 'popArray', 'shiftArray', 'shuffleArray',
			'makeListMaybe', 'makeAllArrayKeysOptional', 'filterArrayRemovingFalsey', 'getEnumCases', 'getEnumCaseObject', 'isCallable', 'isCloneable'] as $method) {
			$r["$name $method"] = $attempt(static fn () => $subject->$method());
		}
		$r["$name toCoercedArgumentType"] = $attempt(static fn () => [$subject->toCoercedArgumentType(true), $subject->toCoercedArgumentType(false)]);
		$r["$name exponentiate"] = $attempt(static fn () => $subject->exponentiate($others['int1']));
		$r["$name setOffsetValueType"] = $attempt(static fn () => [$subject->setOffsetValueType($others['stringA'], $others['int']), $subject->setOffsetValueType(null, $others['int'], false), $subject->setOffsetValueType(offsetType: null, valueType: $others['bool'])]);
		$r["$name setExistingOffsetValueType"] = $attempt(static fn () => $subject->setExistingOffsetValueType($others['stringA'], $others['int']));
		$r["$name unsetOffset"] = $attempt(static fn () => $subject->unsetOffset($others['stringA']));
		$r["$name getKeysArrayFiltered"] = $attempt(static fn () => $subject->getKeysArrayFiltered($others['int'], \PHPStan\TrinaryLogic::createYes()));
		$r["$name chunkArray"] = $attempt(static fn () => $subject->chunkArray($others['int1'], \PHPStan\TrinaryLogic::createNo()));
		$r["$name fillKeysArray"] = $attempt(static fn () => $subject->fillKeysArray($others['int']));
		$r["$name intersectKeyArray"] = $attempt(static fn () => $subject->intersectKeyArray($others['shape']));
		$r["$name reverseArray"] = $attempt(static fn () => $subject->reverseArray(\PHPStan\TrinaryLogic::createMaybe()));
		$r["$name searchArray"] = $attempt(static fn () => [$subject->searchArray($others['int1']), $subject->searchArray($others['int1'], \PHPStan\TrinaryLogic::createYes()), $subject->searchArray(needleType: $others['stringA'])]);
		$r["$name sliceArray"] = $attempt(static fn () => $subject->sliceArray($others['int1'], $others['int1'], \PHPStan\TrinaryLogic::createNo()));
		$r["$name spliceArray"] = $attempt(static fn () => $subject->spliceArray($others['int1'], $others['int1'], $others['array']));
		$r["$name truncateListToSize"] = $attempt(static fn () => $subject->truncateListToSize($others['int1']));
		$r["$name mapValueType"] = $attempt(static fn () => [$subject->mapValueType($toInt), $subject->mapKeyType($toInt)]);
		$r["$name changeKeyCaseArray"] = $attempt(static fn () => [$subject->changeKeyCaseArray(null), $subject->changeKeyCaseArray(1)]);
		$r["$name getTemplateType"] = $attempt(static fn () => $subject->getTemplateType(\ArrayIterator::class, 'TKey'));
		$r["$name getSmallerType"] = $attempt(static fn () => [$subject->getSmallerType($latePhpVersion), $subject->getSmallerOrEqualType($latePhpVersion), $subject->getGreaterType($latePhpVersion), $subject->getGreaterOrEqualType($latePhpVersion)]);
		$r["$name toClassConstantType"] = $attempt(static fn () => $subject->toClassConstantType($stringReflectionProvider));
		$r["$name toObjectTypeForIsACheck"] = $attempt(static fn () => $subject->toObjectTypeForIsACheck($others['exception'], true, false));
		$r["$name getCallableParametersAcceptors"] = $attempt(static fn () => $subject->getCallableParametersAcceptors($lateOutOfClassScope));
		foreach (['value', 'name', 'nonexistent'] as $memberName) {
			$r["$name hasProperty $memberName"] = $attempt(static fn () => [$subject->hasProperty($memberName), $subject->hasInstanceProperty($memberName), $subject->hasStaticProperty($memberName)]);
			$r["$name getProperty $memberName"] = $attempt(static fn () => $subject->getProperty($memberName, $lateOutOfClassScope)->getReadableType());
			$r["$name getInstanceProperty $memberName"] = $attempt(static fn () => $subject->getInstanceProperty($memberName, $lateOutOfClassScope)->getReadableType());
			$r["$name getStaticProperty $memberName"] = $attempt(static fn () => $subject->getStaticProperty($memberName, $lateOutOfClassScope)->getReadableType());
			$r["$name getUnresolvedPropertyPrototype $memberName"] = $attempt(static fn () => [get_class($subject->getUnresolvedPropertyPrototype($memberName, $lateOutOfClassScope)), get_class($subject->getUnresolvedInstancePropertyPrototype($memberName, $lateOutOfClassScope)), get_class($subject->getUnresolvedStaticPropertyPrototype($memberName, $lateOutOfClassScope))]);
		}
		foreach (['getMessage', 'cases', 'nonexistent'] as $methodName) {
			$r["$name hasMethod $methodName"] = $attempt(static fn () => $subject->hasMethod($methodName));
			$r["$name getMethod $methodName"] = $attempt(static fn () => $subject->getMethod($methodName, $lateOutOfClassScope)->getName());
			$r["$name getUnresolvedMethodPrototype $methodName"] = $attempt(static fn () => get_class($subject->getUnresolvedMethodPrototype($methodName, $lateOutOfClassScope)));
		}
		foreach (['KIND_LIST', 'FOO', 'NONEXISTENT'] as $constantName) {
			$r["$name hasConstant $constantName"] = $attempt(static fn () => $subject->hasConstant($constantName));
			$r["$name getConstant $constantName"] = $attempt(static fn () => $subject->getConstant($constantName)->getValueType());
		}
	}
	// the class-specific getters
	foreach (['keyOfArray', 'keyOfT', 'newException', 'newT'] as $name) {
		$r["$name getType"] = $lateView($subjects[$name]->getType());
	}
	foreach (['condIntIsInt', 'condTIsIntSubjectNegated'] as $name) {
		$r["$name getters"] = $lateView([$subjects[$name]->getSubject(), $subjects[$name]->getTarget(), $subjects[$name]->getIf(), $subjects[$name]->getElse(), $subjects[$name]->isNegated()]);
	}
	foreach (['condParamIsInt', 'condParamIsIntNegated', 'condParamYIsT'] as $name) {
		$r["$name getters"] = $lateView([$subjects[$name]->getParameterName(), $subjects[$name]->getTarget(), $subjects[$name]->getIf(), $subjects[$name]->getElse(), $subjects[$name]->isNegated()]);
		$r["$name changeParameterName"] = $lateView([$subjects[$name]->changeParameterName('$z'), $subjects[$name]->changeParameterName('$z')->describe(\PHPStan\Type\VerbosityLevel::precise()), $subjects[$name]->changeParameterName('$z')->equals($subjects[$name])]);
		foreach (['int', 'string', 'templateT', 'union'] as $otherName) {
			$r["$name toConditional $otherName"] = $lateView([$subjects[$name]->toConditional($others[$otherName]), $subjects[$name]->toConditional($others[$otherName])->resolve(), $subjects[$name]->toConditional($others[$otherName])->isResolvable()]);
		}
	}
	// a conditional's if/else pair compared to another conditional's, and
	// the normalized branches (the subject replaced) through resolve()
	$r['cond pairs'] = $lateView([
		$subjects['condIntIsInt']->isSuperTypeOf($subjects['condIntIsIntNegated']),
		$subjects['condTIsInt']->isSuperTypeOf($subjects['condIntIsInt']),
		$subjects['condTIsIntSubject']->isSuperTypeOf($subjects['condTIsIntSubjectNegated']),
		$subjects['condParamIsInt']->isSuperTypeOf($subjects['condParamIsIntNegated']),
		$subjects['condParamYIsT']->isSuperTypeOf($subjects['condParamIsInt']),
		$subjects['condIntIsInt']->equals($subjects['condIntIsIntNegated']),
		$subjects['condParamIsInt']->equals($subjects['condParamIsIntNegated']),
	]);
	// the observation-pass marker
	foreach (['unresolvedNull', 'unresolvedInt', 'unresolvedIntSiteB', 'unresolvedDefault', 'unresolvedArrayOfT', 'unresolvedEnum'] as $name) {
		$marker = $subjects[$name];
		$r["$name getters"] = $lateView([get_class($marker->getSite()), $marker->getSite() === $lateSiteA, $marker->getTemplateName(), $marker->getTemplate(), $marker->getInitialType(), $marker->getDelegate()]);
		$r["$name withInitialType"] = $lateView([$marker->withInitialType(null), $marker->withInitialType($others['string']), $marker->withInitialType($others['string'])->getInitialType(), $marker->withInitialType(null)->equals($marker)]);
		$r["$name withSite"] = $lateView([$marker->withSite($lateSiteB, $lateTKey), $marker->withSite($lateSiteB, $lateTKey)->getTemplateName(), $marker->withSite($lateSiteB, $lateTKey)->getInitialType(), $marker->withSite($lateSiteA, $lateTString)->equals($marker)]);
		$r["$name unwrapBare"] = $lateView([\PHPStan\Type\Generic\UnresolvedTemplateArgumentType::unwrapBare($marker), \PHPStan\Type\Generic\UnresolvedTemplateArgumentType::unwrapBare(new \PHPStan\Type\UnionType([$marker, new \PHPStan\Type\NullType()])), \PHPStan\Type\Generic\UnresolvedTemplateArgumentType::unwrapBare(new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), $marker)), \PHPStan\Type\Generic\UnresolvedTemplateArgumentType::unwrapBare(new \PHPStan\Type\Generic\GenericObjectType(\ArrayIterator::class, [new \PHPStan\Type\IntegerType(), $marker])), \PHPStan\Type\Generic\UnresolvedTemplateArgumentType::unwrapBare(new \PHPStan\Type\IterableType(new \PHPStan\Type\IntegerType(), $marker)), \PHPStan\Type\Generic\UnresolvedTemplateArgumentType::unwrapBare($others['int']) === $others['int'], \PHPStan\Type\Generic\UnresolvedTemplateArgumentType::unwrapBare($others['array']) === $others['array']]);
		$r["$name traverse toMarker"] = $attempt(static fn () => $marker->traverse(static fn (\PHPStan\Type\Type $t): \PHPStan\Type\Type => $t instanceof \PHPStan\Type\Generic\TemplateType ? $subjects['unresolvedIntSiteB'] : $t));
		$r["$name traverseSimultaneously toRight"] = $attempt(static fn () => $marker->traverseSimultaneously($others['string'], static fn ($a, $b) => $b));
	}
	$r['marker nested initial type'] = $attempt(static fn () => new \PHPStan\Type\Generic\UnresolvedTemplateArgumentType($lateSiteA, $lateT, $subjects['unresolvedInt']));
	$r['marker equals'] = [$subjects['unresolvedInt']->equals($subjects['unresolvedIntSiteB']), $subjects['unresolvedInt']->equals(new \PHPStan\Type\Generic\UnresolvedTemplateArgumentType($lateSiteA, $lateT, new \PHPStan\Type\StringType())), $subjects['unresolvedInt']->equals(new \PHPStan\Type\Generic\UnresolvedTemplateArgumentType($lateSiteA, $lateTKey, new \PHPStan\Type\IntegerType())), $subjects['unresolvedInt']->equals($subjects['unresolvedNull'])];
	// the shape factory resolving right away, and its private constructor
	$r['shape create resolved'] = $lateView([
		\PHPStan\Type\LateResolvableArrayShapeType::create([[new \PHPStan\Type\Constant\ConstantStringType('a'), new \PHPStan\Type\IntegerType(), false], [null, new \PHPStan\Type\StringType(), true]], null, \PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode::KIND_ARRAY),
		\PHPStan\Type\LateResolvableArrayShapeType::create([[null, new \PHPStan\Type\IntegerType(), false]], [null, new \PHPStan\Type\MixedType()], \PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode::KIND_LIST),
		\PHPStan\Type\LateResolvableArrayShapeType::create([[null, new \PHPStan\Type\IntegerType(), false]], [new \PHPStan\Type\StringType(), new \PHPStan\Type\BooleanType()], \PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode::KIND_NON_EMPTY_ARRAY),
		\PHPStan\Type\LateResolvableArrayShapeType::create([[new \PHPStan\Type\ObjectType(\stdClass::class), new \PHPStan\Type\IntegerType(), false]], null, \PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode::KIND_ARRAY),
		\PHPStan\Type\LateResolvableArrayShapeType::create([[new \PHPStan\Type\ErrorType('bad key'), new \PHPStan\Type\IntegerType(), false]], null, \PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode::KIND_ARRAY),
		\PHPStan\Type\LateResolvableArrayShapeType::create([[$lateTKey, new \PHPStan\Type\IntegerType(), false]], [null, new \PHPStan\Type\StringType()], \PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode::KIND_NON_EMPTY_LIST),
		\PHPStan\Type\LateResolvableArrayShapeType::create([], null, \PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode::KIND_ARRAY),
		\PHPStan\Type\LateResolvableArrayShapeType::create([], [null, new \PHPStan\Type\StringType()], \PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode::KIND_LIST),
	]);
	$r['shape constructor'] = $attempt(static fn () => (new ReflectionClass(\PHPStan\Type\LateResolvableArrayShapeType::class))->getConstructor()->isPrivate());
	$r['shape new'] = $attempt(static fn () => new \PHPStan\Type\LateResolvableArrayShapeType([], null, 'array'));
	$r['shape equals'] = $lateView([
		$subjects['shapeTKeyInt']->equals(\PHPStan\Type\LateResolvableArrayShapeType::create([[$lateTKey, new \PHPStan\Type\IntegerType(), false]], null, \PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode::KIND_ARRAY)),
		$subjects['shapeTKeyInt']->equals(\PHPStan\Type\LateResolvableArrayShapeType::create([[$lateTKey, new \PHPStan\Type\IntegerType(), true]], null, \PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode::KIND_ARRAY)),
		$subjects['shapeTKeyInt']->equals(\PHPStan\Type\LateResolvableArrayShapeType::create([[$lateTKey, new \PHPStan\Type\IntegerType(), false]], null, \PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode::KIND_LIST)),
		$subjects['shapeTKeyInt']->equals(\PHPStan\Type\LateResolvableArrayShapeType::create([[$lateTKey, new \PHPStan\Type\IntegerType(), false]], [null, new \PHPStan\Type\MixedType()], \PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode::KIND_ARRAY)),
		$subjects['shapeTKeyUnsealedTKeyBool']->equals(\PHPStan\Type\LateResolvableArrayShapeType::create([[$lateTKey, new \PHPStan\Type\IntegerType(), false]], [$lateTKey, new \PHPStan\Type\BooleanType()], \PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode::KIND_ARRAY)),
		$subjects['shapeTKeyUnsealedTKeyBool']->equals(\PHPStan\Type\LateResolvableArrayShapeType::create([[$lateTKey, new \PHPStan\Type\IntegerType(), false]], [null, new \PHPStan\Type\BooleanType()], \PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode::KIND_ARRAY)),
		$subjects['shapeTKeyUnsealedTKeyBool']->equals(\PHPStan\Type\LateResolvableArrayShapeType::create([[$lateTKey, new \PHPStan\Type\IntegerType(), false]], [$lateTKey, new \PHPStan\Type\StringType()], \PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode::KIND_ARRAY)),
		$subjects['shapeTKeyInt']->traverseSimultaneously($subjects['shapeTKeyUnsealedTKeyBool'], static fn ($a, $b) => $b),
		$subjects['shapeTKeyUnsealedTKeyBool']->traverseSimultaneously($subjects['shapeTKeyIntOptionalUnsealedMixed'], static fn ($a, $b) => $b) === $subjects['shapeTKeyUnsealedTKeyBool'],
	]);
	// the uninitialized-slot reads the twins' typed properties raise
	foreach (['keyOfArray' => \PHPStan\Type\KeyOfType::class, 'valueOfArray' => \PHPStan\Type\ValueOfType::class, 'offsetShapeA' => \PHPStan\Type\OffsetAccessType::class, 'constantMissing' => \PHPStan\Type\ClassConstantAccessType::class, 'newException' => \PHPStan\Type\NewObjectType::class, 'condIntIsInt' => \PHPStan\Type\ConditionalType::class, 'condParamIsInt' => \PHPStan\Type\ConditionalTypeForParameter::class, 'shapeTKeyInt' => \PHPStan\Type\LateResolvableArrayShapeType::class, 'unresolvedInt' => \PHPStan\Type\Generic\UnresolvedTemplateArgumentType::class] as $name => $class) {
		$uninitialized = (new ReflectionClass($class))->newInstanceWithoutConstructor();
		$r["$name uninitialized describe"] = $attempt(static fn () => $uninitialized->describe(\PHPStan\Type\VerbosityLevel::precise()));
		$r["$name uninitialized isString"] = $attempt(static fn () => $uninitialized->isString());
		$r["$name uninitialized equals"] = $attempt(static fn () => $subjects[$name]->equals($uninitialized));
		$r["$name uninitialized getReferencedClasses"] = $attempt(static fn () => $uninitialized->getReferencedClasses());
	}
	// a repeated constructor call overwrites the slots in place
	$reconstructed = new \PHPStan\Type\KeyOfType($others['array']);
	$reconstructed->__construct($lateShape);
	$r['keyOf reconstruct'] = $lateView([$reconstructed->getType(), $reconstructed->resolve()]);
	// PHP subclasses over the two non-final natives: what the trait's
	// forwards call through $this (resolve(), isResolvable(), getResult())
	$anonymousKeyOf = new class ($lateT) extends \PHPStan\Type\KeyOfType {

		protected function getResult(): \PHPStan\Type\Type
		{
			return new \PHPStan\Type\Constant\ConstantStringType('overridden');
		}

		public function isResolvable(): bool
		{
			return true;
		}

	};
	$r['anonymous keyOf'] = $lateView([$anonymousKeyOf->resolve(), $anonymousKeyOf->isString(), $anonymousKeyOf->getConstantScalarValues(), $anonymousKeyOf->isSuperTypeOf($others['stringA']), $anonymousKeyOf->isSuperTypeOf($others['int']), $anonymousKeyOf->isSubTypeOf($others['string']), $anonymousKeyOf->isAcceptedBy($others['int'], true), $anonymousKeyOf->describe(\PHPStan\Type\VerbosityLevel::precise()), $anonymousKeyOf->equals($subjects['keyOfT']), $subjects['keyOfT']->equals($anonymousKeyOf), $anonymousKeyOf->traverse($identity) === $anonymousKeyOf, get_class($anonymousKeyOf->traverse($toInt)), $anonymousKeyOf->generalize(\PHPStan\Type\GeneralizePrecision::lessSpecific()), $anonymousKeyOf->getType(), $anonymousKeyOf->hasTemplateOrLateResolvableType()]);
	$anonymousNew = new class ($lateTObject) extends \PHPStan\Type\NewObjectType {

		public function resolve(): \PHPStan\Type\Type
		{
			return new \PHPStan\Type\ObjectType(\stdClass::class);
		}

	};
	$r['anonymous new'] = $lateView([$anonymousNew->resolve(), $anonymousNew->isObject(), $anonymousNew->getObjectClassNames(), $anonymousNew->isSuperTypeOf(new \PHPStan\Type\ObjectType(\stdClass::class)), $anonymousNew->isSuperTypeOf($others['exception']), $anonymousNew->accepts($others['exception'], true), $anonymousNew->isSubTypeOf($others['exception']), $anonymousNew->isGreaterThan($others['int'], $latePhpVersion), $anonymousNew->describe(\PHPStan\Type\VerbosityLevel::precise()), $anonymousNew->toPhpDocNode(), $subjects['newTObject']->equals($anonymousNew), $anonymousNew->equals($subjects['newTObject']), $anonymousNew->isResolvable(), $anonymousNew->traverseSimultaneously($subjects['newException'], static fn ($a, $b) => $b)]);
	foreach ($r as $key => $value) {
		$observations["late $key"] = $value;
	}
}


// ---- TemplateArrayType / TemplateBenevolentUnionType / TemplateBooleanType / TemplateConstantArrayType / TemplateConstantIntegerType / TemplateConstantStringType / TemplateFloatType / TemplateGenericObjectType / TemplateIntegerType / TemplateIntersectionType / TemplateIterableType / TemplateMixedType / TemplateNullType / TemplateObjectShapeType / TemplateObjectType / TemplateObjectWithoutClassType / TemplateStrictMixedType / TemplateStringType / TemplateUnionType / TemplateTypeArgumentStrategy / TemplateTypeParameterStrategy / TemplateTypeFactory / TypeProjectionHelper ----
// the template family: every Template*Type over its native parent (built
// through TemplateTypeFactory::create() over the bounds it dispatches on),
// the TemplateTypeTrait bodies (describe, the compound dispatch of
// isSuperTypeOf/isSubTypeOf/accepts/isAcceptedBy, equals, the factory
// rebuilds of subtract/tryRemove/traverse, inferTemplateTypes,
// toClassConstantType), the per-class overrides (withTypes, recreate,
// filterTypes, the mixed pair's isAcceptedBy/toStrictMixedType), the two
// strategies behind accepts(), and a PHP subclass over the non-final
// TemplateObjectWithoutClassType
foreach ([\PHPStan\Type\Generic\TemplateTypeArgumentStrategy::class, \PHPStan\Type\Generic\TemplateTypeParameterStrategy::class] as $strategyClass) {
	$observations["native $strategyClass"] = (new ReflectionMethod($strategyClass, 'accepts'))->isInternal();
}
$observations['native ' . \PHPStan\Type\Generic\TemplateTypeFactory::class] = (new ReflectionMethod(\PHPStan\Type\Generic\TemplateTypeFactory::class, 'create'))->isInternal();
$observations['native ' . \PHPStan\Type\Generic\TypeProjectionHelper::class] = (new ReflectionMethod(\PHPStan\Type\Generic\TypeProjectionHelper::class, 'describe'))->isInternal();
$templatePhpVersion = new \PHPStan\Php\PhpVersion(80400);
$templateScopeF = \PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('tf');
$templateScopeM = \PHPStan\Type\Generic\TemplateTypeScope::createWithMethod('TemplateFamily\\C', 'm');
$templateVariances = [
	'invariant' => \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant(),
	'covariant' => \PHPStan\Type\Generic\TemplateTypeVariance::createCovariant(),
	'contravariant' => \PHPStan\Type\Generic\TemplateTypeVariance::createContravariant(),
	'static' => \PHPStan\Type\Generic\TemplateTypeVariance::createStatic(),
	'bivariant' => \PHPStan\Type\Generic\TemplateTypeVariance::createBivariant(),
];
$templateStrategies = [
	'parameter' => new \PHPStan\Type\Generic\TemplateTypeParameterStrategy(),
	'argument' => new \PHPStan\Type\Generic\TemplateTypeArgumentStrategy(),
];
$templateConstShape = new \PHPStan\Type\Constant\ConstantArrayType([new \PHPStan\Type\Constant\ConstantStringType('a'), new \PHPStan\Type\Constant\ConstantStringType('b')], [new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()], [2], [1], \PHPStan\TrinaryLogic::createNo());
$templateInnerT = \PHPStan\Type\Generic\TemplateTypeFactory::create($templateScopeM, 'U', new \PHPStan\Type\IntegerType(), $templateVariances['invariant']);
$templateBounds = [
	'null' => null,
	'mixed' => new \PHPStan\Type\MixedType(),
	'explicitMixed' => new \PHPStan\Type\MixedType(true),
	'mixedMinusInt' => new \PHPStan\Type\MixedType(false, new \PHPStan\Type\IntegerType()),
	'strictMixed' => new \PHPStan\Type\StrictMixedType(),
	'int' => new \PHPStan\Type\IntegerType(),
	'int1' => new \PHPStan\Type\Constant\ConstantIntegerType(1),
	'range' => \PHPStan\Type\IntegerRangeType::fromInterval(0, 10),
	'float' => new \PHPStan\Type\FloatType(),
	'bool' => new \PHPStan\Type\BooleanType(),
	'true' => new \PHPStan\Type\Constant\ConstantBooleanType(true),
	'string' => new \PHPStan\Type\StringType(),
	'stringA' => new \PHPStan\Type\Constant\ConstantStringType('a'),
	'classString' => new \PHPStan\Type\ClassStringType(),
	'nullType' => new \PHPStan\Type\NullType(),
	'array' => new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()),
	'arrayIntString' => new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()),
	'list' => new \PHPStan\Type\IntersectionType([new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()), new \PHPStan\Type\Accessory\AccessoryArrayListType()]),
	'constShape' => $templateConstShape,
	'emptyArray' => new \PHPStan\Type\Constant\ConstantArrayType([], []),
	'objectShape' => new \PHPStan\Type\ObjectShapeType(['a' => new \PHPStan\Type\IntegerType(), 'b' => new \PHPStan\Type\StringType()], ['b']),
	'object' => new \PHPStan\Type\ObjectType(\stdClass::class),
	'objectException' => new \PHPStan\Type\ObjectType(\Exception::class),
	'objectFinal' => new \PHPStan\Type\ObjectType(\PHPStan\TrinaryLogic::class),
	'objectUnknown' => new \PHPStan\Type\ObjectType('TemplateFamily\\DoesNotExist'),
	'objectMinusStd' => new \PHPStan\Type\ObjectType(\Throwable::class, new \PHPStan\Type\ObjectType(\Error::class)),
	'enumCase' => new \PHPStan\Type\Enum\EnumCaseObjectType('Random\\IntervalBoundary', 'ClosedOpen'),
	'generic' => new \PHPStan\Type\Generic\GenericObjectType(\ArrayObject::class, [new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]),
	'genericVariances' => new \PHPStan\Type\Generic\GenericObjectType(\Traversable::class, [new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()], null, null, [\PHPStan\Type\Generic\TemplateTypeVariance::createCovariant(), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()]),
	'objectWithoutClass' => new \PHPStan\Type\ObjectWithoutClassType(),
	'objectWithoutClassMinusStd' => new \PHPStan\Type\ObjectWithoutClassType(new \PHPStan\Type\ObjectType(\stdClass::class)),
	'union' => new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]),
	'nullableString' => new \PHPStan\Type\UnionType([new \PHPStan\Type\StringType(), new \PHPStan\Type\NullType()]),
	'benevolent' => new \PHPStan\Type\BenevolentUnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]),
	'intersection' => new \PHPStan\Type\IntersectionType([new \PHPStan\Type\ObjectType(\Countable::class), new \PHPStan\Type\ObjectType(\Traversable::class)]),
	'iterable' => new \PHPStan\Type\IterableType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()),
	'iterableIntString' => new \PHPStan\Type\IterableType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()),
	'callable' => new \PHPStan\Type\CallableType(),
	'never' => new \PHPStan\Type\NeverType(),
	'templateU' => $templateInnerT,
	'keyOf' => new \PHPStan\Type\KeyOfType($templateConstShape),
];
$templateDefaults = [
	'none' => null,
	'int' => new \PHPStan\Type\IntegerType(),
	'stringD' => new \PHPStan\Type\Constant\ConstantStringType('d'),
];
$templateOthers = static fn (): array => [
	'int' => new \PHPStan\Type\IntegerType(),
	'int1' => new \PHPStan\Type\Constant\ConstantIntegerType(1),
	'float' => new \PHPStan\Type\FloatType(),
	'bool' => new \PHPStan\Type\BooleanType(),
	'string' => new \PHPStan\Type\StringType(),
	'stringA' => new \PHPStan\Type\Constant\ConstantStringType('a'),
	'null' => new \PHPStan\Type\NullType(),
	'mixed' => new \PHPStan\Type\MixedType(),
	'strictMixed' => new \PHPStan\Type\StrictMixedType(),
	'never' => new \PHPStan\Type\NeverType(),
	'array' => new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()),
	'arrayIntString' => new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()),
	'constShape' => new \PHPStan\Type\Constant\ConstantArrayType([new \PHPStan\Type\Constant\ConstantStringType('a')], [new \PHPStan\Type\Constant\ConstantIntegerType(1)]),
	'object' => new \PHPStan\Type\ObjectType(\stdClass::class),
	'exception' => new \PHPStan\Type\ObjectType(\Exception::class),
	'generic' => new \PHPStan\Type\Generic\GenericObjectType(\ArrayObject::class, [new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]),
	'objectWithoutClass' => new \PHPStan\Type\ObjectWithoutClassType(),
	'union' => new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]),
	'unionObjects' => new \PHPStan\Type\UnionType([new \PHPStan\Type\ObjectType(\stdClass::class), new \PHPStan\Type\ObjectType(\Exception::class)]),
	'benevolent' => new \PHPStan\Type\BenevolentUnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]),
	'intersection' => new \PHPStan\Type\IntersectionType([new \PHPStan\Type\ObjectType(\Countable::class), new \PHPStan\Type\ObjectType(\Traversable::class)]),
	'nonEmptyString' => new \PHPStan\Type\IntersectionType([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryNonEmptyStringType()]),
	'iterable' => new \PHPStan\Type\IterableType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()),
	'callable' => new \PHPStan\Type\CallableType(),
	'templateTInt' => \PHPStan\Type\Generic\TemplateTypeFactory::create(\PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('tf'), 'T', new \PHPStan\Type\IntegerType(), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
	'templateTNull' => \PHPStan\Type\Generic\TemplateTypeFactory::create(\PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('tf'), 'T', null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
	'templateTOther' => \PHPStan\Type\Generic\TemplateTypeFactory::create(\PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('other'), 'T', null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
	'templateVString' => \PHPStan\Type\Generic\TemplateTypeFactory::create(\PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('tf'), 'V', new \PHPStan\Type\StringType(), \PHPStan\Type\Generic\TemplateTypeVariance::createCovariant(), new \PHPStan\Type\Generic\TemplateTypeArgumentStrategy()),
	'templateUnion' => \PHPStan\Type\Generic\TemplateTypeFactory::create(\PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('tf'), 'T', new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
	'templateObject' => \PHPStan\Type\Generic\TemplateTypeFactory::create(\PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('tf'), 'T', new \PHPStan\Type\ObjectWithoutClassType(), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
];
// a PHP subclass over the non-final TemplateObjectWithoutClassType: the
// trait's $this-calls (getName() in the factory rebuilds, isArgument() in
// describe()) go through it
if (!class_exists('TemplateFamilySubclass', false)) {
	eval('class TemplateFamilySubclass extends \PHPStan\Type\Generic\TemplateObjectWithoutClassType { public function getName(): string { return "S" . parent::getName(); } public function isArgument(): bool { return !parent::isArgument(); } }');
}
$templateMapView = static fn (\PHPStan\Type\Generic\TemplateTypeMap $map) => array_map($view, $map->getTypes());
{
	$r = [];
	$others = $templateOthers();
	$subjects = [];
	foreach ($templateBounds as $boundName => $bound) {
		foreach (['parameter', 'argument'] as $strategyName) {
			foreach (['none', 'int'] as $defaultName) {
				$subjects["$boundName $strategyName $defaultName"] = \PHPStan\Type\Generic\TemplateTypeFactory::create($templateScopeF, 'T', $bound, $strategyName === 'parameter' ? $templateVariances['invariant'] : $templateVariances['covariant'], $templateStrategies[$strategyName], $templateDefaults[$defaultName]);
			}
		}
	}
	$subjects['int contravariant stringD'] = \PHPStan\Type\Generic\TemplateTypeFactory::create($templateScopeF, 'T', new \PHPStan\Type\IntegerType(), $templateVariances['contravariant'], null, $templateDefaults['stringD']);
	$subjects['mixed static none'] = \PHPStan\Type\Generic\TemplateTypeFactory::create($templateScopeF, 'T', null, $templateVariances['static']);
	$subjects['object bivariant none'] = \PHPStan\Type\Generic\TemplateTypeFactory::create($templateScopeF, 'T', new \PHPStan\Type\ObjectType(\stdClass::class), $templateVariances['bivariant']);
	$subjects['int method scope'] = \PHPStan\Type\Generic\TemplateTypeFactory::create($templateScopeM, 'T', new \PHPStan\Type\IntegerType(), $templateVariances['invariant']);
	$subjects['nested template default'] = \PHPStan\Type\Generic\TemplateTypeFactory::create($templateScopeF, 'T', new \PHPStan\Type\StringType(), $templateVariances['invariant'], null, $templateInnerT);
	$subjects['subclass'] = new \TemplateFamilySubclass($templateScopeF, $templateStrategies['parameter'], $templateVariances['invariant'], 'T', new \PHPStan\Type\ObjectWithoutClassType(), null);
	$subjects['subclass argument'] = new \TemplateFamilySubclass($templateScopeF, $templateStrategies['argument'], $templateVariances['covariant'], 'T', new \PHPStan\Type\ObjectWithoutClassType(new \PHPStan\Type\ObjectType(\stdClass::class)), new \PHPStan\Type\ObjectType(\Exception::class));
	$templateMapper = static fn (\PHPStan\Type\Type $t): \PHPStan\Type\Type => $t instanceof \PHPStan\Type\IntegerType ? new \PHPStan\Type\StringType() : $t;
	$templateIdentity = static fn (\PHPStan\Type\Type $t): \PHPStan\Type\Type => $t;
	$templateTraverseCounter = static function (\PHPStan\Type\Type $t, callable $traverse): \PHPStan\Type\Type {
		return $traverse($t);
	};
	foreach ($subjects as $name => $subject) {
		$reflection = new ReflectionClass($subject);
		$r["$name class"] = [get_class($subject), $reflection->isFinal(), $reflection->getParentClass() === false ? null : $reflection->getParentClass()->getName(), $reflection->getConstructor()?->getNumberOfParameters()];
		$r["$name instanceof"] = [$subject instanceof \PHPStan\Type\Type, $subject instanceof \PHPStan\Type\Generic\TemplateType, $subject instanceof \PHPStan\Type\CompoundType, $subject instanceof \PHPStan\Type\SubtractableType, $subject instanceof \PHPStan\Type\MixedType, $subject instanceof \PHPStan\Type\UnionType, $subject instanceof \PHPStan\Type\ArrayType, $subject instanceof \PHPStan\Type\ObjectType];
		foreach (['typeOnly' => \PHPStan\Type\VerbosityLevel::typeOnly(), 'value' => \PHPStan\Type\VerbosityLevel::value(), 'precise' => \PHPStan\Type\VerbosityLevel::precise(), 'cache' => \PHPStan\Type\VerbosityLevel::cache()] as $levelName => $level) {
			$r["$name describe $levelName"] = $subject->describe($level);
		}
		$r["$name getName"] = $subject->getName();
		$r["$name getScope"] = [$subject->getScope()->describe(), $subject->getScope()->equals($templateScopeF)];
		$r["$name getBound"] = $view($subject->getBound());
		$r["$name getDefault"] = $view($subject->getDefault());
		$r["$name getVariance"] = $subject->getVariance()->describe();
		$r["$name getStrategy"] = [get_class($subject->getStrategy()), $subject->getStrategy()->isArgument()];
		$r["$name isArgument"] = $subject->isArgument();
		$argument = $subject->toArgument();
		$r["$name toArgument"] = [$view($argument), $argument->isArgument(), get_class($argument->getStrategy()), $view($argument->getDefault()), $argument->getScope()->describe()];
		$r["$name getSubtractedType"] = $view($subject->getSubtractedType());
		$r["$name getTypeWithoutSubtractedType"] = [$view($subject->getTypeWithoutSubtractedType()), $subject->getTypeWithoutSubtractedType() === $subject];
		$r["$name changeSubtractedType int"] = [$view($subject->changeSubtractedType($others['int'])), $subject->changeSubtractedType($others['int']) === $subject];
		$r["$name changeSubtractedType null"] = $view($subject->changeSubtractedType(null));
		$r["$name toArrayKey"] = $subject->toArrayKey() === $subject;
		$r["$name toCoercedArgumentType"] = [$subject->toCoercedArgumentType(true) === $subject, $subject->toCoercedArgumentType(false) === $subject];
		$r["$name toClassConstantType"] = $view($subject->toClassConstantType($stringReflectionProvider));
		$r["$name toPhpDocNode"] = $view($subject->toPhpDocNode());
		$r["$name hasTemplateOrLateResolvableType"] = $subject->hasTemplateOrLateResolvableType();
		foreach ($templateVariances as $varianceName => $variance) {
			$r["$name getReferencedTemplateTypes $varianceName"] = array_map(static fn (\PHPStan\Type\Generic\TemplateTypeReference $ref): array => [$view($ref->getType()), $ref->getType() === $subject, $ref->getPositionVariance()->describe()], $subject->getReferencedTemplateTypes($variance));
		}
		$r["$name traverse identity"] = $subject->traverse($templateIdentity) === $subject;
		$r["$name traverse mapped"] = $view($subject->traverse($templateMapper));
		$r["$name traverse map"] = $view(\PHPStan\Type\TypeTraverser::map($subject, $templateTraverseCounter));
		$r["$name traverseSimultaneously self"] = $subject->traverseSimultaneously($subject, static fn (\PHPStan\Type\Type $a, \PHPStan\Type\Type $b): \PHPStan\Type\Type => $a) === $subject;
		$r["$name traverseSimultaneously other template"] = $view($subject->traverseSimultaneously($others['templateVString'], static fn (\PHPStan\Type\Type $a, \PHPStan\Type\Type $b): \PHPStan\Type\Type => \PHPStan\Type\TypeCombinator::union($a, $b)));
		$r["$name traverseSimultaneously non-template"] = $subject->traverseSimultaneously($others['int'], static fn (\PHPStan\Type\Type $a, \PHPStan\Type\Type $b): \PHPStan\Type\Type => $b) === $subject;
		$r["$name generalize"] = [$view($subject->generalize(\PHPStan\Type\GeneralizePrecision::lessSpecific())), $view($subject->generalize(\PHPStan\Type\GeneralizePrecision::moreSpecific()))];
		$r["$name comparisons"] = [$view($subject->isGreaterThan($others['null'], $templatePhpVersion)), $view($subject->isGreaterThan($others['int'], $templatePhpVersion)), $view($subject->isGreaterThanOrEqual($others['null'], $templatePhpVersion)), $view($subject->isGreaterThanOrEqual($others['int'], $templatePhpVersion)), $view($subject->isSmallerThan($others['int'], $templatePhpVersion)), $view($subject->isSmallerThanOrEqual($others['int'], $templatePhpVersion)), $view($subject->looseCompare($others['int'], $templatePhpVersion))];
		$r["$name predicates"] = [$view($subject->isNull()), $view($subject->isObject()), $view($subject->isArray()), $view($subject->isString()), $view($subject->isInteger()), $view($subject->isIterable()), $view($subject->isCallable()), $view($subject->isScalar()), $view($subject->isConstantValue()), $view($subject->isSuperTypeOf($subject)), $view($subject->getObjectClassNames())];
		$r["$name conversions"] = [$view($subject->toBoolean()), $view($subject->toNumber()), $view($subject->toString()), $view($subject->toInteger()), $view($subject->toFloat()), $view($subject->toArray()), $view($subject->getIterableKeyType()), $view($subject->getIterableValueType()), $view($subject->getClassStringType())];
		$r["$name array ops"] = [$view($subject->getKeysArray()), $view($subject->getValuesArray()), $view($subject->popArray()), $view($subject->flipArray()), $view($subject->setOffsetValueType(new \PHPStan\Type\Constant\ConstantStringType('b'), new \PHPStan\Type\IntegerType())), $view($subject->setOffsetValueType(null, new \PHPStan\Type\FloatType())), $view($subject->unsetOffset(new \PHPStan\Type\Constant\ConstantStringType('a'))), $view($subject->getOffsetValueType(new \PHPStan\Type\Constant\ConstantStringType('a')))];
		if ($subject instanceof \PHPStan\Type\Generic\GenericObjectType) {
			$r["$name changeVariances"] = $view($subject->changeVariances([\PHPStan\Type\Generic\TemplateTypeVariance::createCovariant(), \PHPStan\Type\Generic\TemplateTypeVariance::createCovariant()]));
			$r["$name generic"] = [$view($subject->getTypes()), array_map(static fn (\PHPStan\Type\Generic\TemplateTypeVariance $v): string => $v->describe(), $subject->getVariances())];
		}
		if ($subject instanceof \PHPStan\Type\UnionType) {
			$r["$name filterTypes ints"] = $view($subject->filterTypes(static fn (\PHPStan\Type\Type $t): bool => $t->isInteger()->yes()));
			$r["$name filterTypes all"] = [$view($subject->filterTypes(static fn (\PHPStan\Type\Type $t): bool => true)), $subject->filterTypes(static fn (\PHPStan\Type\Type $t): bool => true) === $subject];
			$r["$name filterTypes none"] = $view($subject->filterTypes(static fn (\PHPStan\Type\Type $t): bool => false));
			$r["$name getTypes"] = $view($subject->getTypes());
		}
		if ($subject instanceof \PHPStan\Type\Generic\TemplateBenevolentUnionType) {
			$r["$name withTypes"] = $view($subject->withTypes([new \PHPStan\Type\FloatType(), new \PHPStan\Type\NullType()]));
		}
		if ($subject instanceof \PHPStan\Type\Generic\TemplateMixedType) {
			$r["$name toStrictMixedType"] = [$view($subject->toStrictMixedType()), $view($subject->toStrictMixedType()->getDefault()), $subject->toStrictMixedType()->isArgument()];
		}
		if ($subject instanceof \PHPStan\Type\Generic\TemplateMixedType || $subject instanceof \PHPStan\Type\Generic\TemplateStrictMixedType) {
			foreach (['mixed', 'explicitMixed', 'mixedMinusInt'] as $mixedName) {
				$r["$name isSuperTypeOfMixed $mixedName"] = $view($subject->isSuperTypeOfMixed($templateBounds[$mixedName]));
			}
			$r["$name isSuperTypeOfMixed templateMixed"] = $view($subject->isSuperTypeOfMixed($subjects['null parameter none']));
		}
		foreach ($others as $otherName => $other) {
			$r["$name isSuperTypeOf $otherName"] = $view($subject->isSuperTypeOf($other));
			$r["$name isSubTypeOf $otherName"] = $view($subject->isSubTypeOf($other));
			$r["$name accepts $otherName"] = [$view($subject->accepts($other, true)), $view($subject->accepts($other, false))];
			$r["$name isAcceptedBy $otherName"] = [$view($subject->isAcceptedBy($other, true)), $view($subject->isAcceptedBy($other, false))];
			$r["$name equals $otherName"] = [$subject->equals($other), $other->equals($subject)];
			$r["$otherName isSuperTypeOf $name"] = $view($other->isSuperTypeOf($subject));
			$r["$otherName accepts $name"] = [$view($other->accepts($subject, true)), $view($other->accepts($subject, false))];
			$r["$name tryRemove $otherName"] = $view($subject->tryRemove($other));
			$r["$name subtract $otherName"] = $view($subject->subtract($other));
			$r["$name inferTemplateTypes $otherName"] = $templateMapView($subject->inferTemplateTypes($other));
			try {
				$r["$name isValidVariance $otherName"] = [$view($subject->isValidVariance($other, $others['int'])), $view($subject->isValidVariance($others['int'], $other, true))];
			} catch (\PHPStan\ShouldNotHappenException $e) {
				$r["$name isValidVariance $otherName"] = get_class($e);
			}
			$r["$name union $otherName"] = $view(\PHPStan\Type\TypeCombinator::union($subject, $other));
			$r["$name intersect $otherName"] = $view(\PHPStan\Type\TypeCombinator::intersect($subject, $other));
			$r["$name remove $otherName"] = $view(\PHPStan\Type\TypeCombinator::remove($subject, $other));
		}
		foreach (['null parameter none', 'int parameter none', 'int argument int', 'union parameter none', 'subclass'] as $peerName) {
			$peer = $subjects[$peerName];
			$r["$name peer equals $peerName"] = [$subject->equals($peer), $subject->equals(\PHPStan\Type\Generic\TemplateTypeFactory::create($templateScopeF, 'T', $subject->getBound(), $subject->getVariance(), $subject->getStrategy(), $subject->getDefault())), $subject->equals(\PHPStan\Type\Generic\TemplateTypeFactory::create($templateScopeM, 'T', $subject->getBound(), $subject->getVariance()))];
			$r["$name peer isSuperTypeOf $peerName"] = [$view($subject->isSuperTypeOf($peer)), $view($subject->isSubTypeOf($peer)), $view($subject->accepts($peer, true)), $view($subject->isAcceptedBy($peer, true))];
			$r["$name peer inferTemplateTypes $peerName"] = $templateMapView($subject->inferTemplateTypes($peer));
			$r["$name peer traverseSimultaneously $peerName"] = $view($subject->traverseSimultaneously($peer, static fn (\PHPStan\Type\Type $a, \PHPStan\Type\Type $b): \PHPStan\Type\Type => \PHPStan\Type\TypeCombinator::union($a, $b)));
		}
	}

	// the strategies on their own: every strategy over every (left, right) pair
	foreach ($templateStrategies as $strategyName => $strategy) {
		$r["strategy $strategyName isArgument"] = $strategy->isArgument();
		foreach (['null parameter none', 'int parameter none', 'union argument none', 'object bivariant none', 'subclass'] as $leftName) {
			foreach ($others as $otherName => $other) {
				$r["strategy $strategyName accepts $leftName $otherName"] = [$view($strategy->accepts($subjects[$leftName], $other, true)), $view($strategy->accepts($subjects[$leftName], $other, false))];
			}
		}
	}

	// TemplateTypeFactory::fromTemplateTag() and TypeProjectionHelper::describe()
	foreach ($templateBounds as $boundName => $bound) {
		$tag = new \PHPStan\PhpDoc\Tag\TemplateTag('F', $bound ?? new \PHPStan\Type\MixedType(), $boundName === 'int' ? new \PHPStan\Type\StringType() : null, $templateVariances['covariant']);
		$fromTag = \PHPStan\Type\Generic\TemplateTypeFactory::fromTemplateTag($templateScopeF, $tag);
		$r["fromTemplateTag $boundName"] = [$view($fromTag), $fromTag->getName(), $fromTag->isArgument(), get_class($fromTag->getStrategy()), $fromTag->getVariance()->describe(), $view($fromTag->getDefault())];
		foreach ($templateVariances as $varianceName => $variance) {
			$r["projection $boundName $varianceName"] = \PHPStan\Type\Generic\TypeProjectionHelper::describe($bound ?? new \PHPStan\Type\MixedType(true), $variance, \PHPStan\Type\VerbosityLevel::precise());
		}
		$r["projection $boundName null"] = \PHPStan\Type\Generic\TypeProjectionHelper::describe($bound ?? new \PHPStan\Type\MixedType(true), null, \PHPStan\Type\VerbosityLevel::typeOnly());
	}
	try {
		\PHPStan\Type\Generic\TemplateTypeFactory::create($templateScopeF, 'T', new \stdClass(), $templateVariances['invariant']);
		$r['factory bad bound'] = 'no throw';
	} catch (\TypeError $e) {
		$r['factory bad bound'] = preg_replace('~ given.*~', ' given', $e->getMessage());
	}

	// the constructor's checks: a bound of the wrong class, a re-run
	// constructor, an object built without one
	foreach (['array' => [\PHPStan\Type\Generic\TemplateArrayType::class, new \PHPStan\Type\IntegerType()], 'union' => [\PHPStan\Type\Generic\TemplateUnionType::class, new \PHPStan\Type\IntegerType()], 'mixed' => [\PHPStan\Type\Generic\TemplateMixedType::class, new \PHPStan\Type\StrictMixedType()]] as $badName => [$badClass, $badBound]) {
		try {
			new $badClass($templateScopeF, $templateStrategies['parameter'], $templateVariances['invariant'], 'T', $badBound, null);
			$r["bad bound $badName"] = 'no throw';
		} catch (\TypeError $e) {
			$r["bad bound $badName"] = preg_replace('~^(\w+)\\\\?(.*)~', '$1', get_class($e)) . ': ' . preg_replace('~ given.*~', ' given', $e->getMessage());
		}
	}
	$reconstructed = $subjects['int parameter none'];
	$reconstructed->__construct($templateScopeM, $templateStrategies['argument'], $templateVariances['covariant'], 'R', new \PHPStan\Type\Constant\ConstantIntegerType(5), new \PHPStan\Type\StringType());
	$r['reconstruct'] = [$view($reconstructed), $reconstructed->getName(), $reconstructed->isArgument(), $view($reconstructed->getDefault()), $reconstructed->getScope()->describe()];
	try {
		(new ReflectionClass(\PHPStan\Type\Generic\TemplateIntegerType::class))->newInstanceWithoutConstructor()->getName();
		$r['uninitialized'] = 'no throw';
	} catch (\Error $e) {
		$r['uninitialized'] = [get_class($e), $e->getMessage()];
	}


	// ---- TemplateKeyOfType ----
	// the template over the late-resolvable KeyOfType: built through the
	// factory over key-of bounds (a constant shape, a plain array, a list,
	// the empty shape, mixed, a template, a template array, a nested
	// key-of), through the factory's TemplateType arm (a key-of template
	// as the bound) and through its own constructor; the class's getResult()
	// (the bound's result re-templated through the factory) is what the
	// inherited resolve() reaches — memoized, and behind every forwarding
	// method of LateResolvableTypeTrait the TemplateTypeTrait does not
	// override
	$keyOfBounds = [
		'constShape' => new \PHPStan\Type\KeyOfType($templateConstShape),
		'arrayIntString' => new \PHPStan\Type\KeyOfType($templateBounds['arrayIntString']),
		'list' => new \PHPStan\Type\KeyOfType($templateBounds['list']),
		'emptyArray' => new \PHPStan\Type\KeyOfType($templateBounds['emptyArray']),
		'mixed' => new \PHPStan\Type\KeyOfType($templateBounds['mixed']),
		'templateU' => new \PHPStan\Type\KeyOfType($templateInnerT),
		'templateArray' => new \PHPStan\Type\KeyOfType($subjects['arrayIntString parameter none']),
		'keyOfKeyOf' => new \PHPStan\Type\KeyOfType(new \PHPStan\Type\KeyOfType($templateConstShape)),
	];
	$keyOfSubjects = [];
	foreach ($keyOfBounds as $boundName => $bound) {
		foreach (['parameter', 'argument'] as $strategyName) {
			foreach (['none', 'stringD'] as $defaultName) {
				$keyOfSubjects["$boundName $strategyName $defaultName"] = \PHPStan\Type\Generic\TemplateTypeFactory::create($templateScopeF, 'K', $bound, $strategyName === 'parameter' ? $templateVariances['invariant'] : $templateVariances['covariant'], $templateStrategies[$strategyName], $templateDefaults[$defaultName]);
			}
		}
	}
	$keyOfSubjects['nested'] = \PHPStan\Type\Generic\TemplateTypeFactory::create($templateScopeM, 'N', $keyOfSubjects['constShape parameter none'], $templateVariances['contravariant']);
	$keyOfSubjects['nested unresolvable'] = \PHPStan\Type\Generic\TemplateTypeFactory::create($templateScopeM, 'N', $keyOfSubjects['templateU argument stringD'], $templateVariances['bivariant'], $templateStrategies['argument'], $templateDefaults['int']);
	$keyOfSubjects['direct'] = new \PHPStan\Type\Generic\TemplateKeyOfType($templateScopeF, $templateStrategies['parameter'], $templateVariances['static'], 'D', $keyOfBounds['constShape'], $templateDefaults['int']);
	$keyOfPeers = ['constShape parameter none', 'constShape argument stringD', 'templateU parameter none', 'nested', 'direct'];
	$keyOfRights = $others + ['keyOfConstShape' => $keyOfBounds['constShape'], 'keyOfArray' => $keyOfBounds['arrayIntString'], 'keyOfTemplateU' => $keyOfBounds['templateU'], 'constShapeKeys' => new \PHPStan\Type\UnionType([new \PHPStan\Type\Constant\ConstantStringType('a'), new \PHPStan\Type\Constant\ConstantStringType('b')]), 'stringB' => new \PHPStan\Type\Constant\ConstantStringType('b'), 'templateKeyOfOther' => \PHPStan\Type\Generic\TemplateTypeFactory::create(\PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('other'), 'K', $keyOfBounds['constShape'], $templateVariances['invariant'])];
	$keyOfMap = new \PHPStan\Type\Generic\TemplateTypeMap(['K' => new \PHPStan\Type\Constant\ConstantStringType('a'), 'N' => new \PHPStan\Type\IntegerType(), 'U' => $templateBounds['list']]);
	foreach ($keyOfSubjects as $name => $subject) {
		$reflection = new ReflectionClass($subject);
		$r["keyOf $name class"] = [get_class($subject), $reflection->isFinal(), $reflection->getParentClass() === false ? null : $reflection->getParentClass()->getName(), $reflection->getConstructor()?->getNumberOfParameters()];
		$r["keyOf $name instanceof"] = [$subject instanceof \PHPStan\Type\Type, $subject instanceof \PHPStan\Type\Generic\TemplateType, $subject instanceof \PHPStan\Type\KeyOfType, $subject instanceof \PHPStan\Type\CompoundType, $subject instanceof \PHPStan\Type\LateResolvableType, $subject instanceof \PHPStan\Type\SubtractableType];
		foreach (['typeOnly' => \PHPStan\Type\VerbosityLevel::typeOnly(), 'value' => \PHPStan\Type\VerbosityLevel::value(), 'precise' => \PHPStan\Type\VerbosityLevel::precise(), 'cache' => \PHPStan\Type\VerbosityLevel::cache()] as $levelName => $level) {
			$r["keyOf $name describe $levelName"] = $subject->describe($level);
		}
		$r["keyOf $name getName"] = $subject->getName();
		$r["keyOf $name getScope"] = [$subject->getScope()->describe(), $subject->getScope()->equals($templateScopeF)];
		$r["keyOf $name getBound"] = [$view($subject->getBound()), isset($keyOfBounds[explode(' ', $name)[0]]) && $subject->getBound() === $keyOfBounds[explode(' ', $name)[0]]];
		$r["keyOf $name getType"] = [$view($subject->getType()), $subject->getType() === $subject->getBound()->getType()];
		$r["keyOf $name getDefault"] = $view($subject->getDefault());
		$r["keyOf $name getVariance"] = $subject->getVariance()->describe();
		$r["keyOf $name getStrategy"] = [get_class($subject->getStrategy()), $subject->getStrategy()->isArgument()];
		$r["keyOf $name isArgument"] = $subject->isArgument();
		$r["keyOf $name isResolvable"] = $subject->isResolvable();
		$resolved = $subject->resolve();
		$r["keyOf $name resolve"] = [$view($resolved), $resolved === $subject->resolve(), $resolved === $subject, $resolved instanceof \PHPStan\Type\Generic\TemplateType ? [$resolved->getName(), $resolved->getScope()->describe(), $resolved->getVariance()->describe(), get_class($resolved->getStrategy()), $view($resolved->getDefault()), $view($resolved->getBound())] : null];
		$r["keyOf $name resolve vs bound"] = [$view($subject->getBound()->resolve()), $resolved->equals($subject->getBound()->resolve())];
		$argument = $subject->toArgument();
		$r["keyOf $name toArgument"] = [$view($argument), $argument->isArgument(), get_class($argument->getStrategy()), $view($argument->getDefault()), $argument->getScope()->describe(), $view($argument->resolve())];
		$r["keyOf $name getSubtractedType"] = $view($subject->getSubtractedType());
		$r["keyOf $name getTypeWithoutSubtractedType"] = [$view($subject->getTypeWithoutSubtractedType()), $subject->getTypeWithoutSubtractedType() === $subject];
		$r["keyOf $name changeSubtractedType"] = [$view($subject->changeSubtractedType($others['int'])), $subject->changeSubtractedType($others['int']) === $subject, $view($subject->changeSubtractedType(null))];
		$r["keyOf $name toArrayKey"] = $subject->toArrayKey() === $subject;
		$r["keyOf $name toCoercedArgumentType"] = [$subject->toCoercedArgumentType(true) === $subject, $subject->toCoercedArgumentType(false) === $subject];
		$r["keyOf $name toClassConstantType"] = $view($subject->toClassConstantType($stringReflectionProvider));
		$r["keyOf $name toPhpDocNode"] = $view($subject->toPhpDocNode());
		$r["keyOf $name hasTemplateOrLateResolvableType"] = $subject->hasTemplateOrLateResolvableType();
		$r["keyOf $name getReferencedClasses"] = $subject->getReferencedClasses();
		foreach ($templateVariances as $varianceName => $variance) {
			$r["keyOf $name getReferencedTemplateTypes $varianceName"] = array_map(static fn (\PHPStan\Type\Generic\TemplateTypeReference $ref): array => [$view($ref->getType()), $ref->getType() === $subject, $ref->getPositionVariance()->describe()], $subject->getReferencedTemplateTypes($variance));
		}
		$r["keyOf $name traverse identity"] = $subject->traverse($templateIdentity) === $subject;
		$r["keyOf $name traverse mapped"] = $view($subject->traverse($templateMapper));
		$r["keyOf $name traverse map"] = $view(\PHPStan\Type\TypeTraverser::map($subject, $templateTraverseCounter));
		$r["keyOf $name traverseSimultaneously self"] = $subject->traverseSimultaneously($subject, static fn (\PHPStan\Type\Type $a, \PHPStan\Type\Type $b): \PHPStan\Type\Type => $a) === $subject;
		$r["keyOf $name traverseSimultaneously other template"] = $view($subject->traverseSimultaneously($others['templateVString'], static fn (\PHPStan\Type\Type $a, \PHPStan\Type\Type $b): \PHPStan\Type\Type => \PHPStan\Type\TypeCombinator::union($a, $b)));
		$r["keyOf $name traverseSimultaneously key-of"] = $view($subject->traverseSimultaneously($keyOfBounds['arrayIntString'], static fn (\PHPStan\Type\Type $a, \PHPStan\Type\Type $b): \PHPStan\Type\Type => \PHPStan\Type\TypeCombinator::union($a, $b)));
		$r["keyOf $name traverseSimultaneously non-template"] = $subject->traverseSimultaneously($others['int'], static fn (\PHPStan\Type\Type $a, \PHPStan\Type\Type $b): \PHPStan\Type\Type => $b) === $subject;
		$r["keyOf $name generalize"] = [$view($subject->generalize(\PHPStan\Type\GeneralizePrecision::lessSpecific())), $view($subject->generalize(\PHPStan\Type\GeneralizePrecision::moreSpecific()))];
		$r["keyOf $name comparisons"] = [$view($subject->isGreaterThan($others['null'], $templatePhpVersion)), $view($subject->isGreaterThan($others['int'], $templatePhpVersion)), $view($subject->isGreaterThanOrEqual($others['null'], $templatePhpVersion)), $view($subject->isSmallerThan($others['int'], $templatePhpVersion)), $view($subject->isSmallerThanOrEqual($others['string'], $templatePhpVersion)), $view($subject->getSmallerType($templatePhpVersion)), $view($subject->getGreaterOrEqualType($templatePhpVersion)), $view($subject->looseCompare($others['int'], $templatePhpVersion))];
		$r["keyOf $name predicates"] = [$view($subject->isNull()), $view($subject->isObject()), $view($subject->isArray()), $view($subject->isString()), $view($subject->isNonEmptyString()), $view($subject->isLiteralString()), $view($subject->isInteger()), $view($subject->isIterable()), $view($subject->isCallable()), $view($subject->isScalar()), $view($subject->isConstantValue()), $view($subject->isConstantScalarValue()), $view($subject->getConstantScalarTypes()), $view($subject->getConstantScalarValues()), $view($subject->getFiniteTypes()), $view($subject->isOffsetAccessible()), $view($subject->isOffsetAccessLegal())];
		$r["keyOf $name conversions"] = [$view($subject->toBoolean()), $view($subject->toNumber()), $view($subject->toString()), $view($subject->toInteger()), $view($subject->toFloat()), $view($subject->toArray()), $view($subject->getIterableKeyType()), $view($subject->getIterableValueType()), $view($subject->getClassStringType()), $view($subject->toAbsoluteNumber()), $view($subject->exponentiate($others['int']))];
		$r["keyOf $name array ops"] = [$view($subject->getKeysArray()), $view($subject->getValuesArray()), $view($subject->getArraySize()), $view($subject->setOffsetValueType(new \PHPStan\Type\Constant\ConstantStringType('b'), new \PHPStan\Type\IntegerType())), $view($subject->getOffsetValueType($others['int'])), $view($subject->hasOffsetValueType($others['int']))];
		foreach ($keyOfRights as $otherName => $other) {
			$r["keyOf $name isSuperTypeOf $otherName"] = $view($subject->isSuperTypeOf($other));
			$r["keyOf $name isSubTypeOf $otherName"] = $view($subject->isSubTypeOf($other));
			$r["keyOf $name accepts $otherName"] = [$view($subject->accepts($other, true)), $view($subject->accepts($other, false))];
			$r["keyOf $name isAcceptedBy $otherName"] = [$view($subject->isAcceptedBy($other, true)), $view($subject->isAcceptedBy($other, false))];
			$r["keyOf $name equals $otherName"] = [$subject->equals($other), $other->equals($subject)];
			$r["keyOf $otherName isSuperTypeOf $name"] = $view($other->isSuperTypeOf($subject));
			$r["keyOf $otherName accepts $name"] = [$view($other->accepts($subject, true)), $view($other->accepts($subject, false))];
			$r["keyOf $name tryRemove $otherName"] = $view($subject->tryRemove($other));
			$r["keyOf $name subtract $otherName"] = $view($subject->subtract($other));
			$r["keyOf $name inferTemplateTypes $otherName"] = $templateMapView($subject->inferTemplateTypes($other));
			$r["keyOf $name union $otherName"] = $view(\PHPStan\Type\TypeCombinator::union($subject, $other));
			$r["keyOf $name intersect $otherName"] = $view(\PHPStan\Type\TypeCombinator::intersect($subject, $other));
			$r["keyOf $name remove $otherName"] = $view(\PHPStan\Type\TypeCombinator::remove($subject, $other));
		}
		foreach ($keyOfPeers as $peerName) {
			$peer = $keyOfSubjects[$peerName];
			$r["keyOf $name peer equals $peerName"] = [$subject->equals($peer), $subject->equals(\PHPStan\Type\Generic\TemplateTypeFactory::create($templateScopeF, 'K', $subject->getBound(), $subject->getVariance(), $subject->getStrategy(), $subject->getDefault())), $subject->equals(new \PHPStan\Type\Generic\TemplateKeyOfType($subject->getScope(), $subject->getStrategy(), $subject->getVariance(), $subject->getName(), $subject->getBound(), $subject->getDefault()))];
			$r["keyOf $name peer isSuperTypeOf $peerName"] = [$view($subject->isSuperTypeOf($peer)), $view($subject->isSubTypeOf($peer)), $view($subject->accepts($peer, true)), $view($subject->isAcceptedBy($peer, true))];
			$r["keyOf $name peer inferTemplateTypes $peerName"] = $templateMapView($subject->inferTemplateTypes($peer));
			$r["keyOf $name peer traverseSimultaneously $peerName"] = $view($subject->traverseSimultaneously($peer, static fn (\PHPStan\Type\Type $a, \PHPStan\Type\Type $b): \PHPStan\Type\Type => \PHPStan\Type\TypeCombinator::union($a, $b)));
			$r["keyOf $name peer union $peerName"] = [$view(\PHPStan\Type\TypeCombinator::union($subject, $peer)), $view(\PHPStan\Type\TypeCombinator::intersect($subject, $peer))];
		}
		foreach (['resolveToBounds', 'resolveToDefaults', 'toArgument', 'removeFinalByKeywordOverrides'] as $helperMethod) {
			$helperResult = \PHPStan\Type\Generic\TemplateTypeHelper::$helperMethod($subject);
			$r["keyOf $name helper $helperMethod"] = [$view($helperResult), $helperResult === $subject];
		}
		foreach ($templateVariances as $varianceName => $variance) {
			$r["keyOf $name helper resolveTemplateTypes $varianceName"] = $view(\PHPStan\Type\Generic\TemplateTypeHelper::resolveTemplateTypes($subject, $keyOfMap, \PHPStan\Type\Generic\TemplateTypeVarianceMap::createEmpty(), $variance));
		}
		$r["keyOf $name helper generalizeInferredTemplateType"] = $view(\PHPStan\Type\Generic\TemplateTypeHelper::generalizeInferredTemplateType($subject, $others['stringA']));
		$r["keyOf $name in compounds"] = [$view(new \PHPStan\Type\ArrayType($subject, $others['int'])), $view(\PHPStan\Type\TypeCombinator::union($subject, $others['null'])), $view(\PHPStan\Type\TypeCombinator::intersect($subject, $others['nonEmptyString'])), $view(new \PHPStan\Type\KeyOfType($subject)), $view((new \PHPStan\Type\KeyOfType($subject))->resolve()), $view(new \PHPStan\Type\Constant\ConstantArrayType([new \PHPStan\Type\Constant\ConstantIntegerType(0)], [$subject]))];
	}
	// the constructor's checks and the factory's dispatch on the key-of kinds
	foreach (['int' => new \PHPStan\Type\IntegerType(), 'array' => $templateBounds['arrayIntString'], 'valueOf' => new \PHPStan\Type\ValueOfType($templateConstShape)] as $badName => $badBound) {
		try {
			new \PHPStan\Type\Generic\TemplateKeyOfType($templateScopeF, $templateStrategies['parameter'], $templateVariances['invariant'], 'T', $badBound, null);
			$r["keyOf bad bound $badName"] = 'no throw';
		} catch (\TypeError $e) {
			$r["keyOf bad bound $badName"] = preg_replace('~^(\w+)\\\\?(.*)~', '$1', get_class($e)) . ': ' . preg_replace('~ given.*~', ' given', $e->getMessage());
		}
	}
	$keyOfReconstructed = $keyOfSubjects['constShape parameter none'];
	$keyOfReconstructed->__construct($templateScopeM, $templateStrategies['argument'], $templateVariances['covariant'], 'R', $keyOfBounds['arrayIntString'], new \PHPStan\Type\StringType());
	$r['keyOf reconstruct'] = [$view($keyOfReconstructed), $keyOfReconstructed->getName(), $keyOfReconstructed->isArgument(), $view($keyOfReconstructed->getDefault()), $keyOfReconstructed->getScope()->describe(), $view($keyOfReconstructed->getType()), $view($keyOfReconstructed->resolve())];
	foreach (['getName', 'resolve', 'getType', 'isResolvable'] as $uninitializedMethod) {
		try {
			(new ReflectionClass(\PHPStan\Type\Generic\TemplateKeyOfType::class))->newInstanceWithoutConstructor()->$uninitializedMethod();
			$r["keyOf uninitialized $uninitializedMethod"] = 'no throw';
		} catch (\Error $e) {
			$r["keyOf uninitialized $uninitializedMethod"] = [get_class($e), $e->getMessage()];
		}
	}
	$keyOfAnonymousBound = new class ($templateConstShape) extends \PHPStan\Type\KeyOfType {
		public function getType(): \PHPStan\Type\Type
		{
			return new \PHPStan\Type\ArrayType(new \PHPStan\Type\StringType(), new \PHPStan\Type\IntegerType());
		}

		protected function getResult(): \PHPStan\Type\Type
		{
			return new \PHPStan\Type\Constant\ConstantStringType('anon');
		}
	};
	// the factory's exact-class test routes the subclass past the key-of
	// arm; the constructor's `KeyOfType $bound` takes it, and both
	// $this-calls on the bound (getType(), getResult()) reach its overrides
	$r['keyOf anonymous bound factory'] = $view(\PHPStan\Type\Generic\TemplateTypeFactory::create($templateScopeF, 'A', $keyOfAnonymousBound, $templateVariances['invariant']));
	$keyOfOverAnonymous = new \PHPStan\Type\Generic\TemplateKeyOfType($templateScopeF, $templateStrategies['parameter'], $templateVariances['invariant'], 'A', $keyOfAnonymousBound, null);
	$r['keyOf anonymous bound'] = [$view($keyOfOverAnonymous), $view($keyOfOverAnonymous->getType()), $view($keyOfOverAnonymous->resolve()), $view($keyOfOverAnonymous->getBound()), $keyOfOverAnonymous->getBound() === $keyOfAnonymousBound, $view($keyOfOverAnonymous->traverse($templateMapper)), $view($keyOfOverAnonymous->toArgument())];
	foreach ($r as $key => $value) {
		$observations["template $key"] = $value;
	}
}


// ---- ConstantArrayTypeBuilder ----
// The builder folds the keys and values it collects through TypeCombinator
// and hands out ConstantArrayType / ArrayType / IntersectionType compounds,
// so its matrix runs under the real names here (one Type graph per
// process); smoke.php's prefixed block holds the side-by-side basics and
// the error modes. Every scenario runs under both BleedingEdgeToggle states
// (createEmpty() starts unsealed with explicit nevers on bleeding edge).
{
	$r = [];
	$int = new \PHPStan\Type\IntegerType();
	$string = new \PHPStan\Type\StringType();
	$float = new \PHPStan\Type\FloatType();
	$mixed = new \PHPStan\Type\MixedType();
	$ci = static fn (int $v): \PHPStan\Type\Constant\ConstantIntegerType => new \PHPStan\Type\Constant\ConstantIntegerType($v);
	$cs = static fn (string $v): \PHPStan\Type\Constant\ConstantStringType => new \PHPStan\Type\Constant\ConstantStringType($v);
	$closure = static fn (): \PHPStan\Type\ClosureType => new \PHPStan\Type\ClosureType([], new \PHPStan\Type\VoidType(), false);
	$limit = \PHPStan\Type\Constant\ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT;
	$builderClass = \PHPStan\Type\Constant\ConstantArrayTypeBuilder::class;
	$fresh = static fn (): \PHPStan\Type\Constant\ConstantArrayTypeBuilder => \PHPStan\Type\Constant\ConstantArrayTypeBuilder::createEmpty();
	/** @param list<array{0: ?\PHPStan\Type\Type, 1: \PHPStan\Type\Type, 2?: bool}> $sets */
	$built = static function (array $sets) use ($fresh): \PHPStan\Type\Constant\ConstantArrayTypeBuilder {
		$b = $fresh();
		foreach ($sets as $set) {
			$b->setOffsetValueType($set[0], $set[1], $set[2] ?? false);
		}
		return $b;
	};
	$scenarios = [
		'empty' => static fn () => $fresh(),
		'append' => static fn () => $built([[null, $int], [null, $string]]),
		'append optional' => static fn () => $built([[null, $int, true]]),
		'append optional then required' => static fn () => $built([[null, $int, true], [null, $string]]),
		'append required then optional' => static fn () => $built([[null, $int], [null, $string, true]]),
		'append two optional' => static fn () => $built([[null, $int, true], [null, $string, true], [null, $float]]),
		'keys 0 1 2' => static fn () => $built([[$ci(0), $int], [$ci(1), $string], [$ci(2), $float]]),
		'keys 0 2' => static fn () => $built([[$ci(0), $int], [$ci(2), $string]]),
		'key 2 first' => static fn () => $built([[$ci(2), $int]]),
		'key 1 then 0' => static fn () => $built([[$ci(1), $int], [$ci(0), $string]]),
		'negative key' => static fn () => $built([[$ci(-1), $int]]),
		'negative key optional' => static fn () => $built([[$ci(0), $int], [$ci(-1), $string, true]]),
		'string key' => static fn () => $built([[$cs('a'), $int]]),
		'string key optional' => static fn () => $built([[$cs('a'), $int, true]]),
		'string then int key' => static fn () => $built([[$cs('a'), $int], [$ci(0), $string]]),
		'numeric string key' => static fn () => $built([[$cs('1'), $int], [$ci(1), $string]]),
		'overwrite key' => static fn () => $built([[$ci(0), $int], [$ci(0), $string]]),
		'overwrite key optional' => static fn () => $built([[$ci(0), $int], [$ci(0), $string, true]]),
		'optional then overwrite' => static fn () => $built([[$ci(0), $int, true], [$ci(0), $string]]),
		'overwrite string key' => static fn () => $built([[$cs('a'), $int], [$cs('b'), $float], [$cs('a'), $string]]),
		'append after key 5' => static fn () => $built([[$ci(5), $int], [null, $string]]),
		'append after optional key' => static fn () => $built([[$ci(0), $int, true], [null, $string]]),
		'append after optional key optional' => static fn () => $built([[$ci(0), $int, true], [null, $string, true]]),
		'key within auto indexes' => static fn () => $built([[null, $int, true], [null, $string, true], [$ci(1), $float]]),
		'key beyond auto indexes optional' => static fn () => $built([[null, $int], [$ci(5), $string, true]]),
		'int max key' => static fn () => $built([[$ci(PHP_INT_MAX), $int]]),
		'int max key then append' => static fn () => $built([[$ci(PHP_INT_MAX), $int], [null, $string]]),
		'int max key optional then append' => static fn () => $built([[$ci(PHP_INT_MAX), $int, true], [null, $string]]),
		'float key' => static fn () => $built([[new \PHPStan\Type\Constant\ConstantFloatType(1.5), $int]]),
		'bool key' => static fn () => $built([[new \PHPStan\Type\Constant\ConstantBooleanType(true), $int]]),
		'null key' => static fn () => $built([[new \PHPStan\Type\NullType(), $int]]),
		'union const keys' => static fn () => $built([[new \PHPStan\Type\UnionType([$cs('a'), $cs('b')]), $int]]),
		'union const keys matching' => static fn () => $built([[$cs('a'), $int], [new \PHPStan\Type\UnionType([$cs('a'), $cs('b')]), $string]]),
		'union const keys matching optional existing' => static fn () => $built([[$cs('a'), $int, true], [new \PHPStan\Type\UnionType([$cs('a'), $cs('b')]), $string]]),
		'union const keys optional' => static fn () => $built([[$cs('a'), $int], [new \PHPStan\Type\UnionType([$cs('a'), $cs('b')]), $string, true]]),
		'union int keys advancing' => static fn () => $built([[null, $int], [new \PHPStan\Type\UnionType([$ci(1), $ci(3)]), $string], [null, $float]]),
		'union int max key' => static fn () => $built([[null, $int], [new \PHPStan\Type\UnionType([$ci(PHP_INT_MAX), $ci(1)]), $string]]),
		'range key' => static fn () => $built([[\PHPStan\Type\IntegerRangeType::fromInterval(0, 3), $int]]),
		'range key after keys' => static fn () => $built([[$ci(1), $string], [\PHPStan\Type\IntegerRangeType::fromInterval(0, 3), $int]]),
		'range key unbounded' => static fn () => $built([[\PHPStan\Type\IntegerRangeType::fromInterval(0, null), $int]]),
		'range key huge' => static fn () => $built([[\PHPStan\Type\IntegerRangeType::fromInterval(0, 1000), $int]]),
		'general string key' => static fn () => $built([[$string, $int]]),
		'general string key optional' => static fn () => $built([[$string, $int, true]]),
		'general string key after keys' => static fn () => $built([[$cs('a'), $int], [$string, $string]]),
		'general int key after keys' => static fn () => $built([[$ci(0), $int], [$ci(1), $float], [$int, $string]]),
		'general key then const key' => static fn () => $built([[$string, $int], [$cs('a'), $float]]),
		'mixed key' => static fn () => $built([[$mixed, $int]]),
		'int|string key' => static fn () => $built([[new \PHPStan\Type\UnionType([$int, $string]), $float]]),
		'array key' => static fn () => $built([[new \PHPStan\Type\ArrayType($int, $int), $float]]),
		'over limit appends' => static function () use ($fresh, $int, $limit) {
			$b = $fresh();
			for ($i = 0; $i <= $limit; $i++) {
				$b->setOffsetValueType(null, $int);
			}
			return $b;
		},
		'at limit appends' => static function () use ($fresh, $int, $limit) {
			$b = $fresh();
			for ($i = 0; $i < $limit; $i++) {
				$b->setOffsetValueType(null, $int);
			}
			return $b;
		},
		'over limit keys' => static function () use ($fresh, $int, $ci, $limit) {
			$b = $fresh();
			for ($i = 0; $i <= $limit; $i++) {
				$b->setOffsetValueType($ci($i * 2), $int);
			}
			return $b;
		},
		'over limit string keys optional' => static function () use ($fresh, $int, $cs, $limit) {
			$b = $fresh();
			for ($i = 0; $i <= $limit; $i++) {
				$b->setOffsetValueType($cs('k' . $i), $int, true);
			}
			return $b;
		},
		'over limit disabled' => static function () use ($fresh, $int, $limit) {
			$b = $fresh();
			$b->disableArrayDegradation();
			for ($i = 0; $i <= $limit + 40; $i++) {
				$b->setOffsetValueType(null, $int);
			}
			return $b;
		},
		'degrade explicit' => static function () use ($built, $int, $string, $ci) {
			$b = $built([[$ci(0), $int], [$ci(1), $string]]);
			$b->degradeToGeneralArray();
			return $b;
		},
		'degrade oversized explicit' => static function () use ($built, $int, $ci) {
			$b = $built([[$ci(0), $int]]);
			$b->degradeToGeneralArray(true);
			return $b;
		},
		'degrade empty' => static function () use ($fresh) {
			$b = $fresh();
			$b->degradeToGeneralArray();
			return $b;
		},
		'degrade then append' => static function () use ($fresh, $int, $string) {
			$b = $fresh();
			$b->degradeToGeneralArray();
			$b->setOffsetValueType(null, $int);
			$b->setOffsetValueType(null, $string);
			return $b;
		},
		'degrade then key' => static function () use ($fresh, $int, $cs) {
			$b = $fresh();
			$b->degradeToGeneralArray();
			$b->setOffsetValueType($cs('a'), $int);
			return $b;
		},
		'degrade all optional' => static function () use ($fresh, $int, $cs) {
			$b = $fresh();
			$b->degradeToGeneralArray();
			$b->setOffsetValueType($cs('a'), $int, true);
			return $b;
		},
		'degrade then general key' => static function () use ($fresh, $int, $string) {
			$b = $fresh();
			$b->degradeToGeneralArray();
			$b->setOffsetValueType($string, $int);
			return $b;
		},
		'degrade after disable' => static function () use ($fresh) {
			$b = $fresh();
			$b->disableArrayDegradation();
			$b->degradeToGeneralArray();
			return $b;
		},
		'closures 32' => static function () use ($fresh, $closure) {
			$b = $fresh();
			for ($i = 0; $i < 32; $i++) {
				$b->setOffsetValueType(null, $closure());
			}
			return $b;
		},
		'closures 31' => static function () use ($fresh, $closure) {
			$b = $fresh();
			for ($i = 0; $i < 31; $i++) {
				$b->setOffsetValueType(null, $closure());
			}
			return $b;
		},
		'closures 32 with ints' => static function () use ($fresh, $closure, $int, $cs) {
			$b = $fresh();
			$b->setOffsetValueType($cs('n'), $int);
			for ($i = 0; $i < 32; $i++) {
				$b->setOffsetValueType(null, $closure());
			}
			return $b;
		},
		'closures disabled' => static function () use ($fresh, $closure) {
			$b = $fresh();
			$b->disableClosureDegradation();
			for ($i = 0; $i < 40; $i++) {
				$b->setOffsetValueType(null, $closure());
			}
			return $b;
		},
		'closures with disabled degradation' => static function () use ($fresh, $closure) {
			$b = $fresh();
			$b->disableArrayDegradation();
			for ($i = 0; $i < 40; $i++) {
				$b->setOffsetValueType(null, $closure());
			}
			return $b;
		},
		'makeUnsealed' => static function () use ($built, $int, $string, $cs) {
			$b = $built([[$cs('a'), $int]]);
			$b->makeUnsealed($string, $int);
			return $b;
		},
		'makeUnsealed empty' => static function () use ($fresh, $int, $string) {
			$b = $fresh();
			$b->makeUnsealed($int, $string);
			return $b;
		},
		'makeUnsealed then general key' => static function () use ($fresh, $int, $string, $float) {
			$b = $fresh();
			$b->makeUnsealed($int, $string);
			$b->setOffsetValueType($string, $float);
			return $b;
		},
		'makeUnsealed then covered key' => static function () use ($built, $int, $string, $cs) {
			$b = $built([[$cs('a'), $int]]);
			$b->makeUnsealed($string, $int);
			$b->setOffsetValueType($string, $string);
			return $b;
		},
		'makeUnsealed then const key' => static function () use ($fresh, $int, $string, $cs) {
			$b = $fresh();
			$b->makeUnsealed($string, $int);
			$b->setOffsetValueType($cs('a'), $string);
			return $b;
		},
		'mergeUnsealed' => static function () use ($fresh, $int, $string) {
			$b = $fresh();
			$b->mergeUnsealed($int, $string);
			return $b;
		},
		'mergeUnsealed twice' => static function () use ($fresh, $int, $string, $float) {
			$b = $fresh();
			$b->mergeUnsealed($int, $string);
			$b->mergeUnsealed($string, $float);
			return $b;
		},
		'mergeUnsealed after keys' => static function () use ($built, $int, $string, $cs) {
			$b = $built([[$cs('a'), $int]]);
			$b->mergeUnsealed($string, $string);
			return $b;
		},
		'unsealed then degrade' => static function () use ($built, $int, $string, $cs) {
			$b = $built([[$cs('a'), $int]]);
			$b->makeUnsealed($string, $int);
			$b->degradeToGeneralArray();
			return $b;
		},
		'unsealed then over limit' => static function () use ($fresh, $int, $string, $float, $limit) {
			$b = $fresh();
			$b->makeUnsealed($string, $float);
			for ($i = 0; $i <= $limit; $i++) {
				$b->setOffsetValueType(null, $int);
			}
			return $b;
		},
		'from constant array' => static fn () => $builderClass::createFromConstantArray(new \PHPStan\Type\Constant\ConstantArrayType([$ci(0), $ci(1)], [$int, $string])),
		'from constant array then append' => static function () use ($builderClass, $int, $string, $float, $ci) {
			$b = $builderClass::createFromConstantArray(new \PHPStan\Type\Constant\ConstantArrayType([$ci(0), $ci(1)], [$int, $string], [2]));
			$b->setOffsetValueType(null, $float);
			return $b;
		},
		'from constant array optional' => static function () use ($builderClass, $int, $string, $float, $ci) {
			$b = $builderClass::createFromConstantArray(new \PHPStan\Type\Constant\ConstantArrayType([$ci(0), $ci(1)], [$int, $string], [1, 2], [1]));
			$b->setOffsetValueType(null, $float);
			return $b;
		},
		'from constant array unsealed' => static function () use ($builderClass, $int, $string, $float, $cs) {
			$b = $builderClass::createFromConstantArray(new \PHPStan\Type\Constant\ConstantArrayType([$cs('a')], [$int], [0], [], \PHPStan\TrinaryLogic::createNo(), [$string, $float]));
			$b->setOffsetValueType($string, $int);
			return $b;
		},
		'from constant array non-list' => static fn () => $builderClass::createFromConstantArray(new \PHPStan\Type\Constant\ConstantArrayType([$cs('a'), $ci(0)], [$int, $string], [1], [], \PHPStan\TrinaryLogic::createNo())),
		'from constant array big' => static function () use ($builderClass, $int, $ci, $limit) {
			$keys = [];
			$values = [];
			for ($i = 0; $i <= $limit; $i++) {
				$keys[] = $ci($i);
				$values[] = $int;
			}
			return $builderClass::createFromConstantArray(new \PHPStan\Type\Constant\ConstantArrayType($keys, $values, [$limit + 1], [], \PHPStan\TrinaryLogic::createYes()));
		},
		'from empty constant array' => static function () use ($builderClass, $int) {
			$b = $builderClass::createFromConstantArray(new \PHPStan\Type\Constant\ConstantArrayType([], []));
			$b->setOffsetValueType(null, $int);
			return $b;
		},
		'from empty unsealed constant array' => static fn () => $builderClass::createFromConstantArray(new \PHPStan\Type\Constant\ConstantArrayType([], [], [0], [], null, [$string, $int])),
	];
	foreach ([false, true] as $bleedingEdge) {
		foreach ($scenarios as $label => $scenario) {
			$r[($bleedingEdge ? 'bleeding-edge ' : '') . $label] = \PHPStan\DependencyInjection\BleedingEdgeToggle::withBleedingEdge($bleedingEdge, static function () use ($scenario, $view): array {
				try {
					$b = $scenario();
				} catch (\Throwable $e) {
					return ['throws', get_class($e)];
				}
				return [$view($b->getArray()), $b->isList()];
			});
		}
	}
	foreach ($r as $key => $value) {
		$observations["constant array builder $key"] = $value;
	}
	$observations['native ' . \PHPStan\Type\Constant\ConstantArrayTypeBuilder::class] = (new ReflectionMethod(\PHPStan\Type\Constant\ConstantArrayTypeBuilder::class, 'getArray'))->isInternal();
}


// ---- UnionTypeHelper ----
// sortTypes() over the observation types incl. the compounds (an
// intersection answers isConstantArray()/isString() through its members),
// in several input orders; the result is the sequence of input labels
// (spl_object_id → label), so stability among equal members shows too.
{
	$r = [];
	$int = new \PHPStan\Type\IntegerType();
	$string = new \PHPStan\Type\StringType();
	$ci = static fn (int $v): \PHPStan\Type\Constant\ConstantIntegerType => new \PHPStan\Type\Constant\ConstantIntegerType($v);
	$cs = static fn (string $v): \PHPStan\Type\Constant\ConstantStringType => new \PHPStan\Type\Constant\ConstantStringType($v);
	$classScope = \PHPStan\Type\Generic\TemplateTypeScope::createWithClass('Foo');
	$types = [
		'null' => new \PHPStan\Type\NullType(),
		'int' => $int,
		'int again' => new \PHPStan\Type\IntegerType(),
		'string' => $string,
		'const 1' => $ci(1),
		'const 1 again' => $ci(1),
		'const -3' => $ci(-3),
		'const 1.0' => new \PHPStan\Type\Constant\ConstantFloatType(1.0),
		'const 2.5' => new \PHPStan\Type\Constant\ConstantFloatType(2.5),
		'const -3.0' => new \PHPStan\Type\Constant\ConstantFloatType(-3.0),
		'const B' => $cs('B'),
		'const a' => $cs('a'),
		'const A' => $cs('A'),
		'const empty' => $cs(''),
		'const 10' => $cs('10'),
		'const 9' => $cs('9'),
		'true' => new \PHPStan\Type\Constant\ConstantBooleanType(true),
		'false' => new \PHPStan\Type\Constant\ConstantBooleanType(false),
		'bool' => new \PHPStan\Type\BooleanType(),
		'range 0-10' => \PHPStan\Type\IntegerRangeType::fromInterval(0, 10),
		'range min-5' => \PHPStan\Type\IntegerRangeType::fromInterval(null, 5),
		'range 5-max' => \PHPStan\Type\IntegerRangeType::fromInterval(5, null),
		'range -5-5' => \PHPStan\Type\IntegerRangeType::fromInterval(-5, 5),
		'non-empty-string' => new \PHPStan\Type\IntersectionType([$string, new \PHPStan\Type\Accessory\AccessoryNonEmptyStringType()]),
		'numeric-string' => new \PHPStan\Type\IntersectionType([$string, new \PHPStan\Type\Accessory\AccessoryNumericStringType()]),
		'literal-string' => new \PHPStan\Type\IntersectionType([$string, new \PHPStan\Type\Accessory\AccessoryLiteralStringType()]),
		'accessory non-empty' => new \PHPStan\Type\Accessory\AccessoryNonEmptyStringType(),
		'accessory numeric' => new \PHPStan\Type\Accessory\AccessoryNumericStringType(),
		'accessory list' => new \PHPStan\Type\Accessory\AccessoryArrayListType(),
		'accessory non-empty-array' => new \PHPStan\Type\Accessory\NonEmptyArrayType(),
		'enum hearts' => new \PHPStan\Type\Enum\EnumCaseObjectType('App\\Suit', 'Hearts'),
		'enum spades' => new \PHPStan\Type\Enum\EnumCaseObjectType('App\\Suit', 'Spades'),
		'enum red' => new \PHPStan\Type\Enum\EnumCaseObjectType('App\\Color', 'Red'),
		'callable' => new \PHPStan\Type\CallableType(),
		'callable(int): int' => new \PHPStan\Type\CallableType([new \PHPStan\Reflection\Native\NativeParameterReflection('x', false, $int, \PHPStan\Reflection\PassedByReference::createNo(), false, null)], $int, false),
		'closure' => new \PHPStan\Type\ClosureType([], $int, false),
		'closure(string): void' => new \PHPStan\Type\ClosureType([new \PHPStan\Reflection\Native\NativeParameterReflection('x', false, $string, \PHPStan\Reflection\PassedByReference::createNo(), false, null)], new \PHPStan\Type\VoidType(), false),
		'array{}' => new \PHPStan\Type\Constant\ConstantArrayType([], []),
		'array{} again' => new \PHPStan\Type\Constant\ConstantArrayType([], []),
		'array{a: int}' => new \PHPStan\Type\Constant\ConstantArrayType([$cs('a')], [$int]),
		'array{string}' => new \PHPStan\Type\Constant\ConstantArrayType([$ci(0)], [$string]),
		'array{a?: int}' => new \PHPStan\Type\Constant\ConstantArrayType([$cs('a')], [$int], [0], [0]),
		'non-empty-array{a: int}' => new \PHPStan\Type\IntersectionType([new \PHPStan\Type\Constant\ConstantArrayType([$cs('a')], [$int]), new \PHPStan\Type\Accessory\NonEmptyArrayType()]),
		'array<int, string>' => new \PHPStan\Type\ArrayType($int, $string),
		'non-empty-array<int, string>' => new \PHPStan\Type\IntersectionType([new \PHPStan\Type\ArrayType($int, $string), new \PHPStan\Type\Accessory\NonEmptyArrayType()]),
		'list<int>' => new \PHPStan\Type\IntersectionType([new \PHPStan\Type\ArrayType($int, $int), new \PHPStan\Type\Accessory\AccessoryArrayListType()]),
		'stdClass' => new \PHPStan\Type\ObjectType(\stdClass::class),
		'ArrayObject' => new \PHPStan\Type\ObjectType(\ArrayObject::class),
		'ArrayObject<int, string>' => new \PHPStan\Type\Generic\GenericObjectType(\ArrayObject::class, [$int, $string]),
		'float' => new \PHPStan\Type\FloatType(),
		'mixed' => new \PHPStan\Type\MixedType(),
		'never' => new \PHPStan\Type\NeverType(),
		'void' => new \PHPStan\Type\VoidType(),
		'object' => new \PHPStan\Type\ObjectWithoutClassType(),
		'class-string' => new \PHPStan\Type\ClassStringType(),
		'class-string<stdClass>' => new \PHPStan\Type\Generic\GenericClassStringType(new \PHPStan\Type\ObjectType(\stdClass::class)),
		'iterable' => new \PHPStan\Type\IterableType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()),
		'int|string' => new \PHPStan\Type\UnionType([$int, $string]),
		'T' => \PHPStan\Type\Generic\TemplateTypeFactory::create($classScope, 'T', null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
		'static' => new \PHPStan\Type\StaticType($stringReflectionProvider->getClass(\ArrayObject::class)),
		'resource' => new \PHPStan\Type\ResourceType(),
	];
	$labelOf = [];
	foreach ($types as $label => $type) {
		$labelOf[spl_object_id($type)] = $label;
	}
	$sequence = static fn (array $sorted): array => array_map(static fn (\PHPStan\Type\Type $t): string => $labelOf[spl_object_id($t)], $sorted);
	$declared = array_values($types);
	$interleaved = [];
	foreach ($declared as $i => $type) {
		if ($i % 2 === 0) {
			$interleaved[] = $type;
		}
	}
	foreach ($declared as $i => $type) {
		if ($i % 2 === 1) {
			$interleaved[] = $type;
		}
	}
	$rotated = array_merge(array_slice($declared, 17), array_slice($declared, 0, 17));
	foreach (['declared' => $declared, 'reversed' => array_reverse($declared), 'interleaved' => $interleaved, 'rotated' => $rotated, 'reversed pairs' => array_reverse(array_slice($declared, 0, 30))] as $orderLabel => $list) {
		$sorted = \PHPStan\Type\UnionTypeHelper::sortTypes($list);
		$r["sortTypes $orderLabel"] = $sequence($sorted);
		$r["sortTypes $orderLabel keys"] = array_keys($sorted);
	}
	foreach ($types as $aLabel => $a) {
		foreach (['null', 'int', 'const 1', 'const 1.0', 'const a', 'true', 'range 0-10', 'accessory numeric', 'enum hearts', 'closure', 'array{}', 'array{a: int}', 'non-empty-string', 'stdClass', 'mixed', 'T'] as $bLabel) {
			$r["sortTypes pair $aLabel / $bLabel"] = $sequence(\PHPStan\Type\UnionTypeHelper::sortTypes([$a, $types[$bLabel]]));
			$r["sortTypes pair $bLabel / $aLabel"] = $sequence(\PHPStan\Type\UnionTypeHelper::sortTypes([$types[$bLabel], $a]));
		}
	}
	$big = [$types['null']];
	for ($i = 0; $i < 1024; $i++) {
		$big[] = $int;
	}
	$sortedBig = \PHPStan\Type\UnionTypeHelper::sortTypes($big);
	$r['sortTypes over limit'] = [count($sortedBig), $sequence([$sortedBig[0]]), $sortedBig[1] === $int, $sortedBig === $big];
	array_pop($big);
	$sortedBig = \PHPStan\Type\UnionTypeHelper::sortTypes($big);
	$r['sortTypes at limit'] = [count($sortedBig), $sequence([$sortedBig[1023]]), $sortedBig[0] === $int];
	$r['sortTypes empty'] = \PHPStan\Type\UnionTypeHelper::sortTypes([]);
	$r['sortTypes string keys'] = $sequence(\PHPStan\Type\UnionTypeHelper::sortTypes(['x' => $types['null'], 'y' => $types['int'], 5 => $types['const a']]));
	foreach ($r as $key => $value) {
		$observations["union type helper $key"] = $value;
	}
	$observations['native ' . \PHPStan\Type\UnionTypeHelper::class] = (new ReflectionMethod(\PHPStan\Type\UnionTypeHelper::class, 'sortTypes'))->isInternal();
}


// ---- CallableTypeHelper ----
// isParametersAcceptorSuperTypeOf() over pairs of closure and callable
// acceptors (parameter counts, optional and variadic parameters, unnamed
// parameters, return types, purity and staticness) under every
// treatMixedAsAny / strictTypes combination; the result by its trinary and
// reasons.
{
	$r = [];
	$int = new \PHPStan\Type\IntegerType();
	$string = new \PHPStan\Type\StringType();
	$mixed = new \PHPStan\Type\MixedType();
	$void = new \PHPStan\Type\VoidType();
	$param = static fn (string $name, \PHPStan\Type\Type $type, bool $optional = false, bool $variadic = false): \PHPStan\Reflection\Native\NativeParameterReflection => new \PHPStan\Reflection\Native\NativeParameterReflection($name, $optional, $type, \PHPStan\Reflection\PassedByReference::createNo(), $variadic, null);
	$acceptors = [
		'(): mixed' => new \PHPStan\Type\ClosureType([], $mixed, false),
		'(int): int' => new \PHPStan\Type\ClosureType([$param('a', $int)], $int, false),
		'(int, string=): string' => new \PHPStan\Type\ClosureType([$param('a', $int), $param('b', $string, true)], $string, false),
		'(int ...$rest): void' => new \PHPStan\Type\ClosureType([$param('rest', $int, true, true)], $void, true),
		'(int, int ...$rest): int' => new \PHPStan\Type\ClosureType([$param('a', $int), $param('rest', $int, true, true)], $int, true),
		'(mixed): mixed' => new \PHPStan\Type\ClosureType([$param('a', $mixed)], $mixed, false),
		'(int|string, ?string): int|string' => new \PHPStan\Type\ClosureType([$param('a', new \PHPStan\Type\UnionType([$int, $string])), $param('b', new \PHPStan\Type\UnionType([$string, new \PHPStan\Type\NullType()]))], new \PHPStan\Type\UnionType([$int, $string]), false),
		'(string): int' => new \PHPStan\Type\ClosureType([$param('a', $string)], $int, false),
		'(int): string' => new \PHPStan\Type\ClosureType([$param('a', $int)], $string, false),
		'(stdClass): int' => new \PHPStan\Type\ClosureType([$param('o', new \PHPStan\Type\ObjectType(\stdClass::class))], $int, false),
		'(unnamed int): int' => new \PHPStan\Type\ClosureType([$param('', $int)], $int, false),
		'(int, int=, int=): int' => new \PHPStan\Type\ClosureType([$param('a', $int), $param('b', $int, true), $param('c', $int, true)], $int, false),
		'pure (int): int' => new \PHPStan\Type\ClosureType(parameters: [$param('a', $int)], returnType: $int, variadic: false, impurePoints: []),
		'static pure (int): int' => new \PHPStan\Type\ClosureType(parameters: [$param('a', $int)], returnType: $int, variadic: false, impurePoints: [], isStatic: \PHPStan\TrinaryLogic::createYes()),
		'non-static (int): int' => new \PHPStan\Type\ClosureType(parameters: [$param('a', $int)], returnType: $int, variadic: false, isStatic: \PHPStan\TrinaryLogic::createNo()),
		'callable' => new \PHPStan\Type\CallableType(),
		'callable(int): int' => new \PHPStan\Type\CallableType([$param('x', $int)], $int, false),
		'pure callable(int): int' => new \PHPStan\Type\CallableType([$param('x', $int)], $int, false, null, null, [], \PHPStan\TrinaryLogic::createYes()),
		'impure callable(int): int' => new \PHPStan\Type\CallableType([$param('x', $int)], $int, false, null, null, [], \PHPStan\TrinaryLogic::createNo()),
		'callable(int ...$rest): int' => new \PHPStan\Type\CallableType([$param('rest', $int, true, true)], $int, true),
	];
	foreach ($acceptors as $oursLabel => $ours) {
		foreach ($acceptors as $theirsLabel => $theirs) {
			foreach ([[false, true], [true, true], [true, false], [false, false]] as [$treatMixedAsAny, $strictTypes]) {
				$r[sprintf('%s <- %s%s%s', $oursLabel, $theirsLabel, $treatMixedAsAny ? ' mixed-as-any' : '', $strictTypes ? '' : ' loose')] = $view(\PHPStan\Type\CallableTypeHelper::isParametersAcceptorSuperTypeOf($ours, $theirs, $treatMixedAsAny, $strictTypes));
			}
		}
	}
	$r['(int): int <- (int, string=): string default strictTypes'] = $view(\PHPStan\Type\CallableTypeHelper::isParametersAcceptorSuperTypeOf($acceptors['(int): int'], $acceptors['(int, string=): string'], true));
	foreach ($r as $key => $value) {
		$observations["callable type helper $key"] = $value;
	}
	$observations['native ' . \PHPStan\Type\CallableTypeHelper::class] = (new ReflectionMethod(\PHPStan\Type\CallableTypeHelper::class, 'isParametersAcceptorSuperTypeOf'))->isInternal();
}


// ---- GetTemplateTypeType ----
// The late-resolvable `template-type<T, Class, Name>`: its own methods
// (describe, equals, traverse, toPhpDocNode, isResolvable, resolve) and the
// LateResolvableTypeTrait delegations over the resolved type, for subjects
// that resolve to a template argument, to an error, and that stay
// unresolvable (a template type inside).
{
	$r = [];
	$int = new \PHPStan\Type\IntegerType();
	$string = new \PHPStan\Type\StringType();
	$mixed = new \PHPStan\Type\MixedType();
	$phpVersion = new \PHPStan\Php\PhpVersion(80400);
	$classScope = \PHPStan\Type\Generic\TemplateTypeScope::createWithClass('Foo');
	$t = \PHPStan\Type\Generic\TemplateTypeFactory::create($classScope, 'T', null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
	$arrayObjectReflection = $stringReflectionProvider->getClass(\ArrayObject::class);
	$arrayObjectIntString = new \PHPStan\Type\Generic\GenericObjectType(\ArrayObject::class, [$int, $string], null, $arrayObjectReflection);
	$arrayObjectStringT = new \PHPStan\Type\Generic\GenericObjectType(\ArrayObject::class, [$string, $t], null, $arrayObjectReflection);
	$gtt = static fn (\PHPStan\Type\Type $type, string $ancestor, string $name): \PHPStan\Type\Helper\GetTemplateTypeType => new \PHPStan\Type\Helper\GetTemplateTypeType($type, $ancestor, $name);
	$subjects = [
		'Traversable<int, string> TValue' => $gtt(new \PHPStan\Type\Generic\GenericObjectType(\Traversable::class, [$int, $string]), \Traversable::class, 'TValue'),
		'Traversable<int, string> TKey' => $gtt(new \PHPStan\Type\Generic\GenericObjectType(\Traversable::class, [$int, $string]), \Traversable::class, 'TKey'),
		'Iterator<int, string> Traversable TValue' => $gtt(new \PHPStan\Type\Generic\GenericObjectType(\Iterator::class, [$int, $string]), \Traversable::class, 'TValue'),
		'ArrayObject<int, string> plain TValue' => $gtt(new \PHPStan\Type\Generic\GenericObjectType(\ArrayObject::class, [$int, $string]), \ArrayObject::class, 'TValue'),
		'ArrayObject<int, string> TValue' => $gtt($arrayObjectIntString, \ArrayObject::class, 'TValue'),
		'ArrayObject<int, string> TKey' => $gtt($arrayObjectIntString, \ArrayObject::class, 'TKey'),
		'ArrayObject<int, string> unknown' => $gtt($arrayObjectIntString, \ArrayObject::class, 'X'),
		'ArrayObject<int, string> IteratorAggregate TValue' => $gtt($arrayObjectIntString, \IteratorAggregate::class, 'TValue'),
		'ArrayObject<int, string> stdClass T' => $gtt($arrayObjectIntString, \stdClass::class, 'T'),
		'ArrayObject TValue' => $gtt(new \PHPStan\Type\ObjectType(\ArrayObject::class), \ArrayObject::class, 'TValue'),
		'ArrayObject<string, T> TValue' => $gtt($arrayObjectStringT, \ArrayObject::class, 'TValue'),
		'ArrayObject<string, T> TKey' => $gtt($arrayObjectStringT, \ArrayObject::class, 'TKey'),
		'T TValue' => $gtt($t, \ArrayObject::class, 'TValue'),
		'int TValue' => $gtt($int, \ArrayObject::class, 'TValue'),
		'stdClass T' => $gtt(new \PHPStan\Type\ObjectType(\stdClass::class), \stdClass::class, 'T'),
		'union TValue' => $gtt(new \PHPStan\Type\UnionType([$arrayObjectIntString, new \PHPStan\Type\Generic\GenericObjectType(\ArrayObject::class, [$string, new \PHPStan\Type\FloatType()], null, $arrayObjectReflection)]), \ArrayObject::class, 'TValue'),
		'nested TValue' => $gtt($gtt($arrayObjectIntString, \ArrayObject::class, 'TValue'), \ArrayObject::class, 'TValue'),
	];
	$others = [
		'int' => $int,
		'string' => $string,
		'mixed' => $mixed,
		'never' => new \PHPStan\Type\NeverType(),
		'null' => new \PHPStan\Type\NullType(),
		'ArrayObject<int, string>' => $arrayObjectIntString,
		'T' => $t,
		'same' => $subjects['ArrayObject<int, string> TValue'],
		'other' => $subjects['ArrayObject<int, string> TKey'],
		'unresolvable' => $subjects['T TValue'],
		'array{a: int}' => new \PHPStan\Type\Constant\ConstantArrayType([new \PHPStan\Type\Constant\ConstantStringType('a')], [$int]),
	];
	$scope = new \PHPStan\Analyser\OutOfClassScope();
	$catching = static function (callable $cb) use ($view): mixed {
		try {
			return $view($cb());
		} catch (\Throwable $e) {
			return ['throws', get_class($e)];
		}
	};
	foreach ($subjects as $name => $subject) {
		$r["$name instanceof"] = [$subject instanceof \PHPStan\Type\Type, $subject instanceof \PHPStan\Type\CompoundType, $subject instanceof \PHPStan\Type\LateResolvableType];
		foreach (['typeOnly' => \PHPStan\Type\VerbosityLevel::typeOnly(), 'value' => \PHPStan\Type\VerbosityLevel::value(), 'precise' => \PHPStan\Type\VerbosityLevel::precise(), 'cache' => \PHPStan\Type\VerbosityLevel::cache()] as $levelName => $level) {
			$r["$name describe $levelName"] = $subject->describe($level);
		}
		$r["$name isResolvable"] = $subject->isResolvable();
		$r["$name resolve"] = $catching(static fn () => $subject->resolve());
		$r["$name resolve memoized"] = $catching(static fn () => $subject->resolve() === $subject->resolve());
		$r["$name hasTemplateOrLateResolvableType"] = $subject->hasTemplateOrLateResolvableType();
		$r["$name getReferencedClasses"] = $subject->getReferencedClasses();
		$r["$name getReferencedTemplateTypes"] = $view($subject->getReferencedTemplateTypes(\PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()));
		$r["$name getReferencedTemplateTypes covariant"] = $view($subject->getReferencedTemplateTypes(\PHPStan\Type\Generic\TemplateTypeVariance::createCovariant()));
		$r["$name toPhpDocNode"] = $view($subject->toPhpDocNode());
		$r["$name traverse identity"] = $subject->traverse(static fn (\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type) === $subject;
		$r["$name traverse replaced"] = $view($subject->traverse(static fn (\PHPStan\Type\Type $type): \PHPStan\Type\Type => $int));
		$r["$name generalize"] = $view($subject->generalize(\PHPStan\Type\GeneralizePrecision::lessSpecific()));
		foreach ($subjects as $otherName => $other) {
			$r["$name equals $otherName"] = $subject->equals($other);
			$r["$name traverseSimultaneously $otherName identity"] = $subject->traverseSimultaneously($other, static fn (\PHPStan\Type\Type $a, \PHPStan\Type\Type $b): \PHPStan\Type\Type => $a) === $subject;
			$r["$name traverseSimultaneously $otherName right"] = $view($subject->traverseSimultaneously($other, static fn (\PHPStan\Type\Type $a, \PHPStan\Type\Type $b): \PHPStan\Type\Type => $b));
		}
		foreach ($others as $otherName => $other) {
			$r["$name isSuperTypeOf $otherName"] = $catching(static fn () => $subject->isSuperTypeOf($other));
			$r["$name isSubTypeOf $otherName"] = $catching(static fn () => $subject->isSubTypeOf($other));
			$r["$name accepts $otherName"] = $catching(static fn () => $subject->accepts($other, true));
			$r["$name accepts-loose $otherName"] = $catching(static fn () => $subject->accepts($other, false));
			$r["$name isAcceptedBy $otherName"] = $catching(static fn () => $subject->isAcceptedBy($other, true));
			$r["$name equals other $otherName"] = $subject->equals($other);
			$r["$name tryRemove $otherName"] = $catching(static fn () => $subject->tryRemove($other));
			$r["$name traverseSimultaneously other $otherName"] = $view($subject->traverseSimultaneously($other, static fn (\PHPStan\Type\Type $a, \PHPStan\Type\Type $b): \PHPStan\Type\Type => $b));
			$r["$name looseCompare $otherName"] = $catching(static fn () => $subject->looseCompare($other, $phpVersion));
			$r["$name isSmallerThan $otherName"] = $catching(static fn () => $subject->isSmallerThan($other, $phpVersion));
			$r["$name isSmallerThanOrEqual $otherName"] = $catching(static fn () => $subject->isSmallerThanOrEqual($other, $phpVersion));
			$r["$name isGreaterThan $otherName"] = $catching(static fn () => $subject->isGreaterThan($other, $phpVersion));
			$r["$name isGreaterThanOrEqual $otherName"] = $catching(static fn () => $subject->isGreaterThanOrEqual($other, $phpVersion));
			$r["$name getOffsetValueType $otherName"] = $catching(static fn () => $subject->getOffsetValueType($other));
			$r["$name hasOffsetValueType $otherName"] = $catching(static fn () => $subject->hasOffsetValueType($other));
			$r["$name exponentiate $otherName"] = $catching(static fn () => $subject->exponentiate($other));
			$r["$name inferTemplateTypes $otherName"] = $catching(static fn () => $subject->inferTemplateTypes($other));
			$r["$name setOffsetValueType $otherName"] = $catching(static fn () => $subject->setOffsetValueType($other, $int));
			$r["$name setExistingOffsetValueType $otherName"] = $catching(static fn () => $subject->setExistingOffsetValueType($other, $int));
			$r["$name unsetOffset $otherName"] = $catching(static fn () => $subject->unsetOffset($other));
		}
		foreach (['toBoolean', 'toNumber', 'toInteger', 'toFloat', 'toString', 'toArray', 'toArrayKey', 'toBitwiseNotType', 'toAbsoluteNumber', 'toGetClassResultType', 'toObjectTypeForInstanceofCheck',
			'isTrue', 'isFalse', 'isBoolean', 'isScalar', 'isNull', 'isInteger', 'isFloat', 'isString', 'isNumericString', 'isDecimalIntegerString', 'isNonEmptyString', 'isNonFalsyString', 'isLiteralString', 'isLowercaseString', 'isUppercaseString', 'isClassString', 'isVoid',
			'isConstantValue', 'isConstantScalarValue', 'getConstantScalarTypes', 'getConstantScalarValues', 'getFiniteTypes', 'isObject', 'isEnum', 'getArrays', 'getConstantArrays', 'getConstantStrings', 'getObjectClassNames', 'getObjectClassReflections',
			'getClassStringType', 'getClassStringObjectType', 'getObjectTypeOrClassStringObjectType', 'canAccessProperties', 'canCallMethods', 'canAccessConstants', 'isIterable', 'isIterableAtLeastOnce', 'getArraySize', 'getIterableKeyType', 'getFirstIterableKeyType', 'getLastIterableKeyType',
			'getIterableValueType', 'getFirstIterableValueType', 'getLastIterableValueType', 'isArray', 'isConstantArray', 'isOversizedArray', 'isList', 'isOffsetAccessible', 'isOffsetAccessLegal', 'getKeysArray', 'getValuesArray', 'flipArray', 'popArray', 'shiftArray', 'shuffleArray',
			'makeListMaybe', 'makeAllArrayKeysOptional', 'filterArrayRemovingFalsey', 'getEnumCases', 'getEnumCaseObject', 'isCallable', 'isCloneable'] as $method) {
			$r["$name $method"] = $catching(static fn () => $subject->$method());
		}
		foreach (['getSmallerType', 'getSmallerOrEqualType', 'getGreaterType', 'getGreaterOrEqualType'] as $method) {
			$r["$name $method"] = $catching(static fn () => $subject->$method($phpVersion));
		}
		$r["$name toCoercedArgumentType"] = [$catching(static fn () => $subject->toCoercedArgumentType(true)), $catching(static fn () => $subject->toCoercedArgumentType(false))];
		$r["$name getTemplateType"] = $catching(static fn () => $subject->getTemplateType(\ArrayObject::class, 'TValue'));
		$r["$name hasProperty"] = $catching(static fn () => $subject->hasProperty('x'));
		$r["$name hasInstanceProperty"] = $catching(static fn () => $subject->hasInstanceProperty('x'));
		$r["$name hasStaticProperty"] = $catching(static fn () => $subject->hasStaticProperty('x'));
		$r["$name hasMethod"] = $catching(static fn () => $subject->hasMethod('count'));
		$r["$name hasConstant"] = $catching(static fn () => $subject->hasConstant('X'));
		$r["$name mapValueType"] = $catching(static fn () => $subject->mapValueType(static fn (\PHPStan\Type\Type $t): \PHPStan\Type\Type => $t));
		$r["$name mapKeyType"] = $catching(static fn () => $subject->mapKeyType(static fn (\PHPStan\Type\Type $t): \PHPStan\Type\Type => $t));
		$r["$name changeKeyCaseArray"] = $catching(static fn () => $subject->changeKeyCaseArray(null));
		$r["$name searchArray"] = $catching(static fn () => $subject->searchArray($int));
		$r["$name searchArray strict"] = $catching(static fn () => $subject->searchArray($int, \PHPStan\TrinaryLogic::createYes()));
		$r["$name reverseArray"] = $catching(static fn () => $subject->reverseArray(\PHPStan\TrinaryLogic::createNo()));
		$r["$name chunkArray"] = $catching(static fn () => $subject->chunkArray(new \PHPStan\Type\Constant\ConstantIntegerType(2), \PHPStan\TrinaryLogic::createNo()));
		$r["$name fillKeysArray"] = $catching(static fn () => $subject->fillKeysArray($int));
		$r["$name intersectKeyArray"] = $catching(static fn () => $subject->intersectKeyArray($others['array{a: int}']));
		$r["$name sliceArray"] = $catching(static fn () => $subject->sliceArray(new \PHPStan\Type\Constant\ConstantIntegerType(0), new \PHPStan\Type\Constant\ConstantIntegerType(1), \PHPStan\TrinaryLogic::createNo()));
		$r["$name spliceArray"] = $catching(static fn () => $subject->spliceArray(new \PHPStan\Type\Constant\ConstantIntegerType(0), new \PHPStan\Type\Constant\ConstantIntegerType(1), $others['array{a: int}']));
		$r["$name truncateListToSize"] = $catching(static fn () => $subject->truncateListToSize(new \PHPStan\Type\Constant\ConstantIntegerType(1)));
		$r["$name getKeysArrayFiltered"] = $catching(static fn () => $subject->getKeysArrayFiltered($int, \PHPStan\TrinaryLogic::createYes()));
		$r["$name toObjectTypeForIsACheck"] = $catching(static fn () => $subject->toObjectTypeForIsACheck($mixed, true, true));
		$r["$name toClassConstantType"] = $catching(static fn () => $subject->toClassConstantType($stringReflectionProvider));
		$r["$name getCallableParametersAcceptors"] = $catching(static fn () => $subject->getCallableParametersAcceptors($scope));
		foreach (['getProperty', 'getInstanceProperty', 'getStaticProperty', 'getUnresolvedPropertyPrototype', 'getUnresolvedInstancePropertyPrototype', 'getUnresolvedStaticPropertyPrototype', 'getMethod', 'getUnresolvedMethodPrototype', 'getConstant'] as $method) {
			$r["$name $method"] = $catching(static function () use ($subject, $method, $scope): mixed {
				$args = $method === 'getConstant' ? ['X'] : [$method === 'getMethod' || $method === 'getUnresolvedMethodPrototype' ? 'count' : 'x', $scope];
				$result = $subject->$method(...$args);
				return is_object($result) ? get_class($result) : $result;
			});
		}
	}
	foreach ($r as $key => $value) {
		$observations["get template type type $key"] = $value;
	}
	$observations['native ' . \PHPStan\Type\Helper\GetTemplateTypeType::class] = (new ReflectionMethod(\PHPStan\Type\Helper\GetTemplateTypeType::class, 'describe'))->isInternal();
}


// ---- UnresolvableTypeHelper ----
// getUnresolvableType() over types with an ErrorType or an implicit
// NeverType somewhere inside (unions, arrays, generics, callables and
// closures built from the shadowed classes — the traversal is the native
// TypeTraverser's on both sides, the callback the twin's closure on one
// and the native body on the other): null or the distinct reasons, in
// order of first occurrence; the explicit never, a plain type and an
// intersection give null
$observations['native ' . \PHPStan\Rules\PhpDoc\UnresolvableTypeHelper::class] = (new ReflectionMethod(\PHPStan\Rules\PhpDoc\UnresolvableTypeHelper::class, 'getUnresolvableType'))->isInternal();
{
	$r = [];
	$helper = new \PHPStan\Rules\PhpDoc\UnresolvableTypeHelper();
	$int = new \PHPStan\Type\IntegerType();
	$string = new \PHPStan\Type\StringType();
	$errorParameter = new \PHPStan\Reflection\Native\NativeParameterReflection('e', false, new \PHPStan\Type\ErrorType('param'), \PHPStan\Reflection\PassedByReference::createNo(), false, null);
	$subjects = [
		'int' => $int,
		'error' => new \PHPStan\Type\ErrorType(),
		'errorReason' => new \PHPStan\Type\ErrorType('bad'),
		'never' => new \PHPStan\Type\NeverType(),
		'neverReason' => new \PHPStan\Type\NeverType(false, 'why'),
		'explicitNever' => new \PHPStan\Type\NeverType(true),
		'explicitNeverReason' => new \PHPStan\Type\NeverType(true, 'ignored'),
		'nonAcceptingNever' => new \PHPStan\Type\NonAcceptingNeverType(),
		'unionWithError' => new \PHPStan\Type\UnionType([$int, new \PHPStan\Type\ErrorType('u')]),
		'arrayOfNever' => new \PHPStan\Type\ArrayType($int, new \PHPStan\Type\NeverType(false, 'v')),
		'genericWithErrors' => new \PHPStan\Type\Generic\GenericObjectType(\ArrayObject::class, [new \PHPStan\Type\ErrorType('g'), new \PHPStan\Type\NeverType(false, 'g2')]),
		'duplicateReasons' => new \PHPStan\Type\UnionType([new \PHPStan\Type\ErrorType('dup'), new \PHPStan\Type\ArrayType($int, new \PHPStan\Type\ErrorType('dup')), new \PHPStan\Type\ErrorType('other'), new \PHPStan\Type\NeverType(false, 'dup')]),
		'nested' => new \PHPStan\Type\ArrayType(new \PHPStan\Type\ErrorType('k'), new \PHPStan\Type\UnionType([new \PHPStan\Type\NeverType(false, 'n'), $int])),
		'nullReasonMix' => new \PHPStan\Type\UnionType([new \PHPStan\Type\ErrorType(), new \PHPStan\Type\ErrorType('x'), new \PHPStan\Type\NeverType()]),
		'callable' => new \PHPStan\Type\CallableType([$errorParameter], new \PHPStan\Type\NeverType(false, 'ret'), false),
		'closure' => new \PHPStan\Type\ClosureType([$errorParameter], new \PHPStan\Type\ErrorType('closure'), false),
		'intersection' => new \PHPStan\Type\IntersectionType([new \PHPStan\Type\ArrayType($int, $int), new \PHPStan\Type\Accessory\NonEmptyArrayType()]),
		'constantArray' => new \PHPStan\Type\Constant\ConstantArrayType([new \PHPStan\Type\Constant\ConstantStringType('a')], [new \PHPStan\Type\ErrorType('shape')]),
		'circularAlias' => new \PHPStan\Type\CircularTypeAliasErrorType(),
	];
	foreach ($subjects as $name => $subject) {
		$result = $helper->getUnresolvableType($subject);
		$r[$name] = $result === null ? null : [get_class($result), $result->reasons];
	}
	foreach ($r as $key => $value) {
		$observations["unresolvable type helper $key"] = $value;
	}
}

// ---- NativeParameterReflection ----
// The value class over a parameter's name, optionality, type, by-reference
// mode, variadicness and default: the getters, toOptional() (identity for
// an optional parameter), union() (both optional / both variadic, the
// union of the types, the combined by-reference mode, this default only
// when both are optional), named arguments, and the errors of an
// unconstructed instance and of a wrong union() operand
$observations['native ' . \PHPStan\Reflection\Native\NativeParameterReflection::class] = (new ReflectionMethod(\PHPStan\Reflection\Native\NativeParameterReflection::class, 'getName'))->isInternal();
$viewParameter = static function (\PHPStan\Reflection\ParameterReflection $p) use ($view): array {
	$byRef = $p->passedByReference();
	$r = [get_class($p), $p->getName(), $p->isOptional(), $view($p->getType()), [$byRef->no(), $byRef->yes(), $byRef->createsNewVariable()], $p->isVariadic(), $view($p->getDefaultValue())];
	if ($p instanceof \PHPStan\Reflection\ExtendedParameterReflection) {
		$r[] = [$view($p->getNativeType()), $view($p->getPhpDocType()), $view($p->getOutType()), $view($p->isImmediatelyInvokedCallable()), $view($p->getClosureThisType()), count($p->getAttributes()), $p->getAllowedConstants() === null ? null : get_class($p->getAllowedConstants()), $view($p->isPureUnlessCallableIsImpureParameter()), $p->hasNativeType()];
	}
	return $r;
};
{
	$r = [];
	$int = new \PHPStan\Type\IntegerType();
	$string = new \PHPStan\Type\StringType();
	$parameters = [
		'required' => new \PHPStan\Reflection\Native\NativeParameterReflection('a', false, $int, \PHPStan\Reflection\PassedByReference::createNo(), false, null),
		'optional' => new \PHPStan\Reflection\Native\NativeParameterReflection('a', true, $string, \PHPStan\Reflection\PassedByReference::createReadsArgument(), false, new \PHPStan\Type\Constant\ConstantStringType('x')),
		'variadic' => new \PHPStan\Reflection\Native\NativeParameterReflection('rest', true, new \PHPStan\Type\UnionType([$int, $string]), \PHPStan\Reflection\PassedByReference::createCreatesNewVariable(), true, null),
		'named' => new \PHPStan\Reflection\Native\NativeParameterReflection(defaultValue: new \PHPStan\Type\NullType(), variadic: false, passedByReference: \PHPStan\Reflection\PassedByReference::createNo(), type: new \PHPStan\Type\MixedType(), optional: true, name: 'n'),
	];
	foreach ($parameters as $name => $parameter) {
		$r["$name getters"] = $viewParameter($parameter);
		$optional = $parameter->toOptional();
		$r["$name toOptional"] = [$viewParameter($optional), $optional === $parameter, $optional->toOptional() === $optional];
		foreach ($parameters as $otherName => $other) {
			$r["$name union $otherName"] = $viewParameter($parameter->union($other));
		}
	}
	$raw = (new \ReflectionClass(\PHPStan\Reflection\Native\NativeParameterReflection::class))->newInstanceWithoutConstructor();
	foreach (['getName', 'isOptional', 'getType', 'passedByReference', 'isVariadic', 'getDefaultValue', 'toOptional'] as $method) {
		try {
			$raw->$method();
			$r["unconstructed $method"] = 'no error';
		} catch (\Error $e) {
			$r["unconstructed $method"] = [get_class($e), $e->getMessage()];
		}
	}
	try {
		$parameters['required']->union($raw);
		$r['union unconstructed'] = 'no error';
	} catch (\Error $e) {
		$r['union unconstructed'] = [get_class($e), $e->getMessage()];
	}
	try {
		$parameters['required']->union(new \PHPStan\Reflection\Php\DummyParameter('d', $int, false, null, false, null));
		$r['union wrong class'] = 'no error';
	} catch (\TypeError $e) {
		// the userland twin's message names the call site (", called in ... on
		// line N"), the internal one does not
		$r['union wrong class'] = [get_class($e), preg_replace('~, called in .+ on line \d+$~', '', $e->getMessage())];
	}
	foreach ($r as $key => $value) {
		$observations["native parameter reflection $key"] = $value;
	}
}

// ---- CalledOnTypeUnresolvedMethodPrototypeReflection / CalledOnTypeUnresolvedPropertyPrototypeReflection / CallbackUnresolvedMethodPrototypeReflection / CallbackUnresolvedPropertyPrototypeReflection ----
// The lazy member prototypes over the fixture's methods and properties
// (static / $this / static<U> return types, static parameters, self-out,
// asserts, out and closure-this types, a template-typed property), each
// transformed for a set of called-on types on the CalledOnType side and
// through a static-rewriting or the identity callback on the Callback
// side, with and without resolving the template map to bounds: the
// transformed member (its variants, parameters, self-out, throw type and
// asserts), the memoization identities, the doNotResolve... and with...
// derivatives, and the errors of unconstructed instances
foreach ([\PHPStan\Reflection\Type\CalledOnTypeUnresolvedMethodPrototypeReflection::class => 'getTransformedMethod', \PHPStan\Reflection\Type\CalledOnTypeUnresolvedPropertyPrototypeReflection::class => 'getTransformedProperty', \PHPStan\Reflection\Type\CallbackUnresolvedMethodPrototypeReflection::class => 'getTransformedMethod', \PHPStan\Reflection\Type\CallbackUnresolvedPropertyPrototypeReflection::class => 'getTransformedProperty'] as $prototypeClass => $prototypeMethod) {
	$observations['native ' . $prototypeClass] = (new ReflectionMethod($prototypeClass, $prototypeMethod))->isInternal();
}
require_once __DIR__ . '/type-family-prototype-fixture.php';
{
	$r = [];
	$int = new \PHPStan\Type\IntegerType();
	$string = new \PHPStan\Type\StringType();
	$fixture = $stringReflectionProvider->getClass(\PHPStanTurboTests\PrototypeFixture::class);
	$subFixture = $stringReflectionProvider->getClass(\PHPStanTurboTests\PrototypeSubFixture::class);
	$genericFixture = $stringReflectionProvider->getClass(\PHPStanTurboTests\PrototypeFixture::class)->withTypes([$int]);
	$calledOnTypes = [
		'object' => new \PHPStan\Type\ObjectType(\PHPStanTurboTests\PrototypeFixture::class),
		'sub' => new \PHPStan\Type\ObjectType(\PHPStanTurboTests\PrototypeSubFixture::class),
		'generic' => new \PHPStan\Type\Generic\GenericObjectType(\PHPStanTurboTests\PrototypeFixture::class, [$int]),
		'static' => new \PHPStan\Type\StaticType($fixture),
		'this' => new \PHPStan\Type\ThisType($subFixture),
		'genericStatic' => new \PHPStan\Type\Generic\GenericStaticType($fixture, [$string], null, []),
		'union' => new \PHPStan\Type\UnionType([new \PHPStan\Type\ObjectType(\PHPStanTurboTests\PrototypeFixture::class), new \PHPStan\Type\ObjectType(\PHPStanTurboTests\PrototypeSubFixture::class)]),
	];
	$viewVariant = static function (\PHPStan\Reflection\ParametersAcceptor $v) use ($view, $viewParameter): array {
		$r = ['class' => get_class($v), 'return' => $view($v->getReturnType()), 'variadic' => $v->isVariadic(), 'templateTypeMap' => $view($v->getTemplateTypeMap()), 'resolvedTemplateTypeMap' => $view($v->getResolvedTemplateTypeMap()), 'parameters' => array_map($viewParameter, $v->getParameters())];
		if ($v instanceof \PHPStan\Reflection\ExtendedParametersAcceptor) {
			$r['phpDocReturn'] = $view($v->getPhpDocReturnType());
			$r['nativeReturn'] = $view($v->getNativeReturnType());
			$r['callSiteVarianceMap'] = get_class($v->getCallSiteVarianceMap());
		}
		return $r;
	};
	$viewMethod = static function (\PHPStan\Reflection\ExtendedMethodReflection $m) use ($view, $viewVariant): array {
		return [
			'class' => get_class($m),
			'name' => $m->getName(),
			'declaringClass' => $m->getDeclaringClass()->getName(),
			'variants' => array_map($viewVariant, $m->getVariants()),
			'namedArgumentsVariants' => $m->getNamedArgumentsVariants() === null ? null : array_map($viewVariant, $m->getNamedArgumentsVariants()),
			'selfOut' => $view($m->getSelfOutType()),
			'throw' => $view($m->getThrowType()),
			'asserts' => array_map(static fn (\PHPStan\PhpDoc\Tag\AssertTag $tag): array => [$tag->getIf(), $tag->getParameter()->describe(), $view($tag->getType()), $tag->isNegated(), $tag->isEquality()], $m->getAsserts()->getAll()),
			'assertsIfTrue' => count($m->getAsserts()->getAssertsIfTrue()),
		];
	};
	$viewProperty = static function (\PHPStan\Reflection\ExtendedPropertyReflection $p) use ($view): array {
		return [get_class($p), $p->getName(), $p->getDeclaringClass()->getName(), $view($p->getReadableType()), $view($p->getWritableType()), $view($p->getPhpDocType()), $view($p->getNativeType()), $p->isStatic(), $p->isPublic()];
	};
	$staticRewriter = static fn (\PHPStan\Type\Type $to): \Closure => static fn (\PHPStan\Type\Type $type): \PHPStan\Type\Type => \PHPStan\Type\TypeTraverser::map($type, static fn (\PHPStan\Type\Type $type, callable $traverse): \PHPStan\Type\Type => $type instanceof \PHPStan\Type\StaticType ? $to : $traverse($type));
	$identity = static fn (\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type;
	// a userland function's argument TypeError names the call site (", called
	// in ... on line N"), an internal function's does not: the message compared
	// without it
	$catching = static function (callable $fn): mixed {
		try {
			return $fn();
		} catch (\Throwable $e) {
			return [get_class($e), preg_replace('~, called in .+ on line \d+$~', '', $e->getMessage())];
		}
	};
	foreach (['returnsStatic', 'returnsThis', 'takesStatic', 'withValue', 'assertStatic', 'get', 'each', 'fails'] as $methodName) {
		$method = $fixture->getNativeMethod($methodName);
		$genericMethod = $genericFixture->getNativeMethod($methodName);
		foreach ($calledOnTypes as $calledOnName => $calledOnType) {
			foreach ([true, false] as $resolveToBounds) {
				$key = "$methodName $calledOnName " . ($resolveToBounds ? 'bounds' : 'nobounds');
				foreach (['plain' => [$method, $fixture], 'generic' => [$genericMethod, $genericFixture]] as $declaringName => [$declaringMethod, $declaringClass]) {
					$prototype = new \PHPStan\Reflection\Type\CalledOnTypeUnresolvedMethodPrototypeReflection($declaringMethod, $declaringClass, $resolveToBounds, $calledOnType);
					$transformed = $catching(static fn () => $prototype->getTransformedMethod());
					$r["calledOnType $declaringName $key"] = [
						is_object($transformed) ? $viewMethod($transformed) : $transformed,
						$catching(static fn () => $prototype->getTransformedMethod() === $prototype->getTransformedMethod()),
						$prototype->getNakedMethod() === $declaringMethod,
					];
					$doNotResolve = $prototype->doNotResolveTemplateTypeMapToBounds();
					$r["calledOnType $declaringName $key doNotResolve"] = [get_class($doNotResolve), $doNotResolve === $prototype->doNotResolveTemplateTypeMapToBounds(), $doNotResolve->doNotResolveTemplateTypeMapToBounds() === $doNotResolve, $catching(static fn () => $viewMethod($doNotResolve->getTransformedMethod()))];
					$with = $prototype->withCalledOnType($calledOnTypes['sub']);
					$r["calledOnType $declaringName $key withCalledOnType"] = [get_class($with), $with === $prototype, $catching(static fn () => $viewMethod($with->getTransformedMethod()))];
				}
				foreach (['rewrite' => $staticRewriter($calledOnType), 'identity' => $identity] as $callbackName => $callback) {
					$prototype = new \PHPStan\Reflection\Type\CallbackUnresolvedMethodPrototypeReflection($method, $fixture, $resolveToBounds, $callback);
					$transformed = $catching(static fn () => $prototype->getTransformedMethod());
					$r["callback $callbackName $key"] = [
						is_object($transformed) ? $viewMethod($transformed) : $transformed,
						$catching(static fn () => $prototype->getTransformedMethod() === $prototype->getTransformedMethod()),
						$prototype->getNakedMethod() === $method,
					];
					$doNotResolve = $prototype->doNotResolveTemplateTypeMapToBounds();
					$r["callback $callbackName $key doNotResolve"] = [get_class($doNotResolve), $doNotResolve === $prototype->doNotResolveTemplateTypeMapToBounds(), $catching(static fn () => $viewMethod($doNotResolve->getTransformedMethod()))];
					$with = $prototype->withCalledOnType($calledOnTypes['generic']);
					$r["callback $callbackName $key withCalledOnType"] = [get_class($with), $catching(static fn () => $viewMethod($with->getTransformedMethod()))];
				}
			}
		}
	}
	foreach (['sibling', 'value'] as $propertyName) {
		$property = $fixture->getNativeProperty($propertyName);
		$genericProperty = $genericFixture->getNativeProperty($propertyName);
		foreach ($calledOnTypes as $calledOnName => $calledOnType) {
			foreach ([true, false] as $resolveToBounds) {
				$key = "$propertyName $calledOnName " . ($resolveToBounds ? 'bounds' : 'nobounds');
				foreach (['plain' => [$property, $fixture], 'generic' => [$genericProperty, $genericFixture]] as $declaringName => [$declaringProperty, $declaringClass]) {
					$prototype = new \PHPStan\Reflection\Type\CalledOnTypeUnresolvedPropertyPrototypeReflection($declaringProperty, $declaringClass, $resolveToBounds, $calledOnType);
					$transformed = $catching(static fn () => $prototype->getTransformedProperty());
					$r["calledOnType property $declaringName $key"] = [
						is_object($transformed) ? $viewProperty($transformed) : $transformed,
						$catching(static fn () => $prototype->getTransformedProperty() === $prototype->getTransformedProperty()),
						$prototype->getNakedProperty() === $declaringProperty,
					];
					$doNotResolve = $prototype->doNotResolveTemplateTypeMapToBounds();
					$r["calledOnType property $declaringName $key doNotResolve"] = [get_class($doNotResolve), $doNotResolve === $prototype->doNotResolveTemplateTypeMapToBounds(), $catching(static fn () => $viewProperty($doNotResolve->getTransformedProperty()))];
					$with = $prototype->withFechedOnType($calledOnTypes['sub']);
					$r["calledOnType property $declaringName $key withFechedOnType"] = [get_class($with), $with === $prototype, $catching(static fn () => $viewProperty($with->getTransformedProperty()))];
				}
				foreach (['rewrite' => $staticRewriter($calledOnType), 'identity' => $identity] as $callbackName => $callback) {
					$prototype = new \PHPStan\Reflection\Type\CallbackUnresolvedPropertyPrototypeReflection($property, $fixture, $resolveToBounds, $callback);
					$transformed = $catching(static fn () => $prototype->getTransformedProperty());
					$r["callback property $callbackName $key"] = [
						is_object($transformed) ? $viewProperty($transformed) : $transformed,
						$catching(static fn () => $prototype->getTransformedProperty() === $prototype->getTransformedProperty()),
						$prototype->getNakedProperty() === $property,
					];
					$doNotResolve = $prototype->doNotResolveTemplateTypeMapToBounds();
					$r["callback property $callbackName $key doNotResolve"] = [get_class($doNotResolve), $doNotResolve === $prototype->doNotResolveTemplateTypeMapToBounds(), $catching(static fn () => $viewProperty($doNotResolve->getTransformedProperty()))];
					$with = $prototype->withFechedOnType($calledOnTypes['generic']);
					$r["callback property $callbackName $key withFechedOnType"] = [get_class($with), $catching(static fn () => $viewProperty($with->getTransformedProperty()))];
				}
			}
		}
	}
	// a callback returning a non-Type, a non-callable constructor argument,
	// unconstructed instances
	$badCallback = static fn (\PHPStan\Type\Type $type): string => 'nope';
	$badPrototype = new \PHPStan\Reflection\Type\CallbackUnresolvedMethodPrototypeReflection($fixture->getNativeMethod('returnsStatic'), $fixture, true, $badCallback);
	$r['callback returning a string'] = $catching(static fn () => $badPrototype->getTransformedMethod());
	$badPropertyPrototype = new \PHPStan\Reflection\Type\CallbackUnresolvedPropertyPrototypeReflection($fixture->getNativeProperty('sibling'), $fixture, true, $badCallback);
	$r['callback property returning a string'] = $catching(static fn () => $badPropertyPrototype->getTransformedProperty());
	foreach ([\PHPStan\Reflection\Type\CallbackUnresolvedMethodPrototypeReflection::class => $fixture->getNativeMethod('get'), \PHPStan\Reflection\Type\CallbackUnresolvedPropertyPrototypeReflection::class => $fixture->getNativeProperty('value')] as $callbackClass => $member) {
		$r["$callbackClass not callable"] = $catching(static fn () => new $callbackClass($member, $fixture, true, 'no such function'));
	}
	foreach ([\PHPStan\Reflection\Type\CalledOnTypeUnresolvedMethodPrototypeReflection::class => ['getNakedMethod', 'getTransformedMethod', 'doNotResolveTemplateTypeMapToBounds', 'withCalledOnType'], \PHPStan\Reflection\Type\CalledOnTypeUnresolvedPropertyPrototypeReflection::class => ['getNakedProperty', 'getTransformedProperty', 'doNotResolveTemplateTypeMapToBounds', 'withFechedOnType'], \PHPStan\Reflection\Type\CallbackUnresolvedMethodPrototypeReflection::class => ['getNakedMethod', 'getTransformedMethod', 'doNotResolveTemplateTypeMapToBounds', 'withCalledOnType'], \PHPStan\Reflection\Type\CallbackUnresolvedPropertyPrototypeReflection::class => ['getNakedProperty', 'getTransformedProperty', 'doNotResolveTemplateTypeMapToBounds', 'withFechedOnType']] as $prototypeClass => $methods) {
		$raw = (new \ReflectionClass($prototypeClass))->newInstanceWithoutConstructor();
		foreach ($methods as $rawMethod) {
			$r["$prototypeClass unconstructed $rawMethod"] = $catching(static fn () => $raw->$rawMethod(...(str_starts_with($rawMethod, 'with') ? [$int] : [])));
		}
	}
	foreach ($r as $key => $value) {
		$observations["unresolved prototype reflections $key"] = $value;
	}
}

// ---- ResolvedMethodReflection / ChangedTypeMethodReflection ----
// The method reflections the prototypes above hand out, observed through
// every method of the interface: the transformed fixture methods
// (ResolvedMethodReflection over ChangedTypeMethodReflection over the PHP
// reflection), both classes constructed directly over the PHP, built-in and
// dummy reflections and over each other (the by-name delegation paths), a
// wrapped reflection answering isBuiltin()/isAbstract() with bools, the
// memoization identities, getOnlyVariant()'s errors and unconstructed
// instances
foreach ([\PHPStan\Reflection\ResolvedMethodReflection::class, \PHPStan\Reflection\Dummy\ChangedTypeMethodReflection::class] as $methodReflectionClass) {
	$observations['native ' . $methodReflectionClass] = (new ReflectionMethod($methodReflectionClass, 'getName'))->isInternal();
}
{
	$r = [];
	$viewAttributes = static fn (array $attributes): array => array_map(static fn (\PHPStan\Reflection\AttributeReflection $attribute): string => $attribute->getName(), $attributes);
	$viewAllMethod = static function (\PHPStan\Reflection\ExtendedMethodReflection $m) use ($view, $viewMethod, $viewVariant, $catching, $viewAttributes): array {
		return [
			'base' => $catching(static fn () => $viewMethod($m)),
			'onlyVariant' => $catching(static fn () => $viewVariant($m->getOnlyVariant())),
			'prototype' => $catching(static fn () => [get_class($m->getPrototype()), $m->getPrototype()->getDeclaringClass()->getName()]),
			'flags' => $catching(static fn () => [$m->isStatic(), $m->isPrivate(), $m->isPublic(), $m->getDocComment(), $view($m->isDeprecated()), $m->getDeprecatedDescription(), $view($m->isFinal()), $view($m->isFinalByKeyword()), $view($m->isInternal()), $view($m->isBuiltin())]),
			'purity' => $catching(static fn () => [$view($m->hasSideEffects()), $view($m->isPure()), $m->getPureUnlessCallableIsImpureParameters(), $view($m->acceptsNamedArguments()), $view($m->returnsByReference()), $view($m->isAbstract()), $view($m->mustUseReturnValue())]),
			'attributes' => $catching(static fn () => $viewAttributes($m->getAttributes())),
			'phpDoc' => $catching(static fn () => $view($m->getResolvedPhpDoc())),
			'memo' => $catching(static fn () => [$m->getVariants() === $m->getVariants(), $m->getNamedArgumentsVariants() === $m->getNamedArgumentsVariants(), $m->getAsserts() === $m->getAsserts(), $m->getSelfOutType() === $m->getSelfOutType(), $m->hasSideEffects() === $m->hasSideEffects(), $m->getDeclaringClass() === $m->getDeclaringClass()]),
		];
	};
	foreach (['returnsStatic', 'takesStatic', 'withValue', 'assertStatic', 'get', 'each', 'fails'] as $methodName) {
		foreach (['object', 'generic', 'static'] as $calledOnName) {
			foreach ([true, false] as $resolveToBounds) {
				$prototype = new \PHPStan\Reflection\Type\CalledOnTypeUnresolvedMethodPrototypeReflection($genericFixture->getNativeMethod($methodName), $genericFixture, $resolveToBounds, $calledOnTypes[$calledOnName]);
				$transformed = $prototype->getTransformedMethod();
				$key = "$methodName $calledOnName " . ($resolveToBounds ? 'bounds' : 'nobounds');
				$r["transformed $key"] = [get_class($transformed), $viewAllMethod($transformed)];
			}
		}
	}
	$varianceMaps = ['empty' => \PHPStan\Type\Generic\TemplateTypeVarianceMap::createEmpty(), 'covariant' => new \PHPStan\Type\Generic\TemplateTypeVarianceMap(['T' => \PHPStan\Type\Generic\TemplateTypeVariance::createCovariant(), 'U' => \PHPStan\Type\Generic\TemplateTypeVariance::createContravariant()])];
	$templateMaps = ['empty' => \PHPStan\Type\Generic\TemplateTypeMap::createEmpty(), 'fixture' => $genericFixture->getActiveTemplateTypeMap(), 'u' => new \PHPStan\Type\Generic\TemplateTypeMap(['U' => $string, 'T' => $int])];
	$innerReflections = [
		'php withValue' => $fixture->getNativeMethod('withValue'),
		'php assertStatic' => $fixture->getNativeMethod('assertStatic'),
		'php fails' => $fixture->getNativeMethod('fails'),
		'builtin' => $stringReflectionProvider->getClass(\ArrayObject::class)->getNativeMethod('count'),
		'builtin overloaded' => $stringReflectionProvider->getClass(\DateTime::class)->getNativeMethod('setTime'),
		'dummy' => new \PHPStan\Reflection\Dummy\DummyMethodReflection('__call'),
	];
	// a wrapped reflection answering the bool alternatives of the interface
	$boolAnswering = static fn (\PHPStan\Reflection\ExtendedMethodReflection $inner, bool $answer): \PHPStan\Reflection\ExtendedMethodReflection => new class ($inner, $answer) implements \PHPStan\Reflection\ExtendedMethodReflection {

		public function __construct(private \PHPStan\Reflection\ExtendedMethodReflection $inner, private bool $answer)
		{
		}

		public function getDeclaringClass(): \PHPStan\Reflection\ClassReflection { return $this->inner->getDeclaringClass(); }
		public function isStatic(): bool { return !$this->answer; }
		public function isPrivate(): bool { return $this->answer; }
		public function isPublic(): bool { return !$this->answer; }
		public function getDocComment(): ?string { return $this->answer ? '/** doc */' : null; }
		public function getName(): string { return 'boolAnswering'; }
		public function getPrototype(): \PHPStan\Reflection\ClassMemberReflection { return $this; }
		public function getVariants(): array { return $this->inner->getVariants(); }
		public function getOnlyVariant(): \PHPStan\Reflection\ExtendedParametersAcceptor { return $this->inner->getOnlyVariant(); }
		public function getNamedArgumentsVariants(): ?array { return $this->answer ? $this->inner->getVariants() : null; }
		public function isDeprecated(): \PHPStan\TrinaryLogic { return \PHPStan\TrinaryLogic::createMaybe(); }
		public function getDeprecatedDescription(): ?string { return $this->answer ? 'old' : null; }
		public function isFinal(): \PHPStan\TrinaryLogic { return \PHPStan\TrinaryLogic::createFromBoolean($this->answer); }
		public function isFinalByKeyword(): \PHPStan\TrinaryLogic { return \PHPStan\TrinaryLogic::createNo(); }
		public function isInternal(): \PHPStan\TrinaryLogic { return \PHPStan\TrinaryLogic::createYes(); }
		public function isBuiltin(): \PHPStan\TrinaryLogic|bool { return $this->answer; }
		public function getThrowType(): ?\PHPStan\Type\Type { return $this->answer ? new \PHPStan\Type\ObjectType(\LogicException::class) : null; }
		public function hasSideEffects(): \PHPStan\TrinaryLogic { return \PHPStan\TrinaryLogic::createFromBoolean(!$this->answer); }
		public function isPure(): \PHPStan\TrinaryLogic { return \PHPStan\TrinaryLogic::createMaybe(); }
		public function getPureUnlessCallableIsImpureParameters(): array { return $this->answer ? ['callback' => true] : []; }
		public function getAsserts(): \PHPStan\Reflection\Assertions { return $this->inner->getAsserts(); }
		public function acceptsNamedArguments(): \PHPStan\TrinaryLogic { return \PHPStan\TrinaryLogic::createFromBoolean($this->answer); }
		public function getSelfOutType(): ?\PHPStan\Type\Type { return $this->answer ? new \PHPStan\Type\Generic\GenericObjectType(\PHPStanTurboTests\PrototypeFixture::class, [(new \PHPStan\Type\Generic\TemplateTypeReference(\PHPStan\Type\Generic\TemplateTypeFactory::create(\PHPStan\Type\Generic\TemplateTypeScope::createWithClass(\PHPStanTurboTests\PrototypeFixture::class), 'T', null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()))->getType()]) : null; }
		public function returnsByReference(): \PHPStan\TrinaryLogic { return \PHPStan\TrinaryLogic::createNo(); }
		public function isAbstract(): \PHPStan\TrinaryLogic|bool { return !$this->answer; }
		public function getAttributes(): array { return []; }
		public function mustUseReturnValue(): \PHPStan\TrinaryLogic { return \PHPStan\TrinaryLogic::createMaybe(); }
		public function getResolvedPhpDoc(): ?\PHPStan\PhpDoc\ResolvedPhpDocBlock { return null; }

	};
	$innerReflections['bool answering true'] = $boolAnswering($fixture->getNativeMethod('withValue'), true);
	$innerReflections['bool answering false'] = $boolAnswering($fixture->getNativeMethod('get'), false);
	$assertions = $fixture->getNativeMethod('assertStatic')->getAsserts();
	foreach ($innerReflections as $innerName => $inner) {
		foreach ($templateMaps as $templateMapName => $templateMap) {
			foreach ($varianceMaps as $varianceMapName => $varianceMap) {
				$resolved = new \PHPStan\Reflection\ResolvedMethodReflection($inner, $templateMap, $varianceMap);
				$r["resolved over $innerName $templateMapName $varianceMapName"] = $viewAllMethod($resolved);
			}
		}
		$changed = new \PHPStan\Reflection\Dummy\ChangedTypeMethodReflection($genericFixture, $inner, $catching(static fn () => $inner->getVariants()), null, $int, new \PHPStan\Type\ObjectType(\RuntimeException::class), $assertions);
		if (is_array($changed->getVariants())) {
			$r["changed over $innerName"] = $viewAllMethod($changed);
			$r["resolved over changed over $innerName"] = $viewAllMethod(new \PHPStan\Reflection\ResolvedMethodReflection($changed, $templateMaps['u'], $varianceMaps['covariant']));
			$r["changed over resolved over $innerName"] = $viewAllMethod(new \PHPStan\Reflection\Dummy\ChangedTypeMethodReflection(namedArgumentsVariants: [], selfOutType: null, throwType: null, assertions: \PHPStan\Reflection\Assertions::createEmpty(), declaringClass: $fixture, reflection: new \PHPStan\Reflection\ResolvedMethodReflection($inner, $templateMaps['fixture'], $varianceMaps['empty']), variants: []));
		}
	}
	// getOnlyVariant(): no variant, two variants, a variant under another key
	// (the warning converted to an exception, and left a warning)
	$variant = $fixture->getNativeMethod('get')->getOnlyVariant();
	foreach (['none' => [], 'two' => [$variant, $variant], 'key 1' => [1 => $variant]] as $variantsName => $variants) {
		$changed = new \PHPStan\Reflection\Dummy\ChangedTypeMethodReflection($fixture, $fixture->getNativeMethod('get'), $variants, $variants, null, null, $assertions);
		set_error_handler(static function (int $errno, string $errstr): bool {
			throw new \ErrorException($errstr, 0, $errno);
		});
		try {
			$r["changed getOnlyVariant $variantsName"] = $catching(static fn () => $viewVariant($changed->getOnlyVariant()));
			$resolved = new \PHPStan\Reflection\ResolvedMethodReflection($changed, $templateMaps['empty'], $varianceMaps['empty']);
			$r["resolved getOnlyVariant $variantsName"] = $catching(static fn () => $viewVariant($resolved->getOnlyVariant()));
		} finally {
			restore_error_handler();
		}
		if ($variantsName === 'key 1') {
			$r["changed getOnlyVariant $variantsName warning"] = $catching(static fn () => @$changed->getOnlyVariant());
		}
	}
	foreach ([\PHPStan\Reflection\ResolvedMethodReflection::class, \PHPStan\Reflection\Dummy\ChangedTypeMethodReflection::class] as $methodReflectionClass) {
		$raw = (new \ReflectionClass($methodReflectionClass))->newInstanceWithoutConstructor();
		foreach ((new \ReflectionClass(\PHPStan\Reflection\ExtendedMethodReflection::class))->getMethods() as $interfaceMethod) {
			$methodName = $interfaceMethod->getName();
			$r["$methodReflectionClass unconstructed $methodName"] = $catching(static fn () => $view($raw->$methodName()));
		}
	}
	foreach ($r as $key => $value) {
		$observations["method reflections $key"] = $value;
	}
}

// ---- SimpleImpurePoint ----
// The value class and its two statics over real function and method
// reflections: pure / impure / void functions, the flip parameters of
// print_r() and var_export() (positional and named, truthy, maybe and falsy
// arguments), the pure-unless-callable-is-impure parameters of array_filter()
// and array_reduce() fed pure, impure, maybe-pure, null, non-callable and
// omitted callbacks (positional and named), the transformed and plain
// fixture methods, a missing scope or variant, and unconstructed instances
$observations['native ' . \PHPStan\Reflection\Callables\SimpleImpurePoint::class] = (new ReflectionMethod(\PHPStan\Reflection\Callables\SimpleImpurePoint::class, 'createFromVariant'))->isInternal();
{
	$r = [];
	$viewImpurePoint = static fn (?\PHPStan\Reflection\Callables\SimpleImpurePoint $point): ?array => $point === null ? null : [$point->getIdentifier(), $point->getDescription(), $point->isCertain()];
	$bool = new \PHPStan\Type\BooleanType();
	$pureClosure = new \PHPStan\Type\ClosureType([], $int, false, impurePoints: []);
	$impureClosure = new \PHPStan\Type\ClosureType([], $int, false, impurePoints: [new \PHPStan\Reflection\Callables\SimpleImpurePoint('functionCall', 'certain', true)]);
	$maybeClosure = new \PHPStan\Type\ClosureType([], $int, false);
	$sipScope = $stringContainer->getByType(\PHPStan\Analyser\ScopeFactory::class)->create(\PHPStan\Analyser\ScopeContext::create(__FILE__));
	foreach (['true' => new \PHPStan\Type\Constant\ConstantBooleanType(true), 'false' => new \PHPStan\Type\Constant\ConstantBooleanType(false), 'bool' => $bool, 'null' => new \PHPStan\Type\NullType(), 'string' => $string, 'pure' => $pureClosure, 'impure' => $impureClosure, 'maybe' => $maybeClosure, 'pureOrImpure' => new \PHPStan\Type\UnionType([$pureClosure, $impureClosure]), 'array' => new \PHPStan\Type\ArrayType($int, $int)] as $variableName => $variableType) {
		$sipScope = $sipScope->assignVariable($variableName, $variableType, $variableType, \PHPStan\TrinaryLogic::createYes());
	}
	$arg = static fn (string $variable, ?string $name = null): \PhpParser\Node\Arg => new \PhpParser\Node\Arg(new \PhpParser\Node\Expr\Variable($variable), name: $name === null ? null : new \PhpParser\Node\Identifier($name));
	$argLists = [
		'none' => [],
		'array' => [$arg('array')],
		'array true' => [$arg('array'), $arg('true')],
		'array false' => [$arg('array'), $arg('false')],
		'array bool' => [$arg('array'), $arg('bool')],
		'named return true' => [$arg('array'), $arg('true', 'return')],
		'named first return' => [$arg('true', 'return'), $arg('array')],
		'named other' => [$arg('array'), $arg('true', 'other')],
		'array pure' => [$arg('array'), $arg('pure')],
		'array impure' => [$arg('array'), $arg('impure')],
		'array maybe' => [$arg('array'), $arg('maybe')],
		'array null' => [$arg('array'), $arg('null')],
		'array string' => [$arg('array'), $arg('string')],
		'array pureOrImpure' => [$arg('array'), $arg('pureOrImpure')],
		'named callback impure' => [$arg('impure', 'callback'), $arg('array', 'array')],
		'named callback pure' => [$arg('array', 'array'), $arg('pure', 'callback')],
		'named two maybe' => [$arg('array', 'one'), $arg('maybe', 'two')],
		'keyed' => [1 => $arg('impure'), 0 => $arg('array')],
	];
	$functions = [];
	foreach (['strlen', 'usleep', 'print_r', 'var_export', 'highlight_string', 'array_filter', 'array_reduce', 'array_map', 'rand'] as $functionName) {
		$functions[$functionName] = $stringReflectionProvider->getFunction(new \PhpParser\Node\Name($functionName), null);
	}
	foreach ($functions as $functionName => $function) {
		$variant = $function->getVariants()[0];
		$r["function $functionName no scope"] = $catching(static fn () => $viewImpurePoint(\PHPStan\Reflection\Callables\SimpleImpurePoint::createFromVariant($function, $variant)));
		$r["function $functionName no variant"] = $catching(static fn () => $viewImpurePoint(\PHPStan\Reflection\Callables\SimpleImpurePoint::createFromVariant($function, null, $sipScope, $argLists['array true'])));
		foreach ($argLists as $argListName => $args) {
			$r["function $functionName $argListName"] = $catching(static fn () => $viewImpurePoint(\PHPStan\Reflection\Callables\SimpleImpurePoint::createFromVariant($function, $variant, $sipScope, $args)));
			$r["function $functionName $argListName verdict"] = $catching(static fn () => $view(\PHPStan\Reflection\Callables\SimpleImpurePoint::resolvePureUnlessCallableIsImpureVerdict($variant, $sipScope, $args)));
		}
	}
	foreach (['returnsStatic', 'withValue', 'each', 'fails'] as $methodName) {
		$prototype = new \PHPStan\Reflection\Type\CalledOnTypeUnresolvedMethodPrototypeReflection($fixture->getNativeMethod($methodName), $fixture, true, $calledOnTypes['object']);
		foreach (['transformed' => $prototype->getTransformedMethod(), 'plain' => $fixture->getNativeMethod($methodName), 'builtin' => $stringReflectionProvider->getClass(\ArrayObject::class)->getNativeMethod('append')] as $methodKind => $method) {
			$variant = $method->getVariants()[0];
			$r["method $methodName $methodKind"] = $catching(static fn () => $viewImpurePoint(\PHPStan\Reflection\Callables\SimpleImpurePoint::createFromVariant($method, $variant, $sipScope, $argLists['array impure'])));
			$r["method $methodName $methodKind no variant"] = $catching(static fn () => $viewImpurePoint(\PHPStan\Reflection\Callables\SimpleImpurePoint::createFromVariant($method, null)));
		}
	}
	$r['constructed'] = $viewImpurePoint(new \PHPStan\Reflection\Callables\SimpleImpurePoint('methodCall', 'a description', true));
	$r['named constructor'] = $viewImpurePoint(new \PHPStan\Reflection\Callables\SimpleImpurePoint(certain: false, description: 'd', identifier: 'propertyAssign'));
	$raw = (new \ReflectionClass(\PHPStan\Reflection\Callables\SimpleImpurePoint::class))->newInstanceWithoutConstructor();
	foreach (['getIdentifier', 'getDescription', 'isCertain'] as $rawMethod) {
		$r["unconstructed $rawMethod"] = $catching(static fn () => $raw->$rawMethod());
	}
	$r['constant'] = (new \ReflectionClassConstant(\PHPStan\Reflection\Callables\SimpleImpurePoint::class, 'SIDE_EFFECT_FLIP_PARAMETERS'))->getValue();
	foreach ($r as $key => $value) {
		$observations["simple impure point $key"] = $value;
	}
}


// ---- PassedByReference / DummyParameter / ExtendedDummyParameter ----
// The by-reference mode singletons (their identity, the queries, equals()
// and combine() over every pair, the private constructor, an unconstructed
// instance), and the dummy parameters over them: the getters of plain,
// by-reference, named-argument and extended instances (hasNativeType() over
// a plain, an explicit and a real native type), checkAllowedConstants() with
// and without allowed constants, a PHP subclass overriding a getter, the
// errors of unconstructed instances and of a wrong argument type
foreach ([\PHPStan\Reflection\PassedByReference::class => 'no', \PHPStan\Reflection\Php\DummyParameter::class => 'getName', \PHPStan\Reflection\Php\ExtendedDummyParameter::class => 'getNativeType'] as $parameterValueClass => $parameterValueMethod) {
	$observations['native ' . $parameterValueClass] = (new ReflectionMethod($parameterValueClass, $parameterValueMethod))->isInternal();
}
if (!class_exists('PHPStanTurboTests\DummyParameterSubclass', false)) {
	eval('namespace PHPStanTurboTests; class DummyParameterSubclass extends \PHPStan\Reflection\Php\DummyParameter { public function getName(): string { return "sub:" . parent::getName(); } }');
}
{
	$r = [];
	$error = static function (callable $cb): array|string {
		try {
			$cb();
			return 'no error';
		} catch (\Throwable $e) {
			return [get_class($e), preg_replace('~, called in .+ on line \d+$~', '', $e->getMessage())];
		}
	};
	$modes = [
		'no' => \PHPStan\Reflection\PassedByReference::createNo(),
		'reads' => \PHPStan\Reflection\PassedByReference::createReadsArgument(),
		'creates' => \PHPStan\Reflection\PassedByReference::createCreatesNewVariable(),
	];
	$r['singletons'] = [\PHPStan\Reflection\PassedByReference::createNo() === $modes['no'], \PHPStan\Reflection\PassedByReference::createReadsArgument() === $modes['reads'], \PHPStan\Reflection\PassedByReference::createCreatesNewVariable() === $modes['creates'], $modes['no'] !== $modes['reads']];
	foreach ($modes as $name => $mode) {
		$r["mode $name"] = [$mode->no(), $mode->yes(), $mode->createsNewVariable()];
		foreach ($modes as $otherName => $other) {
			$r["mode $name equals $otherName"] = $mode->equals($other);
			$r["mode $name combine $otherName"] = array_search($mode->combine($other), $modes, true);
		}
	}
	$r['private constructor'] = $error(static fn () => (new \ReflectionClass(\PHPStan\Reflection\PassedByReference::class))->newInstance(1));
	$rawMode = (new \ReflectionClass(\PHPStan\Reflection\PassedByReference::class))->newInstanceWithoutConstructor();
	foreach (['no', 'yes', 'createsNewVariable'] as $method) {
		$r["unconstructed mode $method"] = $error(static fn () => $rawMode->$method());
	}
	$r['unconstructed mode equals'] = $error(static fn () => $modes['no']->equals($rawMode));
	$r['unconstructed mode combine'] = $error(static fn () => $modes['no']->combine($rawMode));

	$int = new \PHPStan\Type\IntegerType();
	$string = new \PHPStan\Type\StringType();
	$allowed = new \PHPStan\Reflection\ParameterAllowedConstants('list', [], []);
	$dummies = [
		'plain' => new \PHPStan\Reflection\Php\DummyParameter('a', $int, false, null, false, null),
		'byRef' => new \PHPStan\Reflection\Php\DummyParameter('b', $string, true, $modes['creates'], true, new \PHPStan\Type\Constant\ConstantStringType('x')),
		'named' => new \PHPStan\Reflection\Php\DummyParameter(defaultValue: null, variadic: false, passedByReference: null, optional: true, type: new \PHPStan\Type\MixedType(), name: 'n'),
		'extended' => new \PHPStan\Reflection\Php\ExtendedDummyParameter('e', $int, false, $modes['reads'], false, null, new \PHPStan\Type\MixedType(), $int, $string, \PHPStan\TrinaryLogic::createYes(), new \PHPStan\Type\ObjectType(\stdClass::class), [], null, \PHPStan\TrinaryLogic::createMaybe()),
		'extendedExplicitMixed' => new \PHPStan\Reflection\Php\ExtendedDummyParameter('x', $int, true, null, true, $int, new \PHPStan\Type\MixedType(true), $int, null, \PHPStan\TrinaryLogic::createNo(), null, [], $allowed, \PHPStan\TrinaryLogic::createNo()),
		'extendedNative' => new \PHPStan\Reflection\Php\ExtendedDummyParameter(pureUnlessCallableIsImpureParameter: \PHPStan\TrinaryLogic::createYes(), allowedConstants: null, attributes: [], closureThisType: null, immediatelyInvokedCallable: \PHPStan\TrinaryLogic::createMaybe(), outType: null, phpDocType: $string, nativeType: $string, defaultValue: null, variadic: false, passedByReference: $modes['no'], optional: false, type: $string, name: 's'),
		'subclass' => new \PHPStanTurboTests\DummyParameterSubclass('sub', $int, false, null, false, null),
	];
	foreach ($dummies as $name => $dummy) {
		$r["dummy $name"] = $viewParameter($dummy);
		$r["dummy $name by-ref identity"] = array_search($dummy->passedByReference(), $modes, true);
	}
	foreach (['extended', 'extendedExplicitMixed', 'extendedNative'] as $name) {
		$result = $dummies[$name]->checkAllowedConstants([]);
		$r["dummy $name checkAllowedConstants"] = [get_class($result), $result->isOk(), $result->isBitmaskNotAllowed(), $result->getDisallowedConstants(), $result->getViolatedExclusiveGroups()];
	}
	foreach ([\PHPStan\Reflection\Php\DummyParameter::class => ['getName', 'isOptional', 'getType', 'passedByReference', 'isVariadic', 'getDefaultValue'], \PHPStan\Reflection\Php\ExtendedDummyParameter::class => ['getName', 'getPhpDocType', 'hasNativeType', 'getNativeType', 'getOutType', 'isImmediatelyInvokedCallable', 'getClosureThisType', 'getAttributes', 'getAllowedConstants', 'isPureUnlessCallableIsImpureParameter']] as $class => $methods) {
		$raw = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
		foreach ($methods as $method) {
			$r["unconstructed $class $method"] = $error(static fn () => $raw->$method());
		}
	}
	$r['dummy wrong name'] = $error(static fn () => new \PHPStan\Reflection\Php\DummyParameter([], $int, false, null, false, null));
	$r['extended wrong attributes'] = $error(static fn () => new \PHPStan\Reflection\Php\ExtendedDummyParameter('e', $int, false, null, false, null, $int, $int, null, \PHPStan\TrinaryLogic::createYes(), null, 'x', null, \PHPStan\TrinaryLogic::createNo()));
	foreach ($r as $key => $value) {
		$observations["parameter values $key"] = $value;
	}
}

// ---- ArgumentsNormalizer ----
// reorderArgs() and the reorder*Arguments() methods over positional, named,
// reordered, gapped, duplicate, unknown-named and unpacked arguments against
// acceptors with optional defaults, a variadic tail, a variadic that is not
// last and an optional parameter without a default: the reordered lists
// (their values, names, original-argument attributes and the default-filled
// TypeExprs), the identity of an unchanged call, the rebuilt call's class and
// attributes (the printed-form cache dropped), and the exception of a missing
// default
$observations['native ' . \PHPStan\Analyser\ArgumentsNormalizer::class] = (new ReflectionMethod(\PHPStan\Analyser\ArgumentsNormalizer::class, 'reorderArgs'))->isInternal();
{
	$r = [];
	$error = static function (callable $cb): array|string {
		try {
			$cb();
			return 'no error';
		} catch (\Throwable $e) {
			return [get_class($e), preg_replace('~, called in .+ on line \d+$~', '', $e->getMessage())];
		}
	};
	$int = new \PHPStan\Type\IntegerType();
	$param = static fn (string $name, bool $optional = false, ?\PHPStan\Type\Type $default = null, bool $variadic = false): \PHPStan\Reflection\Php\DummyParameter => new \PHPStan\Reflection\Php\DummyParameter($name, $int, $optional, null, $variadic, $default);
	$variant = static fn (array $parameters, bool $variadic = false): \PHPStan\Reflection\FunctionVariant => new \PHPStan\Reflection\FunctionVariant(\PHPStan\Type\Generic\TemplateTypeMap::createEmpty(), null, $parameters, $variadic, $int);
	$acceptors = [
		'defaults' => $variant([$param('a'), $param('b', true, new \PHPStan\Type\Constant\ConstantIntegerType(1)), $param('c', true, new \PHPStan\Type\Constant\ConstantIntegerType(2))]),
		'variadic' => $variant([$param('a'), $param('rest', true, null, true)], true),
		'variadicNotLast' => $variant([$param('rest', true, null, true), $param('after')], true),
		'noDefault' => $variant([$param('a'), $param('b', true), $param('c')]),
		'native' => $variant([new \PHPStan\Reflection\Native\NativeParameterReflection('a', false, $int, \PHPStan\Reflection\PassedByReference::createNo(), false, null), new \PHPStan\Reflection\Native\NativeParameterReflection('b', true, $int, \PHPStan\Reflection\PassedByReference::createNo(), false, new \PHPStan\Type\Constant\ConstantIntegerType(5))]),
	];
	$arg = static function (int $value, ?string $name = null, bool $unpack = false): \PhpParser\Node\Arg {
		$node = new \PhpParser\Node\Arg(new \PhpParser\Node\Scalar\Int_($value), false, $unpack, ['startLine' => $value], $name === null ? null : new \PhpParser\Node\Identifier($name));
		return $node;
	};
	$argSets = [
		'empty' => [],
		'positional' => [$arg(1), $arg(2)],
		'nonList' => [1 => $arg(1), 3 => $arg(2)],
		'namedInOrder' => [$arg(1, 'a'), $arg(2, 'b')],
		'namedReordered' => [$arg(3, 'c'), $arg(1, 'a')],
		'gap' => [$arg(1), $arg(3, 'c')],
		'requiredGap' => [$arg(3, 'c')],
		'duplicate' => [$arg(1, 'a'), $arg(2, 'a')],
		'unknown' => [$arg(1), $arg(9, 'zzz')],
		'unknownAndGap' => [$arg(9, 'zzz'), $arg(3, 'c'), $arg(1)],
		'unpacked' => [$arg(1, null, true), $arg(2, 'b')],
	];
	$viewArgs = static function (?array $args) use ($view): array|string|null {
		if ($args === null) {
			return null;
		}
		$result = [];
		foreach ($args as $key => $a) {
			$value = $a->value;
			$original = $a->getAttribute(\PHPStan\Analyser\ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE);
			$result[] = [
				$key,
				$value instanceof \PHPStan\Node\Expr\TypeExpr ? ['TypeExpr', $view($value->getExprType())] : [get_class($value), $value instanceof \PhpParser\Node\Scalar\Int_ ? $value->value : null],
				$a->name?->toString(),
				$a->unpack,
				$original === null ? null : [$original->name?->toString(), $original->value === $value],
				array_keys($a->getAttributes()),
			];
		}
		return $result;
	};
	foreach ($acceptors as $acceptorName => $acceptor) {
		foreach ($argSets as $argSetName => $args) {
			$r["reorderArgs $acceptorName $argSetName"] = $error(static function () use (&$r, $acceptor, $args, $viewArgs, $acceptorName, $argSetName): void {
				$reordered = \PHPStan\Analyser\ArgumentsNormalizer::reorderArgs($acceptor, $args);
				$r["reorderArgs $acceptorName $argSetName result"] = [$viewArgs($reordered), $reordered === $args, $reordered === null ? null : array_is_list($reordered)];
			});
			$attributes = ['startLine' => 7, \PHPStan\Node\Printer\ExprPrinter::ATTRIBUTE_CACHE_KEY => 'printed'];
			$calls = [
				'func' => [new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('f'), array_values($args) === $args ? $args : $args, $attributes), 'reorderFuncArguments'],
				'method' => [new \PhpParser\Node\Expr\MethodCall(new \PhpParser\Node\Expr\Variable('o'), 'm', $args, $attributes), 'reorderMethodArguments'],
				'static' => [new \PhpParser\Node\Expr\StaticCall(new \PhpParser\Node\Name('C'), 's', $args, $attributes), 'reorderStaticCallArguments'],
				'new' => [new \PhpParser\Node\Expr\New_(new \PhpParser\Node\Name('C'), $args, $attributes), 'reorderNewArguments'],
			];
			foreach ($calls as $callName => [$call, $method]) {
				$r["$method $acceptorName $argSetName"] = $error(static function () use (&$r, $call, $method, $acceptor, $viewArgs, $acceptorName, $argSetName): void {
					$normalized = \PHPStan\Analyser\ArgumentsNormalizer::$method($acceptor, $call);
					$r["$method $acceptorName $argSetName result"] = $normalized === null ? null : [get_class($normalized), $normalized === $call, $viewArgs($normalized->getArgs()), $normalized->getAttributes(), $normalized instanceof \PhpParser\Node\Expr\MethodCall || $normalized instanceof \PhpParser\Node\Expr\StaticCall ? $normalized->name->toString() : null];
				});
			}
		}
	}
	$r['reorderMethodArguments wrong call'] = $error(static fn () => \PHPStan\Analyser\ArgumentsNormalizer::reorderMethodArguments($acceptors['defaults'], new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('f'))));
	$r['ORIGINAL_ARG_ATTRIBUTE'] = \PHPStan\Analyser\ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE;
	foreach ($r as $key => $value) {
		$observations["arguments normalizer $key"] = $value;
	}
}

// ---- ParametersAcceptorSelector ----
// selectFromTypes() over single and multiple variants (arity filtering, the
// unpack shortcut, the mixed-parameter maybe, the winning certainty and the
// combination of ties / of all acceptable ones), combineAcceptors() over
// plain, extended and callable acceptors (names, optionality, defaults,
// by-reference modes, native / phpdoc / out / closure-this types, attributes,
// allowed constants and the variadic cut-off), combineVariantsForNormalization()
// with and without named arguments, the template predicates, and the
// errors of an empty variant list
$observations['native ' . \PHPStan\Reflection\ParametersAcceptorSelector::class] = (new ReflectionMethod(\PHPStan\Reflection\ParametersAcceptorSelector::class, 'selectFromTypes'))->isInternal();
{
	$r = [];
	$error = static function (callable $cb): array|string {
		try {
			$cb();
			return 'no error';
		} catch (\Throwable $e) {
			return [get_class($e), preg_replace('~, called in .+ on line \d+$~', '', $e->getMessage())];
		}
	};
	$int = new \PHPStan\Type\IntegerType();
	$string = new \PHPStan\Type\StringType();
	$mixed = new \PHPStan\Type\MixedType();
	$template = \PHPStan\Type\Generic\TemplateTypeFactory::create(\PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('foo'), 'T', null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
	$emptyMap = \PHPStan\Type\Generic\TemplateTypeMap::createEmpty();
	$param = static fn (string $name, \PHPStan\Type\Type $type, bool $optional = false, bool $variadic = false, ?\PHPStan\Type\Type $default = null, ?\PHPStan\Reflection\PassedByReference $byRef = null): \PHPStan\Reflection\Php\DummyParameter => new \PHPStan\Reflection\Php\DummyParameter($name, $type, $optional, $byRef, $variadic, $default);
	$extParam = static fn (string $name, \PHPStan\Type\Type $type, bool $optional = false, bool $variadic = false, ?\PHPStan\Type\Type $out = null, ?\PHPStan\Type\Type $closureThis = null, ?\PHPStan\Reflection\ParameterAllowedConstants $allowed = null, ?\PHPStan\TrinaryLogic $immediately = null, ?\PHPStan\TrinaryLogic $pure = null): \PHPStan\Reflection\Php\ExtendedDummyParameter => new \PHPStan\Reflection\Php\ExtendedDummyParameter($name, $type, $optional, \PHPStan\Reflection\PassedByReference::createReadsArgument(), $variadic, $optional ? $type : null, $type, $type, $out, $immediately ?? \PHPStan\TrinaryLogic::createMaybe(), $closureThis, [], $allowed, $pure ?? \PHPStan\TrinaryLogic::createNo());
	$variant = static fn (array $parameters, bool $variadic = false, ?\PHPStan\Type\Type $return = null): \PHPStan\Reflection\FunctionVariant => new \PHPStan\Reflection\FunctionVariant($emptyMap, null, $parameters, $variadic, $return ?? $int);
	$extVariant = static fn (array $parameters, bool $variadic = false, ?\PHPStan\Type\Type $return = null): \PHPStan\Reflection\ExtendedFunctionVariant => new \PHPStan\Reflection\ExtendedFunctionVariant($emptyMap, null, $parameters, $variadic, $return ?? $int, $return ?? $int, $mixed);
	$allowedA = new \PHPStan\Reflection\ParameterAllowedConstants('list', [], []);
	$allowedB = new \PHPStan\Reflection\ParameterAllowedConstants('bitmask', [], []);
	$acceptorSets = [
		'intOrString' => [$variant([$param('a', $int)]), $variant([$param('a', $string)])],
		'arity' => [$variant([$param('a', $int)]), $variant([$param('a', $int), $param('b', $int)]), $variant([$param('a', $int), $param('b', $int, true), $param('c', $int, true)])],
		'variadic' => [$variant([$param('a', $int)]), $variant([$param('a', $int), $param('rest', $string, true, true)], true)],
		'mixedParams' => [$variant([$param('a', $mixed)]), $variant([$param('b', $mixed)])],
		'extended' => [$extVariant([$extParam('a', $int, false, false, $int, $int, $allowedA, \PHPStan\TrinaryLogic::createYes())]), $extVariant([$extParam('b', $string, true, false, $string, null, $allowedA), $extParam('c', $int, true, true)], true)],
		'extendedAllowed' => [$extVariant([$extParam('a', $int, false, false, null, null, $allowedA)]), $extVariant([$extParam('a', $int, false, false, null, null, $allowedB, null, \PHPStan\TrinaryLogic::createYes())])],
		'mixedKinds' => [$variant([$param('x', $int, false, false, $int, \PHPStan\Reflection\PassedByReference::createCreatesNewVariable())]), $extVariant([$extParam('y', $string, true)])],
		'template' => [$variant([$param('a', $template)], false, $template)],
		'closure' => [new \PHPStan\Type\ClosureType([$param('a', $int)], $int, false), new \PHPStan\Type\ClosureType([$param('a', $string), $param('b', $int)], $string, false)],
		'single' => [$variant([$param('a', $int)])],
	];
	$typeSets = [
		'none' => [],
		'int' => [$int],
		'string' => [new \PHPStan\Type\Constant\ConstantStringType('s')],
		'intInt' => [$int, $int],
		'three' => [$int, $int, $int],
		'named' => ['a' => $int],
		'mixedValue' => [$mixed],
	];
	$viewAcceptor = static function ($acceptor) use ($view, $viewParameter): array {
		$result = [get_class($acceptor), $view($acceptor->getReturnType()), $acceptor->isVariadic(), array_map($viewParameter, $acceptor->getParameters())];
		if ($acceptor instanceof \PHPStan\Reflection\ExtendedParametersAcceptor) {
			$result[] = [$view($acceptor->getPhpDocReturnType()), $view($acceptor->getNativeReturnType())];
		}
		if ($acceptor instanceof \PHPStan\Reflection\Callables\CallableParametersAcceptor) {
			$result[] = [count($acceptor->getThrowPoints()), $acceptor->isPure()->describe(), count($acceptor->getImpurePoints()), $acceptor->acceptsNamedArguments()->describe(), $acceptor->mustUseReturnValue()->describe(), $acceptor->isStaticClosure()->describe()];
		}
		return $result;
	};
	foreach ($acceptorSets as $setName => $acceptors) {
		$r["combineAcceptors $setName"] = $error(static function () use (&$r, $setName, $acceptors, $viewAcceptor): void {
			$r["combineAcceptors $setName result"] = $viewAcceptor(\PHPStan\Reflection\ParametersAcceptorSelector::combineAcceptors($acceptors));
		});
		foreach ([false, true] as $unpack) {
			foreach ($typeSets as $typesName => $types) {
				$key = "selectFromTypes $setName $typesName " . ($unpack ? 'unpack' : 'plain');
				$r[$key] = $error(static function () use (&$r, $key, $types, $acceptors, $unpack, $viewAcceptor): void {
					$r["$key result"] = $viewAcceptor(\PHPStan\Reflection\ParametersAcceptorSelector::selectFromTypes($types, $acceptors, $unpack));
				});
			}
		}
		foreach ($acceptors as $i => $acceptor) {
			$r["hasAcceptorTemplate $setName $i"] = [\PHPStan\Reflection\ParametersAcceptorSelector::hasAcceptorTemplateOrLateResolvableType($acceptor), \PHPStan\Reflection\ParametersAcceptorSelector::hasAcceptorTemplateOrLateResolvableParameterType($acceptor)];
		}
		$named = [new \PhpParser\Node\Arg(new \PhpParser\Node\Scalar\Int_(1), false, false, [], new \PhpParser\Node\Identifier('a'))];
		$positional = [new \PhpParser\Node\Arg(new \PhpParser\Node\Scalar\Int_(1))];
		foreach (['named' => $named, 'positional' => $positional] as $argsName => $args) {
			foreach (['withNamed' => [$acceptors[0]], 'withoutNamed' => null] as $namedName => $namedVariants) {
				$key = "combineVariantsForNormalization $setName $argsName $namedName";
				$r[$key] = $error(static function () use (&$r, $key, $args, $acceptors, $namedVariants, $viewAcceptor): void {
					$r["$key result"] = $viewAcceptor(\PHPStan\Reflection\ParametersAcceptorSelector::combineVariantsForNormalization($args, $acceptors, $namedVariants));
				});
			}
		}
	}
	$r['combineAcceptors empty'] = $error(static fn () => \PHPStan\Reflection\ParametersAcceptorSelector::combineAcceptors([]));
	$r['selectFromTypes empty'] = $error(static fn () => \PHPStan\Reflection\ParametersAcceptorSelector::selectFromTypes([], [], false));
	foreach ($r as $key => $value) {
		$observations["parameters acceptor selector $key"] = $value;
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

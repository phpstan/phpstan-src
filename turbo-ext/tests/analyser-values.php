<?php declare(strict_types = 1);

/**
 * Differential test of the native analyser value classes against their PHP
 * twins, under the prefixed activation (PHPStanTurbo\<Short> next to
 * PHPStan\Analyser\<Short>): ImpurePoint, ThrowPoint, InternalThrowPoint,
 * ArgsResult and IssetabilityDescriptor.
 *
 * Each side builds its objects from the same scopes, nodes and types and
 * every public method's answer is compared, together with the objects' state
 * read through reflection. The scopes are the PHP MutatingScope here (the
 * container runs the twins), so the native bodies take their by-name scope
 * paths; the direct entries run under the real names in walk-trace.php and
 * the test suite. A native class handed a PHP twin (a PHP ExpressionResult
 * inside a native ArgsResult, a PHP ThrowPoint to createFromPublic()) takes
 * the by-name fallback of its readers, compared too.
 *
 * Included by smoke.php (uses its check()); runnable alone too.
 */

if (!function_exists('check')) {
	['manifest' => $avManifest] = require __DIR__ . '/activate-prefixed.php';
	$avNormMap = [];
	foreach ($avManifest as $avShadowedClass => $avEntry) {
		$avNormMap[$avEntry['turboClass']] = $avShadowedClass;
	}
	$turboNorm = static fn (string $class): string => strtr($class, $avNormMap);
	$failures = 0;
	function check(bool $cond, string $msg): void
	{
		global $failures;
		if (!$cond) {
			$failures++;
			echo "FAIL: $msg\n";
		}
	}
	$avStandalone = true;
}

$avContainerFactory = new \PHPStan\DependencyInjection\ContainerFactory(dirname(__DIR__, 2));
$avContainer = $avContainerFactory->create(sys_get_temp_dir() . '/phpstan-turbo-smoke', [$avContainerFactory->getConfigDirectory() . '/config.level8.neon'], []);
$avReflectionProvider = $avContainer->getByType(\PHPStan\Reflection\ReflectionProvider::class);
$avPrecise = \PHPStan\Type\VerbosityLevel::precise();

// PHP types for the scopes and the issetability chains: the PHP twins of the
// links and resolutions type-hint the PHP TrinaryLogic the PHP types answer
// with. The throw points subtract through each side's TypeCombinator, so
// they carry the side's own Type classes (see $avThrowTypes).
$avInt = new \PHPStan\Type\IntegerType();
$avString = new \PHPStan\Type\StringType();
$avArray = new \PHPStan\Type\ArrayType($avInt, $avString);
$avThrowTypes = [
	'php' => ['throwable' => new \PHPStan\Type\ObjectType(\Throwable::class), 'exception' => new \PHPStan\Type\ObjectType(\Exception::class), 'error' => new \PHPStan\Type\ObjectType(\Error::class), 'int' => $avInt],
	'native' => ['throwable' => new \PHPStanTurbo\ObjectType(\Throwable::class), 'exception' => new \PHPStanTurbo\ObjectType(\Exception::class), 'error' => new \PHPStanTurbo\ObjectType(\Error::class), 'int' => new \PHPStanTurbo\IntegerType()],
];

$avN = [
	'a' => new \PhpParser\Node\Expr\Variable('a'),
	'm' => new \PhpParser\Node\Expr\Variable('m'),
	'arr' => new \PhpParser\Node\Expr\Variable('arr'),
	'nope' => new \PhpParser\Node\Expr\Variable('nope'),
	'k' => new \PhpParser\Node\Scalar\String_('k'),
	'one' => new \PhpParser\Node\Scalar\Int_(1),
	'call' => new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('f')),
	'stmt' => new \PhpParser\Node\Stmt\Echo_([]),
];
$avN['dim'] = new \PhpParser\Node\Expr\ArrayDimFetch($avN['arr'], $avN['k']);
$avN['dimA'] = new \PhpParser\Node\Expr\ArrayDimFetch($avN['a'], $avN['one']);
$avN['thisParent'] = new \PhpParser\Node\Expr\PropertyFetch(new \PhpParser\Node\Expr\Variable('this'), 'parent');
$avN['thisTruthy'] = new \PhpParser\Node\Expr\PropertyFetch(new \PhpParser\Node\Expr\Variable('this'), 'truthyScope');
$avN['otherParent'] = new \PhpParser\Node\Expr\PropertyFetch(new \PhpParser\Node\Expr\Variable('other'), 'parent');
$avN['exprName'] = new \PhpParser\Node\Expr\PropertyFetch(new \PhpParser\Node\Expr\Variable('this'), new \PhpParser\Node\Expr\Variable('name'));
$avN['call']->setAttribute('startLine', 7);

$avScope = $avContainer->getByType(\PHPStan\Analyser\ScopeFactory::class)->create(\PHPStan\Analyser\ScopeContext::create(__FILE__))
	->assignVariable('a', $avInt, $avInt, \PHPStan\TrinaryLogic::createYes())
	->assignVariable('m', $avString, $avString, \PHPStan\TrinaryLogic::createMaybe())
	->assignVariable('arr', $avArray, $avArray, \PHPStan\TrinaryLogic::createYes())
	->assignExpression($avN['dim'], $avString, $avString)
	->assignExpression(new \PHPStan\Node\Expr\PropertyInitializationExpr('parent'), $avInt, $avInt)
	->assignExpression($avN['thisParent'], $avInt, $avInt);
$avOtherScope = $avScope->assignVariable('a', $avString, $avString, \PHPStan\TrinaryLogic::createYes());

// real property reflections: a promoted readonly one and one with a default
$avFrameReflection = $avReflectionProvider->getClass(\PHPStan\Analyser\Generics\TemplateArgumentFrame::class)->getNativeProperty('parent');
$avResultReflection = $avReflectionProvider->getClass(\PHPStan\Analyser\ExpressionResult::class)->getNativeProperty('truthyScope');
$avFound = [
	'parent' => new \PHPStan\Rules\Properties\FoundPropertyReflection($avFrameReflection, $avScope, 'parent', $avFrameReflection->getReadableType(), $avFrameReflection->getWritableType()),
	'truthyScope' => new \PHPStan\Rules\Properties\FoundPropertyReflection($avResultReflection, $avScope, 'truthyScope', $avResultReflection->getReadableType(), $avResultReflection->getWritableType()),
];

$avKnownScopes = ['avScope' => $avScope, 'avOtherScope' => $avOtherScope];
$avDescribe = static function ($value) use (&$avDescribe, $avKnownScopes, $avN, $avPrecise, $turboNorm): mixed {
	if ($value === null || is_scalar($value)) {
		return $value;
	}
	if (is_array($value)) {
		return array_map($avDescribe, $value);
	}
	if ($value instanceof \PHPStan\Type\Type) {
		return 'type:' . $value->describe($avPrecise);
	}
	if ($value instanceof \PHPStan\Analyser\MutatingScope) {
		$known = array_search($value, $avKnownScopes, true);
		return $known !== false ? 'scope:' . $known : 'scope:' . get_class($value);
	}
	if ($value instanceof \PhpParser\Node) {
		$known = array_search($value, $avN, true);
		return $known !== false ? 'node:' . $known : 'node:' . get_class($value);
	}
	if ($value instanceof \Closure) {
		return 'closure';
	}
	if ($value instanceof \PHPStan\TrinaryLogic || $value instanceof \PHPStanTurbo\TrinaryLogic) {
		return 'trinary:' . $value->describe();
	}
	$class = $turboNorm(get_class($value));
	if (str_starts_with($class, 'PHPStan\\Analyser\\') || str_starts_with($class, 'PHPStan\\Rules\\')) {
		$d = [];
		foreach ((new \ReflectionObject($value))->getProperties() as $property) {
			if ($property->isStatic()) {
				continue;
			}
			$d[$property->getName()] = $property->isInitialized($value) ? $avDescribe($property->getValue($value)) : 'uninitialized';
		}
		return [$class, $d];
	}
	return $class;
};
// a thrown exception's class and message, the side's class names normalized
$avCatch = static function (callable $callback) use ($avDescribe, $turboNorm): mixed {
	try {
		return ['ok', $avDescribe($callback())];
	} catch (\Throwable $e) {
		return [get_class($e), $turboNorm(preg_replace('~, called in .*$~', '', $e->getMessage()))];
	}
};

$avSides = [
	'php' => ['ImpurePoint' => \PHPStan\Analyser\ImpurePoint::class, 'ThrowPoint' => \PHPStan\Analyser\ThrowPoint::class, 'InternalThrowPoint' => \PHPStan\Analyser\InternalThrowPoint::class, 'ArgsResult' => \PHPStan\Analyser\ArgsResult::class, 'IssetabilityDescriptor' => \PHPStan\Analyser\IssetabilityDescriptor::class, 'ExpressionResult' => \PHPStan\Analyser\ExpressionResult::class],
	'native' => ['ImpurePoint' => \PHPStanTurbo\ImpurePoint::class, 'ThrowPoint' => \PHPStanTurbo\ThrowPoint::class, 'InternalThrowPoint' => \PHPStanTurbo\InternalThrowPoint::class, 'ArgsResult' => \PHPStanTurbo\ArgsResult::class, 'IssetabilityDescriptor' => \PHPStanTurbo\IssetabilityDescriptor::class, 'ExpressionResult' => \PHPStanTurbo\ExpressionResult::class],
	// the native value classes over PHP twins of their collaborators: the
	// by-name fallbacks of the readers, compared with the native classes
	// over the same fixture ('native plain': a PHP ExpressionResult cannot
	// hold a native descriptor, so no result holds one)
	'native plain' => ['ImpurePoint' => \PHPStanTurbo\ImpurePoint::class, 'ThrowPoint' => \PHPStanTurbo\ThrowPoint::class, 'InternalThrowPoint' => \PHPStanTurbo\InternalThrowPoint::class, 'ArgsResult' => \PHPStanTurbo\ArgsResult::class, 'IssetabilityDescriptor' => \PHPStanTurbo\IssetabilityDescriptor::class, 'ExpressionResult' => \PHPStanTurbo\ExpressionResult::class],
	'native over PHP collaborators' => ['ImpurePoint' => \PHPStanTurbo\ImpurePoint::class, 'ThrowPoint' => \PHPStan\Analyser\ThrowPoint::class, 'InternalThrowPoint' => \PHPStanTurbo\InternalThrowPoint::class, 'ArgsResult' => \PHPStanTurbo\ArgsResult::class, 'IssetabilityDescriptor' => \PHPStanTurbo\IssetabilityDescriptor::class, 'ExpressionResult' => \PHPStan\Analyser\ExpressionResult::class],
];
$avNoExtensions = new \PHPStan\DependencyInjection\DirectExtensionsCollection([]);
// the ExpressionResult constructor's second argument
$avDefaultNarrowingHelper = $avContainer->getByType(\PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper::class);
$avResults = [];
foreach ($avSides as $side => $c) {
	$r = [];
	$types = $avThrowTypes[$side === 'php' ? 'php' : 'native'];
	$avThrowable = $types['throwable'];
	$avException = $types['exception'];
	$avError = $types['error'];
	$plain = in_array($side, ['native plain', 'native over PHP collaborators'], true);
	$newResult = static fn (\PhpParser\Node\Expr $expr, \PHPStan\Type\Type $type, bool $hasYield = false, bool $isAlwaysTerminating = false, array $throwPoints = [], array $impurePoints = [], ?object $descriptor = null) => new $c['ExpressionResult']($avNoExtensions, $avDefaultNarrowingHelper, $avScope, $avScope, $expr, $hasYield, $isAlwaysTerminating, $throwPoints, $impurePoints, null, static fn () => new \PHPStan\Analyser\SpecifiedTypes(), type: $type, nativeType: $type, issetabilityDescriptor: $plain ? null : $descriptor);

	// ---- ImpurePoint ----
	$impure = new $c['ImpurePoint']($avScope, $avN['call'], 'functionCall', 'call to function f', true);
	$r['impure'] = [$impure->getScope() === $avScope, $impure->getNode() === $avN['call'], $impure->getIdentifier(), $impure->getDescription(), $impure->isCertain(), $avDescribe($impure)];
	$named = new $c['ImpurePoint'](certain: false, description: 'd', identifier: 'echo', node: $avN['stmt'], scope: $avOtherScope);
	$r['impure named'] = $avDescribe($named);
	$r['impure uninitialized'] = $avCatch(static fn () => (new \ReflectionClass($c['ImpurePoint']))->newInstanceWithoutConstructor()->getIdentifier());
	$r['throw point private constructor'] = $avCatch(static fn () => new $c['InternalThrowPoint']($avScope, $avInt, $avN['call'], true, true));

	// ---- ThrowPoint ----
	// (a PHP ThrowPoint over the native types would subtract them through the
	// PHP TypeCombinator — the third side only hands it to createFromPublic())
	$tpExplicit = $c['ThrowPoint']::createExplicit($avScope, $avException, $avN['call'], false);
	$tpImplicit = $c['ThrowPoint']::createImplicit($avScope, $avN['call']);
	$tpImplicitTyped = $c['ThrowPoint']::createImplicit(node: $avN['stmt'], scope: $avOtherScope, type: $avError);
	foreach ($side === 'native over PHP collaborators' || $side === 'native plain' ? [] : ['explicit' => $tpExplicit, 'implicit' => $tpImplicit, 'implicit typed' => $tpImplicitTyped] as $label => $tp) {
		$r['throw point ' . $label] = [
			$avDescribe($tp->getScope()),
			$avDescribe($tp->getType()),
			$avDescribe($tp->getNode()),
			$tp->isExplicit(),
			$tp->canContainAnyThrowable(),
			$avDescribe($tp->subtractCatchType($avThrowable)),
			$avDescribe($tp->subtractCatchType($avException)),
			$avDescribe($tp),
		];
	}

	// ---- InternalThrowPoint ----
	$itpExplicit = $c['InternalThrowPoint']::createExplicit($avScope, $avException, $avN['call'], false);
	$itpExplicitAny = $c['InternalThrowPoint']::createExplicit(canContainAnyThrowable: true, node: $avN['stmt'], type: $avThrowable, scope: $avOtherScope);
	$itpImplicit = $c['InternalThrowPoint']::createImplicit($avScope, $avN['call']);
	$itpImplicitTyped = $c['InternalThrowPoint']::createImplicit($avScope, $avN['a'], $avError);
	foreach (['explicit' => $itpExplicit, 'explicit any' => $itpExplicitAny, 'implicit' => $itpImplicit, 'implicit typed' => $itpImplicitTyped] as $label => $itp) {
		$public = $itp->toPublic();
		$r['internal throw point ' . $label] = [
			$itp->getScope() === $avScope,
			$avDescribe($itp->getScope()),
			$avDescribe($itp->getType()),
			$avDescribe($itp->getNode()),
			$itp->isExplicit(),
			$itp->canContainAnyThrowable(),
			$avDescribe($itp->subtractCatchType($avThrowable)),
			$avDescribe($itp->subtractCatchType($avException)),
			$avDescribe($public),
			$public->getScope() === $itp->getScope() && $public->getNode() === $itp->getNode() && $public->getType() === $itp->getType(),
			$avDescribe($c['InternalThrowPoint']::createFromPublic($public, $avOtherScope)),
			$avDescribe($c['InternalThrowPoint']::createFromPublic($tpImplicitTyped, $avScope)),
			$avDescribe($itp),
		];
	}
	$r['internal throw point uninitialized'] = $avCatch(static fn () => (new \ReflectionClass($c['InternalThrowPoint']))->newInstanceWithoutConstructor()->toPublic());

	// ---- ArgsResult ----
	$argA = $newResult($avN['a'], $avInt);
	$argCall = $newResult($avN['call'], $avString);
	$wrapped = $newResult($avN['call'], $avString, true, false, [$itpImplicit], [$impure]);
	$args = new $c['ArgsResult']($wrapped, null, [spl_object_id($avN['a']) => $argA, spl_object_id($avN['call']) => $argCall], [spl_object_id($avN['a']) => true, spl_object_id($avN['m']) => null]);
	$argsDefault = new $c['ArgsResult'](argResults: [], resolvedParametersAcceptor: new \PHPStan\Reflection\TrivialParametersAcceptor('f'), expressionResult: $newResult($avN['one'], $avInt, false, true));
	$acceptor = new \PHPStan\Reflection\TrivialParametersAcceptor();
	$withAcceptor = $args->withResolvedParametersAcceptor($acceptor);
	$r['args result'] = [
		$args->findArgResult($avN['a']) === $argA,
		$args->findArgResult($avN['call']) === $argCall,
		$args->findArgResult($avN['m']),
		count($args->getArgResults()),
		$args->requireArgResult($avN['a']) === $argA,
		$avCatch(static fn () => $args->requireArgResult($avN['dim'])),
		$avCatch(static fn () => $args->requireArgResult($avN['call'])),
		$args->isPassedByReference($avN['a']),
		$args->isPassedByReference($avN['m']),
		$args->isPassedByReference($avN['call']),
		$args->getScope() === $avScope,
		$args->hasYield(),
		$args->isAlwaysTerminating(),
		$avDescribe($args->getThrowPoints()),
		$avDescribe($args->getImpurePoints()),
		$args->getResolvedParametersAcceptor(),
		$withAcceptor !== $args,
		$withAcceptor->getResolvedParametersAcceptor() === $acceptor,
		$args->getResolvedParametersAcceptor(),
		$withAcceptor->withResolvedParametersAcceptor(null)->getResolvedParametersAcceptor(),
		$withAcceptor->findArgResult($avN['a']) === $argA,
		$avDescribe($args),
		$argsDefault->isPassedByReference($avN['a']),
		$argsDefault->hasYield(),
		$argsDefault->isAlwaysTerminating(),
		$avDescribe($argsDefault),
	];
	$r['args result uninitialized'] = [
		$avCatch(static fn () => (new \ReflectionClass($c['ArgsResult']))->newInstanceWithoutConstructor()->getScope()),
		$avCatch(static fn () => (new \ReflectionClass($c['ArgsResult']))->newInstanceWithoutConstructor()->isPassedByReference($avN['a'])),
		$avCatch(static fn () => (new \ReflectionClass($c['ArgsResult']))->newInstanceWithoutConstructor()->findArgResult($avN['a'])),
		$avCatch(static fn () => (new \ReflectionClass($c['ArgsResult']))->newInstanceWithoutConstructor()->requireArgResult($avN['call'])),
		$avCatch(static fn () => (new \ReflectionClass($c['ArgsResult']))->newInstanceWithoutConstructor()->getArgResults()),
	];

	// ---- IssetabilityDescriptor ----
	$variableA = $c['IssetabilityDescriptor']::variable('a');
	$variableM = $c['IssetabilityDescriptor']::variable('m');
	$variableNope = $c['IssetabilityDescriptor']::variable('nope');
	$arrResult = $newResult($avN['arr'], $avArray, descriptor: $c['IssetabilityDescriptor']::variable('arr'));
	$offset = $c['IssetabilityDescriptor']::offset($arrResult, $newResult($avN['k'], new \PHPStan\Type\Constant\ConstantStringType('k')));
	$offsetUntracked = $c['IssetabilityDescriptor']::offset($newResult($avN['a'], $avArray), $newResult($avN['one'], $avInt));
	$resolverCalls = 0;
	$resolverFor = static function (string $name) use ($avFound, &$resolverCalls): \Closure {
		return static function (\PHPStan\Analyser\MutatingScope $scope) use ($avFound, $name, &$resolverCalls): ?\PHPStan\Rules\Properties\FoundPropertyReflection {
			$resolverCalls++;
			return $avFound[$name];
		};
	};
	$propertyParent = $c['IssetabilityDescriptor']::property(null, $resolverFor('parent'), $avN['thisParent']);
	$propertyTruthy = $c['IssetabilityDescriptor']::property($newResult($avN['a'], $avInt), $resolverFor('truthyScope'), $avN['thisTruthy']);
	$propertyOther = $c['IssetabilityDescriptor']::property($newResult($avN['a'], $avInt, descriptor: $variableA), $resolverFor('parent'), $avN['otherParent']);
	$propertyExprName = $c['IssetabilityDescriptor']::property(null, $resolverFor('parent'), $avN['exprName']);
	$propertyThrows = $c['IssetabilityDescriptor']::property(null, static function (\PHPStan\Analyser\MutatingScope $scope): ?\PHPStan\Rules\Properties\FoundPropertyReflection {
		throw new \RuntimeException('resolver failed');
	}, $avN['thisParent']);
	$descriptors = [
		'variable a' => [$variableA, $avN['a']],
		'variable m' => [$variableM, $avN['m']],
		'variable nope' => [$variableNope, $avN['nope']],
		'offset' => [$offset, $avN['dim']],
		'offset untracked' => [$offsetUntracked, $avN['dimA']],
		'property parent' => [$propertyParent, $avN['thisParent']],
		'property truthy' => [$propertyTruthy, $avN['thisTruthy']],
		'property other' => [$propertyOther, $avN['otherParent']],
		'property expr name' => [$propertyExprName, $avN['exprName']],
		'property throws' => [$propertyThrows, $avN['thisParent']],
	];
	foreach ($descriptors as $label => [$descriptor, $expr]) {
		$row = ['state' => $avDescribe($descriptor)];
		foreach ([[$avScope, false, false], [$avScope, true, false], [$avScope, false, true], [$avOtherScope, true, true], [$avOtherScope->doNotTreatPhpDocTypesAsCertain(), false, true]] as $i => [$scope, $native, $reprocess]) {
			$row[$i] = $avCatch(static fn () => $descriptor->resolve($scope, $native, $expr, $reprocess));
		}
		$row['default reprocess'] = $avCatch(static fn () => $descriptor->resolve(expr: $expr, useNativeTypes: false, scope: $avScope));
		$row['through the result'] = $avCatch(static fn () => $newResult($expr, $avInt, descriptor: $descriptor)->getIssetabilityResolution($avScope, false, true));
		$r['issetability ' . $label] = $row;
	}
	$r['issetability resolver calls'] = $resolverCalls;
	$r['issetability private constructor'] = $avCatch(static fn () => new $c['IssetabilityDescriptor']('variable'));
	$r['issetability uninitialized'] = $avCatch(static fn () => (new \ReflectionClass($c['IssetabilityDescriptor']))->newInstanceWithoutConstructor()->resolve($avScope, false, $avN['a']));
	$broken = (new \ReflectionClass($c['IssetabilityDescriptor']))->newInstanceWithoutConstructor();
	foreach (['kind' => 'offset', 'variableName' => null, 'varResult' => null, 'dimResult' => null, 'innerResult' => null, 'reflectionResolver' => null, 'propertyFetch' => null] as $property => $value) {
		(new \ReflectionProperty($c['IssetabilityDescriptor'], $property))->setValue($broken, $value);
	}
	$r['issetability broken offset'] = $avCatch(static fn () => $broken->resolve($avScope, false, $avN['a']));
	(new \ReflectionProperty($c['IssetabilityDescriptor'], 'kind'))->setValue($broken, 'variable');
	$r['issetability broken variable'] = $avCatch(static fn () => $broken->resolve($avScope, false, $avN['a']));
	(new \ReflectionProperty($c['IssetabilityDescriptor'], 'kind'))->setValue($broken, 'something else');
	$r['issetability broken property'] = $avCatch(static fn () => $broken->resolve($avScope, false, $avN['a']));

	$avResults[$side] = $r;
}
foreach ($avResults['php'] as $label => $described) {
	if (is_array($described) && is_array($avResults['native'][$label] ?? null) && getenv('AV_DEBUG')) {
		foreach ($described as $k => $v) {
			if ($v !== ($avResults['native'][$label][$k] ?? null)) {
				echo "DEBUG $label [$k]: " . json_encode($v) . ' vs ' . json_encode($avResults['native'][$label][$k] ?? null) . "\n";
			}
		}
	}
	check($described === ($avResults['native'][$label] ?? null), "analyser value classes parity ($label): " . json_encode($described) . ' vs ' . json_encode($avResults['native'][$label] ?? null));
}
foreach ($avResults['native over PHP collaborators'] as $label => $described) {
	check($described === ($avResults['native plain'][$label] ?? null), "analyser value classes parity over PHP collaborators ($label): " . json_encode($described) . ' vs ' . json_encode($avResults['native plain'][$label] ?? null));
}
check($avResults['php']['issetability resolver calls'] > 10, 'analyser value classes: the fixture exercises the property resolver');
check($avResults['php']['issetability property parent'][0][0] === 'ok' && $avResults['php']['issetability offset'][0][0] === 'ok', 'analyser value classes: the fixture resolves properties and offsets (' . json_encode([$avResults['php']['issetability property parent'][0], $avResults['php']['issetability offset'][0]]) . ')');

// VariableFlowBuilder reads native throw points and argument results through
// their direct entries: its answers over the native value classes equal its
// answers over the PHP twins
$avFlowThrowPoints = [];
$avFlowCall = new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('g'), [new \PhpParser\Node\Arg($avN['a']), new \PhpParser\Node\Arg($avN['m'], true), new \PhpParser\Node\Arg($avN['arr'])]);
$avFlowDescribe = static fn (?\PHPStan\Analyser\VariableFlow $flow): mixed => $flow === null ? null : $avDescribe($flow);
foreach (['php' => $avSides['php'], 'native' => $avSides['native']] as $side => $c) {
	$throwPoints = [
		$c['InternalThrowPoint']::createExplicit($avScope, $avThrowTypes[$side]['exception'], $avFlowCall, false),
		$c['InternalThrowPoint']::createImplicit($avScope, $avFlowCall),
		$c['InternalThrowPoint']::createImplicit($avScope, $avN['a']),
	];
	$storage = new \PHPStanTurbo\ExpressionResultStorage();
	$newResult = static fn (\PhpParser\Node\Expr $expr, \PHPStan\Type\Type $type, ?\PHPStan\Analyser\VariableFlow $flow = null) => new $c['ExpressionResult']($avNoExtensions, $avDefaultNarrowingHelper, $avScope, $avScope, $expr, false, false, [], [], null, static fn () => new \PHPStan\Analyser\SpecifiedTypes(), type: $type, nativeType: $type, variableFlow: $flow);
	$argsResult = new $c['ArgsResult']($newResult($avFlowCall, $avInt), null, [spl_object_id($avN['a']) => $newResult($avN['a'], $avInt, \PHPStanTurbo\VariableFlow::read('a'))], [spl_object_id($avN['arr']) => true]);
	$avFlowThrowPoints[$side] = [
		$avFlowDescribe(\PHPStanTurbo\VariableFlowBuilder::throws($avFlowCall, $throwPoints)),
		$avFlowDescribe(\PHPStanTurbo\VariableFlowBuilder::throws($avN['a'], $throwPoints)),
		$avCatch(static fn () => \PHPStanTurbo\VariableFlowBuilder::arguments($avFlowCall, $argsResult, $storage)),
	];
}
check($avFlowThrowPoints['php'] === $avFlowThrowPoints['native'], 'VariableFlowBuilder over the native value classes: ' . json_encode($avFlowThrowPoints));

if (isset($avStandalone)) {
	echo $failures === 0 ? "ALL OK\n" : "$failures FAILURE(S)\n";
	exit($failures === 0 ? 0 : 1);
}

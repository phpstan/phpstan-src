<?php declare(strict_types = 1);

/**
 * Differential test of the native analyser value classes against their PHP
 * twins, under the prefixed activation (PHPStanTurbo\<Short> next to
 * PHPStan\Analyser\<Short>): ImpurePoint, ThrowPoint, InternalThrowPoint,
 * ArgsResult, IssetabilityDescriptor, the statement results
 * (InternalStatementResult, InternalStatementExitPoint,
 * InternalEndStatementResult and their public counterparts),
 * TemplateArgumentFrame, AssignTargetWalkMode, PreparedAssignTarget and
 * RecordingNodeCallback.
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
		// a scope a side derived: its variables and its inference facts
		return $known !== false ? 'scope:' . $known : ['scope', $value->debug(), $avDescribe($value->getTemplateArgumentConstraints())];
	}
	if ($value instanceof \PhpParser\Node) {
		$known = array_search($value, $avN, true);
		if ($known !== false) {
			return 'node:' . $known;
		}
		// a node a side built
		$subNodes = [];
		foreach ($value->getSubNodeNames() as $name) {
			$subNodes[$name] = $avDescribe($value->$name);
		}
		return ['node', get_class($value), $subNodes];
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
// a thrown exception's class and message, the side's class names normalized,
// and the warnings raised on the way
$avCatch = static function (callable $callback) use ($avDescribe, $turboNorm): mixed {
	$warnings = [];
	set_error_handler(static function (int $level, string $message) use (&$warnings, $turboNorm): bool {
		$warnings[] = [$level, $turboNorm($message)];
		return true;
	});
	try {
		$outcome = ['ok', $avDescribe($callback())];
	} catch (\Throwable $e) {
		$outcome = [get_class($e), $turboNorm(preg_replace('~, called in .*$~', '', $e->getMessage()))];
	} finally {
		restore_error_handler();
	}
	if ($warnings !== []) {
		$outcome[] = $warnings;
	}
	return $outcome;
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

// ---- the statement results ----
// Exit points leaving the loop at every depth, scopes carrying inference
// facts (the constructor joins them into the result's scope), end
// statements, and keyed throw points (toPublic() preserves the keys).
$avFacts = \Closure::bind(static fn (string $name) => new \PHPStan\Analyser\Generics\TemplateArgumentConstraints(null, null, [$name, null, null, true]), null, \PHPStan\Analyser\Generics\TemplateArgumentConstraints::class);
$avFactScopes = [
	'f1' => $avScope->withTemplateArgumentConstraints($avFacts('f1')),
	'f2' => $avOtherScope->withTemplateArgumentConstraints($avFacts('f2')),
	'f3' => $avScope->assignVariable('c', $avInt, $avInt, \PHPStan\TrinaryLogic::createYes())->withTemplateArgumentConstraints($avFacts('f3')),
];
$avKnownScopes += $avFactScopes;
$avStmt = [
	'return' => new \PhpParser\Node\Stmt\Return_(),
	'break' => new \PhpParser\Node\Stmt\Break_(),
	'break1' => new \PhpParser\Node\Stmt\Break_(new \PhpParser\Node\Scalar\Int_(1)),
	'break2' => new \PhpParser\Node\Stmt\Break_(new \PhpParser\Node\Scalar\Int_(2)),
	'break3' => new \PhpParser\Node\Stmt\Break_(new \PhpParser\Node\Scalar\Int_(3)),
	'breakVar' => new \PhpParser\Node\Stmt\Break_(new \PhpParser\Node\Expr\Variable('n')),
	'continue' => new \PhpParser\Node\Stmt\Continue_(),
	'continue1' => new \PhpParser\Node\Stmt\Continue_(new \PhpParser\Node\Scalar\Int_(1)),
	'continue2' => new \PhpParser\Node\Stmt\Continue_(new \PhpParser\Node\Scalar\Int_(2)),
	'continue4' => new \PhpParser\Node\Stmt\Continue_(new \PhpParser\Node\Scalar\Int_(4)),
	'continueVar' => new \PhpParser\Node\Stmt\Continue_(new \PhpParser\Node\Expr\Variable('n')),
];
$avN += $avStmt;
$avStatementSides = [
	'php' => ['InternalStatementResult' => \PHPStan\Analyser\InternalStatementResult::class, 'InternalStatementExitPoint' => \PHPStan\Analyser\InternalStatementExitPoint::class, 'InternalEndStatementResult' => \PHPStan\Analyser\InternalEndStatementResult::class, 'StatementResult' => \PHPStan\Analyser\StatementResult::class, 'StatementExitPoint' => \PHPStan\Analyser\StatementExitPoint::class, 'EndStatementResult' => \PHPStan\Analyser\EndStatementResult::class, 'InternalThrowPoint' => \PHPStan\Analyser\InternalThrowPoint::class, 'ImpurePoint' => \PHPStan\Analyser\ImpurePoint::class, 'inner' => \PHPStan\Analyser\InternalStatementResult::class],
	'native' => ['InternalStatementResult' => \PHPStanTurbo\InternalStatementResult::class, 'InternalStatementExitPoint' => \PHPStanTurbo\InternalStatementExitPoint::class, 'InternalEndStatementResult' => \PHPStanTurbo\InternalEndStatementResult::class, 'StatementResult' => \PHPStanTurbo\StatementResult::class, 'StatementExitPoint' => \PHPStanTurbo\StatementExitPoint::class, 'EndStatementResult' => \PHPStanTurbo\EndStatementResult::class, 'InternalThrowPoint' => \PHPStanTurbo\InternalThrowPoint::class, 'ImpurePoint' => \PHPStanTurbo\ImpurePoint::class, 'inner' => \PHPStanTurbo\InternalStatementResult::class],
	// a native result over the PHP twins of the exit points, end statements
	// (holding PHP results) and throw points: the readers' by-name fallbacks
	'native over PHP collaborators' => ['InternalStatementResult' => \PHPStanTurbo\InternalStatementResult::class, 'InternalStatementExitPoint' => \PHPStan\Analyser\InternalStatementExitPoint::class, 'InternalEndStatementResult' => \PHPStan\Analyser\InternalEndStatementResult::class, 'StatementResult' => \PHPStanTurbo\StatementResult::class, 'StatementExitPoint' => \PHPStan\Analyser\StatementExitPoint::class, 'EndStatementResult' => \PHPStanTurbo\EndStatementResult::class, 'InternalThrowPoint' => \PHPStan\Analyser\InternalThrowPoint::class, 'ImpurePoint' => \PHPStan\Analyser\ImpurePoint::class, 'inner' => \PHPStan\Analyser\InternalStatementResult::class],
];
$avStatementResults = [];
foreach ($avStatementSides as $side => $c) {
	$r = [];
	$types = $avThrowTypes[$side === 'php' ? 'php' : 'native'];
	$flow = \PHPStan\Analyser\VariableFlow::read('a');
	$exit = static fn (string $stmt, string $scope) => new $c['InternalStatementExitPoint']($avStmt[$stmt], $avKnownScopes[$scope]);
	$exitPoint = $exit('return', 'f1');
	$r['exit point'] = [$exitPoint->getStatement() === $avStmt['return'], $exitPoint->getScope() === $avFactScopes['f1'], $avDescribe($exitPoint->toPublic()), $avDescribe($exitPoint)];
	$r['exit point uninitialized'] = $avCatch(static fn () => (new \ReflectionClass($c['InternalStatementExitPoint']))->newInstanceWithoutConstructor()->toPublic());

	$inner = new $c['inner']($avFactScopes['f3'], true, true, [$exit('return', 'avScope')], [], [], variableFlow: $flow);
	$endStatement = new $c['InternalEndStatementResult']($avStmt['return'], $inner);
	$r['end statement'] = [$endStatement->getStatement() === $avStmt['return'], $endStatement->getResult() === $inner, $avDescribe($endStatement->toPublic()), $avDescribe($endStatement)];
	$r['end statement uninitialized'] = $avCatch(static fn () => (new \ReflectionClass($c['InternalEndStatementResult']))->newInstanceWithoutConstructor()->toPublic());

	$throwPoints = ['x' => $c['InternalThrowPoint']::createImplicit($avScope, $avN['call']), 5 => $c['InternalThrowPoint']::createExplicit($avScope, $types['exception'], $avN['call'], false)];
	$impurePoints = [new $c['ImpurePoint']($avScope, $avN['call'], 'functionCall', 'f', true)];
	$exitSets = [
		'none' => [],
		'return' => [$exit('return', 'f1')],
		'break' => [$exit('return', 'avScope'), $exit('break', 'f1')],
		'break1' => [$exit('break1', 'f2'), $exit('return', 'f1')],
		'break2' => [$exit('break2', 'f1'), $exit('break3', 'f2')],
		'breakVar' => [$exit('return', 'avScope'), $exit('breakVar', 'f2')],
		'continue' => [$exit('continue', 'f1'), $exit('continue2', 'f2'), $exit('continue1', 'f3')],
		'continue deep' => [$exit('continue4', 'avScope'), $exit('continueVar', 'f1'), $exit('break2', 'f3')],
		'keyed' => ['a' => $exit('continue', 'f2'), 7 => $exit('return', 'f3')],
	];
	foreach ($exitSets as $setLabel => $exitPoints) {
		foreach ([[false, null], [true, null], [true, true], [false, false]] as [$terminating, $endReachable]) {
			foreach ([false, true] as $withEnds) {
				$label = sprintf('result %s terminating=%s endReachable=%s ends=%s', $setLabel, var_export($terminating, true), var_export($endReachable, true), var_export($withEnds, true));
				$r[$label] = $avCatch(static function () use ($c, $avScope, $terminating, $endReachable, $withEnds, $exitPoints, $throwPoints, $impurePoints, $endStatement, $flow, $avDescribe, $avStmt, $avCatch): array {
					$result = new $c['InternalStatementResult']($avScope, $withEnds, $terminating, $exitPoints, $throwPoints, $impurePoints, $withEnds ? [$endStatement, 'e' => $endStatement] : [], $withEnds ? null : $flow, $endReachable);
					$filtered = $result->filterOutLoopExitPoints();
					return [
						'scope' => $avDescribe($result->getScope()),
						'hasYield' => $result->hasYield(),
						'isAlwaysTerminating' => $result->isAlwaysTerminating(),
						'isEndReachable' => $result->isEndReachable(),
						'variableFlow' => $result->getVariableFlow() === $flow,
						'exitPoints' => $result->getExitPoints() === $exitPoints,
						'throwPoints' => $result->getThrowPoints() === $throwPoints,
						'impurePoints' => $result->getImpurePoints() === $impurePoints,
						'endStatements' => count($result->getEndStatements()),
						'continue' => $avDescribe($result->getExitPointsByType(\PhpParser\Node\Stmt\Continue_::class)),
						'break' => $avDescribe($result->getExitPointsByType(\PhpParser\Node\Stmt\Break_::class)),
						'return' => $avDescribe($result->getExitPointsByType(\PhpParser\Node\Stmt\Return_::class)),
						'undeclared' => $avDescribe($result->getExitPointsByType('AnalyserValuesNoSuchStatement')),
						'outer' => $avDescribe($result->getExitPointsForOuterLoop()),
						'back edge' => $avCatch(static fn () => $result->getLoopBackEdgeScope()),
						'filtered same' => $filtered === $result,
						'filtered' => $avDescribe($filtered),
						'filtered back edge' => $avCatch(static fn () => $filtered->getLoopBackEdgeScope()),
						'public' => $avDescribe($result->toPublic()),
						'public filtered' => $avDescribe($result->toPublic()->filterOutLoopExitPoints()),
						'public outer' => $avDescribe($result->toPublic()->getExitPointsForOuterLoop()),
						'public continue' => $avDescribe($result->toPublic()->getExitPointsByType(\PhpParser\Node\Stmt\Continue_::class)),
						'state' => $avDescribe($result),
					];
				});
			}
		}
	}
	$r['result named'] = $avDescribe(new $c['InternalStatementResult'](endReachable: true, impurePoints: [], throwPoints: [], exitPoints: [$exit('continue', 'f2')], isAlwaysTerminating: true, hasYield: false, scope: $avFactScopes['f1']));
	$r['result non-object exit point'] = $avCatch(static fn () => new $c['InternalStatementResult']($avScope, false, false, [1], [], []));
	$r['result non-object end statement'] = $avCatch(static fn () => new $c['InternalStatementResult']($avScope, false, false, [], [], [], ['x']));
	$r['result non-object throw point'] = $avCatch(static fn () => (new $c['InternalStatementResult']($avScope, false, false, [], [null], []))->toPublic());
	$r['result uninitialized'] = [
		$avCatch(static fn () => (new \ReflectionClass($c['InternalStatementResult']))->newInstanceWithoutConstructor()->toPublic()),
		$avCatch(static fn () => (new \ReflectionClass($c['InternalStatementResult']))->newInstanceWithoutConstructor()->getLoopBackEdgeScope()),
		$avCatch(static fn () => (new \ReflectionClass($c['InternalStatementResult']))->newInstanceWithoutConstructor()->filterOutLoopExitPoints()),
		$avCatch(static fn () => (new \ReflectionClass($c['InternalStatementResult']))->newInstanceWithoutConstructor()->getExitPointsByType('x')),
	];

	// the public classes directly
	$publicExit = new $c['StatementExitPoint']($avStmt['break2'], $avScope);
	$publicResult = new $c['StatementResult']($avOtherScope, true, true, [$publicExit, 'k' => new $c['StatementExitPoint']($avStmt['continueVar'], $avScope)], [], []);
	$r['public result'] = [
		$publicExit->getStatement() === $avStmt['break2'],
		$publicExit->getScope() === $avScope,
		$avDescribe($publicResult),
		$avDescribe($publicResult->filterOutLoopExitPoints()),
		$publicResult->filterOutLoopExitPoints() === $publicResult,
		$avDescribe($publicResult->getExitPointsForOuterLoop()),
		$avDescribe($publicResult->getExitPointsByType(\PhpParser\Node\Stmt\Continue_::class)),
		$avDescribe((new $c['EndStatementResult']($avStmt['return'], $publicResult))->getResult()),
		$avCatch(static fn () => (new \ReflectionClass($c['StatementResult']))->newInstanceWithoutConstructor()->filterOutLoopExitPoints()),
		$avCatch(static fn () => (new \ReflectionClass($c['EndStatementResult']))->newInstanceWithoutConstructor()->getResult()),
		$avCatch(static fn () => (new \ReflectionClass($c['StatementExitPoint']))->newInstanceWithoutConstructor()->getScope()),
	];
	$avStatementResults[$side] = $r;
}
foreach (['native', 'native over PHP collaborators'] as $side) {
	foreach ($avStatementResults['php'] as $label => $described) {
		check($described === ($avStatementResults[$side][$label] ?? null), "statement results parity, $side ($label): " . json_encode($described) . ' vs ' . json_encode($avStatementResults[$side][$label] ?? null));
	}
}
check(count(array_filter($avStatementResults['php'], static fn ($row) => is_array($row) && ($row[0] ?? null) === 'ok')) >= 70, 'statement results: the fixture constructs the result matrix');

// ---- TemplateArgumentFrame, AssignTargetWalkMode, PreparedAssignTarget ----
// Frames resolve through their parents by the site's object id; the
// unconstrained resolution maps a template bound through the traverser (PHP
// template types on both sides — the resolver callback is what differs).
// returnTypeOfCall() runs on the PHP scope with a PHP frame installed (the
// PHP scope type-hints the PHP frame) against a recording acceptor.
$avAcceptor = new class implements \PHPStan\Reflection\ResolvedFunctionVariant {

	public function getOriginalParametersAcceptor(): \PHPStan\Reflection\ParametersAcceptor
	{
		return $this;
	}

	public function getReturnTypeWithUnresolvableTemplateTypes(): \PHPStan\Type\Type
	{
		return new \PHPStan\Type\MixedType();
	}

	public function getReturnTypeWithUnresolvedTemplateArguments(\PhpParser\Node\Expr $site, \PHPStan\Analyser\Generics\TemplateArgumentFrame $frame, bool $allowUnresolved): \PHPStan\Type\Type
	{
		return new \PHPStan\Type\Constant\ConstantStringType(json_encode([get_class($site), $site->getAttribute('label'), spl_object_id($frame), $allowUnresolved]));
	}

	public function getTemplateTypeMap(): \PHPStan\Type\Generic\TemplateTypeMap
	{
		return \PHPStan\Type\Generic\TemplateTypeMap::createEmpty();
	}

	public function getResolvedTemplateTypeMap(): \PHPStan\Type\Generic\TemplateTypeMap
	{
		return \PHPStan\Type\Generic\TemplateTypeMap::createEmpty();
	}

	public function getCallSiteVarianceMap(): \PHPStan\Type\Generic\TemplateTypeVarianceMap
	{
		return \PHPStan\Type\Generic\TemplateTypeVarianceMap::createEmpty();
	}

	public function getParameters(): array
	{
		return [];
	}

	public function isVariadic(): bool
	{
		return false;
	}

	public function getReturnType(): \PHPStan\Type\Type
	{
		return new \PHPStan\Type\Constant\ConstantStringType('plain return type');
	}

	public function getPhpDocReturnType(): \PHPStan\Type\Type
	{
		return new \PHPStan\Type\MixedType();
	}

	public function getNativeReturnType(): \PHPStan\Type\Type
	{
		return new \PHPStan\Type\MixedType();
	}

	public function hasBoundArgs(): bool
	{
		return false;
	}

	public function resolveConditionalTypes(\PHPStan\Type\Type $type): \PHPStan\Type\Type
	{
		return $type;
	}

};
$avTemplateScope = \PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('f');
$avOtherTemplateScope = \PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('g');
$avInvariant = \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant();
$avTemplates = [
	'U' => \PHPStan\Type\Generic\TemplateTypeFactory::create($avTemplateScope, 'U', null, $avInvariant),
	'V' => \PHPStan\Type\Generic\TemplateTypeFactory::create($avTemplateScope, 'V', null, $avInvariant, null, new \PHPStan\Type\StringType()),
	'W' => \PHPStan\Type\Generic\TemplateTypeFactory::create($avOtherTemplateScope, 'W', null, $avInvariant),
];
$avTemplates['defaulted'] = \PHPStan\Type\Generic\TemplateTypeFactory::create($avTemplateScope, 'D', $avInt, $avInvariant, null, $avString);
$avTemplates['plain bound'] = \PHPStan\Type\Generic\TemplateTypeFactory::create($avTemplateScope, 'P', $avInt, $avInvariant);
$avTemplates['bound U'] = \PHPStan\Type\Generic\TemplateTypeFactory::create($avTemplateScope, 'T', new \PHPStan\Type\ArrayType($avInt, $avTemplates['U']), $avInvariant);
$avTemplates['bound V W'] = \PHPStan\Type\Generic\TemplateTypeFactory::create($avTemplateScope, 'T2', new \PHPStan\Type\UnionType([new \PHPStan\Type\ArrayType($avInt, $avTemplates['V']), new \PHPStan\Type\ArrayType($avString, $avTemplates['W'])]), $avInvariant);
$avSites = [
	'site' => new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('f')),
	'other site' => new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('g')),
];
$avSites['with original'] = new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('h'), [], ['templateArgumentOriginalSite' => $avSites['site']]);
$avSites['with non-expr original'] = new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('i'), [], ['templateArgumentOriginalSite' => new \PhpParser\Node\Name('x')]);
foreach ($avSites as $label => $site) {
	$site->setAttribute('label', $label);
}
$avFramedScope = $avScope->withTemplateArgumentFrame(new \PHPStan\Analyser\Generics\TemplateArgumentFrame(null, []));
$avFrameResults = [];
foreach (['php' => [\PHPStan\Analyser\Generics\TemplateArgumentFrame::class, \PHPStan\Analyser\AssignTargetWalkMode::class, \PHPStan\Analyser\PreparedAssignTarget::class], 'native' => [\PHPStanTurbo\TemplateArgumentFrame::class, \PHPStanTurbo\AssignTargetWalkMode::class, \PHPStanTurbo\PreparedAssignTarget::class]] as $side => [$frameClass, $modeClass, $targetClass]) {
	$r = [];
	$id = static fn (string $site): int => spl_object_id($avSites[$site]);
	$root = new $frameClass(null, [$id('site') . '#U' => $avString, $id('other site') . '#T' => $avInt, $id('site') . '#N' => null], [3 => true, 1 => true, 7 => true]);
	$observing = new $frameClass($root);
	$observingTop = new $frameClass(null);
	$leaf = new $frameClass(siteStatementIndexes: [5 => true, 'x' => true, 2 => true], resolutions: [$id('site') . '#V' => $avArray], parent: $observing);
	$frames = ['root' => $root, 'observing' => $observing, 'observing top' => $observingTop, 'leaf' => $leaf];
	$frameLabel = static function (string $suffix) use ($frames): string {
		foreach ($frames as $label => $frame) {
			if ($suffix === '|templateArguments:' . spl_object_id($frame)) {
				return 'suffix of ' . $label;
			}
		}
		return $suffix;
	};
	foreach ($frames as $label => $frame) {
		$row = [
			'observing' => $frame->isObserving(),
			'first' => $frame->firstSiteStatementIndex(),
			'owns' => [$frame->ownsSiteInStatement(1), $frame->ownsSiteInStatement(2), $frame->ownsSiteInStatement(4)],
			'at or after' => [$frame->hasSiteAtOrAfter(0), $frame->hasSiteAtOrAfter(6), $frame->hasSiteAtOrAfter(8)],
			'suffix' => $frameLabel($frame->getResolutionCacheKeySuffix()),
		];
		foreach ($avSites as $siteLabel => $site) {
			foreach (['U', 'T', 'V', 'N', 'Z'] as $name) {
				$row['resolve ' . $siteLabel . ' ' . $name] = $avDescribe($frame->resolve($site, $name));
			}
			foreach ($avTemplates as $templateLabel => $template) {
				$row['or unconstrained ' . $siteLabel . ' ' . $templateLabel] = $avCatch(static fn () => $frame->resolveOrUnconstrained($site, $template));
			}
		}
		$r['frame ' . $label] = $row;
	}
	foreach ($avTemplates as $templateLabel => $template) {
		$r['unconstrained ' . $templateLabel] = [
			$avCatch(static fn () => $frameClass::resolveUnconstrained($avSites['site'], $template, static fn (\PhpParser\Node\Expr $site, string $name): ?\PHPStan\Type\Type => $name === 'U' ? $avInt : null)),
			$avCatch(static fn () => $frameClass::resolveUnconstrained($avSites['site'], $template, static fn (\PhpParser\Node\Expr $site, string $name): ?\PHPStan\Type\Type => throw new \RuntimeException('resolver ' . $name))),
		];
	}
	foreach (['no frame' => $avScope, 'frame' => $avFramedScope, 'frame promoted' => $avFramedScope->doNotTreatPhpDocTypesAsCertain()] as $scopeLabel => $scope) {
		foreach ($avSites as $siteLabel => $site) {
			foreach ([null, true, false] as $allow) {
				$r['return type ' . $scopeLabel . ' ' . $siteLabel . ' ' . var_export($allow, true)] = [
					$avCatch(static fn () => $frameClass::returnTypeOfCall($avAcceptor, $scope, $site, $allow)),
					$avCatch(static fn () => $frameClass::returnTypeOfCall(new \PHPStan\Reflection\TrivialParametersAcceptor(), $scope, $site, $allow)),
				];
			}
		}
	}
	$r['frame constants'] = [$frameClass::SYNTHETIC_SITE_ATTRIBUTE, $frameClass::ORIGINAL_SITE_ATTRIBUTE];
	$r['frame uninitialized'] = [
		$avCatch(static fn () => (new \ReflectionClass($frameClass))->newInstanceWithoutConstructor()->isObserving()),
		$avCatch(static fn () => (new \ReflectionClass($frameClass))->newInstanceWithoutConstructor()->ownsSiteInStatement(1)),
		$avCatch(static fn () => (new \ReflectionClass($frameClass))->newInstanceWithoutConstructor()->resolve($avSites['site'], 'U')),
		$avCatch(static fn () => (new \ReflectionClass($frameClass))->newInstanceWithoutConstructor()->getResolutionCacheKeySuffix()),
		$avCatch(static function () use ($frameClass) {
			$frame = new $frameClass(null);
			$frame->__construct(null);
			return $frame;
		}),
	];

	// AssignTargetWalkMode: fresh instances with the four flag sets
	foreach (['assign', 'virtualAssign', 'readModifyWrite', 'coalesceReadModifyWrite'] as $factory) {
		$mode = $modeClass::$factory();
		$r['mode ' . $factory] = [$mode->enterExpressionAssign(), $mode->producesTargetReadResult(), $mode->issetSemanticsForRead(), $mode !== $modeClass::$factory(), $avDescribe($mode)];
	}
	$r['mode private constructor'] = $avCatch(static fn () => new $modeClass(true, true, true));

	// PreparedAssignTarget: the required arguments only, then every one
	$getters = ['getKind', 'getVar', 'getAssignedExpr', 'getBeforeScope', 'getScope', 'enterExpressionAssign', 'isAssignOp', 'hasYield', 'getThrowPoints', 'getImpurePoints', 'isAlwaysTerminating', 'getRootVar', 'getVarResult', 'getDimFetchStack', 'getAssignedPropertyExpr', 'getOffsetTypes', 'getOffsetNativeTypes', 'getExistingOffsetTypes', 'getExistingOffsetNativeTypes', 'getOffsetSetTargetResult', 'getObjectResult', 'getPropertyName', 'getPropertyHolderType', 'getTargetReadResult', 'getTargetChainResults', 'getVariableNameResult'];
	$minimal = new $targetClass($targetClass::KIND_VARIABLE, $avN['a'], $avN['call'], $avScope, $avOtherScope, true, false, true, ['t'], ['i'], false);
	$someResult = $newResult($avN['a'], $avInt);
	$full = new $targetClass(
		$targetClass::KIND_ARRAY_DIM_FETCH, $avN['dim'], $avN['one'], $avOtherScope, $avScope, false, true, false, [], [], true,
		$avN['arr'], $someResult, [$avN['dim']], $avN['a'], [[null, $avN['dim']]], [[$avInt, $avN['dim']]], [[$avString, $avN['dim']]], [[$avArray, $avN['dim']]],
		$someResult, $someResult, 'prop', $avString, $someResult, [$someResult], $someResult,
	);
	$named = new $targetClass(kind: $targetClass::KIND_PROPERTY_FETCH, var: $avN['thisParent'], assignedExpr: $avN['a'], beforeScope: $avScope, scope: $avScope, enterExpressionAssign: false, isAssignOp: false, hasYield: false, throwPoints: [], impurePoints: [], isAlwaysTerminating: false, propertyName: null, objectResult: $someResult, targetChainResults: [$someResult]);
	foreach (['minimal' => $minimal, 'full' => $full, 'named' => $named] as $label => $target) {
		$row = [];
		foreach ($getters as $getter) {
			$row[$getter] = $avCatch(static fn () => $target->$getter());
		}
		$row['state'] = $avDescribe($target);
		$r['target ' . $label] = $row;
	}
	$r['target constants'] = [$targetClass::KIND_VARIABLE, $targetClass::KIND_ARRAY_DIM_FETCH, $targetClass::KIND_PROPERTY_FETCH, $targetClass::KIND_STATIC_PROPERTY_FETCH, $targetClass::KIND_LIST, $targetClass::KIND_EXISTING_ARRAY_DIM_FETCH, $targetClass::KIND_FALLBACK];
	$r['target uninitialized'] = $avCatch(static fn () => (new \ReflectionClass($targetClass))->newInstanceWithoutConstructor()->getRootVar());
	$avFrameResults[$side] = $r;
}
foreach ($avFrameResults['php'] as $label => $described) {
	check($described === ($avFrameResults['native'][$label] ?? null), "frame / walk mode / assign target parity ($label): " . json_encode($described) . ' vs ' . json_encode($avFrameResults['native'][$label] ?? null));
}
check(!str_contains(json_encode($avFrameResults['php']['return type frame with original true']), 'with original') && str_contains(json_encode($avFrameResults['php']['return type frame with original true']), 'site'), 'TemplateArgumentFrame: the fixture passes the original site');
check(str_contains(json_encode($avFrameResults['php']['frame leaf']['or unconstrained site bound U']), 'array<int, string>'), 'TemplateArgumentFrame: the fixture resolves a bound through the frames (' . json_encode($avFrameResults['php']['frame leaf']['or unconstrained site bound U']) . ')');

// ---- RecordingNodeCallback ----
// Invoked the ways PHP invokes a callable object, and through a native
// invoker of node callbacks (the native ClassStatementsGatherer forwards
// each pair to its wrapped callback before it gathers; outside a class it
// then throws). getPairs() hands out a copy the later recording must not
// change.
$avGathererClass = $avReflectionProvider->getClass(\PHPStan\Analyser\ArgsResult::class);
$avRecordings = [];
foreach (['php' => \PHPStan\Analyser\RecordingNodeCallback::class, 'native' => \PHPStanTurbo\RecordingNodeCallback::class] as $side => $recordingClass) {
	$r = [];
	$recording = new $recordingClass();
	$r['empty'] = [$recording->count(), $recording->getPairs(), is_callable($recording)];
	$recording($avN['a'], $avScope);
	$before = $recording->getPairs();
	call_user_func($recording, $avN['call'], $avOtherScope);
	call_user_func_array($recording, [$avN['m'], $avScope]);
	\Closure::fromCallable($recording)($avN['arr'], $avOtherScope);
	$recording->__invoke(node: $avN['k'], scope: $avScope);
	// an internal function words the argument count error differently
	$r['too few arguments'] = $avCatch(static fn () => $recording($avN['one']))[0];
	foreach (['native gatherer' => \PHPStanTurbo\ClassStatementsGatherer::class, 'php gatherer' => \PHPStan\Node\ClassStatementsGatherer::class] as $gathererLabel => $gathererClass) {
		$gatherer = new $gathererClass($avGathererClass, $recording);
		$r['through ' . $gathererLabel] = $avCatch(static fn () => $gatherer($avN['dim'], $avScope));
	}
	$r['pairs'] = array_map(static fn (array $pair): array => [$avDescribe($pair[0]), $avDescribe($pair[1]), array_keys($pair)], $recording->getPairs());
	$r['count'] = $recording->count();
	$r['copy kept'] = count($before);
	$r['state'] = $avDescribe($recording);
	$uninitialized = (new \ReflectionClass($recordingClass))->newInstanceWithoutConstructor();
	(new \ReflectionProperty($recordingClass, 'pairs'))->setValue($uninitialized, []);
	$r['reinitialized'] = [$avCatch(static fn () => $uninitialized($avN['a'], $avScope)), $uninitialized->count()];
	$avRecordings[$side] = $r;
}
check($avRecordings['php'] === $avRecordings['native'], 'RecordingNodeCallback parity: ' . json_encode($avRecordings['php']) . ' vs ' . json_encode($avRecordings['native']));
check($avRecordings['php']['count'] === 7, 'RecordingNodeCallback: the fixture records through every invocation (' . $avRecordings['php']['count'] . ')');

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

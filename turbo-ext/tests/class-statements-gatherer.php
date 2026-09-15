<?php declare(strict_types = 1);

/**
 * Differential test of the native ClassStatementsGatherer against the PHP
 * twin, under the prefixed activation (PHPStanTurbo\ClassStatementsGatherer
 * next to PHPStan\Node\ClassStatementsGatherer).
 *
 * A real NodeScopeResolver walk over class-statements-gatherer-fixture.php
 * records every (node, scope) pair the node callback sees. Inside a class the
 * callback is reached through ClassLikeHandler's gatherer, which forwards the
 * exact pair it was handed, so the recorded in-class pairs are the gatherers'
 * input stream. The stream is replayed, once per class reflection seen, into
 * a PHP and a native gatherer, and compared: the pairs forwarded to the inner
 * callback and everything the eight getters expose. AST nodes and scopes
 * compare by identity; the fetch nodes a gatherer builds itself compare by
 * structure (class, subnodes, attributes), the value objects wrapping them by
 * their properties.
 *
 * Under the prefix the scope and the class reflection are the PHP twins, so
 * the native body takes its by-name paths here; the direct entries are
 * exercised by the full test suite and the output identity check.
 *
 * Included by smoke.php (uses its check()); runnable alone too.
 */

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
	$csgStandalone = true;
}

$csgFile = __DIR__ . '/class-statements-gatherer-fixture.php';
$csgContainerFactory = new \PHPStan\DependencyInjection\ContainerFactory(dirname(__DIR__, 2));
$csgContainer = $csgContainerFactory->create(sys_get_temp_dir() . '/phpstan-turbo-smoke-csg', [$csgContainerFactory->getConfigDirectory() . '/config.level8.neon', ...(PHP_VERSION_ID < 80400 ? [__DIR__ . '/php84-syntax.neon'] : [])], [$csgFile]);

// the analysed-file checks compare normalized paths (backslashes on Windows)
$csgFile = $csgContainer->getByType(\PHPStan\File\FileHelper::class)->normalizePath($csgFile);

$csgResolver = $csgContainer->getByType(\PHPStan\Analyser\NodeScopeResolver::class);
$csgResolver->setAnalysedFiles([$csgFile]);
$csgResolver->resetPerFileAnalysisState();
// an analysed file for the path-routing parser too, or it parses the fixture
// as a dependency and strips the method bodies down to their closures
$csgContainer->getService('pathRoutingParser')->setAnalysedFiles([$csgFile]);
$csgAst = $csgContainer->getService('defaultAnalysisParser')->parseFile($csgFile);

/** @var list<array{\PhpParser\Node, \PHPStan\Analyser\Scope}> $csgPairs */
$csgPairs = [];
/** @var array<string, \PHPStan\Reflection\ClassReflection> $csgClasses */
$csgClasses = [];
$csgOutside = null;
$csgCallback = static function (\PhpParser\Node $node, \PHPStan\Analyser\Scope $scope) use (&$csgPairs, &$csgClasses, &$csgOutside): void {
	if (!$scope->isInClass()) {
		$csgOutside ??= [$node, $scope];
		return;
	}
	$csgPairs[] = [$node, $scope];
	$classReflection = $scope->getClassReflection();
	$csgClasses[$classReflection->getName()] = $classReflection;
};
$csgResolver->processNodes(
	$csgAst,
	$csgContainer->getByType(\PHPStan\Analyser\ScopeFactory::class)->create(\PHPStan\Analyser\ScopeContext::create($csgFile), $csgCallback),
	$csgCallback,
);
check(count($csgClasses) >= 3, 'ClassStatementsGatherer: the fixture walk entered the fixture classes (' . implode(', ', array_keys($csgClasses)) . ')');
check(count($csgPairs) >= 100, 'ClassStatementsGatherer: the fixture walk recorded enough in-class pairs (' . count($csgPairs) . ')');
check($csgOutside !== null, 'ClassStatementsGatherer: the fixture walk recorded a pair outside any class');

// every AST node and every emitted node is identity-compared; anything else
// is a node a gatherer built
$csgKnown = [];
foreach ((new \PhpParser\NodeFinder())->find($csgAst, static fn (): bool => true) as $csgNode) {
	$csgKnown[spl_object_id($csgNode)] = true;
}
foreach ($csgPairs as [$csgNode]) {
	$csgKnown[spl_object_id($csgNode)] = true;
}

$csgDescribe = static function (mixed $value) use (&$csgDescribe, $csgKnown): mixed {
	if (is_array($value)) {
		return array_map($csgDescribe, $value);
	}
	if (!is_object($value)) {
		return $value;
	}
	if ($value instanceof \PHPStan\Analyser\Scope) {
		return ['scope', spl_object_id($value)];
	}
	if ($value instanceof \PhpParser\Node) {
		if (isset($csgKnown[spl_object_id($value)])) {
			return ['node', spl_object_id($value)];
		}
		$subNodes = [];
		foreach ($value->getSubNodeNames() as $name) {
			$subNodes[$name] = $csgDescribe($value->$name);
		}
		return ['built', get_class($value), $subNodes, $csgDescribe($value->getAttributes())];
	}
	if (str_starts_with(get_class($value), 'PHPStan\\Node\\')) {
		$properties = [];
		foreach ((new \ReflectionObject($value))->getProperties() as $property) {
			$properties[$property->getName()] = $csgDescribe($property->getValue($value));
		}
		return [get_class($value), $properties];
	}
	return [get_class($value), spl_object_id($value)];
};

$csgGetters = ['getProperties', 'getMethods', 'getMethodCalls', 'getPropertyUsages', 'getConstants', 'getConstantFetches', 'getReturnStatementsNodes', 'getPropertyAssigns'];
$csgRun = static function (string $gathererClass, \PHPStan\Reflection\ClassReflection $classReflection, array $pairs) use ($csgDescribe, $csgGetters): array {
	$forwarded = [];
	$gatherer = new $gathererClass($classReflection, static function (\PhpParser\Node $node, \PHPStan\Analyser\Scope $scope) use (&$forwarded): void {
		$forwarded[] = [spl_object_id($node), spl_object_id($scope)];
	});
	foreach ($pairs as [$node, $scope]) {
		$gatherer($node, $scope);
	}
	$observed = ['forwarded' => $forwarded];
	foreach ($csgGetters as $getter) {
		$observed[$getter] = $csgDescribe($gatherer->$getter());
	}
	return $observed;
};

foreach ($csgClasses as $csgClassName => $csgClassReflection) {
	$csgPhp = $csgRun(\PHPStan\Node\ClassStatementsGatherer::class, $csgClassReflection, $csgPairs);
	$csgNative = $csgRun(\PHPStanTurbo\ClassStatementsGatherer::class, $csgClassReflection, $csgPairs);
	foreach ($csgPhp as $csgKey => $csgValue) {
		check($csgValue === $csgNative[$csgKey], "ClassStatementsGatherer $csgClassName: $csgKey");
	}
	// the branches the fixture is there for must have produced something
	if ($csgClassName === 'ClassStatementsGathererFixture\\Gathered') {
		foreach ($csgGetters as $csgGetter) {
			check(count($csgPhp[$csgGetter]) > 0, "ClassStatementsGatherer $csgClassName: the fixture reaches $csgGetter");
		}
	}
}

// a pair outside any class: the twin's ShouldNotHappenException
if ($csgOutside !== null) {
	$csgThrown = [];
	foreach (['php' => \PHPStan\Node\ClassStatementsGatherer::class, 'native' => \PHPStanTurbo\ClassStatementsGatherer::class] as $csgSide => $csgClass) {
		$csgGatherer = new $csgClass(reset($csgClasses), static function (): void {
		});
		try {
			$csgGatherer($csgOutside[0], $csgOutside[1]);
			$csgThrown[$csgSide] = null;
		} catch (\Throwable $e) {
			$csgThrown[$csgSide] = [get_class($e), $e->getMessage()];
		}
	}
	check($csgThrown['php'] !== null && $csgThrown['php'] === $csgThrown['native'], 'ClassStatementsGatherer: a node outside any class throws the same exception (' . json_encode($csgThrown) . ')');
}

if (isset($csgStandalone)) {
	echo $failures === 0 ? "ALL OK\n" : "$failures FAILURE(S)\n";
	exit($failures === 0 ? 0 : 1);
}

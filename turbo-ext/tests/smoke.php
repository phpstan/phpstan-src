<?php declare(strict_types=1);

// Differential test: PHPStanTurbo\* native classes vs PHPStan's PHP implementations.
// Run: php -d extension=.../phpstan_turbo.so smoke.php  (from repo root)

['manifest' => $shadowedClasses, 'classMap' => $classMap] = require __DIR__ . '/activate-prefixed.php';

use PHPStan\TrinaryLogic;

$failures = 0;
function check(bool $cond, string $msg): void
{
	global $failures;
	if (!$cond) {
		$failures++;
		echo "FAIL: $msg\n";
	}
}

// The native class-reference table is the authority on the map's shape: the
// generated map must cover it exactly, and every baked default must equal
// the mapped class (the native code holds the class entries of the classes
// it shadows itself, so the table never names one of them).
$classRefs = \PHPStanTurbo\Runtime::classRefs();
ksort($classRefs);
check(array_keys($classRefs) === array_keys($classMap), 'the class map covers the native class-reference table exactly');
foreach ($classRefs as $key => $default) {
	check($default !== null, "class-map key $key has no baked default name");
	check(($classMap[$key] ?? null) === $default, "class-map key $key must match the native default");
	check(!isset($shadowedClasses[$default]), "class-map key $key names a shadowed class");
}

// each differential section registers the shadowed class it exercises; the
// completeness check at the end holds the union against the manifest
$covered = [];

// The native classes are declared as PHPStanTurbo\* here and instantiate
// each other under those names (a native TrinaryLogic hands out a native
// BooleanType); a Type-valued result is compared by class modulo that
// prefix, mapped from the manifest so a new port needs no edit here.
$turboNormMap = [];
foreach ($shadowedClasses as $shadowedClass => $entry) {
	$turboNormMap[$entry['turboClass']] = $shadowedClass;
}
$turboNorm = static fn (string $class): string => strtr($class, $turboNormMap);

// ---- TrinaryLogic ----
$covered[\PHPStan\TrinaryLogic::class] = true;
$pYes = TrinaryLogic::createYes();
$pNo = TrinaryLogic::createNo();
$pMaybe = TrinaryLogic::createMaybe();
$nYes = \PHPStanTurbo\TrinaryLogic::createYes();
$nNo = \PHPStanTurbo\TrinaryLogic::createNo();
$nMaybe = \PHPStanTurbo\TrinaryLogic::createMaybe();

$pAll = ['yes' => $pYes, 'no' => $pNo, 'maybe' => $pMaybe];
$nAll = ['yes' => $nYes, 'no' => $nNo, 'maybe' => $nMaybe];

check($nYes === \PHPStanTurbo\TrinaryLogic::createYes(), 'createYes identity');
check($nNo === \PHPStanTurbo\TrinaryLogic::createFromBoolean(false), 'createFromBoolean(false) identity');
check($nYes === \PHPStanTurbo\TrinaryLogic::createFromBoolean(true), 'createFromBoolean(true) identity');

foreach (['yes', 'no', 'maybe'] as $k) {
	check($pAll[$k]->yes() === $nAll[$k]->yes(), "$k yes()");
	check($pAll[$k]->no() === $nAll[$k]->no(), "$k no()");
	check($pAll[$k]->maybe() === $nAll[$k]->maybe(), "$k maybe()");
	check($pAll[$k]->describe() === $nAll[$k]->describe(), "$k describe()");
	// the native side instantiates the shadowing Boolean classes, declared
	// as PHPStanTurbo\* here — compare the class modulo that prefix
	check(get_class($pAll[$k]->toBooleanType()) === $turboNorm(get_class($nAll[$k]->toBooleanType())), "$k toBooleanType() class");
	check($pAll[$k]->toBooleanType()->describe(\PHPStan\Type\VerbosityLevel::precise()) === $nAll[$k]->toBooleanType()->describe(\PHPStan\Type\VerbosityLevel::precise()), "$k toBooleanType() describe");
	foreach (['yes', 'no', 'maybe'] as $j) {
		check($pAll[$k]->and($pAll[$j])->describe() === $nAll[$k]->and($nAll[$j])->describe(), "$k and $j");
		check($pAll[$k]->or($pAll[$j])->describe() === $nAll[$k]->or($nAll[$j])->describe(), "$k or $j");
		check($pAll[$k]->equals($pAll[$j]) === $nAll[$k]->equals($nAll[$j]), "$k equals $j");
		$pc = $pAll[$k]->compareTo($pAll[$j]);
		$nc = $nAll[$k]->compareTo($nAll[$j]);
		check(($pc === null) === ($nc === null) && ($pc === null || $pc->describe() === $nc->describe()), "$k compareTo $j");
		foreach (['yes', 'no', 'maybe'] as $m) {
			check(
				$pAll[$k]->and($pAll[$j], $pAll[$m])->describe() === $nAll[$k]->and($nAll[$j], $nAll[$m])->describe(),
				"$k and($j,$m)",
			);
			check(
				TrinaryLogic::extremeIdentity($pAll[$k], $pAll[$j], $pAll[$m])->describe() === \PHPStanTurbo\TrinaryLogic::extremeIdentity($nAll[$k], $nAll[$j], $nAll[$m])->describe(),
				"extremeIdentity($k,$j,$m)",
			);
			check(
				TrinaryLogic::maxMin($pAll[$k], $pAll[$j], $pAll[$m])->describe() === \PHPStanTurbo\TrinaryLogic::maxMin($nAll[$k], $nAll[$j], $nAll[$m])->describe(),
				"maxMin($k,$j,$m)",
			);
		}
	}
	check($pAll[$k]->negate()->describe() === $nAll[$k]->negate()->describe(), "$k negate()");
	check($pAll[$k]->and()->describe() === $nAll[$k]->and()->describe(), "$k and() no args");
	check($pAll[$k]->or()->describe() === $nAll[$k]->or()->describe(), "$k or() no args");
}

// lazy*
$keys = ['yes', 'no', 'maybe'];
foreach ($keys as $k) {
	foreach ([['yes', 'maybe'], ['no', 'no'], ['maybe', 'yes', 'no'], []] as $items) {
		$pcb = static fn (string $s) => $GLOBALS['pAll'][$s] ?? TrinaryLogic::createYes();
		$ncb = static fn (string $s) => $GLOBALS['nAll'][$s] ?? \PHPStanTurbo\TrinaryLogic::createYes();
		$GLOBALS['pAll'] = $pAll;
		$GLOBALS['nAll'] = $nAll;
		check(
			$pAll[$k]->lazyAnd($items, $pcb)->describe() === $nAll[$k]->lazyAnd($items, $ncb)->describe(),
			"$k lazyAnd " . implode(',', $items),
		);
		check(
			$pAll[$k]->lazyOr($items, $pcb)->describe() === $nAll[$k]->lazyOr($items, $ncb)->describe(),
			"$k lazyOr " . implode(',', $items),
		);
	}
}
foreach ([['yes'], ['yes', 'yes'], ['yes', 'maybe'], ['no', 'no'], ['maybe', 'no', 'yes']] as $items) {
	$pcb = static fn (string $s) => $GLOBALS['pAll'][$s];
	$ncb = static fn (string $s) => $GLOBALS['nAll'][$s];
	check(
		TrinaryLogic::lazyExtremeIdentity($items, $pcb)->describe() === \PHPStanTurbo\TrinaryLogic::lazyExtremeIdentity($items, $ncb)->describe(),
		'lazyExtremeIdentity ' . implode(',', $items),
	);
	check(
		TrinaryLogic::lazyMaxMin($items, $pcb)->describe() === \PHPStanTurbo\TrinaryLogic::lazyMaxMin($items, $ncb)->describe(),
		'lazyMaxMin ' . implode(',', $items),
	);
}

// empty extremeIdentity/maxMin must throw ShouldNotHappenException
foreach (['extremeIdentity', 'maxMin'] as $m) {
	try {
		\PHPStanTurbo\TrinaryLogic::$m();
		check(false, "$m() empty should throw");
	} catch (\PHPStan\ShouldNotHappenException) {
		// ok
	}
}

// lazyMaxMin([]) does NOT throw — it returns Yes ($min starts at YES), unlike
// its non-lazy sibling
$neverCalled = static function ($o) {
	throw new \LogicException('callback must not run for an empty array');
};
check(
	TrinaryLogic::lazyMaxMin([], $neverCalled)->describe() === \PHPStanTurbo\TrinaryLogic::lazyMaxMin([], $neverCalled)->describe()
	&& \PHPStanTurbo\TrinaryLogic::lazyMaxMin([], $neverCalled)->yes(),
	'lazyMaxMin([]) returns Yes',
);

// ---- CombinationsHelper ----
$covered[\PHPStan\Internal\CombinationsHelper::class] = true;
$cases = [
	[],
	[[1, 2, 3]],
	[[1, 2], ['a', 'b', 'c']],
	[[1], [2], [3]],
	[[1, 2], [], [3]],
	[['x' => 1, 'y' => 2], [true, false]],
	[[1.5, 'str', null], [[], [1]], [7]],
];
foreach ($cases as $i => $case) {
	$php = [];
	foreach (\PHPStan\Internal\CombinationsHelper::combinations($case) as $c) {
		$php[] = $c;
	}
	$native = \PHPStanTurbo\CombinationsHelper::combinations($case);
	if (!is_array($native)) {
		$native = iterator_to_array($native, false);
	}
	check($php === $native, "combinations case $i: " . json_encode($php) . ' vs ' . json_encode($native));
}

// ---- ExpressionTypeHolder ----
$covered[\PHPStan\Analyser\ExpressionTypeHolder::class] = true;
$expr1 = new \PhpParser\Node\Expr\Variable('a');
$expr2 = new \PhpParser\Node\Expr\Variable('b');
// The holders' types are the native Type classes: the native holders
// describe them with the native VerbosityLevel (ConditionalExpressionHolder::getKey()),
// which the PHP twins' describe(VerbosityLevel $level) would refuse, while the
// native describe() takes either level.
$int = new \PHPStanTurbo\IntegerType();
$string = new \PHPStanTurbo\StringType();
$int2 = new \PHPStanTurbo\IntegerType();

$pH = static fn ($expr, $type, $c) => new \PHPStan\Analyser\ExpressionTypeHolder($expr, $type, $c);
$nH = static fn ($expr, $type, $c) => new \PHPStanTurbo\ExpressionTypeHolder($expr, $type, $c);

$combos = [
	[$expr1, $int, 'yes'],
	[$expr1, $int2, 'maybe'],
	[$expr1, $string, 'no'],
	[$expr2, $string, 'yes'],
];
foreach ($combos as [$e1, $t1, $c1]) {
	foreach ($combos as [$e2, $t2, $c2]) {
		$p1 = $pH($e1, $t1, $pAll[$c1]);
		$p2 = $pH($e2, $t2, $pAll[$c2]);
		$n1 = $nH($e1, $t1, $nAll[$c1]);
		$n2 = $nH($e2, $t2, $nAll[$c2]);
		check($p1->equals($p2) === $n1->equals($n2), "ETH equals $c1/$c2 " . $t1->describe(\PHPStan\Type\VerbosityLevel::precise()) . '/' . $t2->describe(\PHPStan\Type\VerbosityLevel::precise()));
		check($p1->equalTypes($p2) === $n1->equalTypes($n2), "ETH equalTypes");
		$pa = $p1->and($p2);
		$na = $n1->and($n2);
		check($pa->getCertainty()->describe() === $na->getCertainty()->describe(), "ETH and certainty $c1/$c2");
		check($pa->getType()->describe(\PHPStan\Type\VerbosityLevel::precise()) === $na->getType()->describe(\PHPStan\Type\VerbosityLevel::precise()), "ETH and type");
		check($pa->getExpr() === $na->getExpr() || $pa->getExpr()->name === $na->getExpr()->name, "ETH and expr");
	}
}
// identity semantics of and(): same type object, certainty yes+yes -> $this
$n1 = $nH($expr1, $int, $nYes);
$n2 = $nH($expr2, $int, $nYes);
check($n1->and($n2) === $n1, 'ETH and identity (same type, yes+yes)');
$nMaybeH = $nH($expr1, $int, $nMaybe);
check($nMaybeH->and($n2) === $nMaybeH, 'ETH and identity (maybe this)');
$nNoH = $nH($expr1, $int, $nNo);
check($nNoH->and($n2) === $n2, 'ETH and returns other (no this)');
// createYes / createMaybe
check(\PHPStanTurbo\ExpressionTypeHolder::createYes($expr1, $int)->getCertainty()->yes(), 'ETH createYes');
check(\PHPStanTurbo\ExpressionTypeHolder::createMaybe($expr1, $int)->getCertainty()->maybe(), 'ETH createMaybe');
check(\PHPStanTurbo\ExpressionTypeHolder::createYes($expr1, $int)->getType() === $int, 'ETH createYes type identity');

// ---- ScopeOps::mergeVariableHolders differingKeys ----
$sharedP = $pH($expr1, $int, $pYes);
$sharedN = $nH($expr1, $int, $nYes);
$mergePOurs = ['$shared' => $sharedP, '$a' => $pH($expr1, $int, $pYes), '$b' => $pH($expr2, $string, $pYes)];
$mergePTheirs = ['$shared' => $sharedP, '$b' => $pH($expr2, $string, $pMaybe), '$c' => $pH($expr2, $int, $pYes)];
$mergeNOurs = ['$shared' => $sharedN, '$a' => $nH($expr1, $int, $nYes), '$b' => $nH($expr2, $string, $nYes)];
$mergeNTheirs = ['$shared' => $sharedN, '$b' => $nH($expr2, $string, $nMaybe), '$c' => $nH($expr2, $int, $nYes)];
$pDiffering = [];
$pMerged = \PHPStan\Analyser\ScopeOps::mergeVariableHolders($mergePOurs, $mergePTheirs, $pDiffering);
$nDiffering = [];
$nMerged = \PHPStanTurbo\ScopeOps::mergeVariableHolders($mergeNOurs, $mergeNTheirs, $nDiffering);
check($pDiffering === $nDiffering, 'ScopeOps mergeVariableHolders differingKeys parity: ' . json_encode($pDiffering) . ' vs ' . json_encode($nDiffering));
check(array_keys($pMerged) === array_keys($nMerged), 'ScopeOps mergeVariableHolders merged keys parity');
check(array_keys(\PHPStanTurbo\ScopeOps::mergeVariableHolders($mergeNOurs, $mergeNTheirs)) === array_keys($nMerged), 'ScopeOps mergeVariableHolders without differingKeys');

// ---- ConditionalExpressionHolder ----
$covered[\PHPStan\Analyser\ConditionalExpressionHolder::class] = true;
$pCEH = new \PHPStan\Analyser\ConditionalExpressionHolder(
	['$a' => $pH($expr1, $int, $pYes), '$b' => $pH($expr2, $string, $pMaybe)],
	$pH($expr2, $string, $pNo),
);
$nCEH = new \PHPStanTurbo\ConditionalExpressionHolder(
	['$a' => $nH($expr1, $int, $nYes), '$b' => $nH($expr2, $string, $nMaybe)],
	$nH($expr2, $string, $nNo),
);
check($pCEH->getKey() === $nCEH->getKey(), 'CEH getKey: ' . $pCEH->getKey() . ' vs ' . $nCEH->getKey());
check(count($nCEH->getConditionExpressionTypeHolders()) === 2, 'CEH holders count');
check($nCEH->getTypeHolder()->getCertainty()->no(), 'CEH typeHolder');
try {
	new \PHPStanTurbo\ConditionalExpressionHolder([], $nH($expr1, $int, $nYes));
	check(false, 'CEH empty should throw');
} catch (\PHPStan\ShouldNotHappenException) {
}

// ---- TypeCombinatorCache ----
$covered[\PHPStan\Type\TypeCombinatorCache::class] = true;
// The native class memoizes on a structural key of the arguments and computes a miss
// through the native TypeCombinator's doUnion() and friends (a direct C++ call). The
// prefixed PHPStanTurbo\TypeCombinator runs unmemoized here (the enabler never ran, so
// its $cacheEnabled reads false), which makes it the reference the memoized results
// are held against; the combinator's own behaviour against the PHP twin is the
// type-family differential's job (the prefixed declaration cannot mix the two
// implementations' compound types). The inputs are the native Type classes: the
// native combinator describes its arguments with the native VerbosityLevel, which
// the PHP twins' typed describe() parameter rejects in this prefixed process.
$cacheLevel = \PHPStanTurbo\VerbosityLevel::cache();
$describe = static fn (\PHPStan\Type\Type $t): string => $t->describe($cacheLevel);

$intT = new \PHPStanTurbo\IntegerType();
$stringT = new \PHPStanTurbo\StringType();
$nullT = new \PHPStanTurbo\NullType();
$oneT = new \PHPStanTurbo\ConstantIntegerType(1);
$tenT = new \PHPStanTurbo\ConstantIntegerType(10);
$arrayT = new \PHPStanTurbo\ArrayType(new \PHPStanTurbo\MixedType(), new \PHPStanTurbo\MixedType());
$nonEmpty = new \PHPStanTurbo\NonEmptyArrayType();

$unions = [
	[$intT, $stringT],
	[$oneT, $tenT, $nullT],
	[new \PHPStanTurbo\UnionType([$oneT, $tenT]), $nullT],
];
foreach ($unions as $i => $args) {
	$native = \PHPStanTurbo\TypeCombinatorCache::union(...$args);
	$php = \PHPStanTurbo\TypeCombinator::union(...$args);
	check($describe($native) === $describe($php), "TCC union #$i: {$describe($native)} vs {$describe($php)}");
}

$native = \PHPStanTurbo\TypeCombinatorCache::intersect($arrayT, $nonEmpty);
$php = \PHPStanTurbo\TypeCombinator::intersect($arrayT, $nonEmpty);
check($describe($native) === $describe($php), 'TCC intersect: ' . $describe($native) . ' vs ' . $describe($php));

$nullable = \PHPStanTurbo\TypeCombinator::union($intT, $nullT);
$native = \PHPStanTurbo\TypeCombinatorCache::remove($nullable, $nullT);
$php = \PHPStanTurbo\TypeCombinator::remove($nullable, $nullT);
check($describe($native) === $describe($php), 'TCC remove: ' . $describe($native) . ' vs ' . $describe($php));

// a repeated call must hit the memo and hand back the very same instance
$first = \PHPStanTurbo\TypeCombinatorCache::union($intT, $stringT);
$second = \PHPStanTurbo\TypeCombinatorCache::union(new \PHPStanTurbo\IntegerType(), new \PHPStanTurbo\StringType());
check($first === $second, 'TCC memo hit on structurally equal arguments');

// explicit and implicit mixed are different values and must not share a memo entry
$explicit = \PHPStanTurbo\TypeCombinatorCache::union(new \PHPStanTurbo\MixedType(true), $intT);
$implicit = \PHPStanTurbo\TypeCombinatorCache::union(new \PHPStanTurbo\MixedType(false), $intT);
check($describe($explicit) !== $describe($implicit), 'TCC keeps explicit/implicit mixed apart');

// no interning: argument tuples with different memo keys that arrive at the same
// value hand back distinct instances, as the PHP implementation does
$wider = \PHPStanTurbo\TypeCombinatorCache::union($intT, $stringT, new \PHPStanTurbo\NeverType());
check($describe($wider) === $describe($first), 'TCC: the extra never collapses to the same value');
check($wider !== $first, 'TCC: no shared instance across memo keys');
check(\PHPStanTurbo\TypeCombinatorCache::union($stringT, $intT) !== $first, 'TCC: another argument order is another memo key');
check(\PHPStanTurbo\TypeCombinatorCache::union($intT, $nullT) !== $first, 'TCC: distinct values stay apart');

// a result that is an operand is the operand of the call at hand, as TypeCombinator
// returns it - callers test that identity (ArrayType::setExistingOffsetValueType())
$shape = static fn (): \PHPStan\Type\Type => new \PHPStan\Type\Constant\ConstantArrayType([new \PHPStan\Type\Constant\ConstantStringType('a')], [$intT]);
$firstShape = $shape();
check(\PHPStanTurbo\TypeCombinatorCache::union($firstShape, $shape()) === $firstShape, 'TCC: the union of equal shapes is the first operand');
$secondShape = $shape();
check(\PHPStanTurbo\TypeCombinatorCache::union($secondShape, $shape()) === $secondShape, 'TCC: a memo hit returns the operand of the call at hand');
$firstInt = new \PHPStan\Type\IntegerType();
check(\PHPStanTurbo\TypeCombinatorCache::remove($firstInt, $nullT) === $firstInt, 'TCC: removing nothing returns the operand');
$secondInt = new \PHPStan\Type\IntegerType();
check(\PHPStanTurbo\TypeCombinatorCache::remove($secondInt, $nullT) === $secondInt, 'TCC: a memo hit on removing nothing returns the operand of the call at hand');

\PHPStanTurbo\TypeCombinatorCache::clearCache();
$afterClear = \PHPStanTurbo\TypeCombinatorCache::union($intT, $stringT);
check($describe($afterClear) === $describe($first), 'TCC clearCache keeps results correct');
check($afterClear !== $first, 'TCC clearCache actually drops entries');

// ---- ExpressionResultStorage ----
$covered[\PHPStan\Analyser\ExpressionResultStorage::class] = true;
$makeResult = static function () {
	static $reflection = null;
	$reflection ??= new ReflectionClass(\PHPStan\Analyser\ExpressionResult::class);
	return $reflection->newInstanceWithoutConstructor();
};

foreach (['php' => \PHPStan\Analyser\ExpressionResultStorage::class, 'native' => \PHPStanTurbo\ExpressionResultStorage::class] as $label => $storageClass) {
	$storage = new $storageClass();
	$exprA = new \PhpParser\Node\Expr\Variable('a');
	$exprB = new \PhpParser\Node\Expr\Variable('b');
	$exprC = new \PhpParser\Node\Expr\Variable('c');
	$resultA = $makeResult();
	$resultB = $makeResult();
	$resultC = $makeResult();

	check($storage->findExpressionResult($exprA) === null, "ERS $label: find on empty storage is null");
	$storage->storeExpressionResult($exprA, $resultA);
	check($storage->findExpressionResult($exprA) === $resultA, "ERS $label: find returns the stored result");
	check($storage->findExpressionResult($exprB) === null, "ERS $label: unknown expr is null");
	$storage->storeExpressionResult($exprA, $resultB);
	check($storage->findExpressionResult($exprA) === $resultB, "ERS $label: overwrite for the same expr");

	$duplicate = $storage->duplicate();
	check(get_class($duplicate) === $storageClass, "ERS $label: duplicate creates the same class");
	check($duplicate->findExpressionResult($exprA) === $resultB, "ERS $label: duplicate reads through the fallback");
	$duplicate->storeExpressionResult($exprB, $resultA);
	check($duplicate->findExpressionResult($exprB) === $resultA, "ERS $label: store on the duplicate");
	check($storage->findExpressionResult($exprB) === null, "ERS $label: duplicate stores do not leak back");
	$duplicate->storeExpressionResult($exprA, $resultC);
	check($duplicate->findExpressionResult($exprA) === $resultC, "ERS $label: duplicate store shadows the fallback");
	check($storage->findExpressionResult($exprA) === $resultB, "ERS $label: shadowing store does not leak back");

	$grandchild = $duplicate->duplicate();
	check($grandchild->findExpressionResult($exprB) === $resultA, "ERS $label: find walks the whole fallback chain");

	$other = new $storageClass();
	$other->storeExpressionResult($exprC, $resultC);
	$otherChild = $other->duplicate();
	$otherChild->storeExpressionResult($exprB, $resultB);
	$storage->mergeResults($otherChild);
	check($storage->findExpressionResult($exprB) === $resultB, "ERS $label: mergeResults carries the other's own entries");
	check($storage->findExpressionResult($exprC) === null, "ERS $label: mergeResults ignores the other's fallback chain");
	check($storage->findExpressionResult($exprA) === $resultB, "ERS $label: mergeResults keeps existing entries");

}

// ---- NodeScanner ----
$covered[\PHPStan\Node\NodeScanner::class] = true;
$smokeParserFactory = new \PhpParser\ParserFactory();
$smokeParser = $smokeParserFactory->createForNewestSupportedVersion();
$nodeFinder = new \PhpParser\NodeFinder();
$nodeScannerSnippets = [
	'<?php function f() { yield 1; }',
	'<?php function f() { yield from g(); }',
	'<?php function f() { return 1; }',
	'<?php function f() { $c = function () { yield 2; }; }',
	'<?php function f() { $a = [1, [2, new C(yield)]]; }',
	'<?php echo 1 + 2; class D { public function m() { yield; } }',
];
foreach ($nodeScannerSnippets as $si => $code) {
	$ast = $smokeParser->parse($code);
	foreach ($nodeFinder->find($ast, static fn (): bool => true) as $ni => $node) {
		check(
			\PHPStan\Node\NodeScanner::nodeIsOrContainsYield($node) === \PHPStanTurbo\NodeScanner::nodeIsOrContainsYield($node),
			"NodeScanner snippet #$si node #$ni (" . $node->getType() . ')',
		);
	}
}

// ---- NodeTraverser ----
$covered[\PhpParser\NodeTraverser::class] = true;
// Fresh ASTs per side (visitors mutate them); the visitors themselves are
// plain PHP on both sides — that is how PHPStan uses the native traverser.
$traverserCode = '<?php $x = a($y); remove_me(); function f($p) { $q = $y; } $z = $x; stop_here(); $after = 1;';
$isCallTo = static function (\PhpParser\Node $node, string $name): bool {
	return $node instanceof \PhpParser\Node\Stmt\Expression
		&& $node->expr instanceof \PhpParser\Node\Expr\FuncCall
		&& $node->expr->name instanceof \PhpParser\Node\Name
		&& $node->expr->name->toString() === $name;
};
$runTraverser = static function (string $traverserClass, bool $withStopper) use ($smokeParser, $traverserCode, $isCallTo): array {
	$logger = new class extends \PhpParser\NodeVisitorAbstract {

		/** @var list<string> */
		public array $log = [];

		public function beforeTraverse(array $nodes)
		{
			$this->log[] = 'before';
			return null;
		}

		public function enterNode(\PhpParser\Node $node)
		{
			$this->log[] = 'enter ' . $node->getType();
			return null;
		}

		public function leaveNode(\PhpParser\Node $node)
		{
			$this->log[] = 'leave ' . $node->getType();
			return null;
		}

		public function afterTraverse(array $nodes)
		{
			$this->log[] = 'after';
			return null;
		}

	};
	$mutator = new class ($isCallTo) extends \PhpParser\NodeVisitorAbstract {

		public function __construct(private \Closure $isCallTo)
		{
		}

		public function enterNode(\PhpParser\Node $node)
		{
			if ($node instanceof \PhpParser\Node\Expr\Variable && $node->name === 'y') {
				return new \PhpParser\Node\Expr\Variable('renamed');
			}
			if ($node instanceof \PhpParser\Node\Stmt\Function_) {
				return \PhpParser\NodeVisitor::DONT_TRAVERSE_CHILDREN;
			}
			return null;
		}

		public function leaveNode(\PhpParser\Node $node)
		{
			if (($this->isCallTo)($node, 'remove_me')) {
				return \PhpParser\NodeVisitor::REMOVE_NODE;
			}
			return null;
		}

	};
	$stopper = new class ($isCallTo) extends \PhpParser\NodeVisitorAbstract {

		public function __construct(private \Closure $isCallTo)
		{
		}

		public function enterNode(\PhpParser\Node $node)
		{
			if (($this->isCallTo)($node, 'stop_here')) {
				return \PhpParser\NodeVisitor::STOP_TRAVERSAL;
			}
			return null;
		}

	};

	$traverser = new $traverserClass();
	$traverser->addVisitor($logger);
	$traverser->addVisitor($mutator);
	if ($withStopper) {
		$traverser->addVisitor($stopper);
	}
	$result = $traverser->traverse($smokeParser->parse($traverserCode));

	return [$logger->log, (new \PhpParser\PrettyPrinter\Standard())->prettyPrintFile($result)];
};
foreach ([false, true] as $withStopper) {
	[$pLog, $pCode] = $runTraverser(\PhpParser\NodeTraverser::class, $withStopper);
	[$nLog, $nCode] = $runTraverser(\PHPStanTurbo\NodeTraverser::class, $withStopper);
	$stopLabel = $withStopper ? ' (with STOP_TRAVERSAL)' : '';
	check($pLog === $nLog, "NodeTraverser: visitor call sequence$stopLabel");
	check($pCode === $nCode, "NodeTraverser: transformed output$stopLabel");
}

// ---- ScopeOps ----
$covered[\PHPStan\Analyser\ScopeOps::class] = true;
$scopeOpsClasses = ['php' => \PHPStan\Analyser\ScopeOps::class, 'native' => \PHPStanTurbo\ScopeOps::class];

// getIntertwinedRefRootVariableName
$rootNameCases = [
	'variable' => new \PhpParser\Node\Expr\Variable('a'),
	'nested dim fetch' => new \PhpParser\Node\Expr\ArrayDimFetch(
		new \PhpParser\Node\Expr\ArrayDimFetch(new \PhpParser\Node\Expr\Variable('root'), new \PhpParser\Node\Scalar\Int_(1)),
		new \PhpParser\Node\Scalar\String_('k'),
	),
	'variable variable' => new \PhpParser\Node\Expr\Variable(new \PhpParser\Node\Expr\Variable('a')),
	'dim over call' => new \PhpParser\Node\Expr\ArrayDimFetch(new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('f')), new \PhpParser\Node\Scalar\Int_(0)),
	'property fetch' => new \PhpParser\Node\Expr\PropertyFetch(new \PhpParser\Node\Expr\Variable('o'), 'p'),
];
foreach ($rootNameCases as $label => $rootNameExpr) {
	check(
		\PHPStan\Analyser\ScopeOps::getIntertwinedRefRootVariableName($rootNameExpr) === \PHPStanTurbo\ScopeOps::getIntertwinedRefRootVariableName($rootNameExpr),
		"ScopeOps getIntertwinedRefRootVariableName: $label",
	);
}

// nodeKey
$exprPrinter = new \PHPStan\Node\Printer\ExprPrinter(new \PHPStan\Node\Printer\Printer());
$arrayMapClosure = new \PhpParser\Node\Expr\Closure();
$arrayMapClosure->setAttribute(\PHPStan\Parser\ArrayMapArgVisitor::ATTRIBUTE_NAME, [new \PhpParser\Node\Arg(new \PhpParser\Node\Expr\Variable('items'))]);
$arrayMapClosure->setAttribute('startFilePos', 123);
$nodeKeyCases = [
	'variable fast path' => new \PhpParser\Node\Expr\Variable('foo'),
	'variable variable' => new \PhpParser\Node\Expr\Variable(new \PhpParser\Node\Expr\Variable('foo')),
	'method call' => new \PhpParser\Node\Expr\MethodCall(new \PhpParser\Node\Expr\Variable('o'), 'm', [new \PhpParser\Node\Arg(new \PhpParser\Node\Scalar\Int_(1))]),
	'array_map closure' => $arrayMapClosure,
];
foreach ($nodeKeyCases as $label => $nodeKeyExpr) {
	check(
		\PHPStan\Analyser\ScopeOps::nodeKey($nodeKeyExpr, $exprPrinter) === \PHPStanTurbo\ScopeOps::nodeKey($nodeKeyExpr, $exprPrinter),
		"ScopeOps nodeKey: $label",
	);
}

// mergeVariableHolders — fresh expression graphs and holders per side: the
// superglobal scan memoizes into a node attribute, and holders must be the
// side's own class
$mergeInputs = static function (string $side): array {
	$holder = $side === 'php'
		? static fn ($expr, $type, $certainty) => new \PHPStan\Analyser\ExpressionTypeHolder($expr, $type, $certainty)
		: static fn ($expr, $type, $certainty) => new \PHPStanTurbo\ExpressionTypeHolder($expr, $type, $certainty);
	$yes = $side === 'php' ? \PHPStan\TrinaryLogic::createYes() : \PHPStanTurbo\TrinaryLogic::createYes();
	$maybe = $side === 'php' ? \PHPStan\TrinaryLogic::createMaybe() : \PHPStanTurbo\TrinaryLogic::createMaybe();

	$int = new \PHPStanTurbo\IntegerType();
	$string = new \PHPStanTurbo\StringType();

	$same = $holder(new \PhpParser\Node\Expr\Variable('same'), $int, $yes);
	$andExpr = new \PhpParser\Node\Expr\Variable('and');
	$superGlobalExpr = new \PhpParser\Node\Expr\ArrayDimFetch(new \PhpParser\Node\Expr\Variable('_SERVER'), new \PhpParser\Node\Scalar\String_('x'));

	return [
		[
			'$same' => $same,
			'$and' => $holder($andExpr, $int, $yes),
			'$onlyOurs' => $holder(new \PhpParser\Node\Expr\Variable('onlyOurs'), $string, $yes),
			'$_SERVER[\'x\']' => $holder($superGlobalExpr, $string, $yes),
		],
		[
			'$same' => $same,
			'$and' => $holder($andExpr, $string, $maybe),
			'$onlyTheirs' => $holder(new \PhpParser\Node\Expr\Variable('onlyTheirs'), $int, $yes),
		],
	];
};
$mergeResults = [];
foreach ($scopeOpsClasses as $side => $scopeOpsClass) {
	[$ours, $theirs] = $mergeInputs($side);
	$merged = $scopeOpsClass::mergeVariableHolders($ours, $theirs);
	$described = [];
	foreach ($merged as $exprString => $mergedHolder) {
		$described[$exprString] = [
			$mergedHolder->getCertainty()->describe(),
			$mergedHolder->getType()->describe(\PHPStan\Type\VerbosityLevel::precise()),
		];
	}
	$mergeResults[$side] = $described;
	check($merged['$same'] === $ours['$same'], "ScopeOps mergeVariableHolders $side: identical holder is kept");
}
check($mergeResults['php'] === $mergeResults['native'], 'ScopeOps mergeVariableHolders: merged keys, certainties and types');

// matchConditionalExpressions — a holder whose conditions are all among the
// specified expressions must resolve, transitively (fixed point); '$c'
// resolves only after '$b' did, '$unmatched' never does
$matchInputs = static function (string $side): array {
	$holder = $side === 'php'
		? static fn ($expr, $type, $certainty) => new \PHPStan\Analyser\ExpressionTypeHolder($expr, $type, $certainty)
		: static fn ($expr, $type, $certainty) => new \PHPStanTurbo\ExpressionTypeHolder($expr, $type, $certainty);
	$conditional = $side === 'php'
		? static fn ($conditions, $typeHolder) => new \PHPStan\Analyser\ConditionalExpressionHolder($conditions, $typeHolder)
		: static fn ($conditions, $typeHolder) => new \PHPStanTurbo\ConditionalExpressionHolder($conditions, $typeHolder);
	$yes = $side === 'php' ? \PHPStan\TrinaryLogic::createYes() : \PHPStanTurbo\TrinaryLogic::createYes();

	$int = new \PHPStanTurbo\IntegerType();
	$string = new \PHPStanTurbo\StringType();
	$aExpr = new \PhpParser\Node\Expr\Variable('a');

	return [
		[
			'$b' => [$conditional(['$a' => $holder($aExpr, $int, $yes)], $holder(new \PhpParser\Node\Expr\Variable('b'), $string, $yes))],
			'$c' => [$conditional(['$b' => $holder(new \PhpParser\Node\Expr\Variable('b'), $string, $yes)], $holder(new \PhpParser\Node\Expr\Variable('c'), $int, $yes))],
			'$unmatched' => [$conditional(['$z' => $holder(new \PhpParser\Node\Expr\Variable('z'), $int, $yes)], $holder(new \PhpParser\Node\Expr\Variable('unmatched'), $int, $yes))],
		],
		['$a' => $holder($aExpr, $int, $yes)],
	];
};
$matchResults = [];
foreach ($scopeOpsClasses as $side => $scopeOpsClass) {
	[$conditionalExpressions, $specifiedExpressions] = $matchInputs($side);
	[$remainingConditions, $specified] = $scopeOpsClass::matchConditionalExpressions($conditionalExpressions, $specifiedExpressions);
	$describedConditions = [];
	foreach ($remainingConditions as $exprString => $conditionalHolders) {
		$describedConditions[$exprString] = array_map(static fn ($conditionalHolder): string => $conditionalHolder->getKey(), $conditionalHolders);
	}
	$describedSpecified = [];
	foreach ($specified as $exprString => $specifiedHolder) {
		$describedSpecified[$exprString] = [
			$specifiedHolder->getCertainty()->describe(),
			$specifiedHolder->getType()->describe(\PHPStan\Type\VerbosityLevel::precise()),
		];
	}
	$matchResults[$side] = [$describedConditions, $describedSpecified];

	[, $emptySpecified] = $scopeOpsClass::matchConditionalExpressions($conditionalExpressions, []);
	check($emptySpecified === [], "ScopeOps matchConditionalExpressions $side: empty specified expressions short-circuit");
}
check($matchResults['php'] === $matchResults['native'], 'ScopeOps matchConditionalExpressions: fixed point and remaining conditions');

// ---- ScopeContext ----
// Needs real ClassReflection instances (the PHP twin type-hints them), so a
// container is booted here; equals() must compare by getName(), not identity.
$covered[\PHPStan\Analyser\ScopeContext::class] = true;
$scContainerFactory = new \PHPStan\DependencyInjection\ContainerFactory(dirname(__DIR__, 2));
$scContainer = $scContainerFactory->create(sys_get_temp_dir() . '/phpstan-turbo-smoke', [$scContainerFactory->getConfigDirectory() . '/config.level8.neon'], []);
$scReflectionProvider = $scContainer->getByType(\PHPStan\Reflection\ReflectionProvider::class);
$scClassA = $scReflectionProvider->getClass(\PHPStan\Type\IntegerType::class);
$scClassA2 = $scReflectionProvider->getClass(\PHPStan\Type\IntegerType::class);
$scClassB = $scReflectionProvider->getClass(\PHPStan\Type\StringType::class);
$scTrait = $scReflectionProvider->getClass(\PHPStan\Type\Traits\ConstantScalarTypeTrait::class);
$scResults = [];
foreach (['php' => \PHPStan\Analyser\ScopeContext::class, 'native' => \PHPStanTurbo\ScopeContext::class] as $side => $scClass) {
	$r = [];
	$file = $scClass::create('/a.php');
	$r[] = [$file instanceof $scClass, $file->getFile(), $file->getClassReflection(), $file->getTraitReflection()];
	$inClass = $file->enterClass($scClassA);
	$r[] = [$inClass->getFile(), $inClass->getClassReflection() === $scClassA, $inClass->getTraitReflection()];
	$inTrait = $inClass->enterTrait($scTrait);
	$r[] = [$inTrait->getClassReflection() === $scClassA, $inTrait->getTraitReflection() === $scTrait];
	$begun = $inTrait->beginFile();
	$r[] = [$begun->getFile(), $begun->getClassReflection(), $begun->getTraitReflection()];
	$contexts = [
		'file' => $file, 'file2' => $scClass::create('/a.php'), 'other' => $scClass::create('/b.php'),
		'classA' => $inClass, 'classA2' => $scClass::create('/a.php')->enterClass($scClassA2), 'classB' => $file->enterClass($scClassB),
		'trait' => $inTrait, 'trait2' => $scClass::create('/a.php')->enterClass($scClassA2)->enterTrait($scTrait), 'traitB' => $file->enterClass($scClassB)->enterTrait($scTrait),
	];
	foreach ($contexts as $k1 => $c1) {
		foreach ($contexts as $k2 => $c2) {
			$r[] = [$k1, $k2, $c1->equals($c2)];
		}
	}
	foreach ([
		'class in class' => static fn () => $inClass->enterClass($scClassB),
		'trait via enterClass' => static fn () => $file->enterClass($scTrait),
		'trait outside class' => static fn () => $file->enterTrait($scTrait),
		'non-trait via enterTrait' => static fn () => $inClass->enterTrait($scClassB),
	] as $label => $fn) {
		try {
			$fn();
			$r[] = [$label, 'no exception'];
		} catch (\PHPStan\ShouldNotHappenException $e) {
			$r[] = [$label, $e->getMessage()];
		}
	}
	try {
		new $scClass('/x.php', null, null);
		$r[] = 'ctor callable';
	} catch (\Error $e) {
		$r[] = ['ctor', get_class($e)];
	}
	$scResults[$side] = $r;
}
check($scResults['php'] === $scResults['native'], 'ScopeContext parity: ' . json_encode($scResults['php']) . ' vs ' . json_encode($scResults['native']));

// ---- IsSuperTypeOfResult / AcceptsResult ----
$covered[\PHPStan\Type\IsSuperTypeOfResult::class] = true;
$covered[\PHPStan\Type\AcceptsResult::class] = true;
$resultSides = [
	'php' => [\PHPStan\Type\IsSuperTypeOfResult::class, \PHPStan\Type\AcceptsResult::class, $pAll],
	'native' => [\PHPStanTurbo\IsSuperTypeOfResult::class, \PHPStanTurbo\AcceptsResult::class, $nAll],
];
$resultObservations = [];
foreach ($resultSides as $side => [$is, $ar, $tri]) {
	$o = [];
	$log = [];
	$lazy = static function (string $s) use (&$log): \Closure {
		return static function () use ($s, &$log): string {
			$log[] = $s;
			return $s;
		};
	};
	// structural view of a result without invoking its lazy reasons
	$shape = static fn (object $r): array => [$r->result->describe(), $r->reasons, $r instanceof $is ? count($r->lazyReasons) : null];
	$norm = static fn (string $m): string => str_replace(['PHPStanTurbo\\', 'PHPStan\\Type\\'], '', $m);

	$o['yes identity'] = $is::createYes() === $is::createYes();
	$o['maybe identity'] = $is::createMaybe() === $is::createMaybe();
	$o['no identity'] = $is::createNo() === $is::createNo() && $is::createNo([]) === $is::createNo() && $is::createNo([], []) === $is::createNo();
	$o['fromBoolean'] = $is::createFromBoolean(true) === $is::createYes() && $is::createFromBoolean(false) === $is::createNo();
	$o['no with reasons is fresh'] = $is::createNo(['x']) !== $is::createNo() && $is::createNo([], [$lazy('l')]) !== $is::createNo();
	$o['named-arg skip'] = $shape($is::createNo(lazyReasons: [$lazy('n')]));
	$o['singleton result identity'] = $is::createYes()->result === $tri['yes'] && $is::createNo()->result === $tri['no'] && $is::createMaybe()->result === $tri['maybe'];
	foreach (['yes' => $is::createYes(), 'maybe' => $is::createMaybe(), 'no' => $is::createNo()] as $k => $r) {
		$o["$k flags"] = [$r->yes(), $r->maybe(), $r->no(), $r->describe(), $r->reasons, $r->lazyReasons, $r->getReasons()];
	}

	$a = new $is($tri['no'], ['r1', 'r2'], [$lazy('l1')]);
	$b = new $is($tri['maybe'], ['r2', 'r3'], [$lazy('l2'), $lazy('r1')]);
	$c = new $is($tri['yes'], []);
	$o['props'] = [$a->result === $tri['no'], $a->reasons, count($a->lazyReasons), $c->lazyReasons];
	$o['getReasons'] = [$a->getReasons(), $b->getReasons(), $c->getReasons()];
	$o['getReasons log'] = $log;
	$log = [];

	$o['and'] = [$shape($a->and($b)), $shape($a->and($b, $c)), $shape($a->and()), $shape($c->and($a)), $a->and($b)->getReasons()];
	$o['or'] = [$shape($a->or($b)), $shape($b->or($c, $a)), $shape($a->or()), $a->or($b)->getReasons()];
	$o['and/or fresh'] = $a->and() !== $a && $c->and() !== $c && $c->or() !== $c;
	$log = [];
	$d = $a->decorateReasons(static fn (string $s): string => "<$s>");
	$o['decorate keeps lazy lazy'] = $log;
	$o['decorate'] = [$shape($d), array_map(static fn ($x) => $x instanceof \Closure, $d->lazyReasons), $d->getReasons(), $d->result === $a->result];
	$o['decorate log'] = $log;
	$log = [];
	$o['decorate empty'] = $shape($c->decorateReasons(static fn (string $s): string => $s));

	$o['extremeIdentity'] = [$shape($is::extremeIdentity($a, $b, $c)), $shape($is::extremeIdentity($a, $a)), $shape($is::extremeIdentity($c, $c)), $is::extremeIdentity($a, $b)->getReasons()];
	$o['maxMin'] = [$shape($is::maxMin($a, $b)), $shape($is::maxMin($a, $c)), $shape($is::maxMin($b, $b)), $is::maxMin($a, $b)->getReasons()];
	foreach (['extremeIdentity', 'maxMin'] as $m) {
		try {
			$is::$m();
			$o["$m empty"] = 'no throw';
		} catch (\PHPStan\ShouldNotHappenException) {
			$o["$m empty"] = 'throws';
		}
	}
	$byName = ['a' => $a, 'b' => $b, 'c' => $c];
	$cb = static fn (string $n) => $byName[$n];
	$o['lazyMaxMin'] = [
		$is::lazyMaxMin(['a', 'b', 'c'], $cb) === $c,
		$shape($is::lazyMaxMin(['a', 'b'], $cb)),
		$is::lazyMaxMin(['a', 'b'], $cb)->getReasons(),
		$shape($is::lazyMaxMin(['b'], $cb)),
		$shape($is::lazyMaxMin([], $cb)),
		$is::lazyMaxMin([], $cb) !== $is::createMaybe(),
		$is::lazyMaxMin(['b', 'b'], $cb)->getReasons(),
	];
	$o['negate'] = [$shape($a->negate()), $shape($b->negate()), $shape($c->negate()), $a->negate() !== $a, $a->negate()->getReasons()];
	$acc = $a->toAcceptsResult();
	$o['toAcceptsResult'] = [$acc instanceof $ar, $acc->result === $a->result, $acc->reasons, $c->toAcceptsResult() !== $ar::createYes(), $b->toAcceptsResult()->reasons];

	$o['ar singletons'] = [$ar::createYes() === $ar::createYes(), $ar::createNo() === $ar::createNo([]), $ar::createMaybe() === $ar::createMaybe(), $ar::createFromBoolean(true) === $ar::createYes(), $ar::createFromBoolean(false) === $ar::createNo(), $ar::createNo(['q']) !== $ar::createNo(), $ar::createNo(['q'])->reasons, $ar::createYes()->result === $tri['yes']];
	$x = new $ar($tri['no'], ['a', 'b']);
	$y = new $ar($tri['maybe'], ['b', 'c']);
	$z = new $ar($tri['yes'], []);
	$o['ar flags'] = [[$x->yes(), $x->maybe(), $x->no()], [$y->yes(), $y->maybe(), $y->no()], [$z->yes(), $z->maybe(), $z->no()]];
	$o['ar and/or'] = [$shape($x->and($y)), $shape($y->and($z)), $shape($x->or($y)), $shape($z->or($x)), $shape($x->and($x))];
	$o['ar decorate'] = [$shape($x->decorateReasons(static fn (string $s): string => "[$s]")), $shape($z->decorateReasons(static fn (string $s): string => "[$s]"))];
	$o['ar extremeIdentity/maxMin'] = [$shape($ar::extremeIdentity($x, $y)), $shape($ar::extremeIdentity($z, $z)), $shape($ar::maxMin($x, $y)), $shape($ar::maxMin($x, $z)), $shape($ar::maxMin($y, $y))];
	foreach (['extremeIdentity', 'maxMin'] as $m) {
		try {
			$ar::$m();
			$o["ar $m empty"] = 'no throw';
		} catch (\PHPStan\ShouldNotHappenException) {
			$o["ar $m empty"] = 'throws';
		}
	}
	$arByName = ['x' => $x, 'y' => $y, 'z' => $z];
	$arCb = static fn (string $n) => $arByName[$n];
	$o['ar lazyMaxMin'] = [$ar::lazyMaxMin(['x', 'y', 'z'], $arCb) === $z, $shape($ar::lazyMaxMin(['x', 'y'], $arCb)), $shape($ar::lazyMaxMin(['y'], $arCb)), $shape($ar::lazyMaxMin([], $arCb)), $ar::lazyMaxMin([], $arCb) !== $ar::createMaybe()];

	try {
		$a->reasons = [];
		$o['readonly write'] = 'no throw';
	} catch (\Error $e) {
		$o['readonly write'] = $norm($e->getMessage());
	}
	try {
		$a->__construct($tri['yes'], []);
		$o['reconstruct'] = 'no throw';
	} catch (\Error $e) {
		$o['reconstruct'] = $norm($e->getMessage());
	}
	try {
		(new $is($tri['no'], [], ['not a closure']))->getReasons();
		$o['lazy type'] = 'no throw';
	} catch (\TypeError) {
		$o['lazy type'] = 'TypeError';
	}
	$resultObservations[$side] = $o;
}
foreach ($resultObservations['php'] as $key => $expected) {
	check($expected === ($resultObservations['native'][$key] ?? null), "IsSuperTypeOfResult/AcceptsResult $key: " . json_encode($expected) . ' vs ' . json_encode($resultObservations['native'][$key] ?? null));
}

// ---- TypeUtils ----
// The differential proper runs under the real names in type-family.php: the
// helpers test their arguments against the shadowed Type classes — the PHP
// twins on the PHP side, the prefixed natives here — so a PHP union handed to
// the prefixed class is not a union to it. Only the answers that do not
// depend on that are compared here.
$covered[\PHPStan\Type\TypeUtils::class] = true;
$tuScope = \PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('tu');
$tuTemplate = \PHPStan\Type\Generic\TemplateTypeFactory::create($tuScope, 'T', null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
$tuInputs = [
	'int' => new \PHPStan\Type\IntegerType(),
	'template' => $tuTemplate,
	'arrayOfTemplate' => new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), $tuTemplate),
	'arrayOfInt' => new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()),
	'callable' => new \PHPStan\Type\CallableType(),
	'closure' => new \PHPStan\Type\ClosureType(),
	'string' => new \PHPStan\Type\StringType(),
	'keyOfShape' => new \PHPStan\Type\KeyOfType(new \PHPStan\Type\Constant\ConstantArrayType([new \PHPStan\Type\Constant\ConstantStringType('a')], [new \PHPStan\Type\IntegerType()])),
	'keyOfTemplate' => new \PHPStan\Type\KeyOfType($tuTemplate),
];
$tuPrecise = \PHPStan\Type\VerbosityLevel::precise();
foreach ($tuInputs as $tuName => $tuInput) {
	check(\PHPStanTurbo\TypeUtils::containsTemplateType($tuInput) === \PHPStan\Type\TypeUtils::containsTemplateType($tuInput), "TypeUtils containsTemplateType $tuName");
	$tuPhp = \PHPStan\Type\TypeUtils::findCallableType($tuInput);
	$tuNative = \PHPStanTurbo\TypeUtils::findCallableType($tuInput);
	check(($tuPhp === null) === ($tuNative === null) && ($tuPhp === null || $tuPhp->describe($tuPrecise) === $tuNative->describe($tuPrecise)), "TypeUtils findCallableType $tuName");
	foreach ([true, false] as $tuResolve) {
		$tuPhp = \PHPStan\Type\TypeUtils::resolveLateResolvableTypes($tuInput, $tuResolve);
		$tuNative = \PHPStanTurbo\TypeUtils::resolveLateResolvableTypes($tuInput, $tuResolve);
		check($tuPhp->describe($tuPrecise) === $tuNative->describe($tuPrecise) && get_class($tuPhp) === $turboNorm(get_class($tuNative)), "TypeUtils resolveLateResolvableTypes $tuName " . var_export($tuResolve, true) . ': ' . $tuPhp->describe($tuPrecise) . ' vs ' . $tuNative->describe($tuPrecise));
	}
	check(\PHPStanTurbo\TypeUtils::resolveLateResolvableTypes($tuInput) === \PHPStan\Type\TypeUtils::resolveLateResolvableTypes($tuInput) || $tuInput->hasTemplateOrLateResolvableType(), "TypeUtils resolveLateResolvableTypes identity $tuName");
}

// ---- TypehintHelper ----
// The differential proper runs under the real names in type-family.php (the
// helper tests the types against the shadowed classes — the PHP twins on the
// PHP side, the prefixed natives here); only the answers that do not depend
// on that are compared here.
$covered[\PHPStan\Type\TypehintHelper::class] = true;
$thInt = new \PHPStan\Type\IntegerType();
$thArray = new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType());
$thPrecise = \PHPStan\Type\VerbosityLevel::precise();
check(\PHPStanTurbo\TypehintHelper::decideType($thInt, null) === $thInt && \PHPStan\Type\TypehintHelper::decideType($thInt, null) === $thInt, 'TypehintHelper decideType without a PHPDoc type is the identity');
check(\PHPStanTurbo\TypehintHelper::decideTypeFromReflection(null, $thInt) === $thInt && \PHPStan\Type\TypehintHelper::decideTypeFromReflection(null, $thInt) === $thInt, 'TypehintHelper decideTypeFromReflection without a reflection type is the PHPDoc type');
$thPhp = \PHPStan\Type\TypehintHelper::decideTypeFromReflection(null);
$thNative = \PHPStanTurbo\TypehintHelper::decideTypeFromReflection(null);
check(get_class($thPhp) === $turboNorm(get_class($thNative)) && $thPhp->describe($thPrecise) === $thNative->describe($thPrecise), 'TypehintHelper decideTypeFromReflection without anything is mixed');
$thCore = (new ReflectionMethod(\PHPStan\TrinaryLogic::class, 'yes'))->getReturnType(); // a real (not tentative) return type: the core reflection type, not the adapter
foreach (['php' => \PHPStan\Type\TypehintHelper::class, 'native' => \PHPStanTurbo\TypehintHelper::class] as $thSide => $thClass) {
	try {
		$thClass::decideTypeFromReflection($thCore);
		$thResults[$thSide] = 'no throw';
	} catch (\Throwable $e) {
		$thResults[$thSide] = [get_class($e), $e->getMessage()];
	}
}
check($thResults['php'] === $thResults['native'] && $thResults['php'][0] === \PHPStan\ShouldNotHappenException::class, 'TypehintHelper decideTypeFromReflection of a core reflection type throws: ' . json_encode($thResults));

// ---- the Type ports: BooleanType, ConstantBooleanType, IntegerType, ConstantIntegerType, IntegerRangeType, StringType, ConstantStringType, ClassStringType, GenericClassStringType, FloatType, ConstantFloatType, NullType, VoidType ----
// A Type never acts alone: its results flow into the PHP compound types and
// back through `self`-typed statics (IsSuperTypeOfResult::extremeIdentity()),
// so a native result object meeting the PHP result class in the prefixed
// declaration used above is a TypeError. The Type ports are therefore
// compared the way they run: tests/type-family.php observes the whole
// family under the real names, once as the PHP twins and once with the
// native classes activated in their place, and the two observation sets
// must be identical.
$covered[\PHPStan\Type\BooleanType::class] = true;
$covered[\PHPStan\Type\Constant\ConstantBooleanType::class] = true;
$covered[\PHPStan\Type\IntegerType::class] = true;
$covered[\PHPStan\Type\Constant\ConstantIntegerType::class] = true;
$covered[\PHPStan\Type\IntegerRangeType::class] = true;
$covered[\PHPStan\Type\StringType::class] = true;
$covered[\PHPStan\Type\Constant\ConstantStringType::class] = true;
$covered[\PHPStan\Type\ClassStringType::class] = true;
$covered[\PHPStan\Type\Generic\GenericClassStringType::class] = true;
$covered[\PHPStan\Type\FloatType::class] = true;
$covered[\PHPStan\Type\Constant\ConstantFloatType::class] = true;
$covered[\PHPStan\Type\NullType::class] = true;
$covered[\PHPStan\Type\VoidType::class] = true;
$covered[\PHPStan\Type\NeverType::class] = true;
$covered[\PHPStan\Type\MixedType::class] = true;
$covered[\PHPStan\Type\StrictMixedType::class] = true;
$covered[\PHPStan\Type\ObjectWithoutClassType::class] = true;
$covered[\PHPStan\Type\StaticType::class] = true;
$covered[\PHPStan\Type\ThisType::class] = true;
$covered[\PHPStan\Type\Generic\GenericStaticType::class] = true;
$covered[\PHPStan\Type\ObjectShapeType::class] = true;
$covered[\PHPStan\Type\NonexistentParentClassType::class] = true;
$covered[\PHPStan\Type\ArrayType::class] = true;
$covered[\PHPStan\Type\Accessory\NonEmptyArrayType::class] = true;
$covered[\PHPStan\Type\Accessory\AccessoryArrayListType::class] = true;
$covered[\PHPStan\Type\Accessory\OversizedArrayType::class] = true;
$covered[\PHPStan\Type\Accessory\HasOffsetType::class] = true;
$covered[\PHPStan\Type\Accessory\HasOffsetValueType::class] = true;
$covered[\PHPStan\Type\Accessory\AccessoryNumericStringType::class] = true;
$covered[\PHPStan\Type\Accessory\AccessoryNonEmptyStringType::class] = true;
$covered[\PHPStan\Type\Accessory\AccessoryNonFalsyStringType::class] = true;
$covered[\PHPStan\Type\Accessory\AccessoryLiteralStringType::class] = true;
$covered[\PHPStan\Type\Accessory\AccessoryLowercaseStringType::class] = true;
$covered[\PHPStan\Type\Accessory\AccessoryUppercaseStringType::class] = true;
$covered[\PHPStan\Type\Accessory\AccessoryDecimalIntegerStringType::class] = true;
$covered[\PHPStan\Type\Accessory\HasMethodType::class] = true;
$covered[\PHPStan\Type\Accessory\HasPropertyType::class] = true;
$covered[\PHPStan\Type\ObjectType::class] = true;
$covered[\PHPStan\Type\Generic\GenericObjectType::class] = true;
$covered[\PHPStan\Type\Enum\EnumCaseObjectType::class] = true;
$covered[\PHPStan\Type\IterableType::class] = true;
$covered[\PHPStan\Type\CallableType::class] = true;
$covered[\PHPStan\Type\ClosureType::class] = true;
$covered[\PHPStan\Type\Constant\ConstantArrayType::class] = true;
$covered[\PHPStan\Type\UnionType::class] = true;
$covered[\PHPStan\Type\BenevolentUnionType::class] = true;
$covered[\PHPStan\Type\IntersectionType::class] = true;
$covered[\PHPStan\Type\ErrorType::class] = true;
$covered[\PHPStan\Type\CircularTypeAliasErrorType::class] = true;
$covered[\PHPStan\Type\Generic\AbsorbedTemplateArgumentType::class] = true;
$covered[\PHPStan\Type\NonAcceptingNeverType::class] = true;
$covered[\PHPStan\Type\StringAlwaysAcceptingObjectWithToStringType::class] = true;
$covered[\PHPStan\Type\StringNeverAcceptingObjectWithToStringType::class] = true;
$covered[\PHPStan\Type\ResourceType::class] = true;
// the reflection value classes the Type kernel builds — observed under the
// real names in tests/type-family.php too (their transformations depend on
// the shadowed Type classes' identity)
$covered[\PHPStan\Rules\PhpDoc\UnresolvableTypeHelper::class] = true;
$covered[\PHPStan\Reflection\Native\NativeParameterReflection::class] = true;
$covered[\PHPStan\Reflection\Type\CalledOnTypeUnresolvedMethodPrototypeReflection::class] = true;
$covered[\PHPStan\Reflection\Type\CalledOnTypeUnresolvedPropertyPrototypeReflection::class] = true;
$covered[\PHPStan\Reflection\Type\CallbackUnresolvedMethodPrototypeReflection::class] = true;
$covered[\PHPStan\Reflection\Type\CallbackUnresolvedPropertyPrototypeReflection::class] = true;

/** @return array<string, mixed> */
function observeTypeFamily(string $mode): array
{
	// Windows CI provides the built DLL path via TURBO_DLL (see phar.yml, as
	// for arena-smoke.php); everywhere else the .so sits next to the tests.
	$extension = getenv('TURBO_DLL');
	if (!is_string($extension) || $extension === '') {
		$extension = __DIR__ . '/../phpstan_turbo.so';
	}
	// either mode peaks near 1 GB, past a stock php.ini's memory_limit, and
	// its observations run to tens of MB here
	ini_set('memory_limit', '4G');
	$cmd = sprintf(
		'%s -d memory_limit=4G -d extension=%s %s %s',
		escapeshellarg(PHP_BINARY),
		escapeshellarg($extension),
		escapeshellarg(__DIR__ . '/type-family.php'),
		escapeshellarg($mode),
	);
	$process = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
	if ($process === false) {
		fwrite(STDERR, "proc_open failed\n");
		exit(2);
	}
	$stdout = stream_get_contents($pipes[1]);
	$stderr = stream_get_contents($pipes[2]);
	fclose($pipes[1]);
	fclose($pipes[2]);
	$exitCode = proc_close($process);
	if ($exitCode !== 0 || $stdout === false) {
		fwrite(STDERR, sprintf("type-family.php %s failed with exit code %d\n%s%s", $mode, $exitCode, $stdout === false ? '' : $stdout, $stderr));
		exit(1);
	}
	// the observations are the child's last stdout line; a startup notice
	// (the extension also loaded through php.ini) may precede it
	$lines = array_values(array_filter(explode("\n", $stdout), static fn (string $line): bool => trim($line) !== ''));
	try {
		$observations = json_decode($lines === [] ? '' : $lines[count($lines) - 1], true, 16, JSON_THROW_ON_ERROR);
	} catch (\JsonException $e) {
		fwrite(STDERR, sprintf("type-family.php %s printed no observations: %s\n%s%s", $mode, $e->getMessage(), $stdout, $stderr));
		exit(1);
	}
	if (!is_array($observations)) {
		fwrite(STDERR, sprintf("type-family.php %s printed no observations\n", $mode));
		exit(1);
	}
	return $observations;
}

$typeFamilyPhp = observeTypeFamily('php');
$typeFamilyNative = observeTypeFamily('native');
foreach ([\PHPStan\Type\BooleanType::class, \PHPStan\Type\Constant\ConstantBooleanType::class, \PHPStan\Type\IntegerType::class, \PHPStan\Type\Constant\ConstantIntegerType::class, \PHPStan\Type\IntegerRangeType::class, \PHPStan\Type\StringType::class, \PHPStan\Type\Constant\ConstantStringType::class, \PHPStan\Type\ClassStringType::class, \PHPStan\Type\Generic\GenericClassStringType::class, \PHPStan\Type\FloatType::class, \PHPStan\Type\Constant\ConstantFloatType::class, \PHPStan\Type\NullType::class, \PHPStan\Type\VoidType::class, \PHPStan\Type\NeverType::class, \PHPStan\Type\MixedType::class, \PHPStan\Type\StrictMixedType::class, \PHPStan\Type\ObjectWithoutClassType::class, \PHPStan\Type\StaticType::class, \PHPStan\Type\ThisType::class, \PHPStan\Type\Generic\GenericStaticType::class, \PHPStan\Type\ObjectShapeType::class, \PHPStan\Type\NonexistentParentClassType::class, \PHPStan\Type\ArrayType::class, \PHPStan\Type\Accessory\NonEmptyArrayType::class, \PHPStan\Type\Accessory\AccessoryArrayListType::class, \PHPStan\Type\Accessory\OversizedArrayType::class, \PHPStan\Type\Accessory\HasOffsetType::class, \PHPStan\Type\Accessory\HasOffsetValueType::class, \PHPStan\Type\Accessory\AccessoryNumericStringType::class, \PHPStan\Type\Accessory\AccessoryNonEmptyStringType::class, \PHPStan\Type\Accessory\AccessoryNonFalsyStringType::class, \PHPStan\Type\Accessory\AccessoryLiteralStringType::class, \PHPStan\Type\Accessory\AccessoryLowercaseStringType::class, \PHPStan\Type\Accessory\AccessoryUppercaseStringType::class, \PHPStan\Type\Accessory\AccessoryDecimalIntegerStringType::class, \PHPStan\Type\Accessory\HasMethodType::class, \PHPStan\Type\Accessory\HasPropertyType::class, \PHPStan\Type\ObjectType::class, \PHPStan\Type\Generic\GenericObjectType::class, \PHPStan\Type\Enum\EnumCaseObjectType::class, \PHPStan\Type\IterableType::class, \PHPStan\Type\CallableType::class, \PHPStan\Type\ClosureType::class, \PHPStan\Type\Constant\ConstantArrayType::class, \PHPStan\Type\UnionType::class, \PHPStan\Type\BenevolentUnionType::class, \PHPStan\Type\IntersectionType::class, \PHPStan\Type\ErrorType::class, \PHPStan\Type\CircularTypeAliasErrorType::class, \PHPStan\Type\Generic\AbsorbedTemplateArgumentType::class, \PHPStan\Type\NonAcceptingNeverType::class, \PHPStan\Type\StringAlwaysAcceptingObjectWithToStringType::class, \PHPStan\Type\StringNeverAcceptingObjectWithToStringType::class, \PHPStan\Type\ResourceType::class, \PHPStan\Type\TypeUtils::class, \PHPStan\Type\TypehintHelper::class, \PHPStan\Type\TypeCombinator::class, \PHPStan\Type\Generic\TemplateTypeVariance::class, \PHPStan\Type\Generic\TemplateTypeVarianceMap::class, \PHPStan\Type\Generic\TemplateTypeMap::class, \PHPStan\Type\Generic\TemplateTypeScope::class, \PHPStan\Type\Generic\TemplateTypeReference::class, \PHPStan\Type\Generic\TemplateTypeHelper::class, \PHPStan\Type\KeyOfType::class, \PHPStan\Type\ValueOfType::class, \PHPStan\Type\OffsetAccessType::class, \PHPStan\Type\ClassConstantAccessType::class, \PHPStan\Type\NewObjectType::class, \PHPStan\Type\ConditionalType::class, \PHPStan\Type\ConditionalTypeForParameter::class, \PHPStan\Type\LateResolvableArrayShapeType::class, \PHPStan\Type\Generic\UnresolvedTemplateArgumentType::class, \PHPStan\Type\Generic\TemplateArrayType::class, \PHPStan\Type\Generic\TemplateBenevolentUnionType::class, \PHPStan\Type\Generic\TemplateBooleanType::class, \PHPStan\Type\Generic\TemplateConstantArrayType::class, \PHPStan\Type\Generic\TemplateConstantIntegerType::class, \PHPStan\Type\Generic\TemplateConstantStringType::class, \PHPStan\Type\Generic\TemplateFloatType::class, \PHPStan\Type\Generic\TemplateGenericObjectType::class, \PHPStan\Type\Generic\TemplateIntegerType::class, \PHPStan\Type\Generic\TemplateIntersectionType::class, \PHPStan\Type\Generic\TemplateIterableType::class, \PHPStan\Type\Generic\TemplateMixedType::class, \PHPStan\Type\Generic\TemplateNullType::class, \PHPStan\Type\Generic\TemplateObjectShapeType::class, \PHPStan\Type\Generic\TemplateObjectType::class, \PHPStan\Type\Generic\TemplateObjectWithoutClassType::class, \PHPStan\Type\Generic\TemplateStrictMixedType::class, \PHPStan\Type\Generic\TemplateStringType::class, \PHPStan\Type\Generic\TemplateUnionType::class, \PHPStan\Type\Generic\TemplateTypeArgumentStrategy::class, \PHPStan\Type\Generic\TemplateTypeParameterStrategy::class, \PHPStan\Type\Generic\TemplateTypeFactory::class, \PHPStan\Type\Generic\TypeProjectionHelper::class, \PHPStan\Type\Constant\ConstantArrayTypeBuilder::class, \PHPStan\Type\UnionTypeHelper::class, \PHPStan\Type\CallableTypeHelper::class, \PHPStan\Type\Helper\GetTemplateTypeType::class, \PHPStan\Type\Generic\TemplateKeyOfType::class, \PHPStan\Rules\PhpDoc\UnresolvableTypeHelper::class, \PHPStan\Reflection\Native\NativeParameterReflection::class, \PHPStan\Reflection\Type\CalledOnTypeUnresolvedMethodPrototypeReflection::class, \PHPStan\Reflection\Type\CalledOnTypeUnresolvedPropertyPrototypeReflection::class, \PHPStan\Reflection\Type\CallbackUnresolvedMethodPrototypeReflection::class, \PHPStan\Reflection\Type\CallbackUnresolvedPropertyPrototypeReflection::class] as $typeClass) {
	check(($typeFamilyPhp["native $typeClass"] ?? null) === false, "type-family.php php: $typeClass is the PHP twin");
	check(($typeFamilyNative["native $typeClass"] ?? null) === true, "type-family.php native: $typeClass is the native class");
	unset($typeFamilyPhp["native $typeClass"], $typeFamilyNative["native $typeClass"]);
}
check(count($typeFamilyPhp) > 1000, 'type-family.php: a substantial number of observations (' . count($typeFamilyPhp) . ')');
check(array_keys($typeFamilyPhp) === array_keys($typeFamilyNative), 'type-family.php: both modes observed the same keys');
foreach ($typeFamilyPhp as $key => $expected) {
	$actual = array_key_exists($key, $typeFamilyNative) ? $typeFamilyNative[$key] : '<missing>';
	check($expected === $actual, "Type family $key: " . json_encode($expected) . ' vs ' . json_encode($actual));
}

// ---- TypeTraverser ----
// The native traverser hands its [$traverser, 'method'] arrays to the
// callback and to Type::traverse() exactly as the twin does — PHP types on
// both sides (their traverse() takes any callable), the native traverser
// against the PHP one, the callback recording what it was handed.
$ttSubject = new \PHPStan\Type\UnionType([
	new \PHPStan\Type\Constant\ConstantStringType('foo'),
	new \PHPStan\Type\IntersectionType([new \PHPStan\Type\StringType(), new \PHPStan\Type\Accessory\AccessoryNonEmptyStringType()]),
	new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\Constant\ConstantStringType('bar')),
	new \PHPStan\Type\NullType(),
]);
$ttMakeCallback = static function (array &$log): \Closure {
	return static function (\PHPStan\Type\Type $type, callable $traverse) use (&$log): \PHPStan\Type\Type {
		$log[] = [get_class($type), is_array($traverse) && count($traverse) === 2 && is_object($traverse[0]) && is_string($traverse[1]) ? $traverse[1] : gettype($traverse)];
		if ($type instanceof \PHPStan\Type\Constant\ConstantStringType) {
			return new \PHPStan\Type\ObjectType($type->getValue());
		}
		return $traverse($type);
	};
};
$ttPhpLog = [];
$ttNativeLog = [];
$ttPhpMapped = \PHPStan\Type\TypeTraverser::map($ttSubject, $ttMakeCallback($ttPhpLog));
$ttNativeMapped = \PHPStanTurbo\TypeTraverser::map($ttSubject, $ttMakeCallback($ttNativeLog));
check($ttPhpMapped->describe(\PHPStan\Type\VerbosityLevel::precise()) === $ttNativeMapped->describe(\PHPStan\Type\VerbosityLevel::precise()), 'TypeTraverser: map() result');
check($ttPhpLog === $ttNativeLog, 'TypeTraverser: the callback saw the same types and the same $traverse shape (' . json_encode($ttNativeLog) . ')');
check(count($ttNativeLog) > 4, 'TypeTraverser: the traversal descended into the compound types');
check($ttNativeLog[0][1] === 'traverseInternal', 'TypeTraverser: $traverse is [$traverser, \'traverseInternal\']');
// a callback that never descends replaces the root
$ttPhpRoot = \PHPStan\Type\TypeTraverser::map($ttSubject, static fn (\PHPStan\Type\Type $type, callable $traverse): \PHPStan\Type\Type => new \PHPStan\Type\IntegerType());
$ttNativeRoot = \PHPStanTurbo\TypeTraverser::map($ttSubject, static fn (\PHPStan\Type\Type $type, callable $traverse): \PHPStan\Type\Type => new \PHPStan\Type\IntegerType());
check(get_class($ttPhpRoot) === get_class($ttNativeRoot) && $ttNativeRoot instanceof \PHPStan\Type\IntegerType, 'TypeTraverser: a callback replacing the root');
// a TypeTraverserCallable receives (Type, callable) and its result is the map's
$ttCallable = new class implements \PHPStan\Type\TypeTraverserCallable {

	/** @var list<string> */
	public array $seen = [];

	public function traverse(\PHPStan\Type\Type $type, callable $traverse): \PHPStan\Type\Type
	{
		$this->seen[] = get_class($type) . '/' . (is_array($traverse) ? 'array' : gettype($traverse));
		if ($type instanceof \PHPStan\Type\NullType) {
			return new \PHPStan\Type\VoidType();
		}
		return $traverse($type);
	}

};
$ttPhpCallable = clone $ttCallable;
$ttNativeCallable = clone $ttCallable;
$ttPhpMapped = \PHPStan\Type\TypeTraverser::map($ttSubject, $ttPhpCallable);
$ttNativeMapped = \PHPStanTurbo\TypeTraverser::map($ttSubject, $ttNativeCallable);
check($ttPhpMapped->describe(\PHPStan\Type\VerbosityLevel::precise()) === $ttNativeMapped->describe(\PHPStan\Type\VerbosityLevel::precise()), 'TypeTraverser: map() over a TypeTraverserCallable');
check($ttPhpCallable->seen === $ttNativeCallable->seen && count($ttNativeCallable->seen) > 4, 'TypeTraverser: the TypeTraverserCallable saw the same (Type, callable) pairs');
// the twin's parameter and return types
$ttErrors = static function (string $traverser) use ($ttSubject): array {
	$errors = [];
	try {
		$traverser::map($ttSubject, 'no-such-function-anywhere');
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	try {
		$traverser::map($ttSubject, static fn (\PHPStan\Type\Type $type, callable $traverse) => 'not a type');
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	return $errors;
};
check($ttErrors(\PHPStan\Type\TypeTraverser::class) === $ttErrors(\PHPStanTurbo\TypeTraverser::class), 'TypeTraverser: a non-callable $cb and a non-Type result throw the same (' . implode(', ', $ttErrors(\PHPStanTurbo\TypeTraverser::class)) . ')');

// ---- VerbosityLevel ----
// The singletons and their queries, handle() over every callback
// combination, and getRecommendedLevelByType() over paired inputs: the
// native level inspects Type classes by class entry, so the native side
// gets the native Type classes and the PHP side the twins (same
// construction, the classes mapped through the manifest). Generic subjects
// cannot be paired here — a native GenericObjectType::describe() hands the
// native level to the PHP TypeProjectionHelper's VerbosityLevel parameter —
// so the invariant-template traversal is observed under the real names in
// tests/type-family.php instead.
$vlLevels = [];
foreach (['typeOnly', 'value', 'precise', 'cache'] as $vlFactory) {
	$vlPhp = \PHPStan\Type\VerbosityLevel::$vlFactory();
	$vlNative = \PHPStanTurbo\VerbosityLevel::$vlFactory();
	check($vlNative === \PHPStanTurbo\VerbosityLevel::$vlFactory(), "VerbosityLevel::$vlFactory() identity");
	check($vlPhp->getLevelValue() === $vlNative->getLevelValue(), "VerbosityLevel::$vlFactory() getLevelValue()");
	foreach (['isTypeOnly', 'isValue', 'isPrecise', 'isCache'] as $vlQuery) {
		check($vlPhp->$vlQuery() === $vlNative->$vlQuery(), "VerbosityLevel::$vlFactory() $vlQuery()");
	}
	$vlT = static fn (): string => 'T';
	$vlV = static fn (): string => 'V';
	$vlP = static fn (): string => 'P';
	$vlC = static fn (): string => 'C';
	check($vlPhp->handle($vlT, $vlV) === $vlNative->handle($vlT, $vlV), "VerbosityLevel::$vlFactory() handle(T, V)");
	check($vlPhp->handle($vlT, $vlV, $vlP) === $vlNative->handle($vlT, $vlV, $vlP), "VerbosityLevel::$vlFactory() handle(T, V, P)");
	check($vlPhp->handle($vlT, $vlV, null, $vlC) === $vlNative->handle($vlT, $vlV, null, $vlC), "VerbosityLevel::$vlFactory() handle(T, V, null, C)");
	check($vlPhp->handle($vlT, $vlV, $vlP, $vlC) === $vlNative->handle($vlT, $vlV, $vlP, $vlC), "VerbosityLevel::$vlFactory() handle(T, V, P, C)");
	check($vlPhp->handle(typeOnlyCallback: $vlT, valueCallback: $vlV, cacheCallback: $vlC) === $vlNative->handle(typeOnlyCallback: $vlT, valueCallback: $vlV, cacheCallback: $vlC), "VerbosityLevel::$vlFactory() handle() with named arguments");
	$vlHandleErrors = static function (\PHPStan\Type\VerbosityLevel|\PHPStanTurbo\VerbosityLevel $level): string {
		try {
			$level->handle(static fn () => 1, static fn () => 1, static fn () => 1, static fn () => 1);
			return 'none';
		} catch (\Throwable $e) {
			return get_class($e);
		}
	};
	check($vlHandleErrors($vlPhp) === $vlHandleErrors($vlNative), "VerbosityLevel::$vlFactory() handle() rejects a non-string result the same way");
	$vlLevels[$vlFactory] = [$vlPhp, $vlNative];
}
$vlClass = static fn (string $phpClass, bool $native): string => $native ? $shadowedClasses[$phpClass]['turboClass'] : $phpClass;
/** @return list<array{string, \PHPStan\Type\Type, \PHPStan\Type\Type|null}> */
$vlCases = static function (bool $native) use ($vlClass): array {
	$c = static fn (string $phpClass): string => $vlClass($phpClass, $native);
	$string = new ($c(\PHPStan\Type\StringType::class))();
	$int = new ($c(\PHPStan\Type\IntegerType::class))();
	$constString = new ($c(\PHPStan\Type\Constant\ConstantStringType::class))('foo');
	$constInt = new ($c(\PHPStan\Type\Constant\ConstantIntegerType::class))(1);
	$null = new ($c(\PHPStan\Type\NullType::class))();
	$nonEmpty = new ($c(\PHPStan\Type\IntersectionType::class))([new ($c(\PHPStan\Type\StringType::class))(), new ($c(\PHPStan\Type\Accessory\AccessoryNonEmptyStringType::class))()]);
	$lowercase = new ($c(\PHPStan\Type\IntersectionType::class))([new ($c(\PHPStan\Type\StringType::class))(), new ($c(\PHPStan\Type\Accessory\AccessoryLowercaseStringType::class))()]);
	$list = new ($c(\PHPStan\Type\IntersectionType::class))([new ($c(\PHPStan\Type\ArrayType::class))($int, $string), new ($c(\PHPStan\Type\Accessory\AccessoryArrayListType::class))()]);
	$range = ($c(\PHPStan\Type\IntegerRangeType::class))::fromInterval(1, 5);
	$closure = new ($c(\PHPStan\Type\ClosureType::class))();
	$callable = new ($c(\PHPStan\Type\CallableType::class))();
	$array = new ($c(\PHPStan\Type\ArrayType::class))($int, $string);
	$constArray = new ($c(\PHPStan\Type\Constant\ConstantArrayType::class))([new ($c(\PHPStan\Type\Constant\ConstantIntegerType::class))(0)], [$string]);
	$nullOrOne = new ($c(\PHPStan\Type\UnionType::class))([new ($c(\PHPStan\Type\NullType::class))(), new ($c(\PHPStan\Type\Constant\ConstantIntegerType::class))(1)]);
	$nested = new ($c(\PHPStan\Type\UnionType::class))([new ($c(\PHPStan\Type\Constant\ConstantStringType::class))('a'), new ($c(\PHPStan\Type\IntersectionType::class))([new ($c(\PHPStan\Type\StringType::class))(), new ($c(\PHPStan\Type\Accessory\AccessoryUppercaseStringType::class))()])]);
	$plainObject = new ($c(\PHPStan\Type\ObjectType::class))(\stdClass::class);

	return [
		['string', $string, null],
		['constant string', $constString, null],
		['constant int', $constInt, null],
		['null', $null, null],
		['non-empty-string', $nonEmpty, null],
		['lowercase-string', $lowercase, null],
		['list', $list, null],
		['int range', $range, null],
		['closure', $closure, null],
		['callable', $callable, null],
		['array', $array, null],
		['constant array', $constArray, null],
		['null|1', $nullOrOne, null],
		['nested uppercase', $nested, null],
		['array vs constant string', $array, $constString],
		['string vs lowercase', $string, $lowercase],
		['plain object vs constant string', $plainObject, $constString],
		['plain object vs lowercase', $plainObject, $lowercase],
	];
};
$vlPhpCases = $vlCases(false);
$vlNativeCases = $vlCases(true);
foreach ($vlPhpCases as $vlIndex => [$vlLabel, $vlAccepting, $vlAccepted]) {
	[, $vlNativeAccepting, $vlNativeAccepted] = $vlNativeCases[$vlIndex];
	$vlPhpLevel = \PHPStan\Type\VerbosityLevel::getRecommendedLevelByType($vlAccepting, $vlAccepted);
	$vlNativeLevel = \PHPStanTurbo\VerbosityLevel::getRecommendedLevelByType($vlNativeAccepting, $vlNativeAccepted);
	check($vlPhpLevel->getLevelValue() === $vlNativeLevel->getLevelValue(), sprintf('VerbosityLevel::getRecommendedLevelByType(%s): %d vs %d', $vlLabel, $vlPhpLevel->getLevelValue(), $vlNativeLevel->getLevelValue()));
	check($vlNativeLevel instanceof \PHPStanTurbo\VerbosityLevel, "VerbosityLevel::getRecommendedLevelByType($vlLabel) returns the native singleton");
}
check(\PHPStanTurbo\VerbosityLevel::getRecommendedLevelByType($vlNativeCases[0][1], null) === \PHPStanTurbo\VerbosityLevel::typeOnly(), 'VerbosityLevel::getRecommendedLevelByType() with an explicit null $acceptedType');
check(\PHPStanTurbo\VerbosityLevel::getRecommendedLevelByType(acceptingType: $vlNativeCases[1][1]) === \PHPStanTurbo\VerbosityLevel::value(), 'VerbosityLevel::getRecommendedLevelByType() with a named argument');

// ---- RecursionGuard ----
// Each guard runs its own implementation's types (run() describes the type
// with its VerbosityLevel — the twin's describe(VerbosityLevel $level) takes
// the PHP level only); the observable sequence of results must agree.
$rgObserve = static function (string $guard, \PHPStan\Type\Type $type, \PHPStan\Type\Type $sameDescription, \PHPStan\Type\Type $other): array {
	$log = [];
	$log[] = $guard::run($type, static function () use ($guard, $type, $sameDescription, $other, &$log): string {
		$log[] = 'outer';
		$log[] = get_class($guard::run($type, static fn (): string => 'inner ran'));
		$log[] = get_class($guard::run($sameDescription, static fn (): string => 'same description ran'));
		$log[] = $guard::run($other, static fn (): string => 'other ran');
		return 'outer done';
	});
	$log[] = $guard::run($type, static fn (): string => 'released');
	try {
		$guard::run($type, static function (): void {
			throw new \RuntimeException('boom');
		});
		$log[] = 'no exception';
	} catch (\RuntimeException $e) {
		$log[] = $e->getMessage();
	}
	$log[] = $guard::run($type, static fn (): string => 'released after the exception');
	$log[] = $guard::runOnObjectIdentity($type, static function () use ($guard, $type, $sameDescription, &$log): string {
		$log[] = get_class($guard::runOnObjectIdentity($type, static fn (): string => 'identity inner ran'));
		$log[] = $guard::runOnObjectIdentity($sameDescription, static fn (): string => 'another instance ran');
		$log[] = $guard::run($type, static fn (): string => 'description key next to the identity key');
		return 'identity outer done';
	});
	$log[] = $guard::runOnObjectIdentity($type, static fn (): string => 'identity released');

	return array_map(static fn ($entry) => is_string($entry) ? str_replace('PHPStanTurbo\\', 'PHPStan\\Type\\', $entry) : $entry, $log);
};
$rgTriples = [
	'string' => [
		[new \PHPStan\Type\StringType(), new \PHPStan\Type\StringType(), new \PHPStan\Type\IntegerType()],
		[new \PHPStanTurbo\StringType(), new \PHPStanTurbo\StringType(), new \PHPStanTurbo\IntegerType()],
	],
	'numeric description' => [
		[new \PHPStan\Type\Constant\ConstantIntegerType(5), new \PHPStan\Type\Constant\ConstantIntegerType(5), new \PHPStan\Type\Constant\ConstantIntegerType(6)],
		[new \PHPStanTurbo\ConstantIntegerType(5), new \PHPStanTurbo\ConstantIntegerType(5), new \PHPStanTurbo\ConstantIntegerType(6)],
	],
	'union' => [
		[new \PHPStan\Type\UnionType([new \PHPStan\Type\StringType(), new \PHPStan\Type\NullType()]), new \PHPStan\Type\UnionType([new \PHPStan\Type\StringType(), new \PHPStan\Type\NullType()]), new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\NullType()])],
		[new \PHPStanTurbo\UnionType([new \PHPStanTurbo\StringType(), new \PHPStanTurbo\NullType()]), new \PHPStanTurbo\UnionType([new \PHPStanTurbo\StringType(), new \PHPStanTurbo\NullType()]), new \PHPStanTurbo\UnionType([new \PHPStanTurbo\IntegerType(), new \PHPStanTurbo\NullType()])],
	],
];
foreach ($rgTriples as $rgLabel => [$rgPhp, $rgNative]) {
	$rgPhpLog = $rgObserve(\PHPStan\Type\RecursionGuard::class, ...$rgPhp);
	$rgNativeLog = $rgObserve(\PHPStanTurbo\RecursionGuard::class, ...$rgNative);
	check($rgPhpLog === $rgNativeLog, "RecursionGuard: $rgLabel " . json_encode($rgPhpLog) . ' vs ' . json_encode($rgNativeLog));
	check(in_array('PHPStan\\Type\\ErrorType', $rgNativeLog, true), "RecursionGuard: $rgLabel short-circuited to ErrorType somewhere");
}
$rgErrors = static function (string $guard, \PHPStan\Type\Type $type): string {
	try {
		$guard::run($type, 'no-such-function-anywhere');
		return 'none';
	} catch (\Throwable $e) {
		return get_class($e);
	}
};
check($rgErrors(\PHPStan\Type\RecursionGuard::class, new \PHPStan\Type\StringType()) === $rgErrors(\PHPStanTurbo\RecursionGuard::class, new \PHPStanTurbo\StringType()), 'RecursionGuard: a non-callable $callback throws the same');

// ---- FiniteTypeSet ----
// Sets built from the same lists of types on both sides (PHP types for the
// PHP set, the native classes for the native one — the kinds are class
// names), every query observed.
/** @return array<string, \PHPStan\Type\Type> */
$ftsTypes = static function (bool $native) use ($vlClass): array {
	$c = static fn (string $phpClass): string => $vlClass($phpClass, $native);
	return [
		'a' => new ($c(\PHPStan\Type\Constant\ConstantStringType::class))('a'),
		'b' => new ($c(\PHPStan\Type\Constant\ConstantStringType::class))('b'),
		'a again' => new ($c(\PHPStan\Type\Constant\ConstantStringType::class))('a'),
		'numeric string' => new ($c(\PHPStan\Type\Constant\ConstantStringType::class))('1'),
		'1' => new ($c(\PHPStan\Type\Constant\ConstantIntegerType::class))(1),
		'-3' => new ($c(\PHPStan\Type\Constant\ConstantIntegerType::class))(-3),
		'true' => new ($c(\PHPStan\Type\Constant\ConstantBooleanType::class))(true),
		'false' => new ($c(\PHPStan\Type\Constant\ConstantBooleanType::class))(false),
		'null' => new ($c(\PHPStan\Type\NullType::class))(),
		'float' => new ($c(\PHPStan\Type\Constant\ConstantFloatType::class))(1.5),
		'enum hearts' => new ($c(\PHPStan\Type\Enum\EnumCaseObjectType::class))('App\\Suit', 'Hearts'),
		'enum spades' => new ($c(\PHPStan\Type\Enum\EnumCaseObjectType::class))('App\\Suit', 'Spades'),
		'enum red' => new ($c(\PHPStan\Type\Enum\EnumCaseObjectType::class))('App\\Color', 'Red'),
		'string' => new ($c(\PHPStan\Type\StringType::class))(),
		'object' => new ($c(\PHPStan\Type\ObjectType::class))(\stdClass::class),
		'union' => new ($c(\PHPStan\Type\UnionType::class))([new ($c(\PHPStan\Type\Constant\ConstantIntegerType::class))(7), new ($c(\PHPStan\Type\Constant\ConstantIntegerType::class))(8)]),
		'intersection' => new ($c(\PHPStan\Type\IntersectionType::class))([new ($c(\PHPStan\Type\StringType::class))(), new ($c(\PHPStan\Type\Accessory\AccessoryNonEmptyStringType::class))()]),
		'template' => \PHPStan\Type\Generic\TemplateTypeFactory::create(\PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('f'), 'T', null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
	];
};
$ftsObserve = static function (string $class, array $types): array {
	$describe = static fn (\PHPStan\Type\Type $type): string => $type->describe(\PHPStan\Type\VerbosityLevel::precise());
	$observations = [];
	foreach ($types as $label => $type) {
		$observations["key $label"] = $class::key($type);
	}
	$lists = [
		'scalars' => ['a', 'b', 'numeric string', '1', '-3', 'true', 'false', 'null'],
		'duplicates' => ['a', 'b', 'a again', '1'],
		'mixed' => ['a', 'string', '1', 'object', 'null'],
		'enums' => ['enum hearts', 'enum spades', 'enum red', 'a'],
		'unkeyed' => ['string', 'object', 'float', 'union', 'intersection', 'template'],
		'float next to a' => ['float', 'a'],
		'one' => ['a'],
		'a and b' => ['a', 'b'],
		'b and 1' => ['b', '1'],
	];
	$sets = [];
	foreach ($lists as $name => $labels) {
		$set = $class::create(array_map(static fn (string $label): \PHPStan\Type\Type => $types[$label], $labels));
		$sets[$name] = $set;
		if ($set === null) {
			$observations["create $name"] = null;
			continue;
		}
		$observations["create $name"] = [
			'members' => array_map($describe, $set->getMembers()),
			'others' => array_map($describe, $set->getOthers()),
			'complete' => $set->isComplete(),
			'has s:a' => $set->has('s:a'),
			'has s:x' => $set->has('s:x'),
			'has null' => $set->has('null'),
			'has i:1' => $set->has('i:1'),
			'representatives of a' => array_map($describe, $set->getRepresentativesOfOtherKinds($types['a'])),
			'representatives of enum hearts' => array_map($describe, $set->getRepresentativesOfOtherKinds($types['enum hearts'])),
			'representatives of enum red' => array_map($describe, $set->getRepresentativesOfOtherKinds($types['enum red'])),
			'representatives of string' => array_map($describe, $set->getRepresentativesOfOtherKinds($types['string'])),
			'representatives of union' => array_map($describe, $set->getRepresentativesOfOtherKinds($types['union'])),
			'containedInKey s:a' => $set->containedInKey('s:a')->describe(),
			'containedInKey i:1' => $set->containedInKey('i:1')->describe(),
			'containedInKey s:zzz' => $set->containedInKey('s:zzz')->describe(),
		];
	}
	foreach ($sets as $left => $leftSet) {
		foreach ($sets as $right => $rightSet) {
			if ($leftSet === null || $rightSet === null) {
				continue;
			}
			$observations["containedIn $left / $right"] = $leftSet->containedIn($rightSet)->describe();
		}
	}
	$observations['native TrinaryLogic'] = $sets['one']->containedIn($sets['one']) instanceof \PHPStanTurbo\TrinaryLogic;

	return $observations;
};
$ftsPhp = $ftsObserve(\PHPStan\Type\FiniteTypeSet::class, $ftsTypes(false));
$ftsNative = $ftsObserve(\PHPStanTurbo\FiniteTypeSet::class, $ftsTypes(true));
check($ftsPhp['native TrinaryLogic'] === false && $ftsNative['native TrinaryLogic'] === true, 'FiniteTypeSet: the native set answers with the native TrinaryLogic');
unset($ftsPhp['native TrinaryLogic'], $ftsNative['native TrinaryLogic']);
check(array_keys($ftsPhp) === array_keys($ftsNative), 'FiniteTypeSet: both sides observed the same keys');
foreach ($ftsPhp as $ftsKey => $ftsExpected) {
	$ftsActual = array_key_exists($ftsKey, $ftsNative) ? $ftsNative[$ftsKey] : '<missing>';
	check($ftsExpected === $ftsActual, "FiniteTypeSet $ftsKey: " . json_encode($ftsExpected) . ' vs ' . json_encode($ftsActual));
}
check(count($ftsPhp) > 60, 'FiniteTypeSet: a substantial number of observations (' . count($ftsPhp) . ')');
$ftsErrors = static function (string $class, array $types): array {
	$errors = [];
	try {
		$class::create([$types['a'], 'not a type']);
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	try {
		$class::create([$types['a']])->containedIn(new \stdClass());
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	return $errors;
};
check($ftsErrors(\PHPStan\Type\FiniteTypeSet::class, $ftsTypes(false)) === $ftsErrors(\PHPStanTurbo\FiniteTypeSet::class, $ftsTypes(true)), 'FiniteTypeSet: a non-Type member and a foreign containedIn() argument throw the same (' . implode(', ', $ftsErrors(\PHPStanTurbo\FiniteTypeSet::class, $ftsTypes(true))) . ')');

$covered[\PHPStan\Type\TypeTraverser::class] = true;
$covered[\PHPStan\Type\VerbosityLevel::class] = true;
$covered[\PHPStan\Type\RecursionGuard::class] = true;
$covered[\PHPStan\Type\FiniteTypeSet::class] = true;
$covered[\PHPStan\Type\TypeCombinator::class] = true;

// ---- TemplateTypeVariance ----
// The five singletons and their queries, compose()/equals()/validPosition()
// over every pair, describe() and toPhpDocNodeVariance() (a static variance
// has no node variance: the same exception on both sides). isValidVariance()
// needs a Type graph on one implementation and is observed under the real
// names in tests/type-family.php.
$ttvFactories = ['createInvariant', 'createCovariant', 'createContravariant', 'createStatic', 'createBivariant'];
$ttvPhp = [];
$ttvNative = [];
foreach ($ttvFactories as $ttvFactory) {
	$ttvPhp[$ttvFactory] = \PHPStan\Type\Generic\TemplateTypeVariance::$ttvFactory();
	$ttvNative[$ttvFactory] = \PHPStanTurbo\TemplateTypeVariance::$ttvFactory();
	check($ttvNative[$ttvFactory] === \PHPStanTurbo\TemplateTypeVariance::$ttvFactory(), "TemplateTypeVariance::$ttvFactory() identity");
	check($ttvNative[$ttvFactory] instanceof \PHPStanTurbo\TemplateTypeVariance, "TemplateTypeVariance::$ttvFactory() is the native class");
	foreach (['invariant', 'covariant', 'contravariant', 'static', 'bivariant'] as $ttvQuery) {
		check($ttvPhp[$ttvFactory]->$ttvQuery() === $ttvNative[$ttvFactory]->$ttvQuery(), "TemplateTypeVariance::$ttvFactory() $ttvQuery()");
	}
	check($ttvPhp[$ttvFactory]->describe() === $ttvNative[$ttvFactory]->describe(), "TemplateTypeVariance::$ttvFactory() describe()");
	$ttvNodeVariance = static function (object $variance): string {
		try {
			return $variance->toPhpDocNodeVariance();
		} catch (\Throwable $e) {
			return get_class($e) . ': ' . $e->getMessage();
		}
	};
	check($ttvNodeVariance($ttvPhp[$ttvFactory]) === $ttvNodeVariance($ttvNative[$ttvFactory]), "TemplateTypeVariance::$ttvFactory() toPhpDocNodeVariance() (" . $ttvNodeVariance($ttvNative[$ttvFactory]) . ')');
}
foreach ($ttvFactories as $ttvLeft) {
	foreach ($ttvFactories as $ttvRight) {
		$ttvPhpComposed = $ttvPhp[$ttvLeft]->compose($ttvPhp[$ttvRight]);
		$ttvNativeComposed = $ttvNative[$ttvLeft]->compose($ttvNative[$ttvRight]);
		check($ttvPhpComposed->describe() === $ttvNativeComposed->describe(), "TemplateTypeVariance $ttvLeft compose $ttvRight: " . $ttvPhpComposed->describe() . ' vs ' . $ttvNativeComposed->describe());
		check(($ttvPhpComposed === $ttvPhp[$ttvRight]) === ($ttvNativeComposed === $ttvNative[$ttvRight]), "TemplateTypeVariance $ttvLeft compose $ttvRight returns the operand itself on both sides or neither");
		check($ttvNativeComposed === \PHPStanTurbo\TemplateTypeVariance::{'create' . ucfirst($ttvNativeComposed->describe())}(), "TemplateTypeVariance $ttvLeft compose $ttvRight is a singleton");
		check($ttvPhp[$ttvLeft]->equals($ttvPhp[$ttvRight]) === $ttvNative[$ttvLeft]->equals($ttvNative[$ttvRight]), "TemplateTypeVariance $ttvLeft equals $ttvRight");
		check($ttvPhp[$ttvLeft]->validPosition($ttvPhp[$ttvRight]) === $ttvNative[$ttvLeft]->validPosition($ttvNative[$ttvRight]), "TemplateTypeVariance $ttvLeft validPosition $ttvRight");
	}
}
$ttvErrors = static function (string $class): array {
	$errors = [];
	try {
		$class::createCovariant()->compose(new \stdClass());
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	try {
		$class::createCovariant()->equals(new \stdClass());
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	return $errors;
};
check($ttvErrors(\PHPStan\Type\Generic\TemplateTypeVariance::class) === $ttvErrors(\PHPStanTurbo\TemplateTypeVariance::class), 'TemplateTypeVariance: a foreign operand throws the same (' . implode(', ', $ttvErrors(\PHPStanTurbo\TemplateTypeVariance::class)) . ')');

$covered[\PHPStan\Type\Generic\TemplateTypeVariance::class] = true;

// ---- TemplateTypeVarianceMap ----
// The empty singleton and the lookups over a map of both sides' variances.
$ttvmPhp = new \PHPStan\Type\Generic\TemplateTypeVarianceMap(['T' => $ttvPhp['createCovariant'], 'U' => $ttvPhp['createInvariant'], '5' => $ttvPhp['createStatic']]);
$ttvmNative = new \PHPStanTurbo\TemplateTypeVarianceMap(['T' => $ttvNative['createCovariant'], 'U' => $ttvNative['createInvariant'], '5' => $ttvNative['createStatic']]);
check(\PHPStanTurbo\TemplateTypeVarianceMap::createEmpty() === \PHPStanTurbo\TemplateTypeVarianceMap::createEmpty(), 'TemplateTypeVarianceMap::createEmpty() identity');
check(\PHPStanTurbo\TemplateTypeVarianceMap::createEmpty()->getVariances() === [], 'TemplateTypeVarianceMap::createEmpty() is empty');
check(array_keys($ttvmPhp->getVariances()) === array_keys($ttvmNative->getVariances()), 'TemplateTypeVarianceMap::getVariances() keys');
foreach (['T', 'U', '5', 'V', ''] as $ttvmName) {
	check($ttvmPhp->hasVariance($ttvmName) === $ttvmNative->hasVariance($ttvmName), "TemplateTypeVarianceMap::hasVariance($ttvmName)");
	check(($ttvmPhp->getVariance($ttvmName)?->describe()) === ($ttvmNative->getVariance($ttvmName)?->describe()), "TemplateTypeVarianceMap::getVariance($ttvmName)");
}
check($ttvmNative->getVariance('T') === $ttvNative['createCovariant'], 'TemplateTypeVarianceMap::getVariance() hands out the stored instance');
$covered[\PHPStan\Type\Generic\TemplateTypeVarianceMap::class] = true;

// ---- TemplateTypeMap ----
// Every operation over maps of upper and lower bounds, absorbed arguments
// and template types, each side built from its own Type classes (the
// native map probes NeverType by class entry); Type values compared by
// class modulo the prefix and by description.
$ttmClass = static fn (string $phpClass, bool $native): string => $native ? $shadowedClasses[$phpClass]['turboClass'] : $phpClass;
$ttmScope = \PHPStan\Type\Generic\TemplateTypeScope::createWithClass('Foo');
/** @return array<string, object> */
$ttmMaps = static function (bool $native) use ($ttmClass, $ttmScope): array {
	$c = static fn (string $phpClass): string => $ttmClass($phpClass, $native);
	$mapClass = $c(\PHPStan\Type\Generic\TemplateTypeMap::class);
	$int = new ($c(\PHPStan\Type\IntegerType::class))();
	$string = new ($c(\PHPStan\Type\StringType::class))();
	$constInt = new ($c(\PHPStan\Type\Constant\ConstantIntegerType::class))(1);
	$never = new ($c(\PHPStan\Type\NeverType::class))();
	$t = \PHPStan\Type\Generic\TemplateTypeFactory::create($ttmScope, 'T', null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
	$u = \PHPStan\Type\Generic\TemplateTypeFactory::create($ttmScope, 'U', new \PHPStan\Type\IntegerType(), \PHPStan\Type\Generic\TemplateTypeVariance::createCovariant(), null, new \PHPStan\Type\Constant\ConstantIntegerType(5));
	$absorbed = new ($c(\PHPStan\Type\Generic\AbsorbedTemplateArgumentType::class))(); /* shadowed: each side its own class */
	return [
		'empty' => $mapClass::createEmpty(),
		'upper' => new $mapClass(['T' => $int, 'U' => $string]),
		'lower' => new $mapClass([], ['T' => $string, 'V' => $constInt]),
		'both' => new $mapClass(['T' => $int, 'V' => $string], ['T' => $constInt, 'U' => $int]),
		'templates' => new $mapClass(['T' => $t, 'U' => $u]),
		'absorbed' => new $mapClass(['T' => $absorbed, 'U' => $int]),
		'disjoint lower' => new $mapClass(['T' => $int], ['U' => $never]),
	];
};
$ttmPhpMaps = $ttmMaps(false);
$ttmNativeMaps = $ttmMaps(true);
$ttmView = static function (mixed $v) use (&$ttmView, $turboNorm): mixed {
	if ($v instanceof \PHPStan\Type\Type) {
		return [$turboNorm(get_class($v)), $v->describe(\PHPStan\Type\VerbosityLevel::precise())];
	}
	if (is_array($v)) {
		return array_map($ttmView, $v);
	}
	if (is_object($v)) {
		return $turboNorm(get_class($v));
	}
	return $v;
};
check(\PHPStanTurbo\TemplateTypeMap::createEmpty() === \PHPStanTurbo\TemplateTypeMap::createEmpty(), 'TemplateTypeMap::createEmpty() identity');
foreach ($ttmPhpMaps as $ttmLabel => $ttmPhp) {
	$ttmNative = $ttmNativeMaps[$ttmLabel];
	check($ttmNative instanceof \PHPStanTurbo\TemplateTypeMap, "TemplateTypeMap $ttmLabel is the native class");
	check($ttmView($ttmPhp->getTypes()) === $ttmView($ttmNative->getTypes()), "TemplateTypeMap $ttmLabel getTypes(): " . json_encode($ttmView($ttmNative->getTypes())));
	check($ttmPhp->count() === $ttmNative->count() && $ttmPhp->isEmpty() === $ttmNative->isEmpty(), "TemplateTypeMap $ttmLabel count()/isEmpty()");
	foreach (['T', 'U', 'V', '5', 'X', ''] as $ttmName) {
		check($ttmPhp->hasType($ttmName) === $ttmNative->hasType($ttmName), "TemplateTypeMap $ttmLabel hasType($ttmName)");
		check($ttmView($ttmPhp->getType($ttmName)) === $ttmView($ttmNative->getType($ttmName)), "TemplateTypeMap $ttmLabel getType($ttmName)");
		check($ttmView($ttmPhp->unsetType($ttmName)->getTypes()) === $ttmView($ttmNative->unsetType($ttmName)->getTypes()), "TemplateTypeMap $ttmLabel unsetType($ttmName)");
		check(($ttmPhp->unsetType($ttmName) === $ttmPhp) === ($ttmNative->unsetType($ttmName) === $ttmNative), "TemplateTypeMap $ttmLabel unsetType($ttmName) returns \$this on both sides or neither");
		check(($ttmPhp->unsetType($ttmName) === \PHPStan\Type\Generic\TemplateTypeMap::createEmpty()) === ($ttmNative->unsetType($ttmName) === \PHPStanTurbo\TemplateTypeMap::createEmpty()), "TemplateTypeMap $ttmLabel unsetType($ttmName) returns the empty singleton on both sides or neither");
	}
	check($ttmView($ttmPhp->convertToLowerBoundTypes()->getTypes()) === $ttmView($ttmNative->convertToLowerBoundTypes()->getTypes()), "TemplateTypeMap $ttmLabel convertToLowerBoundTypes()");
	check($ttmPhp->convertToLowerBoundTypes()->count() === $ttmNative->convertToLowerBoundTypes()->count(), "TemplateTypeMap $ttmLabel convertToLowerBoundTypes() count");
	check($ttmView($ttmPhp->resolveToBounds()->getTypes()) === $ttmView($ttmNative->resolveToBounds()->getTypes()), "TemplateTypeMap $ttmLabel resolveToBounds(): " . json_encode($ttmView($ttmNative->resolveToBounds()->getTypes())));
	check($ttmNative->resolveToBounds() === $ttmNative->resolveToBounds(), "TemplateTypeMap $ttmLabel resolveToBounds() is memoized");
	$ttmMapper = static fn (string $name, \PHPStan\Type\Type $type): \PHPStan\Type\Type => $name === 'T' ? new \PHPStan\Type\NullType() : $type;
	check($ttmView($ttmPhp->map($ttmMapper)->getTypes()) === $ttmView($ttmNative->map($ttmMapper)->getTypes()), "TemplateTypeMap $ttmLabel map()");
	// the set operations combine the members through TypeCombinator, where a
	// native Type meeting a PHP TemplateType is a TypeError in this prefixed
	// declaration — the template-bearing maps combine under the real names in
	// tests/type-family.php; an absorbed argument yields before TypeCombinator
	// runs, so it pairs with the others in the unions
	foreach ($ttmPhpMaps as $ttmOtherLabel => $ttmOtherPhp) {
		$ttmOtherNative = $ttmNativeMaps[$ttmOtherLabel];
		if ($ttmLabel === 'templates' || $ttmOtherLabel === 'templates') {
			continue;
		}
		foreach (['union', 'benevolentUnion', 'intersect'] as $ttmOp) {
			if ($ttmOp === 'intersect' && ($ttmLabel === 'absorbed' || $ttmOtherLabel === 'absorbed')) {
				continue;
			}
			$ttmPhpResult = $ttmPhp->$ttmOp($ttmOtherPhp);
			$ttmNativeResult = $ttmNative->$ttmOp($ttmOtherNative);
			check($ttmView($ttmPhpResult->getTypes()) === $ttmView($ttmNativeResult->getTypes()) && $ttmPhpResult->count() === $ttmNativeResult->count(), "TemplateTypeMap $ttmLabel $ttmOp $ttmOtherLabel: " . json_encode($ttmView($ttmPhpResult->getTypes())) . ' vs ' . json_encode($ttmView($ttmNativeResult->getTypes())));
			// the lower bounds of a result are not converted here: the never
			// TypeCombinator::intersect() answers for disjoint bounds is the PHP
			// NeverType in this prefixed declaration, which the native map cannot
			// tell from any other type — observed under the real names in
			// tests/type-family.php ("template map ... convertToLowerBoundTypes")
		}
	}
}
$ttmErrors = static function (string $class): array {
	$errors = [];
	try {
		$class::createEmpty()->union(new \stdClass());
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	try {
		$class::createEmpty()->map('no such function');
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	try {
		(new ReflectionClass($class))->newInstanceWithoutConstructor()->getTypes();
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	return $errors;
};
check($ttmErrors(\PHPStan\Type\Generic\TemplateTypeMap::class) === $ttmErrors(\PHPStanTurbo\TemplateTypeMap::class), 'TemplateTypeMap: a foreign operand, a non-callable and an unconstructed instance throw the same (' . implode(', ', $ttmErrors(\PHPStanTurbo\TemplateTypeMap::class)) . ')');
$covered[\PHPStan\Type\Generic\TemplateTypeMap::class] = true;

// ---- TemplateTypeScope ----
// The four factories, the getters, equals() over every pair and describe().
$ttsScopes = static fn (string $class): array => [
	'anonymous' => $class::createWithAnonymousFunction(),
	'function foo' => $class::createWithFunction('foo'),
	'function bar' => $class::createWithFunction('bar'),
	'method Foo::bar' => $class::createWithMethod('Foo', 'bar'),
	'method Foo::baz' => $class::createWithMethod('Foo', 'baz'),
	'method Bar::bar' => $class::createWithMethod('Bar', 'bar'),
	'class Foo' => $class::createWithClass('Foo'),
	'class Bar' => $class::createWithClass('Bar'),
];
$ttsPhp = $ttsScopes(\PHPStan\Type\Generic\TemplateTypeScope::class);
$ttsNative = $ttsScopes(\PHPStanTurbo\TemplateTypeScope::class);
foreach ($ttsPhp as $ttsLabel => $ttsPhpScope) {
	$ttsNativeScope = $ttsNative[$ttsLabel];
	check($ttsNativeScope instanceof \PHPStanTurbo\TemplateTypeScope, "TemplateTypeScope $ttsLabel is the native class");
	check($ttsPhpScope->getClassName() === $ttsNativeScope->getClassName(), "TemplateTypeScope $ttsLabel getClassName()");
	check($ttsPhpScope->getFunctionName() === $ttsNativeScope->getFunctionName(), "TemplateTypeScope $ttsLabel getFunctionName()");
	check($ttsPhpScope->describe() === $ttsNativeScope->describe(), "TemplateTypeScope $ttsLabel describe(): " . $ttsNativeScope->describe());
	foreach ($ttsPhp as $ttsOtherLabel => $ttsOtherPhp) {
		check($ttsPhpScope->equals($ttsOtherPhp) === $ttsNativeScope->equals($ttsNative[$ttsOtherLabel]), "TemplateTypeScope $ttsLabel equals $ttsOtherLabel");
	}
}
$ttsErrors = static function (string $class): array {
	$errors = [];
	try {
		$class::createWithClass('Foo')->equals(new \stdClass());
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	try {
		new $class('Foo', null);
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	try {
		(new ReflectionClass($class))->newInstanceWithoutConstructor()->describe();
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	return $errors;
};
check($ttsErrors(\PHPStan\Type\Generic\TemplateTypeScope::class) === $ttsErrors(\PHPStanTurbo\TemplateTypeScope::class), 'TemplateTypeScope: a foreign operand, the private constructor and an unconstructed instance throw the same (' . implode(', ', $ttsErrors(\PHPStanTurbo\TemplateTypeScope::class)) . ')');
$covered[\PHPStan\Type\Generic\TemplateTypeScope::class] = true;

// ---- TemplateTypeReference ----
// A pair of a template type and a variance, handed back as given.
$ttrTemplate = \PHPStan\Type\Generic\TemplateTypeFactory::create(\PHPStan\Type\Generic\TemplateTypeScope::createWithClass('Foo'), 'T', null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
$ttrPhp = new \PHPStan\Type\Generic\TemplateTypeReference($ttrTemplate, $ttvPhp['createContravariant']);
$ttrNative = new \PHPStanTurbo\TemplateTypeReference($ttrTemplate, $ttvNative['createContravariant']);
check($ttrPhp->getType() === $ttrTemplate && $ttrNative->getType() === $ttrTemplate, 'TemplateTypeReference::getType() hands back the template type');
check($ttrPhp->getPositionVariance() === $ttvPhp['createContravariant'] && $ttrNative->getPositionVariance() === $ttvNative['createContravariant'], 'TemplateTypeReference::getPositionVariance() hands back the variance');
check($ttrPhp->getPositionVariance()->describe() === $ttrNative->getPositionVariance()->describe(), 'TemplateTypeReference::getPositionVariance() describe()');
$ttrErrors = static function (string $class, object $variance) use ($ttrTemplate): array {
	$errors = [];
	try {
		new $class(new \PHPStan\Type\IntegerType(), $variance);
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	try {
		new $class($ttrTemplate, new \stdClass());
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	try {
		(new ReflectionClass($class))->newInstanceWithoutConstructor()->getType();
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	return $errors;
};
check($ttrErrors(\PHPStan\Type\Generic\TemplateTypeReference::class, $ttvPhp['createInvariant']) === $ttrErrors(\PHPStanTurbo\TemplateTypeReference::class, $ttvNative['createInvariant']), 'TemplateTypeReference: a non-template type, a foreign variance and an unconstructed instance throw the same (' . implode(', ', $ttrErrors(\PHPStanTurbo\TemplateTypeReference::class, $ttvNative['createInvariant'])) . ')');
$covered[\PHPStan\Type\Generic\TemplateTypeReference::class] = true;

// ---- TemplateTypeHelper ----
// The traversals over bare template types (the template types are PHP
// classes on both sides; the compounds they sit in need one Type graph per
// process and are observed under the real names in tests/type-family.php),
// generalizeInferredTemplateType() over each side's constant types, and
// the failure modes.
$tthScope = \PHPStan\Type\Generic\TemplateTypeScope::createWithClass('Foo');
$tthT = \PHPStan\Type\Generic\TemplateTypeFactory::create($tthScope, 'T', null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
$tthU = \PHPStan\Type\Generic\TemplateTypeFactory::create($tthScope, 'U', new \PHPStan\Type\IntegerType(), \PHPStan\Type\Generic\TemplateTypeVariance::createCovariant(), null, new \PHPStan\Type\Constant\ConstantIntegerType(5));
$tthK = \PHPStan\Type\Generic\TemplateTypeFactory::create($tthScope, 'K', new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
$tthAnonymous = \PHPStan\Type\Generic\TemplateTypeFactory::create(\PHPStan\Type\Generic\TemplateTypeScope::createWithAnonymousFunction(), 'A', null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant());
$tthView = static fn (\PHPStan\Type\Type $type): array => [$turboNorm(get_class($type)), $type->describe(\PHPStan\Type\VerbosityLevel::precise())];
foreach (['T' => $tthT, 'U' => $tthU, 'K' => $tthK, 'A' => $tthAnonymous, 'T argument' => $tthT->toArgument()] as $tthLabel => $tthSubject) {
	foreach (['resolveToBounds', 'resolveToDefaults', 'toArgument', 'removeFinalByKeywordOverrides'] as $tthMethod) {
		$tthPhpResult = \PHPStan\Type\Generic\TemplateTypeHelper::$tthMethod($tthSubject);
		$tthNativeResult = \PHPStanTurbo\TemplateTypeHelper::$tthMethod($tthSubject);
		check($tthView($tthPhpResult) === $tthView($tthNativeResult), "TemplateTypeHelper::$tthMethod($tthLabel): " . json_encode($tthView($tthPhpResult)) . ' vs ' . json_encode($tthView($tthNativeResult)));
		check(($tthPhpResult === $tthSubject) === ($tthNativeResult === $tthSubject), "TemplateTypeHelper::$tthMethod($tthLabel) hands the subject back on both sides or neither");
	}
	// resolveTemplateTypes() hands the position variance to the subject's
	// getReferencedTemplateTypes(TemplateTypeVariance $positionVariance) — a
	// native variance meeting a PHP template type's typed parameter is a
	// TypeError in this prefixed declaration, so the resolution is observed
	// under the real names in tests/type-family.php ("template helper
	// resolveTemplateTypes ...")
}
// generalizeInferredTemplateType() describes the template's bound with the
// helper's own VerbosityLevel — the native level meeting a PHP bound's typed
// describe() parameter is a TypeError in this prefixed declaration, so the
// generalization is observed under the real names in tests/type-family.php
// ("template helper generalizeInferredTemplateType ...")
$tthErrors = static function (string $helper, string $mapClass, string $varianceMapClass, object $variance) use ($tthT): array {
	$errors = [];
	try {
		$helper::resolveTemplateTypes($tthT, new \stdClass(), $varianceMapClass::createEmpty(), $variance);
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	try {
		$helper::resolveTemplateTypes($tthT, $mapClass::createEmpty(), $varianceMapClass::createEmpty(), new \stdClass());
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	try {
		$helper::resolveToBounds(new \stdClass());
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	return $errors;
};
check($tthErrors(\PHPStan\Type\Generic\TemplateTypeHelper::class, \PHPStan\Type\Generic\TemplateTypeMap::class, \PHPStan\Type\Generic\TemplateTypeVarianceMap::class, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()) === $tthErrors(\PHPStanTurbo\TemplateTypeHelper::class, \PHPStanTurbo\TemplateTypeMap::class, \PHPStanTurbo\TemplateTypeVarianceMap::class, $ttvNative['createInvariant']), 'TemplateTypeHelper: foreign arguments throw the same (' . implode(', ', $tthErrors(\PHPStanTurbo\TemplateTypeHelper::class, \PHPStanTurbo\TemplateTypeMap::class, \PHPStanTurbo\TemplateTypeVarianceMap::class, $ttvNative['createInvariant'])) . ')');
$covered[\PHPStan\Type\Generic\TemplateTypeHelper::class] = true;
$covered[\PHPStan\Type\KeyOfType::class] = true;
$covered[\PHPStan\Type\ValueOfType::class] = true;
$covered[\PHPStan\Type\OffsetAccessType::class] = true;
$covered[\PHPStan\Type\ClassConstantAccessType::class] = true;
$covered[\PHPStan\Type\NewObjectType::class] = true;
$covered[\PHPStan\Type\ConditionalType::class] = true;
$covered[\PHPStan\Type\ConditionalTypeForParameter::class] = true;
$covered[\PHPStan\Type\LateResolvableArrayShapeType::class] = true;
$covered[\PHPStan\Type\Generic\UnresolvedTemplateArgumentType::class] = true;
$covered[\PHPStan\Type\Generic\TemplateArrayType::class] = true;
$covered[\PHPStan\Type\Generic\TemplateBenevolentUnionType::class] = true;
$covered[\PHPStan\Type\Generic\TemplateBooleanType::class] = true;
$covered[\PHPStan\Type\Generic\TemplateConstantArrayType::class] = true;
$covered[\PHPStan\Type\Generic\TemplateConstantIntegerType::class] = true;
$covered[\PHPStan\Type\Generic\TemplateConstantStringType::class] = true;
$covered[\PHPStan\Type\Generic\TemplateFloatType::class] = true;
$covered[\PHPStan\Type\Generic\TemplateGenericObjectType::class] = true;
$covered[\PHPStan\Type\Generic\TemplateIntegerType::class] = true;
$covered[\PHPStan\Type\Generic\TemplateIntersectionType::class] = true;
$covered[\PHPStan\Type\Generic\TemplateIterableType::class] = true;
$covered[\PHPStan\Type\Generic\TemplateMixedType::class] = true;
$covered[\PHPStan\Type\Generic\TemplateNullType::class] = true;
$covered[\PHPStan\Type\Generic\TemplateObjectShapeType::class] = true;
$covered[\PHPStan\Type\Generic\TemplateObjectType::class] = true;
$covered[\PHPStan\Type\Generic\TemplateObjectWithoutClassType::class] = true;
$covered[\PHPStan\Type\Generic\TemplateStrictMixedType::class] = true;
$covered[\PHPStan\Type\Generic\TemplateStringType::class] = true;
$covered[\PHPStan\Type\Generic\TemplateUnionType::class] = true;
$covered[\PHPStan\Type\Generic\TemplateTypeArgumentStrategy::class] = true;
$covered[\PHPStan\Type\Generic\TemplateTypeParameterStrategy::class] = true;
$covered[\PHPStan\Type\Generic\TemplateTypeFactory::class] = true;
$covered[\PHPStan\Type\Generic\TypeProjectionHelper::class] = true;
$covered[\PHPStan\Type\Generic\TemplateKeyOfType::class] = true;
$covered[\PHPStan\Type\Helper\GetTemplateTypeType::class] = true;

// ---- TemplateTypeArgumentStrategy / TemplateTypeParameterStrategy ----
// The differential proper runs under the real names in type-family.php (the
// strategies feed the template types' accepts()); here the prefixed natives
// answer for PHP template types and PHP right-hand types — only the answers
// that do not depend on which implementation the operands are: the
// argument strategy's `->and(AcceptsResult::createMaybe())` over a
// non-compound right-hand type would hand the PHP result the native maybe
// (a TypeError in this prefixed declaration), so that strategy is compared
// over compound types only.
$stratScope = \PHPStan\Type\Generic\TemplateTypeScope::createWithFunction('strat');
$stratLefts = [
	'mixed' => \PHPStan\Type\Generic\TemplateTypeFactory::create($stratScope, 'T', null, \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
	'int' => \PHPStan\Type\Generic\TemplateTypeFactory::create($stratScope, 'T', new \PHPStan\Type\IntegerType(), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
	'object' => \PHPStan\Type\Generic\TemplateTypeFactory::create($stratScope, 'T', new \PHPStan\Type\ObjectType(\Exception::class), \PHPStan\Type\Generic\TemplateTypeVariance::createCovariant()),
	'union' => \PHPStan\Type\Generic\TemplateTypeFactory::create($stratScope, 'T', new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
];
$stratRights = [
	'int' => new \PHPStan\Type\IntegerType(),
	'int1' => new \PHPStan\Type\Constant\ConstantIntegerType(1),
	'string' => new \PHPStan\Type\StringType(),
	'mixed' => new \PHPStan\Type\MixedType(),
	'exception' => new \PHPStan\Type\ObjectType(\Exception::class),
	'std' => new \PHPStan\Type\ObjectType(\stdClass::class),
	'union' => new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]),
	'unionNullable' => new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\NullType()]),
	'intersection' => new \PHPStan\Type\IntersectionType([new \PHPStan\Type\ObjectType(\Countable::class), new \PHPStan\Type\ObjectType(\Traversable::class)]),
	'template' => \PHPStan\Type\Generic\TemplateTypeFactory::create($stratScope, 'U', new \PHPStan\Type\IntegerType(), \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant()),
	'never' => new \PHPStan\Type\NeverType(),
];
$stratShape = static fn (\PHPStan\Type\AcceptsResult $result): array => [$result->result->describe(), $result->reasons];
foreach (['Argument', 'Parameter'] as $stratKind) {
	$stratPhpClass = '\\PHPStan\\Type\\Generic\\TemplateType' . $stratKind . 'Strategy';
	$stratNativeClass = '\\PHPStanTurbo\\TemplateType' . $stratKind . 'Strategy';
	$stratPhp = new $stratPhpClass();
	$stratNative = new $stratNativeClass();
	check($stratPhp->isArgument() === $stratNative->isArgument(), "TemplateType{$stratKind}Strategy isArgument");
	check($stratNative instanceof \PHPStan\Type\Generic\TemplateTypeStrategy && (new ReflectionClass($stratNative))->isFinal(), "TemplateType{$stratKind}Strategy: the native class implements the interface and is final");
	foreach ($stratLefts as $leftName => $left) {
		foreach ($stratRights as $rightName => $right) {
			if ($stratKind === 'Argument' && !$right instanceof \PHPStan\Type\CompoundType) {
				continue;
			}
			foreach ([true, false] as $strict) {
				$expected = $stratShape($stratPhp->accepts($left, $right, $strict));
				$actual = $stratShape($stratNative->accepts($left, $right, $strict));
				check($expected === $actual, "TemplateType{$stratKind}Strategy accepts $leftName $rightName " . var_export($strict, true) . ': ' . json_encode($expected) . ' vs ' . json_encode($actual));
			}
		}
	}
	try {
		$stratNative->accepts($stratRights['int'], $stratRights['int'], true);
		check(false, "TemplateType{$stratKind}Strategy: a non-template left operand is refused");
	} catch (\TypeError) {
		check(true, "TemplateType{$stratKind}Strategy: a non-template left operand is refused");
	}
}

// ---- TemplateTypeFactory / TypeProjectionHelper ----
// The differential proper runs under the real names in type-family.php
// (the factory dispatches on the shadowed bound classes — the PHP twins on
// the PHP side, the prefixed natives here, so a PHP bound is not "exactly"
// ArrayType to the prefixed factory); only the answers that do not depend
// on that are compared here: a null bound, the tag entry point, and the
// projection descriptions (a KeyOfType bound is a shadowed class too now:
// the key-of branch is observed under the real names in type-family.php).
$ttfScope = \PHPStan\Type\Generic\TemplateTypeScope::createWithMethod('Ttf\\C', 'm');
$ttfVariances = [\PHPStan\Type\Generic\TemplateTypeVariance::createInvariant(), \PHPStan\Type\Generic\TemplateTypeVariance::createCovariant(), \PHPStan\Type\Generic\TemplateTypeVariance::createContravariant(), \PHPStan\Type\Generic\TemplateTypeVariance::createStatic(), \PHPStan\Type\Generic\TemplateTypeVariance::createBivariant()];
$ttfBounds = ['null' => null];
$ttfShape = static fn (\PHPStan\Type\Generic\TemplateType $t): array => [$turboNorm(get_class($t)), $t->describe(\PHPStan\Type\VerbosityLevel::precise()), $t->getName(), $t->isArgument(), $turboNorm(get_class($t->getStrategy())), $t->getVariance()->describe(), $t->getDefault()?->describe(\PHPStan\Type\VerbosityLevel::precise())];
foreach ($ttfBounds as $ttfBoundName => $ttfBound) {
	foreach ($ttfVariances as $ttfVariance) {
		foreach ([null, new \PHPStan\Type\Generic\TemplateTypeArgumentStrategy()] as $ttfStrategy) {
			foreach ([null, new \PHPStan\Type\IntegerType()] as $ttfDefault) {
				$expected = $ttfShape(\PHPStan\Type\Generic\TemplateTypeFactory::create($ttfScope, 'T', $ttfBound, $ttfVariance, $ttfStrategy, $ttfDefault));
				$actual = $ttfShape(\PHPStanTurbo\TemplateTypeFactory::create($ttfScope, 'T', $ttfBound, $ttfVariance, $ttfStrategy, $ttfDefault));
				check($expected === $actual, "TemplateTypeFactory create $ttfBoundName {$ttfVariance->describe()}: " . json_encode($expected) . ' vs ' . json_encode($actual));
			}
		}
		$ttfTag = new \PHPStan\PhpDoc\Tag\TemplateTag('F', $ttfBound ?? new \PHPStan\Type\MixedType(), null, $ttfVariance);
		$expected = $ttfShape(\PHPStan\Type\Generic\TemplateTypeFactory::fromTemplateTag($ttfScope, $ttfTag));
		$actual = $ttfShape(\PHPStanTurbo\TemplateTypeFactory::fromTemplateTag($ttfScope, $ttfTag));
		check($expected === $actual, "TemplateTypeFactory fromTemplateTag $ttfBoundName {$ttfVariance->describe()}: " . json_encode($expected) . ' vs ' . json_encode($actual));
	}
}
check(\PHPStanTurbo\TemplateTypeFactory::create($ttfScope, 'T', null, $ttfVariances[0], default: new \PHPStan\Type\StringType())->getDefault() instanceof \PHPStan\Type\StringType, 'TemplateTypeFactory create: the named default argument skips the strategy');
foreach (['int' => new \PHPStan\Type\IntegerType(), 'union' => new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]), 'object' => new \PHPStan\Type\ObjectType(\Exception::class)] as $ttfTypeName => $ttfType) {
	foreach (array_merge([null], $ttfVariances) as $ttfVariance) {
		foreach ([\PHPStan\Type\VerbosityLevel::typeOnly(), \PHPStan\Type\VerbosityLevel::precise()] as $ttfLevel) {
			$expected = \PHPStan\Type\Generic\TypeProjectionHelper::describe($ttfType, $ttfVariance, $ttfLevel);
			$actual = \PHPStanTurbo\TypeProjectionHelper::describe($ttfType, $ttfVariance, $ttfLevel);
			check($expected === $actual, "TypeProjectionHelper describe $ttfTypeName " . ($ttfVariance?->describe() ?? 'null') . ": $expected vs $actual");
		}
	}
}

// ---- ConstantArrayTypeBuilder ----
// Each side builds from its own Type classes (the native builder probes
// ConstantIntegerType / ConstantStringType / ClosureType / NeverType by
// class entry and folds through its own TypeCombinator); the built arrays
// are compared by class modulo the prefix and by description, under both
// BleedingEdgeToggle states. The broad matrix (general and range offsets,
// unsealed folding, closure degradation, createFromConstantArray() over
// every shape) needs the real-name compound graph and runs in
// tests/type-family.php ("constant array builder ...").
$hlpClass = static fn (string $phpClass, bool $native): string => $native ? $shadowedClasses[$phpClass]['turboClass'] : $phpClass;
$catbBuild = static function (bool $native) use ($hlpClass, $ttmView): array {
	$c = static fn (string $phpClass): string => $hlpClass($phpClass, $native);
	$builderClass = $c(\PHPStan\Type\Constant\ConstantArrayTypeBuilder::class);
	$int = new ($c(\PHPStan\Type\IntegerType::class))();
	$string = new ($c(\PHPStan\Type\StringType::class))();
	$float = new ($c(\PHPStan\Type\FloatType::class))();
	$ci = static fn (int $v): object => new ($c(\PHPStan\Type\Constant\ConstantIntegerType::class))($v);
	$cs = static fn (string $v): object => new ($c(\PHPStan\Type\Constant\ConstantStringType::class))($v);
	$limit = $builderClass::ARRAY_COUNT_LIMIT;
	$fresh = static fn (): object => $builderClass::createEmpty();
	$built = static function (array $sets) use ($fresh): object {
		$b = $fresh();
		foreach ($sets as $set) {
			$b->setOffsetValueType($set[0], $set[1], $set[2] ?? false);
		}
		return $b;
	};
	$scenarios = [
		'empty' => static fn () => $fresh(),
		'append' => static fn () => $built([[null, $int], [null, $string]]),
		'append optional' => static fn () => $built([[null, $int, true], [null, $string]]),
		'int keys' => static fn () => $built([[$ci(0), $int], [$ci(1), $string]]),
		'gap key' => static fn () => $built([[$ci(0), $int], [$ci(2), $string]]),
		'negative key optional' => static fn () => $built([[$ci(0), $int], [$ci(-1), $string, true]]),
		'string key' => static fn () => $built([[$cs('a'), $int]]),
		'overwrite' => static fn () => $built([[$ci(0), $int], [$ci(0), $string]]),
		'overwrite optional' => static fn () => $built([[$ci(0), $int], [$ci(0), $string, true]]),
		'float key' => static fn () => $built([[new ($c(\PHPStan\Type\Constant\ConstantFloatType::class))(1.5), $int]]),
		'int max key then append' => static fn () => $built([[$ci(PHP_INT_MAX), $int], [null, $string]]),
		'general string key' => static fn () => $built([[$string, $int]]),
		'over limit appends' => static function () use ($fresh, $int, $limit) {
			$b = $fresh();
			for ($i = 0; $i <= $limit; $i++) {
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
		'over limit disabled' => static function () use ($fresh, $int, $limit) {
			$b = $fresh();
			$b->disableArrayDegradation();
			for ($i = 0; $i <= $limit + 40; $i++) {
				$b->setOffsetValueType(null, $int);
			}
			return $b;
		},
		'degrade' => static function () use ($built, $int, $string, $ci) {
			$b = $built([[$ci(0), $int], [$ci(1), $string]]);
			$b->degradeToGeneralArray();
			return $b;
		},
		'degrade oversized' => static function () use ($built, $int, $ci) {
			$b = $built([[$ci(0), $int]]);
			$b->degradeToGeneralArray(true);
			return $b;
		},
		'degrade then append' => static function () use ($fresh, $int, $string, $cs) {
			$b = $fresh();
			$b->degradeToGeneralArray();
			$b->setOffsetValueType(null, $int);
			$b->setOffsetValueType($cs('a'), $string, true);
			return $b;
		},
		'makeUnsealed' => static function () use ($built, $int, $string, $cs) {
			$b = $built([[$cs('a'), $int]]);
			$b->makeUnsealed($string, $int);
			return $b;
		},
		'mergeUnsealed twice' => static function () use ($fresh, $int, $string, $float) {
			$b = $fresh();
			$b->mergeUnsealed($int, $string);
			$b->mergeUnsealed($string, $float);
			return $b;
		},
		'from constant array' => static function () use ($builderClass, $c, $int, $string, $float, $ci) {
			$b = $builderClass::createFromConstantArray(new ($c(\PHPStan\Type\Constant\ConstantArrayType::class))([$ci(0), $ci(1)], [$int, $string], [1, 2], [1]));
			$b->setOffsetValueType(null, $float);
			return $b;
		},
	];
	$results = [];
	foreach ([false, true] as $bleedingEdge) {
		foreach ($scenarios as $label => $scenario) {
			$results[($bleedingEdge ? 'bleeding-edge ' : '') . $label] = \PHPStan\DependencyInjection\BleedingEdgeToggle::withBleedingEdge($bleedingEdge, static function () use ($scenario, $ttmView): array {
				$b = $scenario();
				return [$ttmView($b->getArray()), $b->isList()];
			});
		}
	}
	return $results;
};
$catbPhp = $catbBuild(false);
$catbNative = $catbBuild(true);
check(\PHPStanTurbo\ConstantArrayTypeBuilder::createEmpty() instanceof \PHPStanTurbo\ConstantArrayTypeBuilder, 'ConstantArrayTypeBuilder::createEmpty() is the native class');
check(\PHPStanTurbo\ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT === \PHPStan\Type\Constant\ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT, 'ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT');
foreach ($catbPhp as $catbLabel => $catbPhpResult) {
	check($catbPhpResult === $catbNative[$catbLabel], "ConstantArrayTypeBuilder $catbLabel: " . json_encode($catbPhpResult) . ' vs ' . json_encode($catbNative[$catbLabel]));
}
$catbErrors = static function (string $class, object $int): array {
	$errors = [];
	try {
		$class::createFromConstantArray(new \stdClass());
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	try {
		$class::createEmpty()->setOffsetValueType(new \stdClass(), $int);
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	try {
		$class::createEmpty()->setOffsetValueType(null, new \stdClass());
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	try {
		$builder = $class::createEmpty();
		$builder->disableArrayDegradation();
		$builder->degradeToGeneralArray();
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	try {
		new $class([], [], [0], [], \PHPStan\TrinaryLogic::createYes(), null);
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	try {
		(new ReflectionClass($class))->newInstanceWithoutConstructor()->getArray();
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	return $errors;
};
check($catbErrors(\PHPStan\Type\Constant\ConstantArrayTypeBuilder::class, new \PHPStan\Type\IntegerType()) === $catbErrors(\PHPStanTurbo\ConstantArrayTypeBuilder::class, new \PHPStanTurbo\IntegerType()), 'ConstantArrayTypeBuilder: foreign arguments, degrading a builder with degradation disabled, the private constructor and an unconstructed instance throw the same (' . implode(', ', $catbErrors(\PHPStanTurbo\ConstantArrayTypeBuilder::class, new \PHPStanTurbo\IntegerType())) . ')');
$covered[\PHPStan\Type\Constant\ConstantArrayTypeBuilder::class] = true;

// ---- UnionTypeHelper ----
// sortTypes() over each side's own Type classes (the native comparator
// probes NullType, the constant scalars, IntegerRangeType, the enum case
// and the callable classes by class entry) in several input orders; the
// result is the sequence of input labels, so stability among equal members
// shows too. The compounds (intersections answering isConstantArray() and
// isString() through their members) sort under the real names in
// tests/type-family.php ("union type helper ...").
$uthTypes = static function (bool $native) use ($hlpClass): array {
	$c = static fn (string $phpClass): string => $hlpClass($phpClass, $native);
	$int = new ($c(\PHPStan\Type\IntegerType::class))();
	$string = new ($c(\PHPStan\Type\StringType::class))();
	$ci = static fn (int $v): object => new ($c(\PHPStan\Type\Constant\ConstantIntegerType::class))($v);
	$cs = static fn (string $v): object => new ($c(\PHPStan\Type\Constant\ConstantStringType::class))($v);
	$range = $c(\PHPStan\Type\IntegerRangeType::class);
	return [
		'null' => new ($c(\PHPStan\Type\NullType::class))(),
		'int' => $int,
		'int again' => new ($c(\PHPStan\Type\IntegerType::class))(),
		'string' => $string,
		'const 1' => $ci(1),
		'const 1 again' => $ci(1),
		'const -3' => $ci(-3),
		'const 1.0' => new ($c(\PHPStan\Type\Constant\ConstantFloatType::class))(1.0),
		'const 2.5' => new ($c(\PHPStan\Type\Constant\ConstantFloatType::class))(2.5),
		'const B' => $cs('B'),
		'const a' => $cs('a'),
		'const A' => $cs('A'),
		'const 10' => $cs('10'),
		'const 9' => $cs('9'),
		'true' => new ($c(\PHPStan\Type\Constant\ConstantBooleanType::class))(true),
		'false' => new ($c(\PHPStan\Type\Constant\ConstantBooleanType::class))(false),
		'bool' => new ($c(\PHPStan\Type\BooleanType::class))(),
		'range 0-10' => $range::fromInterval(0, 10),
		'range min-5' => $range::fromInterval(null, 5),
		'range 5-max' => $range::fromInterval(5, null),
		'accessory non-empty' => new ($c(\PHPStan\Type\Accessory\AccessoryNonEmptyStringType::class))(),
		'accessory numeric' => new ($c(\PHPStan\Type\Accessory\AccessoryNumericStringType::class))(),
		'accessory list' => new ($c(\PHPStan\Type\Accessory\AccessoryArrayListType::class))(),
		'enum hearts' => new ($c(\PHPStan\Type\Enum\EnumCaseObjectType::class))('App\\Suit', 'Hearts'),
		'enum spades' => new ($c(\PHPStan\Type\Enum\EnumCaseObjectType::class))('App\\Suit', 'Spades'),
		'enum red' => new ($c(\PHPStan\Type\Enum\EnumCaseObjectType::class))('App\\Color', 'Red'),
		'callable' => new ($c(\PHPStan\Type\CallableType::class))(),
		'closure' => new ($c(\PHPStan\Type\ClosureType::class))([], $int, false),
		'array{}' => new ($c(\PHPStan\Type\Constant\ConstantArrayType::class))([], []),
		'array{} again' => new ($c(\PHPStan\Type\Constant\ConstantArrayType::class))([], []),
		'array{a: int}' => new ($c(\PHPStan\Type\Constant\ConstantArrayType::class))([$cs('a')], [$int]),
		'array{string}' => new ($c(\PHPStan\Type\Constant\ConstantArrayType::class))([$ci(0)], [$string]),
		'array<int, string>' => new ($c(\PHPStan\Type\ArrayType::class))($int, $string),
		'stdClass' => new ($c(\PHPStan\Type\ObjectType::class))(\stdClass::class),
		'ArrayObject' => new ($c(\PHPStan\Type\ObjectType::class))(\ArrayObject::class),
		'float' => new ($c(\PHPStan\Type\FloatType::class))(),
		'mixed' => new ($c(\PHPStan\Type\MixedType::class))(),
		'never' => new ($c(\PHPStan\Type\NeverType::class))(),
		'void' => new ($c(\PHPStan\Type\VoidType::class))(),
		'object' => new ($c(\PHPStan\Type\ObjectWithoutClassType::class))(),
		'class-string' => new ($c(\PHPStan\Type\ClassStringType::class))(),
		'iterable' => new ($c(\PHPStan\Type\IterableType::class))(new ($c(\PHPStan\Type\MixedType::class))(), new ($c(\PHPStan\Type\MixedType::class))()),
		'resource' => new ($c(\PHPStan\Type\ResourceType::class))(),
	];
};
$uthOrders = static function (array $types): array {
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
	return [
		'declared' => $declared,
		'reversed' => array_reverse($declared),
		'interleaved' => $interleaved,
		'rotated' => array_merge(array_slice($declared, 13), array_slice($declared, 0, 13)),
		'string keys' => ['x' => $types['null'], 'y' => $types['int'], 5 => $types['const a']],
		'empty' => [],
	];
};
$uthSequences = static function (string $helper, array $types, array $orders): array {
	$labelOf = [];
	foreach ($types as $label => $type) {
		$labelOf[spl_object_id($type)] = $label;
	}
	$sequence = static fn (array $sorted): array => array_map(static fn (object $t): string => $labelOf[spl_object_id($t)], $sorted);
	$results = [];
	foreach ($orders as $orderLabel => $list) {
		$sorted = $helper::sortTypes($list);
		$results[$orderLabel] = [$sequence($sorted), array_keys($sorted)];
	}
	foreach ($types as $aLabel => $a) {
		foreach ($types as $bLabel => $b) {
			$results["pair $aLabel / $bLabel"] = $sequence($helper::sortTypes([$a, $b]));
		}
	}
	$big = [$types['null']];
	for ($i = 0; $i < 1024; $i++) {
		$big[] = $types['int'];
	}
	$sortedBig = $helper::sortTypes($big);
	$results['over limit'] = [count($sortedBig), $sequence([$sortedBig[0]]), $sortedBig === $big];
	array_pop($big);
	$sortedBig = $helper::sortTypes($big);
	$results['at limit'] = [count($sortedBig), $sequence([$sortedBig[1023]]), $sortedBig[0] === $types['int']];
	return $results;
};
$uthPhpTypes = $uthTypes(false);
$uthNativeTypes = $uthTypes(true);
$uthPhp = $uthSequences(\PHPStan\Type\UnionTypeHelper::class, $uthPhpTypes, $uthOrders($uthPhpTypes));
$uthNative = $uthSequences(\PHPStanTurbo\UnionTypeHelper::class, $uthNativeTypes, $uthOrders($uthNativeTypes));
check(array_keys($uthPhp) === array_keys($uthNative), 'UnionTypeHelper: both sides observed the same orders');
foreach ($uthPhp as $uthLabel => $uthPhpResult) {
	check($uthPhpResult === $uthNative[$uthLabel], "UnionTypeHelper::sortTypes $uthLabel: " . json_encode($uthPhpResult) . ' vs ' . json_encode($uthNative[$uthLabel]));
}
$uthErrors = static function (string $helper, object $int): array {
	$errors = [];
	try {
		$helper::sortTypes([$int, new \stdClass()]);
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	try {
		$helper::sortTypes('x');
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	return $errors;
};
check($uthErrors(\PHPStan\Type\UnionTypeHelper::class, $uthPhpTypes['int']) === $uthErrors(\PHPStanTurbo\UnionTypeHelper::class, $uthNativeTypes['int']), 'UnionTypeHelper: a foreign member and a non-array throw the same (' . implode(', ', $uthErrors(\PHPStanTurbo\UnionTypeHelper::class, $uthNativeTypes['int'])) . ')');
$covered[\PHPStan\Type\UnionTypeHelper::class] = true;

// ---- ConstantTypeHelper ----
// getTypeFromValue() over every kind of PHP value: the scalars, nested and
// oversized arrays (each side through its own builder), an enum case, a
// plain object, a closure and a resource; results compared by class
// modulo the prefix and by description.
enum SmokeTurboSuit
{

	case Hearts;
	case Spades;

}
enum SmokeTurboBacked: string
{

	case Red = 'r';

}
$cthResource = fopen('php://memory', 'r');
$cthBig = [];
for ($i = 0; $i < \PHPStan\Type\Constant\ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT + 1; $i++) {
	$cthBig[] = $i % 2 === 0 ? $i : 'v' . $i;
}
$cthBigNested = ['inner' => $cthBig, 'x' => [$cthBig, 1]];
$cthValues = [
	'int 0' => 0,
	'int 42' => 42,
	'int min' => PHP_INT_MIN,
	'float 1.5' => 1.5,
	'float -0.0' => -0.0,
	'float INF' => INF,
	'float NAN' => NAN,
	'true' => true,
	'false' => false,
	'null' => null,
	'string empty' => '',
	'string 0' => '0',
	'string abc' => 'abc',
	'string numeric' => '123',
	'string bytes' => "\xff\x00",
	'array empty' => [],
	'array list' => [1, 'a', 2.5, true, null],
	'array assoc' => ['a' => 1, 'b' => 'x', 7 => [1, 2], '3' => 'numeric key'],
	'array nested' => ['a' => ['b' => ['c' => [1]]], 'd' => []],
	'array negative keys' => [-5 => 'a', -1 => 'b', 0 => 'c'],
	'array big' => $cthBig,
	'array big nested' => $cthBigNested,
	'stdClass' => new \stdClass(),
	'enum case' => SmokeTurboSuit::Hearts,
	'enum case spades' => SmokeTurboSuit::Spades,
	'backed enum case' => SmokeTurboBacked::Red,
	'closure' => static fn () => 1,
	'resource' => $cthResource,
];
foreach ($cthValues as $cthLabel => $cthValue) {
	$cthPhpResult = $ttmView(\PHPStan\Type\ConstantTypeHelper::getTypeFromValue($cthValue));
	$cthNativeResult = $ttmView(\PHPStanTurbo\ConstantTypeHelper::getTypeFromValue($cthValue));
	check($cthPhpResult === $cthNativeResult, "ConstantTypeHelper::getTypeFromValue($cthLabel): " . json_encode($cthPhpResult) . ' vs ' . json_encode($cthNativeResult));
}
fclose($cthResource);
$cthPhpResult = $ttmView(\PHPStan\Type\ConstantTypeHelper::getTypeFromValue($cthResource));
$cthNativeResult = $ttmView(\PHPStanTurbo\ConstantTypeHelper::getTypeFromValue($cthResource));
check($cthPhpResult === $cthNativeResult, 'ConstantTypeHelper::getTypeFromValue(closed resource): ' . json_encode($cthPhpResult) . ' vs ' . json_encode($cthNativeResult));
check(\PHPStanTurbo\ConstantTypeHelper::getTypeFromValue(1) instanceof \PHPStanTurbo\ConstantIntegerType, 'ConstantTypeHelper hands out the native constant types');
$covered[\PHPStan\Type\ConstantTypeHelper::class] = true;

// ---- StaticTypeFactory ----
// The six factories, each side over its own Type classes, compared by
// class modulo the prefix and by description; the memoized ones hand out
// one instance per process on both sides, the others a fresh one per call.
foreach (['falsey', 'truthy', 'argv', 'argc', 'generalOffsetAccessibleType', 'intOffsetAccessibleType'] as $stfMethod) {
	$stfPhpResult = \PHPStan\Type\StaticTypeFactory::$stfMethod();
	$stfNativeResult = \PHPStanTurbo\StaticTypeFactory::$stfMethod();
	check($ttmView($stfPhpResult) === $ttmView($stfNativeResult), "StaticTypeFactory::$stfMethod(): " . json_encode($ttmView($stfPhpResult)) . ' vs ' . json_encode($ttmView($stfNativeResult)));
	check(($stfPhpResult === \PHPStan\Type\StaticTypeFactory::$stfMethod()) === ($stfNativeResult === \PHPStanTurbo\StaticTypeFactory::$stfMethod()), "StaticTypeFactory::$stfMethod() is memoized on both sides or neither");
}
check(\PHPStanTurbo\StaticTypeFactory::truthy() instanceof \PHPStanTurbo\MixedType, 'StaticTypeFactory::truthy() is the native MixedType');
check(\PHPStanTurbo\StaticTypeFactory::falsey() instanceof \PHPStanTurbo\UnionType, 'StaticTypeFactory::falsey() is the native UnionType');
$covered[\PHPStan\Type\StaticTypeFactory::class] = true;

// ---- TypeResult ----
// A readonly pair of a Type and its reasons; the constructor's typed
// parameters, the readonly slots and an unconstructed instance behave the
// same.
$trBuild = static function (string $class, object $type): array {
	$result = new $class($type, ['a', 'b']);
	$errors = [$result->reasons, $result->type === $type, (new $class($type, []))->reasons];
	try {
		new $class(new \stdClass(), []);
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	try {
		new $class($type, 'x');
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	try {
		$result->type = $type;
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	try {
		$result->reasons = [];
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	try {
		$result->__construct($type, []);
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	try {
		(new ReflectionClass($class))->newInstanceWithoutConstructor()->type;
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	try {
		$errors[] = (new ReflectionClass($class))->newInstanceWithoutConstructor()->reasons;
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	return $errors;
};
$trPhp = $trBuild(\PHPStan\Type\TypeResult::class, new \PHPStan\Type\IntegerType());
$trNative = $trBuild(\PHPStanTurbo\TypeResult::class, new \PHPStanTurbo\IntegerType());
check($trPhp === $trNative, 'TypeResult: the slots, foreign arguments, readonly writes, a repeated constructor call and an unconstructed instance behave the same (' . json_encode($trPhp) . ' vs ' . json_encode($trNative) . ')');
$trMixed = new \PHPStanTurbo\TypeResult(new \PHPStan\Type\IntegerType(), []);
check($trMixed->type instanceof \PHPStan\Type\IntegerType && $trMixed->reasons === [], 'TypeResult: the native class holds a PHP Type');
$covered[\PHPStan\Type\TypeResult::class] = true;

// ---- CallableTypeHelper ----
// isParametersAcceptorSuperTypeOf() over closures and callables built from
// each side's own Type classes (the helper combines the parameter and
// return-type results through its own IsSuperTypeOfResult), under every
// treatMixedAsAny / strictTypes combination; the broad matrix (unions,
// objects, variadics, purity and staticness) runs under the real names in
// tests/type-family.php ("callable type helper ...").
$cthAcceptors = static function (bool $native) use ($hlpClass): array {
	$c = static fn (string $phpClass): string => $hlpClass($phpClass, $native);
	$int = new ($c(\PHPStan\Type\IntegerType::class))();
	$string = new ($c(\PHPStan\Type\StringType::class))();
	$mixed = new ($c(\PHPStan\Type\MixedType::class))();
	$void = new ($c(\PHPStan\Type\VoidType::class))();
	$param = static fn (string $name, object $type, bool $optional = false, bool $variadic = false): \PHPStan\Reflection\Native\NativeParameterReflection => new \PHPStan\Reflection\Native\NativeParameterReflection($name, $optional, $type, \PHPStan\Reflection\PassedByReference::createNo(), $variadic, null);
	$closure = $c(\PHPStan\Type\ClosureType::class);
	$callable = $c(\PHPStan\Type\CallableType::class);
	return [
		'(): mixed' => new $closure([], $mixed, false),
		'(int): int' => new $closure([$param('a', $int)], $int, false),
		'(int, string=): string' => new $closure([$param('a', $int), $param('b', $string, true)], $string, false),
		'(int ...$rest): void' => new $closure([$param('rest', $int, true, true)], $void, true),
		'(mixed): mixed' => new $closure([$param('a', $mixed)], $mixed, false),
		'(string): int' => new $closure([$param('a', $string)], $int, false),
		'(unnamed int): int' => new $closure([$param('', $int)], $int, false),
		'callable' => new $callable(),
		'callable(int): int' => new $callable([$param('x', $int)], $int, false),
	];
};
$cthMatrix = static function (string $helper, array $acceptors): array {
	$results = [];
	foreach ($acceptors as $oursLabel => $ours) {
		foreach ($acceptors as $theirsLabel => $theirs) {
			foreach ([[false, true], [true, true], [true, false], [false, false]] as [$treatMixedAsAny, $strictTypes]) {
				$result = $helper::isParametersAcceptorSuperTypeOf($ours, $theirs, $treatMixedAsAny, $strictTypes);
				$results[sprintf('%s <- %s%s%s', $oursLabel, $theirsLabel, $treatMixedAsAny ? ' mixed-as-any' : '', $strictTypes ? '' : ' loose')] = [$result->result->describe(), $result->reasons];
			}
			$result = $helper::isParametersAcceptorSuperTypeOf($ours, $theirs, true);
			$results["$oursLabel <- $theirsLabel default strictTypes"] = [$result->result->describe(), $result->reasons];
		}
	}
	return $results;
};
$cthPhpMatrix = $cthMatrix(\PHPStan\Type\CallableTypeHelper::class, $cthAcceptors(false));
$cthNativeMatrix = $cthMatrix(\PHPStanTurbo\CallableTypeHelper::class, $cthAcceptors(true));
foreach ($cthPhpMatrix as $cthLabel => $cthPhpResult) {
	check($cthPhpResult === $cthNativeMatrix[$cthLabel], "CallableTypeHelper $cthLabel: " . json_encode($cthPhpResult) . ' vs ' . json_encode($cthNativeMatrix[$cthLabel]));
}
check(\PHPStanTurbo\CallableTypeHelper::isParametersAcceptorSuperTypeOf($cthAcceptors(true)['callable'], $cthAcceptors(true)['callable'], false) instanceof \PHPStanTurbo\IsSuperTypeOfResult, 'CallableTypeHelper hands out the native IsSuperTypeOfResult');
$cthErrors = static function (string $helper, object $acceptor): array {
	$errors = [];
	try {
		$helper::isParametersAcceptorSuperTypeOf(new \stdClass(), $acceptor, false);
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	try {
		$helper::isParametersAcceptorSuperTypeOf($acceptor, new \stdClass(), false);
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	try {
		$helper::isParametersAcceptorSuperTypeOf($acceptor, $acceptor, 'x');
		$errors[] = 'none';
	} catch (\Throwable $e) {
		$errors[] = get_class($e);
	}
	return $errors;
};
check($cthErrors(\PHPStan\Type\CallableTypeHelper::class, $cthAcceptors(false)['callable']) === $cthErrors(\PHPStanTurbo\CallableTypeHelper::class, $cthAcceptors(true)['callable']), 'CallableTypeHelper: foreign arguments throw the same (' . implode(', ', $cthErrors(\PHPStanTurbo\CallableTypeHelper::class, $cthAcceptors(true)['callable'])) . ')');
$covered[\PHPStan\Type\CallableTypeHelper::class] = true;

// ---- LruCache ----
// The array-backed LRU: all() must hand out the same keys (a numeric string
// becomes an integer key) in the same order on both sides — the order is
// the eviction order — and the evicted lists, the count and weight bounds
// with the eviction floor, get() touching, set() re-accounting a replaced
// key's weight, replace() and the errors must agree.
$lruResults = [];
foreach (['php' => \PHPStan\Internal\LruCache::class, 'native' => \PHPStanTurbo\LruCache::class] as $side => $lruClass) {
	$r = [];
	$unbounded = new $lruClass();
	$r[] = [$unbounded instanceof $lruClass, $unbounded->count(), $unbounded->all(), $unbounded->get('a')];
	foreach ([['a', 'A', 1], ['10', 'ten', 2], ['b', ['B'], 3], ['0', 0.5, 4], ['a', 'A2', 5], ['01', null, 6], ['-3', true, 0]] as [$k, $v, $w]) {
		$r[] = [$k, $unbounded->set($k, $v, $w), $unbounded->count(), $unbounded->all()];
	}
	$r[] = [$unbounded->get('10'), $unbounded->all(), $unbounded->get('nope'), $unbounded->get('0'), $unbounded->get('01'), $unbounded->all()];
	$unbounded->replace('b', 'B2');
	$unbounded->replace('10', 'TEN');
	$r[] = $unbounded->all();
	try {
		$unbounded->replace('missing', 1);
		$r[] = 'replaced';
	} catch (\PHPStan\ShouldNotHappenException $e) {
		$r[] = [get_class($e), $e->getMessage()];
	}

	$byCount = new $lruClass(3);
	foreach (['a', 'b', 'c', 'd', 'b', 'e', 'f'] as $i => $k) {
		$r[] = [$k, $byCount->set($k, $i, 0), $byCount->all()];
	}
	$r[] = [$byCount->get('b'), $byCount->all()];
	$r[] = [$byCount->set('g', 7, 0), $byCount->all(), $byCount->count()];

	$byWeight = new $lruClass(maxWeight: 10, weightEvictionFloorCount: 1);
	foreach ([['a', 4], ['b', 4], ['c', 4], ['huge', 100], ['d', 1], ['huge', 0], ['e', 9], ['f', 1]] as $i => [$k, $w]) {
		$r[] = [$k, $byWeight->set($k, $i, $w), $byWeight->all()];
	}

	$both = new $lruClass(2, 5, 0);
	foreach ([['a', 3], ['b', 3], ['c', 1], ['d', 1], ['e', 9], ['f', 1]] as $i => [$k, $w]) {
		$r[] = [$k, $both->set($k, $i, $w), $both->all()];
	}
	$r[] = [$both->get('f'), $both->set('g', 1, 1), $both->all()];

	$raw = (new \ReflectionClass($lruClass))->newInstanceWithoutConstructor();
	$r[] = [$raw->count(), $raw->get('x'), $raw->all()];
	try {
		$raw->set('x', 1, 1);
		$r[] = 'set on an unconstructed cache';
	} catch (\Error $e) {
		$r[] = [get_class($e), str_replace($lruClass, 'LruCache', $e->getMessage())];
	}
	$lruResults[$side] = $r;
}
check($lruResults['php'] === $lruResults['native'], 'LruCache parity: ' . json_encode($lruResults['php']) . ' vs ' . json_encode($lruResults['native']));
$covered[\PHPStan\Internal\LruCache::class] = true;
$covered[\PHPStan\Analyser\VolatileExpressionHelper::class] = true;
$covered[\PHPStan\Analyser\VariableFlow::class] = true;
$covered[\PHPStan\Analyser\VariableFlowBuilder::class] = true;
$covered[\PHPStan\Analyser\VariableLivenessResolver::class] = true;

// ---- differential coverage completeness ----
// Every shadowed class must be exercised by one of the tests/ scripts; the
// classes not covered above have their own dedicated script.
$coveredElsewhere = [
	\PHPStan\Cache\ArenaCache::class => 'arena-smoke.php',
	\PHPStan\Parser\ParserRunner::class => 'parser-corpus.php',
	\PHPStan\Reflection\BetterReflection\SourceLocator\PhpFileCleaner::class => 'php-file-cleaner-corpus.php',
	\PHPStan\Reflection\BetterReflection\SourceLocator\SymbolFinderInFiles::class => 'symbol-finder-corpus.php',
];
foreach (array_keys($shadowedClasses) as $shadowedClass) {
	check(
		isset($covered[$shadowedClass]) || isset($coveredElsewhere[$shadowedClass]),
		"shadowed class $shadowedClass has no differential coverage — register it in \$covered next to its checks here, or in \$coveredElsewhere",
	);
}

echo $failures === 0 ? "ALL OK\n" : "$failures FAILURES\n";
exit($failures === 0 ? 0 : 1);

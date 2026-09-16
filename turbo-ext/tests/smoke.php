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


// ---- ExpressionResultStorageStack ----
$covered[\PHPStan\Analyser\ExpressionResultStorageStack::class] = true;
foreach (['php' => \PHPStan\Analyser\ExpressionResultStorageStack::class, 'native' => \PHPStanTurbo\ExpressionResultStorageStack::class] as $label => $stackClass) {
	$stack = new $stackClass();
	check($stack->getCurrent() === null, "ERSS $label: empty stack has no current storage");

	$storageA = new \PHPStan\Analyser\ExpressionResultStorage();
	$storageB = new \PHPStan\Analyser\ExpressionResultStorage();
	$stack->push($storageA);
	check($stack->getCurrent() === $storageA, "ERSS $label: getCurrent answers the pushed storage");
	$stack->push($storageB);
	check($stack->getCurrent() === $storageB, "ERSS $label: getCurrent answers the top of the stack");
	$stack->pop();
	check($stack->getCurrent() === $storageA, "ERSS $label: pop uncovers the one below");
	$stack->push($storageA);
	check($stack->getCurrent() === $storageA, "ERSS $label: the same storage may be pushed twice");
	$stack->pop();
	$stack->pop();
	check($stack->getCurrent() === null, "ERSS $label: the emptied stack has no current storage");

	$popped = null;
	try {
		$stack->pop();
	} catch (\PHPStan\ShouldNotHappenException $e) {
		$popped = $e->getMessage();
	}
	check($popped === 'Unbalanced ExpressionResultStorageStack pop.', "ERSS $label: popping an empty stack throws");

	// the stack survives the failed pop and keeps working
	$stack->push($storageB);
	check($stack->getCurrent() === $storageB, "ERSS $label: usable again after the failed pop");
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

// ---- ExprPrinter ----
// Printing fills a cache attribute on the node itself, so each side prints
// its own fresh, structurally identical fixture: the parsed expressions of a
// few snippets plus PHPStan's own virtual expressions. Compared are the
// printed strings, the attribute each implementation leaves behind, and what
// a second call to the same node returns.
$covered[\PHPStan\Node\Printer\ExprPrinter::class] = true;
$epCacheKey = \PHPStan\Node\Printer\ExprPrinter::ATTRIBUTE_CACHE_KEY;
$epSnippets = [
	'<?php $a = $b + 1; $c = $a->d->e()[0] ?? Foo\Bar::BAZ;',
	'<?php $x = f(1, ...$args) . "pre{$y}post" . \'q\' . <<<T' . "\n" . 'body' . "\n" . 'T;',
	'<?php $r = new C(fn ($p) => $p ? -$p : +$p); $s = $r instanceof C ? clone $r : null;',
	'<?php $o->{\'weird name\'} = $o->{\'plain\'}(); $o?->m()?->p; $q = [1, \'k\' => $z, ...$w];',
	'<?php $i = match ($v) { 1, 2 => "a", default => "b" }; $j = (int) $i; $k = $i <=> $j;',
	'<?php $c = function () { $inner = 1; return $inner; }; static::m(); self::$p; C::class;',
	'<?php list($a, [$b]) = $t; $a **= 2; $b ??= 3; print $a; @$und; $g = &$a; yield $a => $b;',
];
$epBuildExprs = static function () use ($smokeParser, $nodeFinder, $epSnippets): array {
	$exprs = [];
	foreach ($epSnippets as $si => $code) {
		$ast = $smokeParser->parse($code);
		foreach ($nodeFinder->find($ast, static fn (\PhpParser\Node $n): bool => $n instanceof \PhpParser\Node\Expr) as $ni => $node) {
			$exprs["snippet #$si expr #$ni (" . $node->getType() . ')'] = $node;
		}
	}

	$var = new \PhpParser\Node\Expr\Variable('v');
	$dim = new \PhpParser\Node\Scalar\String_('k');
	$exprs['Variable'] = new \PhpParser\Node\Expr\Variable('plain');
	$exprs['Variable with an Expr name'] = new \PhpParser\Node\Expr\Variable(new \PhpParser\Node\Expr\Variable('indirect'));
	$exprs['TypeExpr'] = new \PHPStan\Node\Expr\TypeExpr(new \PHPStan\Type\IntegerType());
	$exprs['NativeTypeExpr'] = new \PHPStan\Node\Expr\NativeTypeExpr(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType());
	$exprs['UnsetOffsetExpr'] = new \PHPStan\Node\Expr\UnsetOffsetExpr($var, $dim);
	$exprs['ExistingArrayDimFetch'] = new \PHPStan\Node\Expr\ExistingArrayDimFetch($var, $dim);
	$exprs['SetOffsetValueTypeExpr'] = new \PHPStan\Node\Expr\SetOffsetValueTypeExpr($var, null, $dim);
	$exprs['SetExistingOffsetValueTypeExpr'] = new \PHPStan\Node\Expr\SetExistingOffsetValueTypeExpr($var, $dim, $var);
	$exprs['AlwaysRememberedExpr'] = new \PHPStan\Node\Expr\AlwaysRememberedExpr($var, new \PHPStan\Type\IntegerType(), new \PHPStan\Type\IntegerType());
	$exprs['PossiblyImpureCallExpr'] = new \PHPStan\Node\Expr\PossiblyImpureCallExpr($var, $dim, 'call');
	$exprs['PropertyInitializationExpr'] = new \PHPStan\Node\Expr\PropertyInitializationExpr('prop');
	$exprs['CloneReinitializationExpr'] = new \PHPStan\Node\Expr\CloneReinitializationExpr('prop');
	$exprs['ForeachValueByRefExpr'] = new \PHPStan\Node\Expr\ForeachValueByRefExpr($var);
	$exprs['ParameterVariableOriginalValueExpr'] = new \PHPStan\Node\Expr\ParameterVariableOriginalValueExpr('p');
	$exprs['OriginalForeachKeyExpr'] = new \PHPStan\Node\Expr\OriginalForeachKeyExpr('k');
	$exprs['OriginalForeachValueExpr'] = new \PHPStan\Node\Expr\OriginalForeachValueExpr('v');
	$exprs['IntertwinedVariableByReferenceWithExpr'] = new \PHPStan\Node\Expr\IntertwinedVariableByReferenceWithExpr('r', $var, $dim);
	$exprs['IssetExpr'] = new \PHPStan\Node\IssetExpr($var);
	// a form containing a newline: printExpr() remembers it even though
	// Printer::p() would not
	$exprs['multi-line closure'] = $smokeParser->parse('<?php function () { $a = 1; return $a; };')[0]->expr;

	return $exprs;
};

$epResults = [];
foreach (['php' => \PHPStan\Node\Printer\ExprPrinter::class, 'native' => \PHPStanTurbo\ExprPrinter::class] as $epSide => $epClass) {
	$epPrinter = new $epClass(new \PHPStan\Node\Printer\Printer());
	$rows = [];
	foreach ($epBuildExprs() as $label => $expr) {
		$printed = $epPrinter->printExpr($expr);
		$rows[$label] = [$printed, $expr->getAttribute($epCacheKey), $epPrinter->printExpr($expr)];
	}

	// the cache is consulted, not bypassed: a seeded attribute wins over the
	// printer, and a Variable is answered before the cache is even read
	$seeded = new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('SEEDED'));
	$seeded->setAttribute($epCacheKey, 'from the cache');
	$seededVariable = new \PhpParser\Node\Expr\Variable('cached');
	$seededVariable->setAttribute($epCacheKey, 'from the cache');
	$rows['seeded cache'] = $epPrinter->printExpr($seeded);
	$rows['seeded cache on a Variable'] = $epPrinter->printExpr($seededVariable);
	$epResults[$epSide] = $rows;
}
foreach ($epResults['php'] as $label => $row) {
	check(
		$row === ($epResults['native'][$label] ?? null),
		"ExprPrinter parity ($label): " . json_encode($row) . ' vs ' . json_encode($epResults['native'][$label] ?? null),
	);
}
check(count($epResults['php']) > 100, 'ExprPrinter: the fixture covers the expression shapes');
check($epResults['php']['Variable'] === ['$plain', null, '$plain'], 'ExprPrinter: the Variable fast path leaves no cache entry');
check(str_contains((string) $epResults['php']['multi-line closure'][1], "\n"), 'ExprPrinter: a multi-line form is remembered too');

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

// ---- the PHPStan\Parser\*Visitor ports ----
// The native NodeTraverser dispatches these without an engine call, so they
// are compared under both traversers and in both directions.
$covered[\PHPStan\Parser\ArrayFilterArgVisitor::class] = true;
$covered[\PHPStan\Parser\ArrayFindArgVisitor::class] = true;
$covered[\PHPStan\Parser\ArrayMapArgVisitor::class] = true;
$covered[\PHPStan\Parser\ArrayOffsetNormalizingVisitor::class] = true;
$covered[\PHPStan\Parser\ArrayWalkArgVisitor::class] = true;
$covered[\PHPStan\Parser\ArrowFunctionArgVisitor::class] = true;
$covered[\PHPStan\Parser\ClosureArgVisitor::class] = true;
$covered[\PHPStan\Parser\ClosureBindArgVisitor::class] = true;
$covered[\PHPStan\Parser\ClosureBindToVarVisitor::class] = true;
$covered[\PHPStan\Parser\CurlSetOptArgVisitor::class] = true;
$covered[\PHPStan\Parser\CurlSetOptArrayArgVisitor::class] = true;
$covered[\PHPStan\Parser\DeclarePositionVisitor::class] = true;
$covered[\PHPStan\Parser\ImmediatelyInvokedClosureVisitor::class] = true;
$covered[\PHPStan\Parser\ImplodeArgVisitor::class] = true;
$covered[\PHPStan\Parser\MagicConstantParamDefaultVisitor::class] = true;
$covered[\PHPStan\Parser\NewAssignedToPropertyVisitor::class] = true;
$covered[\PHPStan\Parser\ParentStmtTypesVisitor::class] = true;
$covered[\PHPStan\Parser\TraitCollectingVisitor::class] = true;
$covered[\PHPStan\Parser\TryCatchTypeVisitor::class] = true;
$covered[\PHPStan\Parser\TypeTraverserInstanceofVisitor::class] = true;
require __DIR__ . '/parser-visitors.php';

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
$covered[\PHPStan\Reflection\ResolvedMethodReflection::class] = true;
$covered[\PHPStan\Reflection\Dummy\ChangedTypeMethodReflection::class] = true;
$covered[\PHPStan\Reflection\Callables\SimpleImpurePoint::class] = true;

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
foreach ([\PHPStan\Type\BooleanType::class, \PHPStan\Type\Constant\ConstantBooleanType::class, \PHPStan\Type\IntegerType::class, \PHPStan\Type\Constant\ConstantIntegerType::class, \PHPStan\Type\IntegerRangeType::class, \PHPStan\Type\StringType::class, \PHPStan\Type\Constant\ConstantStringType::class, \PHPStan\Type\ClassStringType::class, \PHPStan\Type\Generic\GenericClassStringType::class, \PHPStan\Type\FloatType::class, \PHPStan\Type\Constant\ConstantFloatType::class, \PHPStan\Type\NullType::class, \PHPStan\Type\VoidType::class, \PHPStan\Type\NeverType::class, \PHPStan\Type\MixedType::class, \PHPStan\Type\StrictMixedType::class, \PHPStan\Type\ObjectWithoutClassType::class, \PHPStan\Type\StaticType::class, \PHPStan\Type\ThisType::class, \PHPStan\Type\Generic\GenericStaticType::class, \PHPStan\Type\ObjectShapeType::class, \PHPStan\Type\NonexistentParentClassType::class, \PHPStan\Type\ArrayType::class, \PHPStan\Type\Accessory\NonEmptyArrayType::class, \PHPStan\Type\Accessory\AccessoryArrayListType::class, \PHPStan\Type\Accessory\OversizedArrayType::class, \PHPStan\Type\Accessory\HasOffsetType::class, \PHPStan\Type\Accessory\HasOffsetValueType::class, \PHPStan\Type\Accessory\AccessoryNumericStringType::class, \PHPStan\Type\Accessory\AccessoryNonEmptyStringType::class, \PHPStan\Type\Accessory\AccessoryNonFalsyStringType::class, \PHPStan\Type\Accessory\AccessoryLiteralStringType::class, \PHPStan\Type\Accessory\AccessoryLowercaseStringType::class, \PHPStan\Type\Accessory\AccessoryUppercaseStringType::class, \PHPStan\Type\Accessory\AccessoryDecimalIntegerStringType::class, \PHPStan\Type\Accessory\HasMethodType::class, \PHPStan\Type\Accessory\HasPropertyType::class, \PHPStan\Type\ObjectType::class, \PHPStan\Type\Generic\GenericObjectType::class, \PHPStan\Type\Enum\EnumCaseObjectType::class, \PHPStan\Type\IterableType::class, \PHPStan\Type\CallableType::class, \PHPStan\Type\ClosureType::class, \PHPStan\Type\Constant\ConstantArrayType::class, \PHPStan\Type\UnionType::class, \PHPStan\Type\BenevolentUnionType::class, \PHPStan\Type\IntersectionType::class, \PHPStan\Type\ErrorType::class, \PHPStan\Type\CircularTypeAliasErrorType::class, \PHPStan\Type\Generic\AbsorbedTemplateArgumentType::class, \PHPStan\Type\NonAcceptingNeverType::class, \PHPStan\Type\StringAlwaysAcceptingObjectWithToStringType::class, \PHPStan\Type\StringNeverAcceptingObjectWithToStringType::class, \PHPStan\Type\ResourceType::class, \PHPStan\Type\TypeUtils::class, \PHPStan\Type\TypehintHelper::class, \PHPStan\Type\TypeCombinator::class, \PHPStan\Type\Generic\TemplateTypeVariance::class, \PHPStan\Type\Generic\TemplateTypeVarianceMap::class, \PHPStan\Type\Generic\TemplateTypeMap::class, \PHPStan\Type\Generic\TemplateTypeScope::class, \PHPStan\Type\Generic\TemplateTypeReference::class, \PHPStan\Type\Generic\TemplateTypeHelper::class, \PHPStan\Type\KeyOfType::class, \PHPStan\Type\ValueOfType::class, \PHPStan\Type\OffsetAccessType::class, \PHPStan\Type\ClassConstantAccessType::class, \PHPStan\Type\NewObjectType::class, \PHPStan\Type\ConditionalType::class, \PHPStan\Type\ConditionalTypeForParameter::class, \PHPStan\Type\LateResolvableArrayShapeType::class, \PHPStan\Type\Generic\UnresolvedTemplateArgumentType::class, \PHPStan\Type\Generic\TemplateArrayType::class, \PHPStan\Type\Generic\TemplateBenevolentUnionType::class, \PHPStan\Type\Generic\TemplateBooleanType::class, \PHPStan\Type\Generic\TemplateConstantArrayType::class, \PHPStan\Type\Generic\TemplateConstantIntegerType::class, \PHPStan\Type\Generic\TemplateConstantStringType::class, \PHPStan\Type\Generic\TemplateFloatType::class, \PHPStan\Type\Generic\TemplateGenericObjectType::class, \PHPStan\Type\Generic\TemplateIntegerType::class, \PHPStan\Type\Generic\TemplateIntersectionType::class, \PHPStan\Type\Generic\TemplateIterableType::class, \PHPStan\Type\Generic\TemplateMixedType::class, \PHPStan\Type\Generic\TemplateNullType::class, \PHPStan\Type\Generic\TemplateObjectShapeType::class, \PHPStan\Type\Generic\TemplateObjectType::class, \PHPStan\Type\Generic\TemplateObjectWithoutClassType::class, \PHPStan\Type\Generic\TemplateStrictMixedType::class, \PHPStan\Type\Generic\TemplateStringType::class, \PHPStan\Type\Generic\TemplateUnionType::class, \PHPStan\Type\Generic\TemplateTypeArgumentStrategy::class, \PHPStan\Type\Generic\TemplateTypeParameterStrategy::class, \PHPStan\Type\Generic\TemplateTypeFactory::class, \PHPStan\Type\Generic\TypeProjectionHelper::class, \PHPStan\Type\Constant\ConstantArrayTypeBuilder::class, \PHPStan\Type\UnionTypeHelper::class, \PHPStan\Type\CallableTypeHelper::class, \PHPStan\Type\Helper\GetTemplateTypeType::class, \PHPStan\Type\Generic\TemplateKeyOfType::class, \PHPStan\Rules\PhpDoc\UnresolvableTypeHelper::class, \PHPStan\Reflection\Native\NativeParameterReflection::class, \PHPStan\Reflection\Type\CalledOnTypeUnresolvedMethodPrototypeReflection::class, \PHPStan\Reflection\Type\CalledOnTypeUnresolvedPropertyPrototypeReflection::class, \PHPStan\Reflection\Type\CallbackUnresolvedMethodPrototypeReflection::class, \PHPStan\Reflection\Type\CallbackUnresolvedPropertyPrototypeReflection::class, \PHPStan\Reflection\ResolvedMethodReflection::class, \PHPStan\Reflection\Dummy\ChangedTypeMethodReflection::class, \PHPStan\Reflection\Callables\SimpleImpurePoint::class] as $typeClass) {
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

// ---- VolatileExpressionHelper ----
// The tables are the by-reference copies a MutatingScope hands in: holders of
// the side's own class over shared expression nodes; the results are the
// return value and the surviving keys of both tables.
$vehScopeFactory = $scContainer->getByType(\PHPStan\Analyser\ScopeFactory::class);
$vehScope = $vehScopeFactory->create(\PHPStan\Analyser\ScopeContext::create(__FILE__));
$vehInputs = static function (string $side): array {
	$holder = $side === 'php'
		? static fn ($expr, $type, $certainty) => new \PHPStan\Analyser\ExpressionTypeHolder($expr, $type, $certainty)
		: static fn ($expr, $type, $certainty) => new \PHPStanTurbo\ExpressionTypeHolder($expr, $type, $certainty);
	$yes = $side === 'php' ? \PHPStan\TrinaryLogic::createYes() : \PHPStanTurbo\TrinaryLogic::createYes();
	$maybe = $side === 'php' ? \PHPStan\TrinaryLogic::createMaybe() : \PHPStanTurbo\TrinaryLogic::createMaybe();
	$int = new \PHPStanTurbo\IntegerType();
	$string = new \PHPStanTurbo\StringType();
	$true = new \PHPStanTurbo\ConstantBooleanType(true);
	$false = new \PHPStanTurbo\ConstantBooleanType(false);
	$bool = new \PHPStanTurbo\BooleanType();
	$funcCall = static fn (string $name, array $args = [], bool $fullyQualified = false) => new \PhpParser\Node\Expr\FuncCall(
		$fullyQualified ? new \PhpParser\Node\Name\FullyQualified($name) : new \PhpParser\Node\Name($name),
		$args,
	);
	$arg = static fn (\PhpParser\Node\Expr $value) => new \PhpParser\Node\Arg($value);
	$expressionTypes = [
		'ob_get_level()' => $holder($funcCall('ob_get_level'), $int, $yes),
		'\openssl_error_string()' => $holder($funcCall('openssl_error_string', [], true), $string, $yes),
		'$_GET' => $holder(new \PhpParser\Node\Expr\Variable('_GET'), $int, $yes),
		'$_GET[\'x\']' => $holder(new \PhpParser\Node\Expr\ArrayDimFetch(new \PhpParser\Node\Expr\Variable('_GET'), new \PhpParser\Node\Scalar\String_('x')), $string, $yes),
		'$_SERVERx' => $holder(new \PhpParser\Node\Expr\Variable('_SERVERx'), $int, $yes),
		'$a' => $holder(new \PhpParser\Node\Expr\Variable('a'), $int, $yes),
		'class_exists(\'Foo\')' => $holder($funcCall('class_exists', [$arg(new \PhpParser\Node\Scalar\String_('Foo'))]), $false, $yes),
		'function_exists(\'bar\')' => $holder($funcCall('function_exists', [$arg(new \PhpParser\Node\Scalar\String_('bar'))]), $bool, $maybe),
		'\class_exists(\'Baz\')' => $holder($funcCall('class_exists', [$arg(new \PhpParser\Node\Scalar\String_('Baz'))], true), $true, $yes),
		'class_exists(...)' => $holder($funcCall('class_exists', [new \PhpParser\Node\VariadicPlaceholder()]), $false, $yes),
		'enum_exists($x)' => $holder($funcCall('enum_exists', [$arg(new \PhpParser\Node\Expr\Variable('x'))]), $false, $yes),
		'interface_exists()' => $holder($funcCall('interface_exists'), $false, $yes),
		'$f(\'x\')' => $holder(new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Expr\Variable('f'), [$arg(new \PhpParser\Node\Scalar\String_('x'))]), $false, $yes),
		'strlen(\'x\')' => $holder($funcCall('strlen', [$arg(new \PhpParser\Node\Scalar\String_('x'))]), $int, $yes),
	];
	$nativeExpressionTypes = [
		'ob_get_level()' => $expressionTypes['ob_get_level()'],
		'$_GET' => $expressionTypes['$_GET'],
		'$_GET[\'x\']' => $expressionTypes['$_GET[\'x\']'],
		'class_exists(\'Foo\')' => $expressionTypes['class_exists(\'Foo\')'],
		'$b' => $holder(new \PhpParser\Node\Expr\Variable('b'), $int, $yes),
		'openssl_error_string()' => $holder($funcCall('openssl_error_string'), $string, $yes),
	];

	return [$expressionTypes, $nativeExpressionTypes];
};
$vehResults = [];
foreach (['php' => \PHPStan\Analyser\VolatileExpressionHelper::class, 'native' => \PHPStanTurbo\VolatileExpressionHelper::class] as $side => $vehClass) {
	$r = [];
	[$e, $n] = $vehInputs($side);
	$r[] = [$vehClass::invalidateVolatileFunctionCalls($e, $n), array_keys($e), array_keys($n)];
	$e1 = [];
	$n1 = [];
	$r[] = [$vehClass::invalidateVolatileFunctionCalls($e1, $n1), $e1, $n1];
	[$e, $n] = $vehInputs($side);
	$r[] = [$vehClass::invalidateSuperglobals($e, $n), array_keys($e), array_keys($n)];
	[$e, $n] = $vehInputs($side);
	unset($e['$_GET'], $n['$_GET']);
	$r[] = [$vehClass::invalidateSuperglobals($e, $n), array_keys($e), array_keys($n)];
	[$e, $n] = $vehInputs($side);
	$r[] = [$vehClass::invalidateNegativeExistenceChecks($vehScope, $e, $n), array_keys($e), array_keys($n)];
	[$e, $n] = $vehInputs($side);
	$r[] = [$vehClass::invalidateNegativeExistenceChecks($vehScope, $e, $n, ['function_exists']), array_keys($e), array_keys($n)];
	[$e, $n] = $vehInputs($side);
	$r[] = [$vehClass::invalidateNegativeExistenceChecks($vehScope, $e, $n, ['class_exists', 'enum_exists'], '\FOO'), array_keys($e), array_keys($n)];
	[$e, $n] = $vehInputs($side);
	$r[] = [$vehClass::invalidateNegativeExistenceChecks($vehScope, $e, $n, ['class_exists'], 'Other'), array_keys($e), array_keys($n)];
	[$e, $n] = $vehInputs($side);
	$r[] = [$vehClass::invalidateNegativeExistenceChecks($vehScope, $e, $n, ['strlen']), array_keys($e), array_keys($n)];
	// the by-reference contract: the caller's copies change, the originals stay
	[$e, $n] = $vehInputs($side);
	$copyE = $e;
	$copyN = $n;
	$r[] = [$vehClass::invalidateVolatileFunctionCalls($copyE, $copyN), array_keys($e), array_keys($n), array_keys($copyE), array_keys($copyN)];
	$vehResults[$side] = $r;
}
check($vehResults['php'] === $vehResults['native'], 'VolatileExpressionHelper parity: ' . json_encode($vehResults['php']) . ' vs ' . json_encode($vehResults['native']));
check($vehResults['php'][0][0] === true && $vehResults['php'][2][0] === true && $vehResults['php'][4][0] === true && $vehResults['php'][3][0] === false, 'VolatileExpressionHelper: the fixture exercises removals and no-ops');

// ---- VariableFlow ----
// The factories build the PHP flow classes over shared nodes and writes;
// flows are compared structurally (class names modulo the prefix).
$vfDescribeWrite = static fn (?\PHPStan\Node\Variable\VariableWrite $write): ?array => $write === null ? null : [
	$write->getVariableName(),
	spl_object_id($write->getNode()),
	$write->getId(),
	$write->getKind(),
	$write->isOffsetWrite(),
	$write->getOffset(),
	$write->getParentId(),
	$write->replacesOffset(),
];
$vfDescribe = static function ($flow) use (&$vfDescribe, $vfDescribeWrite, $turboNorm) {
	if ($flow === null) {
		return null;
	}
	if (!$flow instanceof \PHPStan\Analyser\VariableFlow) {
		return 'not a flow: ' . get_debug_type($flow);
	}
	$d = ['class' => $turboNorm(get_class($flow)), 'kind' => $flow->kind];
	if ($flow instanceof \PHPStan\Analyser\VariableAccessFlow) {
		$d += [
			'name' => $flow->name,
			'write' => $vfDescribeWrite($flow->write),
			'type' => $flow->type?->describe(\PHPStan\Type\VerbosityLevel::precise()),
			'targetId' => $flow->targetId,
			'container' => $flow->container,
			'offset' => $flow->offset,
		];
	} elseif ($flow instanceof \PHPStan\Analyser\VariableSequenceFlow) {
		$d['children'] = array_map($vfDescribe, $flow->children);
	} elseif ($flow instanceof \PHPStan\Analyser\VariableControlFlow) {
		$d += [
			'children' => array_map($vfDescribe, $flow->children),
			'name' => $flow->name,
			'type' => $flow->type?->describe(\PHPStan\Type\VerbosityLevel::precise()),
			'level' => $flow->level,
			'atLeastOnce' => $flow->atLeastOnce,
			'canExit' => $flow->canExit,
			'catches' => array_map(static fn (array $catch) => [$catch[0]->describe(\PHPStan\Type\VerbosityLevel::precise()), $vfDescribe($catch[1])], $flow->catches),
			'arrow' => $flow->arrow !== null ? spl_object_id($flow->arrow) : null,
			'cases' => array_map(static fn (array $case) => [$vfDescribe($case[0]), $vfDescribe($case[1]), $case[2]], $flow->cases),
			'canRepeat' => $flow->canRepeat,
			'canContainAnyThrowable' => $flow->canContainAnyThrowable,
			'stmt' => $flow->stmt !== null ? spl_object_id($flow->stmt) : null,
			'bindings' => array_map($vfDescribeWrite, $flow->bindings),
			'ownWrites' => array_map($vfDescribeWrite, $flow->ownWrites),
		];
	} elseif ($flow instanceof \PHPStan\Analyser\VariableInputFlow) {
		$d += ['writeId' => $flow->writeId, 'targetId' => $flow->targetId];
	}

	return $d;
};
check((new ReflectionClass(\PHPStanTurbo\VariableFlow::class))->isAbstract(), 'VariableFlow: the native class is abstract');
check((new ReflectionClass(\PHPStanTurbo\VariableFlow::class))->getConstants() === (new ReflectionClass(\PHPStan\Analyser\VariableFlow::class))->getConstants(), 'VariableFlow: the kind constants');
$vfNativeSubclass = new class('x') extends \PHPStanTurbo\VariableFlow {

	public function __construct(string $kind)
	{
		parent::__construct($kind);
	}

	public function again(string $kind): void
	{
		parent::__construct($kind);
	}

};
check($vfNativeSubclass->kind === 'x', 'VariableFlow: the protected constructor fills the readonly $kind slot');
try {
	$vfNativeSubclass->again('y');
	check(false, 'VariableFlow: a second construction must throw');
} catch (\Error $e) {
	check(str_contains($e->getMessage(), 'readonly property'), 'VariableFlow: readonly $kind: ' . $e->getMessage());
}
try {
	$vfNativeSubclass->kind = 'z';
	check(false, 'VariableFlow: $kind is readonly');
} catch (\Error $e) {
	check(true, '');
}
$vfNodeA = new \PhpParser\Node\Expr\Variable('a');
$vfNodeB = new \PhpParser\Node\Expr\Variable('b');
$vfArrow = new \PhpParser\Node\Expr\ArrowFunction(['expr' => $vfNodeA]);
$vfForeach = new \PhpParser\Node\Stmt\Foreach_($vfNodeA, $vfNodeB);
$vfWriteA = new \PHPStan\Node\Variable\VariableWrite('a', $vfNodeA, 11, \PHPStan\Node\Variable\VariableWrite::KIND_ASSIGN);
$vfWriteItem = new \PHPStan\Node\Variable\VariableWrite('b', $vfNodeB, 12, \PHPStan\Node\Variable\VariableWrite::KIND_ARRAY_LITERAL_ITEM, false, null, 11);
$vfWriteOffset = new \PHPStan\Node\Variable\VariableWrite('a', $vfNodeA, 13, \PHPStan\Node\Variable\VariableWrite::KIND_ARRAY_DIM_WRITE, true, 'k', null, false);
$vfInt = new \PHPStanTurbo\IntegerType();
$vfString = new \PHPStanTurbo\StringType();
$vfResults = [];
foreach (['php' => \PHPStan\Analyser\VariableFlow::class, 'native' => \PHPStanTurbo\VariableFlow::class] as $side => $vf) {
	$r = [];
	$readA = $vf::read('a');
	$readB = $vf::read('b', 7, true, 'k');
	$r[] = [$vfDescribe($readA), $vfDescribe($readB), $vfDescribe($vf::read('b', null, false, 3)), $vf::read('this'), $vf::read('_GET'), $vf::read('GLOBALS', 1)];
	$r[] = [$vf::sequence(), $vf::sequence(null, null), $vf::sequence(null, $readA) === $readA, $vfDescribe($vf::sequence($readA, null, $readB)), $vfDescribe($vf::sequence(...[$readA, $readB, $readA]))];
	$r[] = [$vf::choice(), $vf::choice($readA) === $readA, $vf::choice($readA, $readA) === $readA, $vf::choice(null, null), $vfDescribe($vf::choice($readA, null)), $vfDescribe($vf::choice($readA, $readB, null))];
	$r[] = [$vfDescribe($vf::arrow($vfArrow, $readA, null)), $vfDescribe($vf::arrow($vfArrow, null, $readB))];
	$r[] = [$vfDescribe($vf::conditional($readA, $readB, null, true)), $vfDescribe($vf::conditional($readA, $readB, $readA, false)), $vfDescribe($vf::conditional(null, $readB, $readA, null)), $vf::conditional(null, null, null, null), $vfDescribe($vf::conditional($readA, null, null, true))];
	$r[] = [$vfDescribe($vf::switch($readA, [[$readB, $readA, false], [null, null, true]], true)), $vfDescribe($vf::switch(null, [], false))];
	$r[] = [$vfDescribe($vf::write($vfWriteA)), $vfDescribe($vf::write($vfWriteItem, $vfInt)), $vfDescribe($vf::write($vfWriteOffset, null)), $vfDescribe($vf::discard($vfWriteA)), $vfDescribe($vf::discard($vfWriteItem))];
	$r[] = [$vfDescribe($vf::inputs(11, null)), $vfDescribe($vf::inputs(12, 7))];
	$r[] = [$vfDescribe($vf::escape('a')), $vfDescribe($vf::escape('this')), $vfDescribe($vf::mention('b')), $vfDescribe($vf::all($vf::READ_ALL)), $vfDescribe($vf::all($vf::MENTION_ALL)), $vfDescribe($vf::all($vf::OPAQUE))];
	$r[] = [$vfDescribe($vf::exit($vf::RETURN)), $vfDescribe($vf::exit($vf::BREAK, 2)), $vfDescribe($vf::exit($vf::CONTINUE, 1, 'x')), $vfDescribe($vf::exit($vf::STOP, 3, null))];
	$r[] = [$vfDescribe($vf::throwing($vfInt, true)), $vfDescribe($vf::throwing($vfString, false, true))];
	$r[] = [$vf::dead(null), $vfDescribe($vf::dead($readA))];
	$r[] = [$vfDescribe($vf::loop($readA, $readB, null, true, false)), $vfDescribe($vf::loop(null, null, $readA, false, true, false))];
	$r[] = [$vf::loopStatement($vfForeach, $readA, [], [$vfWriteA]) === $readA, $vf::loopStatement($vfForeach, null, [], []), $vfDescribe($vf::loopStatement($vfForeach, $readA, [$vfWriteA], [$vfWriteA, $vfWriteOffset]))];
	$r[] = [$vfDescribe($vf::tryCatch($readA, [[$vfInt, $readB], [$vfString, null]], null)), $vfDescribe($vf::tryCatch(null, [], $readB))];
	$vfResults[$side] = $r;
}
check($vfResults['php'] === $vfResults['native'], 'VariableFlow parity: ' . json_encode($vfResults['php']) . ' vs ' . json_encode($vfResults['native']));
// a VariableWrite that skipped its constructor: the twin's getters throw
$vfRawWrite = (new ReflectionClass(\PHPStan\Node\Variable\VariableWrite::class))->newInstanceWithoutConstructor();
$vfRawResults = [];
foreach (['php' => \PHPStan\Analyser\VariableFlow::class, 'native' => \PHPStanTurbo\VariableFlow::class] as $side => $vf) {
	try {
		$vf::write($vfRawWrite);
		$vfRawResults[$side] = 'no error';
	} catch (\Error $e) {
		$vfRawResults[$side] = [get_class($e), $e->getMessage()];
	}
}
check($vfRawResults['php'] === $vfRawResults['native'], 'VariableFlow: write() over an unconstructed VariableWrite: ' . json_encode($vfRawResults));

// ---- VariableFlowBuilder ----
// Shared nodes and a scope; per side a storage of the side's class holding
// ExpressionResults that carry a flow and a type (set through reflection —
// the results' construction is not what is under test), and an ArgsResult.
$vfbScope = $vehScope
	->assignVariable('arr', new \PHPStan\Type\ArrayType($vfInt, $vfString), new \PHPStan\Type\ArrayType($vfInt, $vfString), \PHPStan\TrinaryLogic::createYes())
	->assignVariable('str', $vfString, $vfString, \PHPStan\TrinaryLogic::createYes())
	->assignVariable('int', $vfInt, $vfInt, \PHPStan\TrinaryLogic::createYes())
	->assignVariable('maybe', $vfInt, $vfInt, \PHPStan\TrinaryLogic::createMaybe());
$vfbResultReflection = new ReflectionClass(\PHPStan\Analyser\ExpressionResult::class);
$vfbMakeResult = static function (?\PHPStan\Analyser\VariableFlow $flow, ?\PHPStan\Type\Type $type = null) use ($vfbResultReflection): \PHPStan\Analyser\ExpressionResult {
	$result = $vfbResultReflection->newInstanceWithoutConstructor();
	$vfbResultReflection->getProperty('variableFlow')->setValue($result, $flow);
	$vfbResultReflection->getProperty('cachedType')->setValue($result, $type ?? new \PHPStanTurbo\MixedType());
	return $result;
};
$vfbN = [
	'a' => new \PhpParser\Node\Expr\Variable('a'),
	'b' => new \PhpParser\Node\Expr\Variable('b'),
	'this' => new \PhpParser\Node\Expr\Variable('this'),
	'get' => new \PhpParser\Node\Expr\Variable('_GET'),
	'arr' => new \PhpParser\Node\Expr\Variable('arr'),
	'str' => new \PhpParser\Node\Expr\Variable('str'),
	'int' => new \PhpParser\Node\Expr\Variable('int'),
	'maybe' => new \PhpParser\Node\Expr\Variable('maybe'),
	'unknown' => new \PhpParser\Node\Expr\Variable('unknown'),
	'varVar' => new \PhpParser\Node\Expr\Variable(new \PhpParser\Node\Expr\Variable('name')),
	'k' => new \PhpParser\Node\Scalar\String_('k'),
	'one' => new \PhpParser\Node\Scalar\Int_(1),
	'call' => new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('f')),
	'closure' => new \PhpParser\Node\Expr\Closure(),
	'arrow' => new \PhpParser\Node\Expr\ArrowFunction(['expr' => new \PhpParser\Node\Scalar\Int_(2)]),
	'name' => new \PhpParser\Node\Name('Foo'),
];
$vfbN['dimArrK'] = new \PhpParser\Node\Expr\ArrayDimFetch($vfbN['arr'], $vfbN['k']);
$vfbN['dimArrNested'] = new \PhpParser\Node\Expr\ArrayDimFetch($vfbN['dimArrK'], $vfbN['one']);
$vfbN['dimArrNull'] = new \PhpParser\Node\Expr\ArrayDimFetch($vfbN['arr'], null);
$vfbN['dimStr'] = new \PhpParser\Node\Expr\ArrayDimFetch($vfbN['str'], $vfbN['one']);
$vfbN['dimInt'] = new \PhpParser\Node\Expr\ArrayDimFetch($vfbN['int'], $vfbN['one']);
$vfbN['dimMaybe'] = new \PhpParser\Node\Expr\ArrayDimFetch($vfbN['maybe'], $vfbN['k']);
$vfbN['dimUnknown'] = new \PhpParser\Node\Expr\ArrayDimFetch($vfbN['unknown'], $vfbN['k']);
$vfbN['dimThis'] = new \PhpParser\Node\Expr\ArrayDimFetch($vfbN['this'], $vfbN['k']);
$vfbN['dimGet'] = new \PhpParser\Node\Expr\ArrayDimFetch($vfbN['get'], $vfbN['k']);
$vfbN['dimCall'] = new \PhpParser\Node\Expr\ArrayDimFetch($vfbN['call'], $vfbN['k']);
$vfbN['dimVarVar'] = new \PhpParser\Node\Expr\ArrayDimFetch($vfbN['varVar'], $vfbN['k']);
$vfbN['prop'] = new \PhpParser\Node\Expr\PropertyFetch($vfbN['a'], 'p');
$vfbN['propExpr'] = new \PhpParser\Node\Expr\PropertyFetch($vfbN['a'], $vfbN['b']);
$vfbN['nullsafeProp'] = new \PhpParser\Node\Expr\NullsafePropertyFetch($vfbN['a'], 'p');
$vfbN['staticProp'] = new \PhpParser\Node\Expr\StaticPropertyFetch($vfbN['name'], 'p');
$vfbN['staticPropExpr'] = new \PhpParser\Node\Expr\StaticPropertyFetch($vfbN['a'], $vfbN['b']);
$vfbN['dimProp'] = new \PhpParser\Node\Expr\ArrayDimFetch($vfbN['prop'], $vfbN['k']);
$vfbN['list'] = new \PhpParser\Node\Expr\List_([
	new \PhpParser\Node\ArrayItem($vfbN['a'], $vfbN['k']),
	null,
	new \PhpParser\Node\ArrayItem($vfbN['b'], null, true),
	new \PhpParser\Node\ArrayItem($vfbN['dimArrK']),
	new \PhpParser\Node\ArrayItem(new \PhpParser\Node\Expr\List_([new \PhpParser\Node\ArrayItem($vfbN['str'])])),
]);
$vfbN['array'] = new \PhpParser\Node\Expr\Array_([new \PhpParser\Node\ArrayItem($vfbN['int'])]);
$vfbN['emptyList'] = new \PhpParser\Node\Expr\List_([]);
$vfbN['callArgs'] = new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('g'), [
	new \PhpParser\Node\Arg($vfbN['a']),
	new \PhpParser\Node\Arg($vfbN['b'], true),
	new \PhpParser\Node\Arg($vfbN['dimArrK']),
	new \PhpParser\Node\Arg($vfbN['closure']),
	new \PhpParser\Node\Arg($vfbN['call']),
	new \PhpParser\Node\Arg($vfbN['arrow']),
]);
$vfbN['callArgs']->setAttribute('startFilePos', 10);
$vfbN['callArgs']->setAttribute('endFilePos', 20);
$vfbN['samePos'] = new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('h'));
$vfbN['samePos']->setAttribute('startFilePos', 10);
$vfbN['samePos']->setAttribute('endFilePos', 20);
$vfbN['otherPos'] = new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('h'));
$vfbN['otherPos']->setAttribute('startFilePos', 10);
$vfbN['otherPos']->setAttribute('endFilePos', 21);
$vfbN['noPos'] = new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('h'));
$vfbN['fcc'] = new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('g'), [new \PhpParser\Node\VariadicPlaceholder()]);
$vfbN['fcc']->setAttribute('startFilePos', 30);
$vfbN['fcc']->setAttribute('endFilePos', 40);
$vfbThrowable = new \PHPStan\Type\ObjectType(\Throwable::class);
$vfbThrowPoints = [
	\PHPStan\Analyser\InternalThrowPoint::createExplicit($vfbScope, $vfInt, $vfbN['callArgs'], false),
	\PHPStan\Analyser\InternalThrowPoint::createExplicit($vfbScope, $vfString, $vfbN['closure'], true),
	\PHPStan\Analyser\InternalThrowPoint::createExplicit($vfbScope, $vfbThrowable, $vfbN['arrow'], false),
	\PHPStan\Analyser\InternalThrowPoint::createExplicit($vfbScope, $vfInt, $vfbN['samePos'], true),
	\PHPStan\Analyser\InternalThrowPoint::createExplicit($vfbScope, $vfString, $vfbN['otherPos'], false),
	\PHPStan\Analyser\InternalThrowPoint::createExplicit($vfbScope, $vfInt, $vfbN['noPos'], false),
	\PHPStan\Analyser\InternalThrowPoint::createImplicit($vfbScope, $vfbN['a']),
];
$vfbSides = [
	'php' => [\PHPStan\Analyser\VariableFlowBuilder::class, \PHPStan\Analyser\VariableFlow::class, \PHPStan\Analyser\ExpressionResultStorage::class],
	'native' => [\PHPStanTurbo\VariableFlowBuilder::class, \PHPStanTurbo\VariableFlow::class, \PHPStanTurbo\ExpressionResultStorage::class],
	'native over a PHP storage' => [\PHPStanTurbo\VariableFlowBuilder::class, \PHPStanTurbo\VariableFlow::class, \PHPStan\Analyser\ExpressionResultStorage::class],
];
$vfbResults = [];
foreach ($vfbSides as $side => [$builder, $vf, $storageClass]) {
	$storage = new $storageClass();
	$flowA = $vf::read('a');
	$flowB = $vf::escape('b');
	$flowK = $vf::mention('k');
	$storage->storeExpressionResult($vfbN['a'], $vfbMakeResult($flowA, $vfInt));
	$storage->storeExpressionResult($vfbN['b'], $vfbMakeResult($flowB));
	$storage->storeExpressionResult($vfbN['k'], $vfbMakeResult($flowK, new \PHPStanTurbo\ConstantStringType('k')));
	$storage->storeExpressionResult($vfbN['one'], $vfbMakeResult(null, new \PHPStanTurbo\ConstantIntegerType(1)));
	$storage->storeExpressionResult($vfbN['call'], $vfbMakeResult($vf::all($vf::OPAQUE)));
	$storage->storeExpressionResult($vfbN['varVar'], $vfbMakeResult($vf::mention('name')));
	$storage->storeExpressionResult($vfbN['dimArrK'], $vfbMakeResult($vf::read('arr', null, false, 'k'), $vfString));
	$storage->storeExpressionResult($vfbN['closure'], $vfbMakeResult($vf::escape('c')));
	$storage->storeExpressionResult($vfbN['prop'], $vfbMakeResult($vf::mention('prop')));
	$argsResult = new \PHPStan\Analyser\ArgsResult(
		$vfbMakeResult(null),
		null,
		[spl_object_id($vfbN['a']) => $vfbMakeResult($vf::read('a', 99)), spl_object_id($vfbN['closure']) => $vfbMakeResult(null)],
		[spl_object_id($vfbN['call']) => true],
	);

	$r = [];
	$r[] = [$vfDescribe($builder::throws($vfbN['callArgs'], $vfbThrowPoints)), $vfDescribe($builder::throws($vfbN['fcc'], $vfbThrowPoints)), $builder::throws($vfbN['a'], []), $vfDescribe($builder::throws($vfbN['a'], $vfbThrowPoints))];
	$r[] = [$vfDescribe($builder::arguments($vfbN['callArgs'], $argsResult, $storage)), $builder::arguments($vfbN['call'], $argsResult, $storage)];
	$r[] = [$vfDescribe($builder::child($vfbN['a'], $storage)), $builder::child($vfbN['unknown'], $storage), $builder::child($vfbN['name'], $storage), $builder::child(null, $storage), $builder::child($vfbN['one'], $storage)];
	foreach (['a', 'this', 'get', 'varVar', 'list', 'array', 'dimArrK', 'dimArrNested', 'dimArrNull', 'dimCall', 'dimVarVar', 'dimProp', 'prop', 'propExpr', 'nullsafeProp', 'staticProp', 'staticPropExpr', 'call', 'k'] as $key) {
		$r[] = [$key, $vfDescribe($builder::targetRead($vfbN[$key], $storage, true)), $vfDescribe($builder::targetRead($vfbN[$key], $storage, false)), $vfDescribe($builder::targetRead($vfbN[$key], $storage, true, 5))];
	}
	foreach (['a', 'this', 'get', 'varVar', 'list', 'array', 'emptyList', 'dimArrK', 'dimArrNested', 'dimArrNull', 'dimStr', 'dimInt', 'dimMaybe', 'dimUnknown', 'dimThis', 'dimGet', 'dimCall', 'dimVarVar', 'dimProp', 'prop', 'call'] as $key) {
		$write = $builder::targetWrite($vfbN[$key], \PHPStan\Node\Variable\VariableWrite::KIND_ASSIGN, $vfbScope, $storage);
		$r[] = [$key, $vfDescribe($write), $vfDescribe($builder::targetWrite($vfbN[$key], \PHPStan\Node\Variable\VariableWrite::KIND_PRE_INC, $vfbScope, $storage, $vfInt)), $vfDescribeWrite($builder::writeSite($vfbN[$key], \PHPStan\Node\Variable\VariableWrite::KIND_ASSIGN, $vfbScope, $storage)), array_map($vfDescribeWrite, $builder::writes($write))];
	}
	$r[] = [$builder::writes(null), array_map($vfDescribeWrite, $builder::writes($vf::sequence($vf::write($vfWriteA), $vf::sequence($vf::escape('x'), $vf::write($vfWriteItem)), $vf::dead($vf::write($vfWriteOffset))))), $builder::writes($vf::all($vf::OPAQUE))];
	foreach (['a', 'this', 'varVar', 'dimArrNested', 'dimCall', 'dimVarVar', 'prop', 'call'] as $key) {
		$r[] = [$key, $vfDescribe($builder::escapeRoot($vfbN[$key]))];
	}
	$vfbResults[$side] = $r;
}
check($vfbResults['php'] === $vfbResults['native'], 'VariableFlowBuilder parity: ' . json_encode($vfbResults['php']) . ' vs ' . json_encode($vfbResults['native']));
check($vfbResults['php'] === $vfbResults['native over a PHP storage'], 'VariableFlowBuilder parity over a PHP storage: ' . json_encode($vfbResults['php']) . ' vs ' . json_encode($vfbResults['native over a PHP storage']));

// ---- VariableLivenessResolver ----
// Flow trees built once (the PHP flow classes over shared nodes and writes)
// and resolved by both sides; the VariableWritesNode is compared field by
// field (writes and types described, loop statements by object id). A throw
// that can contain any Throwable is only placed outside try/catch: inside,
// the PHP twin instantiates the PHP ObjectType(Throwable) against the native
// catch types, which the prefixed declaration cannot mix (see type-family.php).
$vlrDescribe = static function (\PHPStan\Node\VariableWritesNode $node) use ($vfDescribeWrite): array {
	$d = [];
	foreach (['writes', 'readWriteIds', 'usedWriteIds', 'coveredWriteIds', 'readVariableNames', 'redundantWriteTypes', 'referencedVariableNames', 'untrackedVariableNames', 'variableOverwritingLoops', 'opaque', 'allVariableNamesReferenced'] as $property) {
		$value = (new ReflectionProperty($node, $property))->getValue($node);
		if ($property === 'writes') {
			$value = array_map($vfDescribeWrite, $value);
		} elseif ($property === 'redundantWriteTypes') {
			$value = array_map(static fn (\PHPStan\Type\Type $type): string => $type->describe(\PHPStan\Type\VerbosityLevel::precise()), $value);
		} elseif ($property === 'variableOverwritingLoops') {
			$value = array_map(static fn (object $statement): int => spl_object_id($statement), $value);
		}
		$d[$property] = $value;
	}
	$d['functionLike'] = spl_object_id($node->getFunctionLike());

	return $d;
};
$vlrF = \PHPStan\Analyser\VariableFlow::class;
$vlrVar = static fn (string $name) => new \PhpParser\Node\Expr\Variable($name);
$vlrWrite = static fn (string $name, int $id, int $kind = \PHPStan\Node\Variable\VariableWrite::KIND_ASSIGN, bool $offsetWrite = false, $offset = null, ?int $parentId = null, bool $replacesOffset = true) => new \PHPStan\Node\Variable\VariableWrite($name, $vlrVar($name), $id, $kind, $offsetWrite, $offset, $parentId, $replacesOffset);
$vlrInt = new \PHPStanTurbo\IntegerType();
$vlrString = new \PHPStanTurbo\StringType();
$vlrException = new \PHPStanTurbo\ObjectType(\Exception::class);
$vlrRuntime = new \PHPStanTurbo\ObjectType(\RuntimeException::class);
$vlrThrowable = new \PHPStanTurbo\ObjectType(\Throwable::class);
$vlrForeach = new \PhpParser\Node\Stmt\Foreach_($vlrVar('items'), $vlrVar('k'));
$vlrFor = new \PhpParser\Node\Stmt\For_();
$vlrArrow = new \PhpParser\Node\Expr\ArrowFunction(['params' => [new \PhpParser\Node\Param($vlrVar('p')), new \PhpParser\Node\Param($vlrVar('q'))], 'expr' => $vlrVar('p')]);
$vlrFunctions = [
	'function' => new \PhpParser\Node\Stmt\Function_('f', ['params' => [new \PhpParser\Node\Param($vlrVar('a')), new \PhpParser\Node\Param($vlrVar('r'), null, null, true), new \PhpParser\Node\Param($vlrVar('this'))]]),
	'closure by ref' => new \PhpParser\Node\Expr\Closure(['byRef' => true, 'uses' => [new \PhpParser\Node\ClosureUse($vlrVar('u')), new \PhpParser\Node\ClosureUse($vlrVar('ur'), true)]]),
	'method' => new \PhpParser\Node\Stmt\ClassMethod('m', ['params' => [new \PhpParser\Node\Param($vlrVar('promoted'), null, null, false, false, [], \PhpParser\Modifiers::PUBLIC)]]),
];
$vlrFlows = [
	'empty' => null,
	'plain' => $vlrF::sequence(
		$vlrF::write($vlrWrite('a', 1)),
		$vlrF::read('a'),
		$vlrF::write($vlrWrite('b', 2), $vlrInt),
		$vlrF::write($vlrWrite('c', 3)),
		$vlrF::inputs(3, null),
		$vlrF::write($vlrWrite('d', 4)),
		$vlrF::inputs(4, 5),
		$vlrF::write($vlrWrite('e', 5)),
		$vlrF::read('e'),
		$vlrF::write($vlrWrite('f', 6)),
		$vlrF::write($vlrWrite('f', 7)),
		$vlrF::read('f'),
		$vlrF::discard($vlrWrite('g', 8)),
		$vlrF::mention('m'),
		$vlrF::escape('h'),
		$vlrF::write($vlrWrite('h', 9)),
		$vlrF::write($vlrWrite('this', 10)),
		$vlrF::write($vlrWrite('_GET', 11)),
		$vlrF::read('unknown'),
	),
	'branches' => $vlrF::sequence(
		$vlrF::write($vlrWrite('a', 1)),
		$vlrF::conditional($vlrF::read('a'), $vlrF::write($vlrWrite('d', 2)), $vlrF::write($vlrWrite('d', 3)), null),
		$vlrF::read('d'),
		$vlrF::conditional(null, $vlrF::write($vlrWrite('x', 4)), $vlrF::write($vlrWrite('x', 5)), true),
		$vlrF::conditional(null, $vlrF::write($vlrWrite('y', 6)), $vlrF::write($vlrWrite('y', 7)), false),
		$vlrF::choice($vlrF::read('x'), $vlrF::read('y'), null),
		$vlrF::switch($vlrF::read('s'), [[$vlrF::read('c1'), $vlrF::sequence($vlrF::write($vlrWrite('sw', 8)), $vlrF::exit($vlrF::BREAK)), false], [null, $vlrF::sequence($vlrF::read('sw'), $vlrF::write($vlrWrite('sw', 9))), true]], false),
		$vlrF::switch($vlrF::read('s'), [[$vlrF::read('c2'), $vlrF::write($vlrWrite('ex', 10)), false]], true),
		$vlrF::read('ex'),
		$vlrF::dead($vlrF::sequence($vlrF::write($vlrWrite('dead', 11)), $vlrF::read('dead'))),
		$vlrF::exit($vlrF::RETURN, 1, 'a'),
		$vlrF::write($vlrWrite('after', 12)),
	),
	'loops' => $vlrF::sequence(
		$vlrF::write($vlrWrite('i', 1)),
		$vlrF::write($vlrWrite('acc', 2)),
		$vlrF::loop($vlrF::read('i'), $vlrF::sequence($vlrF::read('acc'), $vlrF::write($vlrWrite('acc', 3)), $vlrF::conditional($vlrF::read('stop'), $vlrF::exit($vlrF::BREAK), $vlrF::exit($vlrF::CONTINUE, 1), null), $vlrF::write($vlrWrite('unreached', 4))), $vlrF::write($vlrWrite('i', 5)), false, true),
		$vlrF::read('acc'),
		$vlrF::loop(null, $vlrF::sequence($vlrF::write($vlrWrite('w', 6)), $vlrF::exit($vlrF::STOP)), null, true, false, false),
		$vlrF::write($vlrWrite('k', 7)),
		$vlrF::loopStatement($vlrForeach, $vlrF::loop(null, $vlrF::sequence($vlrF::write($vlrWrite('k', 8, \PHPStan\Node\Variable\VariableWrite::KIND_FOREACH_KEY)), $vlrF::read('k')), null, false, true), [$vlrWrite('k', 8, \PHPStan\Node\Variable\VariableWrite::KIND_FOREACH_KEY)], [$vlrWrite('k', 8, \PHPStan\Node\Variable\VariableWrite::KIND_FOREACH_KEY)]),
		$vlrF::read('k'),
		$vlrF::write($vlrWrite('j', 9)),
		$vlrF::loopStatement($vlrFor, $vlrF::loop($vlrF::read('j'), $vlrF::read('body'), $vlrF::write($vlrWrite('j', 11)), false, true), [$vlrWrite('j', 10)], [$vlrWrite('j', 10), $vlrWrite('j', 11)]),
		$vlrF::escape('j'),
		$vlrF::loopStatement($vlrForeach, null, [], []),
	),
	'exceptions' => $vlrF::sequence(
		$vlrF::write($vlrWrite('t', 1)),
		$vlrF::tryCatch(
			$vlrF::sequence($vlrF::write($vlrWrite('t', 2)), $vlrF::throwing($vlrRuntime, true), $vlrF::write($vlrWrite('t', 3)), $vlrF::throwing($vlrString, false), $vlrF::write($vlrWrite('never', 4))),
			[[$vlrException, $vlrF::sequence($vlrF::read('t'), $vlrF::write($vlrWrite('t', 5)))], [$vlrThrowable, $vlrF::read('caught')]],
			$vlrF::sequence($vlrF::read('fin'), $vlrF::write($vlrWrite('fin', 6))),
		),
		$vlrF::read('t'),
		$vlrF::tryCatch($vlrF::sequence($vlrF::write($vlrWrite('u', 7)), $vlrF::throwing($vlrInt, false)), [[$vlrInt, $vlrF::read('u')]], null),
		$vlrF::loop(null, $vlrF::tryCatch($vlrF::sequence($vlrF::write($vlrWrite('l', 8)), $vlrF::exit($vlrF::BREAK, 1), $vlrF::exit($vlrF::CONTINUE, 2)), [], $vlrF::read('l')), null, true, true),
		$vlrF::throwing($vlrString, false, true),
		$vlrF::write($vlrWrite('unreachable', 9)),
	),
	'arrow and literals' => $vlrF::sequence(
		$vlrF::write($vlrWrite('outer', 1)),
		$vlrF::write($vlrWrite('p', 2)),
		$vlrF::arrow($vlrArrow, $vlrF::sequence($vlrF::read('p'), $vlrF::read('outer'), $vlrF::write($vlrWrite('inner', 3))), $vlrF::read('res')),
		$vlrF::read('p'),
		$vlrF::write($vlrWrite('list', 4)),
		$vlrF::write($vlrWrite('x', 5, \PHPStan\Node\Variable\VariableWrite::KIND_LIST_ITEM, false, null, 4)),
		$vlrF::write($vlrWrite('y', 6, \PHPStan\Node\Variable\VariableWrite::KIND_LIST_ITEM, false, null, 4)),
		$vlrF::read('x'),
		$vlrF::inputs(4, null),
		$vlrF::write($vlrWrite('o', 7, \PHPStan\Node\Variable\VariableWrite::KIND_ARRAY_DIM_WRITE, true, 'k')),
		$vlrF::write($vlrWrite('o', 8, \PHPStan\Node\Variable\VariableWrite::KIND_ARRAY_DIM_WRITE, true, 1, null, false)),
		$vlrF::write($vlrWrite('o', 9, \PHPStan\Node\Variable\VariableWrite::KIND_ARRAY_DIM_WRITE, true, null)),
		$vlrF::read('o', null, false, 'k'),
		$vlrF::read('o', 12, true),
		$vlrF::read('o', 12),
		$vlrF::write($vlrWrite('o', 10, \PHPStan\Node\Variable\VariableWrite::KIND_ARRAY_DIM_WRITE, true, 'k')),
		$vlrF::escape('o'),
	),
	'read all' => $vlrF::sequence($vlrF::write($vlrWrite('a', 1)), $vlrF::write($vlrWrite('o', 2, \PHPStan\Node\Variable\VariableWrite::KIND_ARRAY_DIM_WRITE, true, 'k')), $vlrF::all($vlrF::READ_ALL), $vlrF::write($vlrWrite('b', 3)), $vlrF::mention('c')),
	'mention all' => $vlrF::sequence($vlrF::write($vlrWrite('a', 1)), $vlrF::all($vlrF::MENTION_ALL)),
	'opaque' => $vlrF::sequence($vlrF::write($vlrWrite('a', 1)), $vlrF::all($vlrF::OPAQUE), $vlrF::read('a')),
];
$vlrResults = [];
foreach (['php' => \PHPStan\Analyser\VariableLivenessResolver::class, 'native' => \PHPStanTurbo\VariableLivenessResolver::class] as $side => $resolver) {
	$r = [];
	foreach ($vlrFunctions as $functionLabel => $function) {
		foreach ($vlrFlows as $flowLabel => $flow) {
			$r[$functionLabel . ' / ' . $flowLabel] = $vlrDescribe($resolver::resolve($function, $flow));
		}
	}
	$vlrResults[$side] = $r;
}
foreach ($vlrResults['php'] as $label => $described) {
	check($described === $vlrResults['native'][$label], "VariableLivenessResolver parity ($label): " . json_encode($described) . ' vs ' . json_encode($vlrResults['native'][$label]));
}
check(count($vlrResults['php']['function / loops']['variableOverwritingLoops']) === 2 && $vlrResults['php']['function / read all']['readVariableNames'] !== [], 'VariableLivenessResolver: the fixture exercises binding probes and READ_ALL');

// ---- MutatingScope ----
// scope-family.php rebuilds real walk scopes on both sides — the PHP twin
// under its real name, the native class under the prefix — and compares
// every method, every create() argument list and the scopes they answer.
$covered[\PHPStan\Analyser\MutatingScope::class] = true;
require __DIR__ . '/scope-family.php';

// ---- ClassStatementsGatherer ----
// replays a real walk's (node, scope) stream into both gatherers
$covered[\PHPStan\Node\ClassStatementsGatherer::class] = true;
require __DIR__ . '/class-statements-gatherer.php';

// ---- ClassReflection ----
// reflection-family.php rebuilds real class reflections on both sides —
// the PHP twin under its real name, the native class under the prefix —
// and compares every method and every memo slot.
$covered[\PHPStan\Reflection\ClassReflection::class] = true;
require __DIR__ . '/reflection-family.php';

// ---- ExpressionResult ----
// Results built by both sides from the same scopes, expressions, callbacks
// and extension collections; every public method's answer is compared, and a
// result's state is compared field by field through reflection.
$covered[\PHPStan\Analyser\ExpressionResult::class] = true;
$erScope = $vehScope
	->assignVariable('a', $vfInt, $vfInt, \PHPStan\TrinaryLogic::createYes())
	->assignVariable('s', $vfString, $vfString, \PHPStan\TrinaryLogic::createYes())
	->assignVariable('m', $vfInt, $vfInt, \PHPStan\TrinaryLogic::createMaybe());
$erOtherScope = $vehScope
	->assignVariable('a', $vfString, $vfString, \PHPStan\TrinaryLogic::createYes())
	->assignVariable('s', $vfString, $vfString, \PHPStan\TrinaryLogic::createYes());
$erWiderScope = $vehScope
	->assignVariable('a', new \PHPStanTurbo\UnionType([$vfInt, $vfString]), new \PHPStanTurbo\UnionType([$vfInt, $vfString]), \PHPStan\TrinaryLogic::createYes())
	->assignVariable('s', $vfString, $vfString, \PHPStan\TrinaryLogic::createYes());
$erNoExtensions = new \PHPStan\DependencyInjection\DirectExtensionsCollection([]);
$erExtensionCalls = 0;
$erHitExtensions = new \PHPStan\DependencyInjection\DirectExtensionsCollection([
	new class($erExtensionCalls) implements \PHPStan\Type\ExpressionTypeResolverExtension {

		public function __construct(private int &$calls)
		{
		}

		public function getType(\PhpParser\Node\Expr $expr, \PHPStan\Analyser\Scope $scope): ?\PHPStan\Type\Type
		{
			$this->calls++;
			return $expr instanceof \PhpParser\Node\Expr\Variable && $expr->name === 'ext' ? new \PHPStanTurbo\ConstantStringType('from extension') : null;
		}

	},
]);
$erN = [
	'variable a' => new \PhpParser\Node\Expr\Variable('a'),
	'variable unknown' => new \PhpParser\Node\Expr\Variable('nope'),
	'variable ext' => new \PhpParser\Node\Expr\Variable('ext'),
	'variable variable' => new \PhpParser\Node\Expr\Variable(new \PhpParser\Node\Expr\Variable('a')),
	'func call' => new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('f'), [new \PhpParser\Node\Arg(new \PhpParser\Node\Expr\Variable('a'))]),
	'func call fcc' => new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('f'), [new \PhpParser\Node\VariadicPlaceholder()]),
	'dynamic func call' => new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Expr\Variable('f')),
	'method call' => new \PhpParser\Node\Expr\MethodCall(new \PhpParser\Node\Expr\Variable('a'), 'm', [new \PhpParser\Node\Arg(new \PhpParser\Node\Expr\Variable('s'))]),
	'nullsafe method call' => new \PhpParser\Node\Expr\NullsafeMethodCall(new \PhpParser\Node\Expr\Variable('m'), 'm'),
	'static call' => new \PhpParser\Node\Expr\StaticCall(new \PhpParser\Node\Name('Foo'), 'm'),
	'closure' => new \PhpParser\Node\Expr\Closure(['uses' => [new \PhpParser\Node\ClosureUse(new \PhpParser\Node\Expr\Variable('a'))], 'stmts' => [new \PhpParser\Node\Stmt\Expression(new \PhpParser\Node\Expr\Variable('inner'))]]),
	'arrow' => new \PhpParser\Node\Expr\ArrowFunction(['expr' => new \PhpParser\Node\Expr\Variable('s')]),
	'dim fetch' => new \PhpParser\Node\Expr\ArrayDimFetch(new \PhpParser\Node\Expr\Variable('a'), new \PhpParser\Node\Expr\Variable('s')),
	'this fetch' => new \PhpParser\Node\Expr\PropertyFetch(new \PhpParser\Node\Expr\Variable('this'), 'p'),
	'nullsafe fetch' => new \PhpParser\Node\Expr\NullsafePropertyFetch(new \PhpParser\Node\Expr\Variable('a'), 'p'),
];
$erTypes = [
	'int' => [$vfInt, $vfInt],
	'void' => [new \PHPStanTurbo\VoidType(), new \PHPStanTurbo\VoidType()],
	'string' => [$vfString, $vfString],
];
$erKnownScopes = ['erScope' => $erScope, 'erOtherScope' => $erOtherScope, 'erWiderScope' => $erWiderScope, 'vehScope' => $vehScope];
$erDescribeValue = static function ($value) use ($turboNorm, $erKnownScopes, &$erDescribeValue) {
	if ($value === null || is_bool($value) || is_int($value) || is_string($value)) {
		return $value;
	}
	if ($value instanceof \PHPStan\Type\Type) {
		return 'type:' . $value->describe(\PHPStan\Type\VerbosityLevel::precise());
	}
	if ($value instanceof \Closure) {
		return 'closure';
	}
	if (is_array($value)) {
		return array_map($erDescribeValue, $value);
	}
	if ($value instanceof \PHPStan\Analyser\MutatingScope) {
		// the fixture's scopes are shared between the sides: their identity
		// matters; a scope derived by a side is described by class only
		$known = array_search($value, $erKnownScopes, true);
		return $known !== false ? 'scope:' . $known : $turboNorm(get_class($value));
	}
	if ($value instanceof \PhpParser\Node) {
		// shared between the sides: the identity matters
		return $turboNorm(get_class($value)) . '#' . spl_object_id($value);
	}
	if (is_object($value)) {
		return $turboNorm(get_class($value));
	}
	return get_debug_type($value);
};
// the getSpecifiedTypes() memo is keyed by the context's object id: each
// side hands its results its own TypeSpecifierContext singletons (the native
// result derives its branch scopes with the native class), so the keys are
// described by the context they stand for
$erContextLabels = [];
foreach ([\PHPStan\Analyser\TypeSpecifierContext::class, \PHPStanTurbo\TypeSpecifierContext::class] as $erContextClass) {
	foreach (['createTrue', 'createTruthy', 'createFalse', 'createFalsey', 'createNull'] as $erContextFactory) {
		$erContextLabels[spl_object_id($erContextClass::$erContextFactory())] = $erContextFactory;
	}
}
$erDescribeResult = static function (object $result) use ($erDescribeValue, $erContextLabels): array {
	$d = [];
	foreach ((new ReflectionObject($result))->getProperties() as $property) {
		$d[$property->getName()] = $property->isInitialized($result) ? $erDescribeValue($property->getValue($result)) : 'uninitialized';
		if ($property->getName() !== 'specifiedTypes' || !is_array($d['specifiedTypes'])) {
			continue;
		}
		$memo = [];
		foreach ($d['specifiedTypes'] as $key => $specified) {
			$memo[($erContextLabels[$key >> 1] ?? 'unknown context') . (($key & 1) === 1 ? ' native' : '')] = $specified;
		}
		ksort($memo);
		$d['specifiedTypes'] = $memo;
	}
	ksort($d);

	return $d;
};
// the second constructor argument: the service both sides narrow equality checks through
$erDefaultNarrowingHelper = $scContainer->getByType(\PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper::class);
$erSides = ['php' => \PHPStan\Analyser\ExpressionResult::class, 'native' => \PHPStanTurbo\ExpressionResult::class];
$erResults = [];
foreach ($erSides as $side => $erClass) {
	$r = [];
	$erContext = $side === 'php' ? \PHPStan\Analyser\TypeSpecifierContext::class : \PHPStanTurbo\TypeSpecifierContext::class;
	$erExtensionCalls = 0;
	$flowA = \PHPStan\Analyser\VariableFlow::read('a');
	foreach ($erN as $exprLabel => $expr) {
		foreach ($erTypes as $typeLabel => [$type, $nativeType]) {
			$calls = 0;
			$typeCallback = static function (bool $native) use ($type, $nativeType, &$calls): \PHPStan\Type\Type {
				$calls++;
				return $native ? $nativeType : $type;
			};
			$specifyCalls = 0;
			// the context is the side's own class: the native result hands the
			// callback the native (here prefixed) TypeSpecifierContext singleton
			$specifyCallback = static function (object $context, bool $native) use (&$specifyCalls): \PHPStan\Analyser\SpecifiedTypes {
				$specifyCalls++;
				return new \PHPStan\Analyser\SpecifiedTypes();
			};
			$extensions = $exprLabel === 'variable ext' ? $erHitExtensions : $erNoExtensions;
			$lazy = new $erClass($extensions, $erDefaultNarrowingHelper, $erScope, $erScope, $expr, false, true, [], [], $typeCallback, $specifyCallback, variableFlow: $flowA);
			$eager = new $erClass($extensions, $erDefaultNarrowingHelper, $erScope, $erScope, $expr, true, false, [], [], null, $specifyCallback, type: $type, nativeType: $nativeType, containsNullsafe: true);
			$row = [];
			foreach (['lazy' => $lazy, 'eager' => $eager] as $kind => $result) {
				$row[$kind] = [
					'type' => $result->getType()->describe(\PHPStan\Type\VerbosityLevel::precise()),
					'type again' => $result->getType() === $result->getType(),
					'nativeType' => $result->getNativeType()->describe(\PHPStan\Type\VerbosityLevel::precise()),
					'keepVoid' => $result->getKeepVoidType(false)->describe(\PHPStan\Type\VerbosityLevel::precise()),
					'keepVoidNative' => $result->getKeepVoidType(true)->describe(\PHPStan\Type\VerbosityLevel::precise()),
					'canResolveOwnType' => $result->canResolveOwnType(),
					'hasYield' => $result->hasYield(),
					'isAlwaysTerminating' => $result->isAlwaysTerminating(),
					'containsNullsafe' => $result->containsNullsafe(),
					'scope' => $result->getScope() === $erScope,
					'beforeScope' => $result->getBeforeScope() === $erScope,
					'expr' => $result->getExpr() === $expr,
					'throwPoints' => $result->getThrowPoints(),
					'impurePoints' => $result->getImpurePoints(),
					'variableFlow' => $result->getVariableFlow() === $flowA,
					'argsResult' => $result->getArgsResult(),
					'onScope' => $result->getTypeOnScope($erScope, false)->describe(\PHPStan\Type\VerbosityLevel::precise()),
					'onScopeNative' => $result->getTypeOnScope($erScope, true)->describe(\PHPStan\Type\VerbosityLevel::precise()),
					'onOtherScope' => $result->getTypeOnScope($erOtherScope, false)->describe(\PHPStan\Type\VerbosityLevel::precise()),
					'onPromotedScope' => $result->getTypeOnScope($erOtherScope->doNotTreatPhpDocTypesAsCertain(), false)->describe(\PHPStan\Type\VerbosityLevel::precise()),
					'answersSame' => $result->answersOnScope($erScope, false),
					'answersOther' => $result->answersOnScope($erOtherScope, false),
					'answersOtherNative' => $result->answersOnScope($erOtherScope, true),
					'answersWider' => $result->answersOnScope($erWiderScope, false),
					'askSame' => $result->askScopeVariableStateMatches($erScope, false),
					'askOther' => $result->askScopeVariableStateMatches($erOtherScope, false),
					'askOtherNative' => $result->askScopeVariableStateMatches($erOtherScope, true),
					'askOtherRule' => $result->askScopeVariableStateMatches($erOtherScope, false, true),
					'askWiderRule' => $result->askScopeVariableStateMatches($erWiderScope, false, true),
					'askWider' => $result->askScopeVariableStateMatches($erWiderScope, false),
					'askEmpty' => $result->askScopeVariableStateMatches($vehScope, false, true),
					'specified' => get_class($result->getSpecifiedTypes($erContext::createTruthy())),
					'specified memo' => $result->getSpecifiedTypes($erContext::createTruthy()) === $result->getSpecifiedTypes($erContext::createTruthy()),
					'specified for scope' => get_class($result->getSpecifiedTypesForScope($erScope, $erContext::createFalsey())),
					'created' => $result->getCreatedTypes($vfInt, \PHPStan\Analyser\TypeSpecifierContext::createTruthy()),
					'created for scope' => $result->getCreatedTypesForScope($erScope, $vfInt, \PHPStan\Analyser\TypeSpecifierContext::createTruthy()),
					'truthy' => get_class($result->getTruthyScope()) . ($result->getTruthyScope() === $result->getTruthyScope() ? ' memo' : ''),
					'falsey' => get_class($result->getFalseyScope()) . ($result->getFalseyScope() === $result->getFalseyScope() ? ' memo' : ''),
					'issetability' => $erDescribeResult($result->getIssetabilityResolution($erScope, false)->getLink()),
					'issetability native' => $erDescribeResult($result->getIssetabilityResolution($erScope, true, true)->getLink()),
					'withScope same' => $result->withScope($erScope) === $result,
					'withScope other' => $erDescribeResult($result->withScope($erOtherScope)),
					'finalize' => $erDescribeResult($result->finalize($erOtherScope, true, true, ['t'], ['i'], null)),
					'atAskPosition' => $erDescribeResult($result->atAskPosition($erOtherScope)),
					'atAskPosition same' => $erDescribeResult($result->atAskPosition($erScope)),
					'deviced' => $erDescribeResult($result->onNonNullabilityDevicedScopes($erOtherScope, $erScope)),
					'state' => $erDescribeResult($result),
					'callbackCalls' => $calls,
					'specifyCalls' => $specifyCalls,
				];
			}
			$r[$exprLabel . ' / ' . $typeLabel] = $row;
		}
	}
	$r['extension calls'] = $erExtensionCalls;
	// the constructor's invariants
	foreach ([
		'callback and type' => static fn () => new $erClass($erNoExtensions, $erDefaultNarrowingHelper, $erScope, $erScope, $erN['variable a'], false, false, [], [], static fn (bool $n) => $vfInt, static fn () => new \PHPStan\Analyser\SpecifiedTypes(), type: $vfInt, nativeType: $vfInt),
		'nothing' => static fn () => new $erClass($erNoExtensions, $erDefaultNarrowingHelper, $erScope, $erScope, $erN['variable a'], false, false, [], [], null, static fn () => new \PHPStan\Analyser\SpecifiedTypes()),
		'only resolved type' => static fn () => new $erClass($erNoExtensions, $erDefaultNarrowingHelper, $erScope, $erScope, $erN['variable a'], false, false, [], [], null, static fn () => new \PHPStan\Analyser\SpecifiedTypes(), resolvedType: $vfInt),
		'type without native' => static fn () => new $erClass($erNoExtensions, $erDefaultNarrowingHelper, $erScope, $erScope, $erN['variable a'], false, false, [], [], null, static fn () => new \PHPStan\Analyser\SpecifiedTypes(), type: $vfInt),
		'both resolved' => static fn () => new $erClass($erNoExtensions, $erDefaultNarrowingHelper, $erScope, $erScope, $erN['variable a'], false, false, [], [], null, static fn () => new \PHPStan\Analyser\SpecifiedTypes(), resolvedType: $vfInt, resolvedNativeType: $vfString),
		'not callable' => static fn () => new $erClass($erNoExtensions, $erDefaultNarrowingHelper, $erScope, $erScope, $erN['variable a'], false, false, [], [], 'no-such-function', static fn () => new \PHPStan\Analyser\SpecifiedTypes()),
	] as $label => $construct) {
		try {
			$result = $construct();
			$r['invariant ' . $label] = ['ok', $result->getType()->describe(\PHPStan\Type\VerbosityLevel::precise()), $result->getNativeType()->describe(\PHPStan\Type\VerbosityLevel::precise()), $result->canResolveOwnType()];
		} catch (\Throwable $e) {
			// a userland TypeError appends ", called in <file> on line <n>"
			$r['invariant ' . $label] = [get_class($e), preg_replace('~, called in .*$~', '', str_replace($erClass, 'ExpressionResult', $e->getMessage()))];
		}
	}
	// the memoized type callback is released once both flavours are resolved
	$releaseCalls = 0;
	$released = new $erClass($erNoExtensions, $erDefaultNarrowingHelper, $erScope, $erScope, $erN['func call'], false, false, [], [], static function (bool $native) use (&$releaseCalls): \PHPStan\Type\Type {
		$releaseCalls++;
		return $native ? new \PHPStanTurbo\VoidType() : new \PHPStanTurbo\UnionType([new \PHPStanTurbo\VoidType(), new \PHPStanTurbo\IntegerType()]);
	}, static fn () => new \PHPStan\Analyser\SpecifiedTypes());
	$r['release'] = [$released->getType()->describe(\PHPStan\Type\VerbosityLevel::precise()), $released->getKeepVoidType(false)->describe(\PHPStan\Type\VerbosityLevel::precise()), $released->getNativeType()->describe(\PHPStan\Type\VerbosityLevel::precise()), $released->getKeepVoidType(true)->describe(\PHPStan\Type\VerbosityLevel::precise()), $releaseCalls, $erDescribeResult($released)];
	// the override results derive the branch scopes lazily
	$override = new $erClass($erNoExtensions, $erDefaultNarrowingHelper, $erScope, $erScope, $erN['variable a'], false, false, [], [], null, static fn () => new \PHPStan\Analyser\SpecifiedTypes(), type: $vfInt, nativeType: $vfInt);
	$overridden = new $erClass($erNoExtensions, $erDefaultNarrowingHelper, $erOtherScope, $erOtherScope, $erN['func call'], false, false, [], [], null, static fn () => new \PHPStan\Analyser\SpecifiedTypes(), truthyScopeOverrideResult: $override, falseyScopeOverrideResult: $override, type: $vfInt, nativeType: $vfInt, createTypesCallback: static fn (\PHPStan\Type\Type $type, \PHPStan\Analyser\TypeSpecifierContext $context, bool $native) => new \PHPStan\Analyser\SpecifiedTypes([spl_object_id($type) => $native]));
	$r['override'] = [$overridden->getTruthyScope() === $override->getTruthyScope(), $overridden->getFalseyScope() === $override->getFalseyScope(), $erDescribeResult($overridden->getCreatedTypes($vfInt, \PHPStan\Analyser\TypeSpecifierContext::createTruthy(), true)), $erDescribeResult($overridden->getCreatedTypesForScope($erScope, $vfString, \PHPStan\Analyser\TypeSpecifierContext::createTruthy())), $erDescribeResult($overridden->finalize($erScope, false, false, [], [], null))];
	$erResults[$side] = $r;
}
foreach ($erResults['php'] as $label => $described) {
	check($described === ($erResults['native'][$label] ?? null), "ExpressionResult parity ($label): " . json_encode($described) . ' vs ' . json_encode($erResults['native'][$label] ?? null));
}
check($erResults['php']['extension calls'] > 0 && $erResults['php']['release'][4] === 2, 'ExpressionResult: the fixture exercises the extensions and the callback release');

// ---- PhpClassReflectionExtension ----
// Both sides built from the container's own collaborators with named
// arguments; every public method compared over a fixture of inherited,
// trait, magic, promoted, hooked, attributed, enum, interface and
// signature-mapped members, plus the member-cache memo and eviction.
$covered[\PHPStan\Reflection\Php\PhpClassReflectionExtension::class] = true;
// the fixture declares property hooks and is loaded at run time: PHP 8.4+
if (PHP_VERSION_ID >= 80400) {
	require __DIR__ . '/php-class-reflection-family.php';
}

// ---- TypeSpecifierContext ----
// The singletons and their queries, negate() over every reachable value
// (and its identity with the factories), the constants, the null context's
// negate() exception, the private constructor, an instance that never ran
// its constructor, and the registry the factories fill.
$tscObserve = static function (string $class) use ($turboNorm): array {
	$norm = static fn (string $message): string => str_replace($class, 'TypeSpecifierContext', $message);
	$valueProperty = new \ReflectionProperty($class, 'value');
	$describe = static fn (object $context): array => [$valueProperty->getValue($context), $context->true(), $context->truthy(), $context->false(), $context->falsey(), $context->null()];
	$o = [];
	$factories = ['createTrue', 'createTruthy', 'createFalse', 'createFalsey', 'createNull'];
	$singletons = [];
	foreach ($factories as $factory) {
		$singletons[$factory] = $class::$factory();
		$o[$factory] = [$turboNorm(get_class($singletons[$factory])), $singletons[$factory] === $class::$factory(), $describe($singletons[$factory])];
	}
	$labelOf = static function (object $context) use ($singletons): string {
		$label = array_search($context, $singletons, true);
		return $label === false ? 'other' : $label;
	};
	// every context negate() reaches from the factories, breadth first
	$queue = array_values(array_filter($singletons, static fn (object $context): bool => !$context->null()));
	$seen = [];
	while ($queue !== []) {
		$context = array_shift($queue);
		if (isset($seen[spl_object_id($context)])) {
			continue;
		}
		$seen[spl_object_id($context)] = true;
		$negated = $context->negate();
		$o['negate ' . json_encode($describe($context))] = [$describe($negated), $labelOf($negated), $negated === $context->negate(), $negated->negate() === $context];
		$queue[] = $negated;
	}
	try {
		$class::createNull()->negate();
		$o['null negate'] = 'none';
	} catch (\Throwable $e) {
		$o['null negate'] = [$turboNorm(get_class($e)), $e->getMessage()];
	}
	$o['constants'] = (new \ReflectionClass($class))->getConstants();
	try {
		new $class(1);
		$o['private constructor'] = 'none';
	} catch (\Throwable $e) {
		$o['private constructor'] = [get_class($e), $norm($e->getMessage())];
	}
	$bare = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
	foreach (['true', 'truthy', 'false', 'falsey', 'null', 'negate'] as $query) {
		try {
			$bare->$query();
			$o['uninitialized ' . $query] = 'none';
		} catch (\Throwable $e) {
			$o['uninitialized ' . $query] = [get_class($e), $norm($e->getMessage())];
		}
	}
	$registry = (new \ReflectionProperty($class, 'registry'))->getValue();
	// the fill order depends on which contexts earlier sections asked for first
	ksort($registry);
	$o['registry'] = array_map(static fn (object $context): array => $describe($context), $registry);

	return $o;
};
$tscPhp = $tscObserve(\PHPStan\Analyser\TypeSpecifierContext::class);
$tscNative = $tscObserve(\PHPStanTurbo\TypeSpecifierContext::class);
foreach ($tscPhp as $label => $expected) {
	check($expected === ($tscNative[$label] ?? null), "TypeSpecifierContext parity ($label): " . json_encode($expected) . ' vs ' . json_encode($tscNative[$label] ?? null));
}
check(array_keys($tscPhp) === array_keys($tscNative), 'TypeSpecifierContext: the same observations on both sides');
check(count(array_filter(array_keys($tscPhp), static fn (string $label): bool => str_starts_with($label, 'negate '))) === 6, 'TypeSpecifierContext: negate() reached every context');
$covered[\PHPStan\Analyser\TypeSpecifierContext::class] = true;

// ---- SpecifiedTypes ----
// Each side builds its narrowings from its own Type classes (the merges run
// the side's TypeCombinator — the native one answers native types only) over
// shared expression nodes and opaque holder/recipe/augment values, and every
// public method's result is described structurally: entries by key, types
// by precise description, nodes and opaque values by fixture label. The
// merges cover same-kind folds, the alternative-form entries, their
// conjunction with dedupe, the vacuous and impossible terms, the widening
// past ALTERNATIVE_TERMS_LIMIT, the root-expression merge and the overwrite
// flag; emptySpecifyCallback() is compared by identity and through a native
// ExpressionResult invoking it.
$stA = new \PhpParser\Node\Expr\Variable('a');
$stB = new \PhpParser\Node\Expr\Variable('b');
$stDim = new \PhpParser\Node\Expr\ArrayDimFetch($stA, new \PhpParser\Node\Expr\Variable('b'));
$stHolderX = new \stdClass();
$stHolderY = new \stdClass();
$stRecipe1 = new \stdClass();
$stRecipe2 = new \stdClass();
$stAugment1 = new class implements \PHPStan\Analyser\DeferredSpecifiedTypesAugment {

	public function evaluate(\PHPStan\Analyser\MutatingScope $scope): ?\PHPStan\Analyser\SpecifiedTypes
	{
		return null;
	}

};
$stAugment2 = clone $stAugment1;
$stLabels = ['a' => $stA, 'b' => $stB, 'dim' => $stDim, 'holderX' => $stHolderX, 'holderY' => $stHolderY, 'recipe1' => $stRecipe1, 'recipe2' => $stRecipe2, 'augment1' => $stAugment1, 'augment2' => $stAugment2];
$stSides = [
	'php' => ['PHPStan\\Analyser\\SpecifiedTypes', 'PHPStan\\Type\\', 'PHPStan\\Type\\Constant\\', \PHPStan\Type\VerbosityLevel::class, \PHPStan\Analyser\ExpressionResult::class, \PHPStan\Analyser\TypeSpecifierContext::class],
	'native' => ['PHPStanTurbo\\SpecifiedTypes', 'PHPStanTurbo\\', 'PHPStanTurbo\\', \PHPStanTurbo\VerbosityLevel::class, \PHPStanTurbo\ExpressionResult::class, \PHPStanTurbo\TypeSpecifierContext::class],
];
$stObservations = [];
foreach ($stSides as $side => [$stClass, $stNs, $stConstNs, $stLevel, $stResultClass, $stContextClass]) {
	$precise = $stLevel::precise();
	$label = static function ($value) use ($stLabels, $turboNorm, $precise, &$label) {
		if ($value === null || is_scalar($value)) {
			return $value;
		}
		if (is_array($value)) {
			return array_map($label, $value);
		}
		if ($value instanceof \PHPStan\Type\Type) {
			return 'type:' . $value->describe($precise);
		}
		$found = array_search($value, $stLabels, true);
		return $found !== false ? $found : $turboNorm(get_class($value));
	};
	$describe = static function (object $specified) use ($label, $turboNorm): array {
		return [
			'class' => $turboNorm(get_class($specified)),
			'sure' => $label($specified->getSureTypes()),
			'sureNot' => $label($specified->getSureNotTypes()),
			'alternative' => $label($specified->getAlternativeTypes()),
			'overwrite' => $specified->shouldOverwrite(),
			'root' => $label($specified->getRootExpr()),
			'holders' => $label($specified->getNewConditionalExpressionHolders()),
			'recipes' => $label($specified->getConditionalExpressionHolderRecipes()),
			'augments' => $label($specified->getDeferredAugments()),
		];
	};
	$int = new ($stNs . 'IntegerType')();
	$string = new ($stNs . 'StringType')();
	$null = new ($stNs . 'NullType')();
	$c = static fn (string $value): object => new ($stConstNs . 'ConstantStringType')($value);
	$i = static fn (int $value): object => new ($stConstNs . 'ConstantIntegerType')($value);
	$union = static fn (object ...$types): object => new ($stNs . 'UnionType')($types);
	$withAlternatives = static function (object $specified, array $alternatives) use ($stClass): object {
		$clone = clone $specified;
		(new \ReflectionProperty($stClass, 'alternativeTypes'))->setValue($clone, $alternatives);
		return $clone;
	};
	$o = [];
	$observe = static function (string $key, callable $producer) use (&$o, $describe, $label): void {
		try {
			$value = $producer();
			$o[$key] = is_object($value) && !$value instanceof \Closure ? $describe($value) : $label($value);
		} catch (\Throwable $e) {
			$o[$key] = ['throws', get_class($e)];
		}
	};

	$empty = new $stClass();
	$sureA = new $stClass(['$a' => [$stA, $int]]);
	$sureAString = new $stClass(['$a' => [$stA, $string], '$b' => [$stB, $null]]);
	$sureNotA = new $stClass([], ['$a' => [$stA, $c('x')]]);
	$sureNotAOther = new $stClass([], ['$a' => [$stA, $c('y')], '123' => [$stDim, $int]]);
	$sureAndNotA = new $stClass(['$a' => [$stA, $union($c('x'), $c('y'), $int)]], ['$a' => [$stA, $c('y')]]);
	$numeric = new $stClass(['123' => [$stDim, $string], '$b' => [$stB, $int]], ['123' => [$stDim, $c('')]]);
	$observe('empty', static fn () => $empty);
	$observe('construct sure', static fn () => $sureA);
	$observe('construct numeric key', static fn () => $numeric);
	$observe('construct both', static fn () => $sureAndNotA);

	// the with*()/set*() copies leave the receiver untouched
	$overwritten = $sureA->setAlwaysOverwriteTypes();
	$observe('setAlwaysOverwriteTypes', static fn () => $overwritten);
	$observe('setAlwaysOverwriteTypes receiver', static fn () => $sureA);
	$o['setAlwaysOverwriteTypes copies'] = $overwritten !== $sureA;
	$rooted = $sureA->setRootExpr($stA);
	$observe('setRootExpr', static fn () => $rooted);
	$observe('setRootExpr null', static fn () => $rooted->setRootExpr(null));
	$observe('setRootExpr receiver', static fn () => $sureA);
	$holders = $sureA->setNewConditionalExpressionHolders(['$a' => ['k1' => $stHolderX], '$b' => [$stHolderY]]);
	$observe('setNewConditionalExpressionHolders', static fn () => $holders);
	$recipes = $holders->setConditionalExpressionHolderRecipes([$stRecipe1]);
	$observe('setConditionalExpressionHolderRecipes', static fn () => $recipes);
	$augmented = $recipes->withDeferredAugment($stAugment1)->withDeferredAugment($stAugment2);
	$observe('withDeferredAugment', static fn () => $augmented);
	$observe('withDeferredAugment string-keyed', static function () use ($withAlternatives, $stClass, $stAugment1, $stAugment2, $sureA) {
		$clone = clone $sureA;
		(new \ReflectionProperty($stClass, 'deferredAugments'))->setValue($clone, ['x' => $stAugment1, 5 => $stAugment2]);
		return $clone->withDeferredAugment($stAugment1);
	});
	$observe('withoutConditionalExpressionHolders', static fn () => $augmented->withoutConditionalExpressionHolders());
	$observe('withoutConditionalExpressionHolders receiver', static fn () => $augmented);
	$observe('removeExpr', static fn () => $sureAndNotA->removeExpr('$a'));
	$observe('removeExpr numeric', static fn () => $numeric->removeExpr('123'));
	$observe('removeExpr missing', static fn () => $numeric->removeExpr('$zzz'));
	$observe('removeExpr alternative', static fn () => $withAlternatives($sureA, ['$a' => [$stA, [[$int, null]]], '$b' => [$stB, [[null, $string]]]])->removeExpr('$b'));
	$observe('removeExpr receiver', static fn () => $numeric);

	// unionWith(): the both-hold merge
	$observe('unionWith sure', static fn () => $sureA->unionWith($sureAString));
	$observe('unionWith sure reversed', static fn () => $sureAString->unionWith($sureA));
	$observe('unionWith sureNot', static fn () => $sureNotA->unionWith($sureNotAOther));
	$observe('unionWith empty', static fn () => $empty->unionWith($numeric));
	$observe('unionWith mixed kinds', static fn () => $sureAndNotA->unionWith($numeric)->unionWith($sureNotAOther));
	$observe('unionWith overwrite', static fn () => $sureA->unionWith($sureNotA->setAlwaysOverwriteTypes()));
	$observe('unionWith root same', static fn () => $sureA->setRootExpr($stA)->unionWith($sureNotA->setRootExpr($stA)));
	$observe('unionWith root one side', static fn () => $sureA->unionWith($sureNotA->setRootExpr($stB)));
	$observe('unionWith root different', static fn () => $sureA->setRootExpr($stA)->unionWith($sureNotA->setRootExpr($stB)));
	$observe('unionWith holders', static fn () => $augmented->unionWith(
		$sureNotA->setNewConditionalExpressionHolders(['$a' => ['k1' => $stHolderY, 'k2' => $stHolderX, 7 => $stHolderX], '$c' => [$stHolderX]])
			->setConditionalExpressionHolderRecipes([$stRecipe2, $stRecipe1])
			->withDeferredAugment($stAugment1),
	));
	$altA = $withAlternatives($empty, ['$a' => [$stA, [[$int, null], [null, $c('x')]]]]);
	$altAOther = $withAlternatives($empty, ['$a' => [$stA, [[$union($int, $string), null], [null, $c('y')], [$string, $c('z')]]], '$b' => [$stB, [[$null, null]]]]);
	$observe('unionWith alternatives one side', static fn () => $empty->unionWith($altAOther));
	$observe('unionWith alternatives conjoined', static fn () => $altA->unionWith($altAOther));
	$observe('unionWith alternatives impossible', static fn () => $withAlternatives($empty, ['$a' => [$stA, [[$int, null]]]])->unionWith($withAlternatives($empty, ['$a' => [$stA, [[$string, null], [$c('x'), $string]]]])));
	$observe('unionWith alternatives dedupe', static fn () => $withAlternatives($empty, ['$a' => [$stA, [[null, $c('x')], [null, $c('x')], [$int, null]]]])->unionWith($withAlternatives($empty, ['$a' => [$stA, [[null, null], [$int, null]]]])));
	// past ALTERNATIVE_TERMS_LIMIT distinct terms: atomic constants keep the
	// widening's own union/intersect cheap (intersecting unions distributes)
	$names = array_map(static fn (int $n): string => 'n' . $n, range(1, 34));
	$observe('unionWith alternatives widened subtracts', static fn () => $withAlternatives($empty, ['$a' => [$stA, array_map(static fn (string $name): array => [null, $c($name)], $names)]])
		->unionWith($withAlternatives($empty, ['$a' => [$stA, [[null, null]]]])));
	$observe('unionWith alternatives widened sures', static fn () => $withAlternatives($empty, ['$a' => [$stA, array_map(static fn (string $name): array => [$c($name), null], $names)]])
		->unionWith($withAlternatives($empty, ['$a' => [$stA, [[null, null], [$string, null]]]])));
	$observe('unionWith alternatives widened mixed', static fn () => $withAlternatives($empty, ['$a' => [$stA, [...array_map(static fn (string $name): array => [$c($name), null], $names), [null, $int]]]])
		->unionWith($withAlternatives($empty, ['$a' => [$stA, [[null, null]]]])));

	// intersectWith(): the either-branch merge
	$observe('intersectWith sure', static fn () => $sureA->intersectWith($sureAString));
	$observe('intersectWith sureNot', static fn () => $sureNotA->intersectWith($sureNotAOther));
	$observe('intersectWith sureNot vacuous', static fn () => (new $stClass([], ['$a' => [$stA, $int]]))->intersectWith(new $stClass([], ['$a' => [$stA, $string]])));
	$observe('intersectWith kinds differ', static fn () => $sureA->intersectWith($sureNotA));
	$observe('intersectWith one side', static fn () => $sureA->intersectWith($empty));
	$observe('intersectWith both kinds', static fn () => $sureAndNotA->intersectWith($sureAndNotA));
	$observe('intersectWith numeric', static fn () => $numeric->intersectWith($numeric->removeExpr('$b')));
	$observe('intersectWith alternatives', static fn () => $altA->intersectWith($altAOther));
	$observe('intersectWith alternative folds', static fn () => $withAlternatives($sureAndNotA, ['$a' => [$stA, [[$string, null], [null, $c('q')], [null, null]]]])->intersectWith($altAOther));
	$observe('intersectWith alternative pure', static fn () => $withAlternatives($empty, ['$a' => [$stA, [[$int, null]]]])->intersectWith($sureAString));
	$observe('intersectWith overwrite one', static fn () => $sureA->setAlwaysOverwriteTypes()->intersectWith($sureAString));
	$observe('intersectWith overwrite both', static fn () => $sureA->setAlwaysOverwriteTypes()->intersectWith($sureAString->setAlwaysOverwriteTypes()));
	$observe('intersectWith root', static fn () => $sureA->setRootExpr($stA)->intersectWith($sureAString->setRootExpr($stA)));
	$observe('intersectWith drops holders', static fn () => $augmented->intersectWith($augmented));

	// argument and state errors
	$observe('unionWith foreign', static fn () => $sureA->unionWith(new \stdClass()));
	$observe('intersectWith foreign', static fn () => $sureA->intersectWith(new \stdClass()));
	$observe('setRootExpr scalar', static fn () => $sureA->setRootExpr('$a'));
	$bare = (new \ReflectionClass($stClass))->newInstanceWithoutConstructor();
	$observe('uninitialized getSureTypes', static fn () => $bare->getSureTypes());
	$observe('uninitialized getRootExpr', static fn () => $bare->getRootExpr());

	// emptySpecifyCallback(): one process-wide closure, a fresh empty instance per call
	$callback = $stClass::emptySpecifyCallback();
	$o['emptySpecifyCallback'] = [$callback instanceof \Closure, $callback === $stClass::emptySpecifyCallback()];
	$first = $callback($stContextClass::createTruthy(), false);
	$second = $callback();
	$o['emptySpecifyCallback call'] = [$describe($first), $first !== $second, $describe($second)];
	$emptyResult = new $stResultClass($erNoExtensions, $erDefaultNarrowingHelper, $erScope, $erScope, $stA, false, false, [], [], null, $callback, type: $vfInt, nativeType: $vfInt);
	$fromResult = $emptyResult->getSpecifiedTypes($stContextClass::createTruthy());
	$o['emptySpecifyCallback via ExpressionResult'] = [$describe($fromResult), $fromResult === $emptyResult->getSpecifiedTypes($stContextClass::createTruthy()), $fromResult !== $emptyResult->getSpecifiedTypes($stContextClass::createFalsey())];

	$stObservations[$side] = $o;
}
foreach ($stObservations['php'] as $label => $expected) {
	check($expected === ($stObservations['native'][$label] ?? null), "SpecifiedTypes parity ($label): " . json_encode($expected) . ' vs ' . json_encode($stObservations['native'][$label] ?? null));
}
check(array_keys($stObservations['php']) === array_keys($stObservations['native']), 'SpecifiedTypes: the same observations on both sides');
check(
	count($stObservations['php']['unionWith alternatives widened subtracts']['alternative']['$a'][1]) === 1
	&& count($stObservations['php']['unionWith alternatives widened sures']['alternative']['$a'][1]) === 1
	&& $stObservations['php']['unionWith alternatives impossible']['alternative']['$a'][1] === [['type:*NEVER*', null]]
	&& $stObservations['php']['intersectWith sureNot vacuous']['sureNot'] === [],
	'SpecifiedTypes: the fixture reaches the widening, the impossible conjunction and the vacuous sure-not',
);
$covered[\PHPStan\Analyser\SpecifiedTypes::class] = true;

// ---- ExpressionContext / StatementContext ----
// Every derivation applied two levels deep to every factory's context on
// both sides; each context is compared getter by getter, a derivation that
// returns its receiver is recorded as such, and the private constructors
// must refuse userland instantiation.
$covered[\PHPStan\Analyser\ExpressionContext::class] = true;
$covered[\PHPStan\Analyser\StatementContext::class] = true;
$ecInt = new \PHPStanTurbo\IntegerType();
$ecString = new \PHPStanTurbo\StringType();
$ecExpr = new \PhpParser\Node\Expr\Variable('x');
$ecWrite = new \PHPStan\Node\Variable\VariableWrite('x', $ecExpr, 7, \PHPStan\Node\Variable\VariableWrite::KIND_ASSIGN);
$ecTemplateAcceptor = new \PHPStan\Reflection\TrivialParametersAcceptor();
$ecExtendedAcceptor = new \PHPStan\Reflection\ExtendedFunctionVariant(
	\PHPStan\Type\Generic\TemplateTypeMap::createEmpty(),
	null,
	[],
	false,
	$ecInt,
	$ecInt,
	$ecString,
);
$ecResults = [];
foreach (['php' => [\PHPStan\Analyser\ExpressionContext::class, \PHPStan\Analyser\StatementContext::class], 'native' => [\PHPStanTurbo\ExpressionContext::class, \PHPStanTurbo\StatementContext::class]] as $side => [$ecClass, $stcClass]) {
	$r = [];
	$describeObject = static function (?object $object) use ($turboNorm, $ecExpr, $ecWrite): ?string {
		if ($object === null) {
			return null;
		}
		if ($object === $ecExpr) {
			return 'expr';
		}
		if ($object === $ecWrite) {
			return 'write';
		}
		if ($object instanceof \PHPStan\Type\Type) {
			return $turboNorm(get_class($object)) . ':' . $object->describe(\PHPStan\Type\VerbosityLevel::precise());
		}
		return $turboNorm(get_class($object));
	};
	$describe = static fn (object $c): array => [
		$turboNorm(get_class($c)),
		$c->isDeep(),
		$c->isValueConsumed(),
		$describeObject($c->getPassedToType()),
		$describeObject($c->getNativePassedToType()),
		$c->shouldResolveTemplateArguments(),
		$c->isInThrow(),
		$c->getInAssignRightSideVariableName(),
		$describeObject($c->getInAssignRightSideExpr()),
		$describeObject($c->getInAssignRightSideType()),
		$describeObject($c->getInAssignRightSideNativeType()),
		$describeObject($c->getValueFlowTarget()),
		$c->isValueFlowDirect(),
		$c->isArrayDimFetchRoot(),
		$c->isUnsetTarget(),
	];
	$derivations = [
		'enterDeep' => static fn ($c) => $c->enterDeep(),
		'enterDeepKeepingValueFlow' => static fn ($c) => $c->enterDeepKeepingValueFlow(),
		'withoutValueFlow' => static fn ($c) => $c->withoutValueFlow(),
		'enterMatchArm' => static fn ($c) => $c->enterMatchArm(),
		'enterPassedToType' => static fn ($c) => $c->enterPassedToType($ecInt, $ecString),
		'enterPassedToType same' => static fn ($c) => $c->enterPassedToType($c->getPassedToType(), $c->getNativePassedToType()),
		'enterPassedToType null' => static fn ($c) => $c->enterPassedToType(null, null),
		'withoutTemplateArgumentResolution' => static fn ($c) => $c->withoutTemplateArgumentResolution(),
		'enterThrow' => static fn ($c) => $c->enterThrow(),
		'enterRightSideAssign' => static fn ($c) => $c->enterRightSideAssign('x', $ecExpr),
		'enterAssignRightSideCallArgs trivial' => static fn ($c) => $c->enterAssignRightSideCallArgs($ecTemplateAcceptor),
		'enterAssignRightSideCallArgs extended' => static fn ($c) => $c->enterAssignRightSideCallArgs($ecExtendedAcceptor),
		'enterValueFlow direct' => static fn ($c) => $c->enterValueFlow($ecWrite, true),
		'enterValueFlow' => static fn ($c) => $c->enterValueFlow($ecWrite, false),
		'enterArrayDimFetchRoot' => static fn ($c) => $c->enterArrayDimFetchRoot(),
		'enterUnsetTarget' => static fn ($c) => $c->enterUnsetTarget(),
	];
	foreach ([
		'topLevel' => $ecClass::createTopLevel(),
		'topLevel false' => $ecClass::createTopLevel(false),
		'topLevel named' => $ecClass::createTopLevel(resolveTemplateArguments: false),
		'deep' => $ecClass::createDeep(),
		'deep false' => $ecClass::createDeep(false),
	] as $startLabel => $start) {
		$r[$startLabel] = $describe($start);
		foreach ($derivations as $label1 => $derive1) {
			$first = $derive1($start);
			$r[$startLabel . ' > ' . $label1] = [$first === $start, $describe($first)];
			foreach ($derivations as $label2 => $derive2) {
				$second = $derive2($first);
				$r[$startLabel . ' > ' . $label1 . ' > ' . $label2] = [$second === $first, $describe($second)];
			}
		}
	}
	try {
		new $ecClass(false, null, null);
		$r['ctor'] = 'callable';
	} catch (\Error $e) {
		$r['ctor'] = get_class($e);
	}

	$stcDescribe = static fn (object $c): array => [$turboNorm(get_class($c)), $c->isTopLevel(), $c->getForeachUnrollFactor(), $c->shouldResolveTemplateArguments()];
	$stcDerivations = [
		'withoutTemplateArgumentResolution' => static fn ($c) => $c->withoutTemplateArgumentResolution(),
		'enterDeep' => static fn ($c) => $c->enterDeep(),
		'enterUnrolledForeach 3' => static fn ($c) => $c->enterUnrolledForeach(3),
		'enterUnrolledForeach 0' => static fn ($c) => $c->enterUnrolledForeach(0),
	];
	foreach ([
		'stmt topLevel' => $stcClass::createTopLevel(),
		'stmt topLevel false' => $stcClass::createTopLevel(false),
		'stmt deep' => $stcClass::createDeep(),
		'stmt deep false' => $stcClass::createDeep(resolveTemplateArguments: false),
	] as $startLabel => $start) {
		$r[$startLabel] = $stcDescribe($start);
		foreach ($stcDerivations as $label1 => $derive1) {
			$first = $derive1($start);
			foreach ($stcDerivations as $label2 => $derive2) {
				$second = $derive2($first);
				$r[$startLabel . ' > ' . $label1 . ' > ' . $label2] = [$first === $start, $second === $first, $stcDescribe($second)];
			}
		}
	}
	try {
		$stcClass::createDeep()->enterUnrolledForeach(PHP_INT_MAX)->enterUnrolledForeach(3);
		$r['stmt overflow'] = 'no exception';
	} catch (\TypeError $e) {
		// a userland TypeError appends ", called in <file> on line <n>"
		$r['stmt overflow'] = preg_replace('~, called in .*$~', '', str_replace($stcClass, 'StatementContext', $e->getMessage()));
	}
	try {
		new $stcClass(true);
		$r['stmt ctor'] = 'callable';
	} catch (\Error $e) {
		$r['stmt ctor'] = get_class($e);
	}
	$ecResults[$side] = $r;
}
foreach ($ecResults['php'] as $label => $described) {
	check($described === ($ecResults['native'][$label] ?? null), "ExpressionContext/StatementContext parity ($label): " . json_encode($described) . ' vs ' . json_encode($ecResults['native'][$label] ?? null));
}

// ---- ExprHandlerRegistry / StmtHandlerRegistry ----
// Both registries resolve the same nodes against the same container (and a
// second one); the answers and the memo each side leaves in its private
// static array must match.
$covered[\PHPStan\Analyser\ExprHandlerRegistry::class] = true;
$covered[\PHPStan\Analyser\StmtHandlerRegistry::class] = true;
$hrSecondContainer = $scContainerFactory->create(sys_get_temp_dir() . '/phpstan-turbo-smoke', [$scContainerFactory->getConfigDirectory() . '/config.level8.neon'], []);
$hrVar = new \PhpParser\Node\Expr\Variable('a');
$hrExprs = [
	'variable' => $hrVar,
	'variable variable' => new \PhpParser\Node\Expr\Variable($hrVar),
	'string' => new \PhpParser\Node\Scalar\String_('s'),
	'int' => new \PhpParser\Node\Scalar\Int_(1),
	'interpolated' => new \PhpParser\Node\Scalar\InterpolatedString([new \PhpParser\Node\InterpolatedStringPart('a'), $hrVar]),
	'func call' => new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('f')),
	'func call fcc' => new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('f'), [new \PhpParser\Node\VariadicPlaceholder()]),
	'func call two args' => new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('f'), [new \PhpParser\Node\VariadicPlaceholder(), new \PhpParser\Node\Arg($hrVar)]),
	'method call' => new \PhpParser\Node\Expr\MethodCall($hrVar, 'm'),
	'method call fcc' => new \PhpParser\Node\Expr\MethodCall($hrVar, 'm', [new \PhpParser\Node\VariadicPlaceholder()]),
	'nullsafe method call' => new \PhpParser\Node\Expr\NullsafeMethodCall($hrVar, 'm'),
	'static call' => new \PhpParser\Node\Expr\StaticCall(new \PhpParser\Node\Name('A'), 'm'),
	'new name' => new \PhpParser\Node\Expr\New_(new \PhpParser\Node\Name('A')),
	'new expr' => new \PhpParser\Node\Expr\New_($hrVar),
	'new class' => new \PhpParser\Node\Expr\New_(new \PhpParser\Node\Stmt\Class_(null)),
	'new fcc' => new \PhpParser\Node\Expr\New_(new \PhpParser\Node\Name('A'), [new \PhpParser\Node\VariadicPlaceholder()]),
	'type expr' => new \PHPStan\Node\Expr\TypeExpr($ecInt),
	'unhandled' => new class extends \PhpParser\Node\Expr {

		public function getType(): string
		{
			return 'Unhandled';
		}

		public function getSubNodeNames(): array
		{
			return [];
		}

	},
];
$hrStmts = [
	'echo' => new \PhpParser\Node\Stmt\Echo_([$hrVar]),
	'expression' => new \PhpParser\Node\Stmt\Expression($hrVar),
	'if' => new \PhpParser\Node\Stmt\If_($hrVar),
	'nop' => new \PhpParser\Node\Stmt\Nop(),
	'halt compiler' => new \PhpParser\Node\Stmt\HaltCompiler(''),
	'class' => new \PhpParser\Node\Stmt\Class_('A'),
];
$hrResults = [];
foreach (['php' => [\PHPStan\Analyser\ExprHandlerRegistry::class, \PHPStan\Analyser\StmtHandlerRegistry::class], 'native' => [\PHPStanTurbo\ExprHandlerRegistry::class, \PHPStanTurbo\StmtHandlerRegistry::class]] as $side => [$ehrClass, $shrClass]) {
	$r = [];
	// earlier sections walked code through the PHP registries: start both
	// memos empty (they are caches — dropping them changes no answer)
	(new \ReflectionProperty($ehrClass, 'exprHandlersByClass'))->setValue(null, []);
	(new \ReflectionProperty($shrClass, 'stmtHandlersByClass'))->setValue(null, []);
	foreach ([1 => $scContainer, 2 => $hrSecondContainer, 3 => $scContainer] as $round => $container) {
		foreach ($hrExprs as $label => $expr) {
			$handler = $ehrClass::resolve($expr, $container);
			$r[] = [$round, $label, $handler === null ? null : get_class($handler), $handler === $ehrClass::resolve($expr, $container)];
		}
		foreach ($hrStmts as $label => $stmt) {
			$handler = $shrClass::resolve($stmt, $container);
			$r[] = [$round, $label, $handler === null ? null : get_class($handler), $handler === $shrClass::resolve($stmt, $container)];
		}
	}
	$memo = static function (string $class, string $property): array {
		$described = [];
		foreach ((new \ReflectionProperty($class, $property))->getValue() as $containerId => $byKey) {
			foreach ($byKey as $key => $handler) {
				$described[$containerId][str_replace('PHPStanTurbo\\', 'PHPStan\\Analyser\\', (string) $key)] = $handler === false ? false : get_class($handler);
			}
		}
		return $described;
	};
	$r['expr memo'] = $memo($ehrClass, 'exprHandlersByClass');
	$r['stmt memo'] = $memo($shrClass, 'stmtHandlersByClass');
	$hrResults[$side] = $r;
}
foreach ($hrResults['php'] as $label => $described) {
	check($described === ($hrResults['native'][$label] ?? null), "ExprHandlerRegistry/StmtHandlerRegistry parity ($label): " . json_encode($described) . ' vs ' . json_encode($hrResults['native'][$label] ?? null));
}
check(count($hrResults['php']['expr memo']) === 2 && count($hrResults['php']['stmt memo']) === 2, 'handler registries: the fixture exercises two containers');

// ---- ScalarHandler / VariableHandler and their native closures ----
// walk-trace.php compares the handler ports as the engine runs them; here the
// prefixed ports run next to the twins on the same PHP collaborators (the
// direct entries' fallback paths: PHP scope, context, results and factory),
// and the closures they hand out are called every way PHP code calls one. A
// dynamic name's type is not resolved here: the native typeCallback hands the
// native TypeSpecifierContext to the PHP IdenticalNarrowingHelper, which the
// prefixed declaration cannot satisfy — walk-trace.php covers that path
// (nsrt/bug-12398.php, variable-variable-assign.php).
$covered[\PHPStan\Analyser\ExprHandler\ScalarHandler::class] = true;
$covered[\PHPStan\Analyser\ExprHandler\VariableHandler::class] = true;
$hhNodeScopeResolver = $scContainer->getByType(\PHPStan\Analyser\NodeScopeResolver::class);
$hhFactory = $scContainer->getByType(\PHPStan\Analyser\ExpressionResultFactory::class);
$hhDefault = $scContainer->getByType(\PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper::class);
$hhIdentical = $scContainer->getByType(\PHPStan\Analyser\ExprHandler\Helper\IdenticalNarrowingHelper::class);
$hhInitializer = $scContainer->getByType(\PHPStan\Reflection\InitializerExprTypeResolver::class);
$hhStmt = new \PhpParser\Node\Stmt\Nop();
$hhName = new \PhpParser\Node\Expr\Variable('n');
$hhScope = $erScope->assignVariable('n', new \PHPStanTurbo\ConstantStringType('a'), new \PHPStanTurbo\StringType(), \PHPStan\TrinaryLogic::createYes());
$hhExprs = [
	'variable' => new \PhpParser\Node\Expr\Variable('a'),
	'variable maybe' => new \PhpParser\Node\Expr\Variable('m'),
	'variable undefined' => new \PhpParser\Node\Expr\Variable('nope'),
	'superglobal' => new \PhpParser\Node\Expr\Variable('_GET'),
	'this' => new \PhpParser\Node\Expr\Variable('this'),
	'dynamic name' => new \PhpParser\Node\Expr\Variable($hhName),
	'string' => new \PhpParser\Node\Scalar\String_('s'),
	'int' => new \PhpParser\Node\Scalar\Int_(5),
	'float' => new \PhpParser\Node\Scalar\Float_(1.5),
];
$hhContexts = [
	'top' => \PHPStan\Analyser\ExpressionContext::createTopLevel(),
	'deep' => \PHPStan\Analyser\ExpressionContext::createDeep(),
	'unset' => \PHPStan\Analyser\ExpressionContext::createDeep()->enterUnsetTarget(),
	'dim root' => \PHPStan\Analyser\ExpressionContext::createDeep()->enterArrayDimFetchRoot(),
	'value flow' => \PHPStan\Analyser\ExpressionContext::createDeep()->enterValueFlow(new \PHPStan\Node\Variable\VariableWrite('t', $hhName, 42, \PHPStan\Node\Variable\VariableWrite::KIND_ASSIGN), true),
];
// The native VariableHandler creates the native IssetabilityDescriptor, which
// the container's PHP factory type-hints away under the prefix: the native
// side's factory hands it a PHP twin carrying the same state.
$hhNativeFactory = new class ($hhFactory) implements \PHPStan\Analyser\ExpressionResultFactory {

	public function __construct(private \PHPStan\Analyser\ExpressionResultFactory $factory)
	{
	}

	public function create(
		\PHPStan\Analyser\MutatingScope $scope,
		\PHPStan\Analyser\MutatingScope $beforeScope,
		\PhpParser\Node\Expr $expr,
		bool $hasYield,
		bool $isAlwaysTerminating,
		array $throwPoints,
		array $impurePoints,
		?callable $typeCallback,
		callable $specifyTypesCallback,
		bool $containsNullsafe = false,
		?object $issetabilityDescriptor = null,
		?\PHPStan\Analyser\ExpressionResult $truthyScopeOverrideResult = null,
		?\PHPStan\Analyser\ExpressionResult $falseyScopeOverrideResult = null,
		?callable $createTypesCallback = null,
		?\PHPStan\Type\Type $type = null,
		?\PHPStan\Type\Type $nativeType = null,
		?\PHPStan\Analyser\ArgsResult $argsResult = null,
		?\PHPStan\Analyser\VariableFlow $variableFlow = null,
	): \PHPStan\Analyser\ExpressionResult
	{
		if ($issetabilityDescriptor instanceof \PHPStanTurbo\IssetabilityDescriptor) {
			$twin = (new \ReflectionClass(\PHPStan\Analyser\IssetabilityDescriptor::class))->newInstanceWithoutConstructor();
			foreach ((new \ReflectionClass($issetabilityDescriptor))->getProperties() as $property) {
				(new \ReflectionProperty(\PHPStan\Analyser\IssetabilityDescriptor::class, $property->getName()))->setValue($twin, $property->getValue($issetabilityDescriptor));
			}
			$issetabilityDescriptor = $twin;
		}

		return $this->factory->create($scope, $beforeScope, $expr, $hasYield, $isAlwaysTerminating, $throwPoints, $impurePoints, $typeCallback, $specifyTypesCallback, $containsNullsafe, $issetabilityDescriptor, $truthyScopeOverrideResult, $falseyScopeOverrideResult, $createTypesCallback, $type, $nativeType, $argsResult, $variableFlow);
	}

};
$hhResults = [];
foreach (['php' => [\PHPStan\Analyser\ExprHandler\ScalarHandler::class, \PHPStan\Analyser\ExprHandler\VariableHandler::class, $hhFactory], 'native' => [\PHPStanTurbo\ScalarHandler::class, \PHPStanTurbo\VariableHandler::class, $hhNativeFactory]] as $side => [$scalarClass, $variableClass, $variableFactory]) {
	$r = [];
	$scalar = new $scalarClass($hhInitializer, $hhFactory);
	$variable = new $variableClass($variableFactory, $hhDefault, $hhIdentical, $hhInitializer);
	$describeType = static fn ($type) => $type instanceof \PHPStan\Type\Type ? $type->describe(\PHPStan\Type\VerbosityLevel::precise()) : get_debug_type($type);
	$describe = static function (\PHPStan\Analyser\ExpressionResult $result, bool $resolveTypes) use ($describeType, $vfDescribe, $hhScope, $turboNorm): array {
		$callbacks = [];
		foreach (['typeCallback', 'specifyTypesCallback', 'createTypesCallback'] as $property) {
			$callbacks[$property] = (new \ReflectionProperty($result, $property))->getValue($result);
		}
		$typeCallback = $callbacks['typeCallback'];
		$d = [
			'type' => $resolveTypes ? $describeType($result->getType()) : null,
			'native' => $resolveTypes ? $describeType($result->getNativeType()) : null,
			'flow' => $vfDescribe($result->getVariableFlow()),
			'impure' => array_map(static fn ($point) => [$point->getIdentifier(), $point->getDescription(), $point->isCertain()], $result->getImpurePoints()),
			'throw' => count($result->getThrowPoints()),
			'yield' => $result->hasYield(),
			'terminating' => $result->isAlwaysTerminating(),
			'scope' => $result->getScope() === $hhScope,
			'issetability' => (static function (?object $descriptor): mixed {
				if ($descriptor === null) {
					return null;
				}
				$state = [get_class($descriptor)];
				foreach (['kind', 'variableName'] as $property) {
					$state[$property] = (new \ReflectionProperty($descriptor, $property))->getValue($descriptor);
				}
				return $state;
			})((new \ReflectionProperty($result, 'issetabilityDescriptor'))->getValue($result)),
			'callable' => is_callable($typeCallback),
			'createTypesCallback' => $callbacks['createTypesCallback'],
		];
		if ($typeCallback !== null && $resolveTypes) {
			$d['call'] = $describeType($typeCallback(false));
			$d['call native'] = $describeType($typeCallback(true));
			$d['call_user_func'] = $describeType(call_user_func($typeCallback, false));
			$d['call_user_func_array'] = $describeType(call_user_func_array($typeCallback, [true]));
			$d['fromCallable'] = $describeType(\Closure::fromCallable($typeCallback)(false));
			$d['first-class callable'] = $describeType($typeCallback(...)(true));
			$d['callable parameter'] = $describeType((static fn (callable $callback) => $callback(false, 'surplus'))($typeCallback));
			$d['clone'] = $describeType((clone $typeCallback)(false));
			$d['equality'] = [$typeCallback == $typeCallback, $typeCallback == clone $typeCallback, $typeCallback === $typeCallback];
		}
		$specified = $callbacks['specifyTypesCallback'](\PHPStan\Analyser\TypeSpecifierContext::createTruthy(), false);
		$d['specify'] = [$turboNorm(get_class($specified)), array_map($describeType, array_map(static fn ($pair) => $pair[1], $specified->getSureTypes()))];
		return $d;
	};
	foreach ($hhExprs as $exprLabel => $expr) {
		foreach ($hhContexts as $contextLabel => $context) {
			$handler = $expr instanceof \PhpParser\Node\Scalar ? $scalar : $variable;
			$calls = [
				'' => static fn ($storage) => $handler->processExpr($hhNodeScopeResolver, $hhStmt, $expr, $hhScope, $storage, new \PHPStan\Analyser\NoopNodeCallback(), $context),
			];
			if ($handler === $variable) {
				// no name result for a dynamic name: the twin's typeCallback throws
				$calls[' / composed'] = static fn ($storage) => $variable->composeResult($hhNodeScopeResolver, $expr, null, $storage, $hhScope, $context);
				$calls[' / composed without context'] = static fn ($storage) => $variable->composeResult($hhNodeScopeResolver, $expr, null, $storage, $hhScope);
			}
			foreach ($calls as $callLabel => $call) {
				$storage = new \PHPStan\Analyser\ExpressionResultStorage();
				$hhScope->pushExpressionResultStorage($storage);
				try {
					$r[$exprLabel . ' / ' . $contextLabel . $callLabel] = $describe($call($storage), $exprLabel !== 'dynamic name');
				} catch (\Throwable $e) {
					$r[$exprLabel . ' / ' . $contextLabel . $callLabel] = [get_class($e), $e->getMessage()];
				} finally {
					$hhScope->popExpressionResultStorage();
				}
			}
		}
	}
	$hhResults[$side] = $r;
}
foreach ($hhResults['php'] as $label => $described) {
	check($described === ($hhResults['native'][$label] ?? null), "ScalarHandler/VariableHandler parity ($label): " . json_encode($described) . ' vs ' . json_encode($hhResults['native'][$label] ?? null));
}
check(($hhResults['php']['variable / top']['type'] ?? null) === 'int' && ($hhResults['php']['dynamic name / top']['flow']['kind'] ?? null) === 'sequence', 'ScalarHandler/VariableHandler: the fixture resolves a variable and walks a dynamic name: ' . json_encode([$hhResults['php']['variable / top'] ?? null, $hhResults['php']['dynamic name / top'] ?? null]));
// a native closure is not serializable and has no body when userland creates one
try {
	serialize((new \ReflectionProperty(\PHPStan\Analyser\ExpressionResult::class, 'typeCallback'))->getValue((new \PHPStanTurbo\ScalarHandler($hhInitializer, $hhFactory))->processExpr($hhNodeScopeResolver, $hhStmt, $hhExprs['int'], $hhScope, new \PHPStan\Analyser\ExpressionResultStorage(), new \PHPStan\Analyser\NoopNodeCallback(), $hhContexts['top'])));
	check(false, 'NativeClosure: serialize() must refuse');
} catch (\Exception $e) {
	check(str_contains($e->getMessage(), 'is not allowed'), 'NativeClosure: serialize() message: ' . $e->getMessage());
}
try {
	(new \PHPStanTurbo\NativeClosure())();
	check(false, 'NativeClosure: a userland instance must not be callable');
} catch (\Error $e) {
	check($e->getMessage() === 'phpstan_turbo: native closure without a body', 'NativeClosure: userland instance message: ' . $e->getMessage());
}

// ---- the analyser value classes ----
// analyser-values.php builds the value objects on both sides from the same
// scopes, nodes and types and compares every method's answer and the state.
$covered[\PHPStan\Analyser\ImpurePoint::class] = true;
$covered[\PHPStan\Analyser\ThrowPoint::class] = true;
$covered[\PHPStan\Analyser\InternalThrowPoint::class] = true;
$covered[\PHPStan\Analyser\ArgsResult::class] = true;
$covered[\PHPStan\Analyser\IssetabilityDescriptor::class] = true;
$covered[\PHPStan\Analyser\StatementExitPoint::class] = true;
$covered[\PHPStan\Analyser\StatementResult::class] = true;
$covered[\PHPStan\Analyser\EndStatementResult::class] = true;
$covered[\PHPStan\Analyser\InternalStatementExitPoint::class] = true;
$covered[\PHPStan\Analyser\InternalStatementResult::class] = true;
$covered[\PHPStan\Analyser\InternalEndStatementResult::class] = true;
$covered[\PHPStan\Analyser\Generics\TemplateArgumentFrame::class] = true;
$covered[\PHPStan\Analyser\AssignTargetWalkMode::class] = true;
$covered[\PHPStan\Analyser\PreparedAssignTarget::class] = true;
$covered[\PHPStan\Analyser\RecordingNodeCallback::class] = true;
require __DIR__ . '/analyser-values.php';

// ---- differential coverage completeness ----
// Every shadowed class must be exercised by one of the tests/ scripts; the
// classes not covered above have their own dedicated script.
$coveredElsewhere = [
	\PHPStan\Cache\ArenaCache::class => 'arena-smoke.php',
	\PHPStan\Parser\ParserRunner::class => 'parser-corpus.php',
	\PHPStan\Reflection\BetterReflection\SourceLocator\PhpFileCleaner::class => 'php-file-cleaner-corpus.php',
	\PHPStan\Reflection\BetterReflection\SourceLocator\SymbolFinderInFiles::class => 'symbol-finder-corpus.php',
	\PHPStan\Analyser\ExprHandler\Helper\EarlyTerminatingCallHelper::class => 'walk-trace.php',
	\PHPStan\Analyser\ExprHandler\Helper\MethodCallReturnTypeHelper::class => 'walk-trace.php',
	\PHPStan\Analyser\ExprHandler\Helper\MethodThrowPointHelper::class => 'walk-trace.php',
	\PHPStan\Analyser\ConditionalExpressionHolderRecipe::class => 'walk-trace.php',
	\PHPStan\Analyser\DisjunctionBranchUnionAugment::class => 'walk-trace.php',
	\PHPStan\Analyser\DisjunctionHolderProjectionAugment::class => 'walk-trace.php',
	\PHPStan\Analyser\ExprHandler\Helper\ConditionalExpressionHolderHelper::class => 'walk-trace.php',
	\PHPStan\Analyser\ExprHandler\Helper\BooleanNarrowingHelper::class => 'walk-trace.php',
	\PHPStan\Analyser\TypeSpecifier::class => 'walk-trace.php',
	\PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper::class => 'walk-trace.php',
	\PHPStan\Analyser\ExprHandler\Helper\IdenticalNarrowingHelper::class => 'walk-trace.php',
	\PHPStan\Analyser\NodeScopeResolver::class => 'walk-trace.php',
	\PHPStan\Analyser\StatementsHandler::class => 'walk-trace.php',
	\PHPStan\Analyser\StatementListWalkState::class => 'walk-trace.php',
	\PHPStan\Analyser\ExprHandler\Helper\NonNullabilityHelper::class => 'walk-trace.php',
	\PHPStan\Analyser\ExprHandler\MethodCallHandler::class => 'walk-trace.php',
	\PHPStan\Analyser\ExprHandler\Helper\DynamicReturnTypeStoragePrimer::class => 'walk-trace.php',
	\PHPStan\Analyser\ExprHandler\AssignHandler::class => 'walk-trace.php',
	\PHPStan\Analyser\ExprHandler\AssignOpHandler::class => 'walk-trace.php',
	\PHPStan\Analyser\StmtHandler\ExpressionHandler::class => 'walk-trace.php',
	\PHPStan\Analyser\StmtHandler\ReturnHandler::class => 'walk-trace.php',
	\PHPStan\Analyser\StmtHandler\EchoHandler::class => 'walk-trace.php',
	\PHPStan\Analyser\StmtHandler\BlockHandler::class => 'walk-trace.php',
	\PHPStan\Analyser\StmtHandler\NopHandler::class => 'walk-trace.php',
	\PHPStan\Analyser\StmtHandler\ClassMethodHandler::class => 'walk-trace.php',
	\PHPStan\Analyser\StmtHandler\FunctionHandler::class => 'walk-trace.php',
	\PHPStan\Analyser\StmtHandler\ClassLikeHandler::class => 'walk-trace.php',
	\PHPStan\Analyser\StmtHandler\IfHandler::class => 'walk-trace.php',
	\PHPStan\Analyser\ExprHandler\StaticCallHandler::class => 'walk-trace.php',
	\PHPStan\Analyser\ExprHandler\NewHandler::class => 'walk-trace.php',
];
foreach (array_keys($shadowedClasses) as $shadowedClass) {
	check(
		isset($covered[$shadowedClass]) || isset($coveredElsewhere[$shadowedClass]),
		"shadowed class $shadowedClass has no differential coverage — register it in \$covered next to its checks here, or in \$coveredElsewhere",
	);
}

echo $failures === 0 ? "ALL OK\n" : "$failures FAILURES\n";
exit($failures === 0 ? 0 : 1);

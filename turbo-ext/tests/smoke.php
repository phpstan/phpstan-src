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
// The native class memoizes on a structural key of the arguments and calls back into
// TypeCombinator::doUnion() and friends on a miss. TypeCombinator itself is unshadowed
// here (the enabler never ran), so it is the unmemoized reference implementation.
$cacheLevel = \PHPStan\Type\VerbosityLevel::cache();
$describe = static fn (\PHPStan\Type\Type $t): string => $t->describe($cacheLevel);

$intT = new \PHPStan\Type\IntegerType();
$stringT = new \PHPStan\Type\StringType();
$nullT = new \PHPStan\Type\NullType();
$oneT = new \PHPStan\Type\Constant\ConstantIntegerType(1);
$tenT = new \PHPStan\Type\Constant\ConstantIntegerType(10);
$arrayT = new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType());
$nonEmpty = new \PHPStan\Type\Accessory\NonEmptyArrayType();

$unions = [
	[$intT, $stringT],
	[$oneT, $tenT, $nullT],
	[new \PHPStan\Type\UnionType([$oneT, $tenT]), $nullT],
];
foreach ($unions as $i => $args) {
	$native = \PHPStanTurbo\TypeCombinatorCache::union(...$args);
	$php = \PHPStan\Type\TypeCombinator::union(...$args);
	check($describe($native) === $describe($php), "TCC union #$i: {$describe($native)} vs {$describe($php)}");
}

$native = \PHPStanTurbo\TypeCombinatorCache::intersect($arrayT, $nonEmpty);
$php = \PHPStan\Type\TypeCombinator::intersect($arrayT, $nonEmpty);
check($describe($native) === $describe($php), 'TCC intersect: ' . $describe($native) . ' vs ' . $describe($php));

$nullable = \PHPStan\Type\TypeCombinator::union($intT, $nullT);
$native = \PHPStanTurbo\TypeCombinatorCache::remove($nullable, $nullT);
$php = \PHPStan\Type\TypeCombinator::remove($nullable, $nullT);
check($describe($native) === $describe($php), 'TCC remove: ' . $describe($native) . ' vs ' . $describe($php));

// a repeated call must hit the memo and hand back the very same instance
$first = \PHPStanTurbo\TypeCombinatorCache::union($intT, $stringT);
$second = \PHPStanTurbo\TypeCombinatorCache::union(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType());
check($first === $second, 'TCC memo hit on structurally equal arguments');

// explicit and implicit mixed are different values and must not share a memo entry
$explicit = \PHPStanTurbo\TypeCombinatorCache::union(new \PHPStan\Type\MixedType(true), $intT);
$implicit = \PHPStanTurbo\TypeCombinatorCache::union(new \PHPStan\Type\MixedType(false), $intT);
check($describe($explicit) !== $describe($implicit), 'TCC keeps explicit/implicit mixed apart');

// no interning: argument tuples with different memo keys that arrive at the same
// value hand back distinct instances, as the PHP implementation does
$wider = \PHPStanTurbo\TypeCombinatorCache::union($intT, $stringT, new \PHPStan\Type\NeverType());
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
foreach ([\PHPStan\Type\BooleanType::class, \PHPStan\Type\Constant\ConstantBooleanType::class, \PHPStan\Type\IntegerType::class, \PHPStan\Type\Constant\ConstantIntegerType::class, \PHPStan\Type\IntegerRangeType::class, \PHPStan\Type\StringType::class, \PHPStan\Type\Constant\ConstantStringType::class, \PHPStan\Type\ClassStringType::class, \PHPStan\Type\Generic\GenericClassStringType::class, \PHPStan\Type\FloatType::class, \PHPStan\Type\Constant\ConstantFloatType::class, \PHPStan\Type\NullType::class, \PHPStan\Type\VoidType::class, \PHPStan\Type\NeverType::class, \PHPStan\Type\MixedType::class, \PHPStan\Type\StrictMixedType::class, \PHPStan\Type\ObjectWithoutClassType::class, \PHPStan\Type\StaticType::class, \PHPStan\Type\ThisType::class, \PHPStan\Type\Generic\GenericStaticType::class, \PHPStan\Type\ObjectShapeType::class, \PHPStan\Type\NonexistentParentClassType::class, \PHPStan\Type\ArrayType::class, \PHPStan\Type\Accessory\NonEmptyArrayType::class, \PHPStan\Type\Accessory\AccessoryArrayListType::class, \PHPStan\Type\Accessory\OversizedArrayType::class, \PHPStan\Type\Accessory\HasOffsetType::class, \PHPStan\Type\Accessory\HasOffsetValueType::class, \PHPStan\Type\Accessory\AccessoryNumericStringType::class, \PHPStan\Type\Accessory\AccessoryNonEmptyStringType::class, \PHPStan\Type\Accessory\AccessoryNonFalsyStringType::class, \PHPStan\Type\Accessory\AccessoryLiteralStringType::class, \PHPStan\Type\Accessory\AccessoryLowercaseStringType::class, \PHPStan\Type\Accessory\AccessoryUppercaseStringType::class, \PHPStan\Type\Accessory\AccessoryDecimalIntegerStringType::class, \PHPStan\Type\Accessory\HasMethodType::class, \PHPStan\Type\Accessory\HasPropertyType::class, \PHPStan\Type\ObjectType::class, \PHPStan\Type\Generic\GenericObjectType::class, \PHPStan\Type\Enum\EnumCaseObjectType::class, \PHPStan\Type\IterableType::class, \PHPStan\Type\CallableType::class, \PHPStan\Type\ClosureType::class, \PHPStan\Type\Constant\ConstantArrayType::class, \PHPStan\Type\UnionType::class, \PHPStan\Type\BenevolentUnionType::class, \PHPStan\Type\IntersectionType::class] as $typeClass) {
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

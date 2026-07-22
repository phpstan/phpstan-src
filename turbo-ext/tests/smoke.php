<?php declare(strict_types=1);

// Differential test: PHPStanTurbo\* native classes vs PHPStan's PHP implementations.
// Run: php -d extension=.../phpstan_turbo.so smoke.php  (from repo root)

require __DIR__ . '/../../vendor/autoload.php';

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

if (!extension_loaded('phpstan_turbo')) {
	fwrite(STDERR, "extension not loaded\n");
	exit(2);
}

// The generated class map minus the *Impl entries: the enabler is NOT run
// here, so the original class names are the real PHP twins, not the stub
// subclasses — configuring the Impl entries would make the native factories
// instantiate the PHP implementations. Unconfigured, they fall back to the
// native classes, which is what the differential comparison needs.
\PHPStanTurbo\Runtime::configure(array_filter(
	require __DIR__ . '/../../vendor/turbo-class-map.php',
	static fn (string $key): bool => !str_ends_with($key, 'Impl'),
	ARRAY_FILTER_USE_KEY,
));

// ---- TrinaryLogic ----
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
	check(get_class($pAll[$k]->toBooleanType()) === get_class($nAll[$k]->toBooleanType()), "$k toBooleanType() class");
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
$expr1 = new \PhpParser\Node\Expr\Variable('a');
$expr2 = new \PhpParser\Node\Expr\Variable('b');
$int = new \PHPStan\Type\IntegerType();
$string = new \PHPStan\Type\StringType();
$int2 = new \PHPStan\Type\IntegerType();

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

// ---- ConditionalExpressionHolder ----
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

\PHPStanTurbo\TypeCombinatorCache::clearCache();
$afterClear = \PHPStanTurbo\TypeCombinatorCache::union($intT, $stringT);
check($describe($afterClear) === $describe($first), 'TCC clearCache keeps results correct');
check($afterClear !== $first, 'TCC clearCache actually drops entries');

// ---- ExpressionResultStorage ----
$makeScope = static function () {
	static $reflection = null;
	$reflection ??= new ReflectionClass(\PHPStan\Analyser\MutatingScope::class);
	return $reflection->newInstanceWithoutConstructor();
};

foreach (['php' => \PHPStan\Analyser\ExpressionResultStorage::class, 'native' => \PHPStanTurbo\ExpressionResultStorage::class] as $label => $storageClass) {
	$storage = new $storageClass();
	$exprA = new \PhpParser\Node\Expr\Variable('a');
	$exprB = new \PhpParser\Node\Expr\Variable('b');
	$scopeA = $makeScope();
	$scopeB = $makeScope();

	check($storage->findBeforeScope($exprA) === null, "ERS $label: find on empty storage is null");
	$storage->storeBeforeScope($exprA, $scopeA);
	check($storage->findBeforeScope($exprA) === $scopeA, "ERS $label: find returns the stored scope");
	check($storage->findBeforeScope($exprB) === null, "ERS $label: unknown expr is null");
	$storage->storeBeforeScope($exprA, $scopeB);
	check($storage->findBeforeScope($exprA) === $scopeB, "ERS $label: overwrite for the same expr");

	$duplicate = $storage->duplicate();
	check(get_class($duplicate) === $storageClass, "ERS $label: duplicate creates the same class");
	check($duplicate->findBeforeScope($exprA) === $scopeB, "ERS $label: duplicate carries stored entries");
	$duplicate->storeBeforeScope($exprB, $scopeA);
	check($duplicate->findBeforeScope($exprB) === $scopeA, "ERS $label: store on the duplicate");
	check($storage->findBeforeScope($exprB) === null, "ERS $label: duplicate stores do not leak back");
	$storage->storeBeforeScope($exprB, $scopeB);
	check($duplicate->findBeforeScope($exprB) === $scopeA, "ERS $label: original stores do not leak into the duplicate");

	check($duplicate->pendingFibers === [] && $duplicate->parkedFibers === [], "ERS $label: duplicate starts with empty fiber arrays");
	$storage->pendingFibers[] = ['marker' => 1];
	$storage->parkedFibers[] = 'parked';
	check(count($storage->pendingFibers) === 1 && $storage->parkedFibers === ['parked'], "ERS $label: fiber arrays are appendable");
	$secondDuplicate = $storage->duplicate();
	check($secondDuplicate->pendingFibers === [] && $secondDuplicate->parkedFibers === [], "ERS $label: duplicate does not carry fiber arrays");
	unset($storage->pendingFibers[0]);
	check($storage->pendingFibers === [], "ERS $label: fiber array entries can be unset");
}

// ---- NodeScanner ----
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

	$int = new \PHPStan\Type\IntegerType();
	$string = new \PHPStan\Type\StringType();

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

// buildTypeSpecifications — pure, so both sides can share the inputs; the
// scalar/array/unary-minus entries must be dropped and the result ordered by
// expression-string length with sure-before-not tie-breaking
$specInt = new \PHPStan\Type\IntegerType();
$specString = new \PHPStan\Type\StringType();
$specSure = [
	'$bb' => [new \PhpParser\Node\Expr\Variable('bb'), $specInt],
	'$a' => [new \PhpParser\Node\Expr\Variable('a'), $specInt],
	"'lit'" => [new \PhpParser\Node\Scalar\String_('lit'), $specString],
	'-5' => [new \PhpParser\Node\Expr\UnaryMinus(new \PhpParser\Node\Scalar\Int_(5)), $specInt],
	'[]' => [new \PhpParser\Node\Expr\Array_([]), $specInt],
];
$specSureNot = [
	'$a' => [new \PhpParser\Node\Expr\Variable('a'), $specString],
	'$o->p' => [new \PhpParser\Node\Expr\PropertyFetch(new \PhpParser\Node\Expr\Variable('o'), 'p'), $specString],
];
$specResults = [];
foreach ($scopeOpsClasses as $side => $scopeOpsClass) {
	$specResults[$side] = array_map(
		static fn (array $specification): array => [
			$specification['sure'],
			$specification['exprString'],
			spl_object_id($specification['expr']),
			$specification['type']->describe(\PHPStan\Type\VerbosityLevel::precise()),
		],
		$scopeOpsClass::buildTypeSpecifications($specSure, $specSureNot),
	);
}
check($specResults['php'] === $specResults['native'], 'ScopeOps buildTypeSpecifications: filtering, ordering and tie-breaking');

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

	$int = new \PHPStan\Type\IntegerType();
	$string = new \PHPStan\Type\StringType();
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

echo $failures === 0 ? "ALL OK\n" : "$failures FAILURES\n";
exit($failures === 0 ? 0 : 1);

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

\PHPStanTurbo\Runtime::configure([
	'typeCombinator' => \PHPStan\Type\TypeCombinator::class,
	'booleanType' => \PHPStan\Type\BooleanType::class,
	'constantBooleanType' => \PHPStan\Type\Constant\ConstantBooleanType::class,
	'shouldNotHappenException' => \PHPStan\ShouldNotHappenException::class,
	'verbosityLevel' => \PHPStan\Type\VerbosityLevel::class,
	'variable' => \PhpParser\Node\Expr\Variable::class,
	'funcCall' => \PhpParser\Node\Expr\FuncCall::class,
	'virtualNode' => \PHPStan\Node\VirtualNode::class,
	'node' => \PhpParser\Node::class,
	'name' => \PhpParser\Node\Name::class,
	'expr' => \PhpParser\Node\Expr::class,
	'propertyFetch' => \PhpParser\Node\Expr\PropertyFetch::class,
	'intertwinedVariableByReferenceWithExpr' => \PHPStan\Node\Expr\IntertwinedVariableByReferenceWithExpr::class,
	'arrayDimFetch' => \PhpParser\Node\Expr\ArrayDimFetch::class,
	'methodCall' => \PhpParser\Node\Expr\MethodCall::class,
	'functionLike' => \PhpParser\Node\FunctionLike::class,
]);

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

echo $failures === 0 ? "ALL OK\n" : "$failures FAILURES\n";
exit($failures === 0 ? 0 : 1);

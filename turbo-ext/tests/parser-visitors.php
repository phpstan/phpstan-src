<?php declare(strict_types=1);

/**
 * Differential coverage of the natively ported PHPStan\Parser\*Visitor
 * classes. Included by smoke.php, which declares the native classes as
 * PHPStanTurbo\* next to their PHP twins.
 *
 * Every snippet is traversed four ways and the resulting ASTs (plus the
 * state TraitCollectingVisitor gathers) are compared byte for byte:
 *
 *   php/php        the PHP visitors under the PHP traverser — the reference
 *   turbo/turbo    the production path: the native traverser dispatches the
 *                  native visitors through their pt_native_visitor entry,
 *                  with no engine frame
 *   php/turbo      the native visitors' PHP_METHOD glue, called by the PHP
 *                  traverser like any userland visitor
 *   turbo/php      the native traverser driving the PHP twins, so the
 *                  native-dispatch fast path cannot hide a traverser change
 *
 * The visitors mutate the AST they are given, so each run parses its own.
 */

$parserVisitorClasses = [
	'ArrayFilterArgVisitor',
	'ArrayFindArgVisitor',
	'ArrayMapArgVisitor',
	'ArrayOffsetNormalizingVisitor',
	'ArrayWalkArgVisitor',
	'ArrowFunctionArgVisitor',
	'ClosureArgVisitor',
	'ClosureBindArgVisitor',
	'ClosureBindToVarVisitor',
	'CurlSetOptArgVisitor',
	'CurlSetOptArrayArgVisitor',
	'DeclarePositionVisitor',
	'ImmediatelyInvokedClosureVisitor',
	'ImplodeArgVisitor',
	'MagicConstantParamDefaultVisitor',
	'NewAssignedToPropertyVisitor',
	'ParentStmtTypesVisitor',
	'TraitCollectingVisitor',
	'TryCatchTypeVisitor',
	'TypeTraverserInstanceofVisitor',
];

// the class constants PHPStan reads the attributes back through
foreach ($parserVisitorClasses as $shortName) {
	$phpClass = 'PHPStan\\Parser\\' . $shortName;
	$turboClass = 'PHPStanTurbo\\' . $shortName;
	foreach ((new ReflectionClass($phpClass))->getConstants() as $constantName => $value) {
		check(
			constant($turboClass . '::' . $constantName) === $value,
			"parser visitors: $shortName::$constantName",
		);
	}
}

$parserVisitorSnippets = [
	// the "mark one argument of a named call" family, with the first-class
	// callable and named-argument forms that must not be marked
	'<?php curl_setopt($ch, CURLOPT_URL, $url); CURL_SETOPT($ch, $o, $v); curl_setopt(...); curl_setopt();',
	'<?php curl_setopt_array($ch, [CURLOPT_URL => $url]); curl_setopt_array($ch); curl_setopt_array(...);',
	'<?php array_walk($a, $cb); array_walk(...); array_walk($a); $o->array_walk($a, $cb);',
	'<?php implode(",", $a); join(",", $a); Implode($a); implode(...); \implode(",", $a);',
	'<?php array_filter($a, $cb); array_filter($a); array_filter(...);',
	'<?php array_find($a, $cb); array_any($a, $cb); array_all($a, $cb); array_find_key($a, $cb); array_search($a, $cb);',
	'<?php array_map($cb, $a, $b); array_map($cb, $a); array_map($cb); array_map(null, $a, $b);
		array_map(callback: $cb, array: $a); array_map(array: $a, callback: $cb); array_map(...);',

	// closures and arrow functions called where they are written
	'<?php (function ($x) { return $x; })(1); (function () {})(); ($f = function ($x) {})(2);',
	'<?php (fn ($x) => $x)(1); (fn () => 1)(); ($f = fn ($x) => $x)(2);',
	'<?php $c->bindTo($obj); $c->bindTo(); $c->bindTo(...); $c->BINDTO($obj); $c->other($obj);',
	'<?php Closure::bind($c, $obj); Closure::bind($c); \Closure::bind($c, $obj, "X"); Closure::bind(...);
		Closure::fromCallable($c); Other::bind($c, $obj);',

	// array offsets: every literal spelling the normalizer canonicalises
	'<?php echo $a[\'k\'], $a["k"], $a["a\nb"], $a["$x"], $a["pre{$x}post"], $a[1], $a[0x1F], $a[0b11], $a[$i], $a[C], $a[];',

	// declare position, with and without a shebang
	"<?php declare(strict_types = 1); namespace N; declare(ticks = 1);",
	"#!/usr/bin/env php\n<?php declare(strict_types = 1);",
	'<?php $x = 1; declare(ticks = 1);',

	// magic constants as parameter defaults
	'<?php function f($a = __DIR__, $b = __LINE__, $c = 1, $d = self::C) {} class K { public function m($e = __CLASS__) {} }',

	// new assigned to a property
	'<?php $this->p = new A(); self::$q = new B(); $this->r ??= new C(); $this->s .= new D();
		$x = new E(); $this->t = $u; $this->v = &$u; static::$w = new F();',

	// traits
	'<?php trait T { public function m() {} } trait U {} class C { use T; }',
	'<?php namespace N { trait V {} } namespace M { trait W {} }',

	// parent statement types and try/catch types
	'<?php
		function f() {
			try {
				echo 1;
				try { echo 2; } catch (A | B $e) { echo 3; } finally { echo 4; }
				$c = function () { try { echo 5; } catch (C $e) {} };
				echo match ($x) { 1 => 2, default => 3 };
			} catch (\D $e) {
				echo 6;
			}
		}
		try { f(); } catch (E $e) {} ',
	'<?php try { echo 1; } catch (A $e) { function g() { echo 2; } } while ($x) { do { echo 3; } while ($y); }',

	// instanceof inside TypeTraverser::map()
	'<?php
		$x instanceof Foo;
		\PHPStan\Type\TypeTraverser::map($t, function ($type, $cb) {
			if ($type instanceof Bar) { return $type; }
			return \PHPStan\Type\TypeTraverser::map($type, function ($inner, $cb2) {
				return $inner instanceof Baz ? $inner : $cb2($inner);
			});
		});
		$y instanceof Qux;
		TypeTraverser::map($t, $cb);
		\PHPStan\Type\TypeTraverser::other($t, $cb);',
];

// plus real source files, so the ports meet whatever the repository contains
foreach ([
	'src/Analyser/NodeScopeResolver.php',
	'src/Analyser/MutatingScope.php',
	'src/Analyser/TypeSpecifier.php',
	'src/Type/UnionType.php',
	'src/Type/Constant/ConstantArrayType.php',
	'src/Reflection/ClassReflection.php',
	'src/Parser/RichParser.php',
	'src/Parser/TryCatchTypeVisitor.php',
	'src/Rules/Methods/CallMethodsRule.php',
	'src/Command/AnalyseApplication.php',
] as $sourceFile) {
	$parserVisitorSnippets[] = file_get_contents(dirname(__DIR__, 2) . '/' . $sourceFile);
}

$runParserVisitors = static function (string $traverserClass, string $visitorPrefix, string $code) use ($smokeParser, $parserVisitorClasses): string {
	$traverser = new $traverserClass();
	$traitCollector = null;
	foreach ($parserVisitorClasses as $shortName) {
		$class = $visitorPrefix . $shortName;
		$visitor = new $class();
		if ($shortName === 'TraitCollectingVisitor') {
			$traitCollector = $visitor;
		}
		$traverser->addVisitor($visitor);
	}
	// twice, so the beforeTraverse() state resets are exercised too
	$traverser->traverse($smokeParser->parse($code));
	$result = $traverser->traverse($smokeParser->parse($code));

	return serialize([$result, $traitCollector->traits]);
};

foreach ($parserVisitorSnippets as $snippetIndex => $parserVisitorCode) {
	$reference = $runParserVisitors(\PhpParser\NodeTraverser::class, 'PHPStan\\Parser\\', $parserVisitorCode);
	$combinations = [
		'native traverser, native visitors' => [\PHPStanTurbo\NodeTraverser::class, 'PHPStanTurbo\\'],
		'PHP traverser, native visitors' => [\PhpParser\NodeTraverser::class, 'PHPStanTurbo\\'],
		'native traverser, PHP visitors' => [\PHPStanTurbo\NodeTraverser::class, 'PHPStan\\Parser\\'],
	];
	foreach ($combinations as $label => [$traverserClass, $visitorPrefix]) {
		check(
			$runParserVisitors($traverserClass, $visitorPrefix, $parserVisitorCode) === $reference,
			"parser visitors snippet #$snippetIndex ($label)",
		);
	}
}

// ---- NodeTraverser semantics beyond the visitor ports ----
// Each probe runs once against php-parser's NodeTraverser and once against
// the native one (a subclass of each is declared from the same source), and
// the observations must be identical modulo the prefix.
$ntNormalize = static fn (string $s): string => str_replace(['PHPStanTurbo\\NodeTraverser', '_native'], ['PhpParser\\NodeTraverser', '_php'], $s);
$ntObserve = static function (callable $probe) use ($ntNormalize): array {
	try {
		$result = $probe();
	} catch (\Throwable $e) {
		return ['threw', get_class($e), $ntNormalize($e->getMessage())];
	}

	return ['returned', $ntNormalize(serialize($result))];
};
$ntSides = ['php' => \PhpParser\NodeTraverser::class, 'native' => \PHPStanTurbo\NodeTraverser::class];
$ntCompare = static function (string $label, callable $probeForSide) use ($ntSides, $ntObserve): void {
	$observed = [];
	foreach ($ntSides as $side => $traverserClass) {
		$observed[$side] = $ntObserve(static fn () => $probeForSide($side, $traverserClass));
	}
	check($observed['php'] === $observed['native'], "NodeTraverser: $label: " . json_encode($observed));
};

// the properties are declared like the twin's: `protected array $visitors = []`
// and `protected bool $stopTraversal` (typed, uninitialized until traverse())
foreach ($ntSides as $side => $traverserClass) {
	eval(sprintf(
		'final class NtRedeclaring_%1$s extends \\%2$s { protected array $visitors = []; protected bool $stopTraversal = false; public function stop(): bool { return $this->stopTraversal; } }
		final class NtProbing_%1$s extends \\%2$s { public function stop(): bool { return $this->stopTraversal; } public function dropVisitors(): void { unset($this->visitors); } }',
		$side,
		$traverserClass,
	));
}
foreach (['visitors', 'stopTraversal'] as $ntProperty) {
	$ntCompare("declaration of \$$ntProperty", static function (string $side, string $traverserClass) use ($ntProperty): array {
		$property = new \ReflectionProperty($traverserClass, $ntProperty);
		return [(string) $property->getType(), $property->hasDefaultValue(), $property->getDefaultValue(), $property->isProtected()];
	});
}
$ntCompare('a subclass redeclaring the typed properties', static function (string $side): array {
	$class = 'NtRedeclaring_' . $side;
	$traverser = new $class();
	return [$traverser->stop(), $traverser->traverse([]), $traverser->stop()];
});
$ntCompare('stopTraversal before traverse()', static function (string $side): bool {
	$class = 'NtProbing_' . $side;
	return (new $class())->stop();
});
$ntCompare('stopTraversal after traverse()', static function (string $side): bool {
	$class = 'NtProbing_' . $side;
	$traverser = new $class();
	$traverser->traverse([]);
	return $traverser->stop();
});
$ntCompare('traverse() with $visitors unset', static function (string $side): array {
	$class = 'NtProbing_' . $side;
	$traverser = new $class();
	$traverser->dropVisitors();
	return $traverser->traverse([]);
});
$ntCompare('removeVisitor() with $visitors unset', static function (string $side): void {
	$class = 'NtProbing_' . $side;
	$traverser = new $class();
	$traverser->dropVisitors();
	$traverser->removeVisitor(new \PhpParser\NodeVisitor\NameResolver());
});
$ntCompare('addVisitor() with $visitors unset', static function (string $side): array {
	$class = 'NtProbing_' . $side;
	$traverser = new $class();
	$traverser->dropVisitors();
	$traverser->addVisitor(new \PhpParser\NodeVisitor\NodeConnectingVisitor());
	return $traverser->traverse([new \PhpParser\Node\Stmt\Nop()]);
});

// a subnode array is traversed as a copy and assigned back once, after the
// loop: a visitor holding the parent's array keeps seeing the original, the
// parent's property keeps the original until the loop ends, and a visitor
// reassigning the property is overwritten by the traversal result
$ntMarks = static fn (array $nodes): string => implode(',', array_map(static fn ($n) => $n instanceof \PhpParser\Node ? ($n->getAttribute('r') === true ? 'R' : 'o') : gettype($n), $nodes));
$ntReplacingVisitor = static fn (callable $onEcho) => new class ($onEcho) extends \PhpParser\NodeVisitorAbstract {

	public ?\PhpParser\Node\Stmt\Function_ $function = null;

	/** @var list<string> */
	public array $log = [];

	public mixed $held = null;

	public function __construct(private $onEcho)
	{
	}

	public function enterNode(\PhpParser\Node $node)
	{
		if ($node instanceof \PhpParser\Node\Stmt\Function_) {
			$this->function = $node;
			$this->held = $node->stmts;
			return null;
		}
		if ($node instanceof \PhpParser\Node\Stmt\Echo_) {
			return ($this->onEcho)($this, $node);
		}
		return null;
	}

};
$ntCompare('replacements in a subnode array a visitor holds', static function (string $side, string $traverserClass) use ($smokeParser, $ntMarks, $ntReplacingVisitor): array {
	$visitor = $ntReplacingVisitor(static function ($visitor, $node) use ($ntMarks) {
		$visitor->log[] = $ntMarks($visitor->function->stmts) . ' held=' . $ntMarks($visitor->held);
		$replacement = clone $node;
		$replacement->setAttribute('r', true);
		return $replacement;
	});
	$ast = (new $traverserClass($visitor))->traverse($smokeParser->parse('<?php function f() { echo 1; echo 2; echo 3; }'));
	return [$visitor->log, $ntMarks($ast[0]->stmts), $ntMarks($visitor->held)];
});
$ntCompare('a visitor reassigning the subnode array', static function (string $side, string $traverserClass) use ($smokeParser, $ntMarks, $ntReplacingVisitor): array {
	$visitor = $ntReplacingVisitor(static function ($visitor, $node) {
		$visitor->function->stmts = [new \PhpParser\Node\Stmt\Nop()];
		return null;
	});
	$ast = (new $traverserClass($visitor))->traverse($smokeParser->parse('<?php function f() { echo 1; echo 2; }'));
	return array_map(static fn ($n) => $n->getType(), $ast[0]->stmts);
});
$ntCompare('a visitor reassigning the subnode array next to a replacement', static function (string $side, string $traverserClass) use ($smokeParser, $ntMarks, $ntReplacingVisitor): array {
	$visitor = $ntReplacingVisitor(static function ($visitor, $node) {
		if ($node->exprs[0]->value === 1) {
			$visitor->function->stmts = [new \PhpParser\Node\Stmt\Nop()];
			return null;
		}
		$replacement = clone $node;
		$replacement->setAttribute('r', true);
		return $replacement;
	});
	$ast = (new $traverserClass($visitor))->traverse($smokeParser->parse('<?php function f() { echo 1; echo 2; }'));
	return [array_map(static fn ($n) => $n->getType(), $ast[0]->stmts), $ntMarks($ast[0]->stmts)];
});

// an untouched subnode array is not copied: the shared [] of the parser's
// empty lists stays shared, so traversing a cached AST allocates nothing
$ntRetained = [];
foreach ($ntSides as $side => $traverserClass) {
	$ast = $smokeParser->parse(file_get_contents(dirname(__DIR__, 2) . '/src/Analyser/NodeScopeResolver.php'));
	$traverser = new $traverserClass(new class extends \PhpParser\NodeVisitorAbstract {

		public function enterNode(\PhpParser\Node $node)
		{
			return null;
		}

	});
	$traverser->traverse([new \PhpParser\Node\Stmt\Nop()]);
	gc_collect_cycles();
	$before = memory_get_usage();
	$ast = $traverser->traverse($ast);
	gc_collect_cycles();
	$ntRetained[$side] = memory_get_usage() - $before;
	unset($ast, $traverser);
}
check($ntRetained['native'] <= max($ntRetained['php'], 0) + 1024, 'NodeTraverser: a no-op traversal retains no copies: ' . json_encode($ntRetained));

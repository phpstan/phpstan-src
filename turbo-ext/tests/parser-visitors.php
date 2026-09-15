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

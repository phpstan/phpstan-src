<?php declare(strict_types = 1);

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Expr\YieldFrom;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\VariadicPlaceholder;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Turbo\ShadowedByTurboExtension;

/**
 * Generates three files from the ShadowedByTurboExtension and
 * ReferencedByTurboExtension attributes on every autoloader dump
 * (composer.json scripts.post-autoload-dump):
 *
 * - vendor/turbo-stubs.php: one empty stub shell per shadowed class,
 *   extending the phpstan_turbo extension's native counterpart the attribute
 *   names. TurboExtensionEnabler requires the file before the Composer
 *   autoloader registers, so with the extension active every reference to
 *   the original class name transparently resolves to the native
 *   implementation.
 * - vendor/turbo-shadowed-classes.json: the manifest of shadowed pairs
 *   (each class's PHP source and the .cpp implementing it natively), read
 *   by TurboExtensionEnabler::getShadowedClassSourceFiles(), the compiler's
 *   preload builder, and turbo-ext/tests/signature-parity.php.
 * - vendor/turbo-class-map.php: the class map TurboExtensionEnabler passes
 *   to PHPStanTurbo\Runtime::configure() — one entry per key of the native
 *   class-reference table (pt_class_refs in turbo-ext/src/support.cpp),
 *   from the ReferencedByTurboExtension attributes plus the hardcoded
 *   vendored PhpParser entries below.
 *
 * The attributes are read with runtime reflection against the freshly dumped
 * autoloader — unlike ondrejmirtes/composer-attribute-collector, which parses
 * sources because it must run on PHP 7.4.
 */

error_reporting(E_ALL);

if (PHP_VERSION_ID < 80200) {
	// the CI downgrade legs dump the autoloader under PHP 7.4–8.1, where the
	// not-yet-downgraded sources cannot be class-loaded (and attribute
	// reflection needs 8.0+). The extension requires 8.3+ anyway, and
	// TurboExtensionEnabler treats a missing stubs file as "stay inactive".
	echo "Skipping turbo-stubs.php generation on PHP < 8.2\n";
	exit(0);
}

$root = dirname(__DIR__);

require_once $root . '/vendor/autoload.php';

$rootReal = realpath($root);
if ($rootReal === false) {
	throw new RuntimeException('realpath() failed');
}

$relativize = static function (string $path) use ($rootReal): string {
	$real = realpath($path);
	if ($real === false) {
		throw new RuntimeException(sprintf('%s does not exist', $path));
	}

	return str_replace(DIRECTORY_SEPARATOR, '/', substr($real, strlen($rootReal) + 1));
};

// Shadowed classes living in vendor/ cannot carry the attribute, so their
// pairs are hardcoded here. Class name => [native class, final, .cpp file]
$pairs = [
	'PhpParser\NodeTraverser' => ['PHPStanTurbo\NodeTraverser', false, 'turbo-ext/src/NodeTraverser.cpp'],
];

// Classes the native code references that live in vendor/ cannot carry the
// ReferencedByTurboExtension attribute either; their class-map entries are
// hardcoded here. Key (pt_class_refs in turbo-ext/src/support.cpp) => class
$classMap = [
	'variable' => Variable::class,
	'funcCall' => FuncCall::class,
	'node' => Node::class,
	'name' => Name::class,
	'expr' => Expr::class,
	'propertyFetch' => PropertyFetch::class,
	'arrayDimFetch' => ArrayDimFetch::class,
	'methodCall' => MethodCall::class,
	'functionLike' => FunctionLike::class,
	'callLike' => CallLike::class,
	'staticCall' => StaticCall::class,
	'newExpr' => New_::class,
	'classStmt' => Class_::class,
	'variadicPlaceholder' => VariadicPlaceholder::class,
	'scalar' => Scalar::class,
	'arrayExpr' => Array_::class,
	'unaryMinus' => UnaryMinus::class,
	'yield' => Yield_::class,
	'yieldFrom' => YieldFrom::class,
	'stmt' => Stmt::class,
	'nodeVisitorAbstract' => NodeVisitorAbstract::class,
	'closureExpr' => Closure::class,
	'arrowFunction' => ArrowFunction::class,
];

$sourceDir = $root . '/src';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
	if ($file->getExtension() !== 'php') {
		continue;
	}
	$path = str_replace(DIRECTORY_SEPARATOR, '/', $file->getPathname());
	$contents = file_get_contents($path);
	if ($contents === false) {
		throw new RuntimeException(sprintf('Could not read %s', $path));
	}
	if (!str_contains($contents, 'ByTurboExtension')) {
		continue;
	}

	// src/ is PSR-4 for the PHPStan namespace (held in CI by the name
	// collision detector), so the class name follows from the path
	$className = 'PHPStan\\' . strtr(substr($path, strlen($sourceDir) + 1, -strlen('.php')), '/', '\\');
	if (!class_exists($className) && !interface_exists($className)) {
		continue; // the file declares no class of its own
	}
	$reflection = new ReflectionClass($className);
	$attributes = $reflection->getAttributes(ShadowedByTurboExtension::class);
	if (count($attributes) > 0) {
		$attribute = $attributes[0]->newInstance();
		$pairs[$className] = [$attribute->turboClass, $reflection->isFinal(), $relativize($attribute->implementation)];
	}

	foreach ($reflection->getAttributes(ReferencedByTurboExtension::class) as $referenced) {
		$key = $referenced->newInstance()->key;
		if (isset($classMap[$key])) {
			throw new RuntimeException(sprintf('Both %s and %s claim the class-map key %s', $classMap[$key], $className, $key));
		}
		$classMap[$key] = $className;
	}
}

ksort($pairs);

$namespaces = [];
$manifest = [];
foreach ($pairs as $className => [$turboClass, $final, $cppFile]) {
	$pos = strrpos($className, '\\');
	$namespaces[substr($className, 0, $pos)][] = sprintf(
		"\t%sclass %s extends \\%s {}",
		$final ? 'final ' : '',
		substr($className, $pos + 1),
		$turboClass,
	);

	$phpFile = $relativize((new ReflectionClass($className))->getFileName());
	$entry = [
		'php' => $phpFile,
		'cpp' => $cppFile,
	];
	if (str_starts_with($phpFile, 'vendor/')) {
		$entry['vendored'] = true;
	}
	$manifest[$className] = $entry;
}

$blocks = [];
foreach ($namespaces as $namespace => $declarations) {
	$blocks[] = sprintf("namespace %s {\n\n%s\n\n}", $namespace, implode("\n", $declarations));
}

file_put_contents($root . '/vendor/turbo-stubs.php', sprintf(
	<<<'PHP'
<?php declare(strict_types = 1);

// turbo-stubs.php @generated by build/generate-turbo-stubs.php — do not edit.
// Empty stub shells shadowing each class marked with the
// ShadowedByTurboExtension attribute (plus the hardcoded vendored
// PhpParser\NodeTraverser) with the phpstan_turbo extension's native
// implementation. Required by PHPStan\Turbo\TurboExtensionEnabler before
// the Composer autoloader registers.

%s

PHP,
	implode("\n\n", $blocks),
));

file_put_contents(
	$root . '/vendor/turbo-shadowed-classes.json',
	json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n",
);

ksort($classMap);

$classMapEntries = '';
foreach ($classMap as $key => $className) {
	$classMapEntries .= sprintf("\t%s => %s,\n", var_export($key, true), var_export($className, true));
}

file_put_contents($root . '/vendor/turbo-class-map.php', sprintf(
	<<<'PHP'
<?php declare(strict_types = 1);

// turbo-class-map.php @generated by build/generate-turbo-stubs.php — do not
// edit. The class map PHPStan\Turbo\TurboExtensionEnabler passes to
// PHPStanTurbo\Runtime::configure(): one entry per key of the native
// class-reference table, from the ReferencedByTurboExtension attributes
// (plus the hardcoded vendored PhpParser entries).

return [
%s];

PHP,
	$classMapEntries,
));

echo sprintf("Generated turbo-stubs.php, turbo-shadowed-classes.json (%d classes) and turbo-class-map.php (%d entries)\n", count($pairs), count($classMap));

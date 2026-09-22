<?php declare(strict_types = 1);

/**
 * Compares each shadowed pair's declaration via reflection under REAL-NAME
 * activation — the shape PHPStan runs with: the native class must declare
 * exactly what the PHP twin declares. Checked per class: final/abstract,
 * parent and interfaces; every method of any visibility (a missing or an
 * extra one included) with its visibility, static/final/abstract flags,
 * parameter names, types, optionality, default values, by-ref and variadic
 * flags, and return type; every property (name, type, default, visibility,
 * static, readonly) and every class constant (value, visibility, final).
 * Name-level source parity is bin/side-by-side.php's job; this catches the
 * finer drift — a renamed parameter or a missing default breaks named
 * arguments only in turbo mode, an erased or prefixed class name in the
 * arginfo changes what reflection and the engine's type checks see.
 *
 * Run with the extension loaded and vendor/ installed:
 *   php -d extension=$PWD/turbo-ext/phpstan_turbo.so turbo-ext/tests/signature-parity.php
 *
 * The twins cannot be reflected in a process whose shadowing is active
 * (their names are the native classes then), so a child process without
 * activation dumps them (--dump-twins) and this process activates the native
 * classes under the real names, dumps them the same way and compares.
 */

$root = dirname(__DIR__, 2);
chdir($root);

/**
 * Classes whose native declaration still drifts from the twin — class =>
 * reason. Their problems are listed but do not fail the check; an entry
 * whose class matches is reported as stale.
 *
 * @var array<string, string>
 */
$knownDrift = [
	'PhpParser\\NodeTraverser' => 'pending',
	'PHPStan\\Analyser\\ExpressionResultStorage' => 'pending',
	'PHPStan\\Analyser\\VariableLivenessResolver' => 'pending',
	'PHPStan\\Cache\\ArenaCache' => 'pending',
	'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\PhpFileCleaner' => 'pending',
	'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\SymbolFinderInFiles' => 'pending',
	'PHPStan\\Reflection\\Php\\PhpClassReflectionExtension' => 'pending',
];

/**
 * @param ReflectionType|null $type
 */
function typeString(?ReflectionType $type, string $selfClass): ?string
{
	if ($type === null) {
		return null;
	}
	// from PHP 8.5 reflection names the declaring class for self itself
	return (string) preg_replace('~(^|\||&|\?|\()self($|\||&|\))~i', '$1' . $selfClass . '$2', (string) $type);
}

function exportValue(mixed $value): string
{
	return var_export($value, true);
}

/**
 * @return array<string, mixed>
 */
function dumpClass(ReflectionClass $class, string $root): array
{
	$interfaces = $class->getInterfaceNames();
	sort($interfaces);
	$parent = $class->getParentClass();
	$file = $class->getFileName();
	$dump = [
		'final' => $class->isFinal(),
		'abstract' => $class->isAbstract(),
		'readonly' => method_exists($class, 'isReadOnly') ? $class->isReadOnly() : false,
		'parent' => $parent === false ? null : $parent->getName(),
		'interfaces' => $interfaces,
		// normalized to forward slashes: the manifest stores portable paths
		'file' => $file === false ? null : str_replace(DIRECTORY_SEPARATOR, '/', substr((string) realpath($file), strlen((string) realpath($root)) + 1)),
		'methods' => [],
		'properties' => [],
		'constants' => [],
	];

	foreach ($class->getMethods() as $method) {
		$declaring = $method->getDeclaringClass()->getName();
		$params = [];
		foreach ($method->getParameters() as $parameter) {
			$default = null;
			if ($parameter->isDefaultValueAvailable()) {
				try {
					$default = $parameter->isDefaultValueConstant()
						? 'const ' . $parameter->getDefaultValueConstantName()
						: exportValue($parameter->getDefaultValue());
				} catch (Throwable $e) {
					$default = 'unevaluable: ' . $e->getMessage();
				}
			}
			$params[] = [
				'name' => $parameter->getName(),
				'type' => typeString($parameter->getType(), $declaring),
				'optional' => $parameter->isOptional(),
				'default' => $default,
				'byRef' => $parameter->isPassedByReference(),
				'variadic' => $parameter->isVariadic(),
			];
		}
		$dump['methods'][$method->getName()] = [
			'class' => $declaring,
			'visibility' => $method->isPrivate() ? 'private' : ($method->isProtected() ? 'protected' : 'public'),
			'static' => $method->isStatic(),
			'final' => $method->isFinal(),
			'abstract' => $method->isAbstract(),
			'required' => $method->getNumberOfRequiredParameters(),
			'params' => $params,
			'return' => typeString($method->getReturnType() ?? $method->getTentativeReturnType(), $declaring),
		];
	}

	$defaults = $class->getDefaultProperties();
	foreach ($class->getProperties() as $property) {
		$declaring = $property->getDeclaringClass()->getName();
		$dump['properties'][$property->getName()] = [
			'class' => $declaring,
			'visibility' => $property->isPrivate() ? 'private' : ($property->isProtected() ? 'protected' : 'public'),
			'static' => $property->isStatic(),
			'readonly' => $property->isReadOnly(),
			'type' => typeString($property->getType(), $declaring),
			// a static property's current value would not be its default
			'default' => $property->hasDefaultValue() ? exportValue($property->isStatic() ? ($defaults[$property->getName()] ?? null) : $property->getDefaultValue()) : null,
		];
	}

	foreach ($class->getReflectionConstants() as $constant) {
		try {
			$value = exportValue($constant->getValue());
		} catch (Throwable $e) {
			$value = 'unevaluable: ' . $e->getMessage();
		}
		$dump['constants'][$constant->getName()] = [
			'class' => $constant->getDeclaringClass()->getName(),
			'visibility' => $constant->isPrivate() ? 'private' : ($constant->isProtected() ? 'protected' : 'public'),
			'final' => $constant->isFinal(),
			'value' => $value,
		];
	}

	return $dump;
}

/**
 * @return array<string, array<string, mixed>>
 */
function dumpAll(array $manifest, string $root): array
{
	$dumps = [];
	foreach (array_keys($manifest) as $className) {
		$dumps[$className] = dumpClass(new ReflectionClass($className), $root);
	}

	return $dumps;
}

function loadManifest(string $root): array
{
	$manifestFile = $root . '/vendor/turbo-shadowed-classes.json';
	$classMapFile = $root . '/vendor/turbo-class-map.php';
	if (!is_file($manifestFile) || !is_file($classMapFile)) {
		fwrite(STDERR, "vendor/turbo-shadowed-classes.json or vendor/turbo-class-map.php does not exist — run composer dump-autoload first\n");
		exit(2);
	}

	return [
		json_decode(file_get_contents($manifestFile), true, 8, JSON_THROW_ON_ERROR),
		require $classMapFile,
	];
}

require_once $root . '/vendor/autoload.php';

if (($argv[1] ?? null) === '--dump-twins') {
	[$manifest] = loadManifest($root);
	if (class_exists('PHPStanTurbo\Runtime', false) && \PHPStanTurbo\Runtime::isShadowing()) {
		fwrite(STDERR, "the twin dump runs with the native classes active\n");
		exit(2);
	}
	echo json_encode(dumpAll($manifest, $root), JSON_THROW_ON_ERROR);
	exit(0);
}

if (!extension_loaded('phpstan_turbo')) {
	fwrite(STDERR, "the phpstan_turbo extension is not loaded\n");
	exit(2);
}

[$manifest, $classMap] = loadManifest($root);

// the twins, from a process that never activates the extension (loading it
// declares nothing under PHPStan's class names)
$process = proc_open([PHP_BINARY, '-d', 'memory_limit=-1', __FILE__, '--dump-twins'], [1 => ['pipe', 'w'], 2 => STDERR], $pipes);
if ($process === false) {
	fwrite(STDERR, "proc_open failed\n");
	exit(2);
}
$twinJson = stream_get_contents($pipes[1]);
fclose($pipes[1]);
if (proc_close($process) !== 0) {
	fwrite(STDERR, "dumping the twins failed\n");
	exit(2);
}
$twins = json_decode($twinJson, true, 64, JSON_THROW_ON_ERROR);

// the natives, under the real names — what TurboExtensionEnabler declares
$twinFiles = [];
foreach ($manifest as $className => $entry) {
	$twinFiles[$className] = $root . '/' . $entry['php'];
}
\PHPStanTurbo\Runtime::configure($classMap);
\PHPStanTurbo\Runtime::activateShadowing($twinFiles);
$natives = dumpAll($manifest, $root);

/**
 * @param array<string, mixed> $native
 * @param array<string, mixed> $twin
 * @return list<string>
 */
function compareMembers(string $kind, string $className, array $native, array $twin): array
{
	$problems = [];
	// A private method is optional natively — the logic behind it lives in
	// C++ and nothing outside the class can call it — but a class that
	// declares any of its twin's private methods (for reflection, a
	// differential test, a private constructor) declares all of them.
	$privateMethodsMirrored = false;
	if ($kind === 'method') {
		foreach ($twin as $name => $twinMember) {
			if ($twinMember['class'] === $className && $twinMember['visibility'] === 'private' && isset($native[$name])) {
				$privateMethodsMirrored = true;
			}
		}
	}
	foreach ($twin as $name => $twinMember) {
		$nativeMember = $native[$name] ?? null;
		$own = $twinMember['class'] === $className || ($nativeMember !== null && $nativeMember['class'] === $className);
		if (!$own) {
			continue; // inherited from a class checked on its own
		}
		if ($nativeMember === null) {
			if ($kind === 'method' && $twinMember['visibility'] === 'private' && !$privateMethodsMirrored) {
				continue;
			}
			$problems[] = sprintf('%s %s is not declared natively', $kind, $name);
			continue;
		}
		foreach ($twinMember as $key => $twinValue) {
			if ($key === 'params') {
				continue;
			}
			if ($nativeMember[$key] !== $twinValue) {
				$problems[] = sprintf('%s %s: %s is %s natively, %s in PHP', $kind, $name, $key, json_encode($nativeMember[$key]), json_encode($twinValue));
			}
		}
		if (!isset($twinMember['params'])) {
			continue;
		}
		if (count($nativeMember['params']) !== count($twinMember['params'])) {
			$problems[] = sprintf('%s %s: %d parameters natively, %d in PHP', $kind, $name, count($nativeMember['params']), count($twinMember['params']));
			continue;
		}
		foreach ($twinMember['params'] as $i => $twinParam) {
			foreach ($twinParam as $key => $twinValue) {
				if ($nativeMember['params'][$i][$key] !== $twinValue) {
					$problems[] = sprintf('%s %s: parameter #%d ($%s) %s is %s natively, %s in PHP', $kind, $name, $i + 1, $twinParam['name'], $key, json_encode($nativeMember['params'][$i][$key]), json_encode($twinValue));
				}
			}
		}
	}
	foreach ($native as $name => $nativeMember) {
		if (!isset($twin[$name]) && $nativeMember['class'] === $className) {
			$problems[] = sprintf('%s %s is declared natively but not in PHP', $kind, $name);
		}
	}

	return $problems;
}

$failed = false;
$compared = 0;
$staleDrift = [];
foreach ($manifest as $twinClass => $entry) {
	$twin = $twins[$twinClass];
	$native = $natives[$twinClass];
	$problems = [];

	if (!(new ReflectionClass($twinClass))->isUserDefined() || !\PHPStanTurbo\Runtime::isShadowing()) {
		$problems[] = 'is not the shadowing class under real-name activation';
	}
	foreach (['final', 'abstract', 'readonly', 'parent', 'interfaces'] as $key) {
		if ($native[$key] !== $twin[$key]) {
			$problems[] = sprintf('%s is %s natively, %s in PHP', $key, json_encode($native[$key]), json_encode($twin[$key]));
		}
	}
	if (!array_key_exists('final', $entry) || !array_key_exists('parent', $entry)
		|| $entry['final'] !== $twin['final']
		|| $entry['parent'] !== $twin['parent']
	) {
		$problems[] = 'the manifest final/parent entries do not match the class — regenerate with composer dump-autoload';
	}
	// the manifest must point at the file the class actually lives in
	// (bin/side-by-side.php parses that file's source as the PHP side)
	if ($twin['file'] !== $entry['php']) {
		$problems[] = sprintf('lives in %s, but the manifest says %s — regenerate with composer dump-autoload', $twin['file'], $entry['php']);
	}
	if (($entry['vendored'] ?? false) !== str_starts_with((string) $twin['file'], 'vendor/')) {
		$problems[] = sprintf('the manifest "vendored" flag does not match the class location %s', $twin['file']);
	}

	$problems = array_merge(
		$problems,
		compareMembers('method', $twinClass, $native['methods'], $twin['methods']),
		compareMembers('property', $twinClass, $native['properties'], $twin['properties']),
		compareMembers('constant', $twinClass, $native['constants'], $twin['constants']),
	);
	$compared += count($twin['methods']) + count($twin['properties']) + count($twin['constants']);

	if (isset($knownDrift[$twinClass])) {
		if ($problems === []) {
			$staleDrift[] = $twinClass;
			continue;
		}
		foreach ($problems as $problem) {
			printf("~ %s: %s (known: %s)\n", $twinClass, $problem, $knownDrift[$twinClass]);
		}
		continue;
	}
	if ($problems === []) {
		printf("✓ %s\n", $twinClass);
		continue;
	}
	$failed = true;
	foreach ($problems as $problem) {
		printf("✗ %s: %s\n", $twinClass, $problem);
	}
}
foreach ($staleDrift as $className) {
	$failed = true;
	printf("✗ %s matches its twin — remove it from \$knownDrift\n", $className);
}

printf($failed ? "FAILED\n" : "OK (%d members compared)\n", $compared);
exit($failed ? 1 : 0);

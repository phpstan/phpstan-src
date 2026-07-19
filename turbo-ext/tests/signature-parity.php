<?php declare(strict_types = 1);

/**
 * Compares each shadowed pair's method signatures via reflection: the native
 * class must declare the same shape as the PHP twin — visibility, staticness,
 * parameter names/optionality/by-ref/variadic, and types. Name-level parity
 * is bin/side-by-side.php --check's job; this catches the finer drift (e.g.
 * a renamed parameter would break named arguments only in turbo mode).
 *
 * Run with the extension loaded and vendor/ installed:
 *   php -d extension=$PWD/turbo-ext/phpstan_turbo.so turbo-ext/tests/signature-parity.php
 *
 * The enabler is deliberately NOT run, so the original PHP classes load
 * unshadowed next to the PHPStanTurbo ones.
 */

$root = dirname(__DIR__, 2);
chdir($root);

if (!extension_loaded('phpstan_turbo')) {
	fwrite(STDERR, "the phpstan_turbo extension is not loaded\n");
	exit(1);
}

require $root . '/vendor/autoload.php';

// generated next to vendor/turbo-stubs.php by build/generate-turbo-stubs.php
$manifestFile = 'vendor/turbo-shadowed-classes.json';
if (!is_file($manifestFile)) {
	fwrite(STDERR, $manifestFile . " does not exist — run composer dump-autoload first\n");
	exit(1);
}
$manifest = json_decode(file_get_contents($manifestFile), true, 8, JSON_THROW_ON_ERROR);

// Native arginfo deliberately erases most types to none or object: it cannot
// bake class-name strings of prefixed namespaces into the binary (the scoped
// phar renames e.g. PhpParser\*), and engine-level type checks cost per call.
// So a native type is checked only when it declares something specific; the
// PHP twin remains the authority on types either way.
function isErased(?ReflectionType $type): bool
{
	return $type === null || in_array(strtolower((string) $type), ['object', '?object', 'mixed'], true);
}

/**
 * A native class name means its twin (across all shadowed pairs — a native
 * TrinaryLogic parameter type is equivalent to PHPStan\TrinaryLogic), and
 * self/static mean the pair's own class on either side.
 *
 * @param array<string, string> $nativeToTwin
 */
function normalizeType(?ReflectionType $type, array $nativeToTwin, string $selfClass): string
{
	$s = strtolower((string) $type);
	$s = preg_replace('~(^|\||&|\?)(self|static)($|\||&)~', '$1' . $selfClass . '$3', $s);

	return strtr($s, $nativeToTwin);
}

$nativeToTwin = [];
foreach ($manifest as $twinClass => $entry) {
	$nativeToTwin[strtolower('PHPStanTurbo\\' . basename($entry['cpp'], '.cpp'))] = strtolower($twinClass);
}

function visibility(ReflectionMethod $m): string
{
	return $m->isPrivate() ? 'private' : ($m->isProtected() ? 'protected' : 'public');
}

$failed = false;
$compared = 0;

foreach ($manifest as $twinClass => $entry) {
	$nativeClass = 'PHPStanTurbo\\' . basename($entry['cpp'], '.cpp');
	$twin = new ReflectionClass($twinClass);
	$native = new ReflectionClass($nativeClass);

	$problems = [];

	// the manifest must point at the file the class actually lives in
	// (bin/side-by-side.php parses that file's source as the PHP side)
	// normalized to forward slashes: the manifest stores portable paths
	$actualFile = str_replace(DIRECTORY_SEPARATOR, '/', substr(realpath($twin->getFileName()), strlen(realpath($root)) + 1));
	if ($actualFile !== $entry['php']) {
		$problems[] = sprintf('lives in %s, but the manifest says %s — regenerate with composer dump-autoload', $actualFile, $entry['php']);
	}
	if (($entry['vendored'] ?? false) !== str_starts_with($actualFile, 'vendor/')) {
		$problems[] = sprintf('the manifest "vendored" flag does not match the class location %s', $actualFile);
	}
	foreach ($native->getMethods() as $nativeMethod) {
		$name = $nativeMethod->getName();
		if (!$twin->hasMethod($name)) {
			continue; // orphan — side-by-side.php --check reports it
		}
		$twinMethod = $twin->getMethod($name);
		$compared++;

		if (visibility($nativeMethod) !== visibility($twinMethod)) {
			$problems[] = sprintf('%s(): %s natively, %s in PHP', $name, visibility($nativeMethod), visibility($twinMethod));
		}
		if ($nativeMethod->isStatic() !== $twinMethod->isStatic()) {
			$problems[] = sprintf('%s(): static-ness differs', $name);
		}

		$nativeParams = $nativeMethod->getParameters();
		$twinParams = $twinMethod->getParameters();
		if (count($nativeParams) !== count($twinParams)
			|| $nativeMethod->getNumberOfRequiredParameters() !== $twinMethod->getNumberOfRequiredParameters()
		) {
			$problems[] = sprintf(
				'%s(): %d params (%d required) natively, %d (%d required) in PHP',
				$name,
				count($nativeParams),
				$nativeMethod->getNumberOfRequiredParameters(),
				count($twinParams),
				$twinMethod->getNumberOfRequiredParameters(),
			);
		} else {
			foreach ($nativeParams as $i => $nativeParam) {
				$twinParam = $twinParams[$i];
				if ($nativeParam->getName() !== $twinParam->getName()) {
					$problems[] = sprintf('%s(): parameter #%d is $%s natively, $%s in PHP — breaks named arguments', $name, $i + 1, $nativeParam->getName(), $twinParam->getName());
				}
				if ($nativeParam->isPassedByReference() !== $twinParam->isPassedByReference()) {
					$problems[] = sprintf('%s($%s): by-ref differs', $name, $twinParam->getName());
				}
				if ($nativeParam->isVariadic() !== $twinParam->isVariadic()) {
					$problems[] = sprintf('%s($%s): variadic differs', $name, $twinParam->getName());
				}
				if (!isErased($nativeParam->getType())) {
					$nativeType = normalizeType($nativeParam->getType(), $nativeToTwin, strtolower($twinClass));
					$twinType = normalizeType($twinParam->getType(), $nativeToTwin, strtolower($twinClass));
					if ($nativeType !== $twinType) {
						$problems[] = sprintf('%s($%s): type "%s" natively, "%s" in PHP', $name, $twinParam->getName(), $nativeType, $twinType);
					}
				}
			}
		}

		$nativeReturnType = $nativeMethod->getReturnType() ?? $nativeMethod->getTentativeReturnType();
		if (!isErased($nativeReturnType)) {
			$nativeReturn = normalizeType($nativeReturnType, $nativeToTwin, strtolower($twinClass));
			$twinReturn = normalizeType($twinMethod->getReturnType() ?? $twinMethod->getTentativeReturnType(), $nativeToTwin, strtolower($twinClass));
			if ($nativeReturn !== $twinReturn) {
				$problems[] = sprintf('%s(): returns "%s" natively, "%s" in PHP', $name, $nativeReturn, $twinReturn);
			}
		}
	}

	if ($problems === []) {
		printf("✓ %s\n", $twinClass);
		continue;
	}
	$failed = true;
	foreach ($problems as $problem) {
		printf("✗ %s::%s\n", $twinClass, $problem);
	}
}

printf($failed ? "FAILED\n" : "OK (%d methods compared)\n", $compared);
exit($failed ? 1 : 0);

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
 * The enabler is deliberately NOT run: the native classes are declared as
 * PHPStanTurbo\* next to the original PHP classes (tests/activate-prefixed.php).
 */

$root = dirname(__DIR__, 2);
chdir($root);

['manifest' => $manifest] = require __DIR__ . '/activate-prefixed.php';

// Native arginfo deliberately erases most types to none or object: baking
// class-name strings into the binary would couple it to userland names, and
// engine-level type checks cost per call.
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
	$nativeToTwin[strtolower($entry['turboClass'])] = strtolower($twinClass);
}

function visibility(ReflectionMethod $m): string
{
	return $m->isPrivate() ? 'private' : ($m->isProtected() ? 'protected' : 'public');
}

$failed = false;
$compared = 0;

foreach ($manifest as $twinClass => $entry) {
	$nativeClass = $entry['turboClass'];
	$twin = new ReflectionClass($twinClass);
	$native = new ReflectionClass($nativeClass);

	$problems = [];

	// the class-level shape the native declaration must repeat: what the
	// twin declares is what the shadowing class is linked with at activation
	if ($native->isFinal() !== $twin->isFinal()) {
		$problems[] = sprintf('is %s natively, %s in PHP', $native->isFinal() ? 'final' : 'not final', $twin->isFinal() ? 'final' : 'not final');
	}
	$nativeParent = $native->getParentClass();
	$twinParent = $twin->getParentClass();
	$nativeParentName = $nativeParent === false ? null : strtr(strtolower($nativeParent->getName()), $nativeToTwin);
	if ($nativeParentName !== ($twinParent === false ? null : strtolower($twinParent->getName()))) {
		$problems[] = sprintf('extends %s natively, %s in PHP', $nativeParent === false ? 'nothing' : $nativeParent->getName(), $twinParent === false ? 'nothing' : $twinParent->getName());
	}
	$nativeInterfaces = array_map('strtolower', $native->getInterfaceNames());
	$twinInterfaces = array_map('strtolower', $twin->getInterfaceNames());
	sort($nativeInterfaces);
	sort($twinInterfaces);
	if ($nativeInterfaces !== $twinInterfaces) {
		$problems[] = sprintf('implements [%s] natively, [%s] in PHP', implode(', ', $native->getInterfaceNames()), implode(', ', $twin->getInterfaceNames()));
	}
	if (!array_key_exists('final', $entry) || !array_key_exists('parent', $entry)
		|| $entry['final'] !== $twin->isFinal()
		|| $entry['parent'] !== ($twinParent === false ? null : $twinParent->getName())
	) {
		$problems[] = 'the manifest final/parent entries do not match the class — regenerate with composer dump-autoload';
	}

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

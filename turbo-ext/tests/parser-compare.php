<?php declare(strict_types = 1);

/**
 * The comparison shared by the parser differential tests (parser-corpus.php,
 * parser-upstream-corpus.php): one input parsed with both
 * PHPStanTurbo\ParserRunner (native) and $parser->parse() (PHP), requiring
 * byte-identical serialized ASTs, identical collected errors and thrown
 * exceptions, and identical token counts.
 */

function summarizeErrors(PhpParser\ErrorHandler\Collecting $handler): string
{
	$out = [];
	foreach ($handler->getErrors() as $error) {
		$out[] = $error->getRawMessage() . '|' . var_export($error->getAttributes(), true);
	}

	return implode("\n", $out);
}

/**
 * Parses $code with both engines; returns the list of divergences and whether
 * the (identical-on-both-sides) run collected parse errors.
 *
 * @return array{list<string>, bool}
 */
function compareParse(string $code, PhpParser\Parser\Php8 $parserForNative, PhpParser\Parser\Php8 $parserForPhp): array
{
	$nativeHandler = new PhpParser\ErrorHandler\Collecting();
	$phpHandler = new PhpParser\ErrorHandler\Collecting();

	$nativeThrew = null;
	$phpThrew = null;
	$nativeAst = null;
	$phpAst = null;
	try {
		$nativeAst = PHPStanTurbo\ParserRunner::parse($parserForNative, $code, $nativeHandler);
	} catch (Throwable $e) {
		$nativeThrew = get_class($e) . ': ' . $e->getMessage();
	}
	$nativeTokens = count($parserForNative->getTokens());
	try {
		$phpAst = $parserForPhp->parse($code, $phpHandler);
	} catch (Throwable $e) {
		$phpThrew = get_class($e) . ': ' . $e->getMessage();
	}
	$phpTokens = count($parserForPhp->getTokens());

	$problems = [];
	if ($nativeThrew !== $phpThrew) {
		$problems[] = sprintf('throw mismatch: native=%s php=%s', $nativeThrew ?? '-', $phpThrew ?? '-');
	}
	if ($nativeTokens !== $phpTokens) {
		$problems[] = sprintf('token count mismatch: native=%d php=%d', $nativeTokens, $phpTokens);
	}
	$nativeErrors = summarizeErrors($nativeHandler);
	$phpErrors = summarizeErrors($phpHandler);
	if ($nativeErrors !== $phpErrors) {
		$problems[] = sprintf("errors mismatch:\n--- native ---\n%s\n--- php ---\n%s", $nativeErrors, $phpErrors);
	}
	if ($problems === []) {
		$nativeSer = $nativeAst === null ? 'NULL' : serialize($nativeAst);
		$phpSer = $phpAst === null ? 'NULL' : serialize($phpAst);
		if ($nativeSer !== $phpSer) {
			// find the first differing offset for the report
			$len = min(strlen($nativeSer), strlen($phpSer));
			$at = 0;
			while ($at < $len && $nativeSer[$at] === $phpSer[$at]) {
				$at++;
			}
			$problems[] = sprintf(
				"AST mismatch at byte %d:\n  native: …%s…\n  php:    …%s…",
				$at,
				substr($nativeSer, max(0, $at - 60), 160),
				substr($phpSer, max(0, $at - 60), 160),
			);
		}
	}

	return [$problems, $phpErrors !== ''];
}

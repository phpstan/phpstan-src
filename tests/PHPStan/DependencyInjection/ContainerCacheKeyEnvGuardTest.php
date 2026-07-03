<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use function count;
use function file_get_contents;
use function implode;
use function in_array;
use function is_array;
use function preg_match;
use function sort;
use function str_contains;
use function strtolower;
use function substr;
use function token_get_all;
use const PHP_EOL;
use const T_COMMENT;
use const T_CONSTANT_ENCAPSED_STRING;
use const T_DOC_COMMENT;
use const T_DOUBLE_COLON;
use const T_OBJECT_OPERATOR;
use const T_STRING;
use const T_VARIABLE;
use const T_WHITESPACE;

/**
 * Guards the container-cache-key narrowing in Configurator (phpstan/phpstan#14072): when no config
 * references %env.*%, env vars are dropped from the cache key, so a CompilerExtension reading one at
 * *build time* (getenv() / $_ENV) would make the container depend on a variable the key no longer
 * tracks, and changing it would silently reuse a stale container. There is no such read today; a new
 * one fails this test instead of re-opening that hole.
 *
 * Reads are found by tokenizing the source rather than scanning raw text, so a getenv() mentioned in
 * a comment or inside a string literal is ignored (the same reason the cache-key enumeration parses
 * configs rather than grepping them). Documented limits, acceptable because PHPStan's build-time env
 * access goes through these patterns: literal env-var names only (route dynamic ones via %env.*),
 * getenv() / $_ENV reads (not $_SERVER), and classes that directly extend CompilerExtension.
 */
final class ContainerCacheKeyEnvGuardTest extends TestCase
{

	public function testBuildTimeEnvReadsInCompilerExtensionsAreDeclared(): void
	{
		$srcDir = __DIR__ . '/../../../src';
		$offenders = [];

		/** @var SplFileInfo $file */
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
			if (!$file->isFile() || $file->getExtension() !== 'php') {
				continue;
			}

			$contents = file_get_contents($file->getPathname());
			if ($contents === false || !str_contains($contents, 'extends CompilerExtension')) {
				continue;
			}

			foreach ($this->buildTimeEnvReads($contents) as $envName) {
				$offenders[] = $file->getFilename() . ': ' . $envName;
			}
		}

		sort($offenders);

		$this->assertSame(
			[],
			$offenders,
			'A CompilerExtension reads an environment variable at build time, so the compiled container '
			. 'depends on it while the cache key no longer does - changing it would reuse a stale '
			. 'container. Read it via %env.* in a config instead, or put its name back into the key in '
			. 'Configurator::relevantEnvVariableNamesForCacheKey():' . PHP_EOL
			. implode(PHP_EOL, $offenders),
		);
	}

	/**
	 * Literal env-var names read via getenv('NAME') or $_ENV['NAME'], found through the tokenizer so
	 * that occurrences in comments or string literals are not matched.
	 *
	 * @return list<string>
	 */
	private function buildTimeEnvReads(string $contents): array
	{
		$tokens = token_get_all($contents);
		$names = [];

		for ($i = 0, $count = count($tokens); $i < $count; $i++) {
			$token = $tokens[$i];
			if (!is_array($token)) {
				continue;
			}

			if ($token[0] === T_STRING && strtolower($token[1]) === 'getenv') {
				$previous = $this->previousSignificantTokenId($tokens, $i);
				if (in_array($previous, [T_OBJECT_OPERATOR, T_DOUBLE_COLON], true)) {
					continue; // a method or static call, not the global getenv()
				}

				$name = $this->literalStringArgument($tokens, $i, '(');
			} elseif ($token[0] === T_VARIABLE && $token[1] === '$_ENV') {
				$name = $this->literalStringArgument($tokens, $i, '[');
			} else {
				continue;
			}

			if ($name === null) {
				continue;
			}

			$names[] = $name;
		}

		return $names;
	}

	/**
	 * The literal name in `<opener> 'NAME'` that follows token $index (skipping whitespace and
	 * comments), or null when the first argument is not a plain literal, which means a dynamic name
	 * that has to be routed through %env.* anyway.
	 *
	 * @param list<string|list{int, string, int}> $tokens
	 */
	private function literalStringArgument(array $tokens, int $index, string $opener): ?string
	{
		$openerIndex = $this->nextSignificantTokenIndex($tokens, $index + 1);
		if ($openerIndex === null || $tokens[$openerIndex] !== $opener) {
			return null;
		}

		$argumentIndex = $this->nextSignificantTokenIndex($tokens, $openerIndex + 1);
		if ($argumentIndex === null) {
			return null;
		}

		$argument = $tokens[$argumentIndex];
		if (!is_array($argument) || $argument[0] !== T_CONSTANT_ENCAPSED_STRING) {
			return null;
		}

		$name = substr($argument[1], 1, -1);
		if (preg_match('~^[A-Za-z_][A-Za-z0-9_]*$~', $name) !== 1) {
			return null;
		}

		return $name;
	}

	/**
	 * @param list<string|list{int, string, int}> $tokens
	 */
	private function nextSignificantTokenIndex(array $tokens, int $from): ?int
	{
		for ($i = $from, $count = count($tokens); $i < $count; $i++) {
			$token = $tokens[$i];
			if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
				continue;
			}

			return $i;
		}

		return null;
	}

	/**
	 * @param list<string|list{int, string, int}> $tokens
	 */
	private function previousSignificantTokenId(array $tokens, int $index): ?int
	{
		for ($i = $index - 1; $i >= 0; $i--) {
			$token = $tokens[$i];
			if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
				continue;
			}

			return is_array($token) ? $token[0] : null;
		}

		return null;
	}

}

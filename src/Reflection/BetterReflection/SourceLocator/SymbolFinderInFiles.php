<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\DependencyInjection\AutowiredService;
use function array_filter;
use function array_slice;
use function count;
use function end;
use function explode;
use function implode;
use function in_array;
use function ltrim;
use function php_strip_whitespace;
use function preg_match_all;
use function preg_replace;
use function sprintf;
use function str_contains;
use function strtolower;

#[AutowiredService]
final class SymbolFinderInFiles
{

	public function __construct(private PhpFileCleaner $cleaner)
	{
	}

	/**
	 * @param string[] $files
	 * @return array<string, array{string[], string[], string[]}>
	 */
	public function findSymbols(array $files, bool $supportsEnums): array
	{
		$result = [];
		foreach ($files as $file) {
			$result[$file] = $this->findSymbolsInFile($file, $supportsEnums);
		}

		return $result;
	}

	/**
	 * Inspired by Composer\Autoload\ClassMapGenerator::findClasses()
	 * @link https://github.com/composer/composer/blob/45d3e133a4691eccb12e9cd6f9dfd76eddc1906d/src/Composer/Autoload/ClassMapGenerator.php#L216
	 *
	 * @return array{string[], string[], string[]}
	 */
	private function findSymbolsInFile(string $file, bool $supportsEnums): array
	{
		$contents = @php_strip_whitespace($file);
		if ($contents === '') {
			return [[], [], []];
		}

		$extraTypes = $supportsEnums ? '|enum' : '';
		$matchResults = (bool) preg_match_all(sprintf('{\b(?:(?:class|interface|trait|const|function%s)\s)|(?:define\s*\()}i', $extraTypes), $contents, $matches);
		if (!$matchResults) {
			return [[], [], []];
		}

		$contents = $this->cleaner->clean($contents, count($matches[0]));

		preg_match_all(sprintf('{
			(?:
				\b(?<![\$:>])(?:
					(?: (?P<type>class|interface|trait%s) \s++ (?P<name>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\-]*+) )
					| (?: (?P<function>function) \s++ (?:&\s*)? (?P<fname>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\-]*+) \s*+ [&\(] )
					| (?: (?P<constant>const) \s++ (?P<cname>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\-]*+) \s*+ [^;] )
					| (?: (?:\\\)? (?P<define>define) \s*+ \( \s*+ [\'"] (?P<dname>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+(?:[\\\\]{1,2}[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+)*+) )
					| (?: (?P<ns>namespace) (?P<nsname>\s++[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+(?:\s*+\\\\\s*+[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+)*+)? \s*+ [\{;] )
				)
			)
		}ix', $extraTypes), $contents, $matches);

		$classes = [];
		$functions = [];
		$constants = [];
		$namespace = '';

		for ($i = 0, $len = count($matches['type']); $i < $len; $i++) {
			if (isset($matches['ns'][$i]) && $matches['ns'][$i] !== '') {
				$namespace = preg_replace('~\s+~', '', strtolower($matches['nsname'][$i])) . '\\';
				continue;
			}

			if ($matches['function'][$i] !== '') {
				$functions[] = strtolower(ltrim($namespace . $matches['fname'][$i], '\\'));
				continue;
			}

			if ($matches['constant'][$i] !== '') {
				$constants[] = self::normalizeConstantName(ltrim($namespace . $matches['cname'][$i], '\\'));
			}

			if ($matches['define'][$i] !== '') {
				$constants[] = self::normalizeConstantName($matches['dname'][$i]);
				continue;
			}

			$name = $matches['name'][$i];

			// skip anon classes extending/implementing
			if (in_array($name, ['extends', 'implements'], true)) {
				continue;
			}

			$classes[] = strtolower(ltrim($namespace . $name, '\\'));
		}

		return [
			$classes,
			$functions,
			$constants,
		];
	}

	private static function normalizeConstantName(string $name): string
	{
		if (!str_contains($name, '\\')) {
			return $name;
		}

		$nameParts = array_filter(explode('\\', $name), static fn ($part) => $part !== '');
		return strtolower(implode('\\', array_slice($nameParts, 0, -1))) . '\\' . end($nameParts);
	}

}

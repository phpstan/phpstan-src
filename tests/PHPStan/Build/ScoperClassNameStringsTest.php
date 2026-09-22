<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use function file_get_contents;
use function implode;
use function is_array;
use function preg_match;
use function sprintf;
use function str_replace;
use function str_starts_with;
use function strpos;
use function strtolower;
use function substr;
use function token_get_all;
use const T_CONSTANT_ENCAPSED_STRING;

/**
 * Class-name string literals in src/ - `new ObjectType('BcMath\Number')` and
 * friends - name classes of the analysed code. php-scoper prefixes every string
 * that looks like a fully qualified class name, so unless the namespace is
 * either excluded from prefixing altogether or listed among the namespaces the
 * patcher in compiler/build/scoper.inc.php un-prefixes again, the phar ends up
 * reporting class names like `_PHPStan_abcdef\Filter\FilterFailedException`.
 */
final class ScoperClassNameStringsTest extends TestCase
{

	public function testClassNameStringsAreNotPrefixedInPhar(): void
	{
		/** @var array{excluded: list<string>, unprefixedClassNameStringsInSrc: list<string>} $namespaces */
		$namespaces = require __DIR__ . '/../../../compiler/build/scoper-namespaces.php';

		$finder = new Finder();
		$finder->followLinks();
		foreach ($finder->files()->name('*.php')->in(__DIR__ . '/../../../src') as $fileInfo) {
			$file = $fileInfo->getPathname();
			$code = file_get_contents($file);
			if ($code === false) {
				self::fail(sprintf('Could not read %s', $file));
			}

			foreach (token_get_all($code) as $token) {
				if (!is_array($token) || $token[0] !== T_CONSTANT_ENCAPSED_STRING) {
					continue;
				}

				$className = self::parseStringLiteral($token[1]);
				if (preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*(\\\\[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)+$/', $className) !== 1) {
					continue;
				}

				if (
					self::belongsToNamespace($className, $namespaces['excluded'])
					|| self::belongsToNamespace($className, $namespaces['unprefixedClassNameStringsInSrc'])
				) {
					continue;
				}

				self::fail(sprintf(
					"%s on line %d contains the class name string '%s'. php-scoper prefixes such strings in the phar, "
					. "so its root namespace has to be added to 'unprefixedClassNameStringsInSrc' in compiler/build/scoper-namespaces.php. "
					. 'Namespaces currently handled: %s.',
					$file,
					$token[2],
					$className,
					implode(', ', $namespaces['unprefixedClassNameStringsInSrc']),
				));
			}
		}

		self::expectNotToPerformAssertions();
	}

	private static function parseStringLiteral(string $token): string
	{
		$contents = substr($token, 1, -1);
		if ($token[0] === '\'') {
			return str_replace(['\\\\', '\\\''], ['\\', '\''], $contents);
		}

		// double-quoted strings with any escape sequence besides \\ cannot be class names
		$contents = str_replace('\\\\', "\0", $contents);
		if (strpos($contents, '\\') !== false) {
			return '';
		}

		return str_replace("\0", '\\', $contents);
	}

	/**
	 * @param list<string> $namespaces
	 */
	private static function belongsToNamespace(string $className, array $namespaces): bool
	{
		foreach ($namespaces as $namespace) {
			// namespaces are case-insensitive in PHP and so is php-scoper's matching
			if (str_starts_with(strtolower($className), strtolower($namespace) . '\\')) {
				return true;
			}
		}

		return false;
	}

}

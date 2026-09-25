<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use function file_get_contents;
use function in_array;
use function realpath;
use function sprintf;
use function str_contains;
use function str_replace;
use function strlen;
use function substr;

/**
 * Code in src/ and bin/ that recognises the analysed project's autoloader - an
 * `instanceof ClassLoader` in AutoloadSourceLocator, say - has to name the
 * unprefixed Composer\Autoload\ClassLoader. php-scoper prefixes the reference in
 * the phar, where it then names the phar's own copy and never matches the
 * project's autoloader, unless a patcher in compiler/build/scoper.inc.php
 * strips the prefix back off in that file.
 */
final class ScoperComposerClassLoaderTest extends TestCase
{

	public function testClassLoaderReferencesAreNotPrefixedInPhar(): void
	{
		/** @var array{unprefixedComposerClassLoaderIn: list<string>} $namespaces */
		$namespaces = require __DIR__ . '/../../../compiler/build/scoper-namespaces.php';

		$root = realpath(__DIR__ . '/../../..');
		if ($root === false) {
			self::fail('Could not resolve the repository root.');
		}

		$files = [$root . '/bin/phpstan'];
		$finder = new Finder();
		$finder->followLinks();
		foreach ($finder->files()->name('*.php')->in($root . '/src') as $fileInfo) {
			$files[] = $fileInfo->getPathname();
		}

		foreach ($files as $file) {
			$code = file_get_contents($file);
			if ($code === false) {
				self::fail(sprintf('Could not read %s', $file));
			}

			if (!str_contains($code, 'Composer\Autoload\ClassLoader')) {
				continue;
			}

			$relativePath = str_replace('\\', '/', substr($file, strlen($root) + 1));
			if (in_array($relativePath, $namespaces['unprefixedComposerClassLoaderIn'], true)) {
				continue;
			}

			self::fail(sprintf(
				'%s refers to Composer\\Autoload\\ClassLoader. php-scoper prefixes the reference in the phar, '
				. "where it no longer matches the analysed project's autoloader, so the file has to be added to "
				. "'unprefixedComposerClassLoaderIn' in compiler/build/scoper-namespaces.php.",
				$relativePath,
			));
		}

		self::expectNotToPerformAssertions();
	}

}

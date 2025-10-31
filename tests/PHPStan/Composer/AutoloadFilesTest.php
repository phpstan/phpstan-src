<?php declare(strict_types = 1);

namespace PHPStan\Composer;

use Nette\Utils\Json;
use PHPStan\File\FileHelper;
use PHPStan\File\FileReader;
use PHPStan\ShouldNotHappenException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use function array_map;
use function dirname;
use function realpath;
use function sort;
use function strlen;
use function strpos;
use function substr;
use const DIRECTORY_SEPARATOR;

class AutoloadFilesTest extends TestCase
{

	public function testExpectedFiles(): void
	{
		$finder = new Finder();
		$finder->followLinks();
		$autoloadFiles = [];
		$vendorPath = realpath(__DIR__ . '/../../../vendor');
		if ($vendorPath === false) {
			throw new ShouldNotHappenException();
		}

		$fileHelper = new FileHelper(__DIR__);

		foreach ($finder->files()->name('composer.json')->in(__DIR__ . '/../../../vendor') as $fileInfo) {
			$realpath = $fileInfo->getRealPath();
			if ($realpath === false) {
				throw new ShouldNotHappenException();
			}
			$json = Json::decode(FileReader::read($realpath), Json::FORCE_ARRAY);
			if (!isset($json['autoload']['files'])) {
				continue;
			}

			foreach ($json['autoload']['files'] as $file) {
				$autoloadFile = substr(dirname($realpath) . DIRECTORY_SEPARATOR . $file, strlen($vendorPath) + 1);
				if (strpos($autoloadFile, 'rector' . DIRECTORY_SEPARATOR . 'rector' . DIRECTORY_SEPARATOR) === 0) {
					continue;
				}
				$autoloadFiles[] = $fileHelper->normalizePath($autoloadFile);
			}
		}

		sort($autoloadFiles);

		$expectedFiles = [
			'hoa/consistency/Prelude.php', // Hoa isn't prefixed, no need to load this eagerly
			'hoa/protocol/Wrapper.php', // Hoa isn't prefixed, no need to load this eagerly
			'jetbrains/phpstorm-stubs/PhpStormStubsMap.php', // added to phpstan-dist/bootstrap.php
			'myclabs/deep-copy/src/DeepCopy/deep_copy.php', // dev dependency of PHPUnit
			'react/async/src/functions_include.php', // added to phpstan-dist/bootstrap.php
			'react/promise/src/functions_include.php', // added to phpstan-dist/bootstrap.php
			'phpunit/phpunit/src/Framework/Assert/Functions.php',
			'symfony/deprecation-contracts/function.php', // afaik polyfills aren't necessary
			'symfony/polyfill-ctype/bootstrap.php', // afaik polyfills aren't necessary
			'symfony/polyfill-intl-grapheme/bootstrap.php', // afaik polyfills aren't necessary
			'symfony/polyfill-intl-normalizer/bootstrap.php', // afaik polyfills aren't necessary
			'symfony/polyfill-mbstring/bootstrap.php', // afaik polyfills aren't necessary
			'symfony/polyfill-php80/bootstrap.php', // afaik polyfills aren't necessary
			'symfony/polyfill-php81/bootstrap.php', // afaik polyfills aren't necessary
			'symfony/polyfill-php83/bootstrap.php', // afaik polyfills aren't necessary
			'symfony/polyfill-php84/bootstrap.php', // afaik polyfills aren't necessary
			'symfony/polyfill-php85/bootstrap.php', // afaik polyfills aren't necessary
			'symfony/string/Resources/functions.php', // afaik polyfills aren't necessary
		];

		$expectedFiles = array_map(static fn (string $path): string => $fileHelper->normalizePath($path), $expectedFiles);
		sort($expectedFiles);

		$this->assertSame($expectedFiles, $autoloadFiles);
	}

}

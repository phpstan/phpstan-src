<?php declare(strict_types = 1);

namespace PHPStan\Composer;

use Nette\Utils\Json;
use PHPStan\File\FileHelper;
use PHPStan\File\FileReader;
use PHPStan\ShouldNotHappenException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use function array_map;
use function array_splice;
use function dirname;
use function realpath;
use function sort;
use function strlen;
use function substr;
use const PHP_VERSION_ID;

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
				$autoloadFile = substr(dirname($realpath) . '/' . $file, strlen($vendorPath) + 1);
				$autoloadFiles[] = $fileHelper->normalizePath($autoloadFile);
			}
		}

		sort($autoloadFiles);

		$expectedFiles = [
			'clue/block-react/src/functions_include.php', // added to phpstan-dist/bootstrap.php
			'hoa/consistency/Prelude.php', // Hoa isn't prefixed, no need to load this eagerly
			'hoa/protocol/Wrapper.php', // Hoa isn't prefixed, no need to load this eagerly
			'jetbrains/phpstorm-stubs/PhpStormStubsMap.php', // added to phpstan-dist/bootstrap.php
			'myclabs/deep-copy/src/DeepCopy/deep_copy.php', // dev dependency of PHPUnit
			'phpstan/php-8-stubs/Php8StubsMap.php',
			'react/promise-stream/src/functions_include.php', // added to phpstan-dist/bootstrap.php
			'react/promise-timer/src/functions_include.php', // added to phpstan-dist/bootstrap.php
			'react/promise/src/functions_include.php', // added to phpstan-dist/bootstrap.php
			'ringcentral/psr7/src/functions_include.php', // added to phpstan-dist/bootstrap.php
			'symfony/polyfill-ctype/bootstrap.php', // afaik polyfills aren't necessary
			'symfony/polyfill-mbstring/bootstrap.php', // afaik polyfills aren't necessary
			'symfony/polyfill-php73/bootstrap.php', // afaik polyfills aren't necessary
			'symfony/polyfill-php80/bootstrap.php', // afaik polyfills aren't necessary
		];

		$phpunitFunctions = 'phpunit/phpunit/src/Framework/Assert/Functions.php';
		if (PHP_VERSION_ID >= 70300) {
			array_splice($expectedFiles, 6, 0, [
				$phpunitFunctions,
			]);
		}

		$expectedFiles = array_map(static function (string $path) use ($fileHelper): string {
			return $fileHelper->normalizePath($path);
		}, $expectedFiles);
		sort($expectedFiles);

		$this->assertSame($expectedFiles, $autoloadFiles);
	}

}

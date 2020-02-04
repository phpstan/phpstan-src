<?php declare(strict_types = 1);

namespace PHPStan\Composer;

use Nette\Utils\Json;
use PHPStan\File\FileReader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

class AutoloadFilesTest extends TestCase
{

	public function testExpectedFiles(): void
	{
		$finder = new Finder();
		$finder->followLinks();
		$autoloadFiles = [];
		$vendorPath = realpath(__DIR__ . '/../../../vendor');
		if ($vendorPath === false) {
			throw new \PHPStan\ShouldNotHappenException();
		}
		foreach ($finder->files()->name('composer.json')->in(__DIR__ . '/../../../vendor') as $fileInfo) {
			$realpath = $fileInfo->getRealPath();
			if ($realpath === false) {
				throw new \PHPStan\ShouldNotHappenException();
			}
			$json = Json::decode(FileReader::read($realpath), Json::FORCE_ARRAY);
			if (!isset($json['autoload']['files'])) {
				continue;
			}

			foreach ($json['autoload']['files'] as $file) {
				$autoloadFiles[] = substr(dirname($realpath) . '/' . $file, strlen($vendorPath) + 1);
			}
		}

		sort($autoloadFiles);

		$this->assertSame([
			'hoa/consistency/Prelude.php', // Hoa isn't prefixed, no need to load this eagerly
			'hoa/protocol/Wrapper.php', // Hoa isn't prefixed, no need to load this eagerly
			'jetbrains/phpstorm-stubs/PhpStormStubsMap.php', // added to bin/phpstan
			'myclabs/deep-copy/src/DeepCopy/deep_copy.php', // dev dependency of PHPUnit
			'react/promise-timer/src/functions_include.php', // added to bin/phpstan
			'react/promise/src/functions_include.php', // added to bin/phpstan
			'symfony/polyfill-ctype/bootstrap.php', // afaik polyfills aren't necessary
			'symfony/polyfill-mbstring/bootstrap.php', // afaik polyfills aren't necessary
			'symfony/polyfill-php73/bootstrap.php', // afaik polyfills aren't necessary
		], $autoloadFiles);
	}

}

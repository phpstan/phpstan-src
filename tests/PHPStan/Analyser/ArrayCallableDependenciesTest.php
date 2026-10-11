<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\File\FileHelper;
use PHPStan\Testing\PHPStanTestCase;

/**
 * The dependency contract of a `[Foo::class, 'method']` literal array: the
 * file using it depends on what calling it returns, so a change of the
 * method's return type re-analyses it. Arrays whose first item is not
 * definitely a class-string add no such dependency.
 */
final class ArrayCallableDependenciesTest extends PHPStanTestCase
{

	public function testCallableArrayDependencies(): void
	{
		$fileHelper = self::getContainer()->getByType(FileHelper::class);
		$dataDir = $fileHelper->normalizePath(__DIR__ . '/data/array-callable-deps');
		$resultFile = $dataDir . '/result.php';
		$definiteFile = $dataDir . '/user-definite.php';
		$maybeFile = $dataDir . '/user-maybe.php';

		$analyser = self::getContainer()->getByType(Analyser::class);
		$analyserResult = $analyser->analyse([
			$definiteFile,
			$maybeFile,
			$dataDir . '/factory.php',
			$resultFile,
		]);

		$dependencies = $analyserResult->getDependencies();
		$this->assertNotNull($dependencies);
		$this->assertArrayHasKey($definiteFile, $dependencies);
		$this->assertArrayHasKey($maybeFile, $dependencies);
		$this->assertContains($resultFile, $dependencies[$definiteFile]);
		$this->assertNotContains($resultFile, $dependencies[$maybeFile]);
	}

}

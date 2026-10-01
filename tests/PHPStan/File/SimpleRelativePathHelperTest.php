<?php declare(strict_types = 1);

namespace PHPStan\File;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SimpleRelativePathHelperTest extends TestCase
{

	public static function dataGetRelativePath(): array
	{
		return [
			[
				'/project',
				'/project/src/HelloWorld.php',
				'src/HelloWorld.php',
			],
			[
				'',
				'/project/src/HelloWorld.php',
				'/project/src/HelloWorld.php',
			],
			[
				'/',
				'/project/src/HelloWorld.php',
				'project/src/HelloWorld.php',
			],
		];
	}

	#[DataProvider('dataGetRelativePath')]
	public function testGetRelativePath(
		string $currentWorkingDirectory,
		string $filename,
		string $expectedRelativePath,
	): void
	{
		$helper = new SimpleRelativePathHelper($currentWorkingDirectory);
		$this->assertSame(
			$expectedRelativePath,
			$helper->getRelativePath($filename),
		);
	}

}

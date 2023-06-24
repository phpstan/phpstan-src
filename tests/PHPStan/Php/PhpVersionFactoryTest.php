<?php declare(strict_types = 1);

namespace PHPStan\Php;

use PHPUnit\Framework\TestCase;
use const PHP_VERSION_ID;

class PhpVersionFactoryTest extends TestCase
{

	public function dataCreate(): array
	{
		return [
			[
				null,
				null,
				PHP_VERSION_ID,
				null,
			],
			[
				70200,
				null,
				70200,
				'7.2',
			],
			[
				70200,
				'7.4.6',
				70200,
				'7.2',
			],
			[
				null,
				'7.4.6',
				70406,
				'7.4.6',
			],
			[
				null,
				'7.0',
				70100,
				'7.1',
			],
			[
				null,
				'7.1.1',
				70101,
				'7.1.1',
			],
			[
				null,
				'5.4.1',
				70100,
				'7.1',
			],
			[
				null,
				'8.1',
				80100,
				'8.1',
			],
			[
				null,
				'8.2',
				80200,
				'8.2',
			],
			[
				null,
				'8.3',
				80300,
				'8.3',
			],
			[
				null,
				'8.4',
				80399,
				'8.3.99',
			],
			[
				null,
				'8.0.95',
				80095,
				'8.0.95',
			],
		];
	}

	/**
	 * @dataProvider dataCreate
	 */
	public function testCreate(
		?int $versionId,
		?string $composerPhpVersion,
		int $expectedVersion,
		?string $expectedVersionString,
	): void
	{
		$factory = new PhpVersionFactory($versionId, $composerPhpVersion);
		$phpVersion = $factory->create();
		$this->assertSame($expectedVersion, $phpVersion->getVersionId());

		if ($expectedVersionString === null) {
			return;
		}

		$this->assertSame($expectedVersionString, $phpVersion->getVersionString());
	}

}

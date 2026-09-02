<?php declare(strict_types = 1);

namespace PHPStan\Collectors;

use PHPStan\Analyser\ResultCache\ResultCacheDependencyExtension;
use PHPStan\ShouldNotHappenException;
use PHPUnit\Framework\TestCase;

class ResultCacheDependencyCollectorTest extends TestCase
{

	public function testCreateDataDoesNotCalculateHash(): void
	{
		$extension = new class implements ResultCacheDependencyExtension {

			public function getKey(): string
			{
				return 'provider';
			}

			public function getHash(string $dependencyKey): string
			{
				throw new ShouldNotHappenException();
			}

		};

		$this->assertSame([
			'extensionKey' => 'provider',
			'dependencyKey' => 'dependency',
		], ResultCacheDependencyCollector::createData($extension, 'dependency'));
	}

	public function testGetNodeTypeCannotBeCalled(): void
	{
		$this->expectException(ShouldNotHappenException::class);

		(new ResultCacheDependencyCollector())->getNodeType();
	}

}

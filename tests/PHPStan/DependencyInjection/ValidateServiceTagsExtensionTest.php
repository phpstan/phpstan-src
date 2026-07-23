<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use PHPUnit\Framework\TestCase;
use function md5;
use function mkdir;
use function sys_get_temp_dir;
use function uniqid;

class ValidateServiceTagsExtensionTest extends TestCase
{

	public function testServiceWithTagMustImplementTheAssociatedInterface(): void
	{
		$tmpDir = sys_get_temp_dir() . '/phpstan-validate-service-tags-' . md5(uniqid(more_entropy: true));
		mkdir($tmpDir, 0777, true);

		$containerFactory = new ContainerFactory(__DIR__);

		$this->expectException(MissingImplementedInterfaceInServiceWithTagException::class);
		$this->expectExceptionMessage('Service of type PHPStan\File\FileHelper with tag phpstan.broker.dynamicMethodReturnTypeExtension does not implement interface PHPStan\Type\DynamicMethodReturnTypeExtension.');
		$containerFactory->create($tmpDir, [__DIR__ . '/validateServiceTags.neon'], []);
	}

}

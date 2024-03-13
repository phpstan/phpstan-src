<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Alias;

use PHPStan\Testing\PHPStanTestCase;

class UserClassAliasProviderTest extends PHPStanTestCase
{

	public function testConstruct(): void {

		$aliasProvider = $this->getContainer()->getByType(ClassAliasProvider::class);
		$this->assertInstanceOf(UserClassAliasProvider::class, $aliasProvider);
		$this->assertTrue($aliasProvider->hasAlias('AliasName'));
		$this->assertEquals('ClassAliasTest\OriginalClass', $aliasProvider->getClassName('AliasName'));
		$this->assertEquals('ClassAliasTest\OriginalClass', $aliasProvider->getClassName('ClassAliasTest\AliasName'));

	}

	public function testResolve(): void {
		$reflectionProvider = $this->createReflectionProvider();

		$classReflection = $reflectionProvider->getClass('AliasName');
		$this->assertEquals('ClassAliasTest\OriginalClass', $classReflection->getName());
		$this->assertTrue($classReflection->hasMethod('doFoo'));

		$namespacedClassReflection = $reflectionProvider->getClass('ClassAliasTest\AliasName');
		$this->assertEquals('ClassAliasTest\OriginalClass', $namespacedClassReflection->getName());

	}

	#[\Override] public static function getAdditionalConfigFiles(): array
	{
		return [__DIR__ . '/data/class-alias.neon'];
	}


}

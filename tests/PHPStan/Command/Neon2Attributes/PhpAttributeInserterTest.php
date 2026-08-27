<?php declare(strict_types = 1);

namespace PHPStan\Command\Neon2Attributes;

use PHPUnit\Framework\TestCase;

class PhpAttributeInserterTest extends TestCase
{

	public function testInsertWithExistingUseBlock(): void
	{
		$content = <<<'PHP'
<?php declare(strict_types = 1);

namespace App;

use App\Dependency;
use ZCorp\Widget;

/**
 * Does things.
 */
final class MyService
{

	public function __construct(
		private Dependency $dependency,
		private string $tmpDir,
	)
	{
	}

}
PHP;

		$conversion = new ServiceConversion(
			'services',
			0,
			'App\MyService',
			'MyService.php',
			'#[AutowiredService]',
			['tmpDir' => '#[AutowiredParameter]'],
			['PHPStan\DependencyInjection\AutowiredService', 'PHPStan\DependencyInjection\AutowiredParameter'],
		);

		$this->assertSame(<<<'PHP'
<?php declare(strict_types = 1);

namespace App;

use App\Dependency;
use ZCorp\Widget;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;

/**
 * Does things.
 */
#[AutowiredService]
final class MyService
{

	public function __construct(
		private Dependency $dependency,
		#[AutowiredParameter]
		private string $tmpDir,
	)
	{
	}

}
PHP, (new PhpAttributeInserter())->insert($content, [$conversion]));
	}

	public function testInsertWithoutUseBlock(): void
	{
		$content = <<<'PHP'
<?php declare(strict_types = 1);

namespace App;

final class MyRule
{

}
PHP;

		$conversion = new ServiceConversion(
			'rules',
			0,
			'App\MyRule',
			'MyRule.php',
			'#[RegisteredRule(level: 0)]',
			[],
			['PHPStan\DependencyInjection\RegisteredRule'],
		);

		$this->assertSame(<<<'PHP'
<?php declare(strict_types = 1);

namespace App;

use PHPStan\DependencyInjection\RegisteredRule;

#[RegisteredRule(level: 0)]
final class MyRule
{

}
PHP, (new PhpAttributeInserter())->insert($content, [$conversion]));
	}

	public function testShortNameConflictFallsBackToFullyQualifiedForm(): void
	{
		$content = <<<'PHP'
<?php declare(strict_types = 1);

namespace App;

use App\Attributes\AutowiredService;

final class MyService
{

}
PHP;

		$conversion = new ServiceConversion(
			'services',
			0,
			'App\MyService',
			'MyService.php',
			'#[AutowiredService]',
			[],
			['PHPStan\DependencyInjection\AutowiredService'],
		);

		$this->assertSame(<<<'PHP'
<?php declare(strict_types = 1);

namespace App;

use App\Attributes\AutowiredService;

#[\PHPStan\DependencyInjection\AutowiredService]
final class MyService
{

}
PHP, (new PhpAttributeInserter())->insert($content, [$conversion]));
	}

	public function testParameterSharingLineAborts(): void
	{
		$content = <<<'PHP'
<?php declare(strict_types = 1);

namespace App;

final class MyService
{

	public function __construct(private string $a, private string $b)
	{
	}

}
PHP;

		$conversion = new ServiceConversion(
			'services',
			0,
			'App\MyService',
			'MyService.php',
			'#[AutowiredService]',
			['b' => '#[AutowiredParameter]'],
			['PHPStan\DependencyInjection\AutowiredService', 'PHPStan\DependencyInjection\AutowiredParameter'],
		);

		$this->expectException(Neon2AttributesException::class);
		$this->expectExceptionMessage('does not start its own line');
		(new PhpAttributeInserter())->insert($content, [$conversion]);
	}

}

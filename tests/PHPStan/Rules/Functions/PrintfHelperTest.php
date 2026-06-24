<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use Override;
use PHPStan\Php\PhpVersion;
use PHPStan\Testing\PHPStanTestCase;
use const PHP_VERSION_ID;

class PrintfHelperTest extends PHPStanTestCase
{

	private PrintfHelper $printf;

	#[Override]
	protected function setUp(): void
	{
		parent::setUp();
		$this->printf = new PrintfHelper(new PhpVersion(PHP_VERSION_ID));
	}

	public function testReturnsNullForInvalidPattern(): void
	{
		$this->assertNull($this->printf->getScanfPlaceholdersCount('%a'));
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Command\Neon2Attributes;

use PHPUnit\Framework\TestCase;

class NeonEditorTest extends TestCase
{

	private const NEON = <<<'NEON'
parameters:
	level: 0

rules:
	- Foo\FirstRule
	- Foo\SecondRule

services:
	-
		class: Foo\FirstService
		arguments:
			- %tmpDir%
	named:
		class: Foo\NamedService
	- Foo\ThirdService
NEON;

	public function testRemoveSomeEntries(): void
	{
		$editor = new NeonEditor();
		$result = $editor->removeEntries(self::NEON, 'rules', [0], 2);
		$result = $editor->removeEntries($result, 'services', [0, 2], 3);

		$this->assertSame(<<<'NEON'
parameters:
	level: 0

rules:
	- Foo\SecondRule

services:
	named:
		class: Foo\NamedService
NEON, $result);
	}

	public function testRemoveWholeSection(): void
	{
		$editor = new NeonEditor();
		$result = $editor->removeEntries(self::NEON, 'rules', [0, 1], 2);

		$this->assertSame(<<<'NEON'
parameters:
	level: 0

services:
	-
		class: Foo\FirstService
		arguments:
			- %tmpDir%
	named:
		class: Foo\NamedService
	- Foo\ThirdService
NEON, $result);
	}

	public function testEntryCountMismatchAborts(): void
	{
		$editor = new NeonEditor();
		$this->expectException(Neon2AttributesException::class);
		$this->expectExceptionMessage('Cannot map the `rules` section onto the file');
		$editor->removeEntries(self::NEON, 'rules', [0], 3);
	}

	public function testAddDirectoriesSection(): void
	{
		$editor = new NeonEditor();
		$result = $editor->addDirectoriesSection("parameters:\n\tlevel: 0\n", ['src']);

		$this->assertSame("attributeServicesDirectories:\n\t- src\n\nparameters:\n\tlevel: 0\n", $result);
	}

	public function testAddDirectoriesToExistingSection(): void
	{
		$editor = new NeonEditor();
		$result = $editor->addDirectoriesSection("attributeServicesDirectories:\n\t- src\n\nparameters:\n\tlevel: 0\n", ['src', 'rules']);

		$this->assertSame("attributeServicesDirectories:\n\t- src\n\t- rules\n\nparameters:\n\tlevel: 0\n", $result);
	}

}

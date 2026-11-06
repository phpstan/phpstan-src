<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PHPStan\Fixable\PhpDoc\PhpDocEditor;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<AddNamesRule>
 */
class AddNamesRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new AddNamesRule(self::getContainer()->getByType(PhpDocEditor::class));
	}

	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/add-names.php', __DIR__ . '/data/add-names.php.fixed');
	}

	public function testFixGlobalNamespace(): void
	{
		$this->fix(__DIR__ . '/data/add-names-global.php', __DIR__ . '/data/add-names-global.php.fixed');
	}

	public function testFixFirstUse(): void
	{
		$this->fix(__DIR__ . '/data/add-names-first-use.php', __DIR__ . '/data/add-names-first-use.php.fixed');
	}

	public function testFixSortedUses(): void
	{
		$this->fix(__DIR__ . '/data/add-names-sorted-uses.php', __DIR__ . '/data/add-names-sorted-uses.php.fixed');
	}

	public function testFixPhpDocOnly(): void
	{
		$this->fix(__DIR__ . '/data/add-names-phpdoc-only.php', __DIR__ . '/data/add-names-phpdoc-only.php.fixed');
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PHPStan\Fixable\PhpDoc\PhpDocEditor;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * With fixer.importNames disabled and fixer.preferSelf enabled.
 *
 * @extends RuleTestCase<AddNamesRule>
 */
class AddNamesFullyQualifiedRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new AddNamesRule(self::getContainer()->getByType(PhpDocEditor::class));
	}

	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/add-names.php', __DIR__ . '/data/add-names-fully-qualified.php.fixed');
	}

	public function testFixGlobalNamespace(): void
	{
		$this->fix(__DIR__ . '/data/add-names-global.php', __DIR__ . '/data/add-names-global-fully-qualified.php.fixed');
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/fully-qualified-names.neon',
		];
	}

}

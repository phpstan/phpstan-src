<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<NestedClassDeclarationRule>
 */
class NestedClassDeclarationRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NestedClassDeclarationRule();
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/nested-class-declaration.php'], [
			[
				'Class NestedClassDeclaration\NestedClass is declared in a method, but class declarations may not be nested.',
				10,
			],
			[
				'Interface NestedClassDeclaration\NestedInterface is declared in a method, but class declarations may not be nested.',
				14,
			],
			[
				'Trait NestedClassDeclaration\NestedTrait is declared in a method, but class declarations may not be nested.',
				18,
			],
			[
				'Enum NestedClassDeclaration\NestedEnum is declared in a method, but class declarations may not be nested.',
				22,
			],
			[
				'Class NestedClassDeclaration\InClosure is declared in a method, but class declarations may not be nested.',
				27,
			],
			[
				'Class NestedClassDeclaration\InTraitMethod is declared in a method, but class declarations may not be nested.',
				62,
			],
		]);
	}

}

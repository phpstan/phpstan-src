<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InvalidInheritDocTagRule>
 */
class InvalidInheritDocTagRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InvalidInheritDocTagRule(
			self::getContainer()->getByType(Lexer::class),
			self::getContainer()->getByType(PhpDocParser::class),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-inherit-doc-tag.php'], [
			[
				'PHPDoc tag {@inheritdoc} on method InvalidInheritDocTag\ChildWithInlineInheritDoc::methodWithoutPhpDoc() refers to a parent method that does not have a PHPDoc.',
				31,
			],
			[
				'PHPDoc tag @inheritdoc on method InvalidInheritDocTag\ChildWithBlockInheritDoc::methodWithoutPhpDoc() refers to a parent method that does not have a PHPDoc.',
				52,
			],
			[
				'PHPDoc tag {@inheritdoc} on method InvalidInheritDocTag\ClassWithoutParent::orphanedInheritDoc() refers to a parent method that does not have a PHPDoc.',
				73,
			],
			[
				'PHPDoc tag @inheritdoc on method InvalidInheritDocTag\ClassWithoutParent::orphanedBlockInheritDoc() refers to a parent method that does not have a PHPDoc.',
				81,
			],
			[
				'PHPDoc tag {@inheritdoc} on method InvalidInheritDocTag\ImplementsInterface::interfaceMethodWithoutPhpDoc() refers to a parent method that does not have a PHPDoc.',
				106,
			],
			[
				'PHPDoc tag {@inheritdoc} on method InvalidInheritDocTag\UsesTraitWithoutPhpDoc::traitMethodWithoutPhpDoc() refers to a parent method that does not have a PHPDoc.',
				216,
			],
			[
				'PHPDoc tag {@inheritdoc} on method InvalidInheritDocTag\IssueExampleChild::f() refers to a parent method that does not have a PHPDoc.',
				254,
			],
			[
				'PHPDoc tag {@inheritdoc} on method InvalidInheritDocTag\ChildOfPrivateParentMethod::privateMethod() refers to a parent method that does not have a PHPDoc.',
				280,
			],
			[
				'PHPDoc tag {@inheritdoc} on method InvalidInheritDocTag\OrphanedInheritDocTrait::orphaned() refers to a parent method that does not have a PHPDoc.',
				293,
			],
		]);
	}

}

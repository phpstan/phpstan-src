<?php declare(strict_types = 1);

namespace PHPStan\Rules\ExprUsedAsString;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ExprUsedAsStringTestRule>
 */
class ExprUsedAsStringRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ExprUsedAsStringTestRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/expr-used-as-string.php'], [
			[
				'Expression used as string: PhpParser\Node\Scalar\String_ in PhpParser\Node\Stmt\Echo_',
				8,
			],
			[
				'Expression used as string: PhpParser\Node\Scalar\String_ in PhpParser\Node\Stmt\Echo_',
				9,
			],
			[
				'Expression used as string: PhpParser\Node\Scalar\String_ in PhpParser\Node\Stmt\Echo_',
				9,
			],
			[
				'Expression used as string: PhpParser\Node\Scalar\String_ in PhpParser\Node\Expr\Print_',
				13,
			],
			[
				'Expression used as string: PhpParser\Node\Expr\Variable in PhpParser\Node\Expr\Cast\String_',
				17,
			],
			[
				'Expression used as string: PhpParser\Node\Scalar\InterpolatedString in PhpParser\Node\Scalar\InterpolatedString',
				26,
			],
			[
				'Expression used as string: PhpParser\Node\Expr\Variable in PhpParser\Node\Expr\AssignOp\Concat',
				30,
			],
			[
				'Expression used as string: PhpParser\Node\Expr\BinaryOp\Concat in PhpParser\Node\Stmt\Echo_',
				36,
			],
			[
				'Expression used as string: PhpParser\Node\Scalar\InterpolatedString in PhpParser\Node\Scalar\InterpolatedString',
				41,
			],
		]);
	}

	public function testInlineHtml(): void
	{
		$this->analyse([__DIR__ . '/data/inline-html.php'], [
			[
				'Expression used as string: PHPStan\Node\Expr\TypeExpr in PhpParser\Node\Stmt\InlineHTML',
				7,
			],
		]);
	}

}

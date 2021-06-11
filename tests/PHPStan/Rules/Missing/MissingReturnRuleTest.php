<?php declare(strict_types = 1);

namespace PHPStan\Rules\Missing;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends \PHPStan\Testing\RuleTestCase<MissingReturnRule>
 */
class MissingReturnRuleTest extends RuleTestCase
{

	/** @var bool */
	private $checkExplicitMixedMissingReturn;

	protected function getRule(): Rule
	{
		return new MissingReturnRule($this->checkExplicitMixedMissingReturn, true);
	}

	public function testRule(): void
	{
		$this->checkExplicitMixedMissingReturn = true;

		$this->analyse([__DIR__ . '/data/missing-return.php'], [
			[
				'Method MissingReturn\Foo::doFoo() should return int but return statement is missing.',
				8,
			],
			[
				'Method MissingReturn\Foo::doBar() should return int but return statement is missing.',
				15,
			],
			[
				'Method MissingReturn\Foo::doBaz() should return int but return statement is missing.',
				21,
			],
			[
				'Method MissingReturn\Foo::doLorem() should return int but return statement is missing.',
				36,
			],
			[
				'Anonymous function should return int but return statement is missing.',
				105,
			],
			[
				'Function MissingReturn\doFoo() should return int but return statement is missing.',
				112,
			],
			[
				'Method MissingReturn\SwitchBranches::doBar() should return int but return statement is missing.',
				146,
			],
			[
				'Method MissingReturn\SwitchBranches::doLorem() should return int but return statement is missing.',
				172,
			],
			[
				'Method MissingReturn\SwitchBranches::doIpsum() should return int but return statement is missing.',
				182,
			],
			[
				'Method MissingReturn\SwitchBranches::doDolor() should return int but return statement is missing.',
				193,
			],
			[
				'Method MissingReturn\TryCatchFinally::doBaz() should return int but return statement is missing.',
				234,
			],
			[
				'Method MissingReturn\TryCatchFinally::doDolor() should return int but return statement is missing.',
				263,
			],
			[
				'Method MissingReturn\ReturnInPhpDoc::doFoo() should return int but return statement is missing.',
				290,
			],
			[
				'Method MissingReturn\FooTemplateMixedType::doFoo() should return T but return statement is missing.',
				321,
			],
			[
				'Method MissingReturn\MissingReturnGenerators::emptyBodyUnspecifiedTReturn() should return Generator but return statement is missing.',
				331,
			],
			[
				'Method MissingReturn\MissingReturnGenerators::emptyBodyUnspecifiedTReturn2() should return Generator<int, int, mixed, mixed> but return statement is missing.',
				344,
			],
			[
				'Method MissingReturn\MissingReturnGenerators::emptyBodySpecifiedTReturn() should return Generator<int, int, int, string> but return statement is missing.',
				360,
			],
			[
				'Method MissingReturn\MissingReturnGenerators::bodySpecifiedTReturn() should return string but return statement is missing.',
				370,
			],
			[
				'Method MissingReturn\NeverReturn::doBaz() should always throw an exception or terminate script execution but doesn\'t do that.',
				473,
			],
			[
				'Method MissingReturn\NeverReturn::doBaz2() should always throw an exception or terminate script execution but doesn\'t do that.',
				481,
			],
		]);
	}

	public function testCheckMissingReturnWithTemplateMixedType(): void
	{
		$this->checkExplicitMixedMissingReturn = false;
		$this->analyse([__DIR__ . '/data/missing-return-template-mixed-type.php'], [
			[
				'Method MissingReturnTemplateMixedType\Foo::doFoo() should return T but return statement is missing.',
				13,
			],
		]);
	}

	public function testBug2875(): void
	{
		$this->checkExplicitMixedMissingReturn = true;
		$this->analyse([__DIR__ . '/data/bug-2875.php'], []);
	}

	public function testMissingMixedReturnInEmptyBody(): void
	{
		$this->checkExplicitMixedMissingReturn = true;
		$this->analyse([__DIR__ . '/data/missing-mixed-return-empty-body.php'], [
			[
				'Method MissingMixedReturnEmptyBody\HelloWorld::doFoo() should return mixed but return statement is missing.',
				11,
			],
		]);
	}

	public function testBug3669(): void
	{
		$this->checkExplicitMixedMissingReturn = true;

		require_once __DIR__ . '/data/bug-3669.php';
		$this->analyse([__DIR__ . '/data/bug-3669.php'], []);
	}

}

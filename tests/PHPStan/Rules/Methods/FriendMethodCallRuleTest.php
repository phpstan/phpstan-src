<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Type\FileTypeMapper;

/**
 * @extends \PHPStan\Testing\RuleTestCase<FriendMethodCallRule>
 */
class FriendMethodCallRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): Rule
	{
		return new FriendMethodCallRule(self::getContainer()->getByType(FileTypeMapper::class));
	}

	public function testFriendMethodCall(): void
	{
		$this->analyse([ __DIR__ . '/data/friend-method-call.php'], [
			[
				'You may not not call FriendMethodCallTest\Foo::friendWithEntireBar() from the global scope as the method lists allowed callers through friends.',
				6,
			],
			[
				'FriendMethodCallTest\Bar::notBaz() may not call FriendMethodCallTest\Foo::friendWithBarBaz() as it is not listed as a friend.',
				34,
			],
			[
				'FriendMethodCallTest\BarBar::baz() may not call FriendMethodCallTest\Foo::friendWithEntireBar() as it is not listed as a friend.',
				44,
			],
			[
				'FriendMethodCallTest\BarBar::baz() may not call FriendMethodCallTest\Foo::friendWithBarBaz() as it is not listed as a friend.',
				45,
			],
			[
				'FriendMethodCallTest\BarBar::notBaz() may not call FriendMethodCallTest\Foo::friendWithEntireBar() as it is not listed as a friend.',
				52,
			],
			[
				'FriendMethodCallTest\BarBar::notBaz() may not call FriendMethodCallTest\Foo::friendWithBarBaz() as it is not listed as a friend.',
				53,
			],
			[
				'FriendMethodCallTest\Qux::smh() may not call FriendMethodCallTest\Foo::friendWithEntireBar() as it is not listed as a friend.',
				63,
			],
			[
				'FriendMethodCallTest\Qux::smh() may not call FriendMethodCallTest\Foo::friendWithBarBaz() as it is not listed as a friend.',
				64,
			],
			[
				'FriendMethodCallTest\SelfFriend::caller() may not call FriendMethodCallTest\SelfFriend::notFriend() as it is not listed as a friend.',
				72,
			],
		]);
	}

}

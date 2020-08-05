<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\Rule;
use PHPStan\Type\FileTypeMapper;

/**
 * @extends \PHPStan\Testing\RuleTestCase<InvalidFriendTagTargetRule>
 */
class InvalidFriendTagTargetRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InvalidFriendTagTargetRule(
			self::getContainer()->getByType(FileTypeMapper::class),
			$this->createReflectionProvider()
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-friends.php'], [
			[
				'Class InvalidFriends\Bar specified by a @friend tag does not exist.',
				11,
			],
			[
				'Method InvalidFriends\Foo::notExists() specified by a @friend tag does not exist.',
				11,
			],
		]);
	}

}

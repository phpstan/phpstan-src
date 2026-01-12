<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Assert;
use function implode;
use function sprintf;

/**
 * @extends RuleTestCase<Rule<Class_>>
 */
class AnonymousClassReflectionTest extends RuleTestCase
{

	/**
	 * @return Rule<Class_>
	 */
	protected function getRule(): Rule
	{
		return new /** @implements Rule<Class_> */ class (self::createReflectionProvider()) implements Rule {

			public function __construct(private ReflectionProvider $reflectionProvider)
			{
			}

			public function getNodeType(): string
			{
				return Class_::class;
			}

			public function processNode(Node $node, Scope $scope): array
			{
				if (!$node->isAnonymous()) {
					return [];
				}

				Assert::assertTrue($node->getAttribute('anonymousClass'));

				$classReflection = $this->reflectionProvider->getAnonymousClassReflection($node, $scope);

				return [
					RuleErrorBuilder::message(sprintf(
						"name: %s\ndisplay name: %s",
						$classReflection->getName(),
						$classReflection->getDisplayName(),
					))->identifier('test.anonymousClassReflection')->build(),
				];
			}

		};
	}

	public function testReflection(): void
	{
		$this->analyse([__DIR__ . '/data/anonymous-classes.php'], [
			[
				implode("\n", [
					'name: AnonymousClasstests/PHPStan/Reflection/data/anonymous-classes.php:5',
					'display name: class@anonymous/tests/PHPStan/Reflection/data/anonymous-classes.php:5',
				]),
				5,
			],
			[
				implode("\n", [
					'name: AnonymousClasstests/PHPStan/Reflection/data/anonymous-classes.php:7:1',
					'display name: class@anonymous/tests/PHPStan/Reflection/data/anonymous-classes.php:7:1',
				]),
				7,
			],
			[
				implode("\n", [
					'name: AnonymousClasstests/PHPStan/Reflection/data/anonymous-classes.php:7:2',
					'display name: class@anonymous/tests/PHPStan/Reflection/data/anonymous-classes.php:7:2',
				]),
				7,
			],
			[
				implode("\n", [
					'name: AnonymousClasstests/PHPStan/Reflection/data/anonymous-classes.php:7:3',
					'display name: class@anonymous/tests/PHPStan/Reflection/data/anonymous-classes.php:7:3',
				]),
				7,
			],
			[
				implode("\n", [
					'name: AnonymousClasstests/PHPStan/Reflection/data/anonymous-classes.php:9:1',
					'display name: class@anonymous/tests/PHPStan/Reflection/data/anonymous-classes.php:9:1',
				]),
				9,
			],
			[
				implode("\n", [
					'name: AnonymousClasstests/PHPStan/Reflection/data/anonymous-classes.php:9:2',
					'display name: class@anonymous/tests/PHPStan/Reflection/data/anonymous-classes.php:9:2',
				]),
				9,
			],
			[
				implode("\n", [
					'name: AnonymousClasstests/PHPStan/Reflection/data/anonymous-classes.php:17:1',
					'display name: AnonymousClassReflectionTest\A@anonymous/tests/PHPStan/Reflection/data/anonymous-classes.php:17:1',
				]),
				17,
			],
			[
				implode("\n", [
					'name: AnonymousClasstests/PHPStan/Reflection/data/anonymous-classes.php:17:2',
					'display name: AnonymousClassReflectionTest\A@anonymous/tests/PHPStan/Reflection/data/anonymous-classes.php:17:2',
				]),
				17,
			],
			[
				implode("\n", [
					'name: AnonymousClasstests/PHPStan/Reflection/data/anonymous-classes.php:19:1',
					'display name: AnonymousClassReflectionTest\A@anonymous/tests/PHPStan/Reflection/data/anonymous-classes.php:19:1',
				]),
				19,
			],
			[
				implode("\n", [
					'name: AnonymousClasstests/PHPStan/Reflection/data/anonymous-classes.php:19:2',
					'display name: AnonymousClassReflectionTest\A@anonymous/tests/PHPStan/Reflection/data/anonymous-classes.php:19:2',
				]),
				19,
			],
			[
				implode("\n", [
					'name: AnonymousClasstests/PHPStan/Reflection/data/anonymous-classes.php:29',
					'display name: AnonymousClassReflectionTest\U@anonymous/tests/PHPStan/Reflection/data/anonymous-classes.php:29',
				]),
				29,
			],
			[
				implode("\n", [
					'name: AnonymousClasstests/PHPStan/Reflection/data/anonymous-classes.php:31',
					'display name: AnonymousClassReflectionTest\U@anonymous/tests/PHPStan/Reflection/data/anonymous-classes.php:31',
				]),
				31,
			],
			[
				implode("\n", [
					'name: AnonymousClasstests/PHPStan/Reflection/data/anonymous-classes.php:33',
					'display name: AnonymousClassReflectionTest\V@anonymous/tests/PHPStan/Reflection/data/anonymous-classes.php:33',
				]),
				33,
			],
		]);
	}

}

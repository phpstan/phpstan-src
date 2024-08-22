<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Testing\RuleTestCase;
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
					'name: AnonymousClass0c307d7b8501323d1d30b0afea7e0578',
					'display name: class@anonymous/tests/PHPStan/Reflection/data/anonymous-classes.php:5',
				]),
				5,
			],
			[
				implode("\n", [
					'name: AnonymousClassa16017c480192f8fbf3c03e17840e99c',
					'display name: class@anonymous/tests/PHPStan/Reflection/data/anonymous-classes.php:7:1',
				]),
				7,
			],
			[
				implode("\n", [
					'name: AnonymousClassd68d75f1cdac379350e3027c09a7c5a0',
					'display name: class@anonymous/tests/PHPStan/Reflection/data/anonymous-classes.php:7:2',
				]),
				7,
			],
			[
				implode("\n", [
					'name: AnonymousClass75aa798fed4f30306c14dcf03a50878c',
					'display name: class@anonymous/tests/PHPStan/Reflection/data/anonymous-classes.php:7:3',
				]),
				7,
			],
			[
				implode("\n", [
					'name: AnonymousClass4fcabdc52bfed5f8c101f3f89b2180bd',
					'display name: class@anonymous/tests/PHPStan/Reflection/data/anonymous-classes.php:9:1',
				]),
				9,
			],
			[
				implode("\n", [
					'name: AnonymousClass0e77d7995f4c47dcd5402817970fd7e0',
					'display name: class@anonymous/tests/PHPStan/Reflection/data/anonymous-classes.php:9:2',
				]),
				9,
			],
		]);
	}

}

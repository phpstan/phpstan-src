<?php declare(strict_types = 1);

namespace PHPStan\Rules\Playground;

use PHPStan\Node\InClassMethodNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Methods\MethodParameterComparisonHelper;
use PHPStan\Rules\Methods\MethodPrototypeFinder;
use PHPStan\Rules\Methods\MethodSignatureRule;
use PHPStan\Rules\Methods\MethodVisibilityComparisonHelper;
use PHPStan\Rules\Methods\OverridingMethodRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<PromoteParameterRule<InClassMethodNode>>
 */
class PromoteParameterRuleWithOriginalRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$container = self::getContainer();
		return new PromoteParameterRule(
			new OverridingMethodRule(
				$container->getByType(PhpVersion::class),
				$container->getByType(MethodSignatureRule::class),
				true,
				$container->getByType(MethodParameterComparisonHelper::class),
				$container->getByType(MethodVisibilityComparisonHelper::class),
				$container->getByType(MethodPrototypeFinder::class),
				true,
			),
			$container,
			InClassMethodNode::class,
			false,
			'checkMissingOverrideMethodAttribute',
		);
	}

	#[RequiresPhp('>= 8.3')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/promote-missing-override.php'], [
			[
				'Method PromoteMissingOverride\Bar::doFoo() overrides method PromoteMissingOverride\Foo::doFoo() but is missing the #[\Override] attribute.',
				18,
				'This error would be reported if the <fg=cyan>checkMissingOverrideMethodAttribute: true</> parameter was enabled in your <fg=cyan>%configurationFile%</>.',
			],
		]);
	}

}

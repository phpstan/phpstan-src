<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<ReadOnlyPropertyIndirectModificationRule>
 */
class ReadOnlyPropertyIndirectModificationRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ReadOnlyPropertyIndirectModificationRule(
			new PropertyReflectionFinder(),
		);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug14481(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14481.php'], [
			[
				'Readonly property Bug14481\VehicleListFilterForModule::$carTypes is indirectly modified.',
				28,
			],
			[
				'Readonly property Bug14481\IndirectModificationOutsideConstructor::$items is indirectly modified.',
				47,
			],
			[
				'Readonly property Bug14481\DirectPropertyAssignThroughReadonlyArray::$items is indirectly modified.',
				65,
			],
			[
				'Readonly property Bug14481\NestedArrayDimFetch::$nested is indirectly modified.',
				86,
			],
			[
				'Readonly property Bug14481\IncrementThroughReadonlyArray::$items is indirectly modified.',
				131,
			],
			[
				'Readonly property Bug14481\DeeperChain::$wrappers is indirectly modified.',
				165,
			],
			[
				'@readonly property Bug14481\ReadonlyByPhpDocClass::$items is indirectly modified.',
				195,
			],
			[
				'@readonly property Bug14481\ReadonlyByPhpDocProperty::$items is indirectly modified.',
				217,
			],
		]);
	}

}

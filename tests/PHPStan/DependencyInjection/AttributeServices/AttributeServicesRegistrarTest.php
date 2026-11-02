<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\AttributeServices;

use Nette\DI\ContainerBuilder;
use Nette\InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AttributeServicesRegistrarTest extends TestCase
{

	/**
	 * @return iterable<array{string|non-empty-list<string>, bool}>
	 */
	public static function dataIsConditionMet(): iterable
	{
		yield ['%enabled%', true];
		yield ['%disabled%', false];
		yield ['%toggles.enabled%', true];
		yield ['%toggles.disabled%', false];
		yield ['%reference%', true];
		yield ['not(%disabled%)', true];
		yield ['not(%toggles.enabled%)', false];
		yield [['%enabled%'], true];
		yield [['%enabled%', '%toggles.enabled%'], true];
		yield [['%enabled%', '%disabled%'], false];
		yield [['%disabled%', '%enabled%'], false];
		yield [['%enabled%', 'not(%toggles.disabled%)'], true];
		yield [['%enabled%', 'not(%toggles.enabled%)'], false];
		// like strictRules.booleansInLoopConditions: [%strictRules.allRules%, %featureToggles.bleedingEdge%]
		yield ['%enabledList%', true];
		yield ['%mixedList%', false];
	}

	/**
	 * @param string|non-empty-list<string> $if
	 */
	#[DataProvider('dataIsConditionMet')]
	public function testIsConditionMet(string|array $if, bool $expected): void
	{
		$this->assertSame($expected, AttributeServicesRegistrar::isConditionMet($this->createBuilder(), $if));
	}

	public function testMissingParameter(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Missing parameter 'toggles.missing'.");
		AttributeServicesRegistrar::isConditionMet($this->createBuilder(), 'not(%toggles.missing%)');
	}

	private function createBuilder(): ContainerBuilder
	{
		$builder = new ContainerBuilder();
		$builder->parameters = [
			'enabled' => true,
			'disabled' => false,
			'reference' => '%enabled%',
			'enabledList' => [true, '%enabled%'],
			'mixedList' => ['%enabled%', '%disabled%'],
			'toggles' => [
				'enabled' => true,
				'disabled' => false,
			],
		];

		return $builder;
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\AttributeServices;

use Nette\DI\ContainerBuilder;
use Nette\DI\Definitions\Reference;
use Nette\InvalidArgumentException;
use Nette\PhpGenerator\PhpLiteral;
use olvlvl\ComposerAttributeCollector\TargetMethodParameter;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function array_map;
use function is_array;
use function strtolower;

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

	/**
	 * @return iterable<array{string, mixed}>
	 */
	public static function dataConstructorParameter(): iterable
	{
		// like NEON service arguments: Class::CONST, ::CONST, Enum::Case and @service strings
		// in the value are turned into constants and references
		yield ['PHPStan\\Rules\\LazyRegistry::RULE_TAG', new PhpLiteral('PHPStan\\Rules\\LazyRegistry::RULE_TAG')];
		yield ['::PHP_EOL', new PhpLiteral('PHP_EOL')];
		yield ['%constants%', [new PhpLiteral('PHPStan\\Rules\\LazyRegistry::RULE_TAG'), 'plain', ['nested' => new Reference('service')]]];
		yield ['%toggles.enabled%', new PhpLiteral('$this->getParameter(\'toggles\')[\'enabled\']')];
		yield ['%plainList%', new PhpLiteral('$this->getParameter(\'plainList\')')];
		yield ['plain', 'plain'];
	}

	#[DataProvider('dataConstructorParameter')]
	public function testConstructorParameter(string $ref, mixed $expectedArgument): void
	{
		$builder = $this->createBuilder();
		$definition = $builder->addDefinition('foo')->setType(self::class);
		AttributeServicesRegistrar::processConstructorParameters($builder, self::class, $definition, [
			strtolower(self::class) => [new TargetMethodParameter(new AutowiredParameter($ref), self::class, 'value', '__construct')],
		]);

		$this->assertSame(self::describeArgument(['value' => $expectedArgument]), self::describeArgument($definition->getCreator()->arguments));
	}

	/**
	 * assertEquals() would consider a PhpLiteral equal to a string with the same contents.
	 */
	private static function describeArgument(mixed $argument): mixed
	{
		if ($argument instanceof PhpLiteral) {
			return 'literal ' . $argument;
		}
		if ($argument instanceof Reference) {
			return 'reference ' . $argument->getValue();
		}
		if (is_array($argument)) {
			return array_map(static fn ($item) => self::describeArgument($item), $argument);
		}

		return $argument;
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
			'constants' => ['PHPStan\\Rules\\LazyRegistry::RULE_TAG', 'plain', ['nested' => '@service']],
			'plainList' => ['a', 'b'],
			'toggles' => [
				'enabled' => true,
				'disabled' => false,
			],
		];

		return $builder;
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node\Name;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use function array_keys;
use function array_merge;
use function count;
use function explode;
use function implode;
use function sprintf;
use function str_contains;

#[RequiresPhp('>= 8.0.0')]
class ConstantToFunctionParameterMapTest extends PHPStanTestCase
{

	public function testMapIsValid(): void
	{
		$map = require __DIR__ . '/../../../resources/constantToFunctionParameterMap.php';
		$this->assertIsArray($map);

		$reflectionProvider = self::createReflectionProvider();

		foreach ($map as $entry => $parameters) {
			$this->assertIsString($entry, 'Entry key must be a string.');
			$this->assertIsArray($parameters, sprintf('Parameters for %s must be an array.', $entry));

			if (str_contains($entry, '::')) {
				// Method entry: Class::method
				[$className, $methodName] = explode('::', $entry, 2);

				$this->assertTrue(
					$reflectionProvider->hasClass($className),
					sprintf('Class %s not found in reflection (from %s).', $className, $entry),
				);

				$classReflection = $reflectionProvider->getClass($className);
				$this->assertTrue(
					$classReflection->hasMethod($methodName),
					sprintf('Method %s not found in reflection.', $entry),
				);

				$methodReflection = $classReflection->getNativeMethod($methodName);
				$variants = $methodReflection->getVariants();
				$this->assertNotEmpty($variants, sprintf('Method %s has no variants.', $entry));

				$reflectionParameters = $variants[0]->getParameters();
			} else {
				$this->assertNotSame('', $entry);
				// Function entry
				$nameNode = new Name($entry);
				$this->assertTrue(
					$reflectionProvider->hasFunction($nameNode, null),
					sprintf('Function %s() not found in reflection.', $entry),
				);

				$functionReflection = $reflectionProvider->getFunction($nameNode, null);
				$variants = $functionReflection->getVariants();
				$this->assertNotEmpty($variants, sprintf('Function %s() has no variants.', $entry));

				$reflectionParameters = $variants[0]->getParameters();
			}

			$reflectionParameterNames = [];
			foreach ($reflectionParameters as $reflectionParameter) {
				$reflectionParameterNames[] = $reflectionParameter->getName();
			}

			foreach ($parameters as $parameterName => $config) {
				$this->assertIsString($parameterName, sprintf('Parameter name for %s must be a string.', $entry));
				$this->assertContains(
					$parameterName,
					$reflectionParameterNames,
					sprintf(
						'Parameter $%s not found in %s. Available parameters: $%s',
						$parameterName,
						$entry,
						implode(', $', $reflectionParameterNames),
					),
				);

				$this->assertIsArray($config, sprintf('Config for %s($%s) must be an array.', $entry, $parameterName));
				$this->assertArrayHasKey('type', $config, sprintf('Missing "type" key for %s($%s).', $entry, $parameterName));
				$this->assertContains($config['type'], ['single', 'bitmask'], sprintf('Invalid type "%s" for %s($%s).', $config['type'], $entry, $parameterName));
				$this->assertArrayHasKey('constants', $config, sprintf('Missing "constants" key for %s($%s).', $entry, $parameterName));
				$this->assertIsArray($config['constants'], sprintf('Constants for %s($%s) must be an array.', $entry, $parameterName));
				$this->assertNotEmpty($config['constants'], sprintf('Constants for %s($%s) must not be empty.', $entry, $parameterName));

				foreach ($config['constants'] as $constantName) {
					$this->assertIsString($constantName, sprintf('Constant name for %s($%s) must be a string.', $entry, $parameterName));

					if (str_contains($constantName, '::')) {
						// Class constant: Class::CONSTANT
						[$constClassName, $constName] = explode('::', $constantName, 2);
						$this->assertTrue(
							$reflectionProvider->hasClass($constClassName),
							sprintf('Class %s not found in reflection (constant %s used in %s($%s)).', $constClassName, $constantName, $entry, $parameterName),
						);
						$constClassReflection = $reflectionProvider->getClass($constClassName);
						$this->assertTrue(
							$constClassReflection->hasConstant($constName),
							sprintf('Constant %s not found in reflection (used in %s($%s)).', $constantName, $entry, $parameterName),
						);
					} else {
						$this->assertNotSame('', $constantName);
						// Global constant
						$constantNameNode = new Name($constantName);
						$this->assertTrue(
							$reflectionProvider->hasConstant($constantNameNode, null),
							sprintf('Constant %s (used in %s($%s)) not found in reflection.', $constantName, $entry, $parameterName),
						);
					}
				}

				$allowedKeys = ['type', 'constants', 'exclusiveGroups'];
				foreach (array_keys($config) as $key) {
					$this->assertContains($key, $allowedKeys, sprintf('Unknown key "%s" in config for %s($%s).', $key, $entry, $parameterName));
				}

				if (!isset($config['exclusiveGroups'])) {
					continue;
				}

				$this->assertSame('bitmask', $config['type'], sprintf('exclusiveGroups only makes sense for bitmask type in %s($%s).', $entry, $parameterName));
				$this->assertIsArray($config['exclusiveGroups']);

				foreach ($config['exclusiveGroups'] as $groupIndex => $group) {
					$this->assertIsArray($group, sprintf('Exclusive group #%d for %s($%s) must be an array.', $groupIndex, $entry, $parameterName));
					$this->assertGreaterThanOrEqual(2, count($group), sprintf('Exclusive group #%d for %s($%s) must have at least 2 constants.', $groupIndex, $entry, $parameterName));

					foreach ($group as $constantName) {
						$this->assertContains(
							$constantName,
							$config['constants'],
							sprintf(
								'Constant %s in exclusive group #%d for %s($%s) is not in the constants list.',
								$constantName,
								$groupIndex,
								$entry,
								$parameterName,
							),
						);
					}
				}
			}
		}
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(
			parent::getAdditionalConfigFiles(),
			[
				__DIR__ . '/constantToFunctionParameterMap.neon',
			],
		);
	}

}

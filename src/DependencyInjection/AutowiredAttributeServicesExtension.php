<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Reference;
use Nette\DI\Helpers;
use Nette\Utils\Strings;
use olvlvl\ComposerAttributeCollector\Attributes;
use ReflectionClass;
use function strtolower;
use function substr;

final class AutowiredAttributeServicesExtension extends CompilerExtension
{

	public function loadConfiguration(): void
	{
		require_once __DIR__ . '/../../vendor/attributes.php';
		$autowiredServiceClasses = Attributes::findTargetClasses(AutowiredService::class);
		$builder = $this->getContainerBuilder();

		$autowiredParameters = Attributes::findTargetMethodParameters(AutowiredParameter::class);

		foreach ($autowiredServiceClasses as $class) {
			$reflection = new ReflectionClass($class->name);
			$attribute = $class->attribute;

			$definition = $builder->addDefinition($attribute->name)
				->setType($class->name)
				->setAutowired();

			foreach ($autowiredParameters as $autowiredParameter) {
				if (strtolower($autowiredParameter->method) !== '__construct') {
					continue;
				}
				if (strtolower($autowiredParameter->class) !== strtolower($class->name)) {
					continue;
				}
				$ref = $autowiredParameter->attribute->ref;
				if ($ref === null) {
					$argument = Helpers::expand(
						'%' . Helpers::escape($autowiredParameter->name) . '%',
						$builder->parameters,
					);
				} elseif (Strings::match($ref, '#^@[\w\\\\]+$#D') !== null) {
					$argument = new Reference(substr($ref, 1));
				} else {
					$argument = Helpers::expand(
						$ref,
						$builder->parameters,
					);
				}
				$definition->setArgument($autowiredParameter->name, $argument);
			}

			foreach (ValidateServiceTagsExtension::INTERFACE_TAG_MAPPING as $interface => $tag) {
				if (!$reflection->implementsInterface($interface)) {
					continue;
				}

				$definition->addTag($tag);
			}
		}
	}

}

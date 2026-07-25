<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\CompilerExtension;
use Nette\DI\ContainerBuilder;
use Nette\DI\Definitions\Reference;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\DI\Helpers;
use Nette\PhpGenerator\Dumper;
use Nette\PhpGenerator\PhpLiteral;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\Utils\Strings;
use olvlvl\ComposerAttributeCollector\Attributes;
use olvlvl\ComposerAttributeCollector\TargetMethodParameter;
use Override;
use PHPStan\Collectors\RegistryFactory;
use PHPStan\Rules\LazyRegistry;
use PHPStan\ShouldNotHappenException;
use ReflectionClass;
use stdClass;
use function array_key_exists;
use function array_slice;
use function count;
use function explode;
use function implode;
use function is_array;
use function preg_split;
use function sprintf;
use function strcasecmp;
use function strtolower;
use function substr;
use const PREG_SPLIT_DELIM_CAPTURE;

final class AutowiredAttributeServicesExtension extends CompilerExtension
{

	#[Override]
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'level' => Expect::int()->nullable()->required(),
		]);
	}

	#[Override]
	public function loadConfiguration(): void
	{
		require_once __DIR__ . '/../../vendor/attributes.php';
		$builder = $this->getContainerBuilder();

		$autowiredParameters = Attributes::findTargetMethodParameters(AutowiredParameter::class);
		$constructorParameters = [];
		foreach ($autowiredParameters as $parameter) {
			if (strcasecmp($parameter->method, '__construct') !== 0) {
				continue;
			}
			$lowerClass = strtolower($parameter->class);
			$constructorParameters[$lowerClass] ??= [];
			$constructorParameters[$lowerClass][] = $parameter;
		}

		foreach (Attributes::findTargetClasses(AutowiredService::class) as $class) {
			$reflection = new ReflectionClass($class->name);
			$attribute = $class->attribute;

			$definition = $builder->addDefinition($attribute->name)
				->setType($class->name)
				->setAutowired($attribute->as);

			if ($attribute->factory !== null) {
				[$ref, $method] = explode('::', $attribute->factory);
				$definition->setFactory(new Statement([new Reference(substr($ref, 1)), $method]));
			}

			self::processConstructorParameters($builder, $class->name, $definition, $constructorParameters);

			if (!$attribute->autoTag) {
				continue;
			}

			foreach (ValidateServiceTagsExtension::getInterfaceTagMapping() as $interface => $tag) {
				if (!$reflection->implementsInterface($interface)) {
					continue;
				}

				$definition->addTag($tag);
			}
		}

		foreach (Attributes::findTargetClasses(NonAutowiredService::class) as $class) {
			$attribute = $class->attribute;

			$definition = $builder->addDefinition($attribute->name)
				->setType($class->name)
				->setAutowired(false);

			if ($attribute->factory !== null) {
				[$ref, $method] = explode('::', $attribute->factory);
				$definition->setFactory(new Statement([new Reference(substr($ref, 1)), $method]));
			}

			self::processConstructorParameters($builder, $class->name, $definition, $constructorParameters);
		}

		foreach (Attributes::findTargetClasses(GenerateFactory::class) as $class) {
			$attribute = $class->attribute;
			$definition = $builder->addFactoryDefinition(null)
				->setImplement($attribute->interface);

			if ($attribute->resultType !== null) {
				$definition->getResultDefinition()->setType($attribute->resultType);
			}

			$resultDefinition = $definition->getResultDefinition();
			self::processConstructorParameters($builder, $class->name, $resultDefinition, $constructorParameters);
		}

		/** @var stdClass&object{level: int|null} $config */
		$config = $this->getConfig();
		if ($config->level === null) {
			return;
		}

		foreach (Attributes::findTargetClasses(RegisteredRule::class) as $class) {
			$attribute = $class->attribute;
			if ($attribute->level > $config->level) {
				continue;
			}

			$definition = $builder->addDefinition(null)
				->setFactory($class->name)
				->setAutowired($class->name)
				->addTag(LazyRegistry::RULE_TAG);

			self::processConstructorParameters($builder, $class->name, $definition, $constructorParameters);
		}

		foreach (Attributes::findTargetClasses(RegisteredCollector::class) as $class) {
			$attribute = $class->attribute;
			if ($attribute->level > $config->level) {
				continue;
			}

			$definition = $builder->addDefinition(null)
				->setFactory($class->name)
				->setAutowired($class->name)
				->addTag(RegistryFactory::COLLECTOR_TAG);

			self::processConstructorParameters($builder, $class->name, $definition, $constructorParameters);
		}
	}

	/**
	 * @param class-string $className
	 * @param array<lowercase-string, non-empty-list<TargetMethodParameter<AutowiredParameter>>> $constructorParameters
	 */
	public static function processConstructorParameters(ContainerBuilder $builder, string $className, ServiceDefinition $definition, array $constructorParameters): void
	{
		foreach ($constructorParameters[strtolower($className)] ?? [] as $autowiredParameter) {
			$ref = $autowiredParameter->attribute->ref;
			if ($ref === null) {
				$argument = self::createDeferredParameter($builder, '%' . Helpers::escape($autowiredParameter->name) . '%');
			} elseif (Strings::match($ref, '#^@[\w\\\\]+$#D') !== null) {
				$argument = new Reference(substr($ref, 1));
			} else {
				$argument = self::createDeferredParameter($builder, $ref);
			}
			$definition->setArgument($autowiredParameter->name, $argument);
		}
	}

	/**
	 * Turns a `%foo%` reference into a deferred `$this->getParameter('foo')` lookup instead of reading
	 * ContainerBuilder::$parameters right now. Extensions registered after this one still rewrite the
	 * parameters during loadConfiguration() - ValidateExcludePathsExtension unwraps OptionalPath objects
	 * in `excludePaths` - and a value snapshotted here would keep the pre-rewrite contents.
	 *
	 * `%foo.bar%` becomes `$this->getParameter('foo')['bar']` and `%foo%/suffix` becomes a concatenation,
	 * mirroring how Nette itself compiles references to dynamic parameters.
	 *
	 * @return PhpLiteral|string
	 * @throws ShouldNotHappenException when the reference points at a parameter that does not exist
	 */
	private static function createDeferredParameter(ContainerBuilder $builder, string $ref)
	{
		$parts = preg_split('#%([\w.-]*)%#', $ref, flags: PREG_SPLIT_DELIM_CAPTURE);
		if ($parts === false) {
			throw new ShouldNotHappenException();
		}

		$dumper = new Dumper();
		$lookups = [];
		$pieces = [];
		$withoutReferences = '';
		foreach ($parts as $i => $part) {
			if ($i % 2 === 0) {
				if ($part !== '') {
					$pieces[] = $dumper->dump($part);
					$withoutReferences .= $part;
				}
				continue;
			}

			if ($part === '') {
				// '%%' is an escaped percent sign
				$pieces[] = $dumper->dump('%');
				$withoutReferences .= '%';
				continue;
			}

			$keys = explode('.', $part);
			self::checkParameterExists($builder, $part, $keys);

			$code = $dumper->format('$this->getParameter(?)', $keys[0]);
			foreach (array_slice($keys, 1) as $key) {
				$code .= sprintf('[%s]', $dumper->dump($key));
			}

			$lookups[] = $code;
			$pieces[] = sprintf('(%s)', $code);
		}

		if (count($lookups) === 0) {
			return $withoutReferences;
		}

		if (count($pieces) === 1) {
			// the reference is the whole value, no string coercion
			return ContainerBuilder::literal($lookups[0]);
		}

		return ContainerBuilder::literal(implode(' . ', $pieces));
	}

	/**
	 * The values are resolved at runtime but the keys never change after loadConfiguration(),
	 * so a typo in a reference is still caught while compiling the container.
	 *
	 * @param non-empty-list<string> $keys
	 * @throws ShouldNotHappenException
	 */
	private static function checkParameterExists(ContainerBuilder $builder, string $ref, array $keys): void
	{
		$value = $builder->parameters;
		foreach ($keys as $key) {
			if (!is_array($value)) {
				// a dynamic parameter - cannot be traversed while compiling
				return;
			}
			if (!array_key_exists($key, $value)) {
				throw new ShouldNotHappenException(sprintf("Missing parameter '%s'.", $ref));
			}

			$value = $value[$key];
		}
	}

}

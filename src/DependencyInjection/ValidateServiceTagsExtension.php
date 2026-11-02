<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Nette\DI\InvalidConfigurationException;
use Nette\PhpGenerator\ClassType;
use olvlvl\ComposerAttributeCollector\Attributes;
use Override;
use PhpParser\NodeVisitor;
use PHPStan\DependencyInjection\AttributeServices\AttributeTargetsProvider;
use PHPStan\Parser\RichParser;
use PHPStan\ShouldNotHappenException;
use ReflectionClass;
use function array_flip;
use function array_key_exists;
use function array_keys;
use function interface_exists;
use function is_array;
use function is_string;
use function sprintf;

#[ContainerExtension(name: 'validateServiceTags')]
final class ValidateServiceTagsExtension extends CompilerExtension
{

	/** @var array<class-string, string>|null */
	private static ?array $interfaceTagMapping = null;

	/**
	 * PHPStan's own extension interfaces, derived from the #[ExtensionInterface] attribute above each of them.
	 *
	 * @return array<class-string, string>
	 */
	public static function getInterfaceTagMapping(): array
	{
		if (self::$interfaceTagMapping !== null) {
			return self::$interfaceTagMapping;
		}

		require_once __DIR__ . '/../../vendor/attributes.php';

		$mapping = [
			// vendor interface - cannot carry the #[ExtensionInterface] attribute
			NodeVisitor::class => RichParser::VISITOR_SERVICE_TAG,
		];
		foreach (Attributes::findTargetClasses(ExtensionInterface::class) as $class) {
			// the attribute is not repeatable but the collector does not validate that
			if (array_key_exists($class->name, $mapping)) {
				throw new ShouldNotHappenException(sprintf('Interface %s claims multiple tags', $class->name));
			}
			$mapping[$class->name] = $class->attribute->tag;
		}

		return self::$interfaceTagMapping = $mapping;
	}

	/**
	 * PHPStan's own extension interfaces plus the third-party ones, marked with #[ExtensionInterface]
	 * in the attributeServicesDirectories section or listed in the extensionInterfaces section.
	 *
	 * @return array<class-string, string>
	 * @throws InvalidConfigurationException
	 */
	public static function getExtendedInterfaceTagMapping(Compiler $compiler): array
	{
		$configured = [];
		foreach ($compiler->getExtensions(ExtensionInterfacesExtension::class) as $extension) {
			$config = $extension->getConfig();
			if (!is_array($config)) {
				continue;
			}
			$configured += $config;
		}

		return self::createInterfaceTagMapping($configured);
	}

	/**
	 * @param array<mixed> $configured the extensionInterfaces section
	 * @return array<class-string, string>
	 * @throws InvalidConfigurationException
	 */
	public static function createInterfaceTagMapping(array $configured): array
	{
		$mapping = self::getInterfaceTagMapping();
		$entries = [];
		foreach (AttributeTargetsProvider::create()->findTargetClasses(ExtensionInterface::class) as $class) {
			$entries[] = [$class->name, $class->attribute->tag, sprintf('#[ExtensionInterface] on %s', $class->name)];
		}
		foreach ($configured as $interface => $tag) {
			if (!is_string($interface) || !is_string($tag)) {
				continue;
			}
			$entries[] = [$interface, $tag, sprintf('the %s section', ExtensionInterfacesExtension::NAME)];
		}

		$tagInterfaces = array_flip($mapping);
		foreach ($entries as [$interface, $tag, $source]) {
			if (!interface_exists($interface)) {
				throw new InvalidConfigurationException(sprintf('Interface %s from %s does not exist.', $interface, $source));
			}
			$interface = (new ReflectionClass($interface))->getName();
			if (array_key_exists($interface, $mapping)) {
				if ($mapping[$interface] === $tag) {
					continue;
				}

				throw new InvalidConfigurationException(sprintf('Interface %s is mapped to tag %s, %s maps it to %s.', $interface, $mapping[$interface], $source, $tag));
			}
			if (array_key_exists($tag, $tagInterfaces)) {
				throw new InvalidConfigurationException(sprintf('Tag %s is mapped to interface %s, %s maps it to %s.', $tag, $tagInterfaces[$tag], $source, $interface));
			}

			$mapping[$interface] = $tag;
			$tagInterfaces[$tag] = $interface;
		}

		return $mapping;
	}

	/**
	 * Deliberately runs in afterCompile() rather than beforeCompile(): tags are added by other
	 * extensions' beforeCompile() - ConditionalTagsExtension, and any extension registered from a
	 * project config file, which the compiler always processes after PHPStan's own ones - and
	 * beforeCompile() hooks run in registration order, so validating there would only cover the
	 * extensions that happen to be registered earlier. Tags added after this point no longer reach
	 * the generated container, making this the first moment the tag list is complete.
	 *
	 * @throws MissingImplementedInterfaceInServiceWithTagException
	 */
	#[Override]
	public function afterCompile(ClassType $class): void
	{
		$builder = $this->getContainerBuilder();
		$flippedMapping = array_flip(self::getExtendedInterfaceTagMapping($this->compiler));

		foreach ($builder->getDefinitions() as $definition) {
			/** @var class-string|null $className */
			$className = $definition->getType();
			if ($className === null) {
				continue;
			}
			$reflection = null;
			foreach (array_keys($definition->getTags()) as $tag) {
				if (!array_key_exists($tag, $flippedMapping)) {
					continue;
				}

				$reflection ??= new ReflectionClass($className);
				if ($reflection->implementsInterface($flippedMapping[$tag])) {
					continue;
				}

				throw new MissingImplementedInterfaceInServiceWithTagException($className, $tag, $flippedMapping[$tag]);
			}
		}
	}

}

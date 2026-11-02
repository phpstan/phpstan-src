<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use InvalidArgumentException;
use Nette;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Statement;
use Nette\DI\InvalidConfigurationException;
use Nette\Schema\Expect;
use Override;
use PHPStan\ShouldNotHappenException;
use function array_keys;
use function array_values;
use function count;
use function in_array;
use function is_array;
use function sprintf;

#[ContainerExtension(name: 'conditionalTags')]
final class ConditionalTagsExtension extends CompilerExtension
{

	/**
	 * Accepts any tag, the tags of third-party extension interfaces are known only
	 * after all sections are processed. loadConfiguration() checks them.
	 */
	#[Override]
	public function getConfigSchema(): Nette\Schema\Schema
	{
		return Expect::arrayOf(Expect::arrayOf(Expect::anyOf(
			Expect::bool(),
			Expect::type(Statement::class),
			Expect::listOf(Expect::anyOf(Expect::bool(), Expect::type(Statement::class))),
		), 'string')->min(1));
	}

	#[Override]
	public function loadConfiguration(): void
	{
		/** @var array<string, array<string, mixed>> $config */
		$config = $this->config;
		$tags = array_values(ValidateServiceTagsExtension::getExtendedInterfaceTagMapping($this->compiler));
		foreach ($config as $type => $typeTags) {
			foreach (array_keys($typeTags) as $tag) {
				if (in_array($tag, $tags, true)) {
					continue;
				}

				throw new InvalidConfigurationException(sprintf(
					"Unexpected item '%s › %s › %s', tags of extension interfaces are expected. Map the interface of a custom tag in the %s section.",
					$this->name,
					$type,
					$tag,
					ExtensionInterfacesExtension::NAME,
				));
			}
		}
	}

	#[Override]
	public function beforeCompile(): void
	{
		/** @var mixed[] $config */
		$config = $this->config;
		$builder = $this->getContainerBuilder();

		foreach ($config as $type => $tags) {
			$services = $builder->findByType($type);
			if (count($services) === 0) {
				throw new ShouldNotHappenException(sprintf('No services of type "%s" found.', $type));
			}
			foreach ($services as $service) {
				foreach ($tags as $tag => $condition) {
					if (!self::isConditionMet($condition)) {
						continue;
					}

					$service->addTag($tag);
				}
			}
		}
	}

	/**
	 * Shared with the #[Conditional] attribute. A list of conditions must all be met.
	 *
	 * @param mixed $condition with parameters already expanded
	 */
	public static function isConditionMet(mixed $condition): bool
	{
		if (is_array($condition)) {
			foreach ($condition as $item) {
				if (!self::resolveValue($item)) {
					return false;
				}
			}

			return true;
		}

		return self::resolveValue($condition);
	}

	private static function resolveValue(mixed $parameter): bool
	{
		if (!$parameter instanceof Statement) {
			return (bool) $parameter;
		}

		if ($parameter->getEntity() === 'not') {
			return ! (bool) $parameter->arguments[0];
		}

		throw new InvalidArgumentException('Unsupported Statement.');
	}

}

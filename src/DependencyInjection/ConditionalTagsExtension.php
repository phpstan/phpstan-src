<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use InvalidArgumentException;
use Nette;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Expect;
use Override;
use PHPStan\ShouldNotHappenException;
use function array_fill_keys;
use function array_reduce;
use function array_values;
use function count;
use function is_array;
use function sprintf;

final class ConditionalTagsExtension extends CompilerExtension
{

	#[Override]
	public function getConfigSchema(): Nette\Schema\Schema
	{
		$tags = array_values(ValidateServiceTagsExtension::INTERFACE_TAG_MAPPING);

		return Expect::arrayOf(Expect::structure(
			array_fill_keys($tags, Expect::anyOf(
				Expect::bool(),
				Expect::type(Statement::class),
				Expect::listOf(Expect::anyOf(Expect::bool(), Expect::type(Statement::class))),
			)),
		)->min(1));
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
				foreach ($tags as $tag => $parameter) {
					$parameter = is_array($parameter)
						? array_reduce($parameter, fn ($carry, $item) => $carry && $this->resolveValue($item), true)
						: $this->resolveValue($parameter);

					if ($parameter) {
						$service->addTag($tag);
						continue;
					}
				}
			}
		}
	}

	public function resolveValue(mixed $parameter): bool
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

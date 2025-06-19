<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette;
use Nette\DI\CompilerExtension;
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
			array_fill_keys($tags, Expect::anyOf(Expect::bool(), Expect::listOf(Expect::bool()))),
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
					if (is_array($parameter)) {
						$parameter = array_reduce($parameter, static fn ($carry, $item) => $carry && (bool) $item, true);
					}
					if ((bool) $parameter) {
						$service->addTag($tag);
						continue;
					}
				}
			}
		}
	}

}

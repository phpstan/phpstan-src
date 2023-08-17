<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette;
use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use PHPStan\Analyser\TypeSpecifierFactory;
use PHPStan\Broker\BrokerFactory;
use PHPStan\Collectors\RegistryFactory as CollectorRegistryFactory;
use PHPStan\DependencyInjection\Type\LazyDynamicThrowTypeExtensionProvider;
use PHPStan\Parser\RichParser;
use PHPStan\PhpDoc\TypeNodeResolverExtension;
use PHPStan\Rules\LazyRegistry;
use PHPStan\ShouldNotHappenException;
use function array_reduce;
use function count;
use function is_array;
use function sprintf;

class ConditionalTagsExtension extends CompilerExtension
{

	public function getConfigSchema(): Nette\Schema\Schema
	{
		$bool = Expect::anyOf(Expect::bool(), Expect::listOf(Expect::bool()));
		return Expect::arrayOf(Expect::structure([
			BrokerFactory::PROPERTIES_CLASS_REFLECTION_EXTENSION_TAG => $bool,
			BrokerFactory::METHODS_CLASS_REFLECTION_EXTENSION_TAG => $bool,
			BrokerFactory::DYNAMIC_METHOD_RETURN_TYPE_EXTENSION_TAG => $bool,
			BrokerFactory::DYNAMIC_STATIC_METHOD_RETURN_TYPE_EXTENSION_TAG => $bool,
			BrokerFactory::DYNAMIC_FUNCTION_RETURN_TYPE_EXTENSION_TAG => $bool,
			BrokerFactory::OPERATOR_TYPE_SPECIFYING_EXTENSION_TAG => $bool,
			LazyRegistry::RULE_TAG => $bool,
			TypeNodeResolverExtension::EXTENSION_TAG => $bool,
			TypeSpecifierFactory::FUNCTION_TYPE_SPECIFYING_EXTENSION_TAG => $bool,
			TypeSpecifierFactory::METHOD_TYPE_SPECIFYING_EXTENSION_TAG => $bool,
			TypeSpecifierFactory::STATIC_METHOD_TYPE_SPECIFYING_EXTENSION_TAG => $bool,
			RichParser::VISITOR_SERVICE_TAG => $bool,
			CollectorRegistryFactory::COLLECTOR_TAG => $bool,
			LazyDynamicThrowTypeExtensionProvider::FUNCTION_TAG => $bool,
			LazyDynamicThrowTypeExtensionProvider::METHOD_TAG => $bool,
			LazyDynamicThrowTypeExtensionProvider::STATIC_METHOD_TAG => $bool,
		])->min(1));
	}

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

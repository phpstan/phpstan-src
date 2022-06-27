<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette;
use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use PHPStan\Analyser\TypeSpecifierFactory;
use PHPStan\Broker\BrokerFactory;
use PHPStan\Collectors\RegistryFactory as CollectorRegistryFactory;
use PHPStan\Parser\RichParser;
use PHPStan\PhpDoc\TypeNodeResolverExtension;
use PHPStan\Rules\RegistryFactory as RuleRegistryFactory;
use PHPStan\ShouldNotHappenException;
use function count;
use function sprintf;

class ConditionalTagsExtension extends CompilerExtension
{

	public function getConfigSchema(): Nette\Schema\Schema
	{
		$bool = Expect::bool();
		return Expect::arrayOf(Expect::structure([
			BrokerFactory::PROPERTIES_CLASS_REFLECTION_EXTENSION_TAG => $bool,
			BrokerFactory::METHODS_CLASS_REFLECTION_EXTENSION_TAG => $bool,
			BrokerFactory::DYNAMIC_METHOD_RETURN_TYPE_EXTENSION_TAG => $bool,
			BrokerFactory::DYNAMIC_STATIC_METHOD_RETURN_TYPE_EXTENSION_TAG => $bool,
			BrokerFactory::DYNAMIC_FUNCTION_RETURN_TYPE_EXTENSION_TAG => $bool,
			BrokerFactory::OPERATOR_TYPE_SPECIFYING_EXTENSION_TAG => $bool,
			RuleRegistryFactory::RULE_TAG => $bool,
			TypeNodeResolverExtension::EXTENSION_TAG => $bool,
			TypeSpecifierFactory::FUNCTION_TYPE_SPECIFYING_EXTENSION_TAG => $bool,
			TypeSpecifierFactory::METHOD_TYPE_SPECIFYING_EXTENSION_TAG => $bool,
			TypeSpecifierFactory::STATIC_METHOD_TYPE_SPECIFYING_EXTENSION_TAG => $bool,
			RichParser::VISITOR_SERVICE_TAG => $bool,
			CollectorRegistryFactory::COLLECTOR_TAG => $bool,
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
					if ((bool) $parameter) {
						$service->addTag($tag);
						continue;
					}
				}
			}
		}
	}

}

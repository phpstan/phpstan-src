<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\CompilerExtension;
use Override;
use PHPStan\Analyser\Fiber\FiberNodeScopeResolver;
use PHPStan\Analyser\NodeScopeResolver;
use function getenv;
use const PHP_VERSION_ID;

#[ContainerExtension(name: 'fnsr')]
final class FnsrExtension extends CompilerExtension
{

	#[Override]
	public function beforeCompile()
	{
		if (PHP_VERSION_ID < 80100) {
			return;
		}

		$enable = getenv('PHPSTAN_FNSR');
		if ($enable === '0') {
			return;
		}

		$builder = $this->getContainerBuilder();
		$nodeScopeResolverDef = $builder->getDefinitionByType(NodeScopeResolver::class);
		$nodeScopeResolverDef->setAutowired(false);

		$fiberNodeScopeResolverDef = $builder->getDefinitionByType(FiberNodeScopeResolver::class);
		$fiberNodeScopeResolverDef->setAutowired([NodeScopeResolver::class, FiberNodeScopeResolver::class]);
	}

}

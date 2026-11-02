<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Override;

/**
 * Registers the top-level `extensionInterfaces` section mapping third-party extension
 * interfaces to their service tags, like #[ExtensionInterface] on the interface itself:
 *
 * extensionInterfaces:
 *     App\MyExtension: app.myExtension
 *
 * The section is read by ValidateServiceTagsExtension::getExtendedInterfaceTagMapping().
 */
#[ContainerExtension(name: self::NAME)]
final class ExtensionInterfacesExtension extends CompilerExtension
{

	public const NAME = 'extensionInterfaces';

	#[Override]
	public function getConfigSchema(): Schema
	{
		return Expect::arrayOf('string', 'string');
	}

}

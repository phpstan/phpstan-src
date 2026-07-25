<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\CompilerExtension;
use Nette\DI\Extensions\ExtensionsExtension;
use olvlvl\ComposerAttributeCollector\Attributes;
use Override;
use PHPStan\ShouldNotHappenException;
use function is_a;
use function sprintf;
use function strcmp;
use function usort;

/**
 * Registers every compiler extension marked with #[ContainerExtension], so that PHPStan's own
 * extensions do not have to be listed in the `extensions:` section of conf/config.neon.
 *
 * Extending ExtensionsExtension is required, not cosmetic. Compiler::processExtensions() starts with
 *
 *     $first = $this->getExtensions(ParametersExtension::class) + $this->getExtensions(ExtensionsExtension::class);
 *
 * and only extensions matching those types by instanceof have their loadConfiguration() called before
 * the compiler snapshots its extension list. Everything registered after that snapshot is rejected with
 * "Extensions ... were added while container was being compiled", so a plain CompilerExtension cannot
 * register extensions - not even when it is installed in the compiler's `extensions` slot, since that
 * check looks at the class and not at the name. ContainerFactory installs this class in that slot,
 * which additionally makes it responsible for `extensions:` sections of configuration files; those
 * keep working through the parent implementation.
 *
 * Extensions are registered ordered by name so that a container is reproducible no matter what
 * order the attribute collector reports the classes in. Nothing may depend on that order: the
 * compiler runs loadConfiguration() on every extension before any beforeCompile(), and PHPStan's
 * extensions are written not to care about their relative position within either phase.
 */
final class ContainerExtensionsExtension extends ExtensionsExtension
{

	#[Override]
	public function loadConfiguration(): void
	{
		require_once __DIR__ . '/../../vendor/attributes.php';

		$classes = Attributes::findTargetClasses(ContainerExtension::class);
		usort($classes, static fn ($a, $b): int => strcmp($a->attribute->name, $b->attribute->name));

		foreach ($classes as $class) {
			$className = $class->name;
			if (!is_a($className, CompilerExtension::class, true)) {
				throw new ShouldNotHappenException(sprintf(
					'Class %s with #[ContainerExtension] is not a %s descendant.',
					$className,
					CompilerExtension::class,
				));
			}

			$this->compiler->addExtension($class->attribute->name, new $className());
		}

		parent::loadConfiguration();
	}

}

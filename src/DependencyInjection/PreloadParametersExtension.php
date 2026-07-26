<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\CompilerExtension;
use Nette\DI\Container as NetteDiContainer;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Override;
use function array_keys;

/**
 * Makes Container::getParameters() return every parameter.
 *
 * Nette compiles a parameter whose value is a statement - `foo: ::getenv('BAR')`, or anything
 * concatenating one, such as `baz: %foo%/dir` - into a lazy Container::getDynamicParameter() branch,
 * and since nette/di 3.1.10 (nette/di@4a165140, "exports both statements and dynamic parameters,
 * preloads only the latter") it deliberately leaves those out of the generated getParameters().
 * They stay readable through getParameter(), so nothing in the container breaks, but anything asking
 * for all parameters at once silently receives an incomplete set: validating them against
 * parametersSchema reports the parameter as missing, and `dump-parameters` omits it.
 *
 * Listing every parameter in the preload keeps getParameters() complete no matter how a parameter is
 * written, in a project's configuration file as much as in PHPStan's own. Preloading a parameter that
 * is already static just reads it back out of the array.
 *
 * Relies on running after Nette's ParametersExtension, which writes getParameters() in its own
 * afterCompile(). The compiler registers that one in its constructor, so it always comes before an
 * extension registered from an attribute or from an `extensions:` section.
 */
#[ContainerExtension(name: 'preloadParameters')]
final class PreloadParametersExtension extends CompilerExtension
{

	#[Override]
	public function afterCompile(ClassType $class): void
	{
		$parameterNames = array_keys($this->getContainerBuilder()->parameters);
		if ($parameterNames === []) {
			return;
		}

		if ($class->hasMethod('getParameters')) {
			$method = $class->getMethod('getParameters');
		} else {
			$method = Method::from([NetteDiContainer::class, 'getParameters']);
			$class->addMember($method);
		}

		$method->setBody(
			"array_map([\$this, 'getParameter'], ?);\nreturn parent::getParameters();",
			[$parameterNames],
		);
	}

}

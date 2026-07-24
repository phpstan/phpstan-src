<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Exception;
use function sprintf;

final class NotAnExtensionInterfaceException extends Exception
{

	public function __construct(string $className, string $parameterName, string $interface)
	{
		parent::__construct(sprintf(
			'Parameter $%s of %s asks for extensions of %s which is not marked with the #[%s] attribute.',
			$parameterName,
			$className,
			$interface,
			ExtensionInterface::class,
		));
	}

}

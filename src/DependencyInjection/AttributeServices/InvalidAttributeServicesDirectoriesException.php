<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\AttributeServices;

use Nette\DI\InvalidConfigurationException;
use function implode;

final class InvalidAttributeServicesDirectoriesException extends InvalidConfigurationException
{

	/**
	 * @param non-empty-list<string> $errors
	 */
	public function __construct(private array $errors)
	{
		parent::__construct(implode("\n\n", $errors));
	}

	/**
	 * @return non-empty-list<string>
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

}

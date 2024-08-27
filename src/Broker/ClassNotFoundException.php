<?php declare(strict_types = 1);

namespace PHPStan\Broker;

use PHPStan\AnalysedCodeException;
use function sprintf;

final class ClassNotFoundException extends AnalysedCodeException
{

	public function __construct(private string $className)
	{
		parent::__construct(sprintf('Class %s was not found while trying to analyse it - discovering symbols is probably not configured properly.', $className));
	}

	public function getClassName(): string
	{
		return $this->className;
	}

	public function getTip(): string
	{
		return 'Learn more at https://phpstan.org/user-guide/discovering-symbols';
	}

}

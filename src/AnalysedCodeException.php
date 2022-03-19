<?php declare(strict_types = 1);

namespace PHPStan;

use Exception;

abstract class AnalysedCodeException extends Exception
{

	abstract public function getTip(): ?string;

}

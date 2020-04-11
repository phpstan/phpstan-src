<?php declare(strict_types = 1);

namespace PHPStan;

abstract class AnalysedCodeException extends \Exception
{

	abstract public function getTip(): ?string;

}

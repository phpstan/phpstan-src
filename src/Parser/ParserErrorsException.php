<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Exception;
use PhpParser\Error;
use function array_map;
use function implode;

class ParserErrorsException extends Exception
{

	/** @var Error[] */
	private array $errors;

	private ?string $parsedFile;

	/**
	 * @param Error[] $errors
	 */
	public function __construct(
		array $errors,
		?string $parsedFile
	)
	{
		parent::__construct(implode(', ', array_map(static fn (Error $error): string => $error->getMessage(), $errors)));
		$this->errors = $errors;
		$this->parsedFile = $parsedFile;
	}

	/**
	 * @return Error[]
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

	public function getParsedFile(): ?string
	{
		return $this->parsedFile;
	}

}

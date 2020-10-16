<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Error;

class ParserErrorsException extends \Exception
{

	/** @var \PhpParser\Error[] */
	private array $errors;

	private ?string $parsedFile;

	/**
	 * @param \PhpParser\Error[] $errors
	 * @param string|null $parsedFile
	 */
	public function __construct(
		array $errors,
		?string $parsedFile
	)
	{
		parent::__construct(implode(', ', array_map(static function (Error $error): string {
			return $error->getMessage();
		}, $errors)));
		$this->errors = $errors;
		$this->parsedFile = $parsedFile;
	}

	/**
	 * @return \PhpParser\Error[]
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

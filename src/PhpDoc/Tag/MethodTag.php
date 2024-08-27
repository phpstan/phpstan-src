<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Type;

/**
 * @api
 * @final
 */
class MethodTag
{

	/**
	 * @param array<string, MethodTagParameter> $parameters
	 * @param array<string, TemplateTag> $templateTags
	 */
	public function __construct(
		private Type $returnType,
		private bool $isStatic,
		private array $parameters,
		private array $templateTags = [],
	)
	{
	}

	public function getReturnType(): Type
	{
		return $this->returnType;
	}

	public function isStatic(): bool
	{
		return $this->isStatic;
	}

	/**
	 * @return array<string, MethodTagParameter>
	 */
	public function getParameters(): array
	{
		return $this->parameters;
	}

	/**
	 * @return array<string, TemplateTag>
	 */
	public function getTemplateTags(): array
	{
		return $this->templateTags;
	}

}

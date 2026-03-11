<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\Type;
use function array_values;

/**
 * Represents a template type used with explicit generic args at the usage site.
 * For example, K<T> where @template K of IFoo.
 *
 * This is distinct from TemplateGenericObjectType which represents a template
 * declared with a generic bound (@template K of IFoo<T>).
 */
final class TemplateAppliedGenericObjectType extends GenericObjectType
{

	/**
	 * @param non-empty-string $templateName
	 * @param list<Type> $types
	 * @param list<TemplateTypeVariance> $variances
	 */
	public function __construct(
		private string $templateName,
		string $className,
		array $types,
		?Type $subtractedType = null,
		array $variances = [],
	)
	{
		parent::__construct($className, $types, $subtractedType, variances: $variances);
	}

	/** @return non-empty-string */
	public function getTemplateName(): string
	{
		return $this->templateName;
	}

	protected function recreate(string $className, array $types, ?Type $subtractedType, array $variances = []): GenericObjectType
	{
		return new self(
			$this->templateName,
			$className,
			array_values($types),
			$subtractedType,
			array_values($variances),
		);
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Command\Neon2Attributes;

/**
 * One configuration entry that can be expressed as attributes on its class.
 */
final class ServiceConversion
{

	/**
	 * @param 'services'|'rules' $section
	 * @param int $entryIndex position of the entry among the section's entries, in file order
	 * @param array<string, string> $parameterAttributes constructor parameter name => attribute code
	 * @param list<string> $useImports fully qualified names to import
	 */
	public function __construct(
		public string $section,
		public int $entryIndex,
		public string $className,
		public string $phpFile,
		public string $attributeCode,
		public array $parameterAttributes,
		public array $useImports,
	)
	{
	}

}

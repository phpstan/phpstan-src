<?php declare(strict_types = 1);

namespace PHPStan\Rules;

/**
 * @api
 * @api-do-not-implement
 */
interface MetadataRuleError extends RuleError
{

	/**
	 * @return mixed[]
	 */
	public function getMetadata(): array;

}

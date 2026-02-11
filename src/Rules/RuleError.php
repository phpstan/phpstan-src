<?php declare(strict_types = 1);

namespace PHPStan\Rules;

/**
 * @api
 * @api-do-not-implement
 */
interface RuleError
{

	public function getMessage(): string;

}

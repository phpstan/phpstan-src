<?php declare(strict_types = 1);

namespace PHPStan\Rules;

/**
 * @api
 * @api-do-not-implement
 */
interface FileRuleError extends RuleError
{

	public function getFile(): string;

	public function getFileDescription(): string;

}

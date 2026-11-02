<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

use PHPStan\Rules\FixableNodeRuleError;
use PHPStan\Rules\NodeFix;
use PHPStan\Rules\NonIgnorableRuleError;
use PHPStan\Rules\RuleError;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class RuleError193 implements RuleError, NonIgnorableRuleError, FixableNodeRuleError
{

	public string $message;

	/** @var non-empty-list<NodeFix> */
	public array $nodeFixes;

	public function getMessage(): string
	{
		return $this->message;
	}

	/**
	 * @return non-empty-list<NodeFix>
	 */
	public function getNodeFixes(): array
	{
		return $this->nodeFixes;
	}

}

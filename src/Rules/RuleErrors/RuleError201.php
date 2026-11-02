<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

use PHPStan\Rules\FixableNodeRuleError;
use PHPStan\Rules\NodeFix;
use PHPStan\Rules\NonIgnorableRuleError;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\TipRuleError;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class RuleError201 implements RuleError, TipRuleError, NonIgnorableRuleError, FixableNodeRuleError
{

	public string $message;

	public string $tip;

	/** @var non-empty-list<NodeFix> */
	public array $nodeFixes;

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getTip(): string
	{
		return $this->tip;
	}

	/**
	 * @return non-empty-list<NodeFix>
	 */
	public function getNodeFixes(): array
	{
		return $this->nodeFixes;
	}

}

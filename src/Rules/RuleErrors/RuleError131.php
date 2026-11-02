<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

use PHPStan\Rules\FixableNodeRuleError;
use PHPStan\Rules\LineRuleError;
use PHPStan\Rules\NodeFix;
use PHPStan\Rules\RuleError;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class RuleError131 implements RuleError, LineRuleError, FixableNodeRuleError
{

	public string $message;

	public int $line;

	/** @var non-empty-list<NodeFix> */
	public array $nodeFixes;

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getLine(): int
	{
		return $this->line;
	}

	/**
	 * @return non-empty-list<NodeFix>
	 */
	public function getNodeFixes(): array
	{
		return $this->nodeFixes;
	}

}

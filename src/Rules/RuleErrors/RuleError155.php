<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

use PHPStan\Rules\FixableNodeRuleError;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\LineRuleError;
use PHPStan\Rules\NodeFix;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\TipRuleError;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class RuleError155 implements RuleError, LineRuleError, TipRuleError, IdentifierRuleError, FixableNodeRuleError
{

	public string $message;

	public int $line;

	public string $tip;

	public string $identifier;

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

	public function getTip(): string
	{
		return $this->tip;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	/**
	 * @return non-empty-list<NodeFix>
	 */
	public function getNodeFixes(): array
	{
		return $this->nodeFixes;
	}

}

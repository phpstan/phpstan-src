<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

use PHPStan\Rules\FixableNodeRuleError;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\MetadataRuleError;
use PHPStan\Rules\NodeFix;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\TipRuleError;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class RuleError185 implements RuleError, TipRuleError, IdentifierRuleError, MetadataRuleError, FixableNodeRuleError
{

	public string $message;

	public string $tip;

	public string $identifier;

	/** @var mixed[] */
	public array $metadata;

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

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	/**
	 * @return mixed[]
	 */
	public function getMetadata(): array
	{
		return $this->metadata;
	}

	/**
	 * @return non-empty-list<NodeFix>
	 */
	public function getNodeFixes(): array
	{
		return $this->nodeFixes;
	}

}

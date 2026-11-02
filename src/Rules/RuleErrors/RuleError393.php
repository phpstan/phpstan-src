<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

use PHPStan\Rules\FileDependenciesRuleError;
use PHPStan\Rules\FixableNodeRuleError;
use PHPStan\Rules\NodeFix;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\TipRuleError;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class RuleError393 implements RuleError, TipRuleError, FixableNodeRuleError, FileDependenciesRuleError
{

	public string $message;

	public string $tip;

	/** @var non-empty-list<NodeFix> */
	public array $nodeFixes;

	/** @var list<string> */
	public array $fileDependencies;

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

	/**
	 * @return list<string>
	 */
	public function getFileDependencies(): array
	{
		return $this->fileDependencies;
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\Rules\FileRuleError;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\LineRuleError;
use PHPStan\Rules\MetadataRuleError;
use PHPStan\Rules\NonIgnorableRuleError;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\TipRuleError;
use function is_string;

class RuleErrorTransformer
{

	/**
	 * @param class-string<Node> $nodeType
	 */
	public function transform(
		string|RuleError $ruleError,
		Scope $scope,
		string $nodeType,
		int $nodeLine,
	): Error
	{
		$line = $nodeLine;
		$canBeIgnored = true;
		$fileName = $scope->getFileDescription();
		$filePath = $scope->getFile();
		$traitFilePath = null;
		$tip = null;
		$identifier = null;
		$metadata = [];
		if ($scope->isInTrait()) {
			$traitReflection = $scope->getTraitReflection();
			if ($traitReflection->getFileName() !== null) {
				$traitFilePath = $traitReflection->getFileName();
			}
		}
		if (is_string($ruleError)) {
			$message = $ruleError;
		} else {
			$message = $ruleError->getMessage();
			if (
				$ruleError instanceof LineRuleError
				&& $ruleError->getLine() !== -1
			) {
				$line = $ruleError->getLine();
			}
			if (
				$ruleError instanceof FileRuleError
				&& $ruleError->getFile() !== ''
			) {
				$fileName = $ruleError->getFileDescription();
				$filePath = $ruleError->getFile();
				$traitFilePath = null;
			}

			if ($ruleError instanceof TipRuleError) {
				$tip = $ruleError->getTip();
			}

			if ($ruleError instanceof IdentifierRuleError) {
				$identifier = $ruleError->getIdentifier();
			}

			if ($ruleError instanceof MetadataRuleError) {
				$metadata = $ruleError->getMetadata();
			}

			if ($ruleError instanceof NonIgnorableRuleError) {
				$canBeIgnored = false;
			}
		}
		return new Error(
			$message,
			$fileName,
			$line,
			$canBeIgnored,
			$filePath,
			$traitFilePath,
			$tip,
			$nodeLine,
			$nodeType,
			$identifier,
			$metadata,
		);
	}

}

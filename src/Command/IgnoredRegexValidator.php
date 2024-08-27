<?php declare(strict_types = 1);

namespace PHPStan\Command;

use Hoa\Compiler\Llk\Parser;
use Hoa\Compiler\Llk\TreeNode;
use Hoa\Exception\Exception;
use Nette\Utils\Strings;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\PhpDocParser\Parser\ParserException;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;
use function count;
use function str_contains;
use function str_starts_with;
use function strrpos;
use function substr;

final class IgnoredRegexValidator
{

	public function __construct(
		private Parser $parser,
		private TypeStringResolver $typeStringResolver,
	)
	{
	}

	public function validate(string $regex): IgnoredRegexValidatorResult
	{
		$regex = $this->removeDelimiters($regex);

		try {
			/** @var TreeNode $ast */
			$ast = $this->parser->parse($regex);
		} catch (Exception $e) {
			if (str_starts_with($e->getMessage(), 'Unexpected token "|" (alternation) at line 1')) {
				return new IgnoredRegexValidatorResult([], false, true, '||', '\|\|');
			}
			if (
				str_contains($regex, '()')
				&& str_starts_with($e->getMessage(), 'Unexpected token ")" (_capturing) at line 1')
			) {
				return new IgnoredRegexValidatorResult([], false, true, '()', '\(\)');
			}
			return new IgnoredRegexValidatorResult([], false, false);
		}

		return new IgnoredRegexValidatorResult(
			$this->getIgnoredTypes($ast),
			$this->hasAnchorsInTheMiddle($ast),
			false,
		);
	}

	/**
	 * @return array<string, string>
	 */
	private function getIgnoredTypes(TreeNode $ast): array
	{
		/** @var TreeNode|null $alternation */
		$alternation = $ast->getChild(0);
		if ($alternation === null) {
			return [];
		}

		if ($alternation->getId() !== '#alternation') {
			return [];
		}

		$types = [];
		foreach ($alternation->getChildren() as $child) {
			$text = $this->getText($child);
			if ($text === null) {
				continue;
			}

			$matches = Strings::match($text, '#^([a-zA-Z0-9]+)[,]?\s*#');
			if ($matches === null) {
				continue;
			}

			try {
				$type = $this->typeStringResolver->resolve($matches[1], null);
			} catch (ParserException) {
				continue;
			}

			if ($type instanceof ObjectType) {
				continue;
			}

			$typeDescription = $type->describe(VerbosityLevel::typeOnly());

			if ($typeDescription !== $matches[1]) {
				continue;
			}

			$types[$typeDescription] = $text;
		}

		return $types;
	}

	private function removeDelimiters(string $regex): string
	{
		$delimiter = substr($regex, 0, 1);
		$endDelimiterPosition = strrpos($regex, $delimiter);
		if ($endDelimiterPosition === false) {
			throw new ShouldNotHappenException();
		}

		return substr($regex, 1, $endDelimiterPosition - 1);
	}

	private function getText(TreeNode $treeNode): ?string
	{
		if ($treeNode->getId() === 'token') {
			return $treeNode->getValueValue();
		}

		if ($treeNode->getId() === '#concatenation') {
			$fullText = '';
			foreach ($treeNode->getChildren() as $child) {
				$text = $this->getText($child);
				if ($text === null) {
					continue;
				}

				$fullText .= $text;
			}

			if ($fullText === '') {
				return null;
			}

			return $fullText;
		}

		return null;
	}

	private function hasAnchorsInTheMiddle(TreeNode $ast): bool
	{
		if ($ast->getId() === 'token') {
			$valueArray = $ast->getValue();

			return $valueArray['token'] === 'anchor' && $valueArray['value'] === '$';
		}
		$childrenCount = count($ast->getChildren());
		foreach ($ast->getChildren() as $i => $child) {
			$has = $this->hasAnchorsInTheMiddle($child);
			if (
				$has
				&& ($ast->getId() !== '#concatenation' || $i !== $childrenCount - 1)
			) {
				return true;
			}
		}

		return false;
	}

}

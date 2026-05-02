<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function preg_match;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<InClassMethodNode>
 */
final class InvalidInheritDocTagRule implements Rule
{

	private const INLINE_INHERIT_DOC_REGEX = '~(?<![a-zA-Z0-9])\{@inheritDoc\b[^}]*\}~i';

	public function __construct(
		private Lexer $phpDocLexer,
		private PhpDocParser $phpDocParser,
	)
	{
	}

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$docComment = $node->getOriginalNode()->getDocComment();
		if ($docComment === null) {
			return [];
		}

		$tokens = new TokenIterator($this->phpDocLexer->tokenize($docComment->getText()));
		$phpDocNode = $this->phpDocParser->parse($tokens);

		$inheritDocTagName = null;
		foreach ($phpDocNode->getTags() as $tag) {
			if (strtolower($tag->name) !== '@inheritdoc') {
				continue;
			}

			$inheritDocTagName = $tag->name;
			break;
		}

		if ($inheritDocTagName === null) {
			foreach ($phpDocNode->children as $child) {
				if (!$child instanceof PhpDocTextNode) {
					continue;
				}

				if (preg_match(self::INLINE_INHERIT_DOC_REGEX, $child->text, $matches) !== 1) {
					continue;
				}

				$inheritDocTagName = $matches[0];
				break;
			}
		}

		if ($inheritDocTagName === null) {
			return [];
		}

		$inheritanceClass = $scope->isInTrait() ? $scope->getTraitReflection() : $node->getClassReflection();
		$methodName = $node->getMethodReflection()->getName();

		if ($this->hasInheritablePhpDoc($inheritanceClass, $methodName)) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'PHPDoc tag %s on method %s::%s() refers to a parent method that does not have a PHPDoc.',
				$inheritDocTagName,
				$inheritanceClass->getDisplayName(),
				$methodName,
			))->identifier('phpDoc.invalidInheritDoc')->build(),
		];
	}

	private function hasInheritablePhpDoc(ClassReflection $classReflection, string $methodName): bool
	{
		$parent = $classReflection->getParentClass();
		if ($parent !== null && $this->parentHasPhpDocForMethod($parent, $methodName)) {
			return true;
		}

		foreach ($classReflection->getImmediateInterfaces() as $interface) {
			if ($this->parentHasPhpDocForMethod($interface, $methodName)) {
				return true;
			}
		}

		foreach ($classReflection->getTraits() as $trait) {
			if ($this->parentHasPhpDocForMethod($trait, $methodName)) {
				return true;
			}
		}

		return false;
	}

	private function parentHasPhpDocForMethod(ClassReflection $parent, string $methodName): bool
	{
		if (!$parent->hasNativeMethod($methodName)) {
			return false;
		}

		$parentMethod = $parent->getNativeMethod($methodName);
		if ($parentMethod->isPrivate()) {
			return false;
		}

		return $parentMethod->getResolvedPhpDoc() !== null;
	}

}

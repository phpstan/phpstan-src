<?php declare(strict_types = 1);

namespace PHPStan\ApiGen;

use ApiGen\Info\ClassLikeInfo;
use ApiGen\Info\FunctionInfo;
use ApiGen\Info\MemberInfo;
use ApiGen\Info\MethodInfo;
use Nette\Utils\Strings;
use PhpParser\Node;
use PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use function substr;

class Filter extends \ApiGen\Analyzer\Filter
{

	public function filterClassLikeNode(Node\Stmt\ClassLike $node): bool
	{
		$name = $node->namespacedName->toString();
		if (Strings::startsWith($name, 'PhpParser\\')) {
			return true;
		}

		if (!Strings::startsWith($name, 'PHPStan\\')) {
			return false;
		}

		if (Strings::startsWith($name, 'PHPStan\\PhpDocParser\\')) {
			return true;
		}

		if (Strings::startsWith($name, 'PHPStan\\BetterReflection\\')) {
			return true;
		}

		if ($this->hasApiTag($node)) {
			return true;
		}

		foreach ($node->getMethods() as $method) {
			if ($this->hasApiTag($method)) {
				return true;
			}
		}

		return false;
	}

	public function filterClassLikeTags(array $tags): bool
	{
		return parent::filterClassLikeTags($tags);
	}

	public function filterClassLikeInfo(ClassLikeInfo $info): bool
	{
		return parent::filterClassLikeInfo($info);
	}

	public function filterFunctionNode(Node\Stmt\Function_ $node): bool
	{
		$name = $node->namespacedName->toString();
		if (!Strings::startsWith($name, 'PHPStan\\')) {
			return false;
		}

		return $this->hasApiTag($node);
	}

	public function filterFunctionTags(array $tags): bool
	{
		return parent::filterFunctionTags($tags);
	}

	public function filterFunctionInfo(FunctionInfo $info): bool
	{
		return parent::filterFunctionInfo($info);
	}

	public function filterConstantNode(Node\Stmt\ClassConst $node): bool
	{
		return parent::filterConstantNode($node);
	}

	public function filterPropertyNode(Node\Stmt\Property $node): bool
	{
		return parent::filterPropertyNode($node);
	}

	public function filterPromotedPropertyNode(Node\Param $node): bool
	{
		return parent::filterPromotedPropertyNode($node);
	}

	public function filterMethodNode(Node\Stmt\ClassMethod $node): bool
	{
		return parent::filterMethodNode($node);
	}

	public function filterEnumCaseNode(Node\Stmt\EnumCase $node): bool
	{
		return parent::filterEnumCaseNode($node);
	}

	public function filterMemberTags(array $tags): bool
	{
		return parent::filterMemberTags($tags);
	}

	public function filterMemberInfo(ClassLikeInfo $classLike, MemberInfo $member): bool
	{
		$className = $classLike->name->full;
		if (Strings::startsWith($className, 'PhpParser\\')) {
			return true;
		}
		if (Strings::startsWith($className, 'PHPStan\\PhpDocParser\\')) {
			return true;
		}

		if (Strings::startsWith($className, 'PHPStan\\BetterReflection\\')) {
			return true;
		}
		if (!$member instanceof MethodInfo) {
			return !Strings::startsWith($className, 'PHPStan\\');
		}

		if (!Strings::startsWith($className, 'PHPStan\\')) {
			return false;
		}

		if (isset($classLike->tags['api'])) {
			return true;
		}

		return isset($member->tags['api']);
	}

	private function hasApiTag(Node $node): bool
	{
		$classDoc = $this->extractPhpDoc($node);
		$tags = $this->extractTags($classDoc);

		return isset($tags['api']);
	}

	private function extractPhpDoc(Node $node): PhpDocNode
	{
		return $node->getAttribute('phpDoc') ?? new PhpDocNode([]);
	}

	/**
	 * @return PhpDocTagValueNode[][] indexed by [tagName][]
	 */
	private function extractTags(PhpDocNode $node): array
	{
		$tags = [];

		foreach ($node->getTags() as $tag) {
			if ($tag->value instanceof InvalidTagValueNode) {
				continue;
			}

			$tags[substr($tag->name, 1)][] = $tag->value;
		}

		return $tags;
	}

}

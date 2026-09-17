<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;
use function in_array;

/**
 * Where an `include`/`require` path ends up depends on the working directory, on the `include_path`
 * setting and on the registered stream wrappers, all of which the analysed file can change at
 * runtime. This records, on every `Include_` node, the calls doing that which appear before it in
 * the same file, so that IncludedFilePathResolver can follow them instead of assuming the state of
 * PHPStan's own process.
 */
#[AutowiredService]
final class IncludePathChangingCallsVisitor extends NodeVisitorAbstract
{

	/** Holds a `list<array{string, Node\Expr\FuncCall}>`: the lowercased function name and the call. */
	public const ATTRIBUTE_NAME = 'includePathChangingCalls';

	public const CHDIR = 'chdir';

	public const SET_INCLUDE_PATH = 'set_include_path';

	public const STREAM_WRAPPER_REGISTER = 'stream_wrapper_register';

	public const INI_SET = 'ini_set';

	public const INI_ALTER = 'ini_alter';

	private const FUNCTION_NAMES = [
		self::CHDIR,
		self::SET_INCLUDE_PATH,
		self::STREAM_WRAPPER_REGISTER,
		self::INI_SET,
		self::INI_ALTER,
	];

	/** @var list<array{string, Node\Expr\FuncCall}> */
	private array $calls = [];

	#[Override]
	public function beforeTraverse(array $nodes): ?array
	{
		$this->calls = [];

		return null;
	}

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Expr\Include_) {
			if ($this->calls !== []) {
				$node->setAttribute(self::ATTRIBUTE_NAME, $this->calls);
			}

			return null;
		}

		if (
			!$node instanceof Node\Expr\FuncCall
			|| !$node->name instanceof Node\Name
			|| $node->isFirstClassCallable()
		) {
			return null;
		}

		$functionName = $node->name->toLowerString();
		if (!in_array($functionName, self::FUNCTION_NAMES, true)) {
			return null;
		}

		$this->calls[] = [$functionName, $node];

		return null;
	}

}

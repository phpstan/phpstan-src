<?php declare(strict_types = 1);

namespace PHPStan\Rules\Debug;

use Nette\Utils\Strings;
use PhpParser\Node;
use PHPStan\Node\AfterStmtNode;
use PHPStan\Parser\Parser;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\ParserErrorsException;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function in_array;
use function sprintf;

/**
 * @implements Rule<AfterStmtNode>
 */
class TraceRule implements Rule
{

	public function __construct(
		private Lexer $phpDocLexer,
		private PhpDocParser $phpDocParser,
		private Parser $phpParser,
	)
	{
	}

	public function getNodeType(): string
	{
		return AfterStmtNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$docComment = $node->getDocComment();
		if ($docComment === null) {
			return [];
		}

		$phpDocString = $docComment->getText();

		if (!Strings::match($phpDocString, '(@(phpstan|psalm)-trace)')) {
			return [];
		}

		$tokens = new TokenIterator($this->phpDocLexer->tokenize($phpDocString));
		$phpDocNode = $this->phpDocParser->parse($tokens);

		$errors = [];
		foreach ($phpDocNode->getTags() as $phpDocTag) {
			if (!in_array($phpDocTag->name, ['@phpstan-trace', '@psalm-trace'], true)) {
				continue;
			}

			try {
				$stmts = $this->phpParser->parseString('<?php ' . $phpDocTag->value . ';');
			} catch (ParserErrorsException) {
				continue;
			}

			if (count($stmts) !== 1 || !($stmts[0] instanceof Node\Stmt\Expression)) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(
				sprintf(
					'Dumped type: %s',
					$scope->getType($stmts[0]->expr)->describe(VerbosityLevel::precise()),
				),
			)->nonIgnorable()->identifier('phpstan.dumpType')->build();
		}

		return $errors;
	}

}

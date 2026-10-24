<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Internal\LruCache;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;

#[AutowiredService]
final class PhpDocStringResolver
{

	/**
	 * FileTypeMapper and the rules that validate PHPDocs parse the same strings, often one right after
	 * another. The parsed nodes are not modified afterwards, so equal strings can share one node.
	 *
	 * @var LruCache<PhpDocNode>
	 */
	private LruCache $parsedPhpDocs;

	public function __construct(
		private Lexer $phpDocLexer,
		private PhpDocParser $phpDocParser,
		#[AutowiredParameter(ref: '%cache.parsedPhpDocCountMax%')]
		int $parsedPhpDocCountMax,
	)
	{
		$this->parsedPhpDocs = new LruCache($parsedPhpDocCountMax);
	}

	public function resolve(string $phpDocString): PhpDocNode
	{
		$phpDocNode = $this->parsedPhpDocs->get($phpDocString);
		if ($phpDocNode !== null) {
			return $phpDocNode;
		}

		$tokens = new TokenIterator($this->phpDocLexer->tokenize($phpDocString));
		$phpDocNode = $this->phpDocParser->parse($tokens);
		$tokens->consumeTokenType(Lexer::TOKEN_END); // @phpstan-ignore missingType.checkedException

		$this->parsedPhpDocs->set($phpDocString, $phpDocNode, 0);

		return $phpDocNode;
	}

}

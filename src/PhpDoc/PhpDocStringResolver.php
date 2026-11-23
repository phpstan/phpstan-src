<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Internal\LruCache;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;

/**
 * The same PHPDoc text is asked for several times in a row: by FileTypeMapper
 * when it maps the file's name scopes and again when it resolves the PHPDoc,
 * and by each rule that reads the tags of a statement's PHPDoc. The parsed
 * nodes are remembered, so the text is tokenized and parsed once. Callers
 * must not modify the returned node.
 */
#[AutowiredService]
final class PhpDocStringResolver
{

	private const CACHED_NODES_LIMIT = 2048;

	/** @var LruCache<PhpDocNode> PHPDoc text => parsed nodes */
	private LruCache $cachedNodes;

	public function __construct(private Lexer $phpDocLexer, private PhpDocParser $phpDocParser)
	{
		$this->cachedNodes = new LruCache(self::CACHED_NODES_LIMIT);
	}

	public function resolve(string $phpDocString): PhpDocNode
	{
		$cachedNode = $this->cachedNodes->get($phpDocString);
		if ($cachedNode !== null) {
			return $cachedNode;
		}

		$tokens = new TokenIterator($this->phpDocLexer->tokenize($phpDocString));
		$phpDocNode = $this->phpDocParser->parse($tokens);
		$tokens->consumeTokenType(Lexer::TOKEN_END); // @phpstan-ignore missingType.checkedException

		$this->cachedNodes->set($phpDocString, $phpDocNode, 0);

		return $phpDocNode;
	}

}

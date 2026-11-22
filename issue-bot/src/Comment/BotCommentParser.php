<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Comment;

use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Parser\MarkdownParser;
use Nette\Utils\Strings;
use function count;
use function in_array;

class BotCommentParser
{

	/** Second word of the diff code block info string in comments about results with bleeding edge enabled. */
	public const BLEEDING_EDGE_INFO_WORD = 'bleeding-edge';

	public function __construct(private MarkdownParser $docParser)
	{
	}

	public function parse(string $text): BotCommentParserResult
	{
		$document = $this->docParser->parse($text);
		$walker = $document->walker();
		$hashes = [];
		$diffs = [];
		$bleedingEdge = false;
		while ($event = $walker->next()) { // @phpstan-ignore while.condNotBoolean
			if (!$event->isEntering()) {
				continue;
			}

			$node = $event->getNode();
			if ($node instanceof Link) {
				$url = $node->getUrl();
				$match = Strings::match($url, '/^https:\/\/phpstan\.org\/r\/([0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12})$/i');
				if ($match === null) {
					continue;
				}

				$hashes[] = $match[1];
				continue;
			}

			if (!($node instanceof FencedCode)) {
				continue;
			}

			$infoWords = $node->getInfoWords();
			if ($infoWords[0] !== 'diff') {
				continue;
			}

			$diffs[] = $node->getLiteral();
			$bleedingEdge = in_array(self::BLEEDING_EDGE_INFO_WORD, $infoWords, true);
		}

		if (count($hashes) !== 1) {
			throw new BotCommentParserException();
		}

		if (count($diffs) !== 1) {
			throw new BotCommentParserException();
		}

		return new BotCommentParserResult($hashes[0], $diffs[0], $bleedingEdge);
	}

}

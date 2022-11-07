<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Comment;

use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Parser\MarkdownParser;
use Nette\Utils\Strings;
use function count;

class BotCommentParser
{

	public function __construct(private MarkdownParser $docParser)
	{
	}

	public function parse(string $text): BotCommentParserResult
	{
		$document = $this->docParser->parse($text);
		$walker = $document->walker();
		$hashes = [];
		$diffs = [];
		while ($event = $walker->next()) {
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

			if ($node->getInfo() !== 'diff') {
				continue;
			}

			$diffs[] = $node->getLiteral();
		}

		if (count($hashes) !== 1) {
			throw new BotCommentParserException();
		}

		if (count($diffs) !== 1) {
			throw new BotCommentParserException();
		}

		return new BotCommentParserResult($hashes[0], $diffs[0]);
	}

}

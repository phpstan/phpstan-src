<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Comment;

use Github\Api\Issue;
use Github\Client;
use Nette\Utils\Strings;
use PHPStan\IssueBot\Playground\PlaygroundExample;
use function array_merge;
use function count;

class IssueCommentDownloader
{

	public function __construct(
		private Client $githubClient,
		private BotCommentParser $botCommentParser,
	)
	{
	}

	/**
	 * @return list<Comment>
	 */
	public function getComments(int $issueNumber): array
	{
		$comments = [];
		foreach ($this->downloadComments($issueNumber) as $issueComment) {
			$commentExamples = $this->searchBody($issueComment['body']);
			if (count($commentExamples) === 0) {
				continue;
			}

			if ($issueComment['user']['login'] === 'phpstan-bot') {
				$parserResult = $this->botCommentParser->parse($issueComment['body']);
				if (count($commentExamples) !== 1 || $commentExamples[0]->getHash() !== $parserResult->getHash()) {
					throw new BotCommentParserException();
				}

				$comments[] = new BotComment($issueComment['body'], $commentExamples[0], $parserResult->getDiff());
				continue;
			}

			$comments[] = new Comment($issueComment['user']['login'], $issueComment['body'], $commentExamples);
		}

		return $comments;
	}

	/**
	 * @return mixed[]
	 */
	private function downloadComments(int $issueNumber): array
	{
		$page = 1;

		/** @var Issue $api */
		$api = $this->githubClient->api('issue');

		$comments = [];
		while (true) {
			$newComments = $api->comments()->all('phpstan', 'phpstan', $issueNumber, [
				'page' => $page,
				'per_page' => 100,
			]);
			$comments = array_merge($comments, $newComments);
			if (count($newComments) < 100) {
				break;
			}
			$page++;
		}

		return $comments;
	}

	/**
	 * @return list<PlaygroundExample>
	 */
	public function searchBody(string $text): array
	{
		$matches = Strings::matchAll($text, '/https:\/\/phpstan\.org\/r\/([0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12})/i');

		$examples = [];

		foreach ($matches as [$url, $hash]) {
			$examples[] = new PlaygroundExample($url, $hash);
		}

		return $examples;
	}

}

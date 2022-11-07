<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Console;

use DateTimeImmutable;
use Exception;
use Github\Client;
use Nette\Utils\Strings;
use PHPStan\IssueBot\Comment\BotComment;
use PHPStan\IssueBot\Comment\BotCommentParser;
use PHPStan\IssueBot\Comment\BotCommentParserException;
use PHPStan\IssueBot\Comment\Comment;
use PHPStan\IssueBot\Issue\Issue;
use PHPStan\IssueBot\Issue\IssueCache;
use PHPStan\IssueBot\Playground\PlaygroundExample;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function array_merge;
use function count;
use function file_get_contents;
use function file_put_contents;
use function is_file;
use function serialize;
use function sprintf;
use function unserialize;

class DownloadCommand extends Command
{

	public function __construct(
		private Client $githubClient,
		private BotCommentParser $botCommentParser,
		private string $issueCachePath,
	)
	{
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->setName('download');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->getIssues($output);
		return 0;
	}

	/**
	 * @return Issue[]
	 */
	private function getIssues(OutputInterface $output): array
	{
		/** @var \Github\Api\Issue $api */
		$api = $this->githubClient->api('issue');

		$cache = $this->loadIssueCache();
		if ($cache === null) {
			$output->writeln('Downloading everything fresh');
		} else {
			$output->writeln('Downloading only issues updated since: ' . $cache->getDate()->format(DateTimeImmutable::ATOM));
		}

		$newDate = new DateTimeImmutable();

		$page = 1;
		$issues = [];
		foreach (['feature-request', 'bug'] as $label) {
			while (true) {
				$parameters = [
					'state' => 'open',
					'labels' => $label,
					'page' => $page,
					'per_page' => 100,
					'sort' => 'created',
					'direction' => 'desc',
				];
				if ($cache !== null) {
					$parameters['since'] = $cache->getDate()->format(DateTimeImmutable::ATOM);
				}
				$newIssues = $api->all('phpstan', 'phpstan', $parameters);
				$issues = array_merge($issues, $newIssues);
				if (count($newIssues) < 100) {
					break;
				}

				$page++;
			}
		}

		$issueObjects = [];
		if ($cache !== null) {
			$issueObjects = $cache->getIssues();
		}
		foreach ($issues as $issue) {
			if ($cache !== null) {
				$output->writeln(sprintf('Downloading issue #%d', $issue['number']));
			}
			$comments = [];
			$issueExamples = $this->searchBody($issue['body']);
			if (count($issueExamples) > 0) {
				$comments[] = new Comment($issue['user']['login'], $issue['body'], $issueExamples);
			}

			foreach ($this->getComments($issue['number']) as $issueComment) {
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

			$issueObjects[(int) $issue['number']] = new Issue(
				$issue['number'],
				$comments,
			);
		}

		$this->saveIssueCache(new IssueCache($newDate, $issueObjects));

		return $issueObjects;
	}

	private function loadIssueCache(): ?IssueCache
	{
		if (!is_file($this->issueCachePath)) {
			return null;
		}

		$contents = file_get_contents($this->issueCachePath);
		if ($contents === false) {
			throw new Exception('Read unsuccessful');
		}

		return unserialize($contents);
	}

	private function saveIssueCache(IssueCache $cache): void
	{
		$result = file_put_contents($this->issueCachePath, serialize($cache));
		if ($result === false) {
			throw new Exception('Write unsuccessful');
		}
	}

	/**
	 * @return list<PlaygroundExample>
	 */
	private function searchBody(string $text): array
	{
		$matches = Strings::matchAll($text, '/https:\/\/phpstan\.org\/r\/([0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12})/i');

		$examples = [];

		foreach ($matches as [$url, $hash]) {
			$examples[] = new PlaygroundExample($url, $hash);
		}

		return $examples;
	}

	/**
	 * @return mixed[]
	 */
	private function getComments(int $issueNumber): array
	{
		$page = 1;

		/** @var \Github\Api\Issue $api */
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

}

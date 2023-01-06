<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Console;

use DateTimeImmutable;
use Exception;
use Github\Client;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use PHPStan\IssueBot\Comment\BotComment;
use PHPStan\IssueBot\Comment\BotCommentParser;
use PHPStan\IssueBot\Comment\BotCommentParserException;
use PHPStan\IssueBot\Comment\Comment;
use PHPStan\IssueBot\Issue\Issue;
use PHPStan\IssueBot\Issue\IssueCache;
use PHPStan\IssueBot\Playground\PlaygroundCache;
use PHPStan\IssueBot\Playground\PlaygroundClient;
use PHPStan\IssueBot\Playground\PlaygroundExample;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function array_chunk;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function ceil;
use function count;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function in_array;
use function is_file;
use function serialize;
use function unserialize;

class DownloadCommand extends Command
{

	public function __construct(
		private Client $githubClient,
		private BotCommentParser $botCommentParser,
		private PlaygroundClient $playgroundClient,
		private string $issueCachePath,
		private string $playgroundCachePath,
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
		$issues = $this->getIssues();

		$playgroundCache = $this->loadPlaygroundCache();
		if ($playgroundCache === null) {
			$cachedResults = [];
		} else {
			$cachedResults = $playgroundCache->getResults();
		}

		$unusedCachedResults = $cachedResults;

		$deduplicatedExamples = [];
		foreach ($issues as $issue) {
			foreach ($issue->getComments() as $comment) {
				if ($comment instanceof BotComment) {
					continue;
				}
				foreach ($comment->getPlaygroundExamples() as $example) {
					$deduplicatedExamples[$example->getHash()] = $example;
				}
			}
		}

		$hashes = array_keys($deduplicatedExamples);
		foreach ($hashes as $hash) {
			if (array_key_exists($hash, $cachedResults)) {
				unset($unusedCachedResults[$hash]);
				continue;
			}

			$cachedResults[$hash] = $this->playgroundClient->getResult($hash);
		}

		foreach (array_keys($unusedCachedResults) as $hash) {
			unset($cachedResults[$hash]);
		}

		$this->savePlaygroundCache(new PlaygroundCache($cachedResults));

		$chunkSize = (int) ceil(count($hashes) / 20);
		if ($chunkSize < 1) {
			throw new Exception('Chunk size less than 1');
		}

		$matrix = [];
		foreach ([70100, 70200, 70300, 70400, 80000, 80100, 80200] as $phpVersion) {
			$phpVersionHashes = [];
			foreach ($cachedResults as $hash => $result) {
				$resultPhpVersions = array_keys($result->getVersionedErrors());
				if ($resultPhpVersions === [70400]) {
					$resultPhpVersions = [70100, 70200, 70300, 70400, 80000];
				}

				if (!in_array(80100, $resultPhpVersions, true)) {
					$resultPhpVersions[] = 80100;
				}
				if (!in_array(80200, $resultPhpVersions, true)) {
					$resultPhpVersions[] = 80200;
				}

				if (!in_array($phpVersion, $resultPhpVersions, true)) {
					continue;
				}
				$phpVersionHashes[] = $hash;
			}
			$chunkSize = (int) ceil(count($phpVersionHashes) / 18);
			if ($chunkSize < 1) {
				throw new Exception('Chunk size less than 1');
			}
			$chunks = array_chunk($phpVersionHashes, $chunkSize);
			foreach ($chunks as $chunk) {
				$matrix[] = [
					'phpVersion' => $phpVersion,
					'playgroundExamples' => implode(',', $chunk),
				];
			}
		}

		$output->writeln(Json::encode(['include' => $matrix]));

		return 0;
	}

	/**
	 * @return Issue[]
	 */
	private function getIssues(): array
	{
		/** @var \Github\Api\Issue $api */
		$api = $this->githubClient->api('issue');

		$cache = $this->loadIssueCache();
		$newDate = new DateTimeImmutable();

		$issues = [];
		foreach (['feature-request', 'bug'] as $label) {
			$page = 1;
			while (true) {
				$parameters = [
					'labels' => $label,
					'page' => $page,
					'per_page' => 100,
					'sort' => 'created',
					'direction' => 'desc',
				];
				if ($cache !== null) {
					$parameters['state'] = 'all';
					$parameters['since'] = $cache->getDate()->format(DateTimeImmutable::ATOM);
				} else {
					$parameters['state'] = 'open';
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
			if ($issue['state'] === 'closed') {
				unset($issueObjects[$issue['number']]);
				continue;
			}
			$comments = [];
			$issueExamples = $this->searchBody($issue['body'], $issue['user']['login']);
			if (count($issueExamples) > 0) {
				$comments[] = new Comment($issue['user']['login'], $issue['body'], $issueExamples);
			}

			foreach ($this->getComments($issue['number']) as $issueComment) {
				$commentExamples = $this->searchBody($issueComment['body'], $issueComment['user']['login']);
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

	private function loadPlaygroundCache(): ?PlaygroundCache
	{
		if (!is_file($this->playgroundCachePath)) {
			return null;
		}

		$contents = file_get_contents($this->playgroundCachePath);
		if ($contents === false) {
			throw new Exception('Read unsuccessful');
		}

		return unserialize($contents);
	}

	private function savePlaygroundCache(PlaygroundCache $cache): void
	{
		$result = file_put_contents($this->playgroundCachePath, serialize($cache));
		if ($result === false) {
			throw new Exception('Write unsuccessful');
		}
	}

	/**
	 * @return list<PlaygroundExample>
	 */
	private function searchBody(string $text, string $user): array
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

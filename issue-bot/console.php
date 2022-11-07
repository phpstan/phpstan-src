#!/usr/bin/env php
<?php declare(strict_types = 1);

namespace PHPStan\IssueBot;

require_once __DIR__ . '/vendor/autoload.php';

use Github\AuthMethod;
use Github\Client;
use Github\HttpClient\Builder;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Parser\MarkdownParser;
use PHPStan\IssueBot\Comment\BotCommentParser;
use PHPStan\IssueBot\Console\DownloadCommand;
use PHPStan\IssueBot\GitHub\RateLimitPlugin;
use PHPStan\IssueBot\GitHub\RequestCounterPlugin;
use Symfony\Component\Console\Application;

(static function (): void {
	$token = $_SERVER['GITHUB_PAT'];

	$rateLimitPlugin = new RateLimitPlugin();
	$requestCounter = new RequestCounterPlugin();

	$httpBuilder = new Builder();
	$httpBuilder->addPlugin($rateLimitPlugin);
	$httpBuilder->addPlugin($requestCounter);

	$client = new Client($httpBuilder);
	$client->authenticate($token, AuthMethod::ACCESS_TOKEN);
	$rateLimitPlugin->setClient($client);

	$markdownEnvironment = new Environment();
	$markdownEnvironment->addExtension(new CommonMarkCoreExtension());
	$markdownEnvironment->addExtension(new GithubFlavoredMarkdownExtension());
	$botCommentParser = new BotCommentParser(new MarkdownParser($markdownEnvironment));

	$application = new Application();
	$application->add(new DownloadCommand($client, $botCommentParser, __DIR__ . '/tmp/issueCache.tmp'));

	$application->setCatchExceptions(false);
	$application->run();
})();

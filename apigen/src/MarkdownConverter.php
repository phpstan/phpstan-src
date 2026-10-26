<?php declare(strict_types = 1);

namespace PHPStan\ApiGen;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Inline\HtmlInline;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter as BaseMarkdownConverter;

/** GitHub-flavored Markdown converter that escapes generic types written in PHPDoc descriptions. */
final class MarkdownConverter extends BaseMarkdownConverter
{

	public function __construct()
	{
		$environment = new Environment();
		$environment->addExtension(new CommonMarkCoreExtension());
		$environment->addExtension(new GithubFlavoredMarkdownExtension());
		$environment->addRenderer(HtmlInline::class, new HtmlInlineRenderer(), 100);

		parent::__construct($environment);
	}

}

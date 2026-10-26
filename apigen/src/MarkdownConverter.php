<?php declare(strict_types = 1);

namespace PHPStan\ApiGen;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Inline\HtmlInline;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter as BaseMarkdownConverter;
use League\CommonMark\Output\RenderedContentInterface;
use function str_replace;

/** GitHub-flavored Markdown converter for PHPDoc descriptions that escapes generic types and keeps paragraphs apart. */
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

	public function convert(string $input): RenderedContentInterface
	{
		// ApiGen joins description nodes with a space, which would indent the paragraph
		// after each separator and break code fences that start it.
		return parent::convert(str_replace(' ' . PhpDocResolver::PARAGRAPH_SEPARATOR . ' ', PhpDocResolver::PARAGRAPH_SEPARATOR, $input));
	}

}

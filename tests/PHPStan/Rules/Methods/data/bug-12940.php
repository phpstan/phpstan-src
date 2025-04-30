<?php

namespace Bug12940;

class GeneralUtility
{
	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return T
	 */
	public static function makeInstance(string $className, mixed ...$args): object
	{
		return new $className(...$args);
	}
}

class PageRenderer
{
	public function setTemplateFile(string $path): void
	{
	}

	public function setLanguage(string $lang): void
	{
	}
}

class TypoScriptFrontendController
{

	protected ?PageRenderer $pageRenderer = null;

	public function initializePageRenderer(): void
	{
		if ($this->pageRenderer !== null) {
			return;
		}
		$this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
		$this->pageRenderer->setTemplateFile('EXT:frontend/Resources/Private/Templates/MainPage.html');
		$this->pageRenderer->setLanguage('DE');
	}
}

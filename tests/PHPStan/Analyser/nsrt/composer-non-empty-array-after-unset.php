<?php

namespace ComposerNonEmptyArrayAfterUnset;

use function PHPStan\Testing\assertType;

class Foo
{

	/** @var mixed[] */
	private $config;

	public function doFoo()
	{
		if (!empty($this->config['authors'])) {
			assertType("mixed~(0|0.0|''|'0'|array{}|false|null)", $this->config['authors']);
			foreach ($this->config['authors'] as $key => $author) {
				assertType("iterable", $this->config['authors']);
				if (!is_array($author)) {
					unset($this->config['authors'][$key]);
					assertType("iterable", $this->config['authors']);
					continue;
				}
				foreach (['homepage', 'email', 'name', 'role'] as $authorData) {
					if (isset($author[$authorData]) && !is_string($author[$authorData])) {
						unset($this->config['authors'][$key][$authorData]);
					}
				}
				if (isset($author['homepage'])) {
					unset($this->config['authors'][$key]['homepage']);
				}
				if (isset($author['email']) && !filter_var($author['email'], FILTER_VALIDATE_EMAIL)) {
					unset($this->config['authors'][$key]['email']);
				}
				if (empty($this->config['authors'][$key])) {
					assertType("iterable", $this->config['authors']);
					unset($this->config['authors'][$key]);
					assertType("iterable", $this->config['authors']);
				}
				assertType("iterable", $this->config['authors']);
			}
			assertType("mixed~(0|0.0|''|'0'|array{}|false|null)", $this->config['authors']);
		}
	}

}

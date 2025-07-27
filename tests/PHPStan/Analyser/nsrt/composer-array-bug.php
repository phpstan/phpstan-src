<?php

namespace ComposerArrayBug;

use function PHPStan\Testing\assertType;

class Foo
{

	/** @var mixed[] */
	private $config;

	/** @var string[] */
	private $errors;

	public function doFoo(): void
	{
		if (!empty($this->config['authors'])) {
			assertType("mixed~(0|0.0|''|'0'|array{}|false|null)", $this->config['authors']);
			foreach ($this->config['authors'] as $key => $author) {
				assertType("iterable", $this->config['authors']);

				if (!is_array($author)) {
					$this->errors[] = 'authors.'.$key.' : should be an array, '.gettype($author).' given';
					assertType("iterable", $this->config['authors']);
					unset($this->config['authors'][$key]);
					assertType("iterable", $this->config['authors']);
					continue;
				}
				assertType("iterable", $this->config['authors']);
				foreach (['homepage', 'email', 'name', 'role'] as $authorData) {
					if (isset($author[$authorData]) && !is_string($author[$authorData])) {
						$this->errors[] = 'authors.'.$key.'.'.$authorData.' : invalid value, must be a string';
						unset($this->config['authors'][$key][$authorData]);
					}
				}
				if (isset($author['homepage'])) {
					assertType("iterable", $this->config['authors']);
					unset($this->config['authors'][$key]['homepage']);
					assertType("iterable", $this->config['authors']);
				}
				if (isset($author['email']) && !filter_var($author['email'], FILTER_VALIDATE_EMAIL)) {
					unset($this->config['authors'][$key]['email']);
				}
				if (empty($this->config['authors'][$key])) {
					unset($this->config['authors'][$key]);
				}
			}

			assertType("non-empty-array&hasOffsetValue('authors', mixed~(0|0.0|''|'0'|array{}|false|null))", $this->config);
			assertType("mixed~(0|0.0|''|'0'|array{}|false|null)", $this->config['authors']);

			if (empty($this->config['authors'])) {
				unset($this->config['authors']);
				assertType("array<mixed~'authors', mixed>", $this->config);
			} else {
				assertType("non-empty-array&hasOffsetValue('authors', mixed~(0|0.0|''|'0'|array{}|false|null))", $this->config);
			}

			assertType("non-empty-array&hasOffsetValue('authors', mixed~(0|0.0|''|'0'|array{}|false|null))", $this->config);
		}
	}

}

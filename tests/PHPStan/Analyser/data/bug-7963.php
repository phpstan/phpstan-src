<?php declare(strict_types = 1);

namespace Bug7963;

interface FieldDescriptionInterface
{
	public const TYPE_ARRAY = 'array';
	public const TYPE_BOOLEAN = 'boolean';
	public const TYPE_DATE = 'date';
	public const TYPE_TIME = 'time';
	public const TYPE_DATETIME = 'datetime';
	public const TYPE_TEXTAREA = 'textarea';
	public const TYPE_EMAIL = 'email';
	public const TYPE_ENUM = 'enum';
	public const TYPE_TRANS = 'trans';
	public const TYPE_STRING = 'string';
	public const TYPE_INTEGER = 'integer';
	public const TYPE_FLOAT = 'float';
	public const TYPE_IDENTIFIER = 'identifier';
	public const TYPE_CURRENCY = 'currency';
	public const TYPE_PERCENT = 'percent';
	public const TYPE_CHOICE = 'choice';
	public const TYPE_URL = 'url';
	public const TYPE_HTML = 'html';
	public const TYPE_MANY_TO_MANY = 'many_to_many';
	public const TYPE_MANY_TO_ONE = 'many_to_one';
	public const TYPE_ONE_TO_MANY = 'one_to_many';
	public const TYPE_ONE_TO_ONE = 'one_to_one';
}

class HelloWorld
{
	/**
	 * @phpstan-return array<array{string, string, mixed, array<string, mixed>}>
	 */
	public function getRenderViewElementTests(): array
	{
		$elements = [
			['<th>Data</th> <td>Example</td>', FieldDescriptionInterface::TYPE_STRING, 'Example', ['safe' => false]],
			['<th>Data</th> <td>Example</td>', FieldDescriptionInterface::TYPE_STRING, 'Example', ['safe' => false]],
			['<th>Data</th> <td>Example</td>', FieldDescriptionInterface::TYPE_TEXTAREA, 'Example', ['safe' => false]],
			[
				'<th>Data</th> <td><time datetime="2013-12-24T10:11:12+00:00" title="2013-12-24T10:11:12+00:00"> December 24, 2013 10:11 </time></td>',
				FieldDescriptionInterface::TYPE_DATETIME,
				new \DateTime('2013-12-24 10:11:12', new \DateTimeZone('Europe/London')), [],
			],
			[
				'<th>Data</th> <td><time datetime="2013-12-24T10:11:12+00:00" title="2013-12-24T10:11:12+00:00"> 24.12.2013 10:11:12 </time></td>',
				FieldDescriptionInterface::TYPE_DATETIME,
				new \DateTime('2013-12-24 10:11:12', new \DateTimeZone('Europe/London')),
				['format' => 'd.m.Y H:i:s'],
			],
			[
				'<th>Data</th> <td><time datetime="2013-12-24T10:11:12+00:00" title="2013-12-24T10:11:12+00:00"> December 24, 2013 18:11 </time></td>',
				FieldDescriptionInterface::TYPE_DATETIME,
				new \DateTime('2013-12-24 10:11:12', new \DateTimeZone('UTC')),
				['timezone' => 'Asia/Hong_Kong'],
			],
			[
				'<th>Data</th> <td><time datetime="2013-12-24" title="2013-12-24"> December 24, 2013 </time></td>',
				FieldDescriptionInterface::TYPE_DATE,
				new \DateTime('2013-12-24 10:11:12', new \DateTimeZone('Europe/London')),
				[],
			],
			[
				'<th>Data</th> <td><time datetime="2013-12-24" title="2013-12-24"> 24.12.2013 </time></td>',
				FieldDescriptionInterface::TYPE_DATE,
				new \DateTime('2013-12-24 10:11:12', new \DateTimeZone('Europe/London')),
				['format' => 'd.m.Y'],
			],
			[
				'<th>Data</th> <td><time datetime="10:11:12+00:00" title="10:11:12+00:00"> 10:11:12 </time></td>',
				FieldDescriptionInterface::TYPE_TIME,
				new \DateTime('2013-12-24 10:11:12', new \DateTimeZone('Europe/London')),
				[],
			],
			[
				'<th>Data</th> <td><time datetime="10:11:12+00:00" title="10:11:12+00:00"> 18:11:12 </time></td>',
				FieldDescriptionInterface::TYPE_TIME,
				new \DateTime('2013-12-24 10:11:12', new \DateTimeZone('UTC')),
				['timezone' => 'Asia/Hong_Kong'],
			],
			['<th>Data</th> <td>10.746135</td>', FieldDescriptionInterface::TYPE_FLOAT, 10.746135, ['safe' => false]],
			['<th>Data</th> <td>5678</td>', FieldDescriptionInterface::TYPE_INTEGER, 5678, ['safe' => false]],
			['<th>Data</th> <td>1074.6135 %</td>', FieldDescriptionInterface::TYPE_PERCENT, 10.746135, []],
			['<th>Data</th> <td>0 %</td>', FieldDescriptionInterface::TYPE_PERCENT, 0, []],
			['<th>Data</th> <td>EUR 10.746135</td>', FieldDescriptionInterface::TYPE_CURRENCY, 10.746135, ['currency' => 'EUR']],
			['<th>Data</th> <td>GBP 51.23456</td>', FieldDescriptionInterface::TYPE_CURRENCY, 51.23456, ['currency' => 'GBP']],
			['<th>Data</th> <td>EUR 0</td>', FieldDescriptionInterface::TYPE_CURRENCY, 0, ['currency' => 'EUR']],
			[
				'<th>Data</th> <td> <ul><li>1&nbsp;=>&nbsp;First</li><li>2&nbsp;=>&nbsp;Second</li></ul> </td>',
				FieldDescriptionInterface::TYPE_ARRAY,
				[1 => 'First', 2 => 'Second'],
				['safe' => false],
			],
			[
				'<th>Data</th> <td> [1&nbsp;=>&nbsp;First, 2&nbsp;=>&nbsp;Second] </td>',
				FieldDescriptionInterface::TYPE_ARRAY,
				[1 => 'First', 2 => 'Second'],
				['safe' => false, 'inline' => true],
			],
			[
				'<th>Data</th> <td><span class="label label-success">yes</span></td>',
				FieldDescriptionInterface::TYPE_BOOLEAN,
				true,
				[],
			],
			[
				'<th>Data</th> <td><span class="label label-danger">yes</span></td>',
				FieldDescriptionInterface::TYPE_BOOLEAN,
				true,
				['inverse' => true],
			],
			['<th>Data</th> <td><span class="label label-danger">no</span></td>', FieldDescriptionInterface::TYPE_BOOLEAN, false, []],
			[
				'<th>Data</th> <td><span class="label label-success">no</span></td>',
				FieldDescriptionInterface::TYPE_BOOLEAN,
				false,
				['inverse' => true],
			],
			[
				'<th>Data</th> <td>Delete</td>',
				FieldDescriptionInterface::TYPE_TRANS,
				'action_delete',
				['safe' => false, 'catalogue' => 'SonataAdminBundle'],
			],
			[
				'<th>Data</th> <td>Delete</td>',
				FieldDescriptionInterface::TYPE_TRANS,
				'delete',
				['safe' => false, 'catalogue' => 'SonataAdminBundle', 'format' => 'action_%s'],
			],
			['<th>Data</th> <td>Status1</td>', FieldDescriptionInterface::TYPE_CHOICE, 'Status1', ['safe' => false]],
			[
				'<th>Data</th> <td>Alias1</td>',
				FieldDescriptionInterface::TYPE_CHOICE,
				'Status1',
				['safe' => false, 'choices' => [
					'Status1' => 'Alias1',
					'Status2' => 'Alias2',
					'Status3' => 'Alias3',
				]],
			],
			[
				'<th>Data</th> <td>NoValidKeyInChoices</td>',
				FieldDescriptionInterface::TYPE_CHOICE,
				'NoValidKeyInChoices',
				['safe' => false, 'choices' => [
					'Status1' => 'Alias1',
					'Status2' => 'Alias2',
					'Status3' => 'Alias3',
				]],
			],
			[
				'<th>Data</th> <td>Delete</td>',
				FieldDescriptionInterface::TYPE_CHOICE,
				'Foo',
				['safe' => false, 'catalogue' => 'SonataAdminBundle', 'choices' => [
					'Foo' => 'action_delete',
					'Status2' => 'Alias2',
					'Status3' => 'Alias3',
				]],
			],
			[
				'<th>Data</th> <td>NoValidKeyInChoices</td>',
				FieldDescriptionInterface::TYPE_CHOICE,
				['NoValidKeyInChoices'],
				['safe' => false, 'choices' => [
					'Status1' => 'Alias1',
					'Status2' => 'Alias2',
					'Status3' => 'Alias3',
				], 'multiple' => true],
			],
			[
				'<th>Data</th> <td>NoValidKeyInChoices, Alias2</td>',
				FieldDescriptionInterface::TYPE_CHOICE,
				['NoValidKeyInChoices', 'Status2'],
				['safe' => false, 'choices' => [
					'Status1' => 'Alias1',
					'Status2' => 'Alias2',
					'Status3' => 'Alias3',
				], 'multiple' => true],
			],
			[
				'<th>Data</th> <td>Alias1, Alias3</td>',
				FieldDescriptionInterface::TYPE_CHOICE,
				['Status1', 'Status3'],
				['safe' => false, 'choices' => [
					'Status1' => 'Alias1',
					'Status2' => 'Alias2',
					'Status3' => 'Alias3',
				], 'multiple' => true],
			],
			[
				'<th>Data</th> <td>Alias1 | Alias3</td>',
				FieldDescriptionInterface::TYPE_CHOICE,
				['Status1', 'Status3'], ['safe' => false, 'choices' => [
				'Status1' => 'Alias1',
				'Status2' => 'Alias2',
				'Status3' => 'Alias3',
			], 'multiple' => true, 'delimiter' => ' | '],
			],
			[
				'<th>Data</th> <td>Delete, Alias3</td>',
				FieldDescriptionInterface::TYPE_CHOICE,
				['Foo', 'Status3'],
				['safe' => false, 'catalogue' => 'SonataAdminBundle', 'choices' => [
					'Foo' => 'action_delete',
					'Status2' => 'Alias2',
					'Status3' => 'Alias3',
				], 'multiple' => true],
			],
			[
				'<th>Data</th> <td><b>Alias1</b>, <b>Alias3</b></td>',
				FieldDescriptionInterface::TYPE_CHOICE,
				['Status1', 'Status3'],
				['safe' => true, 'choices' => [
					'Status1' => '<b>Alias1</b>',
					'Status2' => '<b>Alias2</b>',
					'Status3' => '<b>Alias3</b>',
				], 'multiple' => true],
			],
			[
				'<th>Data</th> <td>&lt;b&gt;Alias1&lt;/b&gt;, &lt;b&gt;Alias3&lt;/b&gt;</td>',
				FieldDescriptionInterface::TYPE_CHOICE,
				['Status1', 'Status3'],
				['safe' => false, 'choices' => [
					'Status1' => '<b>Alias1</b>',
					'Status2' => '<b>Alias2</b>',
					'Status3' => '<b>Alias3</b>',
				], 'multiple' => true],
			],
			[
				'<th>Data</th> <td><a href="http://example.com">http://example.com</a></td>',
				FieldDescriptionInterface::TYPE_URL,
				'http://example.com',
				['safe' => false],
			],
			[
				'<th>Data</th> <td><a href="http://example.com" target="_blank">http://example.com</a></td>',
				FieldDescriptionInterface::TYPE_URL,
				'http://example.com',
				['safe' => false, 'attributes' => ['target' => '_blank']],
			],
			[
				'<th>Data</th> <td><a href="http://example.com" target="_blank" class="fooLink">http://example.com</a></td>',
				FieldDescriptionInterface::TYPE_URL,
				'http://example.com',
				['safe' => false, 'attributes' => ['target' => '_blank', 'class' => 'fooLink']],
			],
			[
				'<th>Data</th> <td><a href="https://example.com">https://example.com</a></td>',
				FieldDescriptionInterface::TYPE_URL,
				'https://example.com',
				['safe' => false],
			],
			[
				'<th>Data</th> <td><a href="http://example.com">example.com</a></td>',
				FieldDescriptionInterface::TYPE_URL,
				'http://example.com',
				['safe' => false, 'hide_protocol' => true],
			],
			[
				'<th>Data</th> <td><a href="https://example.com">example.com</a></td>',
				FieldDescriptionInterface::TYPE_URL,
				'https://example.com',
				['safe' => false, 'hide_protocol' => true],
			],
			[
				'<th>Data</th> <td><a href="http://example.com">http://example.com</a></td>',
				FieldDescriptionInterface::TYPE_URL,
				'http://example.com',
				['safe' => false, 'hide_protocol' => false],
			],
			[
				'<th>Data</th> <td><a href="https://example.com">https://example.com</a></td>',
				FieldDescriptionInterface::TYPE_URL,
				'https://example.com',
				['safe' => false,
					'hide_protocol' => false, ],
			],
			[
				'<th>Data</th> <td><a href="http://example.com">Foo</a></td>',
				FieldDescriptionInterface::TYPE_URL,
				'Foo',
				['safe' => false, 'url' => 'http://example.com'],
			],
			[
				'<th>Data</th> <td><a href="http://example.com">&lt;b&gt;Foo&lt;/b&gt;</a></td>',
				FieldDescriptionInterface::TYPE_URL,
				'<b>Foo</b>',
				['safe' => false, 'url' => 'http://example.com'],
			],
			[
				'<th>Data</th> <td><a href="http://example.com"><b>Foo</b></a></td>',
				FieldDescriptionInterface::TYPE_URL,
				'<b>Foo</b>',
				['safe' => true, 'url' => 'http://example.com'],
			],
			[
				'<th>Data</th> <td><a href="/foo">Foo</a></td>',
				FieldDescriptionInterface::TYPE_URL,
				'Foo',
				['safe' => false, 'route' => ['name' => 'sonata_admin_foo']],
			],
			[
				'<th>Data</th> <td><a href="http://localhost/foo">Foo</a></td>',
				FieldDescriptionInterface::TYPE_URL,
				'Foo',
				['safe' => false, 'route' => [
					'name' => 'sonata_admin_foo',
					'absolute' => true,
				]],
			],
			[
				'<th>Data</th> <td><a href="/foo">foo/bar?a=b&amp;c=123456789</a></td>',
				FieldDescriptionInterface::TYPE_URL,
				'http://foo/bar?a=b&c=123456789',
				[
					'safe' => false,
					'route' => ['name' => 'sonata_admin_foo'],
					'hide_protocol' => true,
				],
			],
			[
				'<th>Data</th> <td><a href="http://localhost/foo">foo/bar?a=b&amp;c=123456789</a></td>',
				FieldDescriptionInterface::TYPE_URL,
				'http://foo/bar?a=b&c=123456789',
				['safe' => false, 'route' => [
					'name' => 'sonata_admin_foo',
					'absolute' => true,
				], 'hide_protocol' => true],
			],
			[
				'<th>Data</th> <td><a href="/foo/abcd/efgh?param3=ijkl">Foo</a></td>',
				FieldDescriptionInterface::TYPE_URL,
				'Foo',
				['safe' => false, 'route' => [
					'name' => 'sonata_admin_foo_param',
					'parameters' => ['param1' => 'abcd', 'param2' => 'efgh', 'param3' => 'ijkl'],
				]],
			],
			[
				'<th>Data</th> <td><a href="http://localhost/foo/abcd/efgh?param3=ijkl">Foo</a></td>',
				FieldDescriptionInterface::TYPE_URL,
				'Foo',
				['safe' => false, 'route' => [
					'name' => 'sonata_admin_foo_param',
					'absolute' => true,
					'parameters' => [
						'param1' => 'abcd',
						'param2' => 'efgh',
						'param3' => 'ijkl',
					],
				]],
			],
			[
				'<th>Data</th> <td><a href="/foo/obj/abcd/12345/efgh?param3=ijkl">Foo</a></td>',
				FieldDescriptionInterface::TYPE_URL,
				'Foo',
				['safe' => false, 'route' => [
					'name' => 'sonata_admin_foo_object',
					'parameters' => [
						'param1' => 'abcd',
						'param2' => 'efgh',
						'param3' => 'ijkl',
					],
					'identifier_parameter_name' => 'barId',
				]],
			],
			[
				'<th>Data</th> <td><a href="http://localhost/foo/obj/abcd/12345/efgh?param3=ijkl">Foo</a></td>',
				FieldDescriptionInterface::TYPE_URL,
				'Foo',
				['safe' => false, 'route' => [
					'name' => 'sonata_admin_foo_object',
					'absolute' => true,
					'parameters' => [
						'param1' => 'abcd',
						'param2' => 'efgh',
						'param3' => 'ijkl',
					],
					'identifier_parameter_name' => 'barId',
				]],
			],
			[
				'<th>Data</th> <td> &nbsp;</td>',
				FieldDescriptionInterface::TYPE_EMAIL,
				null,
				[],
			],
			[
				'<th>Data</th> <td> <a href="mailto:admin@admin.com">admin@admin.com</a></td>',
				FieldDescriptionInterface::TYPE_EMAIL,
				'admin@admin.com',
				[],
			],
			[
				'<th>Data</th> <td> <a href="mailto:admin@admin.com?">admin@admin.com</a></td>',
				FieldDescriptionInterface::TYPE_EMAIL,
				'admin@admin.com',
				['subject' => 'Main Theme', 'body' => 'Message Body'],
			],
			[
				'<th>Data</th> <td> <a href="mailto:admin@admin.com?">admin@admin.com</a></td>',
				FieldDescriptionInterface::TYPE_EMAIL,
				'admin@admin.com',
				['subject' => 'Main Theme'],
			],
			[
				'<th>Data</th> <td> <a href="mailto:admin@admin.com?">admin@admin.com</a></td>',
				FieldDescriptionInterface::TYPE_EMAIL,
				'admin@admin.com',
				['body' => 'Message Body'],
			],
			[
				'<th>Data</th> <td> admin@admin.com</td>',
				FieldDescriptionInterface::TYPE_EMAIL,
				'admin@admin.com',
				['as_string' => true, 'subject' => 'Main Theme', 'body' => 'Message Body'],
			],
			[
				'<th>Data</th> <td> admin@admin.com</td>',
				FieldDescriptionInterface::TYPE_EMAIL,
				'admin@admin.com',
				['as_string' => true, 'subject' => 'Main Theme'],
			],
			[
				'<th>Data</th> <td> admin@admin.com</td>',
				FieldDescriptionInterface::TYPE_EMAIL,
				'admin@admin.com',
				['as_string' => true, 'body' => 'Message Body'],
			],
			[
				'<th>Data</th> <td> <a href="mailto:admin@admin.com">admin@admin.com</a></td>',
				FieldDescriptionInterface::TYPE_EMAIL,
				'admin@admin.com',
				['as_string' => false],
			],
			[
				'<th>Data</th> <td> admin@admin.com</td>',
				FieldDescriptionInterface::TYPE_EMAIL,
				'admin@admin.com',
				['as_string' => true],
			],
			[
				'<th>Data</th> <td><p><strong>Creating a Template for the Field</strong> and form</p></td>',
				FieldDescriptionInterface::TYPE_HTML,
				'<p><strong>Creating a Template for the Field</strong> and form</p>',
				[],
			],
			[
				'<th>Data</th> <td>Creating a Template for the Field and form</td>',
				FieldDescriptionInterface::TYPE_HTML,
				'<p><strong>Creating a Template for the Field</strong> and form</p>',
				['strip' => true],
			],
			[
				'<th>Data</th> <td>Creating a Template for the...</td>',
				FieldDescriptionInterface::TYPE_HTML,
				'<p><strong>Creating a Template for the Field</strong> and form</p>',
				['truncate' => true],
			],
			[
				'<th>Data</th> <td>Creatin...</td>',
				FieldDescriptionInterface::TYPE_HTML,
				'<p><strong>Creating a Template for the Field</strong> and form</p>',
				['truncate' => ['length' => 10]],
			],
			[
				'<th>Data</th> <td>Creating a Template for the Field...</td>',
				FieldDescriptionInterface::TYPE_HTML,
				'<p><strong>Creating a Template for the Field</strong> and form</p>',
				['truncate' => ['cut' => false]],
			],
			[
				'<th>Data</th> <td>Creating a Template for t etc.</td>',
				FieldDescriptionInterface::TYPE_HTML,
				'<p><strong>Creating a Template for the Field</strong> and form</p>',
				['truncate' => ['ellipsis' => ' etc.']],
			],
			[
				'<th>Data</th> <td>Creating a Template[...]</td>',
				FieldDescriptionInterface::TYPE_HTML,
				'<p><strong>Creating a Template for the Field</strong> and form</p>',
				[
					'truncate' => [
						'length' => 20,
						'cut' => false,
						'ellipsis' => '[...]',
					],
				],
			],
			[
				<<<'EOT'
                    <th>Data</th> <td><div
                            class="sonata-readmore"
                            data-readmore-height="40"
                            data-readmore-more="Read more"
                            data-readmore-less="Close">
                                A very long string
                    </div></td>
                    EOT
				,
				FieldDescriptionInterface::TYPE_STRING,
				' A very long string ',
				[
					'collapse' => true,
					'safe' => false,
				],
			],
			[
				<<<'EOT'
                    <th>Data</th> <td><div
                            class="sonata-readmore"
                            data-readmore-height="10"
                            data-readmore-more="More"
                            data-readmore-less="Less">
                                A very long string
                    </div></td>
                    EOT
				,
				FieldDescriptionInterface::TYPE_STRING,
				' A very long string ',
				[
					'collapse' => [
						'height' => 10,
						'more' => 'More',
						'less' => 'Less',
					],
					'safe' => false,
				],
			],
		];

		if (\PHP_VERSION_ID >= 80100) {
			$elements[] = [
				'<th>Data</th> <td>Hearts</td>',
				FieldDescriptionInterface::TYPE_ENUM,
				'',
				[],
			];
		}

		return $elements;
	}
}

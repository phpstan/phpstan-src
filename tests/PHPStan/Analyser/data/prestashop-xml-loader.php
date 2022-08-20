<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

namespace PrestaShopBundleInfiniteRunBug;

class XmlLoader
{

	protected $data_path;

	public function getEntityInfo($entity, $exists)
	{
		$info = [
			'config' => [
				'id' => '',
				'primary' => '',
				'class' => '',
				'sql' => '',
				'ordersql' => '',
				'image' => '',
				'null' => '',
			],
			'fields' => [],
		];

		if (!$exists) {
			return $info;
		}

		$xml = @simplexml_load_file($this->data_path . $entity . '.xml', 'SimplexmlElement');
		if (!$xml) {
			return $info;
		}

		if ($xml->fields['id']) {
			$info['config']['id'] = (string) $xml->fields['id'];
		}

		if ($xml->fields['primary']) {
			$info['config']['primary'] = (string) $xml->fields['primary'];
		}

		if ($xml->fields['class']) {
			$info['config']['class'] = (string) $xml->fields['class'];
		}

		if ($xml->fields['sql']) {
			$info['config']['sql'] = (string) $xml->fields['sql'];
		}

		if ($xml->fields['ordersql']) {
			$info['config']['ordersql'] = (string) $xml->fields['ordersql'];
		}

		if ($xml->fields['null']) {
			$info['config']['null'] = (string) $xml->fields['null'];
		}

		if ($xml->fields['image']) {
			$info['config']['image'] = (string) $xml->fields['image'];
		}

		foreach ($xml->fields->field as $field) {
			$column = (string) $field['name'];
			$info['fields'][$column] = [];
			if (isset($field['relation'])) {
				$info['fields'][$column]['relation'] = (string) $field['relation'];
			}
		}

		return $info;
	}

}

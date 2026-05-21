<?php

namespace Bug14624;

final class BenchDb extends \mysqli
{
	/**
	 * @param string $query
	 * @param int $resultMode
	 * @return BenchDbResult
	 */
	public function query($query, $resultMode = MYSQLI_STORE_RESULT)
	{
		throw new \RuntimeException();
	}
}

final class BenchDbResult extends \mysqli_result
{
	public function getIterator(): \Iterator
	{
		throw new \RuntimeException();
	}
}

final class BenchRepro
{
	private BenchDb $db;

	/**
	 * @return mixed[]
	 */
	public function build()
	{
		$out = [];
		$rows = $this->db->query('');

		while ($row = $rows->fetch_assoc()) {
			$row['group_id'] = intval($row['group_id']);
			$bucket = intval($row['bucket']);

			if (!isset($out[$row['a']])) {
				$out[$row['a']] = ['id' => $row['a'], 'label' => $row['a_label'], 'groups' => []];
			}

			if (!isset($out[$row['a']]['groups'][$bucket])) {
				$out[$row['a']]['groups'][$bucket] = [];
			}

			if (!isset($out[$row['a']]['groups'][$bucket][$row['group_id']])) {
				$out[$row['a']]['groups'][$bucket][$row['group_id']] = ['id' => $row['group_id'], 'label' => $row['group_label'], 'sections' => []];
			}

			if (!isset($out[$row['a']]['groups'][$bucket][$row['group_id']]['sections'][$row['section_id']])) {
				$out[$row['a']]['groups'][$bucket][$row['group_id']]['sections'][$row['section_id']] = ['id' => $row['section_id'], 'label' => $row['section_label'], 'items' => []];
			}

			if (!isset($out[$row['a']]['groups'][$bucket][$row['group_id']]['sections'][$row['section_id']]['items'][$row['item_id']])) {
				$row['csv_ids'] = $row['csv_ids'] ? array_map('intval', explode(',', $row['csv_ids'])) : [];
				$out[$row['a']]['groups'][$bucket][$row['group_id']]['sections'][$row['section_id']]['items'][$row['item_id']] = [
					'id' => $row['item_id'], 'title' => $row['item_title'], 'code' => $row['item_code'],
					'type' => $row['item_type'], 'state' => $row['item_state'], 'priority' => $row['item_priority'],
					'csv_ids' => $row['csv_ids'], 'related_rows' => [], 'details' => [], 'tags' => [],
				];
				if ($row['csv_ids']) {
					$relatedRows = $this->db->query('');
					while ($relatedRow = $relatedRows->fetch_assoc()) {
						$out[$row['a']]['groups'][$bucket][$row['group_id']]['sections'][$row['section_id']]['items'][$row['item_id']]['related_rows'][] = $relatedRow;
					}
				}
			}

			if (!isset($out[$row['a']]['groups'][$bucket][$row['group_id']]['sections'][$row['section_id']]['items'][$row['item_id']]['details'][$row['detail_id']])) {
				$out[$row['a']]['groups'][$bucket][$row['group_id']]['sections'][$row['section_id']]['items'][$row['item_id']]['details'][$row['detail_id']] = [
					'id' => $row['detail_id'], 'title' => $row['detail_title'], 'code' => $row['detail_code'],
					'kind' => $row['detail_kind'], 'amount' => $row['detail_amount'],
					'records' => [], 'notes' => [], 'flags' => [],
				];
			}

			if (!isset($out[$row['a']]['groups'][$bucket][$row['group_id']]['sections'][$row['section_id']]['items'][$row['item_id']]['details'][$row['detail_id']]['records'][$row['record_id']])) {
				$out[$row['a']]['groups'][$bucket][$row['group_id']]['sections'][$row['section_id']]['items'][$row['item_id']]['details'][$row['detail_id']]['records'][$row['record_id']] = [
					'id' => $row['record_id'], 'name' => $row['record_name'], 'code' => $row['record_code'],
					'version' => $row['record_version'], 'payload' => $row['record_payload'],
				];
			}
		}

		return $out;
	}
}

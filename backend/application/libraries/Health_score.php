<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Perhitungan Asset Health Score (Bab 2.1).
 *
 *   nilai item  : baik = 1, perlu = 0,5, rusak = 0   (config nexa: result_values)
 *   nilai grup  : rata-rata nilai item grup
 *   skor akhir  : jumlah(bobot grup x nilai grup), dibulatkan, 0-100
 *   label       : >= 80 Sehat, 60-79 Perlu perhatian, < 60 Kritis
 *
 * Pemakaian:
 *   $this->load->library('health_score');
 *   $items = $this->health_score->normalize($input['items'], $errors);
 *   $score = $this->health_score->calculate($items);
 */
class Health_score
{
	protected $checklist;
	protected $values;

	public function __construct()
	{
		$CI = &get_instance();
		$this->checklist = $CI->config->item('checklist');
		$this->values    = $CI->config->item('result_values');
	}

	/**
	 * Pastikan semua item checklist terisi dengan nilai yang valid.
	 * Item yang tidak dikirim dianggap 'baik'. Nilai tidak dikenal masuk ke $errors.
	 */
	public function normalize($items, &$errors = array())
	{
		$items = is_array($items) ? $items : array();
		$out   = array();
		foreach ($this->checklist as $group) {
			foreach ($group['items'] as $key => $label) {
				$v = isset($items[$key]) ? (string) $items[$key] : 'baik';
				if ( ! isset($this->values[$v])) {
					$errors['items.' . $key] = 'Nilai "' . $label . '" harus baik, perlu, atau rusak.';
					$v = 'baik';
				}
				$out[$key] = $v;
			}
		}

		return $out;
	}

	/** Nilai per grup (0-100, dibulatkan) */
	public function group_scores($items)
	{
		$out = array();
		foreach ($this->checklist as $gkey => $group) {
			$sum = 0.0;
			foreach ($group['items'] as $key => $label) {
				$sum += $this->values[isset($items[$key]) ? $items[$key] : 'baik'];
			}
			$out[$gkey] = (int) round(100 * $sum / count($group['items']));
		}

		return $out;
	}

	/** Skor akhir 0-100 */
	public function calculate($items)
	{
		$total = 0.0;
		foreach ($this->checklist as $group) {
			$sum = 0.0;
			foreach ($group['items'] as $key => $label) {
				$sum += $this->values[isset($items[$key]) ? $items[$key] : 'baik'];
			}
			$total += $group['weight'] * $sum / count($group['items']);
		}

		return (int) round($total);
	}

	public static function label($score)
	{
		if ($score === NULL) {
			return 'Belum diperiksa';
		}

		return $score >= 80 ? 'Sehat' : ($score >= 60 ? 'Perlu perhatian' : 'Kritis');
	}

	/** Ringkasan temuan untuk Asset Timeline */
	public function findings_text($items)
	{
		$perlu = array();
		$rusak = array();
		foreach ($this->checklist as $group) {
			foreach ($group['items'] as $key => $label) {
				if ($items[$key] === 'perlu') {
					$perlu[] = $label;
				} elseif ($items[$key] === 'rusak') {
					$rusak[] = $label;
				}
			}
		}
		$parts = array();
		if ($perlu) {
			$parts[] = 'Perlu perbaikan: ' . implode(', ', $perlu);
		}
		if ($rusak) {
			$parts[] = 'Rusak/tidak ada: ' . implode(', ', $rusak);
		}

		return $parts ? implode('. ', $parts) : 'Semua item dalam kondisi baik';
	}

	/** Label item dan grup untuk disimpan ke inspection_items */
	public function rows($inspection_id, $items)
	{
		$rows = array();
		foreach ($this->checklist as $gkey => $group) {
			foreach ($group['items'] as $key => $label) {
				$rows[] = array(
					'inspection_id' => (int) $inspection_id,
					'group_key'     => $gkey,
					'item_key'      => $key,
					'item_label'    => $label,
					'result'        => $items[$key],
				);
			}
		}

		return $rows;
	}
}

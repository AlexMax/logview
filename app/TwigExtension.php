<?php
namespace Logview;

class TwigExtension extends \Twig_Extension {
	public function getName() {
		return "logview";
	}

	public function getFilters() {
		return [
			'bytesize' => new \Twig_Filter_Method($this, 'bytesize'),
		];
	}

	public function bytesize($input) {
		if ($input < 1024) {
			return number_format($input, 2);
		} elseif ($input < 1048576) {
			return number_format($input / 1024, 2).'K';
		} elseif ($input < 1073741824) {
			return number_format($input / 1048576, 2).'M';
		} elseif ($input < PHP_INT_MAX) {
			return number_format($input / 1073741824, 2).'G';
		}
	}
}

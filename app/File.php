<?php
namespace Logview;

class File {
	// Gets the character BEFORE the file pointer.  Leaves the file pointer
	// pointing at that character.  Returns FALSE if we're at the beginning
	// of the file.
	public static function fgetrc($fh) {
		if (ftell($fh) === 0) {
			return FALSE;
		}
		fseek($fh, -1, SEEK_CUR);
		$char = fgetc($fh);
		fseek($fh, -1, SEEK_CUR);
		return $char;
	}

	// Gets the string BEFORE the current file pointer.  Leaves the file
	// pointer pointing at the first character of the returned string.  Returns
	// FALSE if we're at the beginning of the file.
	public static function fgetrs($fh) {
		if (($end = ftell($fh)) === 0) {
			return FALSE;
		}

		fseek($fh, -1, SEEK_CUR);
		do {
			$c = self::fgetrc($fh);
		} while($c !== "\n" && $c !== FALSE);
		if ($c === "\n") {
			fseek($fh, 1, SEEK_CUR);
		}
		$begin = ftell($fh);
		$str = fread($fh, $end - $begin);
		fseek($fh, $begin, SEEK_SET);
		return $str;
	}
}

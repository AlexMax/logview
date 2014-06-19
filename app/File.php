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
			$c = fgetrc($fh);
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

/*
// Byte position to start reading from.
// Passing in -1 will always go to the bottom of the file.
$pos = isset($_GET['p']) ? (int)$_GET['p'] : 0;

// Number of lines to return
$limit = isset($_GET['l']) ? (int)$_GET['l'] : 24;

$data = [];

if ($pos === -1) {
	// Since we're at the bottom of the file, work backwards
	fseek($fh, 0, SEEK_END);

	while (($row = fgetrs($fh)) && $limit--) {
		$pos = ftell($fh);
		$data[$pos] = $row;
	}
} else {
	// Start at an absolute position
	fseek($fh, $pos, SEEK_SET);

	// Work backwards until we find a newline
	do {
		$c = fgetrc($fh);
	} while($c !== "\n" && $c !== FALSE);

	// Point at the first character past the newline
	fseek($fh, 1, SEEK_CUR);

	// Read a set number of lines
	while (($row = fgets($fh)) && $limit--) {
		$data[$pos] = $row;
		$pos = ftell($fh);
	}
}

*/

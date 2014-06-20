<?php

require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Logview\File;

$app = new Silex\Application();

// Providers
$app->register(new Silex\Provider\TwigServiceProvider(), [
	'twig.path' => __DIR__.'/view',
]);
$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__."/../config/config.json"));

// Settings
$app['debug'] = true;
$app['twig'] = $app->share($app->extend("twig", function (\Twig_Environment $twig, Silex\Application $app) {
    $twig->addExtension(new Logview\TwigExtension($app));
    return $twig;
}));

// A simple directory listing of all log files
$app->get('/', function() use ($app) {
	$context = [];

	$logdir = realpath($app['logdir']);
	$context['files'] = [];
	foreach (new DirectoryIterator($logdir) as $entry) {
		if ($entry->isDot()) {
			continue;
		}

		$context['files'][$entry->getBasename()] = [
			'mtime' => $entry->getMTime(),
			'size' => $entry->getSize()
		];
	}
	ksort($context['files']);

	return $app['twig']->render('files.twig', $context);
});

$app->get('/files/{filename}', function(Request $req, $filename) use ($app) {
	$filename = realpath($app['logdir'].'/'.$filename);
	$logdir = realpath($app['logdir']);

	if (strpos($filename, $logdir) !== 0) {
		$app->abort(404, "Attempted directory traversal");
	}

	$p = $req->query->get('p', 'bot');
	$limit = (int)$req->query->get('l', 24);
	$direction = strtolower($req->query->get('d', 'desc'));

	$logclasses = [];
	if ($p === 'bot') {
		$bottom = true;
		$position = 0;
		$logclass = 'bottom';
	} else {
		$bottom = false;
		$position = (int)$p;
		$logclass = '';
	}

	// Limit the amount of lines to something reasonable
	if ($limit > 100) {
		$app->abort(400, "Too many lines");
	}

	// Don't allow out-of-bounds reads
	$filesize = filesize($filename);
	if ($position > $filesize || $position < 0) {
		$app->abort(400, "Out of bounds");
	}

	$filedata = [];
	$fh = fopen($filename, 'rb');

	if ($bottom) {
		// Go to the end of the file
		fseek($fh, 0, SEEK_END);

		// Our "real" position is in absolute bytes
		$position = ftell($fh);
	} else {
		// Start at an absolute position
		fseek($fh, $position, SEEK_SET);
	}

	if ($direction === 'asc') {
		// Work backwards until we find a newline
		do {
			$c = File::fgetrc($fh);
		} while ($c !== "\n" && $c !== FALSE);

		// Point at the first character past the newline
		if ($c === "\n") {
			fseek($fh, 1, SEEK_CUR);
		}

		// Read a set number of lines
		$position = ftell($fh);
		$linecount = $limit;
		while (($row = fgets($fh)) && $linecount--) {
			$filedata[$position] = $row;
			$position = ftell($fh);
		}
	} elseif ($direction === 'desc') {
		// Work forwards until we find a newline
		do {
			$c = fgetc($fh);
		} while ($c !== "\n" && $c !== FALSE);

		// Read a set number of lines backwards
		$linecount = $limit;
		while (($row = File::fgetrs($fh)) && $linecount--) {
			$position = ftell($fh);
			$filedata[$position] = $row;
		}

		$filedata = array_reverse($filedata, true);
	} else {
		$app->abort(400, "Invalid direction");
	}

	// Create the next and previous byte positions
	$bytes = array_keys($filedata);

	$context = [
		'filename' => basename($filename),
		'filedata' => $filedata,
		'prevbyte' => $bytes[0],
		'nextbyte' => $bytes[count($bytes) - 1],
		'limit' => $limit,
		'logclass' => $logclass,
	];

	return $app['twig']->render('file.twig', $context);
});

$app->run();

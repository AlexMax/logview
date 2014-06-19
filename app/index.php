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

	$position = (int)$req->query->get('p', -1);
	$limit = (int)$req->query->get('l', 24);

	$filedata = [];
	$fh = fopen($filename, 'r');
	if ($position === -1) {
		// Since we're at the bottom of the file, work backwards
		fseek($fh, 0, SEEK_END);

		while (($row = File::fgetrs($fh)) && $limit--) {
			$position = ftell($fh);
			$filedata[$position] = $row;
		}

		$filedata = array_reverse($filedata, true);
	} else {
		// Start at an absolute position
		fseek($fh, $position, SEEK_SET);

		// Work backwards until we find a newline
		do {
			$c = File::fgetrc($fh);
		} while ($c !== "\n" && $c !== FALSE);

		// Point at the first character past the newline
		fseek($fh, 1, SEEK_CUR);

		// Read a set number of lines
		while (($row = fgets($fh)) && $limit--) {
			$filedata[$position] = $row;
			$position = ftell($fh);
		}
	}

	// Create the next and previous byte positions
	$bytes = array_keys($filedata);

	$context = [
		'filename' => basename($filename),
		'filedata' => $filedata,
		'prevbyte' => $bytes[0],
		'nextbyte' => $bytes[count($bytes) - 1],
	];

	return $app['twig']->render('file.twig', $context);
});

$app->run();

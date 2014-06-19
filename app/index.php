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

	$context = [
		'filename' => basename($filename),
		'filedata' => []
	];

	return $app['twig']->render('file.twig', $context);
});

$app->run();

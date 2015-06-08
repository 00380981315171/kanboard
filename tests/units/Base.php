<?php

require __DIR__.'/../../vendor/autoload.php';
require __DIR__.'/../../app/constants.php';

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\Stopwatch\Stopwatch;
use SimpleLogger\Logger;
use SimpleLogger\File;

date_default_timezone_set('UTC');

class FakeEmailClient
{
    public $email;
    public $name;
    public $subject;
    public $html;

    public function send($email, $name, $subject, $html)
    {
        $this->email = $email;
        $this->name = $name;
        $this->subject = $subject;
        $this->html = $html;
    }
}

class FakeHttpClient
{
    private $url = '';
    private $data = array();
    private $headers = array();

    public function getUrl()
    {
        return $this->url;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function toPrettyJson()
    {
        return json_encode($this->data, JSON_PRETTY_PRINT);
    }

    public function postJson($url, array $data, array $headers = array())
    {
        $this->url = $url;
        $this->data = $data;
        $this->headers = $headers;
        return true;
    }

    public function postForm($url, array $data, array $headers = array())
    {
        $this->url = $url;
        $this->data = $data;
        $this->headers = $headers;
        return true;
    }
}

abstract class Base extends PHPUnit_Framework_TestCase
{
    protected $container;

    public function setUp()
    {
        if (DB_DRIVER === 'mysql') {
            $pdo = new PDO('mysql:host='.DB_HOSTNAME, DB_USERNAME, DB_PASSWORD);
            $pdo->exec('DROP DATABASE '.DB_NAME);
            $pdo->exec('CREATE DATABASE '.DB_NAME);
            $pdo = null;
        }
        else if (DB_DRIVER === 'postgres') {
            $pdo = new PDO('pgsql:host='.DB_HOSTNAME, DB_USERNAME, DB_PASSWORD);
            $pdo->exec('DROP DATABASE '.DB_NAME);
            $pdo->exec('CREATE DATABASE '.DB_NAME.' WITH OWNER '.DB_USERNAME);
            $pdo = null;
        }

        $this->container = new Pimple\Container;
        $this->container->register(new ServiceProvider\DatabaseProvider);
        $this->container->register(new ServiceProvider\ClassProvider);

        $this->container['dispatcher'] = new TraceableEventDispatcher(
            new EventDispatcher,
            new Stopwatch
        );

        $this->container['db']->log_queries = true;

        $this->container['logger'] = new Logger;
        $this->container['logger']->setLogger(new File('/dev/null'));
        $this->container['httpClient'] = new FakeHttpClient;
        $this->container['emailClient'] = new FakeEmailClient;
    }

    public function tearDown()
    {
        $this->container['db']->closeConnection();
    }
}

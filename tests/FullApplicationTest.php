<?php

use Scarf\Scarf;
use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class FullApplicationTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testBasicRequest()
    {
        $app = new Scarf;

        $app->boot();

        $app->get('/', function () use ($app) {
            return ['name' => 'Scarf', 'version' => $app->version()];
        });

        $response = $app->handle(Request::create('/', 'GET'));

        $content = $response->getContent();

        $this->assertInternalType('string', $content);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertInternalType('object', json_decode($content));
        $this->assertInternalType('array', json_decode($content, true));
        $this->assertEquals(json_encode(['name' => 'Scarf', 'version' => $app->version()]), $content);
        $this->assertJsonStringEqualsJsonString(
            json_encode(['name' => 'Scarf', 'version' => $app->version()]), $content
        );
    }
}

class ScarfTestingService
{
}

class ScarfTestingServiceProvider extends ServiceProvider
{
    public function register()
    {
    }
}

class ScarfTestingController
{
    public $service = null;

    public function __construct(ScarfTestingService $service)
    {
        $this->service = null;
    }

    public function show($id)
    {
        return $id;
    }
}

class ScarfTestingMiddleware
{
    public function handle($request, $next)
    {
        //
    }
}

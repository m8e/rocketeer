<?php
namespace Rocketeer\Services\Connections\Gateways;

use Mockery;
use Rocketeer\TestCases\RocketeerTestCase;

class SeclibGatewayTest extends RocketeerTestCase
{
    public function testHostAndPortSetCorrectly()
    {
        $gateway = $this->getGateway();
        $this->assertEquals('127.0.0.1', $gateway->getHost());
        $this->assertEquals(22, $gateway->getPort());
    }

    public function testConnectProperlyCallsLoginWithAuth()
    {
        $gateway = $this->getGateway();
        $gateway->shouldReceive('getNewKey')->andReturn($key = Mockery::mock('StdClass'));
        $key->shouldReceive('setPassword')->once()->with('keyphrase');
        $key->shouldReceive('loadKey')->once()->with('keystuff');
        $gateway->getConnection()->shouldReceive('login')->with('taylor', $key);

        $gateway->connect('taylor');
    }

    public function testKeyTextCanBeSetManually()
    {
        $files = Mockery::mock('Illuminate\Filesystem\Filesystem');
        $gateway = Mockery::mock('Illuminate\Remote\SecLibGateway', array(
            '127.0.0.1:22',
            array('username' => 'taylor', 'keytext' => 'keystuff'),
            $files
        ))->makePartial();
        $gateway->shouldReceive('getConnection')->andReturn(Mockery::mock('StdClass'));
        $gateway->shouldReceive('getNewKey')->andReturn($key = Mockery::mock('StdClass'));
        $key->shouldReceive('setPassword')->once()->with(null);
        $key->shouldReceive('loadKey')->once()->with('keystuff');
        $gateway->getConnection()->shouldReceive('login')->with('taylor', $key);

        $gateway->connect('taylor');
    }

    public function getGateway()
    {
        $files = Mockery::mock('Illuminate\Filesystem\Filesystem');
        $files->shouldReceive('get')->with('keypath')->andReturn('keystuff');
        $gateway = Mockery::mock('Illuminate\Remote\SecLibGateway', array(
            '127.0.0.1:22',
            array('username' => 'taylor', 'key' => 'keypath', 'keyphrase' => 'keyphrase'),
            $files
        ))->makePartial();
        $gateway->shouldReceive('getConnection')->andReturn(Mockery::mock('StdClass'));

        return $gateway;
    }
}

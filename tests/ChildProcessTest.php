<?php
/**
 * Phergie plugin for handling requests to increment or decrement counters on specified terms (https://github.com/PSchwisow/phergie-irc-plugin-react-karma)
 *
 * @link https://github.com/PSchwisow/phergie-irc-plugin-react-karma for the canonical source repository
 * @copyright Copyright (c) 2016 Patrick Schwisow (https://github.com/PSchwisow/phergie-irc-plugin-react-karma)
 * @license http://phergie.org/license Simplified BSD License
 * @package PSchwisow\Phergie\Plugin\Karma
 */

namespace PSchwisow\Phergie\Tests\Plugin\Karma;

use Phake;
use PSchwisow\Phergie\Plugin\Karma\ChildProcess;
use WyriHaximus\React\ChildProcess\Messenger\Messages\Payload;

/**
 * Tests for the ChildProcess class.
 *
 * @category PSchwisow
 * @package PSchwisow\Phergie\Plugin\Karma
 */
class ChildProcessTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $loop = Phake::mock('\React\EventLoop\LoopInterface');
        $messenger = Phake::mock('\WyriHaximus\React\ChildProcess\Messenger\Messenger');

        ChildProcess::create($messenger, $loop);

        Phake::verify($messenger)->registerRpc('config', $this->isType('array'));
        Phake::verify($messenger)->registerRpc('modifyKarma', $this->isType('array'));
        Phake::verify($messenger)->registerRpc('fetchKarma', $this->isType('array'));
    }

    public function testRpcConfigSuccessObject()
    {
        $loop = Phake::mock('\React\EventLoop\LoopInterface');
        $messenger = Phake::mock('\WyriHaximus\React\ChildProcess\Messenger\Messenger');
        $child = new ChildProcess($messenger, $loop);
        $adapter = Phake::mock('\PSchwisow\Phergie\Plugin\Karma\Adapter\AdapterInterface');

        $response = $child->rpcConfig(new Payload(['service' => $adapter, 'service_config' => ['foo' => 'bar']]));
        $this->assertInstanceOf('React\Promise\FulfilledPromise', $response);
        $response->done(function ($data) {
            $this->assertEquals(['success' => true], $data);
        });

        Phake::verify($adapter)->setConfig(['foo' => 'bar']);
    }

    public function testRpcConfigSuccessClassname()
    {
        $loop = Phake::mock('\React\EventLoop\LoopInterface');
        $messenger = Phake::mock('\WyriHaximus\React\ChildProcess\Messenger\Messenger');
        $child = new ChildProcess($messenger, $loop);

        $response = $child->rpcConfig(new Payload(['service' => 'Random']));
        $this->assertInstanceOf('React\Promise\FulfilledPromise', $response);
        $response->done(function ($data) {
            $this->assertEquals(['success' => true], $data);
        });
    }

    public function testRpcConfigInvalidClass()
    {
        $loop = Phake::mock('\React\EventLoop\LoopInterface');
        $messenger = Phake::mock('\WyriHaximus\React\ChildProcess\Messenger\Messenger');
        $child = new ChildProcess($messenger, $loop);

        $response = $child->rpcConfig(new Payload(['service' => 'stdClass', 'service_config' => ['foo' => 'bar']]));
        $this->assertInstanceOf('React\Promise\RejectedPromise', $response);
        $response->done(
            function ($data) {
                $this->fail('This configuration should not pass.');
            },
            function ($data) {
                $this->assertEquals(
                    [
                        'success' => false,
                        'error' => 'Karma service adapter must implement PSchwisow\Phergie\Plugin\Karma\Adapter\AdapterInterface'
                    ],
                    $data
                );
            }
        );
    }

    public function testRpcConfigClassNotFound()
    {
        $loop = Phake::mock('\React\EventLoop\LoopInterface');
        $messenger = Phake::mock('\WyriHaximus\React\ChildProcess\Messenger\Messenger');
        $child = new ChildProcess($messenger, $loop);

        $response = $child->rpcConfig(new Payload(['service' => 'FooClass', 'service_config' => ['foo' => 'bar']]));
        $this->assertInstanceOf('React\Promise\RejectedPromise', $response);
        $response->done(
            function ($data) {
                $this->fail('This configuration should not pass.');
            },
            function ($data) {
                $this->assertEquals(
                    [
                        'success' => false,
                        'error' => 'Class not found: \'FooClass\''
                    ],
                    $data
                );
            }
        );
    }

    public function testRpcModifyKarma()
    {
        $loop = Phake::mock('\React\EventLoop\LoopInterface');
        $messenger = Phake::mock('\WyriHaximus\React\ChildProcess\Messenger\Messenger');
        $child = new ChildProcess($messenger, $loop);
        $adapter = Phake::mock('\PSchwisow\Phergie\Plugin\Karma\Adapter\AdapterInterface');
        Phake::when($adapter)->modifyKarma('shrek', 3)
            ->thenReturn(\React\Promise\resolve(['success' => true, 'karma' => 3]));
        $child->rpcConfig(new Payload(['service' => $adapter]));

        $response = $child->rpcModifyKarma(new Payload(['term' => 'shrek', 'karma' => 3]));
        $this->assertInstanceOf('React\Promise\FulfilledPromise', $response);
        $response->done(function ($data) {
            $this->assertEquals(['success' => true, 'karma' => 3], $data);
        });

        Phake::verify($adapter)->modifyKarma('shrek', 3);
    }

    public function testRpcFetchKarma()
    {
        $loop = Phake::mock('\React\EventLoop\LoopInterface');
        $messenger = Phake::mock('\WyriHaximus\React\ChildProcess\Messenger\Messenger');
        $child = new ChildProcess($messenger, $loop);
        $adapter = Phake::mock('\PSchwisow\Phergie\Plugin\Karma\Adapter\AdapterInterface');
        Phake::when($adapter)->fetchKarma('shrek')
            ->thenReturn(\React\Promise\resolve(['success' => true, 'karma' => 3]));
        $child->rpcConfig(new Payload(['service' => $adapter]));

        $response = $child->rpcFetchKarma(new Payload(['term' => 'shrek']));
        $this->assertInstanceOf('React\Promise\FulfilledPromise', $response);
        $response->done(function ($data) {
            $this->assertEquals(['success' => true, 'karma' => 3], $data);
        });

        Phake::verify($adapter)->fetchKarma('shrek');
    }
}

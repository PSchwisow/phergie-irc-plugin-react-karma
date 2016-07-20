<?php
/**
 * Phergie plugin for handling requests to increment or decrement counters on specified terms (https://github.com/PSchwisow/phergie-irc-plugin-react-karma)
 *
 * @link https://github.com/PSchwisow/phergie-irc-plugin-react-karma for the canonical source repository
 * @copyright Copyright (c) 2016 Patrick Schwisow (https://github.com/PSchwisow/phergie-irc-plugin-react-karma)
 * @license http://phergie.org/license Simplified BSD License
 * @package PSchwisow\Phergie\Plugin\Karma
 */

namespace PSchwisow\Phergie\Tests\Plugin\Karma\Adapter;

use PSchwisow\Phergie\Plugin\Karma\Adapter\Random;

/**
 * Tests for the Random karma storage adapter.
 *
 * @category PSchwisow
 * @package PSchwisow\Phergie\Plugin\Karma
 */
class RandomTest extends \PHPUnit_Framework_TestCase
{
    public function testSetConfig()
    {
        $adapter = new Random();
        // this method doesn't do anything in this adapter, so just make sure it doesn't throw an error
        $adapter->setConfig(['foo' => 'bar']);
        $this->assertTrue(true);
    }

    public function testModifyKarma()
    {
        $adapter = new Random();
        $response = $adapter->modifyKarma('shrek', 3);
        $this->assertInstanceOf('React\Promise\FulfilledPromise', $response);
        $response->done(function ($data) {
            $this->assertEquals(['success' => true, 'karma' => 3], $data);
        });
    }

    public function testFetchKarma()
    {
        $adapter = new Random();
        $response = $adapter->fetchKarma('shrek');
        $this->assertInstanceOf('React\Promise\FulfilledPromise', $response);
        $response->done(function ($data) {
            $this->assertInternalType('array', $data);
            $this->assertTrue($data['success']);
            // random value, so let's just check that it's an integer
            $this->assertInternalType('integer', $data['karma']);
        });
    }
}

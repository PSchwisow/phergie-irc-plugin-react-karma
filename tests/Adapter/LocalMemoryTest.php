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

use PSchwisow\Phergie\Plugin\Karma\Adapter\LocalMemory;

/**
 * Tests for the LocalMemory karma storage adapter.
 *
 * @category PSchwisow
 * @package PSchwisow\Phergie\Plugin\Karma
 */
class LocalMemoryTest extends \PHPUnit_Framework_TestCase
{
    public function testSetConfig()
    {
        $adapter = new LocalMemory();
        // this method doesn't do anything in this adapter, so just make sure it doesn't throw an error
        $adapter->setConfig(['foo' => 'bar']);
        $this->assertTrue(true);
    }

    public function testModifyKarma()
    {
        $adapter = new LocalMemory();
        $reflClass = new \ReflectionClass('PSchwisow\Phergie\Plugin\Karma\Adapter\LocalMemory');
        $reflKarma = $reflClass->getProperty('karma');
        $reflKarma->setAccessible(true);
        $karma = $reflKarma->getValue($adapter);
        $this->assertNotEquals(3, array_key_exists('shrek', $karma) ? $karma['shrek'] : 0);

        $response = $adapter->modifyKarma('shrek', 3);
        $this->assertInstanceOf('React\Promise\FulfilledPromise', $response);
        $response->done(function ($data) {
            $this->assertEquals(['success' => true, 'karma' => 3], $data);
        });
        $this->assertEquals(3, $reflKarma->getValue($adapter)['shrek']);
    }

    public function testFetchKarma()
    {
        $adapter = new LocalMemory();

        // Test with no value set, return 0 value
        $response = $adapter->fetchKarma('shrek');
        $this->assertInstanceOf('React\Promise\FulfilledPromise', $response);
        $response->done(function ($data) {
            $this->assertEquals(['success' => true, 'karma' => 0], $data);
        });

        // Test with previous value set, should return that value
        $reflClass = new \ReflectionClass('PSchwisow\Phergie\Plugin\Karma\Adapter\LocalMemory');
        $reflKarma = $reflClass->getProperty('karma');
        $reflKarma->setAccessible(true);
        $reflKarma->setValue($adapter, ['shrek' => 5]);

        $response = $adapter->fetchKarma('shrek');
        $this->assertInstanceOf('React\Promise\FulfilledPromise', $response);
        $response->done(function ($data) {
            $this->assertEquals(['success' => true, 'karma' => 5], $data);
        });
        $this->assertEquals(5, $reflKarma->getValue($adapter)['shrek']);
    }
}

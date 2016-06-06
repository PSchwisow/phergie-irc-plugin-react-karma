<?php
/**
 * Child process for Phergie Karma plugin (https://github.com/PSchwisow/phergie-irc-plugin-react-karma)
 *
 * @link https://github.com/PSchwisow/phergie-irc-plugin-react-karma for the canonical source repository
 * @copyright Copyright (c) 2016 Patrick Schwisow (https://github.com/PSchwisow/phergie-irc-plugin-react-karma)
 * @license http://phergie.org/license Simplified BSD License
 * @package PSchwisow\Phergie\Plugin\Karma
 */

namespace PSchwisow\Phergie\Plugin\Karma;

use PSchwisow\Phergie\Plugin\Karma\Adapter\AdapterInterface;
use React\EventLoop\LoopInterface;
use WyriHaximus\React\ChildProcess\Messenger\ChildInterface;
use WyriHaximus\React\ChildProcess\Messenger\Messages\Payload;
use WyriHaximus\React\ChildProcess\Messenger\Messenger;

/**
 * ChildProcess class.
 *
 * @category PSchwisow
 * @package PSchwisow\Phergie\Plugin\Karma
 */
class ChildProcess implements ChildInterface
{
    const DEFAULT_ADAPTER = 'LocalMemory';

    /**
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;

    /**
     * @var \WyriHaximus\React\ChildProcess\Messenger\Messenger
     */
    protected $recipient;

    /**
     * @var \PSchwisow\Phergie\Plugin\Karma\Adapter\AdapterInterface
     */
    protected $adapter;

    /**
     * Create child process and set up RPC endpoints.
     *
     * @param \WyriHaximus\React\ChildProcess\Messenger\Messenger $messenger
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function __construct(Messenger $messenger, LoopInterface $loop)
    {
        $this->recipient = $messenger;
        $this->loop = $loop;

        $this->recipient->registerRpc('config', [$this, 'rpcConfig']);
        $this->recipient->registerRpc('modifyKarma', [$this, 'rpcModifyKarma']);
        $this->recipient->registerRpc('fetchKarma', [$this, 'rpcFetchKarma']);
    }

    /**
     * @param \WyriHaximus\React\ChildProcess\Messenger\Messenger $messenger
     * @param \React\EventLoop\LoopInterface $loop
     * @return void
     */
    public static function create(Messenger $messenger, LoopInterface $loop)
    {
        new static($messenger, $loop);
    }

    /**
     * RPC Call - Set configuration
     *
     * @param \WyriHaximus\React\ChildProcess\Messenger\Messages\Payload $payload
     * @return \React\Promise\FulfilledPromise|\React\Promise\Promise
     */
    public function rpcConfig(Payload $payload) {
        try {
            $this->setAdapter(isset($payload['service']) ? $payload['service'] : self::DEFAULT_ADAPTER);
            if (!empty($payload['service_config'])) {
                $this->adapter->setConfig($payload['service_config']);
            }
        } catch (\Exception $ex) {
            return \React\Promise\reject([
                'success' => false,
                'error'   => $ex->getMessage()
            ]);
        }

        return \React\Promise\resolve([
            'success' => true
        ]);
    }

    /**
     * Set adapter
     *
     * @param string
     * @return void
     * @throws \InvalidArgumentException
     */
    public function setAdapter($adapter)
    {
        if (is_string($adapter)) {
            if (class_exists($adapter)) {
                $class = $adapter;
            } elseif (class_exists("PSchwisow\\Phergie\\Plugin\\Karma\\Adapter\\{$adapter}")) {
                $class = "PSchwisow\\Phergie\\Plugin\\Karma\\Adapter\\{$adapter}";
            } else {
                throw new \InvalidArgumentException("Class not found: '$adapter'");
            }
            $adapter = new $class;
        }

        if (!$adapter instanceof AdapterInterface) {
            throw new \InvalidArgumentException(
                'Karma service adapter must implement PSchwisow\Phergie\Plugin\Karma\Adapter\AdapterInterface'
            );
        }
        $this->adapter = $adapter;

    }

    /**
     * RPC Call - Modify karma value for specified term
     *
     * @param \WyriHaximus\React\ChildProcess\Messenger\Messages\Payload $payload
     * @return \React\Promise\FulfilledPromise|\React\Promise\Promise
     */
    public function rpcModifyKarma(Payload $payload) {
        return $this->adapter->modifyKarma($payload['term'], $payload['karma']);
    }

    /**
     * RPC Call - Fetch karma value for specified term
     *
     * @param \WyriHaximus\React\ChildProcess\Messenger\Messages\Payload $payload
     * @return \React\Promise\FulfilledPromise|\React\Promise\Promise
     */
    public function rpcFetchKarma(Payload $payload) {
        return $this->adapter->fetchKarma($payload['term']);
    }
}

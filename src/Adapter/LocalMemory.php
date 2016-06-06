<?php
/**
 * Adapter that provides in-memory karma storage (https://github.com/PSchwisow/phergie-irc-plugin-react-karma)
 *
 * @link https://github.com/PSchwisow/phergie-irc-plugin-react-karma for the canonical source repository
 * @copyright Copyright (c) 2016 Patrick Schwisow (https://github.com/PSchwisow/phergie-irc-plugin-react-karma)
 * @license http://phergie.org/license Simplified BSD License
 * @package PSchwisow\Phergie\Plugin\Karma
 */

namespace PSchwisow\Phergie\Plugin\Karma\Adapter;

/**
 * LocalMemory adapter class.
 *
 * @category PSchwisow
 * @package PSchwisow\Phergie\Plugin\Karma
 */
class LocalMemory implements AdapterInterface
{
    /**
     * @var array
     */
    protected $karma = [];
    
    /**
     * Set configuration
     *
     * @param array $config
     * @return void
     */
    public function setConfig(array $config) {
        // do nothing
    }

    /**
     * Modify karma value for specified term
     *
     * @param string $term
     * @param integer $karma
     * @return \React\Promise\FulfilledPromise|\React\Promise\Promise
     */
    public function modifyKarma($term, $karma) {
        $this->karma[$term] = $karma;
        return \React\Promise\resolve([
            'success' => true,
            'karma'   => $this->karma[$term]
        ]);
    }

    /**
     * RPC Call - Fetch karma value for specified term
     *
     * @param string $term
     * @return \React\Promise\FulfilledPromise|\React\Promise\Promise
     */
    public function fetchKarma($term) {
        return \React\Promise\resolve([
            'success' => true,
            'karma'   => array_key_exists($term, $this->karma) ? $this->karma[$term] : 0
        ]);
    }
}

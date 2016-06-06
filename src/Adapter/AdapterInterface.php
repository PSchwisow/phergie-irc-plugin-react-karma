<?php
/**
 * Interface for adapters for Phergie Karma plugin backend (https://github.com/PSchwisow/phergie-irc-plugin-react-karma)
 *
 * @link https://github.com/PSchwisow/phergie-irc-plugin-react-karma for the canonical source repository
 * @copyright Copyright (c) 2016 Patrick Schwisow (https://github.com/PSchwisow/phergie-irc-plugin-react-karma)
 * @license http://phergie.org/license Simplified BSD License
 * @package PSchwisow\Phergie\Plugin\Karma
 */

namespace PSchwisow\Phergie\Plugin\Karma\Adapter;

/**
 * Interface for backend adapters
 *
 * @category PSchwisow
 * @package PSchwisow\Phergie\Plugin\Karma\Adapter
 */
interface AdapterInterface
{
    /**
     * Set configuration
     *
     * @param array $config
     * @return void
     */
    public function setConfig(array $config);

    /**
     * Modify karma value for specified term
     *
     * @param string $term
     * @param integer $karma
     * @return \React\Promise\FulfilledPromise|\React\Promise\Promise
     */
    public function modifyKarma($term, $karma);

    /**
     * RPC Call - Fetch karma value for specified term
     *
     * @param string $term
     * @return \React\Promise\FulfilledPromise|\React\Promise\Promise
     */
    public function fetchKarma($term);
}

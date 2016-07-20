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
use PSchwisow\Phergie\Plugin\Karma\Plugin;
use WyriHaximus\React\ChildProcess\Messenger\Messages\Payload;

/**
 * Tests for the Plugin class.
 *
 * @category PSchwisow
 * @package PSchwisow\Phergie\Plugin\Karma
 */
class PluginTest extends \PHPUnit_Framework_TestCase
{
    private $event;

    private $queue;

    private $emitter;

    private $logger;

    private $loop;

    private $plugin;

    private $messenger;

    protected function setUp()
    {
        $this->event = Phake::mock('\Phergie\Irc\Plugin\React\Command\CommandEvent');
        $this->queue = Phake::mock('\Phergie\Irc\Bot\React\EventQueueInterface');
        $this->emitter = Phake::mock('\Evenement\EventEmitterInterface');
        $this->logger = Phake::mock('\Psr\Log\LoggerInterface');
        $this->loop = Phake::mock('\React\EventLoop\LoopInterface');
        $this->messenger = Phake::mock('\WyriHaximus\React\ChildProcess\Messenger\Messenger');
        $this->plugin = $this->getPlugin();
    }

    protected function getPlugin()
    {
        $config = [
//            'accountId' => 'ACCOUNT'
        ];
        $plugin = new Plugin($config);
        $plugin->setEventEmitter($this->emitter);
        $plugin->setLogger($this->logger);

        // don't call $plugin->setLoop($this->loop); because we don't want to really spawn a process
        $reflClass = new \ReflectionClass('PSchwisow\Phergie\Plugin\Karma\Plugin');
        $reflLoop = $reflClass->getProperty('eventLoop');
        $reflLoop->setAccessible(true);
        $reflLoop->setValue($plugin, $this->loop);

        $reflMessenger = $reflClass->getProperty('messenger');
        $reflMessenger->setAccessible(true);
        $reflMessenger->setValue($plugin, $this->messenger);

        // @todo set user messages through config when that is supported
        $reflUserMessages = $reflClass->getProperty('userMessages');
        $reflUserMessages->setAccessible(true);
        $reflUserMessages->setValue(
            $plugin,
            [
                'karma++'       => [
                    '%owner% karma is on the rise.',
                ],
                'karma--'       => [
                    '%owner% takes a karma hit.',
                ],
                'compare-true'  => [
                    'No kidding, %owner% totally kicks %owned%\'s ass !',
                ],
                'compare-false' => [
                    'I\'d say %owner% is better than %owned%.',
                ]
            ]
        );

        return $plugin;
    }

    /**
     * Tests that getSubscribedEvents() returns an array.
     */
    public function testGetSubscribedEvents()
    {
        $this->assertInternalType('array', $this->plugin->getSubscribedEvents());
    }

    public function testHandleKarmaCommandNeutral()
    {
        Phake::when($this->event)->getCustomParams()->thenReturn(['donkey']);
        Phake::when($this->event)->getSource()->thenReturn('#channel');
        Phake::when($this->event)->getCommand()->thenReturn('PRIVMSG');
        Phake::when($this->event)->getNick()->thenReturn('shrek');
        Phake::when($this->messenger)
            ->rpc($this->isInstanceOf('WyriHaximus\React\ChildProcess\Messenger\Messages\Rpc'))
            ->thenReturn(\React\Promise\resolve(new Payload(['success' => true, 'karma' => 0])));
        $this->plugin->handleKarmaCommand($this->event, $this->queue);

        Phake::verify($this->queue)->ircPrivmsg('#channel', 'shrek: donkey has neutral karma.');
    }

    public function testHandleKarmaCommandPositive()
    {
        Phake::when($this->event)->getCustomParams()->thenReturn(['donkey']);
        Phake::when($this->event)->getSource()->thenReturn('#channel');
        Phake::when($this->event)->getCommand()->thenReturn('PRIVMSG');
        Phake::when($this->event)->getNick()->thenReturn('shrek');
        Phake::when($this->messenger)
            ->rpc($this->isInstanceOf('WyriHaximus\React\ChildProcess\Messenger\Messages\Rpc'))
            ->thenReturn(\React\Promise\resolve(new Payload(['success' => true, 'karma' => 3])));
        $this->plugin->handleKarmaCommand($this->event, $this->queue);

        Phake::verify($this->queue)->ircPrivmsg('#channel', 'shrek: donkey has karma of 3.');
    }

    public function testHandleKarmaCommandNegative()
    {
        Phake::when($this->event)->getCustomParams()->thenReturn(['me']);
        Phake::when($this->event)->getSource()->thenReturn('#channel');
        Phake::when($this->event)->getCommand()->thenReturn('PRIVMSG');
        Phake::when($this->event)->getNick()->thenReturn('shrek');
        Phake::when($this->messenger)
            ->rpc($this->isInstanceOf('WyriHaximus\React\ChildProcess\Messenger\Messages\Rpc'))
            ->thenReturn(\React\Promise\resolve(new Payload(['success' => true, 'karma' => -3])));
        $this->plugin->handleKarmaCommand($this->event, $this->queue);

        Phake::verify($this->queue)->ircPrivmsg('#channel', 'shrek: You have karma of -3.');
    }

    public function testHandleKarmaCommandFixed()
    {
        Phake::when($this->event)->getCustomParams()->thenReturn(['phergie']);
        Phake::when($this->event)->getSource()->thenReturn('#channel');
        Phake::when($this->event)->getCommand()->thenReturn('PRIVMSG');
        Phake::when($this->event)->getNick()->thenReturn('shrek');
        $this->plugin->handleKarmaCommand($this->event, $this->queue);

        Phake::verify($this->queue)->ircPrivmsg('#channel', 'shrek: Phergie has karma of awesome.');
    }

    public function testHandleKarmaCommandNoQuery()
    {
        Phake::when($this->event)->getCustomParams()->thenReturn([]);
        Phake::when($this->event)->getSource()->thenReturn('#channel');
        Phake::when($this->event)->getCommand()->thenReturn('PRIVMSG');
        $this->plugin->handleKarmaCommand($this->event, $this->queue);

        Phake::verify($this->queue, Phake::atLeast(2))->ircPrivmsg('#channel', $this->isType('string'));
    }

    /**
     * Tests handleKarmaHelp().
     */
    public function testHandleKarmaHelp()
    {
        Phake::when($this->event)->getCustomParams()->thenReturn([]);
        Phake::when($this->event)->getSource()->thenReturn('#channel');
        Phake::when($this->event)->getCommand()->thenReturn('PRIVMSG');
        $this->plugin->handleKarmaHelp($this->event, $this->queue);

        Phake::verify($this->queue, Phake::atLeast(2))->ircPrivmsg('#channel', $this->isType('string'));
    }

    public function testHandleReincarnateCommand()
    {
        Phake::when($this->event)->getCustomParams()->thenReturn(['donkey']);
        Phake::when($this->event)->getSource()->thenReturn('#channel');
        Phake::when($this->event)->getCommand()->thenReturn('PRIVMSG');
        Phake::when($this->event)->getNick()->thenReturn('shrek');
        Phake::when($this->messenger)
            ->rpc($this->isInstanceOf('WyriHaximus\React\ChildProcess\Messenger\Messages\Rpc'))
            ->thenReturn(\React\Promise\resolve(new Payload(['success' => true, 'karma' => 0])));
        $this->plugin->handleReincarnateCommand($this->event, $this->queue);

        Phake::verify($this->logger)->debug("[Karma]'donkey' was reincarnated by shrek.");
    }

    public function testHandleReincarnateCommandNoQuery()
    {
        Phake::when($this->event)->getCustomParams()->thenReturn([]);
        Phake::when($this->event)->getSource()->thenReturn('#channel');
        Phake::when($this->event)->getCommand()->thenReturn('PRIVMSG');
        $this->plugin->handleReincarnateCommand($this->event, $this->queue);

        Phake::verify($this->queue, Phake::atLeast(2))->ircPrivmsg('#channel', $this->isType('string'));
    }

    /**
     * Tests handleReincarnateHelp().
     */
    public function testHandleReincarnateHelp()
    {
        Phake::when($this->event)->getCustomParams()->thenReturn([]);
        Phake::when($this->event)->getSource()->thenReturn('#channel');
        Phake::when($this->event)->getCommand()->thenReturn('PRIVMSG');
        $this->plugin->handleReincarnateHelp($this->event, $this->queue);

        Phake::verify($this->queue, Phake::atLeast(2))->ircPrivmsg('#channel', $this->isType('string'));
    }

    public function testHandleIrcReceivedIncrementKarma() {
        $event = Phake::mock('Phergie\Irc\Event\UserEvent');
        Phake::when($event)->getParams()->thenReturn([
            'text' => 'donkey++'
        ]);
        Phake::when($event)->getSource()->thenReturn('#channel');
        Phake::when($event)->getNick()->thenReturn('shrek');
        Phake::when($this->messenger)
            ->rpc($this->isInstanceOf('WyriHaximus\React\ChildProcess\Messenger\Messages\Rpc'))
            ->thenReturn(\React\Promise\resolve(new Payload(['success' => true, 'karma' => 3])))
            ->thenReturn(\React\Promise\resolve(new Payload(['success' => true, 'karma' => 3])));

        $this->plugin->handleIrcReceived($event, $this->queue);

        Phake::verify($this->queue)->ircPrivmsg('#channel', 'donkey karma is on the rise.');
    }

    public function testHandleIrcReceivedIncrementKarmaSelf() {
        $event = Phake::mock('Phergie\Irc\Event\UserEvent');
        Phake::when($event)->getParams()->thenReturn([
            'text' => 'shrek++'
        ]);
        Phake::when($event)->getSource()->thenReturn('#channel');
        Phake::when($event)->getNick()->thenReturn('shrek');
        Phake::when($this->messenger)
            ->rpc($this->isInstanceOf('WyriHaximus\React\ChildProcess\Messenger\Messages\Rpc'))
            ->thenReturn(\React\Promise\resolve(new Payload(['success' => true, 'karma' => 3])));

        $this->plugin->handleIrcReceived($event, $this->queue);

        Phake::verify($this->queue)->ircPrivmsg('#channel', 'You can\'t give yourself karma.');
    }

    public function testHandleIrcReceivedDecrementKarma() {
        $event = Phake::mock('Phergie\Irc\Event\UserEvent');
        Phake::when($event)->getParams()->thenReturn([
            'text' => 'donkey--'
        ]);
        Phake::when($event)->getSource()->thenReturn('#channel');
        Phake::when($event)->getNick()->thenReturn('shrek');
        Phake::when($this->messenger)
            ->rpc($this->isInstanceOf('WyriHaximus\React\ChildProcess\Messenger\Messages\Rpc'))
            ->thenReturn(\React\Promise\resolve(new Payload(['success' => true, 'karma' => 3])))
            ->thenReturn(\React\Promise\resolve(new Payload(['success' => true, 'karma' => 2])));

        $this->plugin->handleIrcReceived($event, $this->queue);

        Phake::verify($this->queue)->ircPrivmsg('#channel', 'donkey takes a karma hit.');
    }

    public function testHandleIrcReceivedCompareKarmaGreaterThanWin() {
        $event = Phake::mock('Phergie\Irc\Event\UserEvent');
        Phake::when($event)->getParams()->thenReturn([
            'text' => 'shrek > donkey'
        ]);
        Phake::when($event)->getSource()->thenReturn('#channel');
        Phake::when($event)->getNick()->thenReturn('shrek');
        Phake::when($this->messenger)
            ->rpc($this->isInstanceOf('WyriHaximus\React\ChildProcess\Messenger\Messages\Rpc'))
            ->thenReturn(\React\Promise\resolve(new Payload(['success' => true, 'karma' => 3])))
            ->thenReturn(\React\Promise\resolve(new Payload(['success' => true, 'karma' => 2])));

        $this->plugin->handleIrcReceived($event, $this->queue);

        Phake::verify($this->queue)->ircPrivmsg('#channel', 'No kidding, shrek totally kicks donkey\'s ass !');
    }

    public function testHandleIrcReceivedCompareKarmaGreaterThanLose() {
        $event = Phake::mock('Phergie\Irc\Event\UserEvent');
        Phake::when($event)->getParams()->thenReturn([
            'text' => 'shrek > donkey'
        ]);
        Phake::when($event)->getSource()->thenReturn('#channel');
        Phake::when($event)->getNick()->thenReturn('shrek');
        Phake::when($this->messenger)
            ->rpc($this->isInstanceOf('WyriHaximus\React\ChildProcess\Messenger\Messages\Rpc'))
            ->thenReturn(\React\Promise\resolve(new Payload(['success' => true, 'karma' => 3])))
            ->thenReturn(\React\Promise\resolve(new Payload(['success' => true, 'karma' => 4])));

        $this->plugin->handleIrcReceived($event, $this->queue);

        Phake::verify($this->queue)->ircPrivmsg('#channel', 'I\'d say donkey is better than shrek.');
    }

    public function testHandleIrcReceivedCompareKarmaGreaterThanFixed() {
        $event = Phake::mock('Phergie\Irc\Event\UserEvent');
        Phake::when($event)->getParams()->thenReturn([
            'text' => 'shrek > Phergie'
        ]);
        Phake::when($event)->getSource()->thenReturn('#channel');
        Phake::when($event)->getNick()->thenReturn('shrek');

        $this->plugin->handleIrcReceived($event, $this->queue);

        Phake::verify($this->queue, Phake::never())->ircPrivmsg;
    }

    public function testHandleIrcReceivedCompareKarmaGreaterThanEverything() {
        $event = Phake::mock('Phergie\Irc\Event\UserEvent');
        Phake::when($event)->getParams()->thenReturn([
            'text' => 'shrek > all'
        ]);
        Phake::when($event)->getSource()->thenReturn('#channel');
        Phake::when($event)->getNick()->thenReturn('donkey');
        Phake::when($this->messenger)
            ->rpc($this->isInstanceOf('WyriHaximus\React\ChildProcess\Messenger\Messages\Rpc'))
            ->thenReturn(\React\Promise\resolve(new Payload(['success' => true, 'karma' => 3])))
            ->thenReturn(\React\Promise\resolve(new Payload(['success' => true, 'karma' => 2])));

        $this->plugin->handleIrcReceived($event, $this->queue);

        Phake::verify($this->queue)->ircPrivmsg('#channel', 'shrek karma is on the rise.');
        Phake::verify($this->queue)->ircPrivmsg('#channel', 'I\'d say shrek is better than all.');
    }

    public function testHandleIrcReceivedCompareKarmaLessThanWin() {
        $event = Phake::mock('Phergie\Irc\Event\UserEvent');
        Phake::when($event)->getParams()->thenReturn([
            'text' => 'shrek < donkey'
        ]);
        Phake::when($event)->getSource()->thenReturn('#channel');
        Phake::when($event)->getNick()->thenReturn('shrek');
        Phake::when($this->messenger)
            ->rpc($this->isInstanceOf('WyriHaximus\React\ChildProcess\Messenger\Messages\Rpc'))
            ->thenReturn(\React\Promise\resolve(new Payload(['success' => true, 'karma' => 3])))
            ->thenReturn(\React\Promise\resolve(new Payload(['success' => true, 'karma' => 4])));

        $this->plugin->handleIrcReceived($event, $this->queue);

        Phake::verify($this->queue)->ircPrivmsg('#channel', 'No kidding, donkey totally kicks shrek\'s ass !');
    }

    public function testHandleIrcReceivedCompareKarmaLessThanLose() {
        $event = Phake::mock('Phergie\Irc\Event\UserEvent');
        Phake::when($event)->getParams()->thenReturn([
            'text' => 'shrek < donkey'
        ]);
        Phake::when($event)->getSource()->thenReturn('#channel');
        Phake::when($event)->getNick()->thenReturn('shrek');
        Phake::when($this->messenger)
            ->rpc($this->isInstanceOf('WyriHaximus\React\ChildProcess\Messenger\Messages\Rpc'))
            ->thenReturn(\React\Promise\resolve(new Payload(['success' => true, 'karma' => 3])))
            ->thenReturn(\React\Promise\resolve(new Payload(['success' => true, 'karma' => 2])));

        $this->plugin->handleIrcReceived($event, $this->queue);

        Phake::verify($this->queue)->ircPrivmsg('#channel', 'I\'d say shrek is better than donkey.');
    }

    public function testHandleIrcReceivedCompareKarmaEverthingLessThan() {
        $event = Phake::mock('Phergie\Irc\Event\UserEvent');
        Phake::when($event)->getParams()->thenReturn([
            'text' => 'everything < donkey'
        ]);
        Phake::when($event)->getSource()->thenReturn('#channel');
        Phake::when($event)->getNick()->thenReturn('donkey');
        Phake::when($this->messenger)
            ->rpc($this->isInstanceOf('WyriHaximus\React\ChildProcess\Messenger\Messages\Rpc'))
            ->thenReturn(\React\Promise\resolve(new Payload(['success' => true, 'karma' => 3])))
            ->thenReturn(\React\Promise\resolve(new Payload(['success' => true, 'karma' => 3])));

        $this->plugin->handleIrcReceived($event, $this->queue);

        Phake::verify($this->queue)->ircPrivmsg('#channel', 'You can\'t give yourself karma.');
        Phake::verify($this->queue)->ircPrivmsg('#channel', 'I\'d say everything is better than donkey.');
    }

    public function testHandleIrcReceivedNotKarma() {
        $event = Phake::mock('Phergie\Irc\Event\UserEvent');
        Phake::when($event)->getParams()->thenReturn([
            'text' => 'nothing related to karma'
        ]);

        $this->plugin->handleIrcReceived($event, $this->queue);

        Phake::verify($this->queue, Phake::never())->ircPrivmsg;
    }
}

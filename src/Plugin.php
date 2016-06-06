<?php
/**
 * Phergie plugin for handling requests to increment or decrement counters on specified terms (https://github.com/PSchwisow/phergie-irc-plugin-react-karma)
 *
 * @link https://github.com/PSchwisow/phergie-irc-plugin-react-karma for the canonical source repository
 * @copyright Copyright (c) 2016 Patrick Schwisow (https://github.com/PSchwisow/phergie-irc-plugin-react-karma)
 * @license http://phergie.org/license Simplified BSD License
 * @package PSchwisow\Phergie\Plugin\Karma
 */

namespace PSchwisow\Phergie\Plugin\Karma;

use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\Event\UserEvent;
use Phergie\Irc\Plugin\React\Command\CommandEvent;
use React\EventLoop\LoopInterface;
use WyriHaximus\React\ChildProcess\Messenger\Factory as MessengerFactory;
use WyriHaximus\React\ChildProcess\Messenger\Messages\Factory as MessageFactory;
use WyriHaximus\React\ChildProcess\Messenger\Messenger;

/**
 * Plugin class.
 *
 * @category PSchwisow
 * @package PSchwisow\Phergie\Plugin\Karma
 */
class Plugin extends AbstractPlugin
{
    /**
     * @var \WyriHaximus\React\ChildProcess\Messenger\Messenger
     */
    protected $messenger;

    /**
     * @var array
     */
    protected $userMessages = [
        'karma++'       => [
            '%owner% karma is on the rise',
            '%owner% is getting more karma',
            '%owner% karma, karma every where and this one is for you',
            '%owner% whaaat?!?! karma for you',
            '%owner% this is karma, you will take it and you will like it',
            '%owner% loves karma and getting more of it',
        ],
        'karma--'       => [
            '%owner% takes a karma hit',
            '%owner% ouch, losing karma sucks',
            '%owner% that\'s got to hurt, goodbye karma',
        ],
        'compare-true'  => [
            'No kidding, %owner% totally kicks %owned%\'s ass !',
            'True that.',
            'I concur.',
            'Yay, %owner% ftw !',
            '%owner% is made of WIN!',
            'Nothing can beat %owner%!'
        ],
        'compare-false' => [
            'No sir, not at all.',
            'You\'re wrong dude, %owner% wins.',
            'I\'d say %owner% is better than %owned%.',
            'You must be joking, %owner% FTW!',
            '%owned% is made of LOSE!',
            '%owned% = Epic Fail.'
        ]
    ];

    protected $fixedKarma = [
        'phergie'       => 'Phergie has karma of awesome',
        'pi'            => 'pi has karma of 3.1415926535898',
        'chucknorris'   => 'Chuck Norris has karma of Warning: Integer out of range',
        'chuck norris'  => 'Chuck Norris has karma of Warning: Integer out of range',
        'c'             => 'c has karma of 299 792 458 m/s',
        'e'             => 'e has karma of 2.718281828459',
        'euler'         => 'Euler has karma of 0.57721566490153286061',
        'mole'          => 'mole has karma of 6.02214e23 molecules',
        'avogadro'      => 'Avogadro has karma of 6.02214e23 molecules',
        'mc^2'          => 'mc^2 has karma of E',
        'mc2'           => 'mc2 has karma of E',
        'spoon'         => 'spoon has no karma - there is no spoon',
        'i'             => 'i haz big karma',
        'karma'         => 'The karma law says that all living creatures are responsible for their karma - their actions and the effects of their actions. You should watch yours.'
    ];

    /**
     * @var array
     */
    protected $config = [];

    /**
     * Accepts plugin configuration.
     *
     * Supported keys:
     *
     *
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Sets the event loop instance.
     *
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function setLoop(LoopInterface $loop)
    {
        parent::setLoop($loop);

        \React\Promise\Timer\timeout(
            MessengerFactory::parentFromClass('PSchwisow\Phergie\Plugin\Karma\ChildProcess', $loop),
            10.0,
            $loop
        )
            ->then(
                function (Messenger $messenger) use ($loop) {
                    $this->logDebug('got a messenger');
                    $this->messenger = $messenger;
                    $messenger->on('error', function ($e) {
                        $this->logDebug('Error: ' . var_export($e, true));
                    });
                    $this->messenger->rpc(MessageFactory::rpc('config', $this->config))
                        ->then(function ($payload) {
                            $this->logDebug('configuration sent to child. Response: ' . var_export($payload, true));
                        });
                },
                function ($error) {
                    if ($error instanceof \React\Promise\Timer\TimeoutException) {
                        // the operation has failed due to a timeout
                        $this->logDebug('TIMEOUT');
                    } else {
                        // the input operation has failed due to some other error
                        $this->logDebug('OTHER ERROR');
                    }
                }
            );
    }

    /**
     * Log debugging messages
     *
     * @param string $message
     */
    public function logDebug($message)
    {
        $this->logger->debug('[Karma]' . $message);
    }

    /**
     *
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            'command.karma' => 'handleKarmaCommand',
            'command.reincarnate' => 'handleReincarnateCommand',
            'command.karma.help' => 'handleKarmaHelp',
            'command.reincarnate.help' => 'handleReincarnateHelp',
            'irc.received.privmsg' => 'handleIrcReceived',
        ];
    }

    /**
     * Karma Command
     *
     * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
     * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
     */
    public function handleKarmaCommand(CommandEvent $event, Queue $queue)
    {
        $params = $event->getCustomParams();
        if (count($params) < 1) {
            $this->handleKarmaHelp($event, $queue);
            return;
        }

        $term = $params[0];
        $nick = $event->getNick();

        $fixedKarma = $this->fetchFixedKarma($term);
        if ($fixedKarma) {
            $message = $nick . ': ' . $fixedKarma;
            $queue->ircPrivmsg($event->getSource(), $message);
            return;
        }

        $this->messenger->rpc(MessageFactory::rpc('fetchKarma', [
            'term' => $this->getCanonicalTerm($term, $nick)
        ]))->then(function ($payload) use ($event, $queue, $term, $nick) {
            $this->logDebug('payload: ' . var_export($payload, true));
            $karma = $payload['karma'];
            $message = $nick . ': ';
            if ($term == 'me') {
                $message .= 'You have';
            } else {
                $message .= $term . ' has';
            }
            $message .= ' ';
            if ($karma) {
                $message .= 'karma of ' . $karma;
            } else {
                $message .= 'neutral karma';
            }
            $message .= '.';
            $queue->ircPrivmsg($event->getSource(), $message);
        });
    }

    /**
     * Reincarnate Command - Resets the karma for a term to 0.
     *
     * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
     * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
     */
    public function handleReincarnateCommand(CommandEvent $event, Queue $queue)
    {
        $params = $event->getCustomParams();
        if (count($params) < 1) {
            $this->handleReincarnateHelp($event, $queue);
        } else {
            $term = $params[0];
            $nick = $event->getNick();
            $this->messenger->rpc(MessageFactory::rpc('modifyKarma', [
                'term' => $this->getCanonicalTerm($term, $nick),
                'karma' => 0
            ]))->then(function ($payload) use ($event, $queue, $term, $nick) {
                $this->logDebug('payload: ' . var_export($payload, true));
                $this->logDebug("'$term' was reincarnated by $nick.");
            });
        }
    }

    /**
     * Karma Command Help
     *
     * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
     * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
     */
    public function handleKarmaHelp(CommandEvent $event, Queue $queue)
    {
        $this->sendHelpReply($event, $queue, [
            'Usage: karma term',
            'term - the term to check (or \'me\' to check for your current nick)',
            'Requests the current karma value of the specified term.',
        ]);
    }

    /**
     * Reincarnate Command Help
     *
     * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
     * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
     */
    public function handleReincarnateHelp(CommandEvent $event, Queue $queue)
    {
        $this->sendHelpReply($event, $queue, [
            'Usage: reincarnate term',
            'term - the term to reset (or \'me\' for your current nick)',
            'Resets the current karma value of the specified term.',
        ]);
    }

    /**
     * @param \Phergie\Irc\Event\UserEvent $event
     * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
     */
    public function handleIrcReceived(UserEvent $event, Queue $queue)
    {
        $params = $event->getParams();
        $message = $params['text'];
        $termPattern = '\S+?|\([^<>]+?\)+';
        $actionPattern = '(?P<action>\+\+|--)';
        $modifyPattern = <<<REGEX
        {^
        (?J) # allow overwriting capture names
        \s*  # ignore leading whitespace
        (?:  # start with ++ or -- before the term
            $actionPattern
            (?P<term>$termPattern)
        |   # follow the term with ++ or --
            (?P<term>$termPattern)
            $actionPattern # allow no whitespace between the term and the action
        )
        $}ix
REGEX;
        $versusPattern = <<<REGEX
        {^
            (?P<term0>$termPattern)
                \s+(?P<method><|>)\s+
            (?P<term1>$termPattern)$#
        $}ix
REGEX;
        $match = null;
        if (preg_match($modifyPattern, $message, $match)) {
            $this->modifyKarma($match['term'], $match['action'], $event, $queue);
        } elseif (preg_match($versusPattern, $message, $match)) {
            $term0 = trim($match['term0']);
            $term1 = trim($match['term1']);
            $method = $match['method'];
            $this->compareKarma($term0, $term1, $method, $event, $queue);
        }
    }

    /**
     * Get the canonical form of a given term.
     *
     * In the canonical form all sequences of whitespace
     * are replaced by a single space and all characters
     * are lowercased.
     *
     * @param string $term Term for which a canonical form is required
     * @param string $nick The sender's nick (used if the term is 'me')
     *
     * @return string Canonical term
     */
    protected function getCanonicalTerm($term, $nick)
    {
        $canonicalTerm = strtolower(preg_replace('|\s+|', ' ', trim($term, '()')));
        switch ($canonicalTerm) {
            case 'me':
                $canonicalTerm = strtolower($nick);
                break;
            case 'all':
            case '*':
            case 'everything':
                $canonicalTerm = 'everything';
                break;
        }
        return $canonicalTerm;
    }

    /**
     * Check to see if the term has fixed karma.
     *
     * @param string $term
     * @return bool|string Return the fixed karma message or false if none is found.
     */
    protected function fetchFixedKarma($term) {
        // skip the full logic for canonical terms because none of the edge cases can be fixed karma
        $canonicalTerm = strtolower(preg_replace('|\s+|', ' ', trim($term, '()')));
        if (array_key_exists($canonicalTerm, $this->fixedKarma)) {
            return $this->fixedKarma[$canonicalTerm];
        }
        return false;
    }

    /**
     * Compares the karma between two terms. Optionally increases/decreases
     * the karma of either term.
     *
     * @param string $term0  First term
     * @param string $term1  Second term
     * @param string $method Comparison method (< or >)
     * @param \Phergie\Irc\Event\UserEvent $event
     * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
     */
    protected function compareKarma($term0, $term1, $method, UserEvent $event, Queue $queue)
    {
        $nick = $event->getNick();
        $canonicalTerm0 = $this->getCanonicalTerm($term0, $nick);
        $canonicalTerm1 = $this->getCanonicalTerm($term1, $nick);

        $fixedKarma0 = $this->fetchFixedKarma($term0);
        $fixedKarma1 = $this->fetchFixedKarma($term1);
        if ($fixedKarma0 || $fixedKarma1) {
            return;
        }

        if ($canonicalTerm0 == 'everything') {
            $change = $method == '<' ? '++' : '--';
            $karma0 = ['karma' => 0];
            $karma1 = $this->modifyKarma($term1, $change, $event, $queue);
        } elseif ($canonicalTerm1 == 'everything') {
            $change = $method == '<' ? '--' : '++';
            $karma0 = $this->modifyKarma($term0, $change, $event, $queue);
            $karma1 = ['karma' => 0];
        } else {
            $karma0 = $this->messenger->rpc(MessageFactory::rpc('fetchKarma', [
                'term' => $this->getCanonicalTerm($term0, $nick)
            ]));
            $karma1 = $this->messenger->rpc(MessageFactory::rpc('fetchKarma', [
                'term' => $this->getCanonicalTerm($term1, $nick)
            ]));
        }
        \React\Promise\all([$karma0, $karma1])
            ->then(function ($payload) use ($event, $queue, $method, $term0, $term1) {
                $this->logDebug('payload karma0: ' . var_export($payload[0], true));
                $this->logDebug('payload karma1: ' . var_export($payload[1], true));
                // Combining the first and second branches here causes an odd
                // single-line lapse in code coverage, but the lapse disappears if
                // they're separated
                if ($method == '<' && $payload[0]['karma'] < $payload[1]['karma']) {
                    $replyType = 'compare-true';
                } elseif ($method == '>' && $payload[0]['karma'] > $payload[1]['karma']) {
                    $replyType = 'compare-true';
                } else {
                    $replyType = 'compare-false';
                }
                $message = (max($payload[0]['karma'], $payload[1]['karma']) == $payload[0]['karma'])
                    ? $this->getUserMessage($replyType, $term0, $term1)
                    : $this->getUserMessage($replyType, $term1, $term0);
                $queue->ircPrivmsg($event->getSource(), $message);
            });
   }

    /**
     * Modifies a term's karma.
     *
     * @param string $term   Term to modify
     * @param string $action Karma action (either ++ or --)
     * @param \Phergie\Irc\Event\UserEvent $event
     * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
     * @return bool|\React\Promise\Promise
     */
    protected function modifyKarma($term, $action, UserEvent $event, Queue $queue)
    {
        $nick = $event->getNick();
        $canonicalTerm = $this->getCanonicalTerm($term, $nick);
        if ($canonicalTerm == strtolower($nick)) {
            $message = 'You can\'t give yourself karma';
            $queue->ircPrivmsg($event->getSource(), $message);
            return false;
        }

        $karma = 0;
        return $this->messenger
            ->rpc(MessageFactory::rpc('fetchKarma', [
                'term' => $canonicalTerm
            ]))
            ->then(function ($payload) use ($event, $queue, $action, $term, $canonicalTerm, &$karma) {
                $this->logDebug('payload: ' . var_export($payload, true));
                $karma = $payload['karma'] + (($action == '++') ? 1 : -1);
                return MessageFactory::rpc('modifyKarma', [
                    'term' => $canonicalTerm,
                    'karma' => $karma
                ]);
            })
            ->then([$this->messenger, 'rpc'])
            ->then(function ($payload) use ($event, $queue, $action, $term) {
                $this->logDebug('payload: ' . var_export($payload, true));
                $queue->ircPrivmsg($event->getSource(), $this->getUserMessage('karma' . $action, $term));
                return $payload['karma'];
            });
    }

    /**
     * Get a random message for different action types.
     *
     * @param string $type  'karma++', 'karma--', 'compare-true', or 'compare-false'
     * @param string $owner Term to replace '%owner%' in messages (for comparisons, should be higher karma term)
     * @param string $owned Term to replace '%owned%' in messages (for comparisons, should be lower karma term)
     * @return string
     */
    protected function getUserMessage($type, $owner = '', $owned = '') {
        $phrase_value = array_rand($this->userMessages[$type]);
        $message = str_replace(
            ['%owner%', '%owned%'],
            [$owner, $owned],
            $this->userMessages[$type][$phrase_value]
        );
        return $message;
    }

    /**
     * Responds to a help command.
     *
     * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
     * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
     * @param array $messages
     */
    protected function sendHelpReply(CommandEvent $event, Queue $queue, array $messages)
    {
        $method = 'irc' . $event->getCommand();
        $target = $event->getSource();
        foreach ($messages as $message) {
            $queue->$method($target, $message);
        }
    }
}

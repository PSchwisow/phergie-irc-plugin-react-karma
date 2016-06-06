# pschwisow/phergie-irc-plugin-react-karma

[Phergie](http://github.com/phergie/phergie-irc-bot-react/) plugin for handling requests to increment or decrement counters on specified terms.

[![Build Status](https://secure.travis-ci.org/PSchwisow/phergie-irc-plugin-react-karma.png?branch=master)](http://travis-ci.org/PSchwisow/phergie-irc-plugin-react-karma)

## Install

The recommended method of installation is [through composer](http://getcomposer.org).

`php composer.phar require pschwisow/phergie-irc-plugin-react-karma`

See Phergie documentation for more information on
[installing and enabling plugins](https://github.com/phergie/phergie-irc-bot-react/wiki/Usage#plugins).

## Provided Commands

| Command    | Parameters        | Description           |
|:----------:|-------------------|-----------------------|
| {commmand} | [param1] [param2] | {description}         |

## Configuration

```php
return [
    'plugins' => [
        // configuration
        new \PSchwisow\Phergie\Plugin\Karma\Plugin([



        ])
    ]
];
```

## Tests

To run the unit test suite:

```
curl -s https://getcomposer.org/installer | php
php composer.phar install
./vendor/bin/phpunit
```

## License

Released under the BSD License. See `LICENSE`.

# pschwisow/phergie-irc-plugin-react-karma

[Phergie](http://github.com/phergie/phergie-irc-bot-react/) plugin for handling requests to increment or decrement counters on specified terms.

[![Build Status](https://secure.travis-ci.org/PSchwisow/phergie-irc-plugin-react-karma.png?branch=master)](http://travis-ci.org/PSchwisow/phergie-irc-plugin-react-karma) [![Code Climate](https://codeclimate.com/github/PSchwisow/phergie-irc-plugin-react-karma/badges/gpa.svg)](https://codeclimate.com/github/PSchwisow/phergie-irc-plugin-react-karma) [![Test Coverage](https://codeclimate.com/github/PSchwisow/phergie-irc-plugin-react-karma/badges/coverage.svg)](https://codeclimate.com/github/PSchwisow/phergie-irc-plugin-react-karma)

## Install

The recommended method of installation is [through composer](http://getcomposer.org).

`php composer.phar require pschwisow/phergie-irc-plugin-react-karma`

See Phergie documentation for more information on
[installing and enabling plugins](https://github.com/phergie/phergie-irc-bot-react/wiki/Usage#plugins).

## Provided Commands

The karma plugin maintains karma values for a list of terms and compares them as requested. Terms are most often IRC nicks, but the plugin does not require this. Terms may optionally be enclosed in parentheses (most often used for terms that contain whitespace).

| Command           | Description                            |
|:-----------------:|----------------------------------------|
| !karma term       | Get the current karma value for a term |
| !reincarnate term | Reset the karma value for a term       |
| term++            | Increment the karma value for a term   |
| term--            | Decrement the karma value for a term   |
| term1 < term2     | Compare karma values of two terms      |
| term1 > term2     | Compare karma values of two terms      |

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

# Roger-Q

[![Latest Version](https://img.shields.io/github/release/liip/roger-q.svg?style=flat-square)](https://github.com/liip/roger-q/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/travis/liip/roger-q/master.svg?style=flat-square)](https://travis-ci.org/liip/roger-q)

Roger-Q is a tool to handle RabbitMQ queues, and it includes commands to dump, dedupe and publish messages.

## Commands

To get the list of all commands, run `roger-q.phar list`

To see all options of a command, run `roger-q.phar {commmand} --help`.

### Dump (`dump`)

Dumps the messages from the given queue to the standard output.

The messages are dumped as JSON as in the following example (the example has been pretty-formatted):
```json5
[
  {
     "payload_bytes": 129,
     "redelivered": false,
     "exchange": "my-exchange-name",
     "routing_key": "routing-key",
     "message_count": 3891,
     "properties": {
       // Message headers here
     },
     "payload": "The message payload",
     "payload_encoding": "string"
   },
   // Next messages here...
]
```

The command uses the RabbitMQ management API, rather than the regular protocol, to read and write messages, which is much
faster for queues with many messages.

Example usage:

```bash
# roger-q.phar dump queue-name
```

### Dedupe (`dedupe`)

In the following example the messages from `messages.txt` file will be de-duplicated by checking the uniqueness of the
given field.
The resulting messages are outputted to the standard output.

```bash
# cat messages.txt | roger-q.phar dedupe id-field
```

### Publish (`publish`)

Publish messages to a given queue.

The following example will publish the messages from the `messages.txt` file into the `queue-name` queue.
The queue is additionally purged before starting the publishing operation.

```bash
# cat messages.txt | roger-q.phar publish queue-name --purge
```

## PHAR building

[Box](https://github.com/humbug/box) is used to build a precompiled PHAR file of the application.

Run `make dist` to:

-   download the box library in `tools/` (if not present)
-   create the PHAR executable in `dist/roger-q.phar`

## Development

To run the PHP coding-styles checks (`php-cs-fixer` and `phpstan`) run the `make phpcs` command to:

-   download the `php-cs-fixer` tool in `tools/` (if not present)
-   download the `phpstan` tool in `tools/` (if not present)
-   Run `php-cs-fixer` on the source code
-   Run `phpstan` on the source code

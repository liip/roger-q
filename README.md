# Roger-Q

Roger-Q is a tool to handle RabbitMQ queues, and it includes commands to dump, dedupe and publish messages.

## Commands

### Dump (`dump`)

Dumps the messages from the given queue to the standard output.
Messages will be outputted as separated by newlines, allowing the command's output to be piped into other commands.

Example:

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

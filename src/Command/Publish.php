<?php

declare(strict_types=1);

namespace App\Command;

use Enqueue\AmqpBunny\AmqpConnectionFactory;
use Interop\Amqp\Impl\AmqpMessage;
use Pnz\JsonException\Json;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Publish extends Command
{
    /**
     * We publish with the amqp protocol.
     */
    private const DEFAULT_RABBITMQ_PORT = 5672;

    protected static $defaultName = 'publish';

    protected function configure(): void
    {
        $this
            ->setDescription('Publishes messages from STDIN to the specified queue (NOT an exchange but only a queue)')
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'RabbitMQ host to connect to', 'localhost')
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'RabbitMQ message port', self::DEFAULT_RABBITMQ_PORT)
            ->addOption('username', null, InputOption::VALUE_REQUIRED, 'Username for the RabbitMQ connection', 'guest')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Password for the RabbitMQ connection', 'guest')
            ->addOption('vhost', null, InputOption::VALUE_REQUIRED, 'RabbitMQ VHost where the queue is declared', '/')
            ->addOption('purge', null, InputOption::VALUE_NONE, 'Whether or not the queue should be purged before publishing')
            ->addArgument('queue', InputArgument::REQUIRED, 'Name of the queue to publish the messages to')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $host = $input->getOption('host');
        \assert(\is_string($host), 'host name is a string');
        if (false !== mb_strpos($host, ':')) {
            throw new \UnexpectedValueException('You can not specify the port as part of the hostname. Use the separate "port" option.');
        }

        $queueName = $input->getArgument('queue');
        \assert(\is_string($queueName));

        $data = $this->readStdin();
        $messages = Json::decode($data, true);

        $factory = new AmqpConnectionFactory([
            'host' => $input->getOption('host'),
            'port' => $input->getOption('port'),
            'vhost' => $input->getOption('vhost'),
            'user' => $input->getOption('username'),
            'pass' => $input->getOption('password'),
            'persisted' => false,
        ]);

        $context = $factory->createContext();
        $queue = $context->createQueue($queueName);
        $producer = $context->createProducer();

        if ($input->getOption('purge')) {
            $context->purgeQueue($queue);
        }

        foreach ($messages as $message) {
            $msg = new AmqpMessage($message['payload'] ?? '', [], $message['properties'] ?? []);
            $msg->setRoutingKey($message['routing_key'] ?? null);
            $producer->send($queue, $msg);
        }

        if ($output instanceof ConsoleOutputInterface && $output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->getErrorOutput()->writeln(sprintf(
                'Published <info>%s</info> messages to queue <info>%s</info>',
                \count($messages),
                $queueName
            ));
        }

        return Command::SUCCESS;
    }

    private function readStdin(): string
    {
        $data = '';
        while (!feof(\STDIN)) {
            $data .= fread(\STDIN, 1024);
        }

        if ('' === $data) {
            throw new \InvalidArgumentException('No data received from STDIN');
        }

        return $data;
    }
}

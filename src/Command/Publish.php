<?php

declare(strict_types=1);

namespace App\Command;

use Enqueue\AmqpBunny\AmqpConnectionFactory;
use Interop\Amqp\Impl\AmqpMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function GuzzleHttp\json_decode;

class Publish extends Command
{
    protected static $defaultName = 'publish';

    protected function configure(): void
    {
        $this
            ->setDescription('Publishes messages to the specified queue')
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'RabbitMQ host to connect to', 'localhost')
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'Port to connect to RabbitMQ', 5672)
            ->addOption('username', null, InputOption::VALUE_REQUIRED, 'Username for the RabbitMQ connection', 'guest')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Password for the RabbitMQ connection', 'guest')
            ->addOption('vhost', null, InputOption::VALUE_REQUIRED, 'RabbitMQ VHost where the queue is declared', '/')
            ->addOption('purge', null, InputOption::VALUE_NONE, 'Whether or not the queue should be purged before publishing')
            ->addArgument('queue', InputArgument::REQUIRED, 'Name of the queue to publish the messages to')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $queueName = $input->getArgument('queue');
        \assert(\is_string($queueName));

        $data = $this->readStdin();
        $messages = json_decode($data, true);

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
    }

    private function readStdin(): string
    {
        $data = '';
        while (!feof(STDIN)) {
            $data .= fread(STDIN, 1024);
        }

        if ('' === $data) {
            throw new \InvalidArgumentException('No data received from STDIN');
        }

        return $data;
    }
}

<?php

declare(strict_types=1);

namespace App\Command;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Dump extends Command
{
    protected static $defaultName = 'dump';

    protected function configure(): void
    {
        $this
            ->setDescription('Dumps the messages of the specified queue to the standard output')
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'RabbitMQ host to connect to', 'localhost')
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'Port to connect to RabbitMQ', Publish::DEFAULT_RABBITMQ_PORT)
            ->addOption('username', null, InputOption::VALUE_REQUIRED, 'Username for the RabbitMQ connection', 'guest')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Password for the RabbitMQ connection', 'guest')
            ->addOption('vhost', null, InputOption::VALUE_REQUIRED, 'RabbitMQ VHost where the queue is declared', '/')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Number of messages to dump')
            ->addOption('no-requeue', null, InputOption::VALUE_NONE, 'Disables the requeue of messages, the queue will stay empty')
            ->addArgument('queue', InputArgument::REQUIRED, 'Name of the queue to dump')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $queueName = $input->getArgument('queue');
        $host = $input->getOption('host');
        $vHost = $input->getOption('vhost');
        \assert(\is_string($queueName));
        \assert(\is_string($host));
        \assert(\is_string($vHost));

        $uri = new Uri('http://'.$host);
        if ($uri->getPort()) {
            throw new \UnexpectedValueException('You can not specify the port as part of the hostname. Use the separate "port" option.');
        }
        $uri = $uri->withPort($input->getOption('port'));

        $guzzle = new Client([
            'base_uri' => $uri,
            RequestOptions::CONNECT_TIMEOUT => 0,
            RequestOptions::READ_TIMEOUT => 0,
            RequestOptions::TIMEOUT => 0,
            RequestOptions::AUTH => [
                $input->getOption('username'),
                $input->getOption('password'),
            ],
        ]);

        $data = [
            'count' => $input->getOption('limit'),
            'requeue' => !$input->getOption('no-requeue'),
            'encoding' => 'auto',
        ];
        if (null === $data['count']) {
            $data['count'] = PHP_INT_MAX;
        }

        $response = $guzzle->request('POST', sprintf('/api/queues/%s/%s/get', $vHost, $queueName), [
            RequestOptions::HEADERS => [
                'Accept-Encoding' => 'gzip',
                'Transfer-Encoding' => 'chunked',
            ],
            RequestOptions::JSON => $data,
            RequestOptions::SINK => STDOUT,
        ]);

        if ($output instanceof ConsoleOutputInterface && $output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->getErrorOutput()->writeln(sprintf(
                'Dumped <info>%s</info> bytes from queue <info>%s</info>',
                $response->getHeaderLine('x-encoded-content-length'),
                $queueName
            ));
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function GuzzleHttp\json_decode;
use function GuzzleHttp\json_encode;

class Dedupe extends Command
{
    protected static $defaultName = 'dedupe';

    protected function configure(): void
    {
        $this
            ->setDescription('Removes duplicated messages')
            ->addArgument('field', InputArgument::REQUIRED, 'Name of the field which identifies each message')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $field = $input->getArgument('field');
        \assert(\is_string($field));

        $data = $this->readStdin();
        $messages = json_decode($data, true);

        $removedMessagesCount = 0;
        $seenValues = [];
        $cleanMessages = [];
        foreach ($messages as $i => $message) {
            if (!array_key_exists('payload', $message)) {
                throw new \UnexpectedValueException(sprintf(
                    'Message #%d does not have a payload (%s)',
                    $i,
                    json_encode($message)
                ));
            }

            $payload = json_decode($message['payload'], true);
            if (!array_key_exists($field, $payload)) {
                throw new \UnexpectedValueException(sprintf(
                    'Payload of message #%d does not have the required field %s (%s)',
                    $i,
                    $field,
                    $message['payload']
                ));
            }

            if (\in_array($payload[$field], $seenValues, true)) {
                ++$removedMessagesCount;
            } else {
                $seenValues[] = $payload[$field];
                $cleanMessages[] = $message;
            }
        }

        $output->writeln(json_encode($cleanMessages));

        if ($output instanceof ConsoleOutputInterface && $output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->getErrorOutput()->writeln(sprintf(
                'Removed <info>%d</info> duplicated messages out of <info>%d</info> total messages, resulting in <info>%d</info> messages',
                $removedMessagesCount,
                \count($messages),
                \count($cleanMessages)
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

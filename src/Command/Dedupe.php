<?php

declare(strict_types=1);

namespace App\Command;

use Pnz\JsonException\Json;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Dedupe extends Command
{
    protected static $defaultName = 'dedupe';

    protected function configure(): void
    {
        $this
            ->setDescription('Removes duplicated messages')
            ->addArgument('field', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Name of the JSON payload fields which identifies each message')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        /** @var string[] $fields */
        $fields = $input->getArgument('field');
        \assert(\is_array($fields));

        $data = $this->readStdin();
        $messages = Json::decode($data, true);

        $removedMessagesCount = 0;
        $seenValues = [];
        $cleanMessages = [];
        foreach ($messages as $i => $message) {
            if ($this->isDuplicatedMessage($fields, $message, $seenValues, $i)) {
                ++$removedMessagesCount;
            } else {
                $cleanMessages[] = $message;
            }
        }

        $output->writeln(Json::encode($cleanMessages));

        if ($output instanceof ConsoleOutputInterface && $output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->getErrorOutput()->writeln(sprintf(
                'Removed <info>%d</info> duplicated messages out of <info>%d</info> total messages, resulting in <info>%d</info> messages',
                $removedMessagesCount,
                \count($messages),
                \count($cleanMessages)
            ));
        }
    }

    /**
     * @param string[] $fields
     */
    private function getValuesHash(array $fields, array $data): string
    {
        $values = [];
        foreach ($fields as $field) {
            $values[$field] = $data[$field];
        }

        return sha1(serialize($values));
    }

    /**
     * @param string[] $fields
     * @param bool[]   $seenValues Hashmap of value-hash => bool, to keep track of values already encountered
     */
    private function isDuplicatedMessage(array $fields, array $message, array &$seenValues, int $messageNum): bool
    {
        if (!\array_key_exists('payload', $message)) {
            throw new \UnexpectedValueException(sprintf('Message #%d does not have a payload (%s)', $messageNum, Json::encode($message)));
        }

        $payload = Json::decode($message['payload'], true);

        // Check that all given fields exist in the payload:
        foreach ($fields as $field) {
            if (!\array_key_exists($field, $payload)) {
                throw new \UnexpectedValueException(sprintf(
                    'Payload of message #%d does not have the required fields %s (%s)',
                    $messageNum,
                    implode(',', $fields),
                    $message['payload']
                ));
            }
        }

        $hash = $this->getValuesHash($fields, $payload);
        if (\array_key_exists($hash, $seenValues)) {
            return true;
        }

        $seenValues[$hash] = true;

        return false;
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

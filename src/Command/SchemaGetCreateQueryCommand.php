<?php

namespace AlexDpy\Acl\Command;

use AlexDpy\Acl\Database\Schema\AclSchema;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class SchemaGetCreateQueryCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('schema:get-create-query');

        $this->addArgument('driver', InputArgument::OPTIONAL, 'The database driver to use');

        $this->addOption(
            'permissions_table_name',
            null,
            InputOption::VALUE_OPTIONAL,
            'The permissions table name',
            AclSchema::DEFAULT_PERMISSIONS_TABLE_NAME
        );
        $this->addOption(
            'requester_column_length',
            null,
            InputOption::VALUE_OPTIONAL,
            'The request column length',
            AclSchema::DEFAULT_REQUESTER_COLUMN_LENGTH
        );
        $this->addOption(
            'resource_column_length',
            null,
            InputOption::VALUE_OPTIONAL,
            'The resource column length',
            AclSchema::DEFAULT_RESOURCE_COLUMN_LENGTH
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $availableDrivers = ['mysql', 'sqlite', 'pgsql'];

        if (null !== $driver = $input->getArgument('driver')) {
            if (!in_array($driver, $availableDrivers)) {
                $output->writeln(sprintf('Driver "%s" is not available.', $driver));
                $driver = null;
            }
        }

        if (null === $driver) {
            $questionHelper = new QuestionHelper();

            $driver = $questionHelper->ask($input, $output, new ChoiceQuestion(
                'Which database driver do you use ?', $availableDrivers
            ));
        }

        $aclSchema = new AclSchema([
            'permissions_table_name' => $input->getOption('permissions_table_name'),
            'requester_column_length' => $input->getOption('requester_column_length'),
            'resource_column_length' => $input->getOption('resource_column_length'),
        ]);

        $output->writeln('Here is the "create query" for <comment>' . $driver . '</comment>:' . PHP_EOL);

        $output->writeln('<info>' . $aclSchema->getCreateQuery('sqlite') . '</info>' . PHP_EOL);
    }
}

<?php
/**
 * @author    Ewave <https://ewave.com/>
 * @copyright 2018-2019 NASKO TRADING PTY LTD
 * @license   https://ewave.com/wp-content/uploads/2018/07/eWave-End-User-License-Agreement.pdf BSD Licence
 */

namespace MagentoOneDevBox\Command\Pool;

use CoreDevBoxScripts\Command\CommandAbstract;
use CoreDevBoxScripts\Library\Registry;
use CoreDevBoxScripts\Library\EnvConfig;
use CoreDevBoxScripts\Command\Options\Db as DbOptions;
use MagentoOneDevBox\Command\Options\Magento as MagentoOptions;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command for Magento installation
 */
class MagentoSetup extends CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('magento1:install:fresh')
            ->setDescription(
                'Install Fresh magento1: [Code Download]-> INSTALLATION [FRESH DB]->[Magento finalisation]'
            )
            ->setHelp('This command allows you to install Magento.');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $this->commandTitle($io, 'Magento Setup: Fresh Installation');

        $this->executeWrappedCommands(
            [
                'core:setup:code',
                'core:setup:permissions'
            ],
            $input,
            $output
        );

        $mPath = EnvConfig::getValue('WEBSITE_APPLICATION_ROOT') ?: EnvConfig::getValue('WEBSITE_DOCUMENT_ROOT');

        $this->executeCommands(
            sprintf('cd %s && rm -rf var/* app/etc/local.xml', $mPath),
            $output
        );

        $magentoHost = EnvConfig::getValue('WEBSITE_HOST_NAME');
        $magentoBackendPath = $this->requestOption(MagentoOptions::BACKEND_PATH, $input, $output);
        $magentoAdminUser = $this->requestOption(MagentoOptions::ADMIN_USER, $input, $output);
        $magentoAdminPassword = $this->requestOption(MagentoOptions::ADMIN_PASSWORD, $input, $output);

        $projectName = EnvConfig::getValue('PROJECT_NAME');

        $mysqlHost = EnvConfig::getValue('CONTAINER_MYSQL_NAME');
        $mysqlHost = $projectName . '_' . $mysqlHost;

        $dbName = EnvConfig::getValue('CONTAINER_MYSQL_DB_NAME');
        $dbUser = EnvConfig::getValue('CONTAINER_MYSQL_ROOT_PASS'); //todo change
        $dbPassword = EnvConfig::getValue('CONTAINER_MYSQL_ROOT_PASS');

        if (!$mysqlHost || !$dbName || !$dbPassword) {
            $output->writeln('<comment>Some of required data are missed</comment>');
            $output->writeln('<comment>Reply on:</comment>');

            $mysqlHost = $input->getOption(DbOptions::HOST);
            $dbUser = $input->getOption(DbOptions::USER);
            $dbPassword = $input->getOption(DbOptions::PASSWORD);
            $dbName = $input->getOption(DbOptions::NAME);
        }

        $headers = ['Parameter', 'Value'];
        $rows = [
            ['DB Name', $dbName],
            ['Admin Backend Path', $magentoBackendPath],
            ['Admin User', $magentoAdminUser],
            ['Admin Password', $magentoAdminPassword],
        ];

        $io->table($headers, $rows);

        $command = sprintf(
            'cd %s && php -f install.php -- '
            . '--license_agreement_accepted "yes" '
            . '--locale "en_US" '
            . '--timezone "America/Los_Angeles" '
            . '--default_currency "USD" '
            . '--db_host "%s" '
            . '--db_name "%s" '
            . '--db_user "%s" '
            . '--db_pass "%s" '
            . '--db_prefix "" '
            . '--session_save "db" '
            . '--admin_frontname "%s" '
            . '--url "http://%s/" '
            . '--use_rewrites "yes" '
            . '--use_secure "no" '
            . '--secure_base_url "https://%s/" '
            . '--use_secure_admin "no" '
            . '--admin_firstname "Magento" '
            . '--admin_lastname "User" '
            . '--admin_email "user@example.com" '
            . '--admin_username "%s" '
            . '--admin_password "%s" '
            . '--skip_url_validation "yes" '
            . '--encryption_key "BRuvuCrUd4aSWutr" ',
            $mPath,
            $mysqlHost,
            $dbName,
            $dbUser,
            $dbPassword,
            $magentoBackendPath,
            $magentoHost,
            $magentoHost,
            $magentoAdminUser,
            $magentoAdminPassword
        );

        $this->executeCommands($command, $output);

        Registry::setData(
            [
                MagentoOptions::HOST => $magentoHost,
                MagentoOptions::BACKEND_PATH => $magentoBackendPath,
                MagentoOptions::ADMIN_USER => $magentoAdminUser,
                MagentoOptions::ADMIN_PASSWORD => $magentoAdminPassword
            ]
        );

        $this->executeWrappedCommands(
            [
                'magento1:setup:redis',
                'magento1:setup:permissions'
            ],
            $input,
            $output
        );

        /* use $e for Exception variable */
        if (!isset($e)) {
            $io->success('Magento has been installed');
            return true;
        } else {
            $io->warning('Some issues appeared during magento installation');
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsConfig()
    {
        return [
            MagentoOptions::PATH => MagentoOptions::get(MagentoOptions::PATH),
            MagentoOptions::BACKEND_PATH => MagentoOptions::get(MagentoOptions::BACKEND_PATH),
            MagentoOptions::ADMIN_USER => MagentoOptions::get(MagentoOptions::ADMIN_USER),
            MagentoOptions::ADMIN_PASSWORD => MagentoOptions::get(MagentoOptions::ADMIN_PASSWORD),
            MagentoOptions::SAMPLE_DATA_INSTALL => MagentoOptions::get(MagentoOptions::SAMPLE_DATA_INSTALL),
        ];
    }
}

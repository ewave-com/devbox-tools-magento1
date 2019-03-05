<?php
/**
 * @author    Ewave <https://ewave.com/>
 * @copyright 2018-2019 NASKO TRADING PTY LTD
 * @license   https://ewave.com/wp-content/uploads/2018/07/eWave-End-User-License-Agreement.pdf BSD Licence
 */

namespace MagentoOneDevBox\Command\Pool;

use CoreDevBoxScripts\Command\CommandAbstract;
use CoreDevBoxScripts\Library\EnvConfig;
use MagentoOneDevBox\Command\Options\Magento as MagentoOptions;
use MagentoOneDevBox\Library\Array2XML;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command for downloading Magento sources
 */
class MagentoSetupConfigs extends CommandAbstract
{
    /**
     * @var string
     */
    protected $configFile = '';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->configFile = EnvConfig::getValue('PROJECT_CONFIGURATION_FILE');
        $this->setName('magento1:setup:configs')
            ->setDescription(
                'Download Magento Configs Files [' . $this->configFile . ' file will be used as configuration]'
            )
            ->setHelp(
                'Download Magento Configs Files [' . $this->configFile . ' file will be used as configuration]'
            );

        $this->questionOnRepeat = 'Try to update configs again?';

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->executeRepeatedly('updateConfigs', $input, $output);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     */
    protected function updateConfigs(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $this->commandTitle($io, 'Configuration files sync.');

        $useExistingSources = $this->requestOption(MagentoOptions::M_CONFIGS_REUSE, $input, $output, true);
        if (!$useExistingSources) {
            $output->writeln('<comment>Skipping this step.</comment>');
            return true;
        }

        $this->executeWrappedCommands(
            [
                'core:remote-files:download',
                'core:setup:permissions'
            ],
            $input,
            $output
        );

        $this->updateConfigsFiles($io, $input, $output);
        return true;
    }

    /**
     * @param $io
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     */
    public function updateConfigsFiles($io, $input, $output)
    {
        $projectName = EnvConfig::getValue('PROJECT_NAME');
        $mysqlHost = EnvConfig::getValue('CONTAINER_MYSQL_NAME');
        $mysqlDbName = EnvConfig::getValue('CONTAINER_MYSQL_DB_NAME');
        $mysqlRootPassword = EnvConfig::getValue('CONTAINER_MYSQL_ROOT_PASS');
        $mysqlRootName = EnvConfig::getValue('CONTAINER_MYSQL_ROOT_PASS'); //todo change
        $mysqlHost = $projectName . '_' . $mysqlHost;
        //$tablePrefix = $this->requestOption(DB::TABLE_PREFIX, $input, $output, true);
        $tablePrefix='';

        $mPath = EnvConfig::getValue('WEBSITE_DOCUMENT_ROOT');
        $destinationMagentoPath = $mPath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'etc';
        if (!$destinationMagentoPath) {
            $destinationMagentoPath = $this->requestOption(MagentoOptions::PATH, $input, $output, true);
        }
        $configPath = sprintf('%s/local.xml', $destinationMagentoPath);
        $rootNode = 'resources';
        $config = [
            1 => [
                'name' => 'db',
                'value' => '',
                'children' => [
                    [
                        'name' => 'table_prefix',
                        'value' => $tablePrefix,
                    ],
                ],
            ],
            2 => [
                'name' => 'default_setup',
                'value' => '',
                'children' => [
                    [
                        'name' => 'connection',
                        'value' => '',
                        'children' => [
                            [
                                'name' => 'host',
                                'value' => $mysqlHost
                            ],
                            [
                                'name' => 'username',
                                'value' => $mysqlRootName,
                            ],
                            [
                                'name' => 'password',
                                'value' => $mysqlRootPassword,
                            ],
                            [
                                'name' => 'dbname',
                                'value' => $mysqlDbName,
                            ],
                            [
                                'name' => 'initStatements',
                                'value' => 'SET NAMES utf8',
                            ],
                            [
                                'name' => 'model',
                                'value' => 'mysql4',
                            ],
                            [
                                'name' => 'type',
                                'value' => 'pdo_mysql',
                            ],
                            [
                                'name' => 'pdoType',
                                'value' => "",
                            ],
                            [
                                'name' => 'active',
                                'value' => '1',
                            ],
                        ],
                    ],
                ],
            ]
        ];

        if (!empty($config)) {
            $xml = new Array2XML($rootNode, $configPath);
            $xml->createNode($config);
            $xml->save($configPath);
        }

        if (!isset($e)) {
            $io->success('Configs have been copied');
            return true;
        } else {
            $io->warning('Some issues appeared during configs updating');
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsConfig()
    {
        return [
            MagentoOptions::M_CONFIGS_REUSE => MagentoOptions::get(MagentoOptions::M_CONFIGS_REUSE),
            MagentoOptions::PATH => MagentoOptions::get(MagentoOptions::PATH),
        ];
    }
}

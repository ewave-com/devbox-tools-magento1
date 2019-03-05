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
use MagentoOneDevBox\Command\Options\Magento as MagentoOptions;
use MagentoOneDevBox\Command\Options\Redis as RedisOptions;
use MagentoOneDevBox\Library\Array2XML;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command for Redis setup
 */
class MagentoSetupRedis extends CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('magento1:setup:redis')
            ->setDescription('Setup Redis for Magento')
            ->setHelp('This command allows you to setup Redis for Magento.');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $this->commandTitle($io, 'Redis Configuration');

        $mPath = EnvConfig::getValue('WEBSITE_DOCUMENT_ROOT');
        $destinationMagentoPath = $mPath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'etc';
        if (!$destinationMagentoPath) {
            $destinationMagentoPath = $this->requestOption(MagentoOptions::PATH, $input, $output, true);
        }

        $projectName = EnvConfig::getValue('PROJECT_NAME');
        $redisContainer = EnvConfig::getValue('CONTAINER_REDIS_NAME');

        $host = $projectName . '_' . $redisContainer;
        $configPath = sprintf('%s/local.xml', $destinationMagentoPath);
        $rootNode = 'global';

        if ($this->requestOption(RedisOptions::SESSION_SETUP, $input, $output, true)) {
            $config[] = [
                'name' => 'redis_session',
                'value' => '',
                'children' =>
                    [
                        [
                            'name' => 'host',
                            'value' => $host,
                        ],
                        [
                            'name' => 'port',
                            'value' => '6379',
                        ],
                        [
                            'name' => 'timeout',
                            'value' => '2.5',
                        ],
                        [
                            'name' => 'db',
                            'value' => '2',
                        ],
                        [
                            'name' => 'compression_threshold',
                            'value' => '2048',
                        ],
                        [
                            'name' => 'compression_lib',
                            'value' => 'gzip',
                        ],
                        [
                            'name' => 'log_level',
                            'value' => '1',
                        ],
                        [
                            'name' => 'max_concurrency',
                            'value' => '6',
                        ],
                        [
                            'name' => 'break_after_frontend',
                            'value' => '5',
                        ],
                        [
                            'name' => 'fail_after',
                            'value' => '10',
                        ],
                        [
                            'name' => 'break_after_adminhtml',
                            'value' => '30',
                        ],
                        [
                            'name' => 'first_lifetime',
                            'value' => '600',
                        ],
                        [
                            'name' => 'bot_first_lifetime',
                            'value' => '60',
                        ],
                        [
                            'name' => 'bot_lifetime',
                            'value' => '7200',
                        ],
                        [
                            'name' => 'disable_locking',
                            'value' => '0',
                        ],
                        [
                            'name' => 'min_lifetime',
                            'value' => '60',
                        ],
                        [
                            'name' => 'max_lifetime',
                            'value' => '2592000',
                        ],
                    ],
            ];
            $config[] = [
                'name' => 'session_save',
                'value' => 'db'
            ];
        } else {
            $config[] = [
                'name' => 'session_save',
                'value' => 'files'
            ];
            $config[] = [
                'name' => 'redis_session',
                'remove' => true
            ];
        }

        if ($this->requestOption(RedisOptions::CACHE_SETUP, $input, $output, true)) {
            $config[] = [
                'name' => 'cache',
                'value' => '',
                'children' => [
                    [
                        'name' => 'backend',
                        'value' => 'Cm_Cache_Backend_Redis',
                    ],
                    [
                        'name' => 'backend_options',
                        'value' => '',
                        'children' => [
                            [
                                'name' => 'server',
                                'value' => $host,
                            ],
                            [
                                'name' => 'port',
                                'value' => '6379',
                            ],
                            [
                                'name' => 'database',
                                'value' => '0',
                            ],
                            [
                                'name' => 'force_standalone',
                                'value' => '0',
                            ],
                            [
                                'name' => 'connect_retries',
                                'value' => '1',
                            ],
                            [
                                'name' => 'read_timeout',
                                'value' => '10',
                            ],
                            [
                                'name' => 'automatic_cleaning_factor',
                                'value' => '0',
                            ],
                            [
                                'name' => 'compress_data',
                                'value' => '1',
                            ],
                            [
                                'name' => 'compress_tags',
                                'value' => '1',
                            ],
                            [
                                'name' => 'compress_threshold',
                                'value' => '20480',
                            ],
                            [
                                'name' => 'compression_lib',
                                'value' => 'gzip',
                            ],
                        ],
                    ],
                ],
            ];
        } else {
            $config[] = [
                'name' => 'cache',
                'remove' => true
            ];
        }

        $this->executeRepeatedly('enableRedisCacheModule', $input, $output, $io);

        if (!empty($config)) {
            $xml = new Array2XML($rootNode, $configPath);
            $xml->createNode($config);
            $xml->save($configPath);
        }

        $output->writeln('<info>Cache clean...</info>');
        $this->executeCommands(sprintf('cd %s && rm -rf var/cache/* var/session/*', $mPath), $output);

        /* use $e for Exception variable */
        if (!isset($e)) {
            $io->success('Redis configuration has been updated');
        } else {
            $io->warning('Some issues appeared during redis configuration');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function enableRedisCacheModule($input, $output, $io)
    {
        $config[] = [
            'name' => 'modules',
            'value' => '',
            'children' => [
                [
                    'name' => 'Cm_RedisSession',
                    'value' => '',
                    'children' => [
                        [
                            'name' => 'active',
                            'value' => 'true',
                        ],
                        [
                            'name' => 'codePool',
                            'value' => 'community',
                        ],
                    ],
                ],
            ],
        ];
        $rootNode = 'config';
        $mPath = EnvConfig::getValue('WEBSITE_DOCUMENT_ROOT');
        $destinationMagentoPath = $mPath . DIRECTORY_SEPARATOR . 'app'
            . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'modules';

        if (!$destinationMagentoPath) {
            $destinationMagentoPath = $this->requestOption(
                RedisOptions::REDIS_MODULE_CONFIG_PATH,
                $input,
                $output,
                true
            );
        }

        $output->writeln('<info>Enabling cache module...</info>');
        $configPath = sprintf('%s/Cm_RedisSession.xml', $destinationMagentoPath);
        $xml = new Array2XML($rootNode, $configPath);
        $xml->createNode($config);
        $xml->save($configPath);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsConfig()
    {
        return [
            MagentoOptions::PATH => MagentoOptions::get(MagentoOptions::PATH),
            RedisOptions::CACHE_SETUP => RedisOptions::get(RedisOptions::CACHE_SETUP),
            RedisOptions::SESSION_SETUP => RedisOptions::get(RedisOptions::SESSION_SETUP),
            RedisOptions::REDIS_CLASS_NAME => RedisOptions::get(RedisOptions::REDIS_CLASS_NAME),
        ];
    }
}

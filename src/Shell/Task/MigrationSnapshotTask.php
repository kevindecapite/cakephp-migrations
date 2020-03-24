<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Migrations\Shell\Task;

use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Migrations\TableFinderTrait;
use Migrations\Util\UtilTrait;

/**
 * Task class for generating migration snapshot files.
 */
class MigrationSnapshotTask extends SimpleMigrationTask
{
    use SnapshotTrait;
    use TableFinderTrait;
    use UtilTrait;

    protected $_name;

    /**
     * {@inheritDoc}
     */
    public function bake(string $name): string
    {
        $collection = $this->getCollection($this->connection);
        EventManager::instance()->on('Bake.initialize', function (Event $event) use ($collection) {
            $event->getSubject()->loadHelper('Migrations.Migration', [
                'collection' => $collection,
            ]);
        });
        $this->_name = $name;

        return parent::bake($name);
    }

    /**
     * {@inheritDoc}
     */
    public function template(): string
    {
        return 'Migrations.config/snapshot';
    }

    /**
     * {@inheritDoc}
     */
    public function templateData(): array
    {
        $namespace = Configure::read('App.namespace');
        $pluginPath = '';
        if ($this->plugin) {
            $namespace = $this->_pluginNamespace($this->plugin);
            $pluginPath = $this->plugin . '.';
        }

        $collection = $this->getCollection($this->connection);
        $options = [
            'require-table' => $this->params['require-table'],
            'plugin' => $this->plugin,
        ];
        $tables = $this->getTablesToBake($collection, $options);

        sort($tables, SORT_NATURAL);

        $tables = array_combine($tables, $tables);

        $autoId = true;
        if (isset($this->params['disable-autoid'])) {
            $autoId = !$this->params['disable-autoid'];
        }

        return [
            'plugin' => $this->plugin,
            'pluginPath' => $pluginPath,
            'namespace' => $namespace,
            'collection' => $collection,
            'tables' => $tables,
            'action' => 'create_table',
            'name' => $this->_name,
            'autoId' => $autoId,
        ];
    }

    /**
     * Get a collection from a database
     *
     * @param string $connection Database connection name.
     * @return \Cake\Database\Schema\Collection
     */
    public function getCollection($connection)
    {
        $connection = ConnectionManager::get($connection);

        return $connection->getSchemaCollection();
    }

    /**
     * To check if a Table Model is to be added in the migration file
     *
     * @param string $tableName Table name in underscore case.
     * @param string|null $pluginName Plugin name if exists.
     * @deprecated Will be removed in the next version
     * @return bool True if the model is to be added.
     */
    public function tableToAdd($tableName, $pluginName = null)
    {
        return true;
    }

    /**
     * Gets the option parser instance and configures it.
     *
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser(): ConsoleOptionParser
    {
        $parser = parent::getOptionParser();

        $parser->setDescription(
            'Bake migration snapshot class.'
        )->addArgument('name', [
            'help' => 'Name of the migration to bake. Can use Plugin.name to bake migration files into plugins.',
            'required' => true,
        ])
        ->addOption('require-table', [
            'boolean' => true,
            'default' => false,
            'help' => 'If require-table is set to true, check also that the table class exists.',
        ])->addOption('disable-autoid', [
            'boolean' => true,
            'default' => false,
            'help' => 'Disable phinx behavior of automatically adding an id field.',
        ])
        ->addOption('no-lock', [
            'help' => 'If present, no lock file will be generated after baking',
            'boolean' => true,
        ]);

        return $parser;
    }
}

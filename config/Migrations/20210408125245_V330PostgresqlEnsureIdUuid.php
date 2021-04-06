<?php

use Cake\Datasource\ConnectionManager;
use Migrations\AbstractMigration;

class V330PostgresqlEnsureIdUuid extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change()
    {
    if ($this->getAdapter()->getOptions()["adapter"] === "pgsql")   {
            $con = ConnectionManager::get('default');
            $tables = $con->execute('select table_name from information_schema.columns where column_name=:column_name and data_type=:data_type and table_catalog=:tc',
            ['column_name'=>'id','data_type'=>'character','tc' => $this->getAdapter()->getOptions('database')["name"]])->fetchAll(); 

            //$this->execute('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');
            foreach ($tables as $table) {
                $this->execute("ALTER TABLE \"$table[0]\" ALTER COLUMN id set default uuid_generate_v4()");

                }
            }
        }
    }

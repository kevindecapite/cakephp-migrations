<?php

use Phinx\Seed\AbstractSeed;

/**
 * NumbersSeed seed.
 */
class NumbersAltSeed extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html
     */
    public function run()
    {
        $data = [
            [
                'number' => '5',
                'radix' => '10',
            ],
        ];

        $table = $this->table('numbers');
        $table->insert($data)->save();
    }
}

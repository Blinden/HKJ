<?php

class BetaalTable extends AbstractTable
{

    protected $table = 'hkj_betaal';
    protected $identifier = 'id';

    public function createTabel()
    {
        $this->db->query(<<<"TEXT"
            CREATE TABLE IF NOT EXISTS hkj_betaal (
                  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                  aangifte_id CHAR(40),
                  order_id VARCHAR,
                  bedrag DECIMAL(8, 2),
                  omschrijving VARCHAR,
                  "transaction" TEXT,
                  datum DATE,
                  status CHAR(20)
             );
TEXT
        );
        $this->db->query('CREATE UNIQUE INDEX IF NOT EXISTS "unique_hkj_betaal_aangifte_id" ON "hkj_betaal" ("aangifte_id" ASC)');
    }
}

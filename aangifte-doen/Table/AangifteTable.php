<?php

/**
 * Description of AangifteTable
 *
 * @author Bart
 */
class AangifteTable extends AbstractTable
{

    protected $table = 'hkj_aangifte';
    protected $identifier = 'id';

    public function createTabel()
    {
        $this->db->query(<<<"TEXT"
            CREATE TABLE IF NOT EXISTS "hkj_aangifte" (
                "id" INTEGER PRIMARY KEY NOT NULL,
                "aangifte_id" CHAR(40) NOT NULL,
                "datum" DATETIME,
                "akkoord" BOOL DEFAULT 0,
                "jaar" INTEGER DEFAULT 0,
                "student" BOOL DEFAULT 0,
                "inkomen" BOOL DEFAULT 0,
                "opgaves" TEXT,
                "teruggave" DECIMAL(8,2),
                "studiefinanciering" BOOL DEFAULT 0,
                "extrakosten" BOOL DEFAULT 0,
                "studiekosten" TEXT,
                "ziektekosten" TEXT,
                "korting" BOOL,
                "email_verstuurd" DATETIME,
                "user_id" INTEGER,
                "betaal_id" INTEGER
            );
TEXT
        );
        $this->db->query('CREATE UNIQUE INDEX IF NOT EXISTS "unique_hkj_aangifte_aangifte_id" ON "hkj_aangifte" ("aangifte_id" ASC)');
        $this->db->query('CREATE INDEX IF NOT EXISTS "index_hkj_aangifte_datum" ON "hkj_aangifte" ("datum" DESC)');
    }

}

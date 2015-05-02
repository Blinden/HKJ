<?php

class UserTable extends AbstractTable
{

    protected $table = 'hkj_user';
    protected $identifier = 'id';

    public function createTable()
    {
        $this->db->query(<<<"TEXT"
            CREATE TABLE IF NOT EXISTS hkj_user (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                voornaam VARCHAR,
                achternaam VARCHAR,
                email VARCHAR,
                bsn VARCHAR,
                geboorte DATETIME,
                facebook_id BIGINT UNSIGNED,
                facebook_token VARCHAR,
                facebook_date DATETIME,
                nieuwsbrief BOOL DEFAULT (0)
            )
TEXT
        );
        $this->db->query('CREATE INDEX IF NOT EXISTS index_hkj_user_facebook_id ON hkj_user (facebook_id ASC)');
        $this->db->query('CREATE INDEX IF NOT EXISTS index_hkj_user_email ON hkj_user (email ASC)');
    }

}

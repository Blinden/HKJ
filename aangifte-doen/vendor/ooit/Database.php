<?php

class Database extends AbstractService
{
    /**
     * @var \PDO
     */
    protected $driver;

    public function isConnected()
    {
        return $this->driver !== null;
    }

    public function lastInsertId()
    {
        $this->connect();
        return $this->driver->lastInsertId();
    }

    public function connect()
    {
        if (!$this->isConnected()) {
            $this->driver = new \PDO($this->getConfig('dns'));
        }
        return $this->driver;
    }

    public function query($sql, $data = array())
    {
        $this->connect();
        try {
            $statement = $this->driver->prepare($sql);
            if (!$statement) {
                throw new Exception($this->driver->errorCode() . " " . implode(', ', $this->driver->errorInfo()) . " " . $sql);
            }

            if ($statement->execute($data) === false) {
                throw new Exception($this->driver->errorCode() . " " . implode(', ', $this->driver->errorInfo()) . " " . $sql);
            }
            return $statement;

        } catch (Exception $e) {
            throw $e;
        }
    }


}

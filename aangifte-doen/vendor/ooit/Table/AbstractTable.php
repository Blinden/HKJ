<?php

/**
 * Description of AbstractTable
 *
 * @author Bart
 */
class AbstractTable
{

    /**
     * @var Database
     */
    protected $db;
    protected $table;
    protected $identifier = 'id';

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     *
     * @return array|false
     */
    public function get($id)
    {
        $result = $this->select(array($this->identifier => $id));
        if (is_array($result)) {
            return array_shift($result);
        }
        return null;
    }

    /**
     *
     * @return PdoStatement|false
     */
    public function select($where = array())
    {
        $params = array();
        $conditions = array();
        foreach ($where as $field => $value) {
            if ($value == 'isnull') {
                $conditions[] = "\"{$field}\" IS NULL";
            }
            else {
                $conditions[] = "\"{$field}\" = :{$field}";
                $params[":{$field}"] = $value;
            }
        }
        $conditionSql = count($conditions) ? " WHERE " . implode(' AND ', $conditions) : '';
        $sql = sprintf("SELECT * FROM %s%s", $this->table, $conditionSql);

        $result = $this->db->query($sql, $params);
        if ($result) {
            $result = $result->fetchAll(PDO::FETCH_ASSOC);
        }
        return $result;
    }

    /**
     *
     * @param array $data
     * @return integer
     */
    public function delete($id)
    {
        $sql = sprintf("DELETE FROM %s WHERE %s = :%s", $this->table, $this->identifier, $this->identifier);
        return $this->db->query($sql, array(":$this->identifier" => $id));
    }

    /**
     *
     * @param array $data
     * @return integer
     */
    public function insert(array $data)
    {
        $fields = array();
        $params = array();
        foreach ($data as $field => $value) {
            if ($field != $this->identifier) {
                $fields[] = "\"{$field}\"";
                $params[":{$field}"] = $value;
            }
        }
        $fieldsSql = implode(', ', $fields);
        $paramsSql = implode(', ', array_keys($params));

        $sql = sprintf("INSERT INTO %s (%s) VALUES (%s)", $this->table, $fieldsSql, $paramsSql);
        return $this->db->query($sql, $params);
    }

    /**
     *
     * @param array $data
     * @return integer
     */
    public function update(array $data)
    {
        $params = array();
        $set = array();
        foreach ($data as $field => $value) {
            if ($field != $this->identifier) {
                $set[] = "\"{$field}\" = :{$field}";
            }
            $params[":{$field}"] = $value;
        }
        $setSql = implode(', ', $set);

        $sql = sprintf("UPDATE %s SET %s WHERE %s = :%s", $this->table, $setSql, $this->identifier, $this->identifier);

        return $this->db->query($sql, $params);
    }

    public function getDb()
    {
        return $this->db;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function setDb(Database $db)
    {
        $this->db = $db;
        return $this;
    }

    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

}

<?php

class TableService extends AbstractService
{

    /**
     * @var AbstractTable
     */
    protected $table;

    /**
     * @var EntityInterface|string
     */
    protected $entityClass;

    public function getDatabase()
    {
        // TBD depreciated
        return ServiceProvider::get('Database');
    }

    /**
     *
     * @return EntityInterface
     */
    public function find($id)
    {
        $data = $this->table->get($id);
        if ($data) {
            return new $this->entityClass($data);
        }
        return null;
    }

    /**
     *
     * @return EntityInterface
     */
    public function findOne($where)
    {
        $rows = $this->table->select($where);
        if (count($rows) > 0) {
            return new $this->entityClass(array_shift($rows));
        }
        return null;
    }

    /**
     *
     * @return UserEntity
     */
    public function findAll($where)
    {
        $rows = $this->table->select($where);

        $result = array();
        foreach ($rows as $data) {
            $user = new $this->entityClass($data);
            $result[$user->id] = $user;
        }
        return $result;
    }

}

<?php
/**
 * Description of UserService
 *
 * @author Bart
 */
class UserService extends TableService
{

    protected $entityClass = 'UserEntity';

    public function __construct()
    {
        $this->table = new UserTable($this->getDatabase());
    }

    public function getAangiftes(UserEntity $user)
    {
        return ServiceProvider::get('AangifteService')->findAll(array('user_id' => $user->id));
    }

    public function persist(UserEntity $user)
    {
        if (empty($user->id)) {
            $this->table->insert($user->extract());
            $user->id = $this->table->getDb()->lastInsertId();
        }
        else {
            $this->table->update($user->extract());
        }
        return $user;
    }

}

<?php

class ProfileEntity extends UserEntity
{

    public function hydrate($data)
    {
        if (isset($data['id'])) {
            $this->facebook_id = $data['id'];
            unset($data['id']);
        }
        if (isset($data['first_name'])) {
            $this->voornaam = $data['first_name'];
        }
        if (isset($data['last_name'])) {
            $this->achternaam = $data['last_name'];
        }
        if (isset($data['birthday'])) {
            $this->geboorte = $this->datetime($data['birthday']);
        }
        if (isset($data['updated_time'])) {
            $this->facebook_date = $this->datetime($data['updated_time']);
        }
        return parent::hydrate($data);
    }

}

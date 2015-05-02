<?php
/**
 * @deprecated
 */
class AanvragerEntity extends UserEntity
{

    public function extract()
    {
        $data = parent::extract();
        $data['voornaam'] = $this->voorletters;
        return $data;
    }

    public function hydrate($data)
    {
        parent::hydrate($data);
        if (isset($data['voorletters'])) {
            $this->voornaam = $data['voorletters'];
        }
    }

}

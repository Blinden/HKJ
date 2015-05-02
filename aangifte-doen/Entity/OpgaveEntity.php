<?php

class OpgaveEntity
{

    public $id;
    public $werkgever = '';
    public $salaris = 0;
    public $loonheffing = 0;
    public $arbeidskorting = 0;

    public function __construct(array $data = null)
    {
        if ($data) {
            $this->exchangeArray($data);
        }
    }

    public function exchangeArray($data)
    {
        $this->id = (!empty($data['id'])) ? $data['id'] : null;
        $this->werkgever = (!empty($data['werkgever'])) ? $data['werkgever'] : '';
        $this->salaris = (!empty($data['salaris'])) ? $data['salaris'] : null;
        $this->loonheffing = (!empty($data['loonheffing'])) ? $data['loonheffing'] : null;
        $this->arbeidskorting = (!empty($data['arbeidskorting'])) ? $data['arbeidskorting'] : 0;
    }

}

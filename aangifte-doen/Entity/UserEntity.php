<?php

class UserEntity extends AbstractEntity
{

    public $id;
    public $achternaam;
    public $voornaam;
    public $email;
    public $bsn;
    public $geboorte;
    public $facebook_id;
    public $facebook_token;
    public $facebook_date;
    public $nieuwsbrief;

    public function getAge($jaar = null)
    {
        $geboorte = $this->formatDate($this->geboorte, 'Y');
        if ($geboorte !== false) {
            $now = date('Y');
            $jaar = $jaar ?: $now;
            return $jaar - $geboorte;
        }
        return null;
    }

    public function getFullName()
    {
        return implode(' ', array($this->voornaam, $this->achternaam));
    }

    public function hydrate($data)
    {
        if (isset($data['id'])) {
            $this->id = (empty($data['id'])) ? null : (integer) $data['id'];
        }
        if (isset($data['achternaam'])) {
            $this->achternaam = $data['achternaam'];
        }
        if (isset($data['voornaam'])) {
            $this->voornaam = $data['voornaam'];
        }
        if (isset($data['email'])) {
            $this->email = $data['email'];
        }
        if (isset($data['bsn'])) {
            $this->bsn = $data['bsn'];
        }
        if (isset($data['geboorte'])) {
            $this->geboorte = $this->datetime($data['geboorte']);
        }
        if (isset($data['facebook_id'])) {
            $this->facebook_id = $data['facebook_id'];
        }
        if (isset($data['facebook_token'])) {
            $this->facebook_token = $data['facebook_token'];
        }
        if (isset($data['facebook_date'])) {
            $this->facebook_date = $this->datetime($data['facebook_date']);
        }
        if (isset($data['nieuwsbrief'])) {
            $this->nieuwsbrief = $data['nieuwsbrief'];
        }
    }

    public function extract()
    {
        return array(
            'id' => $this->id,
            'achternaam' => $this->achternaam,
            'voornaam' => $this->voornaam,
            'email' => $this->email,
            'bsn' => $this->bsn,
            'geboorte' => $this->formatDate($this->geboorte, 'Y-m-d'),
            'facebook_id' => $this->facebook_id,
            'facebook_token' => $this->facebook_token,
            'facebook_date' => $this->formatDate($this->facebook_date),
            'nieuwsbrief' => $this->nieuwsbrief,
        );
    }

}

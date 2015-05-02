<?php

class AangifteEntity extends AbstractEntity
{

    public $id;
    public $aangifte_id;
    public $datum;
    public $jaar;
    public $student;
    public $inkomen;
    public $opgaves = array();
    public $teruggave;
    public $studiefinanciering;
    public $extrakosten;
    public $studiekosten = array();
    public $ziektekosten = array();
    public $akkoord = false;
    public $email_verstuurd;
    public $korting;
    public $user_id;
    public $betaal_id;

    /**
     * @var BetaalEntity
     */
    public $betaling;

    /**
     * @var UserEntity
     */
    public $user;


    public function getOpgave($id = null)
    {
        if (!isset($this->opgaves[$id])) {
            return $this->setOpgave(new OpgaveEntity());
        }
        return $this->opgaves[$id];
    }

    public function hasOpgaves()
    {
        return count($this->opgaves) > 0;
    }

    public function setOpgave(OpgaveEntity $opgave)
    {
        if (empty($opgave->id)) {
            $max_id = array_reduce($this->opgaves, function($id, $opgave) {
                return ($id < $opgave->id) ? $opgave->id : $id;
            }, 0);
            $opgave->id = $max_id + 1;
        }
        $this->opgaves[$opgave->id] = $opgave;
        return $opgave;
    }

    public function deleteOpgave($id)
    {
        if (isset($this->opgaves[$id])) {
            $this->opgaves[$id] = null;
            $this->opgaves = array_filter($this->opgaves);
        }
    }

    public function getUser()
    {
        if (!$this->user) {
            if ($this->user_id) {
                $user = ServiceProvider::get('UserService')->find($this->user_id);
            }
            else {
                $user = new UserEntity();
            }
            $this->setUser($user);
        }
        return $this->user;
    }

    public function setUser(UserEntity $user = null)
    {
        $this->user_id = ($user) ? $user->id : null;
        $this->user = $user;
        return $this;
    }

    public function hasUser()
    {
        return ($this->user instanceof UserEntity || $this->user_id);
    }

    public function getBetaling()
    {
        if (!$this->betaling) {
            if ($this->betaal_id) {
                $betaling = ServiceProvider::get('BetaalService')->find($this->betaal_id);
            }
            else {
                $betaling = new BetaalEntity(array(
                    'aangifte_id' => $this->aangifte_id,
                    'order_id' => date('Ymd') . sprintf('%u06', $this->id),
                    'bedrag' => $this->getOnkosten(),
                    'omschrijving' => 'Hoeveelkrijgjij.nl',
                ));
            }
            $this->setBetaling($betaling);
        }
        return $this->betaling;
    }

    public function setBetaling(BetaalEntity $betaling = null)
    {
        $this->betaal_id = ($betaling) ? $betaling->id : null;
        $this->betaling = $betaling;
        return $this;
    }

    public function hasBetaling()
    {
        return ($this->betaling instanceof BetaalEntity || $this->betaal_id);
    }

    public function getOpgaveTotals()
    {
        $result = array(
            'salaris' => 0,
            'loonheffing' => 0,
            'arbeidskorting' => 0,
        );
        foreach ($this->opgaves as $opgave) {
            $result['salaris'] += floor($opgave->salaris);
            $result['loonheffing'] += floor($opgave->loonheffing);
            $result['arbeidskorting'] += floor($opgave->arbeidskorting);
        }
        return $result;
    }

    public function getTeruggave($force = false)
    {
        if ($force || !$this->teruggave) {
            $this->teruggave = ServiceProvider::get('AangifteService')->teruggave($this);
        }
        return $this->teruggave;
    }

    public function getOnkosten()
    {
        return ServiceProvider::get('AangifteService')->getOnkosten($this);
    }

    public function hydrate($data)
    {
        if (isset($data['id'])) {
            $this->id = (empty($data['id'])) ? null : (integer) $data['id'];
        }
        if (isset($data['aangifte_id'])) {
            $this->aangifte_id = $data['aangifte_id'];
        }
        if (isset($data['user_id'])) {
            $this->user_id = $data['user_id'];
        }
        if (isset($data['betaal_id'])) {
            $this->betaal_id = $data['betaal_id'];
        }
        if (isset($data['datum'])) {
            $this->datum = $this->datetime($data['datum']);
        }

        if (isset($data['jaar'])) {
            $this->jaar = (integer) $data['jaar'];
        }
        if (isset($data['student'])) {
            $this->student = (boolean) $data['student'];
        }
        if (isset($data['inkomen'])) {
            $this->inkomen = (boolean) $data['inkomen'];
        }
        if (isset($data['studiefinanciering'])) {
            $this->studiefinanciering = (boolean) $data['studiefinanciering'];
        }
        if (isset($data['extrakosten'])) {
            $this->extrakosten = (boolean) $data['extrakosten'];
        }
        if (isset($data['teruggave'])) {
            $this->teruggave = $data['teruggave'];
        }
        if (isset($data['korting'])) {
            $this->korting = (boolean) $data['korting'];
        }

        if (isset($data['akkoord'])) {
            $this->akkoord = (boolean) $data['akkoord'];
        }
        if (isset($data['email_verstuurd'])) {
            $this->email_verstuurd = $this->datetime($data['email_verstuurd']);
        }

        if (isset($data['opgaves'])) {
            if (is_string($data['opgaves'])) {
                $this->opgaves = unserialize($data['opgaves']);
            }
            else {
                $this->opgaves = $data['opgaves'];
            }
        }
        if (isset($data['studiekosten'])) {
            if (is_string($data['studiekosten'])) {
                $this->studiekosten = unserialize($data['studiekosten']);
            }
            else {
                $this->studiekosten = $data['studiekosten'];
            }
        }
        if (isset($data['ziektekosten'])) {
            if (is_string($data['ziektekosten'])) {
                $this->ziektekosten = unserialize($data['ziektekosten']);
            }
            else {
                $this->ziektekosten = $data['ziektekosten'];
            }
        }
    }

    public function extract()
    {
        return array(
            'id' => $this->id,
            'aangifte_id' => $this->aangifte_id,
            'datum' => $this->formatDate($this->datum, 'Y-m-d H:i'),
            'jaar' => $this->jaar,
            'student' => $this->student,
            'inkomen' => $this->inkomen,
            'studiefinanciering' => $this->studiefinanciering,
            'extrakosten' => $this->extrakosten,
            'teruggave' => $this->teruggave,
            'korting' => $this->korting,
            'akkoord' => $this->akkoord,
            'email_verstuurd' => $this->formatDate($this->email_verstuurd, 'Y-m-d H:i'),
            'user_id' => $this->user_id,
            'betaal_id' => $this->betaal_id,
            //
            'opgaves' => serialize($this->opgaves),
            'studiekosten' => serialize($this->studiekosten),
            'ziektekosten' => serialize($this->ziektekosten),
        );
    }

}





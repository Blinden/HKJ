<?php

class BetaalEntity extends AbstractEntity
{

    public $id;
    public $aangifte_id;
    public $order_id;
    public $omschrijving = '';
    public $bedrag = 0.0;
    public $transaction;
    public $datum;
    public $status;

    public function isCompleted()
    {
        $status = $this->getBetaalStatus();
        return $status === 'completed' || $status === 'success';
    }

    public function getBetaalStatus()
    {
        return strtolower($this->status);
    }

    public function getTransactionId()
    {
        if (isset($this->transaction['transactionID'])) {
            return $this->transaction['transactionID'];
        }
        return false;
    }

    /**
     * @return \DateTime|boolean
     */
    public function getTransactionDate()
    {
        if (isset($this->transaction['status']['statusDateTime'])) {
            return $this->transaction['status']['statusDateTime'];
        }
        return false;
    }


    public function getBankAuthenticationUrl()
    {
        if (isset($this->transaction['issuerAuthenticationURL'])) {
            return $this->transaction['issuerAuthenticationURL'];
        }
        return false;
    }

    public function getTransactionStatus()
    {
        if (isset($this->transaction['status']['result'])) {
            return strtolower($this->transaction['status']['result']);
        }
        return false;
    }

    public function extract()
    {
        return array(
            'id' => $this->id,
            'aangifte_id' => $this->aangifte_id,
            'order_id' => $this->order_id,
            'omschrijving' => $this->omschrijving,
            'bedrag' => $this->bedrag,
            'transaction' => serialize($this->transaction),
            'datum' => $this->formatDate($this->datum, 'Y-m-d H:i:s'),
            'status' => $this->status,
        );
    }

    public function hydrate($data)
    {
        if (isset($data['id'])) {
            $this->id = (empty($data['id'])) ? null : (integer) $data['id'];
        }
        if (isset($data['aangifte_id'])) {
            $this->aangifte_id = $data['aangifte_id'];
        }
        if (isset($data['order_id'])) {
            $this->order_id = $data['order_id'];
        }
        if (isset($data['omschrijving'])) {
            $this->omschrijving = $data['omschrijving'];
        }
        if (isset($data['bedrag'])) {
            $this->bedrag = round($data['bedrag'], 2);
        }
        if (isset($data['datum'])) {
            $this->datum = $this->datetime($data['datum']);
        }
        if (isset($data['status'])) {
            $this->status = $data['status'];
        }

        if (isset($data['transaction'])) {
            if (is_string($data['transaction'])) {
                $this->transaction = unserialize($data['transaction']);
            }
            else {
                $this->transaction = $data['transaction'];
            }
        }
    }

}

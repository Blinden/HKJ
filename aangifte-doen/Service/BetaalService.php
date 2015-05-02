<?php

class BetaalService extends TableService
{

    protected $entityClass = 'BetaalEntity';

    public function __construct()
    {
        // TBD
        $this->table = new BetaalTable($this->getDatabase());
    }

    public function getBetaalStatusUrl(BetaalEntity $betaling)
    {
        return $this->getConfig('betaal-url') . '&amp;status&amp;id=' . $betaling->aangifte_id;
    }

    public function getBetaalUrl(BetaalEntity $betaling)
    {
        return $this->getConfig('betaal-url') . '&amp;id=' . $betaling->aangifte_id;
    }

    /**
     * @param string $aangifte_id
     * @return AangifteEntity
     */
    public function getAangifte($aangifte_id)
    {
        return ServiceProvider::get('AangifteService')->getAangifte($aangifte_id);
    }

    /**
     * @param BetaalEntity $betaling
     */
    public function persist(BetaalEntity $betaling)
    {
        if (empty($betaling->id)) {
            $this->table->insert($betaling->extract());
            $betaling->id = $this->table->getDb()->lastInsertId();
        }
        else {
            $this->table->update($betaling->extract());
        }
        return $betaling;
    }

    /**
     * @return IdealService
     */
    protected function getIdealService()
    {
        return ServiceProvider::get('IdealService');
    }

    public function getBanken()
    {
        return $this->getIdealService()->getBankList();
    }

    /**
     *
     */
    public function transactionStatus(BetaalEntity $betaling)
    {
        if (!$betaling->isCompleted()) {
            $idealService = $this->getIdealService();
            $idealService->transactionStatus($betaling);
            $betaling->transaction['status'] = $idealService->getResponse();
            $betaling->status = $betaling->getTransactionStatus();
            $this->persist($betaling);
        }
        return $betaling->getBetaalStatus();
    }

    /**
     * Start een ideal betaal transaction
     *
     * @return string
     */
    public function transactionStart($bank_id, BetaalEntity $betaling)
    {
        if ($betaling->isCompleted()) {
            return false;
        }

        $status_url = $this->getBetaalStatusUrl($betaling);

        $idealService = $this->getIdealService();
        $this->getIdealService()->transactionStart($bank_id, $betaling, $status_url);
        $betaling->transaction = $idealService->getResponse();

        return true;
    }

}

<?php

class BetaalController extends AbstractController
{

    protected $name = 'betaal';

    /**
     *
     * @var BetaalService
     */
    protected $service;

    public function __construct()
    {
        $this->service = ServiceProvider::get('BetaalService');
    }

    /**
     *
     */
    public function dispatch()
    {
        if ($this->hasQuery('status')) {
            return $this->statusAction();
        }
        return $this->betalenAction();
    }

    /**
     * Verwerk een betaling en redirect naar ideal-service
     */
    public function betalenAction()
    {
        if ($this->isPost() && $this->validateCsrf()) {
            $bank_id = $this->getPost('bank_id');
            if (!empty($bank_id)) {
                $aangifte = $this->getAangifte($this->getPost('aangifte_id'));

                if ($this->service->transactionStart($bank_id, $aangifte->getBetaling())) {

                    // Sla de betaling op
                    ServiceProvider::get('AangifteService')->persist($aangifte);

                    // Ga naar de bank
                    $this->redirect($aangifte->getBetaling()->getBankAuthenticationUrl());
                }
                $this->redirect('?action=betaal&status&id=' . $aangifte->aangifte_id);
            }
        }
        $aangifte = $this->getAangifte();

        // Betaling is al verwerkt!
        if ($aangifte->getBetaling()->isCompleted()) {
            $this->redirect("?action=betaal&status&id={$aangifte->aangifte_id}");
        }

        $view = array(
            'csrf' => $this->generateCsrf(),
            'aangifte' => $aangifte,
            'banken' => $this->service->getBanken(),
            'teruggave' => number_format($aangifte->getTeruggave(), 2, ',', '.'),
            'onkosten' => $aangifte->getOnkosten(),
            'progress' => array('step' => 1, 'steps' => 3),
        );
        return $this->render('betalen', $view);
    }

    /**
     * Toon de status van een iDeal betaling
     */
    public function statusAction()
    {
        $aangifte = $this->getAangifte();
        $betaling = $aangifte->getBetaling();

        $this->service->transactionStatus($betaling);
        if ($betaling->getBetaalStatus() !== 'completed') {
            // Switch voor GoogleTags-conversie
            $this->succes = ($betaling->getTransactionStatus() === 'success');
            if ($this->succes) {
                $betaling->status = 'completed';
                $betaling->datum = $betaling->getTransactionDate();
                $this->service->persist($betaling);

                ServiceProvider::get('AangifteService')->deleteAangifteEmails($aangifte->getUser());
                ServiceProvider::get('AangifteService')->sendOrderbevestingsEmail($aangifte);

                //Moneybird
                ServiceProvider::get('AangifteService')->newMoneybirdContact($aangifte->getUser());
                ServiceProvider::get('AangifteService')->newMoneybirdInvoice($aangifte);

                // MailChimp
                ServiceProvider::get('AangifteService')->newMailchimpSubscriber($aangifte->getUser(), 'order');
                ServiceProvider::get('AangifteService')->newMailchimpOrder($aangifte);
            }
        }

        $view = array(
            'csrf' => $this->generateCsrf(),
            'aangifte' => $aangifte,
            'onkosten' => $betaling->bedrag,
            'status' => $betaling->status,
            'isCompleted' => $betaling->isCompleted(),
            'datum' => $betaling->formatDate($betaling->datum, 'Y-m-d H:i:s'),
            'progress' => array('step' => 3, 'steps' => 3),
        );
        return $this->render('status', $view);
    }

    /**
     *
     * @return AangifteEntity
     */
    protected function getAangifte()
    {
        $aangifte_id = $this->getPost('aangifte_id', $this->getQuery('id'));
        $aangifte = $this->service->getAangifte($aangifte_id);

        // Aangifte is niet gevonden?!
        if (!$aangifte) {
            $this->redirect('?action=aangifte&jaar');
        }

        // Opdracht is niet akkoord
        if ($aangifte->akkoord !== true) {
            $this->setMessage('order', 'Je moet eerste akkoord gaan met voorwaarden.');
            return $this->redirect('?action=aangifte&order');
        }

        // Jaar niet ingevuld
        if (!$aangifte->jaar) {
            return $this->redirect('?action=aangifte&jaar');
        }


        return $aangifte;
    }

}

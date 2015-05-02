<?php

class TestController
{

    protected $controller;

    public function dispatch(AbstractController $controller)
    {
        if (!$controller->hasQuery('test')) {
            return false;
        }

        $this->controller = $controller;

        $this->userService = ServiceProvider::get('UserService');
        $this->aangifteService = ServiceProvider::get('AangifteService');
        $this->betaalService = ServiceProvider::get('BetaalService');

//        $this->debugTest();

        if ($controller->getQuery('test') === 'aangifte') {
            if ($controller->getQuery('email') === 'orderbevestiging') {
                return $this->testOrderBevestigingsEmails();
            }
        }

        if ($controller->getQuery('test') === 'mandrill') {

            if ($controller->hasQuery('search')) {
                return $this->mandrillSearchTest();
            }
            if ($controller->hasQuery('delete')) {
                return $this->mandrillDeleteTest();
            }
            if ($controller->hasQuery('scheduled')) {
                return $this->mandrillScheduledTest();
            }
            elseif ($controller->hasQuery('send')) {
                return $this->mandrillSendTest();
            }

            return $this->mandrillScheduledTest();
//            throw new Exception('Onbekende test');
        }

        if ($controller->getQuery('test') === 'error') {
            $this->dispatch();
        }
        if ($controller->getQuery('test') === 'exception') {
            throw new Exception('test');
        }

        $this->defaultTest();
//
//        $aangifte = $this->getAangifte();
//        if (!$aangifte) {
//            $this->controller->redirect('?action=aangifte');
//        }
//        $this->aangifteService->setAangifte($aangifte);
//        $this->controller->redirect('?action=aangifte&order');
    }

    /**
     *
     */
    protected function testOrderBevestigingsEmails()
    {
        /* @var $service AangifteService */
        $service = ServiceProvider::get('AangifteService');
        $aangifte = $this->getAangifte();

        // Orderbevestiging HKJ
        $response = $service->sendOrderbevestingsEmail($aangifte);
        var_dump($response);
    }

    protected function debugTest()
    {
    }

    /**
     * @return AangifteEntity
     */
    protected function getAangifte()
    {
        /* @var $service AangifteService */
        $service = ServiceProvider::get('AangifteService');
        if ($this->controller->hasQuery('id')) {
            return $service->getAangifte($this->controller->getQuery('id'));
        }

        /* @var $aangifte AangifteEntity */
        $aangifte = $service->getAangifte('HdAzjv5azHiF5a3KKOCbpdT5ATnGLUsiXTI6eD6D');
        if (!$aangifte) {
            $aangifte = $service->getAangifte();
        }

        return $aangifte;
    }

    public function mandrillSearchTest()
    {
        /* @var $service MandrillService */
        $service = ServiceProvider::get('MandrillService');
        $params = array(
            'date_from' => '2/4/2014',
            'date_to' => '2/5/2014',
        );
        $reponse['search'] = $service->search($params);

        echo '<pre>';
        print_r($reponse);
        echo '</pre>';
    }

    public function mandrillScheduledTest()
    {
        /* @var $mandrillService MandrillService */
        $mandrillService = ServiceProvider::get('MandrillService');
        $reponse['sheduled'] = $mandrillService->getScheduled();

        echo '<pre>';
        print_r($reponse);
        echo '</pre>';
    }

    public function mandrillDeleteTest()
    {
        /* @var $mandrillService MandrillService */
        $mandrillService = ServiceProvider::get('MandrillService');

        $email = 'bart@oo-it.nl';
        $reponse[] = $mandrillService->getScheduled($email);
//        $reponse[] = $mandrillService->deleteScheduled($email);
        $reponse[] = '$mandrillService->deleteScheduled($email) is niet aangeroepen!';
        $reponse[] = $mandrillService->getScheduled($email);

        echo '<pre>';
        print_r($reponse);
        echo '</pre>';
    }

    public function mandrillSendTest()
    {
        /* @var $mandrillService MandrillService */
        $mandrillService = ServiceProvider::get('MandrillService');
        $aangifte = $this->getAangifte();

        $sender = new UserEntity(array(
            'voornaam' => 'team',
            'achternaam' => 'hoeveelkrijgjij.nl',
            'email' => 'info@hoeveelkrijgjij.nl'
        ));
        $recipients = array(
            'to' => array(
                $aangifte->getUser(),
            ),
        );

        // Orderbevestiging
        $send_at = new \DateTime();
        $reponse['ontvangstbevestiging'] = $mandrillService->sendTemplate('ontvangstbevestiging', $sender, $recipients, $aangifte);
        $reponse['reminder-1'] = $mandrillService->sendTemplate('reminder-1', $sender, $recipients, $aangifte);
        $reponse['reminder-2'] = $mandrillService->sendTemplate('reminder-2', $sender, $recipients, $aangifte);
        $reponse['orderbevestiging'] = $mandrillService->sendTemplate('orderbevestiging', $sender, $recipients, $aangifte);
        $reponse['orderbevestiging-v2'] = $mandrillService->sendTemplate('orderbevestiging-v2', $sender, $recipients, $aangifte);

        echo '<pre>';
        print_r($reponse);
        echo '</pre>';
    }

    protected function defaultTest()
    {
        $aangifte = $this->getAangifte();
        if (!$aangifte) {
            $this->controller->redirect('?action=aangifte');
        }
        if ($this->controller->hasQuery('jaar')) {
            $aangifte->jaar = $this->controller->getQuery('jaar');
        }
        //$aangifte->korting = null;
        $aangifte->akkoord = null;
        $aangifte->email_verstuurd = null;
        $aangifte->getBetaling()->status = null;
        //$aangifte->getBetaling()->transaction = null;
        $this->aangifteService->setAangifte($aangifte);
        if ($aangifte->id) {
            $this->aangifteService->persist($aangifte);
        }

        if ($this->controller->getQuery('action') == 'betaal') {
            $this->controller->redirect('?action=betaal&id=' . $aangifte->aangifte_id);
        }
        $this->controller->redirect('?action=aangifte&order');
    }

    /*
    SELECT * FROM hkj_aangifte
        JOIN hkj_betaal ON hkj_aangifte.betaal_id = hkj_betaal.id
        JOIN hkj_betaal ON hkj_aangifte.betaal_id = hkj_betaal.id
        WHERE hkj_betaal.status = 'completed'
     *
     */
}

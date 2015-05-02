<?php

class AangifteService extends TableService
{

    const MAX_KOSTEN = 20.00;

    /**
     *
     * @var AangifteEntity
     */
    protected $aangifte;
    protected $teruggave;
    protected $aangifteJaren = array(2014, 2013, 2012, 2011, 2010);
    protected $jaren = array(
        2014 => array(
            'jaar' => 2014,
            'function' => 'calc2014',
            'drempel' => array(
                'teruggave' => 14,
                'studiekosten' => 250
            )
        ),
        2013 => array(
            'jaar' => 2013,
            'function' => 'calc2013',
            'drempel' => array(
                'teruggave' => 14,
                'studiekosten' => 250
            )
        ),
        2012 => array(
            'jaar' => 2012,
            'function' => 'calc2012',
            'drempel' => array(
                'teruggave' => 14,
                'studiekosten' => 500
            )
        ),
        2011 => array('jaar' => 2011, 'function' => 'calc2011', 'drempel' => array('teruggave' => 14, 'studiekosten' => 500)),
        2010 => array('jaar' => 2010, 'function' => 'calc2010', 'drempel' => array('teruggave' => 14, 'studiekosten' => 500)),
        2009 => array('jaar' => 2009, 'function' => 'calc2009', 'drempel' => array('teruggave' => 14, 'studiekosten' => 500)),
        2008 => array('jaar' => 2008, 'function' => 'calc2008', 'drempel' => array('teruggave' => 13, 'studiekosten' => 500)),
    );
    protected $entityClass = 'AangifteEntity';

    public function __construct()
    {
        $this->table = new AangifteTable($this->getDatabase());
    }

    public function getProfiel()
    {
        $facebook = new FacebookService();
        return $facebook->getUserProfile();
    }

    /**
     * Geef de jaren waarin aangifte gedaan kan worden
     *
     * @return array
     */
    public function getJaren()
    {
        return $this->aangifteJaren;
    }

    /**
     *
     * @return AangifteEntity
     */
    public function getAangifte($aangifte_id = null)
    {
        $aangifte = null;
        if ($aangifte_id) {
            $aangifte = $this->findOne(array('aangifte_id' => $aangifte_id));
            $this->setAangifte($aangifte);
            return $aangifte;
        }

        if (!$aangifte) {
            $aangifte = $this->getSession('aangifte');
        }
        if (!$aangifte) {
            $aangifte = new AangifteEntity();
        }
        return $aangifte;
    }

    /**
     *
     * @param AangifteEntity $aangifte
     * @return \AangifteService
     */
    public function setAangifte(AangifteEntity $aangifte = null)
    {
        $this->setSession('aangifte', $aangifte);
        return $this;
    }

    /**
     * Bereken de kosten voor deze aangifte in rekening worden gebracht
     *
     * @return float
     */
    public function getOnkosten(AangifteEntity $aangifte)
    {
        // Gratis
        if ($aangifte->jaar === 2008) {
            return 0.0;
        }

        $result = $aangifte->getTeruggave() / 10;
        if ($result > self::MAX_KOSTEN) {
            $result = self::MAX_KOSTEN;
        }
        if ($aangifte->korting === true) {
            $result = ($result * 9) / 10;
        }
        return round($result, 2);
    }

    /**
     *
     * @return UserEntity
     */
    public function getAanvrager()
    {
        return $this->getAangifte()->getUser();
    }

    /**
     * Schrijf de aangifte naar database
     *
     * @return \AangifteService
     */
    public function persist(AangifteEntity $aangifte)
    {
        if ($aangifte->hasUser()) {
            $aangifte->setUser(ServiceProvider::get('UserService')->persist($aangifte->getUser()));
        }

        if ($aangifte->hasBetaling()) {
            $aangifte->setBetaling(ServiceProvider::get('BetaalService')->persist($aangifte->getBetaling()));
        }

        if (empty($aangifte->id)) {
            $aangifte->aangifte_id = $aangifte->aangifte_id? : self::generateId(40);
            $this->table->insert($aangifte->extract());
            $aangifte->id = $this->table->getDb()->lastInsertId();
        } else {
            $this->table->update($aangifte->extract());
        }

        $this->setAangifte($aangifte);
        return $aangifte;
    }

    public function sendOrderbevestingsEmail(AangifteEntity $aangifte)
    {
        $mandrill = ServiceProvider::get('MandrillService');
        $sender = new UserEntity(array(
            'voornaam' => 'team',
            'achternaam' => 'hoeveelkrijgjij.nl',
            'email' => 'info@hoeveelkrijgjij.nl'
        ));

        $recipients = array(
            'to' => array(
                $aangifte->getUser(),
            ),
//            'bcc' => array(
//                new UserEntity(array(
//                    'voornaam' => 'team',
//                    'achternaam' => 'hoeveelkrijgjij.nl',
//                    'email' => 'hoeveelkrijgjij795510@feedbackcompany.nl'
//                ))
//            ),
        );
        // Orderbevestiging
        $reponse['orderbevestiging-v2'] = $mandrill->sendTemplate('orderbevestiging-v2', $sender, $recipients, $aangifte, null, 'hoeveelkrijgjij795510@feedbackcompany.nl');


        $recipients = array(
            'to' => array(
                $sender,
            ),
        );
        // Orderbevestiging HKJ
        $reponse['orderbevestiging-intern'] = $mandrill->sendTemplate('orderbevestiging-intern', $sender, $recipients, $aangifte);
        return $reponse;
    }

    public function sendAangifteMails(AangifteEntity $aangifte)
    {
        $mandrill = ServiceProvider::get('MandrillService');
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
        // ontvangstbevestiging
        $reponse['ontvangstbevestiging'] = $mandrill->sendTemplate('ontvangstbevestiging', $sender, $recipients, $aangifte);

        // Reminder 1 verzenden  over 1 dag
        $send_at = new \DateTime();
        $send_at->add(new DateInterval('P1D'));
        $reponse['reminder-1'] = $mandrill->sendTemplate('reminder-1', $sender, $recipients, $aangifte, $send_at->format('c'));

        // Reminder-2 verzenden over 8 dagen
        $send_at = new \DateTime();
        $send_at->add(new DateInterval('P8D'));
        $reponse['reminder-2'] = $mandrill->sendTemplate('reminder-2', $sender, $recipients, $aangifte, $send_at->format('c'));

        $aangifte->email_verstuurd = new \DateTime();

        return $reponse;
    }

    public function deleteAangifteEmails(UserEntity $user)
    {
        $mandrill = ServiceProvider::get('MandrillService');
        $mandrill->deleteScheduled($user->email);
    }

    public function newMoneybirdContact(UserEntity $user)
    {
        /* As long as there is no other enviroment */
        if (FrontController::getEnviroment() === FrontController::ENV_PRODUCTION) {
            $moneybird = ServiceProvider::get('MoneybirdService');
            $moneybird->newContact($user->voornaam, $user->achternaam, $user->email, $user->id);
        }
    }

    public function newMoneybirdInvoice(AangifteEntity $aangifte)
    {
        /* As long as there is no other enviroment */
        if (FrontController::getEnviroment() === FrontController::ENV_PRODUCTION) {
            $user = $aangifte->getUser();
            $moneybird = ServiceProvider::get('MoneybirdService');
            $moneybird->newInvoice($user->id, $aangifte->getOnkosten(), $user->email, $aangifte->jaar);
        }
    }

    public function newMailchimpSubscriber(UserEntity $user, $list_type)
    {
        /* As long as there is no other enviroment */
        if (FrontController::getEnviroment() === FrontController::ENV_PRODUCTION) {
            $mailchimp = ServiceProvider::get('MailchimpService');
            $mailchimp->newSubscriber($list_type, $user->email, $user->voornaam, $user->achternaam, $user->formatDate($user->geboorte, 'd/m/Y'));
        }
    }

    public function newMailchimpOrder(AangifteEntity $aangifte)
    {
        /* As long as there is no other enviroment */
        if (FrontController::getEnviroment() === FrontController::ENV_PRODUCTION) {
            $user = $aangifte->getUser();
            $betaling = $aangifte->getBetaling();

            $mailchimp = ServiceProvider::get('MailchimpService');
            $mailchimp->newOrder($betaling->order_id, $user->email, $aangifte->getOnkosten(), $aangifte->jaar);
        }
    }

    /**
     * Bereken de teruggave voor deze aangifte
     *
     * @param AangifteEntity $aangifte
     * @return float
     */
    public function teruggave(AangifteEntity $aangifte)
    {
        if (isset($this->jaren[$aangifte->jaar]['function'])) {
            $calculator = $this->jaren[$aangifte->jaar]['function'];
            return round($this->$calculator($aangifte->getOpgaveTotals()), 2);
        }
        return;
    }

    public function getTeruggaveDrempel($jaar)
    {
        return $this->jaren[$jaar]['drempel']['teruggave'];
    }

    public function getStudiekostenDrempel($jaar)
    {
        return $this->jaren[$jaar]['drempel']['studiekosten'];
    }

    protected function getEnviroment()
    {
        return FrontController::getEnviroment();
    }

    protected function calc2014($opgave)
    {
        $a = $opgave['salaris'];
        $b = $opgave['loonheffing'];
        $c = $opgave['arbeidskorting'];

        $tt = 0;

        if ($a < 8913 && $c > ($a * 0.01807)) {
            $d = ((0.051 * $a) + (0.179 * $a) + (0.006 * $a) + (0.1265 * $a));
            $e = (2103 + $c);
            $f = $d - $e;

            if ($e < $d) {
                $f = ($d - $e);
            }
            if ($e > $d) {
                $e = $d;
                $f = ($d - $e);
            }

            $tt = (0 - ($f - $b));
        }

        if ($a < 8913 && $c < ($a * 0.01807)) {
            $d = ((0.051 * $a) + (0.179 * $a) + (0.006 * $a) + (0.1265 * $a));
            $e = 2103 + ($a * 0.01807);
            $f = $d - $e;

            if ($e < $d) {
                $f = ($d - $e);
            }
            if ($e > $d) {
                $e = $d;
                $f = ($d - $e);
            }

            $tt = (0 - ($f - $b));
        }

        if ($a > 8913 && $c > ((161 + (0.18724 * ($a - 8913))))) {
            $d = ((0.051 * $a) + (0.179 * $a) + (0.006 * $a) + (0.1265 * $a));
            $e = (2103 + $c);
            $f = $d - $e;

            if ($e < $d) {
                $f = ($d - $e);
            }
            if ($e > $d) {
                $e = $d;
                $f = ($d - $e);
            }

            $tt = (0 - ($f - $b));
        }

        if ($a > 8913 && $c < ((161 + (0.18724 * ($a - 8913))))) {
            $d = ((0.051 * $a) + (0.179 * $a) + (0.006 * $a) + (0.1265 * $a));
            $e = 2103 + (161 + (0.18724 * ($a - 8913)));
            $f = $d - $e;

            if ($e < $d) {
                $f = ($d - $e);
            }
            if ($e > $d) {
                $e = $d;
                $f = ($d - $e);
            }

            $tt = (0 - ($f - $b));
        }

        return $tt;
    }

    /**
     * 
     * @param array $opgave
     * @return float
     */
    protected function calc2013($opgave)
    {
        $a = $opgave['salaris'];
        $b = $opgave['loonheffing'];
        $c = $opgave['arbeidskorting'];

        $tt = 0;

        if ($a < 8816 && $c > ($a * 0.01827)) {
            $d = ((0.0585 * $a) + (0.179 * $a) + (0.006 * $a) + (0.1265 * $a));
            $e = (2001 + $c);
            $f = $d - $e;

            if ($e < $d) {
                $f = ($d - $e);
            }
            if ($e > $d) {
                $e = $d;
                $f = ($d - $e);
            }

            $tt = (0 - ($f - $b));
        }

        if ($a < 8816 && $c < ($a * 0.01827)) {
            $d = ((0.0585 * $a) + (0.179 * $a) + (0.006 * $a) + (0.1265 * $a));
            $e = 2001 + ($a * 0.01827);
            $f = $d - $e;

            if ($e < $d) {
                $f = ($d - $e);
            }
            if ($e > $d) {
                $e = $d;
                $f = ($d - $e);
            }

            $tt = (0 - ($f - $b));
        }

        if ($a > 8816 && $c > ((161 + (0.16115 * ($a - 8816))))) {
            $d = ((0.0585 * $a) + (0.179 * $a) + (0.006 * $a) + (0.1265 * $a));
            $e = (2001 + $c);
            $f = $d - $e;

            if ($e < $d) {
                $f = ($d - $e);
            }
            if ($e > $d) {
                $e = $d;
                $f = ($d - $e);
            }

            $tt = (0 - ($f - $b));
        }

        if ($a > 8816 && $c < ((161 + (0.16115 * ($a - 8816))))) {
            $d = ((0.0585 * $a) + (0.179 * $a) + (0.006 * $a) + (0.1265 * $a));
            $e = 2001 + (161 + (0.16115 * ($a - 8816)));
            $f = $d - $e;

            if ($e < $d) {
                $f = ($d - $e);
            }
            if ($e > $d) {
                $e = $d;
                $f = ($d - $e);
            }

            $tt = (0 - ($f - $b));
        }

        return $tt;
    }

    /**
     *
     * @param array $opgave
     * @return float
     */
    protected function calc2012($opgave)
    {
        $a = $opgave['salaris'];
        $b = $opgave['loonheffing'];
        $c = $opgave['arbeidskorting'];

        $tt = 0;

        if ($a < 9295 && $c > ($a * 0.01733)) {
            $d = ((0.0195 * $a) + (0.179 * $a) + (0.011 * $a) + (0.1215 * $a));
            $e = (2033 + $c);
            $f = $d - $e;

            if ($e < $d) {
                $f = ($d - $e);
            }
            if ($e > $d) {
                $e = $d;
                $f = ($d - $e);
            }

            $tt = (0 - ($f - $b));
        }

        if ($a < 9295 && $c < ($a * 0.01733)) {
            $d = ((0.0195 * $a) + (0.179 * $a) + (0.011 * $a) + (0.1215 * $a));
            $e = 2033 + ($a * 0.01733);
            $f = $d - $e;

            if ($e < $d) {
                $f = ($d - $e);
            }
            if ($e > $d) {
                $e = $d;
                $f = ($d - $e);
            }

            $tt = (0 - ($f - $b));
        }

        if ($a > 9295 && $c > ((161 + (0.1232 * ($a - 9295))))) {
            $d = ((0.0195 * $a) + (0.179 * $a) + (0.011 * $a) + (0.1215 * $a));
            $e = (2033 + $c);
            $f = $d - $e;

            if ($e < $d) {
                $f = ($d - $e);
            }
            if ($e > $d) {
                $e = $d;
                $f = ($d - $e);
            }

            $tt = (0 - ($f - $b));
        }

        if ($a > 9295 && $c < ((161 + (0.1232 * ($a - 9295))))) {
            $d = ((0.0195 * $a) + (0.179 * $a) + (0.011 * $a) + (0.1215 * $a));
            $e = 2033 + (161 + (0.1232 * ($a - 9295)));
            $f = $d - $e;

            if ($e < $d) {
                $f = ($d - $e);
            }
            if ($e > $d) {
                $e = $d;
                $f = ($d - $e);
            }

            $tt = (0 - ($f - $b));
        }

        return $tt;
    }

    /**
     *
     * @param array $opgave
     * @return float
     */
    protected function calc2011($opgave)
    {
        $a = $opgave['salaris'];
        $b = $opgave['loonheffing'];
        $c = $opgave['arbeidskorting'];

        $tt = 0;

        if ($a < 9210 && $c > ($a * 0.01716)) {
            $d = ((0.0185 * $a) + (0.179 * $a) + (0.011 * $a) + (0.1215 * $a));
            $e = (1987 + $c);
            $f = $d - $e;

            if ($e < $d) {
                $f = ($d - $e);
            }
            if ($e > $d) {
                $e = $d;
                $f = ($d - $e);
            }

            $tt = (0 - ($f - $b));
        }

        if ($a < 9210 && $c < ($a * 0.01716)) {
            $d = ((0.0185 * $a) + (0.179 * $a) + (0.011 * $a) + (0.1215 * $a));
            $e = 1987 + ($a * 0.01716);
            $f = $d - $e;

            if ($e < $d) {
                $f = ($d - $e);
            }
            if ($e > $d) {
                $e = $d;
                $f = ($d - $e);
            }

            $tt = (0 - ($f - $b));
        }

        if ($a > 9209 && $c > ((158 + (0.12152 * ($a - 9209))))) {
            $d = ((0.0185 * $a) + (0.179 * $a) + (0.011 * $a) + (0.1215 * $a));
            $e = (1987 + $c);
            $f = $d - $e;

            if ($e < $d) {
                $f = ($d - $e);
            }
            if ($e > $d) {
                $e = $d;
                $f = ($d - $e);
            }

            $tt = (0 - ($f - $b));
        }

        if ($a > 9209 && $c < ((158 + (0.12152 * ($a - 9209))))) {
            $d = ((0.0185 * $a) + (0.179 * $a) + (0.011 * $a) + (0.1215 * $a));
            $e = 1987 + (158 + (0.12152 * ($a - 9209)));
            $f = $d - $e;

            if ($e < $d) {
                $f = ($d - $e);
            }
            if ($e > $d) {
                $e = $d;
                $f = ($d - $e);
            }

            $tt = (0 - ($f - $b));
        }

        return $tt;
    }

    /**
     *
     * @param array $opgave
     * @return float
     */
    protected function calc2010($opgave)
    {
        $a = $opgave['salaris'];
        $b = $opgave['loonheffing'];
        $c = $opgave['arbeidskorting'];

        $tt = 0;
        if ($a < 9041 && $c > ($a * 0.01737)) {
            $d = ((0.0230 * $a) + (0.179 * $a) + (0.011 * $a) + (0.1215 * $a));
            $e = (1987 + $c);
            $f = $d - $e;

            if ($e < $d) {
                $f = ($d - $e);
            }
            if ($e > $d) {
                $e = $d;
                $f = ($d - $e);
            }

            $tt = (0 - ($f - $b));
        }

        if ($a < 9041 && $c < ($a * 0.01737)) {
            $d = ((0.0230 * $a) + (0.179 * $a) + (0.011 * $a) + (0.1215 * $a));
            $e = 1987 + ($a * 0.01737);
            $f = $d - $e;

            if ($e < $d) {
                $f = ($d - $e);
            }
            if ($e > $d) {
                $e = $d;
                $f = ($d - $e);
            }

            $tt = (0 - ($f - $b));
        }

        if ($a > 9041 && $c > ((157 + (0.11888 * ($a - 9041))))) {
            $d = ((0.0230 * $a) + (0.179 * $a) + (0.011 * $a) + (0.1215 * $a));
            $e = (1987 + $c);
            $f = $d - $e;

            if ($e < $d) {
                $f = ($d - $e);
            }
            if ($e > $d) {
                $e = $d;
                $f = ($d - $e);
            }

            $tt = (0 - ($f - $b));
        }

        if ($a > 9041 && $c < ((157 + (0.11888 * ($a - 9041))))) {
            $d = ((0.0230 * $a) + (0.179 * $a) + (0.011 * $a) + (0.1215 * $a));
            $e = 1987 + (157 + (0.11888 * ($a - 9041)));
            $f = $d - $e;

            if ($e < $d) {
                $f = ($d - $e);
            }
            if ($e > $d) {
                $e = $d;
                $f = ($d - $e);
            }

            $tt = (0 - ($f - $b));
        }

        return $tt;
    }

    /**
     *
     * @param array $opgave
     * @return float
     */
    protected function calc2009($opgave)
    {
        $a = $opgave['salaris'];
        $b = $opgave['loonheffing'];
        $c = $opgave['arbeidskorting'];

        $tt = 0;
        if ($a < 8859 && $c > ($a * 0.01738)) {


            $d = ((0.0235 * $a) + (0.179 * $a) + (0.011 * $a) + (0.1215 * $a));
            $e = (2007 + $c);
            $f = $d - $e;

            if ($e < $d) {
                $f = ($d - $e);
            }
            if ($e > $d) {
                $e = $d;
                $f = ($d - $e);
            }

            $tt = (0 - ($f - $b));
        }

        if ($a < 8859 && $c < ($a * 0.01738)) {
            $d = ((0.0235 * $a) + (0.179 * $a) + (0.011 * $a) + (0.1215 * $a));
            $e = 2007 + ($a * 0.01738);
            $f = $d - $e;

            if ($e < $d) {
                $f = ($d - $e);
            }
            if ($e > $d) {
                $e = $d;
                $f = ($d - $e);
            }

            $tt = (0 - ($f - $b));
        }

        if ($a > 8859 && $c > ((154 + (0.12381 * ($a - 8859))))) {
            $d = ((0.0235 * $a) + (0.179 * $a) + (0.011 * $a) + (0.1215 * $a));
            $e = (2007 + $c);
            $f = $d - $e;

            if ($e < $d) {
                $f = ($d - $e);
            }
            if ($e > $d) {
                $e = $d;
                $f = ($d - $e);
            }

            $tt = (0 - ($f - $b));
        }

        if ($a > 8859 && $c < ((154 + (0.12381 * ($a - 8859))))) {
            $d = ((0.0235 * $a) + (0.179 * $a) + (0.011 * $a) + (0.1215 * $a));
            $e = 2007 + (154 + (0.12381 * ($a - 8859)));
            $f = $d - $e;

            if ($e < $d) {
                $f = ($d - $e);
            }
            if ($e > $d) {
                $e = $d;
                $f = ($d - $e);
            }

            $tt = (0 - ($f - $b));
        }

        return $tt;
    }

    /**
     *
     * @param array $opgave
     * @return float
     */
    protected function calc2008($opgave)
    {
        $a = $opgave['salaris'];
        $b = $opgave['loonheffing'];
        $c = $opgave['arbeidskorting'];

        $tt = 0;

        if ($a < 9209 && $c > ($a * 0.01716)) {
            $d = ((0.0185 * $a) + (0.179 * $a) + (0.011 * $a) + (0.1215 * $a));
            $e = (1987 + $c);
            $f = $d - $e;

            if ($e < $d) {
                $f = ($d - $e);
            }
            if ($e > $d) {
                $e = $d;
                $f = ($d - $e);
            }

            $tt = (0 - ($f - $b));
        }

        if ($a < 9209 && $c < ($a * 0.01716)) {
            $d = ((0.0185 * $a) + (0.179 * $a) + (0.011 * $a) + (0.1215 * $a));
            $e = 1987 + ($a * 0.01716);
            $f = $d - $e;

            if ($e < $d) {
                $f = ($d - $e);
            }
            if ($e > $d) {
                $e = $d;
                $f = ($d - $e);
            }

            $tt = (0 - ($f - $b));
        }

        if ($a > 9209 && $c > ((158 + (0.12152 * ($a - 9209))))) {
            $d = ((0.0185 * $a) + (0.179 * $a) + (0.011 * $a) + (0.1215 * $a));
            $e = (1987 + $c);
            $f = $d - $e;

            if ($e < $d) {
                $f = ($d - $e);
            }
            if ($e > $d) {
                $e = $d;
                $f = ($d - $e);
            }

            $tt = (0 - ($f - $b));
        }

        if ($a > 9209 && $c < ((158 + (0.12152 * ($a - 9209))))) {
            $d = ((0.0185 * $a) + (0.179 * $a) + (0.011 * $a) + (0.1215 * $a));
            $e = 1987 + (158 + (0.12152 * ($a - 9209)));
            $f = $d - $e;

            if ($e < $d) {
                $f = ($d - $e);
            }
            if ($e > $d) {
                $e = $d;
                $f = ($d - $e);
            }

            $tt = (0 - ($f - $b));
        }

        return $tt;
    }

    /**
     * Genereer een random identifier
     *
     * @param type $length
     * @return string
     */
    static protected function generateId($length)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = '';
        $size = strlen($chars);
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[rand(0, $size - 1)];
        }
        return $str;
    }

}

<?php

class AangifteController extends AbstractController
{

    const EMAIL_RECIPIENT = 'info@hoeveelkrijgjij.nl';

    protected $name = 'aangifte';

    /**
     * @var AangifteService
     */
    protected $service;

    public function __construct()
    {
        $this->service = ServiceProvider::get('AangifteService');
    }

    /**
     *
     */
    public function dispatch()
    {
        switch (true) {
            case $this->hasQuery('jaar'):
                $this->jaarAction();
                break;

            case $this->hasQuery('check'):
                $this->checkAction();
                break;

            case $this->hasQuery('opgave'):
                $this->opgaveAction();
                break;

            case $this->hasQuery('studiefinanciering'):
                $this->studiefinancieringAction();
                break;

            case $this->hasQuery('kosten'):
                $this->kostenAction();
                break;

            case $this->hasQuery('profiel'):
                $this->profielAction();
                break;

            case $this->hasQuery('order'):
                $this->orderAction();
                break;

            case $this->hasQuery('korting'):
                $this->kortingAction();
                break;

            case $this->hasQuery('facebook'):
                $this->facebookAction();
                break;

            case $this->hasQuery('betalen'):
                $this->redirect('?action=betaal');
                break;

            case $this->hasQuery('geen-opgave'):
                $this->render('geen-opgave');
                break;

            case $this->hasQuery('jonger-dan-30'):
                $this->render('jonger-dan-30');
                break;

            default:
                $aangifte = new AangifteEntity();
                //$aangifte->setUser($this->service->getAangifte()->getUser());
                $this->service->setAangifte($aangifte);
                $this->jaarAction();
                break;
        }
    }

    /**
     * Verwerk jaar requests
     */
    public function jaarAction()
    {
        $aangifte = $this->service->getAangifte();

        // POST:
        if ($this->isPost() && $this->validateCsrf()) {
            $jaar = (integer) $this->getPost('jaar');
            if (in_array($jaar, $this->service->getJaren())) {
                if ($aangifte->jaar && $jaar !== $aangifte->jaar) {
                    $user = $aangifte->getUser();
                    $aangifte = new $aangifte();
                    $aangifte->setUser($user);
//                    $this->service->setAangifte($aangifte);
                }
                $aangifte->jaar = $jaar;
                $this->redirect('?action=aangifte&jaar&next');
            }
        }

        // GET: &next
        if ($this->hasQuery('next') && in_array($aangifte->jaar, $this->service->getJaren())) {
            $this->redirect('?action=aangifte&check');
        }

        // GET: *
        $view = array(
            'csrf' => $this->generateCsrf(),
            'title' => 'Jaarkeuze',
            'jaren' => $this->service->getJaren(),
            'aangifte' => $aangifte,
            'progress' => array('step' => 1, 'steps' => 5),
        );
        $this->render('jaar', $view);
    }

    /**
     * Alleen als je student of scholier bent, jonger dan 30jaar
     * en/of je minder verdiende dan 18.000 euro dan kun je aangifte doen via de tool
     */
    function checkAction()
    {
        $aangifte = $this->service->getAangifte();

        if ($this->isPost() && $this->validateCsrf()) {
            $check = $this->getPost('check', array());
            $aangifte->student = isset($check['student']) && $check['student'];
            $aangifte->inkomen = isset($check['inkomen']) && $check['inkomen'];

            if (!isset($check['student']) && $check['student'] == false) {
                $this->redirect('?action=aangifte&jonger-dan-30');
            }
            if (!isset($check['inkomen']) && $check['inkomen'] == false) {
                $this->redirect('?action=aangifte&hoog-inkomen');
            }
            if (!isset($check['opgave']) && $check['opgave'] == false) {
                $this->redirect('?action=aangifte&geen-opgave');
            }

            $this->redirect('?action=aangifte&check&next');
        }

        // GET: &prev
        if ($this->hasQuery('prev')) {
            $this->redirect('?action=aangifte&jaar');
        }
        // GET: &next
        if ($this->hasQuery('next')) {
            $this->redirect('?action=aangifte&opgave');
        }

        // GET: *
        $view = array(
            'csrf' => $this->generateCsrf(),
            'title' => 'Geschiktheid',
            'aangifte' => $aangifte,
            'progress' => array('step' => 2, 'steps' => 5),
        );
        $this->render('check', $view);
    }

    /**
     * Je komt niet in aanmerking voor behandeling met de aangifte tool...
     *
     * Verstuur een email als er gegevens gepost zijn.
     * Like us... en/of laat je contact-gegevens achter
     */
    function geenStudentAction()
    {
        $aangifte = $this->service->getAangifte();

        if ($this->isPost() && $this->validateCsrf()) {
            $aangifte->getUser()->hydrate($this->getPost('aanvrager'));

            $this->aangifte = $aangifte;
            $sender = $aangifte->getUser()->email;
            $recipient = self::EMAIL_RECIPIENT;
            $subject = "Betreft: Inkomen +18.000 - {$this->aangifte->jaar}";
            $message = $this->render('inkomen-email');
            $this->sendEmail($sender, $recipient, $subject, $message);

            $this->message = '<h1>Bedankt!</h1><p>Je bericht is om ' . date('H:i:s') . ' verstuurd. Wij zullen jouw bericht zo spoedig mogelijk verwerken.<br /></p>';
            $this->render('final');
            exit;
        }
        $this->csrf = $this->generateCsrf();
        $this->aangifte = $aangifte;
        $this->render('geen-student');
    }

    /**
     *
     *
     * Max 5 werkgevers kunnen verwerkt worden
     */
    function opgaveAction()
    {
        // GET: &prev
        if ($this->hasQuery('prev')) {
            $this->redirect('?action=aangifte&check');
        }
        // GET: *
        if (!$this->service->getAangifte()->hasOpgaves()) {
            $this->redirect('?action=opgave&insert');
        }
        // GET: &next
        if ($this->hasQuery('next')) {
            $this->redirect('?action=aangifte&studiefinanciering');
        }

        $this->redirect('?action=opgave');
    }

    /**
     *
     */
    function studiefinancieringAction()
    {
        $aangifte = $this->service->getAangifte();

        if ($this->isPost() && $this->validateCsrf()) {
            $aangifte->studiefinanciering = (boolean) $this->getPost('antwoord');
            $this->redirect('?action=aangifte&studiefinanciering&next');
        }
        // GET: &prev
        if ($this->hasQuery('prev')) {
            $this->redirect('?action=aangifte&opgave');
        }
        // GET: &next
        if ($this->hasQuery('next')) {
            $this->redirect('?action=aangifte&kosten');
        }

        $view = array(
            'csrf' => $this->generateCsrf(),
            'title' => 'Aftrekposten',
            'aangifte' => $aangifte,
            'progress' => array('step' => 4, 'steps' => 5),
        );
        return $this->render('studiefinanciering', $view);
    }

    /**
     *
     */
    function kostenAction()
    {
        $aangifte = $this->service->getAangifte();

        if ($this->isPost() && $this->validateCsrf()) {
            if ($this->hasPost('antwoord')) {
                $aangifte->extrakosten = (boolean) $this->getPost('antwoord');
                if ($aangifte->extrakosten) {
                    $this->redirect('?action=aangifte&kosten&edit');
                }
                $this->redirect('?action=aangifte&kosten&next');
            }

            if ($this->hasPost('ziektekosten')) {
                $aangifte->ziektekosten = $this->getPost('ziektekosten');
            }
            if ($this->hasPost('studiekosten')) {
                $aangifte->studiekosten = $this->getPost('studiekosten');
            }
            $this->redirect('?action=aangifte&kosten&next');
        }

        // GET: &prev
        if ($this->hasQuery('prev')) {
            $this->redirect('?action=aangifte&studiefinanciering');
        }
        // GET: &next
        if ($this->hasQuery('next')) {
            $this->redirect('?action=aangifte&profiel');
//            $this->redirect('?action=aangifte&teruggave');
        }

        $view = array(
            'csrf' => $this->generateCsrf(),
            'title' => 'Aftrekposten',
            'aangifte' => $aangifte,
            'drempel' => $this->service->getStudiekostenDrempel($aangifte->jaar),
            'progress' => array('step' => 4, 'steps' => 5),
        );
        if ($this->hasQuery('edit')) {
            return $this->render('kosten-edit', $view);
        }
        else {
            return $this->render('kosten', $view);
        }
    }

    public function profielAction()
    {
        $aangifte = $this->service->getAangifte();

        if ($this->isPost() && $this->validateCsrf()) {
            $user = new UserEntity($this->getPost('profiel'));
            $aangifte->setUser($user);

            if ($user->geboorte instanceof \DateTime) {
                if ($user->getAge($aangifte->jaar) >= 30) {
                    $this->redirect('?action=aangifte&jonger-dan-30');
                }
                $aangifte->datum = new \DateTime();
                $this->service->persist($aangifte);

                // Emails worden maar 1 maal verstuurd
                if (!$aangifte->email_verstuurd) {
                    if ($aangifte->getTeruggave(true) > $this->service->getTeruggaveDrempel($aangifte->jaar)) {
                        $this->service->sendAangifteMails($aangifte);
                        $aangifte->email_verstuurd = new \DateTime();
                    }
                }

                $this->service->persist($aangifte);
                // Voeg gebruiker toe aan MailChimp mailinglijst
                ServiceProvider::get('AangifteService')->newMailchimpSubscriber($aangifte->getUser(), 'profile');
                // Voeg gebruiker toe aan MailChimp nieuwsbrief mailinglijsten
                if ($user->nieuwsbrief == true) {
                    ServiceProvider::get('AangifteService')->newMailchimpSubscriber($aangifte->getUser(), 'newsletter');
                }
                $this->redirect('?action=aangifte&profiel&next');
            }
            else {
                $this->setMessage('profiel', 'Je hebt je geboortedatum niet goed ingevuld.');
            }
        }

        // GET: &prev
        if ($this->hasQuery('prev')) {
            $this->redirect('?action=aangifte&kosten');
        }
        // GET: &next
        if ($this->hasQuery('next')) {
            $this->redirect('?action=aangifte&order');
        }

        $view = array(
            'csrf' => $this->generateCsrf(),
            'title' => 'Klantgegevens',
            'aangifte' => $aangifte,
            'profiel' => $aangifte->getUser(),
            'progress' => array('step' => 5, 'steps' => 5),
        );
        return $this->render('profiel', $view);
    }

    /**
     * A order pagina
     */
    function orderAction()
    {
        $aangifte = $this->service->getAangifte();
        if ($this->isPost() && $this->validateCsrf() && $this->getPost('akkoord') == true) {

            $aangifte->akkoord = true;
            $aangifte->datum = new \DateTime();
            // Emails worden maar 1 maal verstuurd
            if (!$aangifte->email_verstuurd) {
                if ($aangifte->getTeruggave(true) > $this->service->getTeruggaveDrempel($aangifte->jaar)) {
                    $this->service->sendAangifteMails($aangifte);
                    $aangifte->email_verstuurd = new \DateTime();
                }
            }
            $this->service->persist($aangifte);

            $this->redirect('?action=aangifte&order&next');
        }

        // GET: &prev
        if ($this->hasQuery('prev')) {
            $this->redirect('?action=aangifte&profiel');
        }
        // GET: &next
        if ($this->hasQuery('next')) {
            $this->redirect("?action=betaal&id={$aangifte->aangifte_id}");
        }

        $view = array(
            'csrf' => $this->generateCsrf(),
            'aangifte' => $aangifte,
            'teruggave' => $aangifte->getTeruggave(true),
            'onkosten' => $aangifte->getOnkosten(),
            'drempel' => $this->service->getTeruggaveDrempel($aangifte->jaar),
        );
        return $this->render('order', $view);
    }

    protected function getFacebookMessage(AangifteEntity $aangifte)
    {
        return <<<"TXT"
Ik heb net op Hoeveelkrijgjij.nl uitgerekend hoeveel belastinggeld ik kan terugkrijgen. Je kunt het zelf ook eenvoudig berekenen, en direct je belastingaangifte laten regelen.
TXT;
    }

    /**
     *
     */
    public function kortingAction()
    {
        $aangifte = $this->service->getAangifte();

        if ($this->hasQuery('ja')) {
            $facebook = new FacebookService();
            $response = $facebook->facebook->api('/me/feed', 'POST', array(
                'message' => $this->getFacebookMessage($aangifte),
                'link' => 'https://hoeveelkrijgjij.nl/?utm_source=facebook&utm_medium=social&utm_campaign=10%25%20korting',
                'picture' => 'https://hoeveelkrijgjij.nl/assets/bibi-espina-2-fb.jpeg',
                'name' => 'Hoeveelkrijgjij.nl',
                'description' => 'Op HoeveelKrijgJij.nl check je razendsnel of je belastinggeld kunt terugkrijgen. Als je wilt, regelt HoeveelKrijgJij.nl direct je belastingaangifte!'
            ));

            if (isset($response['id'])) {
                $aangifte->korting = true;
                if ($aangifte->hasBetaling()) {
                    $betaling =$aangifte->getBetaling();
                    $betaling->bedrag = $aangifte->getOnkosten();
                }
                $this->service->persist($aangifte);
            }
            $this->redirect('?action=aangifte&korting&next');
        }
        if ($this->hasQuery('nee')) {
            $aangifte->korting = false;
            $this->service->persist($aangifte);
            $this->redirect('?action=aangifte&korting&next');
        }

        // GET: &prev
        if ($this->hasQuery('prev')) {
            $this->redirect('?action=aangifte&order');
        }
        // GET: &next
        if ($this->hasQuery('next') || $aangifte->korting) {
            $this->redirect('?action=aangifte&order');
        }

        $this->redirect('?action=aangifte&facebook');
    }

    protected function facebookStatusAction()
    {
        $facebook = new FacebookService();
        if (!$facebook->getUserId()) {
            $this->redirect($facebook->getLoginStatus());
        }
    }

    /**
     *
     */
    function facebookAction()
    {
        $aangifte = $this->service->getAangifte();

        if ($aangifte->korting === true) {
            $this->redirect('?action=aangifte&korting');
        }

        $facebook = new FacebookService();
        if ($facebook->getUserId()) {
            $profile = $facebook->getUserProfile();
            if (!$profile) {
                $this->redirect($facebook->getLoginUrl());
            }
            // Update user with profile
            $aangifte->getUser()->hydrate($profile->extract());
            $this->service->persist($aangifte);
        }
        elseif (!$this->hasQuery('error')) {
            $this->redirect($facebook->getLoginUrl());
        }

        $korting = round($aangifte->getOnkosten() / 10, 2);

        $view = new stdClass();
        $view->app_id = $facebook->facebook->getAppId();

        $view->aangifte = $aangifte;
        $view->teruggave = number_format($aangifte->getTeruggave(), 0, ',', '.');
        $view->onkosten = number_format($aangifte->getOnkosten(), 2, ',', '.');
        $view->korting = number_format($korting, 2, ',', '.');
        $view->message = $this->getFacebookMessage($aangifte);

        $this->render('facebook', $view);
    }

}

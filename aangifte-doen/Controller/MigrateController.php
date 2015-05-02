<?php

class MigrateController extends AbstractController
{

    protected $name = 'migrate';
    protected $userService;
    protected $aangifteService;
    protected $betaalService;

    public function __construct()
    {
        $this->userService = new UserService();
        $this->aangifteService = new AangifteService();
        $this->betaalService = new BetaalService();
    }

    public function dispatch()
    {
        $this->redirect('?action=aangifte');
        if ($this->hasQuery('action')) {
            if ($this->getQuery('action') === 'migrate') {
                return $this->migrateAction();
            }
        }
    }

    protected function migrateAction()
    {
        $table = new AbstractTable($this->aangifteService->getDatabase());
        $table->setTable('aangifte');
        $table->setIdentifier('id');

        $result = $table->select();
        foreach ($result as $data) {

            /* @var orgin_aangifte AangifteEntity */
            $orgin_aangifte = unserialize($data['aangifte_data']);

            if (isset($orgin_aangifte->id)) {
                $orgin_aangifte->aangifte_id = $orgin_aangifte->id;
                $orgin_aangifte->id = null;
            }
            $aangifte = $this->aangifteService->getAangifte($orgin_aangifte->aangifte_id);

            if (!isset($aangifte->email_verstuurd)) {
                if (isset($orgin_aangifte->emailverstuurd)) {
                    $date = $orgin_aangifte->emailverstuurd;
                    $date = str_replace(':', ' ', $date);
                    $date = str_replace('.', ':', $date);
                    $aangifte->email_verstuurd = $aangifte->datetime($date);
                }
                elseif (isset($orgin_aangifte->email_verstuurd)) {
                    $aangifte->email_verstuurd = $aangifte->datetime($orgin_aangifte->email_verstuurd);
                }
            }

            if ($aangifte->email_verstuurd) {
                $aangifte->datum = $aangifte->email_verstuurd;
            }

            $this->aangifteService->persist($aangifte);
        }
    }

}

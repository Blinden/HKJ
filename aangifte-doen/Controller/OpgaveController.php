<?php

class OpgaveController extends AbstractController
{

    protected $name = 'opgave';

    /**
     * @var AangifteService
     */
    protected $service;

    public function __construct()
    {
        $this->service = ServiceProvider::get('AangifteService');
    }

    public function dispatch()
    {
        if ($this->hasQuery('insert') || $this->hasQuery('edit')) {
            return $this->editAction();
        }
        if ($this->hasQuery('delete')) {
            return $this->deleteAction();
        }
        if ($this->hasQuery('cancel')) {
            if (!$this->service->getAangifte()->hasOpgaves()) {
                return $this->redirect('?action=aangifte&opgave&prev');
            }
        }
        if ($this->hasQuery('prev')) {
            return $this->redirect('?action=aangifte&opgave&prev');
        }

        if (!$this->service->getAangifte()->hasOpgaves()) {
            $this->redirect('?action=opgave&insert');
        }
        if ($this->hasQuery('next')) {
            return $this->redirect('?action=aangifte&opgave&next');
        };
        return $this->indexAction();
    }

    public function indexAction()
    {
        $aangifte = $this->service->getAangifte();

        $view = array(
            'title' => "Overzicht jaaropgave(n)",
            'aangifte' => $aangifte,
            'opgaves' => $aangifte->opgaves,
            'opgave_count' => count($aangifte->opgaves),
            'progress' => array('step' => 3, 'steps' => 5),
        );
        $this->render('index', $view);
    }

    public function editAction()
    {
        $aangifte = $this->service->getAangifte();

        // POST
        if ($this->isPost() && $this->validateCsrf()) {
            $opgave = new OpgaveEntity($this->getPost('opgave'));
            $aangifte->setOpgave($opgave);

            if ($this->hasPost('submit-insert') && count($aangifte->opgaves) < 5) {
                $this->redirect('?action=opgave&insert');
            }
            $this->redirect('?action=opgave');
        }

        // GET: &insert || &edit&id=:id
        if ($this->hasQuery('insert') || $this->hasQuery('edit')) {
            $id = (integer) $this->getQuery('edit');
            $view = array(
                'csrf' => $this->generateCsrf(),
                'title' => 'Jaaropgave(n)',
                'aangifte' => $aangifte,
                'opgave' => $aangifte->getOpgave($id),
                'opgave_count' => count($aangifte->opgaves),
                'progress' => array('step' => 3, 'steps' => 5),
            );
            return $this->render('edit', $view);
        }

        $this->redirect('?action=opgave');
    }

    public function deleteAction()
    {
        $aangifte = $this->service->getAangifte();

        // GET: &delete&id=
        if ($this->hasQuery('delete')) {
            $id = (integer) $this->getQuery('delete');
            if ($aangifte->deleteOpgave($id)) {
                $this->setMessage('aangifte-header', 'Opgave is verwijderd');
            }
        }

        $this->redirect('?action=opgave');
    }

}



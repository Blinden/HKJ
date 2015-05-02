<?php

require_once('vendor/mandrill/src/Mandrill.php');

class MandrillService extends AbstractService
{

    /**
     * @var Mandrill
     */
    protected $mandrill;

    /**
     * @var UserEntity
     */
    protected $sender;

    /**
     * @var UserEntity[]
     */
    protected $recipients = array();

    public function __construct()
    {
        $this->mandrill = new Mandrill($this->getConfig('api_key'));
    }

    /**
     *
     * @param AangifteEntity $aangifte
     * @return array
     */
    protected function getMergeVariables(AangifteEntity $aangifte)
    {
        $merge = array();

        $merge[] = array('name' => 'AANGIFTE_ID', 'content' => $aangifte->aangifte_id);
        $merge[] = array('name' => 'JAAR', 'content' => $aangifte->jaar);
        $merge[] = array('name' => 'TERUGGAVE', 'content' => number_format($aangifte->getTeruggave(), 2, ',', '.'));
        $merge[] = array('name' => 'ONKOSTEN', 'content' => number_format($aangifte->getOnkosten(), 2, ',', '.'));
        $merge[] = array('name' => 'DATUM', 'content' => $aangifte->formatDate($aangifte->datum, 'd-m-Y H:i:s'));

        $opgave_table = ServiceProvider::get('FrontController')->renderBuffered('opgave-table', array('aangifte' => $aangifte), 'mandrill');
        $aftrek_table = ServiceProvider::get('FrontController')->renderBuffered('aftrekpost-table', array('aangifte' => $aangifte), 'mandrill');
        $merge[] = array('name' => 'OPGAVES', 'content' => $opgave_table . $aftrek_table);

        $user = $aangifte->getUser();
        $merge[] = array('name' => 'VOORNAAM', 'content' => $user->voornaam);
        $merge[] = array('name' => 'ACHTERNAAM', 'content' => $user->achternaam);
        $merge[] = array('name' => 'GEBOORTE', 'content' => $user->formatDate($user->geboorte, 'd-m-Y'));
        $merge[] = array('name' => 'EMAIL', 'content' => $user->email);
        $merge[] = array('name' => 'BSN', 'content' => $user->bsn);
        $merge[] = array('name' => 'STUDIEFINANCIERING', 'content' => $aangifte->studiefinanciering ? 'Ja' : 'Nee');

        $betaling = $aangifte->getBetaling();
        $merge[] = array('name' => 'ORDER_ID', 'content' => $betaling->order_id);
        $merge[] = array('name' => 'TRANSACTION_ID', 'content' => $betaling->getTransactionId());
        $merge[] = array('name' => 'TRANSACTION_DATE', 'content' => $betaling->formatDate($betaling->getTransactionDate()));
        $merge[] = array('name' => 'TRANSACTION_STATUS', 'content' => $betaling->getBetaalStatus());

        $merge[] = array('name' => 'BETAAL_URL', 'content' => ServiceProvider::get('BetaalService')->getBetaalUrl($aangifte->getBetaling()));

        return $merge;
    }

    protected function composeRecipients($recipients)
    {
        $result = array();
        foreach ($recipients as $to => $to_recipients) {
            foreach ($to_recipients as $recipient) {
                $result[] = array(
                    'email' => $recipient->email,
                    'name' => $recipient->getFullname(),
                    'type' => $to
                );
            }
        }
        return $result;
    }

    protected function composeMetadata($aangifte)
    {
        return array(
            'aangifte_id' => $aangifte->aangifte_id,
        );
    }

    public function sendTemplate($template, $sender, $recipients, $aangifte, $send_at = '2014-01-01 00:00:00', $bcc = '')
    {
        $message = array(
            'from_email' => $sender->email,
            'from_name' => $sender->getFullname(),
            'to' => $this->composeRecipients($recipients),
            'headers' => array('Reply-To' => $sender->email),
            'important' => false,
            'track_opens' => null,
            'track_clicks' => null,
            'auto_text' => null,
            'auto_html' => null,
            'inline_css' => null,
            'url_strip_qs' => null,
            'preserve_recipients' => null,
            'view_content_link' => null,
            'bcc_address' => $bcc,
            'tracking_domain' => null,
            'signing_domain' => null,
            'return_path_domain' => null,
            'merge' => true,
            'global_merge_vars' => $this->getMergeVariables($aangifte),
            'merge_vars' => array(),
            'tags' => array(),
//          'subaccount' => '',
            'google_analytics_domains' => array('hoeveelkrijgjij.nl'),
            'google_analytics_campaign' => $template,
            'metadata' => array(
                'website' => 'hoeveelkrijgjij.nl',
                'aangifte_id' => $aangifte->aangifte_id,
            ),
            'recipient_metadata' => array(),
            'attachments' => array(),
            'images' => array()
        );

        $async = false;
        $ip_pool = 'Main Pool';

        try {
            $result = $this->mandrill->messages->sendTemplate($template, array(), $message, $async, $ip_pool, $send_at);
        } catch (Mandrill_Error $e) {
            $result = $e;
        }

        return $result;
    }

    public function search($params)
    {
        $query = isset($params['query']) ? $params['query'] : '*';
        $date_from = $params['date_from'];
        $date_to = $params['date_to'];
        $tags = isset($params['tags']) ? $params['tags'] : null;
        $senders = isset($params['senders']) ? $params['senders'] : null;
        $api_keys = isset($params['api_keys']) ? $params['api_keys'] : null;
        $limit = isset($params['limit']) ? $params['limit'] : null;
        try {
            $result = $this->mandrill->messages->search($query, $date_from, $date_to, $tags, $senders, $api_keys, $limit);
        } catch (Mandrill_Error $e) {
            return $e;
        }
        return $result;
    }

    public function info($id)
    {
        try {
            $result = $this->mandrill->messages->info($id);
        } catch (Mandrill_Error $e) {
            return $e;
        }
        return $result;
    }

    public function getScheduled($to = '')
    {
        try {
            $result = $this->mandrill->messages->listScheduled($to);
        } catch (Mandrill_Error $e) {
            return $e;
        }
        return $result;
    }

    public function deleteScheduled($to)
    {
        $scheduled = $this->getScheduled($to);
        if (!$scheduled) {
            return;
        }

        foreach ($scheduled as $message) {
            try {
                $response[] = $this->mandrill->messages->cancelScheduled($message['_id']);
            } catch (Mandrill_Error $e) {
                return $e;
            }
        }

        return $response;
    }

}

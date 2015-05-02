<?php

/**
 * Description of Mailchimp Service
 *
 * @author Hidde Beydals <hello@hidde.co>
 */
require_once('vendor/mailchimp-php-api/Mailchimp.php');

class MailchimpService extends AbstractService
{

    /**
     * @var Mailchimp
     */
    protected $mailchimp;

    /**
     * @var Mailchimp List
     */
    protected $list;

    /**
     * @var Mailchimp Ecomm
     */
    protected $ecomm;

    public function __construct()
    {
        $this->mailchimp = new Mailchimp($this->getConfig('api_key'));
        $this->list = new Mailchimp_Lists($this->mailchimp);
        $this->ecomm = new Mailchimp_Ecomm($this->mailchimp);
    }
    
    /**
     * Voegt nieuwe gebruiker toe aan MailChimp, switch bepaald in welke lijst
     * de gebruiker toegevoegd dient te worden en of er een bevestigings email 
     * verzonden dient te worden naar de gebrukiker (double_optin).
     */
    public function newSubscriber($list_type, $email, $firstname, $lastname, $birthday)
    {
        switch ($list_type):
            case 'profile':
                $id = $this->getConfig('profile_list_id');
                $email = array(
                    'email' => htmlentities($email),
                );
                $merge_vars = array(
                    'FNAME' => $firstname,
                    'LNAME' => $lastname,
                    'BDAY'  => $birthday,
                );
                $double_optin = false;
                break;
            case 'order':
                $id = $this->getConfig('order_list_id');
                $email = array(
                    'email' => htmlentities($email),
                );
                $merge_vars = array(
                    'FNAME' => $firstname,
                    'LNAME' => $lastname,
                    'BDAY'  => $birthday,
                );
                $double_optin = false;
                break;
            case 'newsletter':
                $id = $this->getConfig('newsletter_list_id');
                $email = array(
                    'email' => htmlentities($email),
                );
                $merge_vars = array(
                    'FNAME' => $firstname,
                    'LNAME' => $lastname,
                    'BDAY'  => $birthday,
                );
                $double_optin = true;
                break;
        endswitch;

        $subscriber = $this->list->subscribe($id, $email, $merge_vars, 'html', $double_optin, true);
    }

    /*
     * Voegt bestelling toe aan e-mailadres voor filtering binnen MailChimp
     */
    public function newOrder($order_id, $email, $price, $year)
    {
        $order = array(
            'id' => $order_id,
            'email' => $email,
            'total' => $price,
            'store_id' => $this->getConfig('store_id'),
            'store_name' => $this->getConfig('store_name'),
            'items' => array(
                array(
                    'product_id' => $year,
                    'product_name' => "Belastingaangifte {$year}",
                    'category_id' => 1,
                    'category_name' => 'Belastingaangiften',
                    'qty' => 1,
                    'cost' => $price,
                ),
            )
        );

        $this->ecomm->orderAdd($order);
    }

}
<?php

/**
 * Description of MoneybirdService
 *
 * @author Hidde Beydals <hello@hidde.co>
 */
require_once('vendor/moneybird-php-api/ApiConnector.php');
spl_autoload_register('Moneybird\ApiConnector::autoload');

class MoneybirdService extends AbstractService
{

    /**
     * @var Transport
     */
    protected $transport;

    /**
     * @var Connector
     */
    protected $connector;

    /**
     * @var Exception
     */
    protected $response;

    public function __construct()
    {
        $this->transport = new Moneybird\HttpClient();
        $this->transport->setAuth(
            $this->getConfig('emailaddress'),
            $this->getConfig('password')
        );
        $this->connector = new Moneybird\ApiConnector(
            $this->getConfig('clientname'),
            $this->transport, 
            new Moneybird\XmlMapper()
        );
    }

    public function contactService()
    {
        return $this->connector->getService('Contact');
    }

    public function invoiceService()
    {
        return $this->connector->getService('Invoice');
    }

    public function newContact($voornaam, $achternaam, $email, $id)
    {
        $this->contact = new Moneybird\Contact(array(
            'firstname' => $voornaam,
            'lastname' => $achternaam,
            'email' => $email, 
            'customerId' => $id, 
        ));
        $this->contact->save($this->contactService());
        return $this->contact->id;
    }

    public function newInvoice($id, $price, $email, $jaar)
    {
        define('TAXRATEID_HIGH', $this->getConfig('taxrateid'));
        $this->contact = $this->contactService()->getByCustomerId($id);
        $this->details = new Moneybird\Invoice\Detail\ArrayObject();
        $this->details->append(new Moneybird\Invoice\Detail(array(
            'amount' => 1, 
            'description' => "Belastingaangifte IB/PVV {$jaar}",
            'price' => $price,
            'taxRateId' => TAXRATEID_HIGH,
        )));
        $this->invoice = new Moneybird\Invoice(array(
            'details' => $this->details,
            'pricesAreInclTax' => true
        ), $this->contact);
        $this->invoice->save($this->invoiceService());
        $this->invoice->send($this->invoiceService(), 'email', $email);
        $this->payment = new Moneybird\Invoice\Payment(array(
            'paymentDate' => new DateTime(),
            'paymentMethod' => 'ideal',
            'price' => $price,
            'sendEmail' => true,
            'invoiceProfileId' => 6,
        ));
        $this->invoice->registerPayment(
            $this->invoiceService(),
            $this->payment
        );
    }

}
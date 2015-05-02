<?php

/**
 * Description of IdealService
 *
 * @author Bart
 */
require_once('vendor/ideal/Connector/iDEALConnector.php');

use iDEALConnector\iDEALConnector;
use iDEALConnector\Entities\Transaction;
use iDEALConnector\Entities\AcquirerTransactionResponse;
use iDEALConnector\Entities\AcquirerStatusResponse;
use iDEALConnector\Exceptions\iDEALException;

class IdealService extends AbstractService
{

    /**
     * @var iDEALConnector
     */
    protected $connector;

    /**
     * @var Exception
     */
    protected $response;

    public function __construct()
    {
        $this->connector = iDEALConnector::getDefaultInstance($this->getConnectorConfig());
    }

    protected function getConnectorConfig()
    {
        return $this->getConfig('connector-config');
    }

    /**
     * @return array|boolean
     */
    public function getBankList()
    {
        $this->response = null;
        $result = array();
        try {
            $response = $this->connector->getIssuers();
            foreach ($response->getCountries() as $country) {
                $country_name = $country->getCountryNames();
                foreach ($country->getIssuers() as $issuer) {
                    $result[$country_name][$issuer->getId()] = $issuer->getName();
                }

                $this->acquirerID = $response->getAcquirerID();
                $this->responseDatetime = $response->getDirectoryDate();
            }
        } catch (Exception $e) {
            $this->response = $e;
            return false;
        }
        return $result;
    }

    /**
     * @param type $bank_id
     * @param BetaalEntity $betaling
     * @param type $returnUrl
     * @return AcquirerTransactionResponse
     */
    public function transactionStart($bank_id, BetaalEntity $betaling, $returnUrl = null)
    {
        $this->response = null;
        try {
            // TBD retrieve from config
            $expirationPeriod = 60;
            $currency = 'EUR';
            $language = 'nl';

            $transaction = new Transaction(
                (float) $betaling->bedrag, $betaling->omschrijving, $betaling->aangifte_id, $expirationPeriod, $betaling->order_id, $currency, $language
            );
            $response = $this->connector->startTransaction($bank_id, $transaction, $returnUrl);
        } catch (Exception $e) {
            $this->response = $e;
            return false;
        }
        $this->response = array(
            'status' => true,
            'acquirerID' => $response->getAcquirerID(),
            'issuerAuthenticationURL' => $response->getIssuerAuthenticationURL(),
            'transactionID' => $response->getTransactionID(),
            'purchaseID' => $response->getPurchaseID(),
            'response' => serialize($response),
        );
        return $response;
    }

    public function getResponse()
    {
        if ($this->response instanceof Exception) {
            $e = $this->response;
            $this->response = array(
                'status' => false,
                'result' => 'error',
                'error_msg' => $e->getMessage(),
            );
            if ($e instanceof iDEALException) {
                $this->response['error_msg'] = $e->getConsumerMessage();
            }
        }
        return $this->response;
    }

    /**
     *
     * @param BetaalEntity $betaling
     * @return AcquirerStatusResponse
     */
    public function transactionStatus(BetaalEntity $betaling)
    {
        $transaction_id = $betaling->getTransactionId();
        if (!$transaction_id) {
            return false;
        }
        try {
            $response = $this->connector->getTransactionStatus($transaction_id);
        } catch (Exception $e) {
            $this->response = $e;
            return false;
        }
        $this->response = array(
            'status' => true,
            'result' => $response->getStatus(),
            'acquirerID' => $response->getAcquirerID(),
            'consumerName' => $response->getConsumerName(),
            'consumerIBAN' => $response->getConsumerIBAN(),
            'consumerBIC' => $response->getConsumerBIC(),
            'amount' => $response->getAmount(),
            'currency' => $response->getCurrency(),
            'statusDateTime' => $response->getStatusTimestamp(),
            'transactionID' => $response->getTransactionID(),
            'response' => serialize($response),
        );
        return $response;
    }

}

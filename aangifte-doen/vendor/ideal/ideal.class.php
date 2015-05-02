<?php

use iDEALConnector\iDEALConnector;
use iDEALConnector\Configuration\DefaultConfiguration;
use iDEALConnector\Exceptions\ValidationException;
use iDEALConnector\Exceptions\SecurityException;
use iDEALConnector\Exceptions\SerializationException;
use iDEALConnector\Exceptions\iDEALException;
use iDEALConnector\Entities\DirectoryResponse;
use iDEALConnector\Entities\Transaction;
use iDEALConnector\Entities\AcquirerTransactionResponse;
use iDEALConnector\Entities\AcquirerStatusResponse;

require_once("Connector/iDEALConnector.php");
date_default_timezone_set("UTC");

/**
 * iDeal 3.3.1 Class
 */

class Ideal_SEPA
{
	// iDeal Connector parameter
	private $iDEALConnector = false;
	private $config = false;

	// Error description parameters
	private $errorCode = 0;
	private $errorMsg = "";
	private $consumerMessage = "";

	// iDEAL data parameters
	private $acquirerID = "";
	private $responseDatetime = null;
	private $issuerList = array();
	private $actionType = "";

	// Parameters used by the requestTransaction method
	private $issuerId = "";
	private $purchaseId = "";
	private $amount = "";
	private $description = "";
	private $entranceCode = "";
	private $merchantReturnUrl = "";
	private $expirationPeriod = 0;
	private $issuerAuthenticationURL = "";
	private $transactionID = "";

	// Parameters used by the requestTransactionStatus method
	private $consumerName = "";
	private $consumerIBAN = "";
	private $consumerBIC = "";
	private $currency = "";
	private $statusDateTime = null;
	private $status = "";

	/**
	 * Constructs the class and sets some parameters
	 */
	public function __construct()
	{
		$this->config = new DefaultConfiguration(dirname(__FILE__)."/Connector/config.conf");
		$this->iDEALConnector = iDEALConnector::getDefaultInstance(dirname(__FILE__)."/Connector/config.conf");
		$this->merchantReturnUrl = $this->config->getMerchantReturnURL();
		$this->expirationPeriod = intval($this->config->getExpirationPeriod());
	}

	/**
	 * Request a transaction status
	 */
	public function requestTransactionStatus($transactionID)
	{
		if( $transactionID == "")
		{
			return false;
		}

		try
		{
			$response = $this->iDEALConnector->getTransactionStatus($transactionID);

			$this->acquirerID = $response->getAcquirerID();
			$this->consumerName = $response->getConsumerName();
			$this->consumerIBAN = $response->getConsumerIBAN();
			$this->consumerBIC = $response->getConsumerBIC();
			$this->amount = $response->getAmount();
			$this->currency = $response->getCurrency();
			$this->statusDateTime = $response->getStatusTimestamp();
			$this->transactionID = $response->getTransactionID();
			$this->status = $response->getStatus();

			return array(
				'status' => true,
				'acquirerID' => $this->acquirerID,
				'consumerName' => $this->consumerName,
				'consumerIBAN' => $this->consumerIBAN,
				'consumerBIC' => $this->consumerBIC,
				'amount' => $this->amount,
				'currency' => $this->currency,
				'statusDateTime' => $this->statusDateTime,
				'transactionID' => $this->transactionID,
				'result' => $this->status
			);
		}
		catch (SerializationException $ex)
		{
			return array(
				'status' => false,
				'error_type' => 'Serialization',
				'error_msg' => $ex->getMessage()
			);
		}
		catch (SecurityException $ex)
		{
			return array(
				'status' => false,
				'error_type' => 'Security',
				'error_msg' => $ex->getMessage()
			);
		}
		catch(ValidationException $ex)
		{
			return array(
				'status' => false,
				'error_type' => 'Validation',
				'error_msg' => $ex->getMessage()
			);
		}
		catch (iDEALException $ex)
		{
			return array(
				'status' => false,
				'error_type' => 'iDeal Exception',
				'error_msg' => $ex->getMessage(),
				'error_code' => $ex->getErrorCode(),
				'error_consumerMessage' => $ex->getConsumerMessage()
			);
		}
		catch (Exception $ex)
		{
			return array(
				'status' => false,
				'error_type' => 'Exception',
				'error_msg' => $ex->getMessage()
			);
		}
	}

	/**
	 * Request a transaction
	 */
	public function requestTransaction($issuerId, $purchaseId, $amount, $description, $entranceCode, $merchantReturnUrl = false, $expirationPeriod = 0)
	{
		// Basic check for parameters first
		if($issuerId == "" || $purchaseId == "" || $amount == 0 || $description == "" || $entranceCode == "") {
			return false;
		}

		// Set parameters
		$this->issuerId = $issuerId;
		$this->purchaseId = $purchaseId;
		$this->amount = $amount;
		$this->description = $description;
		$this->entranceCode = $entranceCode;

		// Only set mrechantReturnUrl if set
		if( $merchantReturnUrl != false )
		{
			$this->merchantReturnUrl = $merchantReturnUrl;
		}

		// Only set expirationPeriod if set
		if( intval($expirationPeriod) > 0 )
		{
			$this->expirationPeriod = $expirationPeriod;
		}

		// Try to start a transaction
		try
		{
			$response = $this->iDEALConnector->startTransaction(
				$this->issuerId,
				new Transaction(
					$this->amount,
					$this->description,
					$entranceCode,
					$this->expirationPeriod,
					$this->purchaseId,
					'EUR',
					'nl'
				),
				$this->merchantReturnUrl
			);

			$this->acquirerID = $response->getAcquirerID();
			$this->issuerAuthenticationURL = $response->getIssuerAuthenticationURL();
			$this->transactionID = $response->getTransactionID();

			return array(
				'status' => true,
				'acquirerID' => $this->acquirerID,
				'issuerAuthenticationURL' => $this->issuerAuthenticationURL,
				'transactionID' => $this->transactionID
			);
		}
		catch (SerializationException $ex)
		{
			return array(
				'status' => false,
				'error_type' => 'Serialization',
				'error_msg' => $ex->getMessage()
			);
		}
		catch (SecurityException $ex)
		{
			return array(
				'status' => false,
				'error_type' => 'Security',
				'error_msg' => $ex->getMessage()
			);
		}
		catch(ValidationException $ex)
		{
			return array(
				'status' => false,
				'error_type' => 'Validation',
				'error_msg' => $ex->getMessage()
			);
		}
		catch (iDEALException $ex)
		{
			return array(
				'status' => false,
				'error_type' => 'iDeal Exception',
				'error_msg' => $ex->getMessage(),
				'error_code' => $ex->getErrorCode(),
				'error_consumerMessage' => $ex->getConsumerMessage()
			);
		}
		catch (Exception $ex)
		{
			return array(
				'status' => false,
				'error_type' => 'Exception',
				'error_msg' => $ex->getMessage()
			);
		}

	}

	/**
	 * Get the Issuer List
	 */
	public function getIssuerList()
	{
		try
		{
			$response = $this->iDEALConnector->getIssuers();

			// Loop Countries
			foreach ($response->getCountries() as $country)
			{
				$this->issuerList[$country->getCountryNames()] = array();

				// Loop available banks
				foreach ($country->getIssuers() as $issuer) {
					$this->issuerList[ $country->getCountryNames() ][ $issuer->getId() ] = $issuer->getName();
				}

				$this->acquirerID = $response->getAcquirerID();
				$this->responseDatetime = $response->getDirectoryDate();
			}

			return array(
				'status' => true,
				'acquirerID' => $this->acquirerID,
				'responseDatetime' => $this->responseDatetime,
				'issuerList' => $this->issuerList
			);
		}
		catch (SerializationException $ex)
		{
			return array(
				'status' => false,
				'error_type' => 'Serialization',
				'error_msg' => $ex->getMessage()
			);
		}
		catch (SecurityException $ex)
		{
			return array(
				'status' => false,
				'error_type' => 'Security',
				'error_msg' => $ex->getMessage()
			);
		}
		catch(ValidationException $ex)
		{
			return array(
				'status' => false,
				'error_type' => 'Validation',
				'error_msg' => $ex->getMessage()
			);
		}
		catch (iDEALException $ex)
		{
			return array(
				'status' => false,
				'error_type' => 'iDeal Exception',
				'error_msg' => $ex->getMessage(),
				'error_code' => $ex->getErrorCode(),
				'error_consumerMessage' => $ex->getConsumerMessage()
			);
		}
		catch (Exception $ex)
		{
			return array(
				'status' => false,
				'error_type' => 'Exception',
				'error_msg' => $ex->getMessage()
			);
		}
	}

}
<?php

namespace code\PagSeguro;

use \GuzzleHttp\Client;
use code\Model\Order;

class Transporter 
{

	public static function createSession()
	{

		$client = new Client();

		$response = $client->request('POST', Config::getUrlSessions() . "?" . http_build_query(Config::getAuthentication()),[
				'verify'=>false
			]);

		$xml = simplexml_load_string($response->getBody()->getContents()); # '{"id": 1420053, "name": "guzzle", ...}'

		return ((string)$xml->id);

	}

	public static function sendTransaction(Payment $payment)
	{

		$client = new Client();

		$response = $client->request('POST', Config::getUrlTransaction() . "?" . http_build_query(Config::getAuthentication()),[
				'verify'=>false,
				'headers'=>[
					'Content-Type'=>'application/xml'
				],
				'body'=>$payment->getDOMDocument()->saveXml()
			]);

		$xml = simplexml_load_string($response->getBody()->getContents()); # '{"id": 1420053, "name": "guzzle", ...}'

		var_dump($xml);

		$order = new Order();

		$order->get((int) $xml->reference);

		$order->setPagSeguroTransactionRespose(

			(string)$xml->code,
			(float)$xml->grossAmount,
			(float)$xml->disccountAmount,
			(float)$xml->feeAmount,
			(float)$xml->netAmount,
			(float)$xml->extraAmount,
			(string)$xml->paymentLink

		);

		return $xml;
	}

}

?>
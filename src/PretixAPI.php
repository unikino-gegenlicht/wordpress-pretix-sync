<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class PretixAPI {
	private string $api_key;
	private string $host;

	private Client $client;
	private array $request_options;

	function __construct( $host, $api_key ) {
		$this->host            = $host;
		$this->api_key         = $api_key;
		$this->client          = new Client( [ "base_uri" => "{$this->host}/api/v1/" ] );
		$this->request_options = [
			"headers" => [
				"Authorization" => "Token {$this->api_key}"
			],
			"query"   => [ "page_size" => 50 ]
		];
	}

	/**
	 * @throws GuzzleException
	 */
	function getOrganizers(): array {
		$response      = $this->client->request( "GET", "organizers", $this->request_options );
		$response_body = (string) $response->getBody();
		$response      = json_decode( $response_body, true );

		return $response["results"];
	}

	/**
	 * @throws GuzzleException
	 */
	function getEvents( string $organizer ): array {
		$response      = $this->client->request( "GET", "organizers/{$organizer}/events", $this->request_options );
		$response_body = (string) $response->getBody();
		$response      = json_decode( $response_body, true );

		return $response["results"];
	}


}
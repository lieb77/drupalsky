<?php

declare(strict_types=1);

namespace Drupal\drupalsky;

use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Mail\MailFormatHelper;

use Drupal\drupalsky\AtprotoClientService;
use Drupal\drupalsky\Endpoints;

class PdsRepositoryService {

	public function __construct(
		protected AtprotoClientService $atprotoClient,
    	protected EndPoints $endpoints
  	){}

	/**
	 *
	 *
	 */ 
	public function syncRide(NodeInterface $node) {
		$rkey = $node->uuid();
		
		// Must dereference the bike
		$bid      = $node->field_bike->target_id;
		$bikeName = $bid ? Node::load($bid)->getTitle() : 'Unknown Bike';
		
		// Get your field_ridedate string (e.g., "2026-03-10")
		$rideDateRaw = $node->get('field_ridedate')->value;
		// Append time and Z for AT Protocol compliance
		$isoDate = $rideDateRaw ? $rideDateRaw . 'T12:00:00Z' : date('c', $node->getCreatedTime());
		
		$record = [
			'$type' => 'net.paullieberman.bike.ride',
			'createdAt' => $isoDate, // ADD THIS: The field the network uses for sorting
			'route' => $node->getTitle(),
			'miles' => (int) $node->get('field_miles')->value,
			'date'  => $rideDateRaw, // Keep your original date field for your own lexicon
			'bike'  => $bikeName,
			'url'   => $node->toUrl('canonical', ['absolute' => TRUE])->toString(),
			'body'  => MailFormatHelper::htmlToText($node->body->value),
		];
		
		return $this->atprotoClient->request('POST', $this->endpoints->putRecord(), [
			'json' => [
				'repo' => $this->atprotoClient->getDid(),
				'collection' => 'net.paullieberman.bike.ride',
				'rkey' => $rkey,
				'record' => $record,
			],
		]);
	}
	
}

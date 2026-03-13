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

	public function syncRide(NodeInterface $node) {
		$rkey = $node->uuid();
		
		// Must dereference the bike
		$bid      = $node->field_bike->target_id;
		$bikeName = Node::load($bid)->getTitle();
		
		$record = [
			'$type' => 'net.paullieberman.bike.ride',
			'route' => $node->getTitle(),
			'miles' => (int) $node->get('field_miles')->value,
			'date'  => date('c', $node->getCreatedTime()),
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

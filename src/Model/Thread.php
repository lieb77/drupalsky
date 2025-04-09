<?php

declare(strict_types=1);

namespace Drupal\drupalsky\Model;

class Thread {

  protected $feed = [];

  public function __construct($data) {
    $replies = [];
    foreach ($data as $item) {
      $post = $this->parsePost($item->post);
      foreach ($item->replies as $reply ) {
        $replies[] = $this->parsePost($reply->post);
      }
    }
    $this->feed = [
      'post'    => $post,
      'replies' => $replies,
    ];
  }


  /*
   * getFeed
   *
   */
  public function getFeed() {
    return $this->feed;
  }

  /**
   * Parse post
   *
   * Return array
   */
  private function parsePost($post){

    $ext  = [];
    $parent = '';

    if (isset($post->embed->images)) {
      $image = $post->embed->images[0];
      $ext = [
        'thumb' => $image->thumb,
        'alt'   => $image->alt,
      ];
    }
    elseif (isset($post->embed->external)) {
      $ext = $post->embed->external;
    }

    if (isset($post->record->reply)){
      $puri = $post->record->reply->parent->uri;
      if (preg_match('/at:\/\/did:plc:([^\/]+)\/app\.bsky\.feed\.post\/([^\/]+)/', $puri, $matches)) {
        $parent = $matches[1] . '|' . $matches[2];
      }
    }

    return [
      'author' => !empty($post->author->displayName) ? $post->author->displayName : "",
      'avatar' => !empty($post->author->avatar) ? $post->author->avatar : null,
      'date'   => $this->getDate($post->record->createdAt),
      'text'   => $post->record->text,
      'url'  => $this->atUriToBskyAppUrl($post->uri),
      'ext'      => $ext,
      'parent' => $parent,
    ];
  }

  /**
   * Format date
   *
   */
  private function getDate($date){
    return date('M d, Y H:i', strtotime($date));
  }

  /**
	 * Converts an AT URI for a Bluesky post to a https://bsky.app.
	 *
	 * @param atUri The AT URI of the post.  Must be in the format at://<DID>/<COLLECTION>/<RKEY>
	 * @returns The HTTPS URL to view the post on bsky.app, or null if the AT URI is invalid or not a post.
	 */
	private function atUriToBskyAppUrl($uri) {

	// at://did:plc:6aapcgkhjeffsdjc656mshnp/app.bsky.feed.post/3lhlw4gq4uj2t
  // at://did:plc:xgiwtxbtt6xc7low5vdz7dq4/app.bsky.feed.post/3lj4wjx2gds2g"

		$regex = "/^at:\/\/(did:plc:.+)\/(.+)\/(.+)$/";
		$count = preg_match($regex, $uri, $matches);

		$did        = $matches[1];
		$collection = $matches[2];
		$rkey       = $matches[3];

		if ($collection === 'app.bsky.feed.post') {
			return "https://bsky.app/profile/" . $did . "/post/" . $rkey;
		}
		else {
			return null; // Not a post record
		}
	}

// end of class
}

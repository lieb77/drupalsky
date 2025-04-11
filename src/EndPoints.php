<?php

declare(strict_types=1);

namespace Drupal\drupalsky;


/**
 * Returns endpoints for each function
 *
 *
 */
class EndPoints {

	public function createSession() {
		return '/xrpc/com.atproto.server.createSession';
	}

	public function refreshSession() {
	  return '/xrpc/com.atproto.server.refreshSession';
	}

	public function getProfile() {
		return '/xrpc/app.bsky.actor.getProfile';
	}

	public function getFeed() {
		return '/xrpc/app.bsky.feed.getFeed';
	}

	public function getPosts() {
		return '/xrpc/app.bsky.feed.getPosts';
	}

	public function getPostThread() {
	  return '/xrpc/app.bsky.feed.getPostThread';
	}

	public function getTimeline(){
		return '/xrpc/app.bsky.feed.getTimeline';
	}

	public function searchPosts() {
		return '/xrpc/app.bsky.feed.searchPosts';
	}

	public function getFollowers() {
		return '/xrpc/app.bsky.graph.getFollowers';
	}

	public function getFollows() {
		return '/xrpc/app.bsky.graph.getFollows';
	}

	public function getAuthorFeed() {
		return '/xrpc/app.bsky.feed.getAuthorFeed';
	}

}

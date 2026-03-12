<?php

declare(strict_types=1);

namespace Drupal\drupalsky;

/**
 * Returns endpoints for each function.
 */
class EndPoints
{

    public function createSession()
    {
        return '/xrpc/com.atproto.server.createSession';
    }

    public function refreshSession()
    {
        return '/xrpc/com.atproto.server.refreshSession';
    }

    public function getProfile()
    {
        return '/xrpc/app.bsky.actor.getProfile';
    }

    public function getFeed()
    {
        return '/xrpc/app.bsky.feed.getFeed';
    }

    public function getPosts()
    {
        return '/xrpc/app.bsky.feed.getPosts';
    }

    public function getPostThread()
    {
        return '/xrpc/app.bsky.feed.getPostThread';
    }

    public function getTimeline()
    {
        return '/xrpc/app.bsky.feed.getTimeline';
    }

  
    public function searchPosts()
    {
        return '/xrpc/app.bsky.feed.searchPosts';
    }

   
    public function getFollowers()
    {
        return '/xrpc/app.bsky.graph.getFollowers';
    }

    public function getFollows()
    {
        return '/xrpc/app.bsky.graph.getFollows';
    }

    public function getAuthorFeed()
    {
        return '/xrpc/app.bsky.feed.getAuthorFeed';
    }

	// Added for version 2
	
	/**
     * Read a specific record by its rkey.
     * Useful for checking if a ride exists before importing.
     */
    public function getRecord()
    {
        return '/xrpc/com.atproto.repo.getRecord';
    }

    /**
     * List all records in a collection (e.g., all rides).
     * Useful for your Next.js verification or dashboard.
     */
    public function listRecords()
    {
        return '/xrpc/com.atproto.repo.listRecords';
    }

    /**
     * Create a new record.
     */
    public function createRecord()
    {
        return '/xrpc/com.atproto.repo.createRecord';
    }

    /**
     * Update an existing record or create it if missing.
     * Best for keeping Drupal edits in sync with the PDS.
     */
    public function putRecord()
    {
        return '/xrpc/com.atproto.repo.putRecord';
    }

    /**
     * Delete a record from the PDS.
     */
    public function deleteRecord()
    {
        return '/xrpc/com.atproto.repo.deleteRecord';
    }




}

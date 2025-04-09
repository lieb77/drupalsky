<?php

declare(strict_types=1);

namespace Drupal\drupalsky\Model;

class Profile {

  protected $profile;

  public function __construct($data) {
    $this->profile = [
      'displayName' => !empty($data->displayName) ? $data->displayName : "",
      'handle'      => $data->handle,
      'avatar'      => !empty($data->avatar) ? $data->avatar : null,
      'description' => !empty($data->description) ? $data->description : "",
      'banner'      => $data->banner,
      'followers'   => $data->followersCount,
      'follows'     => $data->followsCount,
      'posts'       => $data->postsCount,
    ];
  }

  /**
   * getProfile
   */
  public function getProfile(){
      return $this->profile;
  }

// end of class
}

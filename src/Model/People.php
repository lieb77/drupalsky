<?php

declare(strict_types=1);

namespace Drupal\drupalsky\Model;

class People {

  protected $people = [];

  public function __construct($data) {

   foreach($data as $person){
      $this->people[] = [
        'displayName' => !empty($person->displayName) ? $person->displayName : "",
        'handle'      => $person->handle,
        'avatar'      => !empty($person->avatar) ? $person->avatar : null,
        'description' => !empty($person->description) ? $person->description : "",
      ];
    }
  }

  /**
   * getProfile
   */
  public function getPeople(){
      return $this->people;
  }

// end of class
}

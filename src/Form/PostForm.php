<?php

declare(strict_types=1);

namespace Drupal\drupalsky\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drupalsky\DrupalSky;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides a DrupalSky form.
 */
final class PostForm extends FormBase {


	/**
   * The controller constructor.
   */
  public function __construct(private DrupalSky $service)
  {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('drupalsky.service'),
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'drupalsky_post';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['body'] = [
			'#type' => 'text_format',
			'#title' => $this->t('Body'),
		];

		$form['link'] = [
			'#type'		=> 'url',
			'#title'  => $this->t("Add link"),
			'#size'		=> 32
		];

  	$form['file'] = [
			'#type'		=> 'file',
			'#title'  => $this->t("Attach image"),
		];




    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Post to Bluesky'),
      ],
    ];


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
   	$body = $form_state->getValue('body');

 		if (mb_strlen($body['value']) > 300) {
			$form_state->setErrorByName('body',
					$this->t('Bluesky posts have to be <= 300 characters.'),
			);
		}

	}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

  	if (!isset($this->service)){
  		$this->service = \Drupal::service('drupalsky.service');
  	}
  dpm($form_state->getValues());

		$form_state->setRebuild(TRUE);
  }

}

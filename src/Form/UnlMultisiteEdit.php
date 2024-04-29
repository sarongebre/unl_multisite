<?php

namespace Drupal\unl_multisite\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Site deletion confirmation.
 */
class UnlMultisiteEdit extends ConfirmFormBase {

  /**
   * Base database API.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $databaseConnection;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Class constructor for form object.
   *
   * @param \Drupal\Core\Database\Connection $database_connection
   *   Base database API.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   */
  public function __construct(Connection $database_connection, Messenger $messenger) {
    $this->databaseConnection = $database_connection;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unl_multisite_site_edit';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to save this information?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('unl_multisite.site_list');
  }



  /**
   * {@inheritdoc}
   */
  public function getFormName() {
    return 'delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unl_multisite_site_edit';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormName() {
    return 'edit';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $site_id = NULL) {
    $site_data = $this->databaseConnection->select('unl_sites', 's')
      ->fields('s', array('site_id', 'd7_site_id', 'site_path','d7_site_path', 'uri', 'installed'))
      ->condition('site_id', $site_id)
      ->execute()
      ->fetchAll();
    if(count($site_data) > 1 ) {
      $form['error_display'] = [
        '#markup' => '<p>Can not edit site. More than one site has this site ID:' . $site_id . '</p>',
      ];
    } else {

      foreach ($site_data as $site) {
        $form['site_id'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Site ID'),
          '#disabled' => TRUE,
          '#value' => $site->site_id,
        );
        $form['d7_site_id'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Drupal 7 ID'),
          '#default_value' => $site->d7_site_id,
          '#description'=> 'Accepts a numerical value and/or a brief description',
        );
        $form['d7_site_path'] = array(
          '#type' => 'url',
          '#title' => $this->t('Drupal 7 Path'),
          '#default_value' => $site->d7_site_path,
          '#description'=> 'This field only accepts external URLs, such as https://cms.unl.edu.',
        );

        $form['site_path'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Site path'),
          '#disabled' => TRUE,
          '#default_value' => $site->site_path,
        );

        $form['uri'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Site URI'),
          '#disabled' => TRUE,
          '#value' => $site->uri,
        );

        $form['installed'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Installed'),
          '#disabled' => TRUE,
          '#value' => ($site->installed == 2 ? 'Installed': 'Not Installed'),
        );

      }
    }

    $form['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    $form['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#submit' => ['::cancelForm'],
    ];


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $d7_site_id = $form_state->getValue('d7_site_id');
    $site_id = $form_state->getValue('site_id');
    $site_path = $form_state->getValue('site_path');
    $d7_site_path = $form_state->getValue('d7_site_path');


    $query = $this->databaseConnection->update('unl_sites');
    $query->fields([
      'd7_site_id' => $d7_site_id,
      'site_path' => $site_path,
      'd7_site_path' => $d7_site_path,
    ]);
    $query->condition('site_id', $site_id);
    $result = $query->execute();

    // Check unl_sites database update result.
    if ($result) {
      \Drupal::messenger()->addMessage($this->t('Drupal 7 data updated successfully.'));
      $form_state->setRedirect('unl_multisite.site_list');
    }
    else {
      \Drupal::messenger()->addMessage($this->t('Failed to update Drupal 7 data.'), 'error');
     }
  }

  public function cancelForm(array &$form, FormStateInterface $form_state) {
    // Cancel button logic.
    $form_state->setRedirect('unl_multisite.site_list');
  }
}

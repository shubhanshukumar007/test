<?php

namespace Drupal\dn_students\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Routing;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * Provides the form for adding students.
 */
class StudentForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dn_student_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $record = NULL) {
    $form['title'] = [
      '#type' => 'markup',
      '#prefix' => '<div class="row"><div class="col-sm-12 contact-us">',
      '#markup' => "<h6>Contact us:</h6>",
    ];

    $form['fname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full Name:'),
      '#maxlength' => 20,
      '#attributes' => [
        'class' => ['txt-class'],
      ],
      '#default_value' => '',
      '#prefix' => '<div class="row"><div id="div-fname col-sm-12"><div class="div-fname-main">',
    ];

    $form['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email:'),
      '#maxlength' => 50,
      '#attributes' => [
        'class' => ['txt-class'],
      ],
      '#default_value' => '',
    ];

    $form['phone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone:'),
      '#maxlength' => 20,
      '#attributes' => [
        'class' => ['txt-class'],
      ],
      '#default_value' => '',
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['Save'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#ajax' => ['callback' => '::saveDataAjaxCallback'],
      '#value' => $this->t('Submit'),
      '#suffix' => '</div></div></div></div></div>',
    ];

    $form['#attached']['library'][] = 'dn_students/global_styles';
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    // Add JavaScript to handle validation errors.
    $form['#attached']['library'][] = 'dn_students/form-validation';


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array & $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Validate Full Name (fname) for empty value.
    if (empty($values['fname'])) {
      $form_state->setErrorByName('fname', $this->t('Full Name field is required.'));
    }

    // Validate Email for empty value and correct format.
    if (empty($values['email'])) {
      $form_state->setErrorByName('email', $this->t('Email field is required.'));
    }
    elseif (!\Drupal::service('email.validator')->isValid($values['email'])) {
      $form_state->setErrorByName('email', $this->t('Invalid email format.'));
    }

    // Validate Phone for empty value and correct format (numeric).
    if (empty($values['phone'])) {
      $form_state->setErrorByName('phone', $this->t('Phone field is required.'));
    }
    elseif (!is_numeric($values['phone'])) {
      $form_state->setErrorByName('phone', $this->t('Phone must be a numeric value.'));
    }
  }

  /**
   * Our custom Ajax response.
   */
  public function saveDataAjaxCallback(array &$form, FormStateInterface $form_state) {
    $conn = Database::getConnection();
    $values = $form_state->getValues();
    $fields["fname"] = $values['fname'];
    $fields["email"] = $values['email'];
    $fields["phone"] = $values['phone'];
    $response = new AjaxResponse();

    // Validate the fields again before proceeding.
    if (empty($fields["fname"])) {
      $css = ['border' => '1px solid red'];
      $text_css = ['color' => 'red'];
      $message = ($this->t('Full Name not valid.'));

      $response->addCommand(new \Drupal\Core\Ajax\CssCommand('#edit-fname', $css));
      $response->addCommand(new \Drupal\Core\Ajax\CssCommand('#div-fname-message', $text_css));
      $response->addCommand(new \Drupal\Core\Ajax\HtmlCommand('#div-fname-message', $message));
    }
    elseif (empty($fields["email"]) || !\Drupal::service('email.validator')->isValid($fields["email"])) {
      $css = ['border' => '1px solid red'];
      $text_css = ['color' => 'red'];
      $message = ($this->t('Invalid email format.'));
      $response->addCommand(new \Drupal\Core\Ajax\CssCommand('#edit-email', $css));
      $response->addCommand(new \Drupal\Core\Ajax\CssCommand('#div-email-message', $text_css));
      $response->addCommand(new \Drupal\Core\Ajax\HtmlCommand('#div-email-message', $message));
    }
    elseif (empty($fields["phone"]) || !is_numeric($fields["phone"])) {
      $css = ['border' => '1px solid red'];
      $text_css = ['color' => 'red'];
      $message = ($this->t('Phone must be a numeric value.'));
      $response->addCommand(new \Drupal\Core\Ajax\CssCommand('#edit-phone', $css));
      $response->addCommand(new \Drupal\Core\Ajax\CssCommand('#div-phone-message', $text_css));
      $response->addCommand(new \Drupal\Core\Ajax\HtmlCommand('#div-phone-message', $message));
    }
    else {
      // Fields are valid, proceed to save.
      $conn->insert('students')
        ->fields($fields)
        ->execute();

      // Clear the form values.
      $form_state->setValue('fname', '');
      $form_state->setValue('email', '');
      $form_state->setValue('phone', '');

      $render_array = \Drupal::formBuilder()->getForm('Drupal\dn_students\Form\StudentTableForm', 'All');
      $response->addCommand(new HtmlCommand('.result_message', ''));
      $response->addCommand(new \Drupal\Core\Ajax\AppendCommand('.result_message', $render_array));
      $response->addCommand(new HtmlCommand('.pagination', ''));
      $response->addCommand(new \Drupal\Core\Ajax\AppendCommand('.pagination', getPager()));
      $response->addCommand(new InvokeCommand('.txt-class', 'val', ['']));
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array & $form, FormStateInterface $form_state) {
    // Handle any additional form submission logic if needed.
  }
}

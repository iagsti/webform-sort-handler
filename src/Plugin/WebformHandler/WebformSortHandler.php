<?php

namespace Drupal\webform_sort_handler\Plugin\WebformHandler;

use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Form\FormStateInterface;


/**
 * Webform Sort Handler
 *
 * @WebformHandler(
 *   id = "webform_sort_handler",
 *   label = @Translation("Sort"),
 *   category = @Translation("Webform Handler"),
 *   description = @Translation("Sort webform submissions"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class WebformSortHandler extends WebformHandlerBase {

  /**
  * {@inheritdoc}
  */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state)
  {
    parent::buildConfigurationForm($form, $form_state);

    $elements = $this->getElements();
    $this->sortElements($elements['sort_container']['sort_table']);

    $form['sort_container'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Sort Order')
    ];

    $form['sort_container']['sort_table'] = [
      '#type' => 'table',
      '#header' => [
        t('Item'),
        t('Enabled'),
        t(''),
        [
          'data' => t('Sort'),
          'colspan' => '1',
        ],
      ],

      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'weight'
        ]
      ]

    ];

    foreach ($elements['sort_container']['sort_table'] as $item) {

      $element = $item['element'];
      $weight = $item['weight'];
      $enabled = $item['enabled'];

      $form['sort_container']['sort_table'][$weight]['#weight'] = $weight;
      $form['sort_container']['sort_table'][$weight]['title'] = ['#markup' => $element,];
      $form['sort_container']['sort_table'][$weight]['element'] = [
        '#type' => 'value',
        '#value' => $element,
      ];

      $form['sort_container']['sort_table'][$weight]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => t('Enable'),
        '#title_display' => 'invisible',
        '#default_value' => $enabled,
      ];

      $form['sort_container']['sort_table'][$weight]['#attributes']['class'][] = 'draggable';
      $form['sort_container']['sort_table'][$weight]['weight'] = [
        '#type' => 'number',
        '#title' => t('Weight for @title', ['@title' => $weight]),
        '#title_display' => 'invisible',
        '#size' => 4,
        '#default_value' => $weight,
        '#attributes' => [
          'class' => [
            'weight'
          ]
        ]
      ];
    }

    return $this->setSettingsParents($form);
  }


  /**
   * Get Table Elements
   */
  protected function getElements()
  {
    $flat = $this->getWebform()->getElementsDecodedAndFlattened();
    $elements = $flat ? $flat : [];
    $elements = array_keys($elements);
    $elements = isset($elements) ? $this->setElementsAttr($elements) : [];

    $config_elements = isset($this->configuration['sort_container']) ?
      $this->configuration['sort_container']['sort_table'] : [];

    $elements_attr = $elements['sort_container']['sort_table'];
    $elements['sort_container']['sort_table'] = array_replace_recursive($elements_attr, $config_elements);

    return $elements;
  }


  /**
   * Add element and width attributes.
   *
   * @param array $elements
   * @return mixed
   */
  protected function setElementsAttr(array $elements)
  {
    $assoc['sort_container']['sort_table'] = [];
    for ($i=0; $i < sizeof($elements); $i++) {
      $assoc['sort_container']['sort_table'][] = [
        'weight' => $i,
        'element' => $elements[$i],
        'enabled' => False,
      ];
    }

    return $assoc;
  }


   /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $configuration = $this->getConfiguration();

    return ['#settings' => $configuration['settings']] + parent::getSummary();
  }


  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
  {
    $this->configuration['sort_container'] = [];
    $this->applyFormStateToConfiguration($form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE)
  {
    $submission = $webform_submission->getData();
    $elements = $this->getElements();
    $elements = $this->getEnabled($elements['sort_container']['sort_table']);
    $this->sortElements($elements);

    $output = [];

    foreach($elements as $item) {
      $e = $item['element'];
      $output[$e] = $submission[$e];
    }

    $webform_submission->setData($output);
  }


  /**
   * Get enabled elements.
   *
   * @param array $elements
   * @return array
   */
  protected function getEnabled(array $elements)
  {
    return array_filter($elements, function ($item) {
      return $item['enabled'];
    });
  }


  /**
   * Sort elements by weight.
   *
   * @param array $elements
   */
  protected function sortElements (array &$elements)
  {
    uasort($elements, function($a, $b) {
      if ($a['weight'] == $b['weight']) return 0;
      return ($a['weight'] < $b['weight']) ? -1:1;
    });
  }

}

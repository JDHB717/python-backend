<?php

namespace Drupal\contrib_tracker\Plugin\QueueWorker;

use Drupal\contrib_tracker\ContributionManagerInterface;
use Drupal\contrib_tracker\ContributionRetrieverInterface;
use Drupal\contrib_tracker\ContributionStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Retrieve user's information from drupal.org.
 *
 * @QueueWorker(
 *   id = "contrib_tracker_process_users",
 *   title = @Translation("Process users for contribution tracking"),
 *   cron = {"time" = 600}
 * )
 */
class ProcessUser extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Contribution manager service.
   *
   * @var \Drupal\contrib_tracker\ContributionManagerInterface
   */
  protected $contributionManager;

  /**
   * Contribution retriever service.
   *
   * @var \Drupal\contrib_tracker\ContributionRetrieverInterface
   */
  protected $contributionRetriever;

  /**
   * Contribution storage service.
   *
   * @var \Drupal\contrib_tracker\ContributionStorageInterface
   */
  protected $contributionStorage;

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (is_a($data, UserInterface::class)) {
      $do_username = $data->field_drupalorg_username[0]->getValue()['value'];
      $do_user = $this->contributionRetriever->getUserInformation($do_username);
      $uid = $do_user->getId();

      // @TODO: Add logging across the operation.
      // Store all comments by the user.
      $this->contributionManager->storeCommentsByDrupalOrgUser($uid, $data);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContributionManagerInterface $manager, ContributionRetrieverInterface $retriever, ContributionStorageInterface $contribution_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->contributionManager = $manager;
    $this->contributionRetriever = $retriever;
    $this->contributionStorage = $contribution_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('contrib_tracker_manager'),
      $container->get('contrib_tracker_retriever'),
      $container->get('contrib_tracker_storage')
    );
  }

}

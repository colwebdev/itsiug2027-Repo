<?php

namespace Drupal\csv_import_user\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\user\Entity\User;
use Drupal\user\RoleStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for importing users from a CSV file.
 */
class UserImportCsvForm extends FormBase {

  protected $entityTypeManager;
  protected $fileSystem;
  protected $roleStorage;

  /**
   * The file repository service.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected $fileRepository;

  /**
   * Constructs the service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   * @param \Drupal\user\RoleStorageInterface $role_storage
   *   The role storage.
   * @param \Drupal\file\FileRepositoryInterface $file_repository
   *   The file repository service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    FileSystemInterface $file_system,
    RoleStorageInterface $role_storage,
    FileRepositoryInterface $file_repository,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->roleStorage = $role_storage;
    $this->fileRepository = $file_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('entity_type.manager')->getStorage('user_role'),
      $container->get('file.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_import_csv_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['csv_file'] = [
      '#type' => 'file',
      '#title' => $this->t('CSV File'),
      '#description' => $this->t('Upload a CSV file to import users.'),
      '#required' => TRUE,
    ];

    // Get custom user fields.
    $custom_fields = $this->getUserCustomFields();

    if (!empty($custom_fields)) {
      $form['custom_fields'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Select custom fields to import'),
        '#options' => $custom_fields,
        '#description' => $this->t('Select which custom fields should be imported.'),
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import Users'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Handle the uploaded file with validation for only CSV files, 5 MB limit.
    $validators = [
      'FileExtension' => ['extensions' => 'csv'],
      'FileSizeLimit' => ['fileLimit' => 5242880],
    ];

    $file = file_save_upload('csv_file', $validators, FALSE, 0);

    if ($file) {
      $file = $this->entityTypeManager->getStorage('file')->load($file->id());

      if ($file) {
        // Read and parse the CSV content.
        $csv_data = $this->parseCsv($file->getFileUri());

        // Get selected custom fields from the form state.
        $selected_fields = $form_state->getValue('custom_fields');

        $selected_fields = array_filter($selected_fields);

        // Import users and include selected custom fields.
        $this->importUsers($csv_data, $selected_fields);

        $this->messenger()->addMessage($this->t('User import complete.'));
      }
      else {
        $this->messenger()->addError($this->t('Failed to load the file.'));
      }
    }
    else {
      $this->messenger()->addError($this->t('There was an error uploading the file. Only CSV files are allowed.'));
    }
  }

  /**
   * Parse the CSV file into an array of user data.
   *
   * @param string $uri
   *   The file URI of the uploaded CSV file.
   *
   * @return array
   *   An array of parsed CSV data.
   */
  private function parseCsv($uri) {
    $data = [];
    $file_path = $this->fileSystem->realpath($uri);

    $handle = fopen($file_path, 'r');
    if ($handle) {
      $headers = fgetcsv($handle);

      while (($row = fgetcsv($handle)) !== FALSE) {
        $data[] = array_combine($headers, $row);
      }
      fclose($handle);
    }

    return $data;
  }

  /**
   * Import the users based on the parsed CSV data and selected custom fields.
   *
   * @param array $csv_data
   *   An array of parsed CSV data.
   * @param array $selected_fields
   *   An array of selected custom fields to import (keyed by field name).
   */
  private function importUsers(array $csv_data, array $selected_fields) {
    foreach ($csv_data as $row) {
      if (isset($row['username']) && isset($row['email'])) {
        $existing_user = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $row['username']]);

        if (!empty($existing_user)) {
          $this->messenger()->addWarning($this->t('User with username @username already exists. Skipping import for @username.', ['@username' => $row['username']]));
        }
        else {
          $user = User::create([
            'name' => $row['username'],
            'mail' => $row['email'],
            'status' => 1,
          ]);

          foreach ($selected_fields as $field_name => $checked) {
            if ($checked && isset($row[$field_name])) {
              if ($field_name == 'user_picture' && !empty($row[$field_name])) {
                $file_uri = $row[$field_name];

                /** @var \Drupal\file\FileInterface[] $files */
                $files = $this->entityTypeManager
                  ->getStorage('file')
                  ->loadByProperties(['uri' => $file_uri]);

                /** @var \Drupal\file\FileInterface|null $file */
                $file = reset($files) ?: NULL;

                if ($file) {
                  $file->setPermanent();
                  $file->save();
                  $user->set('user_picture', ['target_id' => $file->id()]);
                }
                else {
                  $image_data = file_get_contents($file_uri);
                  if ($image_data) {
                    // Specify the destination path.
                    $destination = 'public://pictures/' . basename($file_uri);

                    $file = $this->fileRepository->writeData($image_data, $destination, FileSystemInterface::EXISTS_REPLACE);

                    if ($file) {
                      $file->setPermanent();
                      $file->save();

                      $user->set('user_picture', ['target_id' => $file->id()]);
                    }
                    else {
                      $this->messenger()->addWarning($this->t('Failed to save image from URL for @username.', ['@username' => $row['username']]));
                    }
                  }
                  else {
                    $this->messenger()->addWarning($this->t('Could not fetch image from URL for @username.', ['@username' => $row['username']]));
                  }
                }
              }
              else {
                $user->set($field_name, $row[$field_name]);
              }
            }
          }

          // Assign the role.
          if (isset($row['role'])) {
            $role_name = trim($row['role']);
            $role = $this->roleStorage->load($role_name);
            if ($role) {
              $user->addRole($role_name);
            }
            else {
              $this->messenger()->addWarning($this->t('Role @role not found for user @user', ['@role' => $role_name, '@user' => $row['username']]));
            }
          }

          $user->save();
          $this->messenger()->addMessage($this->t('User @username imported successfully.', ['@username' => $row['username']]));
        }
      }
      else {
        $this->messenger()->addWarning($this->t('Skipping row due to missing required fields: username or email.'));
      }
    }
  }

  /**
   * Get all custom fields for the user entity.
   *
   * @return array
   *   An associative array of field names.
   */
  private function getUserCustomFields() {
    $fields = [];

    // Load all field configurations for the user entity type.
    $field_definitions = $this->entityTypeManager->getStorage('field_config')->loadByProperties(['entity_type' => 'user']);

    $base_fields = ['name', 'mail', 'status'];

    foreach ($field_definitions as $field_definition) {
      $field_name = $field_definition->getName();

      // Skip base fields.
      if (in_array($field_name, $base_fields)) {
        continue;
      }

      // Add custom fields to the list.
      $fields[$field_name] = $field_definition->getLabel();
    }

    return $fields;
  }

}

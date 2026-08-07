<?php

declare(strict_types=1);

namespace Drupal\dxpr_builder\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Environment;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Lock\LockAcquiringException;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\file\Upload\FileUploadHandler;
use Drupal\file\Upload\FormUploadedFile;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\MimeTypes;

/**
 * Controller for handling file uploads.
 */
final class UploadFileController extends ControllerBase {

  /**
   * Construct an UploadFileController object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   * @param \Drupal\file\Upload\FileUploadHandler $fileUploadHandler
   *   The file upload handler.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock service.
   */
  public function __construct(
    protected FileSystemInterface $fileSystem,
    protected FileUploadHandler $fileUploadHandler,
    protected LockBackendInterface $lock,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('file_system'),
      $container->get('file.upload_handler'),
      $container->get('lock'),
    );
  }

  /**
   * Callback to handle AJAX file uploads.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The http request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns JSON response.
   */
  public function fileUpload(Request $request): JsonResponse {
    // Getting the UploadedFile directly from the request.
    $uploads = $request->files->get('upload');
    if (empty($uploads)) {
      return new JsonResponse(['message' => 'No files were uploaded.'], Response::HTTP_BAD_REQUEST);
    }

    $default_scheme = $this->config('system.file')->get('default_scheme');

    /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $upload */
    foreach ($uploads as $upload) {
      if ($upload === NULL || !$upload->isValid()) {
        return new JsonResponse(['message' => $upload?->getErrorMessage() ?: 'Invalid file upload'], Response::HTTP_INTERNAL_SERVER_ERROR);
      }

      $filename = $upload->getClientOriginalName();

      $type = explode('/', $upload->getClientMimeType());
      $type = $type[0] ?: 'file';

      $destination = match ($type) {
        'image' => $default_scheme . '://dxpr_builder_images',
        'video' => $default_scheme . '://dxpr_builder_videos',
        default => $default_scheme . '://dxpr_builder_files',
      };

      // Check the destination file path is writable.
      if (!$this->fileSystem->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY)) {
        return new JsonResponse(['message' => 'Destination file path is not writable'], Response::HTTP_INTERNAL_SERVER_ERROR);
      }

      $validators = $this->getUploadFileValidators($type);

      $file_uri = "{$destination}/{$filename}";
      // "FileSystemInterface::EXISTS_RENAME" is added for D9 compatibility.
      // @phpstan-ignore-next-line
      $file_uri = $this->fileSystem->getDestinationFilename($file_uri, class_exists(FileExists::class) ? FileExists::Rename : FileSystemInterface::EXISTS_RENAME);

      // Lock based on the prepared file URI.
      $lock_id = $this->generateLockIdFromFileUri($file_uri);

      if (!$this->lock->acquire($lock_id)) {
        return new JsonResponse(['message' => sprintf('File "%s" is already locked for writing.', $file_uri)], Response::HTTP_SERVICE_UNAVAILABLE);
      }

      try {
        $uploadedFile = new FormUploadedFile($upload);
        // "FileSystemInterface::EXISTS_RENAME" is added for D9 compatibility.
        // @phpstan-ignore-next-line
        $uploadResult = $this->fileUploadHandler->handleFileUpload($uploadedFile, $validators, $destination, class_exists(FileExists::class) ? FileExists::Rename : FileSystemInterface::EXISTS_RENAME, FALSE);
        // Method "FileUploadResult::hasViolations()" doesn't exist in D9.
        // @phpstan-ignore function.alreadyNarrowedType
        if (method_exists($uploadResult, 'hasViolations') && $uploadResult->hasViolations()) {
          return new JsonResponse(['message' => implode('. ', array_map(fn ($violation) => $violation->getMessage(), (array) $uploadResult->getViolations()))], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
      }
      catch (FileException $e) {
        return new JsonResponse(['message' => 'File could not be saved'], Response::HTTP_INTERNAL_SERVER_ERROR);
      }
      catch (LockAcquiringException $e) {
        return new JsonResponse(['message' => sprintf('File "%s" is already locked for writing.', $upload->getClientOriginalName())], Response::HTTP_SERVICE_UNAVAILABLE);
      }

      $this->lock->release($lock_id);

      $file = $uploadResult->getFile();

      $files[] = [
        'url' => $file->createFileUrl(relative: FALSE),
        'uuid' => $file->uuid(),
        'fid' => $file->id(),
        'entity_type' => $file->getEntityTypeId(),
      ];
    }

    return new JsonResponse($files ?? [], Response::HTTP_CREATED);
  }

  /**
   * Generates a lock ID based on the file URI.
   *
   * @param string $file_uri
   *   The file URI.
   *
   * @return string
   *   The generated lock ID.
   */
  protected static function generateLockIdFromFileUri(string $file_uri): string {
    return 'file:dxpr_builder:' . Crypt::hashBase64($file_uri);
  }

  /**
   * Retrieves file upload validators based on the specified type.
   *
   * @param string $type
   *   The type of file being uploaded.
   *
   * @return array
   *   An associative array of upload validators.
   *
   * @phpstan-return array<string, array<int|string, mixed>>
   */
  protected function getUploadFileValidators(string $type): array {
    $default_mimetypes = MimeTypes::getDefault();

    // @todo This should be global variable and shared with js app as well.
    $mimetypes = match ($type) {
      'image' => ['gif', 'jpg', 'jpeg', 'png', 'svg'],
      'video' => ['webm', 'ogv', 'ogg', 'mp4'],
      default => [],
    };

    $allowed_extensions = [];
    foreach ($mimetypes as $mime_type) {
      $allowed_extensions = [
        ...$allowed_extensions,
        ...$default_mimetypes->getExtensions("$type/" . $mime_type),
      ];
    }

    $validators = [
      'FileExtension' => [
        'extensions' => implode(' ', $allowed_extensions),
      ],
      'FileSizeLimit' => [
        'fileLimit' => Environment::getUploadMaxSize(),
      ],
    ];

    // For D9, we need to provide additional validator with extensions.
    if (version_compare(\Drupal::VERSION, '10.0', '<')) {
      $validators['file_validate_extensions'][] = implode(' ', $allowed_extensions);
    }

    return $validators;
  }

}

# Hyperf-file-upload component

Includes the following classes:
 
- Model:
  - File;
  - FileRelations trait.
- Repository:
  - FileRepository.
- Service:
  - AddExternalFileService;
  - AddFileService;
  - ClearUnusedDeletedFilesService;
  - DownloadFileService.

Install:
```shell script
composer require onix-systems-php/hyperf-file-upload
```

Publish config and database migrations:
```shell script
php bin/hyperf.php vendor:publish onix-systems-php/hyperf-file-upload
```

Fill `file_upload` config with file upload configuration, following existing examples.  


Add `$fileRelations` config and `FileRelations` trait to models you want assign files to:
```php
use FileRelations;

public $fileRelations = [
    'avatar' => [
        'limit' => 1,
        'required' => false,
        'mimeTypes' => [image/png', 'image/jpg', 'image/jpeg', 'image/bmp'],
        'presets' => [
            '150x150' => ['fit' => [150, 150]],
            '250x250' => ['fit' => [250, 250]],
        ],
    ],
    'documents' => [
        'limit' => null,
        'required' => false,
        'mimeTypes' => ['application/pdf'],
    ],
];
```

Methods you might need to redefine in `FileRelations` trait:
- `getAuth`: this method should return active user
- `processFileActions`: if you defined new extra actions in config, you'll need to define action's login in this method

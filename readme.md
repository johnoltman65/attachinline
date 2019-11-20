# Attach Inline

Add inline scripts and styles to a Drupal 8 site.

```php
$render['element'] = [
  '#attached' => [
    // Existing Functionality
    'library' => [
      'drupal/drupalSettings'
    ],
    'drupalSettings' => ['module' => $data],

    // New functionality
    'js' => [
      [
        'data' => 'alert("Hi!")',
        'scope' => 'header',
      ],
    ],
    'css' => [
      '.highlight { background-color: yellow; }',
    ],
  ],
];
```

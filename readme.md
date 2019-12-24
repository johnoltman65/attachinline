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
        'data'   => 'alert("Hi!")',
        'scope'  => 'header',
        'group'  => JS_DEFAULT,
        'weight' => -30,
        'dependencies' => ['core/jquery'],
      ],
    ],
    'css' => [
      [
        'data'  => '.highlight { background-color: yellow; }',
        'group' => CSS_THEME,
      ],
    ],
  ],
];
```

<?php
  $browser_language = (string) (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) === true && $_SERVER['HTTP_ACCEPT_LANGUAGE'] !== '') ? strtok(strip_tags($_SERVER['HTTP_ACCEPT_LANGUAGE']), ',') : '';
  $browser_language = (isset($_GET['language']) === true && $_GET['language'] !== '') ? $_GET['language'] : $browser_language;
  $language = (string) '';
  switch (substr($browser_language, 0, 2)) {
    case 'de':
      $language = 'de';
      break;
    case 'en':
      $language = 'en';
      break;
    default:
      $language = 'en';
  }

  $available_languages = (array) [
    (array) [
        'name' => (string) 'English',
        'token' => (string) 'en',
    ],
    (array) [
        'name' => (string) 'Deutsch',
        'token' => (string) 'de',
    ],
  ];
    
  $switch_language = (string) '';
  foreach ($available_languages as $available_language) {
    if ($available_language['token'] !== $language) {
        $switch_language .= '<a href="'.strip_tags($_SERVER['PHP_SELF']).'?language='.$available_language['token'].'" lang="'.$available_language['token'].'" hreflang="'.$available_language['token'].'">'.$available_language['name'].'</a> | ';
    }
  }
  $switch_language = substr($switch_language, 0, -3);

  echo 'BASE v.0.1'
?>

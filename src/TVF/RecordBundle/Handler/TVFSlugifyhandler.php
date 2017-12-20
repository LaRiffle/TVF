<?php

namespace TVF\RecordBundle\Handler;

class TVFSlugifyhandler {
  /*
    Handle the slugification of text anywhere:
    - "Ma Collection d'été" => "ma-collection-dete"
  */
  static public function slugify($text){
    // on sauve les accents classiques
    $text = preg_replace('#Ç#', 'C', $text);
    $text = preg_replace('#ç#', 'c', $text);
    $text = preg_replace('#è|é|ê|ë#', 'e', $text);
    $text = preg_replace('#È|É|Ê|Ë#', 'E', $text);
    $text = preg_replace('#à|á|â|ã|ä|å#', 'a', $text);
    $text = preg_replace('#@|À|Á|Â|Ã|Ä|Å#', 'A', $text);
    $text = preg_replace('#ì|í|î|ï#', 'i', $text);
    $text = preg_replace('#Ì|Í|Î|Ï#', 'I', $text);
    $text = preg_replace('#ð|ò|ó|ô|õ|ö#', 'o', $text);
    $text = preg_replace('#Ò|Ó|Ô|Õ|Ö#', 'O', $text);
    $text = preg_replace('#ù|ú|û|ü#', 'u', $text);
    $text = preg_replace('#Ù|Ú|Û|Ü#', 'U', $text);
    $text = preg_replace('#ý|ÿ#', 'y', $text);
    $text = preg_replace('#Ý#', 'Y', $text);
    // replace non letter or digits by -
    $text = preg_replace('~[^\\pL\d]+~u', '-', $text);
    // trim
    $text = trim($text, '-');
      // transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    // lowercase
    $text = strtolower($text);
    // remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);
    if (empty($text))
      return 'n-a';
    return $text;
  }
}

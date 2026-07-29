<?php

namespace AGR\Helpers;

/**
 * Custom labels for the "used" stat line on a card's tooltip.
 *
 * Cards opt in from their __construct:
 *   $this->usedText = UsedText::get('PLOWS');
 *
 */
class UsedText
{
  public static function get($key)
  {
    switch ($key) {
      case 'SOWS':
        return clienttranslate('Sows');

      case 'IMPROVEMENTS_BUILT':
        return clienttranslate('Improvements built');

      case 'ANIMALS_BOUGHT':
        return clienttranslate('Animals bought');

      case 'ANIMALS_RECEIVED':
        return clienttranslate('Animals received');

      case 'GOODS_BOUGHT':
        return clienttranslate('Goods bought');

      case 'BONUS_ACTIONS':
        return clienttranslate('Actions taken');

      case 'FARMERS_RETURNED_HOME':
        return clienttranslate('Farmers returned home');

      case 'TIMES_BAKED':
        return clienttranslate('Times baked');

      case 'CROPS_MOVED':
        return clienttranslate('Number of crops moved');

      case 'GRAIN_GIVEN':
        return clienttranslate('Grain given');

      case 'CARDS_DISCARDED':
        return clienttranslate('Cards discarded');

      case 'VEGETABLE_CONVERTED':
        return clienttranslate('<VEGETABLE> converted');

      case 'PAIRS_TAKEN':
        return clienttranslate('Pairs taken');

      case 'HARVESTS_SKIPPED':
        return clienttranslate('Harvests skipped');

      case 'FIELDS_HARVESTED':
        return clienttranslate('Fields harvested');

      default:
        throw new \BgaVisibleSystemException('Unknown usedText key: ' . $key);
    }
  }
}

<?php
namespace AGR\Cards\E;

class E60_WorkingGloves extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E60_WorkingGloves';
    $this->name = clienttranslate('Working Gloves');
    $this->deck = 'E';
    $this->author = 'letsdance';
    $this->number = 60;
    $this->category = 'FOOD_-_CONVERT';
    $this->desc = [
      clienttranslate(
        'When you play this card, you get 1 <FOOD>. Each time you pay an occupation cost, you can pay 1 building resource of your choice in place of (up to) 2 <FOOD>.'
      ),
    ];
  }

  public function onBuy($player)
  {
    return $this->gainNode([FOOD => 1]);
  }

  public function onPlayerComputeCostsOccupation($player, &$args)
  {
    if (empty($args['costs']['trades']) || !is_array($args['costs']['trades'])) {
      return;
    }

    $opts = [WOOD, CLAY, REED, STONE];

    $newTrades = [];

    foreach ($args['costs']['trades'] as $trade) {
      // Avoid re-applying to our own generated trades
      $sources = $trade['sources'] ?? [];
      if (is_array($sources) && in_array($this->id, $sources, true)) {
        continue;
      }

      // Only meaningful if there is at least 1 food to replace
      if (!isset($trade[FOOD]) || (int) $trade[FOOD] <= 0) {
        continue;
      }

      // Replace up to 2 food from this cost
      $replace = min(2, (int) $trade[FOOD]);

      foreach ($opts as $res) {
        $alt = $trade;

        // Reduce food by up to 2; remove key if zeroed
        $alt[FOOD] = (int) $alt[FOOD] - $replace;
        if ($alt[FOOD] <= 0) {
          unset($alt[FOOD]);
        }

        // Add exactly 1 of the chosen building resource
        $alt[$res] = 1;

        // This swap can only be used once per occupation (crucial, it was blowing up without this)
        $alt['max'] = 1;

        // Tag provenance so we do not process this again in the same hook chain
        if (!isset($alt['sources']) || !is_array($alt['sources'])) {
          $alt['sources'] = [];
        }
        $alt['sources'][] = $this->id;

        $newTrades[] = $alt;
      }
    }

    if (!empty($newTrades)) {
      // Append new alternatives; UI lets the player pick exactly one
      $args['costs']['trades'] = array_merge($args['costs']['trades'], $newTrades);
    }
  }
}

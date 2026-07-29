<?php
namespace AGR\Cards\C;

use AGR\Helpers\Utils;

class C56_FeedFence extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C56_FeedFence';
    $this->name = clienttranslate('Feed Fence');
    $this->deck = 'C';
    $this->number = 56;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'For each new stable you build, you get 1 <FOOD> —for your last one, get 3 <FOOD>. Each time you build stables, you can build exactly 1 stable for 1 <CLAY> instead of 2 <WOOD>.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->rulings = [
      clienttranslate(
        'You can only build a stable for 1 <CLAY> if that stable would otherwise have cost 2 <WOOD>; it therefore does not combo with cards such as __Stable Cleaner__ (C094).'
      ),
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Stables');
  }

  public function onPlayerAfterStables($player, $event)
  {
    $built = count($event['stables']) + (!empty($event['farmHandStableBuilt']) ? 1 : 0);
    $gains = [FOOD => $built];

    // checking for 4th stable rather than number in reserve to prevent unwanted interaction with A89
    if ($player->board()->countStablesForCards() == 4) {
      $gains[FOOD] = $gains[FOOD] + 2;
    }

    return $this->gainNode($gains);
  }

  public function onPlayerComputeCostsStables($player, &$args)
  {
    $reqCost = false;  
      
    foreach ($args['costs']['trades'] as $trade) {
      if (isset($trade[WOOD]) && $trade[WOOD] == 2) {
        $reqCost = true;
        break;
      }
    }
    
    if ($reqCost) {
      Utils::addCost($args['costs'], [CLAY => 1, 'max' => 1], $this->id);
    }
  }
}

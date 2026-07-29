<?php
namespace AGR\Cards\E;
use AGR\Core\Notifications;
use AGR\Managers\Scores;

class E159_OldMiser extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E159_OldMiser';
    $this->name = clienttranslate('Old Miser');
    $this->deck = 'E';
    $this->author = 'keith';
    $this->number = 159;
    $this->category = FOOD;
    $this->desc = [
      clienttranslate(
        'In the feeding phase of each harvest, each of your people requires 1 less <FOOD>. During scoring, your people are worth 2 points each instead of 3.'
      ),
    ];
    $this->players = '4+';
  }

  public function onBuy($player) 
  {
    Notifications::updateHarvestCosts();
    Scores::compute();
  }

  // Rest of functionality in:
  //    - models/Player.php -> getHarvestCost() 
  //    - managers/Scores.php -> computeFarmers()
}

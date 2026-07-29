<?php
namespace AGR\Cards\E;
use AGR\Helpers\Utils;

class E141_VegetableVendor extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E141_VegetableVendor';
    $this->name = clienttranslate('Vegetable Vendor');
    $this->deck = 'E';
    $this->author = 'ss';
    $this->number = 141;
    $this->category = 'CROPS';
    $this->desc = [
      clienttranslate(
        'Each time you use the __Major Improvement__ or __Vegetable Seeds__ action space, you also get 1 <VEGETABLE> or a __Major or Minor Improvement__ action, respectively.'
      ),
    ];
    $this->players = '3+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'MajorImprovement') ||
      $this->isActionCardEvent($event, 'VegetableSeeds');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    if ($this->isActionCardEvent($event, 'MajorImprovement')){
      return $this->gainNode([VEGETABLE => 1]);
    }
    else{
      return Utils::wrapOptional([
        'action' => IMPROVEMENT,
        'source' => $this->name,
        'args' => [
          'types' => [MINOR, MAJOR],
          'actionType' => 'MajorOrMinor',
        ],
      ]);	  
    }
  }
}
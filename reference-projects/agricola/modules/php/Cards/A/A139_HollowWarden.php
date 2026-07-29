<?php
namespace AGR\Cards\A;
use AGR\Helpers\Utils;

class A139_HollowWarden extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A139_HollowWarden';
    $this->name = clienttranslate('Hollow Warden');
    $this->deck = 'A';
    $this->number = 139;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get a __Major Improvement__ action to build a Fireplace. Each time you use the __Hollow__ accumulation space, you also get 1 <FOOD>.'
      ),
    ];
    $this->players = '3+';
    $this->isArtifexOrBubulcus = true;
  }

  public function onBuy($player)
  {
    return Utils::wrapOptional([
      'action' => IMPROVEMENT,
      'args' => [
        'types' => [MAJOR],
        'allowedPurchases' => ['Major_Fireplace1', 'Major_Fireplace2', 'A60_OrientalFireplace'],
      ],      
    ]);
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'Hollow');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    return $this->gainNode([FOOD => 1]);
  }
}

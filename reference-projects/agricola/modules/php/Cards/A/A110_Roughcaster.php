<?php
namespace AGR\Cards\A;

class A110_Roughcaster extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A110_Roughcaster';
    $this->name = clienttranslate('Roughcaster');
    $this->deck = 'A';
    $this->number = 110;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you build at least 1 clay room or renovate your house from clay to stone, you also get 3 <FOOD>.'
      ),
    ];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    return ($this->isActionEvent($event, 'Construct') && $event['roomType'] == 'roomClay') ||
      ($this->isActionEvent($event, 'Renovation') &&
        $event['oldRoomType'] == 'roomClay' &&
        $event['newRoomType'] == 'roomStone');
  }

  protected function getReward($player)
  {
    return $this->gainNode([FOOD => 3]);
  }

  public function onPlayerAfterRenovation($player, $event)
  {
    return $this->getReward($player);
  }

  public function onPlayerAfterConstruct($player, $event)
  {
    return $this->getReward($player);
  }
}

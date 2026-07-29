<?php
namespace AGR\Cards\D;

class D160_Midwife extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D160_Midwife';
    $this->name = clienttranslate('Midwife');
    $this->deck = 'D';
    $this->number = 160;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time another player uses the first person they place in a round to take a __Family Growth__ action, you get 1 <GRAIN> from the general supply.'
      ),
    ];
    $this->players = '4+';
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'WishChildren', 'opponent');
  }

  public function onOpponentAfterWishChildren($player, $event)
  {
    if ($player->countPlacedFarmers() == 1) {
      return $this->gainNode([GRAIN => 1]);
    }
  }
}

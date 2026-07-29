<?php
namespace AGR\Cards\C;

class C164_GermanHeathKeeper extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C164_GermanHeathKeeper';
    $this->name = clienttranslate('German Heath Keeper');
    $this->deck = 'C';
    $this->number = 164;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time any player (including you) uses the __Pig Market__ accumulation space, you get 1 <SHEEP> from the general supply.'
      ),
    ];
    $this->players = '4+';
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'PigMarket', null);
  }
  
  public function onPlayerPlaceFarmer($player, $event)
  {
    return $this->gainNode([SHEEP => 1]);
  }  
  
  public function onOpponentPlaceFarmer($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'pId' => $this->pId,
      'childs' => [
        [
          'type' => \NODE_SEQ,
          'optional' => true,
          'forceConfirmation' => true,
          'pId' => $this->pId,
          'childs' => [
            $this->gainNode([SHEEP => 1]),
          ],
        ],
      ],
    ];
  }  
}

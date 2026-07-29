<?php
namespace AGR\Cards\A;

class A154_Paymaster extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A154_Paymaster';
    $this->name = clienttranslate('Paymaster');
    $this->deck = 'A';
    $this->number = 154;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time another player uses a food accumulation space, you can give them 1 <GRAIN> from your supply to get 1 bonus <SCORE>.'
      ),
    ];
    $this->players = '4+';
    $this->extraVp = true;
    $this->isArtifexOrBubulcus = true;
    $this->bannedWeak3or4p = true;
  }

  public function isListeningTo($event)
  {  
    return $this->isCollectEvent($event, FOOD, false, 'opponent');
  }
  
  public function onOpponentAfterCollect($player, $event)
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
            $this->payNode([GRAIN => 1], null, 1, $player->getId()),
            $this->gainNode([SCORE => 1]),
          ],
        ],
      ],
    ];
  }
}

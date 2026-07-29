<?php
namespace AGR\Cards\C;
use AGR\Managers\ActionCards;
use AGR\Managers\Meeples;
use AGR\Helpers\Utils;
use AGR\Core\Notifications;

class C156_HoofCaregiver extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C156_HoofCaregiver';
    $this->name = clienttranslate('Hoof Caregiver');
    $this->deck = 'C';
    $this->number = 156;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Immediately add 1 <CATTLE> from the general supply to the __Cattle Market__ accumulation space. Afterward, for each cattle on __Cattle Market__, you get 1 <GRAIN> plus 1 <FOOD>.'
      ),
    ];
    $this->players = '4+';
    $this->isCorbariusOrDulcinaria = true;

    $this->rulings = [
      clienttranslate('For example, if afterwards there are 2 <COW> on the space, you receive 2 <GRAIN> and 2 <FOOD>.'),
    ];
  }

  public function onBuy($player)
  {
    $cards = ActionCards::getVisible($player)->getIds();
    $revealed = in_array('ActionCattleMarket', $cards);

    if ($revealed) {
      $n = Meeples::getResourcesOnCard('ActionCattleMarket', null, CATTLE)->count() + 1;

      return [
        'type' => NODE_SEQ,
        'childs' => [
          [
            'action' => SPECIAL_EFFECT,
            'args' => [
              'cardId' => $this->id,
              'method' => 'placeCattle',
              'args' => ['ActionCattleMarket'],
            ]
          ],
          $this->gainNode([GRAIN => $n, FOOD => $n]),
        ]
      ];
    }
  }
  
  public function placeCattle($actionCardId)
  {
    $resourceIds = Meeples::createResourceOnCard(CATTLE, $actionCardId, 1);
    Notifications::accumulate(Meeples::getMany($resourceIds), true);
  }  
}

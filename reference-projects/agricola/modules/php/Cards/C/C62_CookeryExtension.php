<?php
namespace AGR\Cards\C;

use AGR\Core\Globals;
use AGR\Helpers\Utils;
use AGR\Helpers\CardRulings;

class C62_CookeryExtension extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C62_CookeryExtension';
    $this->name = clienttranslate('Cookery Extension');
    $this->deck = 'C';
    $this->number = 62;
    $this->isCorbariusOrDulcinaria = true;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each harvest, you can use each of your cooking improvements once to get double the amount of <FOOD> for 1 animal or <VEGETABLE>.'
      ),
    ];
    $this->cost = [
      CLAY => '2',
    ];

    $this->rulings = CardRulings::fromKeys([
      'MUST_USE_EXCHANGE_WINDOW',
    ]);
  }

  public function getExchanges()
  {
    $exchanges = parent::getExchanges();

    $player = $this->getPlayer();
    if ($player === null) {
      return $exchanges;
    }

    // Identify all cooking improvements the player has in play.
    $cookeriesById = [];
    foreach ([MAJOR, MINOR] as $type) {
      foreach ($player->getCards($type, true) as $card) {
        if ($card->canCook()) {
          $cookeriesById[$card->id] = $card;
        }
      }
    }
    $cookeries = array_values($cookeriesById);

    // Derive doubled harvest-only exchanges from each cookery's anytime cook conversions.
    $validFrom = [VEGETABLE, SHEEP, PIG, CATTLE];

    foreach ($cookeries as $cookery) {
      // Shared flag per cookery instance, so you can use exactly one option per cookery each harvest.
      $flag = $cookery->id;

      $source = $cookery->getName() . ' (x2)';

      foreach ($cookery->getExchanges() as $exchange) {
        if (!is_null($exchange['triggers'])) {
          continue;
        }

        $fromRes = array_keys($exchange['from'])[0];
        $fromQty = $exchange['from'][$fromRes];
        if ($fromQty != 1 || !in_array($fromRes, $validFrom)) {
          continue;
        }

        $to = $exchange['to'];
        $to[FOOD] = 2 * $to[FOOD];

        $exchanges[] = [
          'source' => $source,
          'flag' => $flag,
          'triggers' => [HARVEST],
          'max' => 1,
          'from' => [$fromRes => 1],
          'to' => $to,
        ];
      }
    }

    // Ensure derived exchanges respect per-harvest "used" flags
    if (Globals::isHarvest()) {
      $flags = Globals::getExchangeFlags();
      Utils::filter($exchanges, function ($exchange) use ($flags) {
        return is_null($exchange['flag']) || !in_array($exchange['flag'], $flags);
      });
    }

    return $exchanges;
  }

}

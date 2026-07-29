<?php

namespace AGR\Helpers;

class CardRulings
{
  /**
   * @param string[] $keys
   * @return array
   */
  public static function fromKeys($keys)
  {
    $out = [];
    foreach ($keys as $key) {
      foreach (self::template($key) as $line) {
        if ($line !== '') {
          $out[] = $line;
        }
      }
    }
    return $out;
  }

  /**
   * @param string $key
   * @return array
   */
  private static function template($key)
  {
    switch ($key) {
      case 'ANIMALS_MUST_BE_ACCOMMODATED':
        return [clienttranslate('Animals must be accommodated on your farmyard before being used for this effect.')];

      case 'BOTH_MINOR_AND_MAJOR':
        return [clienttranslate('This card counts as either 1 minor improvement or 1 major improvement, whichever is most convenient when considering another effect. But it never counts as 2 improvements at once.')];

      case 'CANT_PAY_SHIFTING_CULTIVATOR':
        return [clienttranslate('<FOOD> obtained from this effect may not be used to pay for __Shifting Cultivator__ (A091).')];

      case 'CAN_SOW_MULTIPLE':
        return [clienttranslate('You may sow multiple stacks at once with cards that allow you to sow in "exactly one field" such as __Chief Forester__ (A115) or __Furrows__ (D003).')];

      case 'EXACTLY_ONE_GROWTH_ACTION':
        return [clienttranslate('This card allows exactly 1 growth action regardless of how many rooms are built.')];

      case 'HAPPENS_AFTER_COTTAGER':
        return [clienttranslate('Resources gained from this card cannot be used to pay for the effect of __Cottager__ (B087).')];

      case 'JUNIOR_ARTIST_JOB_CONTRACT':
        return [clienttranslate('You cannot use the effects of __Junior Artist__ (B152) and __Job Contract__ (C023) with the same farmer.')];

      case 'LANDS_ON_SECOND_SPACE':
        return [clienttranslate('The person ends on the second action space used.')];

      case 'MEETING_PLACE_NEVER_REUSED':
        return [clienttranslate('No card, including this one, can allow you to use __Meeting Place__ if it is already occupied.')];

      case 'MUST_BAKE_NORMALLY':
        return [clienttranslate('You must also bake normally in order to make this exchange.')];

      case 'MUST_USE_EXCHANGE_WINDOW':
        return [clienttranslate('This exchange can be performed in the Exchange Center window.')];

      case 'NEGATED_BY_MILKING_PLACE':
        return [clienttranslate('This effect is negated by __Milking Place__ (D012).')];

      case 'NEVER_FIVE_FARMERS':
        return [clienttranslate('If you remove a farmer from play because of this card, you can never have 5 family members.')];

      case 'NEWBORN_ANIMAL_NEEDS_SPACE':
        return [clienttranslate('You must be able to accommodate each newborn animal in order to get it.')];

      case 'OFFTURN_STABLES_DONT_TRIGGER':
        return [clienttranslate('Stables not built during a player\'s turn, for example with __Stable Planner__ (A089) or __Groom__ (B089), do not trigger this card.')];

      case 'ONE_JUMP_PER_TURN':
        return [clienttranslate('The “jump” to a second action space may only be done once per turn.')];

      case 'ONLY_PLAYED_BY_OWNER':
        return [clienttranslate('This only refers to cards played by the owner.')];

      case 'PARTIAL_ACCOMMODATION_NEUTRAL':
        return [clienttranslate('Taking, but not fully accommodating, the animals neither triggers nor voids the effect.')];

      case 'PRIVATE_FIELD_PHASE':
        return [clienttranslate('This is not a harvest, so cards that trigger "each harvest" or "in the field phase of each harvest" do not combo with this card.')];

      case 'ROOM_FROM_CARDS_NOT_COUNTED':
        return [clienttranslate('Cards that provide room for a person do not count for this effect.')];

      case 'SCHOLAR_IMMEDIATE_USE':
        return [clienttranslate('If played by __Scholar__ (B097), the effect can be used immediately.')];

      case 'TRIGGERS_BEFORE_ACTION_SPACE':
        return [clienttranslate('This card effect happens when you land on the action space, before the effect of the space.')];

      case 'UPDATED_DIGITAL':
        return [clienttranslate('This card text was updated for the digital implementation of the game.')];

      case 'WOOD_STONE_NOT_CROPS':
        return [clienttranslate('<WOOD> and <STONE> are not crops and cannot be acted on by this card.')];

      case 'ILLUSIONIST_LANTERN_HOUSE':
        return [clienttranslate('You cannot discard cards with __Illusionist__ (B146) if you have played __Lantern House__ (C035).')];

      case 'TURN_MEANS_PERSON_ACTION':
        return [clienttranslate('A "turn" begins when a player receives control to place a person, and ends when all resulting actions are complete. Actions taken during the Preparation, Returning Home, or Harvest phases are not taken on any turn.')];

      default:
        throw new \BgaVisibleSystemException('Unknown ruling template: ' . $key);
    }
  }
}

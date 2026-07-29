<?php
namespace AGR\Models;
use AGR\Managers\Players;

/*
 * AbstractCard: all utility functions concerning a card
 */

class AbstractCard extends \AGR\Helpers\DB_Manager implements \JsonSerializable
{
  protected static $table = 'cards';
  protected static $primary = 'card_id';

  /*
   * STATIC INFORMATIONS
   */
  protected $name = '';
  protected $tooltip = [];
  protected $text = []; // Text of the card, needed for front
  protected $type = null;
  protected $deck = 'base';
  protected $rulings = [];

  /*
   * DYNAMIC INFORMATIONS
   */
  protected $id = -1;
  protected $location = '';
  protected $pId = null;
  protected $state = 0;

  public function __construct($row)
  {
    if ($row != null) {
      $this->id = $row['id'];
      $this->location = $row['location'];
      $this->pId = $row['player_id'];
      $this->state = $row['state'];
      $this->extraDatas = json_decode(\stripslashes($row['extra_datas']), true);
      if ($this->pId != null) {
        $this->pId = (int) $this->pId;
      }
    }
  }

  /*
   * Getters
   */
  public function getId()
  {
    return $this->id;
  }
  public function getPId()
  {
    return $this->pId;
  }
  public function getPlayer()
  {
    $pId = $this->getPId();
    return $pId == null ? $pId : Players::get($pId);
  }
  public function getName()
  {
    return $this->name;
  }

  public function getLocation()
  {
    return $this->location;
  }

  public function getState()
  {
    return $this->state;
  }

  public function jsonSerialize()
  {
    $data = [
      'id' => $this->id,
      'pId' => $this->pId,
      'name' => $this->name,
      'location' => $this->location,
      'state' => $this->state,
      'tooltip' => $this->tooltip,
    ];

    if (!empty($this->rulings)) {
      $data['rulings'] = $this->rulings;
    }

    return $data;
  }

  public function getExtraDatas($variable)
  {
    return $this->extraDatas[$variable] ?? null;
  }

  public function setExtraDatas($variable, $value)
  {
    $this->extraDatas[$variable] = $value;
    self::DB()->update(['extra_datas' => \addslashes(\json_encode($this->extraDatas))], $this->id);
  }
}

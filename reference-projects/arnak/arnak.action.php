<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * arnak implementation : © Adam Spanel <adam.spanel@seznam.cz>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * arnak.action.php
 *
 * arnak main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 * 
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/arnak/arnak/myAction.html", ...)
 *
 */
  
  
class action_arnak extends APP_GameAction
{ 
  // Constructor: please do not modify
  public function __default()
  {
    if( self::isArg( 'notifwindow') )
    {
      $this->view = "common_notifwindow";
      $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
    }
    else
    {
      $this->view = "arnak_arnak";
      self::trace( "Complete reinitialization of board game" );
    }
  }
  public function stNextPlayer() {
    $this->game->stNextPlayer();
  }
  public function stNextRound() {
    $this->game->stNextRound();
  }
  public function displayDeck() {
    self::setAjaxMode(); 
    $playerId = self::getArg("playerId", AT_posint, true );
    $this->game->displayDeck($playerId);
    self::ajaxResponse();
  }
  public function displayDiscard() {
    self::setAjaxMode(); 
    $this->game->displayDiscard();
    self::ajaxResponse();
  }
  public function playCard() {
    self::setAjaxMode(); 
    $cardId = self::getArg("cardId", AT_posint, true );
    $arg = base64_decode(self::getArg("arg", AT_base64));
    $this->game->playCard($cardId, $arg);
    self::ajaxResponse();
  }
  public function discardCard() {
    self::setAjaxMode(); 
    $cardId = self::getArg("cardId", AT_posint, true );
    $this->game->clientDiscardCard($cardId);
    self::ajaxResponse();
  }
  public function selectCard() {
    self::setAjaxMode(); 
    $cardId = self::getArg("cardId", AT_posint, true);
    $this->game->selectCard($cardId);
    self::ajaxResponse();
  }
  public function cancelIdolExile() {
    self::setAjaxMode(); 
    $this->game->cancelIdolExile();
    self::ajaxResponse();
  }
  public function cancelExile() {
    self::setAjaxMode(); 
    $this->game->clientExile('cancel');
    self::ajaxResponse();
  }
  public function cancelTravel() {
    self::setAjaxMode(); 
    $this->game->cancelTravel();
    self::ajaxResponse();
  }
  public function cancelEarring() {
    self::setAjaxMode();
    $this->game->cancelEarring();
    self::ajaxResponse();
  }
  public function cancelShellDiscard() {
    self::setAjaxMode();
    $this->game->cancelShellDiscard();
    self::ajaxResponse();
  }
  public function exile() {
    self::setAjaxMode(); 
    $cardId = self::getArg( "cardId", AT_posint, true );
    $this->game->clientExile($cardId);
    self::ajaxResponse();
  }
  public function getFromExile() {
    self::setAjaxMode(); 
    $cardId = self::getArg( "cardId", AT_posint, true );
    $this->game->getFromExile($cardId);
    self::ajaxResponse();
  }
  public function upgrade() {
    self::setAjaxMode(); 
    $type = self::getArg( "type", AT_posint, true );
    $this->game->clientUpgrade($type);
    self::ajaxResponse();
  }
  public function buyCard() {
    self::setAjaxMode(); 
    $cardId = self::getArg( "cardId", AT_posint, true );
    $this->game->buyCard($cardId, false, true, false);
    self::ajaxResponse();
  }
  public function cancelBuy() {
    self::setAjaxMode(); 
    $this->game->cancelBuy();
    self::ajaxResponse();
  }
  public function planeCompass() {
    self::setAjaxMode(); 
    $this->game->planeCompass();
    self::ajaxResponse();
  }
  public function idolBonus() {
    self::setAjaxMode();
    $bonusName = self::getArg("bonusName", AT_alphanum, true);
    $this->game->idolBonus($bonusName);
    self::ajaxResponse();
  }
  public function research() {
    self::setAjaxMode();
    $researchId = self::getArg("researchId", AT_alphanum, true);
    $this->game->clickResearch($researchId);
    self::ajaxResponse();
  }
  public function getTempleTile() {
    self::setAjaxMode();
    $tileNum = self::getArg("tileNum", AT_posint, true);
    $this->game->clickTempleTile($tileNum);
    self::ajaxResponse();
  }
  public function useResearchToken() {
    self::setAjaxMode();
    $tokenId = self::getArg("tokenId", AT_alphanum, true);
    $tokenArg = base64_decode(self::getArg("arg", AT_base64));
    $this->game->useToken($tokenId, $tokenArg);
    self::ajaxResponse();
  }
  public function useAssistant() {
    self::setAjaxMode();
    $assNum = self::getArg("assNum", AT_alphanum, true);
    $arg = self::getArg("assArg", AT_base64);
    $this->game->useAssistant($assNum, $arg);
    self::ajaxResponse();
  }
  public function useGuard() {
    self::setAjaxMode();
    $guardNum = self::getArg("guardNum", AT_alphanum, true);
    $arg = self::getArg("guardArg", AT_base64);
    $this->game->useGuard($guardNum, $arg);
    self::ajaxResponse();
  }
  public function moveToSite() {
    self::setAjaxMode(); 
    $siteId = self::getArg( "siteId", AT_posint, true );
    $movePayment = self::getArg( "movePayment", AT_base64, true );
    $this->game->moveToSite($siteId, $movePayment);
    self::ajaxResponse();
  }
  public function skipArt() {
    self::setAjaxMode(); 
    $this->game->skipArt();
    self::ajaxResponse();
  }
  public function pass() {
    self::setAjaxMode(); 
    $this->game->pass();
    self::ajaxResponse();
  }
  public function confirmKeep() {
    self::setAjaxMode(); 
    $cards = json_decode(base64_decode(self::getArg( "cards", AT_base64, true )));
    $this->game->confirmKeep($cards);
    self::ajaxResponse();
  }
  public function undo() {
    $this->game->undo();
    self::ajaxResponse();
  }
  public function endTurn() {
    $this->game->endTurn();
    self::ajaxResponse();
  }
}


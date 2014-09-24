<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * LeDernierPeuple implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 * 
 * ledernierpeuple.action.php
 *
 * LeDernierPeuple main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/ledernierpeuple/ledernierpeuple/myAction.html", ...)
 *
 */
  
  
  class action_ledernierpeuple extends APP_GameAction
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
            $this->view = "ledernierpeuple_ledernierpeuple";
            self::trace( "Complete reinitialization of board game" );
      }
  	} 
  	
  	// TODO: defines your action entry points there


	/**
	 * Method calls when the player choose a card in the deck
	 */
	public function chooseCard(){
		self::setAjaxMode();     
        $cardId = self::getArg( "cardId", AT_posint, true );
		$playerId = self::getArg( "playerId", AT_posint, true );
        $result = $this->game->chooseCard( $playerId, $cardId );
        self::ajaxResponse();
	}
	
	/**
	 * Method calls when the player use the card he chose
	 */
	public function useCard(){
		self::setAjaxMode();     
        $pawnId = self::getArg( "pawnId", AT_posint, true );
		$tileId = self::getArg( "tileId", AT_posint, true );
		$partial = self::getArg( "partial", AT_bool, true );
        $result = $this->game->movePawn( $pawnId, $tileId, $partial );
        self::ajaxResponse();
	}
	
	/**
	 * Method calls when the player use the card he chose
	 */
	public function skipTurn(){
		self::setAjaxMode();
        $result = $this->game->skipTurn();
        self::ajaxResponse();
	}

    /*
    
    Example:
  	
    public function myAction()
    {
        self::setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $arg1 = self::getArg( "myArgument1", AT_posint, true );
        $arg2 = self::getArg( "myArgument2", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->myAction( $arg1, $arg2 );

        self::ajaxResponse( );
    }
    
    */

  }
  


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
 * states.inc.php
 *
 * LeDernierPeuple game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!

$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => clienttranslate("Game setup"),
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array( "" => 2 )
    ),
    
	
	/**
	 * *****************************************
	 * 	Step 1 choose a power card
	 * ***************************************** 
	 */
	2 => array(
			"name" => "checkHasPowerCard",
    		"action" => "stCheckHasPowerCard",
    		"type" => "game",
    		"transitions" => array( "true" => 3, "false" => 10)
	),
	
	
	3 => array(
			"name" => "choosePowerCard",
    		"description" => clienttranslate('${actplayer} can choose a Power card'),
    		"descriptionmyturn" => clienttranslate('${you} can choose a Power card'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "choosePowerCard", "skipPowerCard" ),
    		"transitions" => array( "powerCardChosen" => 9, "skipPowerCard" => 10, "chooseTargetPlayer" => 4, 
									"chooseSwitchedPawns" => 5)
	),
	
	4 => array(
			"name" => "chooseTargetPlayer",
    		"description" => clienttranslate('${actplayer} must choose a target'),
    		"descriptionmyturn" => clienttranslate('${you} must choose a target by clicking a pawn'),
    		"type" => "activeplayer",
    		"args" => "argPossibleTarget",
    		"possibleactions" => array( "chooseTarget" ),
    		"transitions" => array( "targetChosen" => 9)
	),
	
	5 => array(
			"name" => "chooseSwitchedPawns",
    		"description" => clienttranslate('${actplayer} must choose two pawns to switch'),
    		"descriptionmyturn" => clienttranslate('${you} must choose two pawns to switch'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "chooseSwitchedPawns" ),
    		"transitions" => array( "pawnsSwitched" => 9)
	),
	
	9 => array(
			"name" => "endPowerCard",
    		"action" => "stEndPowerCard",
    		"type" => "game",
    		"transitions" => array("end" => 10)
	),
	
    
    
	/**
	 * *****************************************
	 * 	Step 2 choose a move card
	 * ***************************************** 
	 */

    10 => array(
    		"name" => "chooseCard",
    		"description" => clienttranslate('${actplayer} must choose a card'),
    		"descriptionmyturn" => clienttranslate('${you} must choose a card'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "chooseCard", "skipTurn" ),
    		"transitions" => array( "cardChosen" => 11, "skipTurn" => 20)
    ),
    
    11 => array(
			"name" => "useCard",
    		"description" => clienttranslate('${actplayer} must use his card'),
    		"descriptionmyturn" => clienttranslate('${you} must choose a pawn to move'),
    		"args" => "argPossibleMoves",
    		"type" => "activeplayer",
    		"possibleactions" => array( "useCard" ),
    		"transitions" => array( "cardUsed" => 13, "chooseCombination"=>12, "doubleMove" => 11)
	),
	
	12 => array(
			"name" => "chooseCombination",
    		"description" => clienttranslate('${actplayer} must choose a pawn with wich make the attack'),
    		"descriptionmyturn" => clienttranslate('${you} must choose a pawn with wich make the attack'),
    		"args" => "argPossibleCombination",
    		"type" => "activeplayer",
    		"possibleactions" => array( "combinationChosen" ),
    		"transitions" => array( "combinationChosen" => 13)
	),
	
	
	13 => array(
			"name" => "drawCard",
    		"action" => "stDrawCard",
    		"type" => "game",
    		"transitions" => array( "cardDrawed" => 14)
	),
	
	
	14 => array(
			"name" => "endMoveCard",
    		"action" => "stEndMoveCard",
    		"type" => "game",
    		"transitions" => array("end" => 20, "speedPowerUsed" => 2)
	),

	
	
	/**
	 * *****************************************
	 * 	Step 3 next player
	 * ***************************************** 
	 */	
	20 => array(
			"name" => "nextPlayer",
    		"action" => "stNextPlayer",
    		"type" => "game",
    		"updateGameProgression" => true,
    		"transitions" => array( "next" => 2, "victory" => 99)
	),
	
/*
    Examples:
    
    2 => array(
        "name" => "nextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stNextPlayer",
        "updateGameProgression" => true,   
        "transitions" => array( "endGame" => 99, "nextPlayer" => 10 )
    ),
    
    10 => array(
        "name" => "playerTurn",
        "description" => clienttranslate('${actplayer} must play a card or pass'),
        "descriptionmyturn" => clienttranslate('${you} must play a card or pass'),
        "type" => "activeplayer",
        "possibleactions" => array( "playCard", "pass" ),
        "transitions" => array( "playCard" => 2, "pass" => 2 )
    ), 

*/    
   
    // Final state.
    // Please do not modify.
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);



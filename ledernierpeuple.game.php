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
  * ledernierpeuple.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );

require_once('init_db.php');


class LeDernierPeuple extends Table
{
	function LeDernierPeuple( )
	{
        	
 
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();self::initGameStateLabels( array( 
            //    "my_first_global_variable" => 10,
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        ) );
        
	}
	
    protected function getGameName( )
    {
        return "Le dernier peuple";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {    
        $sql = "DELETE FROM player WHERE 1 ";
        self::DbQuery( $sql ); 

        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $default_colors = array( "0065AE", "888888", "D40000", "009E48", "D76000", "040404" );

 
        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar, player_score) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."',0)";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        // Init global values with their initial values
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );
        
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // TODO: setup the initial game situation here
        
        //init the tiles
        $request = getRequestInitTiles($players);
        self::DbQuery( $request );
        
		//init the pawns
        $request = getRequestInitPawns($players);
	   	self::DbQuery( $request );
		
		
		//init the cards
        $request = getRequestInitCards($players);
	   	self::DbQuery( $request );
		
       

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

	
	

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array( 'players' => array() );
    
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
  
        // TODO: Gather all information about current game situation (visible by player $current_player_id).
        
        //get the tiles
        $sql = "SELECT * FROM tile order by id";
        $result['tiles'] = self::getCollectionFromDb( $sql );
		
		//get the pawns
        $sql = "SELECT * FROM pawn order by id";
        $result['pawns'] = self::getCollectionFromDb( $sql );
		
		//get the cards of the player
        $sql = "SELECT cardOrder,moveType,moveShift,teleportTile FROM card where location = '".$current_player_id."' order by cardOrder";
        $result["cards"] = self::getCollectionFromDb( $sql );
		
  
        return $result;
    }



	public function chooseCard($playerId, $cardId){
			
		$playerName = self::getActivePlayerName();
		
		// Check that this player is active and that this action is possible at this moment
        self::checkAction( 'chooseCard' );  
		
		//udpate the card to indicates it was chosen
		$sql = "UPDATE card set chosen=1 where cardOrder=".$cardId;
		
		self::DbQuery( $sql );
		
		//notify the player
		self::notifyAllPlayers( "playDisc", clienttranslate( '${playerName} has chosen a card' ), array(
                'playerName' => $playerName
            ) );
			
		//the card is chosen, we can move to the next state
		$this->gamestate->nextState( 'cardChosen' );
	}
	
	
	public function skipTurn(){
		$playerId = self::getActivePlayerId();
		$playerName = self::getActivePlayerName();
		
		self::debug( "Player ".$playerId." skip his turn" );
		
		$sql="select count(*) from card where location='".$playerId."'";
		$nbCardInHand = self::getUniqueValueFromDB( $sql );
		
		//compute how many card the player can get
		$nbCardMaxInHand = 7;
		if($nbCardInHand >= $nbCardMaxInHand){
			$nbCardToDraw = 0;
		}
		else if ($nbCardInHand + 1 >= $nbCardMaxInHand){
			$nbCardToDraw = 1;
		}
		else{
			$nbCardToDraw = 2;
		}
		
		
		$this->log('${playerName} skip his turn and draw ${nbCard} cards', 
					array("playerName"=>$playerName, "nbCard" => $nbCardToDraw));
					
		$this->drawCard($nbCardToDraw, $playerId);
		
		$this->gamestate->nextState( 'skipTurn' );
	}
	
	/**
	 * Check if the move made by the player is correct
	 */
	private function checkPossibleMove($possibleMoves, $tileId, $pawnId){
		if($possibleMoves[$pawnId] == null){
			return FALSE;
		}
		foreach ($possibleMoves[$pawnId] as $possibleMove) {
			if($possibleMove["tileId"] == $tileId){
				return TRUE;
			}
		}
		return FALSE;
	}
	
	/**
	 * Method calls when a player move one of his pawn on a tile
	 */
	public function movePawn($pawnId, $tileId, $partial){
		// Check that this player is active and that this action is possible at this moment
        self::checkAction( 'useCard' );  
		
		$playerId = self::getActivePlayerId();
		$playerName = self::getActivePlayerName();
		
		self::debug( "Player ".$playerId." try to move the pawn num ".$pawnId." on the tile ".$tileId );
		
		$args = $this->argPossibleMoves();
		$possibleMoves = $args["possibleMoves"];
		
		
		if(!$this->checkPossibleMove($possibleMoves, $tileId, $pawnId)){
			throw new feException("Impossible move");
		}
		
		//put a parameter to remind this is a partial move
		if($partial){
			$this->createPrivateParameter("doubleMove", "TRUE");
			$this->createPrivateParameter("doubleMovePawnId", $pawnId);
		}
		
		//notify the player
		self::notifyAllPlayers( "movePawn", clienttranslate( '${playerName} move a pawn on tile ${tileId}' ), array(
                'playerId' => $playerId,
                'playerName' => $playerName,
                'tileId' => $tileId,
                'pawnId' => $pawnId
            ) );
		
		$sql = "select count(*) as nbTiles from tile";
		$result = self::getObjectFromDb( $sql );
		$nbTiles = $result["nbTiles"];
		
		//variable to save the pawns we have to teleport at the end of the action
		$teleportPawns = array();
		
		//flag that indicates an attack has been launched
		$attackFlag = FALSE;
		//flag that indicates the player need to choose a combination to attack
		$chooseCombinationFlag = FALSE;
		
		//if we are next to a species tile, it might be an attack
		if($tileId  % 4 == 2 || $tileId % 4 == 0){
			self::debug( "Potential attack" );
			$otherTileId = -1;
			$attackedTile = -1;
			//we need to check if another pawn is present on the tile at the other side of the species tile
			if($tileId  % 4 == 2) {
				//id of the tile on the other side 
				$otherTileId = $this->mod(($tileId - 3),  $nbTiles) + 1;
				//id of the attacked tile
				$attackedTile = $this->mod(($tileId - 2),  $nbTiles) + 1;
			}
			else if($tileId % 4 == 0) {
				//id of the tile on the other side 
				$otherTileId = $this->mod(($tileId + 2), $nbTiles);
				//id of the attacked tile
				$attackedTile = $this->mod(($tileId + 1), $nbTiles);
			}
			
			//get the id of the player's pawn
			$sql = "select player_name,player_id from pawn p, player pl where p.playerId=pl.player_id and p.id=".$pawnId;
			$result3 = self::getObjectFromDb($sql);
			$pawnPlayerId = $result3["player_id"];
			$pawnPlayerName = $result3["player_name"]; 
			
			//get the name and id of the player who might be attacked
			$sql = "select player_name,player_id from player p, tile t where t.speciesPlayerId=p.player_id and t.id=".$attackedTile;
			$result2 = self::getObjectFromDb($sql);
			$playerNameAttacked = $result2["player_name"];
			$playerIdAttacked = $result2["player_id"];
			
			//if the pawn belong to the player who is attacked and he has his second pawn on the other tile
			//he earns 2 points
			if($playerIdAttacked == $pawnPlayerId){
				//check in base if another pawn which belong to the same player is on the tile
				$sql = "select * from pawn where tileId=".$otherTileId." and playerId = ".$playerIdAttacked.' and id != '.$pawnId;
				$result = self::getObjectFromDb( $sql );
				
				//if there is a result, the player earns 2 points
				if($result != null){
					$sql="update player set player_score=player_score+2 where player_id=".$pawnPlayerId;
					self::DbQuery( $sql );
					
					$this->log('${playerName} earns 2 points', array("playerName"=>$pawnPlayerName));
					
					//notify the new scores
					$newScores = self::getCollectionFromDb( "SELECT player_id, player_score FROM player", true );
			        self::notifyAllPlayers( "newScores", "", array(
			            "scores" => $newScores
			        ) );
					
					//we add the two pawns to be teleported after the move
					$teleportPawns[] = $pawnId;
					$teleportPawns[] = $result["id"];
					
					$attackFlag = TRUE;
				}
			}
			else{
				//check in base if another pawn that don't belong to the attacked player is on the tile
				$sql = "select * from pawn where tileId=".$otherTileId." and playerId != ".$playerIdAttacked.' and id != '.$pawnId;
				$result = self::getCollectionFromDb( $sql );
				
				if($result != null && count($result) > 0 && $pawnPlayerId != $playerIdAttacked){
					
					self::debug( "Attack player ".$playerNameAttacked );
					
					//if there are just 1 result, no choice, we can attack directly
					if(count($result) == 1){
						$otherPawn = reset($result);
						
						self::debug( "Attack with player ".$otherPawn["playerId"] );
						
						//the player attacks with his two pawns
						if($otherPawn["playerId"] == $pawnPlayerId){
								
							$this->attack(array("id"=>$playerId, "name"=>$playerName),
										  array("id"=>$pawnPlayerId, "name"=>$pawnPlayerName),  
										  array("id"=>$playerIdAttacked, "name"=>$playerNameAttacked));
						}
						else {
								
							//we get the name of the player who helps to attack
							$sql = "select player_name from player p where p.player_id=".$otherPawn["playerId"];
							$result = self::getObjectFromDb($sql);
							$playerNameHelper = $result["player_name"];
							
							$this->attack(array("id"=>$playerId, "name"=>$playerName), 
										  array("id"=>$pawnPlayerId, "name"=>$pawnPlayerName),
										  array("id"=>$playerIdAttacked, "name"=>$playerNameAttacked),
										  array("id"=>$otherPawn["playerId"], "name"=>$playerNameHelper));
	
						}
						
						//we add the two pawns to be teleported after the move
						$teleportPawns[] = $pawnId;
						$teleportPawns[] = $otherPawn["id"];
						
						$attackFlag = TRUE;
					}
					//we have more than one pawn on the other tile
					else{
						//we need to check if the player who made the attack owns one of the pawns on the other tile
						//if he does, he automatically attacks with it
						$attack = FALSE;
						foreach ($result as $pawn) {
							if($pawn["playerId"] == $playerId){
								$this->attack(array("id"=>$playerId, "name"=>$playerName), 
											  array("id"=>$pawnPlayerId, "name"=>$pawnPlayerName),
										  	  array("id"=>$playerIdAttacked, "name"=>$playerNameAttacked));
								$attack = TRUE;
								
								//we add the two pawns to be teleported after the move
								$teleportPawns[] = $pawnId;
								$teleportPawns[] = $pawn["id"];
								
								$attackFlag = TRUE;
								
								break;
							}
						}
						//if the pawns on the other tile does'nt belong to the active player, he has to make a choice
						if(!$attack){
							$chooseCombinationFlag = TRUE;
						}
					}
				}
			}
		}

		
		//udpate the pawn position
		$sql = "UPDATE pawn set tileId=".$tileId." where id=".$pawnId;
		self::DbQuery( $sql );
		
		//teleport the pawns after the attack
		if(count($teleportPawns) > 0){
			$this->teleportAfterAttack($teleportPawns);
		}
		//update the card status to put it in the trash
		if(!$partial || $attackFlag || $chooseCombinationFlag){
			$sql = "UPDATE card set location='TRASH' where chosen=1 and location=".$playerId;
			self::DbQuery( $sql );
		}
		
		
		//determine the next state
		if($attackFlag){
			//if the player attacked, we automatically move on cardUsed state even if it's a double move
			$nextState = "cardUsed";
		}
		else{
			//the player has to make a choice to attack
			if($chooseCombinationFlag){
				$nextState = "chooseCombination";
			}
			else{
				//otherwise (it's not an attack nor a choice of attack), 
				//we move on doubleMove if the card allow it or we move to cardUsed
				$nextState = $partial ? "doubleMove" : "cardUsed";
			}
		}
		
		// Finally, go to the next state
        $this->gamestate->nextState( $nextState );
		
	}

	/**
	 * Launch an attack
	 * $playerAttack : The active player who do the action
	 * $pawnPlayer : The player who own the pawn that trigger the attack
	 * $playerAttacked : The player being attacked
	 * $playerHelper : The player who own the pawn at the other side of the attack if this player is not the same player as $pawnPlayer
	 */
	function attack($playerAttack, $pawnPlayer, $playerAttacked, $playerHelper = NULL){
		//nb points earn by the pawnPlayer
		$pointsEarn = 0;
		//nb points earn by the helper
		$helperPointsEarn = 0;
		
		if(is_null($playerHelper)){
			$pointsEarn = 3;
		}
		else {
				
			//if the active player moves another player's pawn and the attack is launched with the pawn 
			//of the active player, the active player earns 2 points and the other player earns 1 point 
			if($playerAttack["id"] == $playerHelper["id"]){
				$helperPointsEarn = 2;
				$pointsEarn = 1;
			}
			//in other case, the helper earns 1 points and the launcher earns 2 points
			else{
				$helperPointsEarn = 1;
				$pointsEarn = 2;
			}
			
			//update the score of the helper player
			$sql="update player set player_score=player_score+".$helperPointsEarn." where player_id=".$playerHelper["id"];
			self::DbQuery( $sql );
			
		}
		
		//update the score of the active player who made the attack
		$sql="update player set player_score=player_score+".$pointsEarn." where player_id=".$pawnPlayer["id"];
		self::DbQuery( $sql );
		
		//update the score of the attacked player
		$sql="update player set player_score=player_score-1 where player_score >= 1 and player_id=".$playerAttacked["id"];
		self::DbQuery( $sql );
		
		//LOGS
		if(is_null($playerHelper)){
			//the active player launch the attack with one of his pawn
			if($pawnPlayer["id"] == $playerAttack["id"]){
				$this->log('${playerName} attacks ${playerNameAttacked}', array(
					"playerName" => $playerAttack["name"],"playerNameAttacked" => $playerAttacked["name"]));	
			}
			//the active player launch the attack with the pawn of another player  
			else {
				$this->log('${playerName} attacks with the pawn of ${pawnPlayerName} on ${playerNameAttacked}', array(
					"playerName" => $playerAttack["name"],"playerNameAttacked" => $playerAttacked["name"],
					"pawnPlayerName" => $pawnPlayer["name"]));	
			}
		}
		else{
			//the active player launch the attack with one of his pawn with the help of another player
			if($pawnPlayer["id"] == $playerAttack["id"]){
				$this->log('${playerName} attacks ${playerNameAttacked} with the help of ${playerNameHelper}', array(
					"playerName" => $playerAttack["name"],"playerNameAttacked" => $playerAttacked["name"],
					"playerNameHelper" => $playerHelper["name"]));
			}
			//the active player launch the attack with the pawn of another player with the help of another player
			else {
				$this->log('${playerName} attacks ${playerNameAttacked} with the pawn of ${pawnPlayerName} helped by ${playerNameHelper}', array(
					"playerName" => $playerAttack["name"],"playerNameAttacked" => $playerAttacked["name"],
					"playerNameHelper" => $playerHelper["name"], "pawnPlayerName" => $pawnPlayer["name"]));
			}
		}
					
    	$this->log( '${pawnPlayerName} earns '.$pointsEarn.' points', array(
            "pawnPlayerName" => $pawnPlayer["name"] ));
		
		if(!is_null($playerHelper)){
			$this->log( '${playerNameHelper} earns '.$helperPointsEarn.' points', array(
            	"playerNameHelper" => $playerHelper["name"] ));	
		}
		
    	$this->log('${playerNameAttacked} loses 1 point', array(
            "playerNameAttacked" => $playerAttacked["name"] ));
            
        
		//notify the new scores
		$newScores = self::getCollectionFromDb( "SELECT player_id, player_score FROM player", true );
        self::notifyAllPlayers( "newScores", "", array(
            "scores" => $newScores
        ) );
	}


	/**
	 * Teleport the pawns after the attack
	 */
	function teleportAfterAttack($pawns){
		//if there are pawns to teleport after an attack, we teleport it
		if(count($pawns) > 0){
				
			//get random cards to determine new pawns position	
			$newCards = $this->getRandomCardsInDeck(count($pawns));
			
			$cardIds = array();
			$teleport = array();
			
			for ($i=0; $i < count($newCards); $i++) { 
				$card = $newCards[$i];
				$pawn = $pawns[$i];
				
				$sql = "update pawn set tileId=".$card["teleportTile"]." where id=".$pawn["id"];
				self::DbQuery( $sql );
				
				$teleport[] = array("pawnId"=>$pawn["id"], "tileId" => $card["teleportTile"]);
				$cardIds[] = $card["cardOrder"];
				
				$this->log('The pawn ${pawnId} is teleported on tile ${tileId}', 
							array('pawnId' => $pawn["id"], "tileId" => $card["teleportTile"]));
			}
			
			//put the cards in the trash
			$sql = "update card set location='TRASH' where cardOrder IN (".implode( $cardIds, ',' ).")";
			self::DbQuery( $sql );
			
		 	self::notifyAllPlayers( "teleportAfterMove","",
		 		 array(
		 		 		"teleport" => $teleport
			        ));
		}
	}
	
	/**
	 * Get $nbCard cards from the deck
	 */
	function getRandomCardsInDeck($nbCard){
		//we check if we have enough card in the deck
		$sql = "select count(*) from card where location='DECK'";
		
		$nbCardAvailable = self::getUniqueValueFromDB( $sql );
		
		//we don't have enough card, we move the cards from the trash to the deck
		if($nbCardAvailable < $nbCard){
			$sql = "update card set location='DECK',chosen=0 where location='TRASH'";
			self::DbQuery( $sql );	
		}
		
		//recheck if we have enough card in the deck after the change
		$sql = "select count(*) from card where location='DECK'";
		$nbCardAvailable = self::getUniqueValueFromDB( $sql );
		
		if($nbCardAvailable < $nbCard){
			//we don't have enough remaining cards, so we get the last ones
			$nbCard = $nbCardAvailable;
		}
		
		//get $nbCard random cards
		$sql = "select * from card where location='DECK' order by rand() LIMIT 0,".$nbCard;
		
		$newCards = self::getObjectListFromDB($sql);
		
		return $newCards;
	}


	/**
	 * Draw $nbCard new card(s) in the deck for the player $playerId
	 */
	function drawCard($nbCard, $playerId){
		
		$newCards = $this->getRandomCardsInDeck($nbCard);
		
		if(count($newCards) > 0){
			//build the update request to put the cards in the player's hands
			$sql = "update card set location='".$playerId."' where cardOrder IN (";
			$cardIds = array();
			foreach ($newCards as $card) {
				$cardIds[] = $card["cardOrder"];
			}
			$sql .= implode( $cardIds, ',' ); 
			$sql .= ");";
			
			self::DbQuery( $sql );	
			
			//notify the player he's got new cards 
			self::notifyPlayer( $playerId, "newCards", clienttranslate('You get ${nbCard} new card(s)'), array(
					"newCards"=>$newCards,
					"nbCard" => count($newCards)
					));	
		}
		else{
			//notify the player no more cards are available
			$this->log("No more cards are available", array());
		}
	
		return $newCards;
	}


	/**
	 * Modulo with the expected behaviour concerning negative number
	 * Example -2 % 16 = -2 but mod(-2,16) = 14
	 */
	function mod($a, $n) {
		$res = ($a % $n) + ($a < 0 ? $n : 0);
    	return $res;
	}
	
	/**
	 * Log a message in the players' message boxes
	 */
	function log($msg, $params){
		self::notifyAllPlayers( "log", clienttranslate($msg), $params);
	}

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */



//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in ledernierpeuple.action.php)
    */

    /*
    
    Example:

    function playCard( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playCard' ); 
        
        $player_id = self::getActivePlayerId();
        
        // Add your game logic to play a card there 
        ...
        
        // Notify all players about the card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} played ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );
          
    }
    
    */

    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */
    
    /**
	 * Method which returns the possible moves for each pawn of the player
	 */
    function argPossibleMoves(){
    	$playerId = self::getActivePlayerId();
		
		$sql = "select * from card where location=".$playerId." and chosen=1";
		$card = self::getObjectFromDb( $sql );
		
		//get the number total of tiles
		$sql = "select count(*) as nbTiles from tile";
		$result = self::getObjectFromDb( $sql );
		$nbTiles = $result["nbTiles"];
		
		//if we are on a doubleMove, we only have one choice
		if($this->readParameter("doubleMove") == "TRUE"){
			
			$doubleMovePawnId = $this->readParameter("doubleMovePawnId");
			
			$sql = "select tileId from pawn where id=".$doubleMovePawnId;
			$pawnTile = self::getUniqueValueFromDB( $sql );
			
			$moveTileId = $this->mod((($pawnTile -1) + $card["moveShift2"]), $nbTiles) + 1;
			
			$possibleMoves = array();
			$possibleMoves[$doubleMovePawnId] = array(array("tileId" => $moveTileId, "partial" => FALSE));
			
			return array(
						"possibleMoves" => $possibleMoves,
						"cardId" => $card["cardOrder"],
						"playerId" => $playerId
					);
		}
		
		if($card["moveType"] == "own"){
			$sql = "select * from pawn where playerId=".$playerId;
			$pawns = self::getCollectionFromDB( $sql );
		}
		else{
			$sql = "select * from pawn where playerId!=".$playerId;
			$pawns = self::getCollectionFromDB( $sql );
		}
		
		
		$possibleMoves = array();
		
		foreach ($pawns as $pawn) {
			$pawnTile = $pawn["tileId"];
			
			$pawnPossibleMoves = array();
			//move possible with the addition of card moveShift
			self::debug("pawnTile : ".$pawnTile.", cardShift : ".$card["moveShift"]."nbTiles : ".$nbTiles);
			$moveTileId = $this->mod((($pawnTile -1) + $card["moveShift"]), $nbTiles) + 1;
			$partial = FALSE;
			if(array_key_exists("moveShift2", $card) && $card["moveShift2"] != 0){
				$partial = TRUE;
			}
			$pawnPossibleMoves[] = array("tileId" => $moveTileId, "partial" => $partial);
			
			//move possible with a double move
			if(array_key_exists("moveShift2", $card)){
				$moveTileId = $this->mod((($pawnTile -1) + $card["moveShift"] + $card["moveShift2"]), $nbTiles) + 1;
				$pawnPossibleMoves[] = array("tileId"=>$moveTileId, "partial"=>FALSE);
			}
			
			//move possible with the teleportation
			$pawnPossibleMoves[] = array("tileId" => $card["teleportTile"], "partial" => FALSE);
			
			//add the possible moves for this pawn
			$possibleMoves[$pawn["id"]] = $pawnPossibleMoves;
		}
		
		return array(
			"possibleMoves" => $possibleMoves,
			"cardId" => $card["cardOrder"],
			"playerId" => $playerId
		);
    }

    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */
    
    /*
    
    Example for game state "MyGameState":

    function stMyGameState()
    {
        // Do some stuff ...
        
        // (very often) go to another gamestate
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }    
    */
    
    /**
	 * Draw a new card for the active player
	 */
    function stDrawCard(){
    		
		$playerId = self::getActivePlayerId();
		
		$this->drawCard(1, $playerId);
		
		//destroy all the parameters
		$this->destroyParameter("doubleMove");
		$this->destroyParameter("doubleMovePawnId");
    	
    	$this->gamestate->nextState( 'cardDrawed' );
    }
    
    
    /**
	 * Method which check if the card played by the active player gives a double move
	 */
    function stCheckDoubleMove(){
    		
    	//TODO
    	//self::debug("#### PARTIAL MOVE from database => ".$this->readParameterAndDestroy("partialMove"));
    	//self::debug("#### PARTIAL MOVE => ".$this->partialMove);
		
    	$this->gamestate->nextState( 'false' );
    }
    
    /**
	 * Method which change the active player after a player finished his move
	 * Before that, we check if a player rich the total of points for win the game
	 */
    function stNextPlayer(){
    	
		$pointsForVictory = 12;
		
		//check if a player rich the total of points necessary to win
		$sql = "select max(player_score) from player"; 
		
		$maxScore = self::getUniqueValueFromDB( $sql );
		
		if($maxScore >= $pointsForVictory){
			self::debug("A player win the game!");
			$this->gamestate->nextState( 'victory' );
		}
		else{
			self::debug("Activate next player");
			$this->activeNextPlayer();
			$this->gamestate->nextState( 'next' );
		}
		
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] == "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] == "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $sql = "
                UPDATE  player
                SET     player_is_multiactive = 0
                WHERE   player_id = $active_player
            ";
            self::DbQuery( $sql );

            $this->gamestate->updateMultiactiveOrNextState( '' );
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }
	
	
	
	function createPrivateParameter($name, $value){
		$playerId = self::getCurrentPlayerId();
		$sql = "insert into parameter values ('".$name."','".$playerId."','".$value."')";
		self::DbQuery( $sql );
	}
	
	function createPublicParameter($name, $value){
		$sql = "insert into parameter values ('".$name."',NULL,'".$value."')";
		self::DbQuery( $sql );
	}
	
	function readParameter($name){
		$playerId = self::getCurrentPlayerId();
		$sql = "select value from parameter where (visibility is NULL or visibility='".$playerId."') and name='".$name."'";
		return self::getUniqueValueFromDB($sql);
	}
	
	function readParameterAndDestroy($name){
		$value = $this->readParameter($name);
		if($value != NULL){
			$this->destroyParameter($name);
		}
		return $value;
	}
	
	function destroyParameter($name){
		$playerId = self::getCurrentPlayerId();
		$sql = "delete from parameter where (visibility is NULL or visibility='".$playerId."') and name='".$name."'";
		self::DbQuery($sql);
	}
}

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
            	"visible_score" => 100
        ) );
        
	}
	
    protected function getGameName( )
    {
        return "ledernierpeuple";
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
        $default_colors = array( "0000FF", "FFA500", "FF0000", "719D3E", "000000" );
		
 
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
		
	   	//init the power cards
	   	$request = getRequestInitPowerCards();
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
    
        
        //if the visible_score option property is set to 2 => all the scores are visible
        if(self::getGameStateValue("visible_score") == 2){
        	$sql = "SELECT player_id id, player_score score FROM player ";
			$result['players'] = self::getCollectionFromDb( $sql );
        }
		//if not only the current player score is send
		else{
			
			$sql = "SELECT player_id id FROM player ";
        	$result['players'] = self::getCollectionFromDb( $sql );
			
			//get the score of the current player
			$sql = "SELECT player_score score FROM player where player_id=".$current_player_id;
       		$result['players'][$current_player_id]["score"] = self::getUniqueValueFromDB( $sql );	
		}
        
        
        //get the tiles
        $sql = "SELECT * FROM tile order by id";
        $result['tiles'] = self::getCollectionFromDb( $sql );
		
		//get the pawns
        $sql = "SELECT * FROM pawn order by id";
        $result['pawns'] = self::getCollectionFromDb( $sql );
		
		//get the cards of the player
        $result["cards"] = $this->getAllCardsOf($current_player_id);
		
		//get the number of cards of each players
		$allPlayerIds = array_keys($result['players']);
		$sql = "select player_id, sum(nbCard) as nbCard from
				(
				select player_id, (select count(*) from powerCard where location=player_id) as nbCard from player
				union all 
				select player_id, (select count(*) from card where location=player_id) as nbCard from player) as tab group by player_id";
				
		
		
		self::debug( "###QUERY : ".$sql);
		
		$result["nbCards"] = self::getCollectionFromDb( $sql, true );
		
	
        return $result;
    }



	public function chooseCard($playerId, $cardId){
			
		$playerName = self::getActivePlayerName();
		
		// Check that this player is active and that this action is possible at this moment
        self::checkAction( 'chooseCard' );  
		
		//udpate the card to indicates it was chosen
		$sql = "UPDATE card set chosen=1 where id=".$cardId;
		
		self::DbQuery( $sql );
		
		//notify the player
		self::notifyAllPlayers( "playDisc", clienttranslate( '${playerName} choses a card' ), array(
                'playerName' => $playerName
            ) );
			
		//the card is chosen, we can move to the next state
		$this->gamestate->nextState( 'cardChosen' );
	}
	
	/**
	 * Skip turn and draw new cards instead of play
	 */
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
		
		
		$this->log('${playerName} skips his turn and draws ${nbCard} cards', 
					array("playerName"=>$playerName, "nbCard" => $nbCardToDraw));
					
		$this->drawCard($nbCardToDraw, $playerId);
		
		$this->gamestate->nextState( 'skipTurn' );
	}
	
	
	/**
	 * The player choose to not use one of his power cards
	 */
	public function skipPowerCard(){
		
		// Check that this player is active and that this action is possible at this moment
        self::checkAction( 'skipPowerCard' );  
		
		$this->gamestate->nextState( 'skipPowerCard' );
	}
	
	
	/**
	 * The player has chosen a power card
	 */
	public function choosePowerCard($playerId, $cardId){
			
		// Check that this player is active and that this action is possible at this moment
        self::checkAction( 'choosePowerCard' );  
		
		//udpate the card to indicates it was chosen
		$sql = "UPDATE powerCard set chosen=1 where id=".$cardId;
		self::DbQuery( $sql );
		
		$sql = "select * from powerCard where id=".$cardId;
		$powerCard = self::getObjectFromDb( $sql );
		
		$playerName = self::getActivePlayerName();
		
		//notify the players unless it's the power card defense
		if($powerCard["name"] != "defense"){
			$this->log('${playerName} uses the power card <b>${cardName}</b>', 
					array("playerName"=>$playerName, "cardName" => $powerCard["name"]));
		}
					
		
		switch($powerCard["name"]){
			
			case "bandit":
			case "blackMagic":
			case "mace":
			case "curse":
			case "barter":
			case "thief":
				//for those power cards, we need to choose the targeted player
				$this->gamestate->nextState( 'chooseTargetPlayer' );
				return;
				
			case "switch":
				//switch => the current player has to choose 2 pawns to switch
				$this->gamestate->nextState( 'chooseSwitchedPawns' );
				return;
			
			case "luck":
				//luck => the current player draw 1 power card and 1 move card
				$this->drawCard(1, $playerId);
				$this->drawPowerCard($playerId);
				break;
				
			case "defense":
				$this->createPublicParameter("DEFENSE_POWER", $playerId);
				break;
				
			case "strength":
			case "heal":
				//strength => the current player earns 2 points
				//heal => the current player earns 1 points
				if($powerCard["name"] == "strength"){
					$nbPoints = 2;
				}
				else{
					$nbPoints = 1;
				}

				$sql="update player set player_score=player_score+".$nbPoints." where player_id=".$playerId;
				self::DbQuery( $sql );
				
				//log
				$this->log('Player ${playerName} earns ${nbPoints} point(s)', 
							array("playerName" => $playerName, "nbPoints" => $nbPoints));	
				
				$this->notifNewScores(array($playerId));
				
				break;
				
			case "speed":
				$this->createPrivateParameter("SPEED_POWER", "TRUE");
				break;
		}
			
		//the card is chosen, we can move to the next state
		$this->gamestate->nextState( 'powerCardChosen' );
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
		
		
		//get the id of the player's pawn
		$sql = "select player_name,player_id from pawn p, player pl where p.playerId=pl.player_id and p.id=".$pawnId;
		$result3 = self::getObjectFromDb($sql);
		$pawnPlayerId = $result3["player_id"];
		$pawnPlayerName = $result3["player_name"]; 
		
		//variable to save the pawns we have to teleport at the end of the action
		$teleportPawns = array();
		
		//flag that indicates an attack has been launched
		$attackFlag = FALSE;
		//flag that indicates the player need to choose a combination to attack
		$chooseCombinationFlag = FALSE;
		
		//if we are next to a species tile, it might be an attack
		if($tileId % 4 == 1 || $tileId % 4 == 3){
			self::debug( "Potential attack" );
			$otherTileId = -1;
			$attackedTile = -1;
			//we need to check if another pawn is present on the tile at the other side of the species tile
			if($tileId  % 4 == 3) {
				//id of the tile on the other side 
				$otherTileId = $this->mod(($tileId - 3),  $nbTiles) + 1;
				//id of the attacked tile
				$attackedTile = $this->mod(($tileId - 2),  $nbTiles) + 1;
			}
			else if($tileId % 4 == 1) {
				//id of the tile on the other side 
				$otherTileId = $this->mod(($tileId + 2), $nbTiles);
				//id of the attacked tile
				$attackedTile = $this->mod(($tileId + 1), $nbTiles);
			}
			
			
			//get the name and id of the player who might be attacked
			$sql = "select player_name,player_id from player p, tile t where t.speciesPlayerId=p.player_id and t.id=".$attackedTile;
			$result2 = self::getObjectFromDb($sql);
			$playerNameAttacked = $result2["player_name"];
			$playerIdAttacked = $result2["player_id"];
			
			//check in base if another pawn is on the tile on the other side
			$sql = "select * from pawn where tileId=".$otherTileId." and id != ".$pawnId;
			$result = self::getCollectionFromDb( $sql );
			
			if($result != null && count($result) > 0){
				
				self::debug( "Attack player ".$playerNameAttacked );
				
				//if there are just 1 result, no choice, we can attack directly
				if(count($result) == 1){
					$otherPawn = reset($result);
					
					self::debug( "Attack with player ".$otherPawn["playerId"] );
					
					//we get the name of the player who helps to attack
					$sql = "select player_name from player p where p.player_id=".$otherPawn["playerId"];
					$result = self::getObjectFromDb($sql);
					$playerNameHelper = $result["player_name"];
					

					$this->attack(array("id"=>$playerId, "name"=>$playerName), 
									  array("id"=>$pawnPlayerId, "name"=>$pawnPlayerName),
									  array("id"=>$playerIdAttacked, "name"=>$playerNameAttacked),
									  array("id"=>$otherPawn["playerId"], "name"=>$playerNameHelper));
					
					
					
					//we add the two pawns to be teleported after the move
					$teleportPawns[] = $pawnId;
					$teleportPawns[] = $otherPawn["id"];
					
					$attackFlag = TRUE;
				}
				//we have more than one pawn on the other tile
				else{
					//check if all the pawns belong to the same player
					$lastPlayerId = NULL;
					$allSamePlayer = TRUE;
					
					//we need to check if the player who made the attack owns one of the pawns on the other tile
					//if he does, he automatically attacks with it
					foreach ($result as $pawn) {
						if($pawn["playerId"] == $playerId){
							$this->attack(array("id"=>$playerId, "name"=>$playerName), 
										  array("id"=>$pawnPlayerId, "name"=>$pawnPlayerName),
									  	  array("id"=>$playerIdAttacked, "name"=>$playerNameAttacked),
										  array("id"=>$playerId, "name"=>$playerName));
						
							//we add the two pawns to be teleported after the move
							$teleportPawns[] = $pawnId;
							$teleportPawns[] = $pawn["id"];
							
							$attackFlag = TRUE;
							
							break;
						}
						if($lastPlayerId != NULL && $lastPlayerId != $pawn["playerId"]){
							$allSamePlayer = FALSE;
						}
						$lastPlayerId = $pawn["playerId"];
					}
					//all the possible pawns belong to the same player => we can attack directly
					if(!$attackFlag && $allSamePlayer){
						$sql = "select player_name from player where player_id=".$lastPlayerId;
						$otherPawnPlayerName = self::getUniqueValueFromDB( $sql );
						
						$this->attack(array("id"=>$playerId, "name"=>$playerName), 
										  array("id"=>$pawnPlayerId, "name"=>$pawnPlayerName),
									  	  array("id"=>$playerIdAttacked, "name"=>$playerNameAttacked),
										  array("id"=>$lastPlayerId, "name"=>$otherPawnPlayerName));

						//we add the two pawns to be teleported after the move
						$teleportPawns[] = $pawnId;
						$firstResult = reset($result);
						$teleportPawns[] = $firstResult["id"];

						$attackFlag = TRUE;
					}
					
					//if the pawns on the other tile does'nt belong to the active player, he has to make a choice
					if(!$attackFlag){
						$chooseCombinationFlag = TRUE;
						$this->createPrivateParameter("otherTileId", $otherTileId);
						$this->createPrivateParameter("playerIdAttacked", $playerIdAttacked);
						$this->createPrivateParameter("basePawnId", $pawnId);
					}
				}
			}
		}
		//check if we're on a dig tile
		else if ($tileId % 4 == 0){
			$this->drawPowerCard($pawnPlayerId);
		}

		
		//udpate the pawn position
		$sql = "UPDATE pawn set tileId=".$tileId." where id=".$pawnId;
		self::DbQuery( $sql );
		
		//teleport the pawns after the attack
		if(count($teleportPawns) > 0){
			$this->teleportAfterAttack($teleportPawns);
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
	function attack($playerAttack, $pawnPlayer, $playerAttacked, $playerHelper){
		//nb points earn by the pawnPlayer
		$pointsEarn = 0;
		//nb points earn by the helper
		$helperPointsEarn = 0;
		
		//put the names of the players on an array 
		$playerNames = array();
		$playerNames[$playerAttack["id"]] = $playerAttack["name"];
		$playerNames[$pawnPlayer["id"]] = $pawnPlayer["name"];
		$playerNames[$playerAttacked["id"]] = $playerAttacked["name"];
		$playerNames[$playerHelper["id"]] = $playerHelper["name"];
		
		self::debug( "Attack! PlayerAttack : ".implode(",",$playerAttack) );
		self::debug( "PawnPlayer : ".implode(",",$pawnPlayer) );
		self::debug( "PlayerAttacked : ".implode(",",$playerAttacked) );
		self::debug( "PlayerHelper : ".implode(",",$playerHelper) );
		
		//check if the player attacked has the defense power card activated
		$defensePlayer = $this->readParameter("DEFENSE_POWER");
		$defense = FALSE;
		if($defensePlayer && $defensePlayer == $playerAttacked["id"]){
			$this->destroyParameter("DEFENSE_POWER");
			$defense = TRUE;
		}
		
		
		//compute the points earns/loses
		$points = array();
		//points earns
		if($pawnPlayer["id"] == $playerHelper["id"]){
			$points[$pawnPlayer["id"]] = 3;
		}
		else if ($playerAttack["id"] == $playerHelper["id"]){
			$points[$playerAttack["id"]] = 2;
			$points[$pawnPlayer["id"]] = 1;
		}
		else{
			$points[$playerHelper["id"]] = 1;
			$points[$pawnPlayer["id"]] = 2;
		}
		//points loses
		if(!$defense){
			if(array_key_exists($playerAttacked["id"], $points)){
				$points[$playerAttacked["id"]] = $points[$playerAttacked["id"]] - 1; 
			}
			else{
				$points[$playerAttacked["id"]] = -1;
			}
		}
		
		self::debug( "Points : ".implode(",",$points) );
		
		foreach ($points as $id => $point) {
			
			if($point > 0){
				//update the score
				$sql="update player set player_score=player_score+".$point." where player_id=".$id;
				self::DbQuery( $sql );
				//log
				$this->log('Player ${playerName} earns ${points} point(s)', 
							array("playerName" => $playerNames[$id], "points" => $point));	
			}
			else if ($point < 0){
				//update the score
				$sql="update player set player_score=player_score".$point." where player_id=".$id." and player_score > 0";
				self::DbQuery( $sql );
				//log
				$this->log('Player ${playerName} loses ${points} point(s)', 
							array("playerName" => $playerNames[$id], "points" => abs($point)));
			}
			
			//notify the new score to the player concerned
			if($point != 0){
				//notify the new scores
				$this->notifNewScores(array($id));
			}
		}

		//notify everybody than the player attacked had the power card defense
		if($defense){
			$this->log('Player ${playerName} is protected by the power card defense', 
							array("playerName" => $playerAttacked["name"]));
		}
		
        
		
	}


	/**
	 * Teleport the pawns after the attack
	 */
	function teleportAfterAttack($pawns){
		//if there are pawns to teleport after an attack, we teleport it
		if(count($pawns) > 0){
				
			//get random cards to determine new pawns position	
			$newCards = $this->getRandomCardsInDeck(count($pawns), "card");
			
			$cardIds = array();
			$teleport = array();
			
			for ($i=0; $i < count($newCards); $i++) { 
				$card = $newCards[$i];
				$pawn = $pawns[$i];
				
				//get the player name of the pawn
				$sql="select pl.player_name from player pl, pawn pa where pl.player_id=pa.playerId and pa.id=".$pawn["id"];
				$pawnPlayerName = self::getUniqueValueFromDB( $sql );
				
				$sql = "update pawn set tileId=".$card["teleportTile"]." where id=".$pawn["id"];
				self::DbQuery( $sql );
				
				$teleport[] = array("pawnId"=>$pawn["id"], "tileId" => $card["teleportTile"]);
				$cardIds[] = $card["id"];
				
				$this->log('${pawnPlayerName}\'s pawn is teleported on tile ${tileId}', 
							array('pawnPlayerName' => $pawnPlayerName, "tileId" => $card["teleportTile"]));
			}
			
			//put the cards in the trash
			$sql = "update card set location='TRASH' where id IN (".implode( $cardIds, ',' ).")";
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
	function getRandomCardsInDeck($nbCard, $table){
		//we check if we have enough card in the deck
		$sql = "select count(*) from ".$table." where location='DECK'";
		
		$nbCardAvailable = self::getUniqueValueFromDB( $sql );
		
		//we don't have enough card, we move the cards from the trash to the deck
		if($nbCardAvailable < $nbCard){
			$sql = "update ".$table." set location='DECK',chosen=0 where location='TRASH'";
			self::DbQuery( $sql );	
		}
		
		//recheck if we have enough card in the deck after the change
		$sql = "select count(*) from ".$table." where location='DECK'";
		$nbCardAvailable = self::getUniqueValueFromDB( $sql );
		
		if($nbCardAvailable < $nbCard){
			//we don't have enough remaining cards, so we get the last ones
			$nbCard = $nbCardAvailable;
		}
		
		//get $nbCard random cards
		$sql = "select *, '".$table."' as cardType from ".$table." where location='DECK' order by rand() LIMIT 0,".$nbCard;
		
		$newCards = self::getObjectListFromDB($sql);
		
		return $newCards;
	}


	/**
	 * Draw $nbCard new card(s) in the deck for the player $playerId
	 */
	function drawCard($nbCard, $playerId){
		
		$newCards = $this->getRandomCardsInDeck($nbCard, "card");
		
		if(count($newCards) > 0){
			//build the update request to put the cards in the player's hands
			$sql = "update card set location='".$playerId."' where id IN (";
			$cardIds = array();
			foreach ($newCards as $card) {
				$cardIds[] = $card["id"];
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
		
		
		$this->notifyNewNbOfCards($playerId);
		
	
		return $newCards;
	}


	/**
	 * Draw a power card if there are, and put it in the player's deck 
	 */
	function drawPowerCard($playerId){
		$newCards = $this->getRandomCardsInDeck(1, "powerCard");
		
		if(count($newCards) > 0){
			//build the update request to put the power card in the player's hands
			$sql = "update powerCard set location='".$playerId."' where id IN (";
			$cardIds = array();
			foreach ($newCards as $card) {
				$cardIds[] = $card["id"];
			}
			$sql .= implode( $cardIds, ',' ); 
			$sql .= ");";
			
			self::DbQuery( $sql );	
			
			//notify the player he's got new cards 
			self::notifyPlayer( $playerId, "newCards", clienttranslate('You get 1 new power card'), array(
					"newCards"=>$newCards,
					));	
			
		}
		else{
			//notify the player no more power cards are available
			$this->log("No more power cards are available", array());
		}
		
		$this->notifyNewNbOfCards($playerId);
		
		return $newCards;
	}

	
	/**
	 * Notifiy the players the new number of cards of a player
	 */
	function notifyNewNbOfCards($playerId){
			
		$sql = 	"select sum(nbCard) as nbCard from ".
				"( ".
				"SELECT count(*) as nbCard FROM `powerCard` where location = '".$playerId."' ".
				"union all ".
				"SELECT count(*) as nbCard FROM `card` where location = '".$playerId."') as tab ";
				
		$newNbCards = self::getUniqueValueFromDB($sql);		
		
		//notify all players the new number of cards own by this player after the round
		self::notifyAllPlayers( "newNbCards", "", array(
				"nbCards"=>array($playerId => $newNbCards)
				));
	}
	
	
	/**
	 * Method called when the player has chosen a combination to make the attack
	 */
	function combinationChosen($pawnId){
		
		$playerId = self::getActivePlayerId();
		$playerName = self::getActivePlayerName();
		
		//get the pawn who made the attack
		$basePawnId = $this->readParameter("basePawnId");
		
		//get the id of the player who is attacked
		$playerIdAttacked = $this->readParameter("playerIdAttacked");
		
		//get the id of the tile where are the potential helpers
		$otherTileId = $this->readParameter("otherTileId");
		
		//check the parameters
		if($basePawnId == NULL){
			throw new feException("No basePawnId parameter");
		}
		if($playerIdAttacked == NULL){
			throw new feException("No playerIdAttacked parameter");
		}
		if($otherTileId == NULL){
			throw new feException("No otherTileId parameter");
		}
		
		
		//check if the pawnId is correct
		//get the pawns on the other tile the player can choose
		$sql = "select * from pawn where tileId=".$otherTileId;
		$pawnsCombination = self::getCollectionFromDB( $sql );
		$checkPawn = FALSE;
		foreach ($pawnsCombination as $pawnCombinationId => $value) {
			if($pawnCombinationId == $pawnId){
				$checkPawn = TRUE;
			}
		}
		//if the pawn selected is not part of the available ones it may be a cheat so we throw an exception
		if(!$checkPawn){
			throw new BgaUserException( self::_("The chosen pawn is not available") );
		}
		
		//get the id of the selected pawn who help the attack
		$sql = "select player_name,player_id from pawn p, player pl where p.playerId=pl.player_id and p.id=".$pawnId;
		$result = self::getObjectFromDb($sql);
		$playerHelperId = $result["player_id"];
		$playerHelperName = $result["player_name"]; 
		
		//get the name of the player who is attacked
		$sql = "select player_name from player p where p.player_id=".$playerIdAttacked;
		$playerNameAttacked = self::getUniqueValueFromDB($sql);
		
		//get the name and id of the player who is the source of the attack
		$sql = "select player_name,player_id from pawn p, player pl where p.playerId=pl.player_id and p.id=".$basePawnId;
		$result = self::getObjectFromDb($sql);
		$pawnPlayerId = $result["player_id"];
		$pawnPlayerName = $result["player_name"]; 

		$this->attack(array("id"=>$playerId, "name"=>$playerName), 
					  array("id"=>$pawnPlayerId, "name"=>$pawnPlayerName),
				  	  array("id"=>$playerIdAttacked, "name"=>$playerNameAttacked),
					  array("id"=>$playerHelperId, "name"=>$playerHelperName));
					  
					  
		//teleport the pawns after the attack
		$this->teleportAfterAttack(array($pawnId, $basePawnId));
	
		
		//destroy the parameters 
		$this->destroyParameter("otherTileId");
		$this->destroyParameter("basePawnId");
		$this->destroyParameter("playerIdAttacked");
		
		// Finally, go to the next state
        $this->gamestate->nextState( "combinationChosen" );
	}


	/**
	 * Get all the cards in a player's deck
	 */
	function getAllCardsOf($playerId){
		$sql =	"SELECT id,'card' as cardType from card where chosen=0 and location=".$playerId.
						" union all".
						" select id,'powerCard' as cardType from powerCard where chosen=0 and location=".$playerId;
				
		$targetedCards = self::getCollectionFromDB( $sql );
		
		return $targetedCards;
	}

	
	/**
	 * Get a random card in a player's deck.
	 * Return NULL if no card in the deck
	 */
	function getRandomCardInDeckOf($playerId){
		
		$targetedCards = $this->getAllCardsOf($playerId);
		
		if(count($targetedCards) == 0){
			return NULL;
		}
		
		shuffle($targetedCards);
		
		return $targetedCards[0];
	}


	/**
	 * Function use to apply the effects of the power card bandit
	 */
	function playPowerCardBandit($targetedPlayer, $playerId, $playerName){
		
		$chosenCard = $this->getRandomCardInDeckOf($targetedPlayer["player_id"]);
		
		if($chosenCard == NULL){
			$this->log('${playerName} try to steal a card from ${targetedPlayer} but he\'s got no card', 
					array("playerName" => $playerName, "targetedPlayer" => $targetedPlayer["player_name"]));
			return;
		}
		
		//get the first card and place it in the current player's deck
		$sql="update ".$chosenCard["cardType"]." set location=".$playerId." where id=".$chosenCard["id"];
		self::DbQuery( $sql );	
		
		//notify the player he loses a card 
		self::notifyPlayer( $targetedPlayer["player_id"], "loseCards", "", array(
			"losedCards"=> array($chosenCard),
			));	
			
		//notify the player the new card
		self::notifyPlayer( $playerId, "newCards", "", array(
			"newCards"=> array($chosenCard),
			));
			
		//log the action
		$this->log('${playerName} steals a card from ${targetedPlayer}', 
					array("playerName" => $playerName, "targetedPlayer" => $targetedPlayer["player_name"]));
		
		//notify the new number of cards of the two concerned players
		$this->notifyNewNbOfCards($playerId);
		$this->notifyNewNbOfCards($targetedPlayer["player_id"]);
	}

	/**
	 * Function use to apply the effects of the power card black magic
	 */
	function playPowerCardBlackMagic($targetedPlayer, $playerId, $playerName){
		$chosenCard = $this->getRandomCardInDeckOf($targetedPlayer["player_id"]);
		
		if($chosenCard == NULL){
			$this->log('${playerName} tries to destroy a card from ${targetedPlayer} but he\'s got no card', 
					array("playerName" => $playerName, "targetedPlayer" => $targetedPlayer["player_name"]));
			return;
		}
		
		//get the first card and place it in the current player's deck
		$sql="update ".$chosenCard["cardType"]." set location='TRASH' where id=".$chosenCard["id"];
		self::DbQuery( $sql );	
		
		//notify the player he loses a card 
		self::notifyPlayer( $targetedPlayer["player_id"], "loseCards", "", array(
			"losedCards"=> array($chosenCard),
			));	
			
		//log the action
		$this->log('${playerName} destroys a card from ${targetedPlayer}', 
					array("playerName" => $playerName, "targetedPlayer" => $targetedPlayer["player_name"]));
		
		//notify the new number of cards of the targeted player
		$this->notifyNewNbOfCards($targetedPlayer["player_id"]);
	} 


	/**
	 * Function use to apply the effects of the power card mace
	 */
	function playPowerCardMace($targetedPlayer){
		$this->createPublicParameter("MACE_POWER", $targetedPlayer["player_id"]);
	}
	
	
	/**
	 * Function use to apply the effects of the power card curse
	 */
	function playPowerCardCurse($targetedPlayer, $playerId, $playerName){
		
		//check the current points of the targeted player
		$sql = "select player_score from player where player_id=".$targetedPlayer["player_id"];
		$currentPoints = self::getUniqueValueFromDB($sql);

		if($currentPoints <= 0){
			$this->log('${playerName} tries to remove a point from ${targetedPlayer} but he\'s got no point', 
					array("playerName" => $playerName, "targetedPlayer" => $targetedPlayer["player_name"]));
			return;
		}
		
		$sql= "update player set player_score=player_score-1 where player_id=".$targetedPlayer["player_id"];
		self::DbQuery( $sql );
		
		//notify the new scores
		$this->notifNewScores(array($targetedPlayer["player_id"]));

		$this->log('${playerName} removes a point from ${targetedPlayer}', 
					array("playerName" => $playerName, "targetedPlayer" => $targetedPlayer["player_name"]));
	}


	/**
	 * Function use to apply the effects of the power card thief
	 */
	function playPowerCardThief($targetedPlayer, $playerId, $playerName){
		
		//check the current points of the targeted player
		$sql = "select player_score from player where player_id=".$targetedPlayer["player_id"];
		$currentPoints = self::getUniqueValueFromDB($sql);

		if($currentPoints <= 0){
			$this->log('${playerName} tries to steal a point from ${targetedPlayer} but he\'s got no point', 
					array("playerName" => $playerName, "targetedPlayer" => $targetedPlayer["player_name"]));
			return;
		}
		
		$sql= "update player set player_score=player_score-1 where player_id=".$targetedPlayer["player_id"];
		self::DbQuery( $sql );
		$sql= "update player set player_score=player_score+1 where player_id=".$playerId;
		self::DbQuery( $sql );
		
		//notify the new scores
		$this->notifNewScores(array($playerId, $targetedPlayer["player_id"]));
		
		$this->log('${playerName} steals a point from ${targetedPlayer}', 
					array("playerName" => $playerName, "targetedPlayer" => $targetedPlayer["player_name"]));
	}



	/**
	 * Function use to apply the effects of the power card barter
	 */
	function playPowerCardBarter($targetedPlayer, $playerId, $playerName){
		
		$playerCards = $this->getAllCardsOf($playerId);
		$targetedPlayerCards = $this->getAllCardsOf($targetedPlayer["player_id"]);
		
		//put the player's cards in the targeted player's deck
		if(count($playerCards) > 0){
			
			//get the ids of each type of cards
			$cardIds=array();
			$powerCardIds=array();
			
			foreach ($playerCards as $card) {
				if($card["cardType"] == "powerCard"){
					$powerCardIds[] = $card["id"];
				}
				else if($card["cardType"] == "card"){
					$cardIds[] = $card["id"];
				}
			}
			
			if(count($cardIds) > 0){
				$sql = "update card set location=".$targetedPlayer["player_id"]." where chosen=0 and id IN(".implode(",", $cardIds).")";
				self::DbQuery( $sql );	
			}

			if(count($powerCardIds) > 0){
				$sql = "update powerCard set location=".$targetedPlayer["player_id"]." where chosen=0 and id IN(".implode(",", $powerCardIds).")";
				self::DbQuery( $sql );	
			}
			
			//notify the player he loses all his cards 
			self::notifyPlayer( $playerId, "loseCards", "", array(
				"losedCards"=> $playerCards,
				));	
				
			//notify his new cards to the targeted player 
			self::notifyPlayer( $targetedPlayer["player_id"], "newCards", "", array(
				"newCards"=> $playerCards,
				));
		}
		//put the targeted player's cards in the player's deck
		if(count($targetedPlayerCards) > 0){
			
			
			//get the ids of each type of cards
			$cardIds=array();
			$powerCardIds=array();
			
			foreach ($targetedPlayerCards as $card) {
				if($card["cardType"] == "powerCard"){
					$powerCardIds[] = $card["id"];
				}
				else if($card["cardType"] == "card"){
					$cardIds[] = $card["id"];
				}
			}
			
			if(count($cardIds) > 0){
				$sql = "update card set location=".$playerId." where chosen=0 and id IN(".implode(",", $cardIds).")";
				self::DbQuery( $sql );	
			}

			if(count($powerCardIds) > 0){
				$sql = "update powerCard set location=".$playerId." where chosen=0 and id IN(".implode(",", $powerCardIds).")";
				self::DbQuery( $sql );	
			}
			
			//notify the targeted player he loses all his cards 
			self::notifyPlayer( $targetedPlayer["player_id"], "loseCards", "", array(
				"losedCards"=> $targetedPlayerCards,
				));	
				
			//notify his new cards to the player 
			self::notifyPlayer( $playerId, "newCards", "", array(
				"newCards"=> $targetedPlayerCards,
				));
		}
		
			
		//log the action
		$this->log('${playerName} and ${targetedPlayer} switch their cards', 
					array("playerName" => $playerName, "targetedPlayer" => $targetedPlayer["player_name"]));
		
		//notify the new number of cards of the two concerned players
		$this->notifyNewNbOfCards($playerId);
		$this->notifyNewNbOfCards($targetedPlayer["player_id"]);
	}


	function targetChosen($pawnId){
		$playerId = self::getActivePlayerId();
		$playerName = self::getActivePlayerName();
		
		//get the targeted player id and name
		$sql="select player_id,player_name from pawn inner join player on (pawn.playerId=player.player_id) where id=".$pawnId;
		$targetedPlayer = self::getObjectFromDb( $sql );
		
		//get the power card 
		$sql="select * from powerCard where location=".$playerId." and chosen=1";
		$powerCard = self::getObjectFromDb( $sql );
		
		//check if the player targeted has the defense power card activated
		$defensePlayer = $this->readParameter("DEFENSE_POWER");
		if($defensePlayer && $defensePlayer == $targetedPlayer["player_id"]){
			$this->destroyParameter("DEFENSE_POWER");
			$this->log('Player ${playerName} is protected by the power card defense', 
							array("playerName" => $targetedPlayer["player_name"]));
			$this->gamestate->nextState( "targetChosen" );
			return;
		}
		
		switch($powerCard["name"]){
			
			case "bandit":
				//bandit => steal a card of the player
				$this->playPowerCardBandit($targetedPlayer, $playerId, $playerName);
				
				break;
			case "blackMagic":
				//blackMagic => put one player's card in the trash
				$this->playPowerCardBlackMagic($targetedPlayer, $playerId, $playerName);
				
				break;
			case "mace":
				//mace => the targeted player pass the next turn
				$this->playPowerCardMace($targetedPlayer);
				
				break;
			case "curse":
				//curse => the targeted player loses one point 
				$this->playPowerCardCurse($targetedPlayer, $playerId, $playerName);
				
				break;
			case "barter":
				//barter => swap the cards with the targeted player
				$this->playPowerCardBarter($targetedPlayer, $playerId, $playerName);
				
				break;
			case "thief":
				//thief => steal one point of the targeted player
				$this->playPowerCardThief($targetedPlayer, $playerId, $playerName);
				
				break;
				
			default:
				throw new BgaUserException( self::_("Action not available") ); 
				
		}

		
		// Finally, go to the next state
        $this->gamestate->nextState( "targetChosen" );
	}


	/**
	 * Method which switched two pawns' position
	 */
	function switchPawns($firstPawnId, $secondPawnId){
		
		$playerId = self::getActivePlayerId();
		$playerName = self::getActivePlayerName();
		
		//get the current pawns position
		$sql="select tileId from pawn where id=".$firstPawnId;
		$firstPawnTile = self::getUniqueValueFromDB($sql);
		$sql="select tileId from pawn where id=".$secondPawnId;
		$secondPawnTile = self::getUniqueValueFromDB($sql);
		
		//update the position
		$sql="update pawn set tileId=".$secondPawnTile." where id=".$firstPawnId;
		self::DbQuery($sql);
		$sql="update pawn set tileId=".$firstPawnTile." where id=".$secondPawnId;
		self::DbQuery($sql);
		
		
		//notify the players
		self::notifyAllPlayers( "movePawn", clienttranslate( '${playerName} switch two pawns position' ), array(
                'playerId' => $playerId,
                'playerName' => $playerName,
                'tileId' => $secondPawnTile,
                'pawnId' => $firstPawnId
            ) );
		self::notifyAllPlayers( "movePawn", '', array(
            'playerId' => $playerId,
            'tileId' => $firstPawnTile,
            'pawnId' => $secondPawnId
        ) );
		
		$this->gamestate->nextState( "pawnsSwitched" );
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
	
	
	function notifNewScores($players){
		$sql = "SELECT player_id, player_score FROM player where player_id IN (".implode( $players, ',' ).")";
		
		$newScores = self::getCollectionFromDb( $sql , true );
	    
		//we are in visible_score mode => we send the notif to every player
		if(self::getGameStateValue("visible_score") == 2){
			self::notifyAllPlayers( "newScores", "", array("scores" => $newScores));
		}
		else{
			foreach ($players as $playerId){
				self::notifyPlayer( $playerId, "newScores", "", array("scores" => array($playerId => $newScores[$playerId])));	
			}
				
		}
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
        //check if a player rich the total of points necessary to win
		$sql = "select max(player_score) from player"; 
		
		$maxScore = self::getUniqueValueFromDB( $sql );
		
		//get the progress by step in order to hide the a minimum the score of the leader
		$progress = 0;
		if($maxScore <= 2){
			$progress = 0;
		}	
		else if($maxScore <= 5){
			$progress = 25;
		}
		else if($maxScore <= 8){
			$progress = 50;
		}
		else if($maxScore <= 11){
			$progress = 75;
		}
		else {
			$progress = 100;
		}
		
		$lastProgress = $this->readParameter("LAST_PROGRESS");
		
		//if the saved progression is superior to the current progress
		//which can happen if the leader loses some points, 
		//we display the previous progression
		if($lastProgress != NULL && $lastProgress > $progress){
			return $lastProgress;
		}
		
		//save the progression if it has changed
		if($lastProgress != $progress){
			if($lastProgress != NULL){
				$this->destroyParameter("LAST_PROGRESS");	
			}
			$this->createPublicParameter("LAST_PROGRESS", $progress);	
		}
		
		
		
        return $progress;
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
						"cardId" => $card["id"],
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
			"cardId" => $card["id"],
			"playerId" => $playerId
		);
    }
    
    
	/**
	 * Get the arguments for the combination the player have to choose 
	 */
	function argPossibleCombination(){
		
		$playerId = self::getActivePlayerId();
		
		$otherTileId = $this->readParameter("otherTileId");
		if($otherTileId == NULL){
			throw new feException("No otherTileId parameter");
		}
		
		//get the pawns on the other tile the player can choose
		$sql = "select * from pawn where tileId=".$otherTileId;
		$pawnsCombination = self::getCollectionFromDB( $sql );
		
		return array(
			"pawnsCombination" => $pawnsCombination
		);
	}
	
	
	/**
	 * Get the arguments for the choice of the target player affected by the power card
	 */
	function argPossibleTarget(){
		$playerId = self::getActivePlayerId();
		
		$sql = "select * from pawn where playerId!=".$playerId;
		
		$pawns = self::getCollectionFromDB( $sql );
		
		return array(
			"possiblePawns" => $pawns
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
	 * Check if the player has power cards
	 */
	function stCheckHasPowerCard(){
		
		$playerId = self::getActivePlayerId();
		
		$sql = "select count(*) from powerCard where location='".$playerId."' and chosen = 0";
		
		$nbPowerCards = self::getUniqueValueFromDB( $sql );
		
		self::debug("Nb power cards for player ".$playerId." : ".$nbPowerCards);
		
		if($nbPowerCards > 0){
			$this->gamestate->nextState( 'true' );
		}
		else{
			$this->gamestate->nextState( 'false' );
		}
	}
	
	/**
	 * Method called when the player finished to use his power card
	 */
	function stEndPowerCard(){
			
		$playerId = self::getActivePlayerId();
		
		//put the power card in the trash
		$sql = "UPDATE powerCard set location='TRASH',chosen=0 where location='".$playerId."' and chosen=1";
		self::DbQuery( $sql );
		
		//notify the change of nb of cards
		$this->notifyNewNbOfCards($playerId);
		
		$this->gamestate->nextState( 'end' );
		
	}
	
    
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
	 * Method called when the player finished to move his pawn
	 */
	function stEndMoveCard(){
		
		$playerId = self::getActivePlayerId();
		
		//update the card status to put it in the trash
		$sql = "UPDATE card set location='TRASH',chosen=0 where chosen=1 and location=".$playerId;
		self::DbQuery( $sql );
		
		$this->notifyNewNbOfCards($playerId);
		
		$speedPower = $this->readParameterAndDestroy("SPEED_POWER");
		if($speedPower && $speedPower == "TRUE"){
			$this->gamestate->nextState( 'speedPowerUsed' );	
		}
		else{
			$this->gamestate->nextState( 'end' );
		}
		
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
			
			//new player ID
			$newPlayerId = self::getActivePlayerId();
			
			//check if the new player has been targeted by the mace power card
			$macePlayerId = $this->readParameter("MACE_POWER");
			if($macePlayerId && $macePlayerId == $newPlayerId){
				$newPlayerName = self::getActivePlayerName();
				$this->log('${playerName} is affected by the mace power card, he skips his turn', 
					array("playerName" => $newPlayerName));
				
				$this->activeNextPlayer();
				$this->destroyParameter("MACE_POWER");
			}
			
			
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
            	case "choosePowerCard":
					$this->gamestate->nextState( "skipPowerCard" );
                	break;
				case "chooseCard":
					$this->gamestate->nextState( "skipTurn" );
                	break;
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
		$sql = "insert into parameter(name,visibility,value) values ('".$name."','".$playerId."','".$value."')";
		self::DbQuery( $sql );
	}
	
	function createPublicParameter($name, $value){
		$sql = "insert into parameter(name,visibility,value) values ('".$name."',NULL,'".$value."')";
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

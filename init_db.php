<?php

/**
 * Contains the methods used to initialize the informations in the database
 */


 /**
  * Get the request for the initialisation of the tiles
  */
 function getRequestInitTiles($players){
 	
	$nbPlayer = count($players);
	
 	//get the number of tiles 	
	$nbTiles = $nbPlayer * 4;
	
	$sql ="INSERT INTO tile VALUES ";
	$values = array();
	for ($i=1; $i <= $nbTiles; $i++) {
		$tileType = "normal";
		$speciesPlayerId = "NULL";
		if($i % 4 == 2){
			$tileType = "species";
			$playerIds = array_keys($players);
			$playerIdx = floor($i / 4);
			$speciesPlayerId = $playerIds[$playerIdx];
		}
		else if($i % 4 == 0){
			$tileType = "event";
		}
		
		$values[] = "('".$i."','".$tileType."',".$speciesPlayerId.")";
	}
	$sql .= implode( $values, ',' );
	return $sql;
 } 
 
 
 
  /**
  * Get the request for the initialisation of the pawns
  */
 function getRequestInitPawns($players){
 	
	$sql ="INSERT INTO pawn(playerId,tileId) VALUES ";
	$values = array();
	$tileId = 2;
	foreach( $players as $player_id => $player )
    {
        $values[] = "('".$player_id."','".$tileId."')";
		$values[] = "('".$player_id."','".$tileId."')";
		$tileId = $tileId + 4;
    }
	$sql .= implode( $values, ',' );
	return $sql;
 }
 
 
 
  /**
  * Get the request for the initialisation of the cards
  */
 function getRequestInitCards($players){
 	
	//array representing all the available cards with their properties
 	$cardsDef = array(
		1 => array("id" => 1, "nbPlayer" => 3, "teleportTile" => 2, "moveType" => "own", "moveShift" => 0, "moveShift2" => 1),
		2 => array("id" => 2, "nbPlayer" => 3, "teleportTile" => 1, "moveType" => "own", "moveShift" => 1),
		3 => array("id" => 3, "nbPlayer" => 3, "teleportTile" => 6, "moveType" => "other", "moveShift" => 1),
		4 => array("id" => 4, "nbPlayer" => 3, "teleportTile" => 4, "moveType" => "own", "moveShift" => 2),
		5 => array("id" => 5, "nbPlayer" => 3, "teleportTile" => 12, "moveType" => "own", "moveShift" => 2),
		6 => array("id" => 6, "nbPlayer" => 3, "teleportTile" => 3, "moveType" => "other", "moveShift" => 2),
		7 => array("id" => 7, "nbPlayer" => 3, "teleportTile" => 6, "moveType" => "own", "moveShift" => 2, "moveShift2" => 1),
		8 => array("id" => 8, "nbPlayer" => 3, "teleportTile" => 5, "moveType" => "own", "moveShift" => 3),
		9 => array("id" => 9, "nbPlayer" => 3, "teleportTile" => 10, "moveType" => "other", "moveShift" => 3),
		10 => array("id" => 10, "nbPlayer" => 3, "teleportTile" => 11, "moveType" => "own", "moveShift" => 4),
		11 => array("id" => 11, "nbPlayer" => 3, "teleportTile" => 9, "moveType" => "own", "moveShift" => 4, "moveShift2" => 1),
		12 => array("id" => 12, "nbPlayer" => 3, "teleportTile" => 1, "moveType" => "own", "moveShift" => 6),
		13 => array("id" => 13, "nbPlayer" => 3, "teleportTile" => 4, "moveType" => "own", "moveShift" => -1),
		14 => array("id" => 14, "nbPlayer" => 3, "teleportTile" => 10, "moveType" => "own", "moveShift" => -1),
		15 => array("id" => 15, "nbPlayer" => 3, "teleportTile" => 5, "moveType" => "other", "moveShift" => -1),
		16 => array("id" => 16, "nbPlayer" => 3, "teleportTile" => 3, "moveType" => "own", "moveShift" => -1, "moveShift2" => -1),
		17 => array("id" => 17, "nbPlayer" => 3, "teleportTile" => 8, "moveType" => "own", "moveShift" => -2),
		18 => array("id" => 18, "nbPlayer" => 3, "teleportTile" => 2, "moveType" => "other", "moveShift" => -2),
		19 => array("id" => 19, "nbPlayer" => 3, "teleportTile" => 7, "moveType" => "own", "moveShift" => -3),
		20 => array("id" => 20, "nbPlayer" => 3, "teleportTile" => 8, "moveType" => "own", "moveShift" => -3),
		21 => array("id" => 21, "nbPlayer" => 3, "teleportTile" => 11, "moveType" => "other", "moveShift" => -3),
		22 => array("id" => 22, "nbPlayer" => 3, "teleportTile" => 9, "moveType" => "own", "moveShift" => -3, "moveShift2" => -1),
		23 => array("id" => 23, "nbPlayer" => 3, "teleportTile" => 12, "moveType" => "own", "moveShift" => -5),
		24 => array("id" => 24, "nbPlayer" => 3, "teleportTile" => 7, "moveType" => "own", "moveShift" => -5, "moveShift2" => -1),
		25 => array("id" => 25, "nbPlayer" => 4, "teleportTile" => 13, "moveType" => "own", "moveShift" => 4),
		26 => array("id" => 26, "nbPlayer" => 4, "teleportTile" => 14, "moveType" => "other", "moveShift" => 4),
		27 => array("id" => 27, "nbPlayer" => 4, "teleportTile" => 15, "moveType" => "own", "moveShift" => 6, "moveShift2" => 1),
		28 => array("id" => 28, "nbPlayer" => 4, "teleportTile" => 16, "moveType" => "own", "moveShift" => 8),
		29 => array("id" => 29, "nbPlayer" => 4, "teleportTile" => 14, "moveType" => "own", "moveShift" => -4),
		30 => array("id" => 30, "nbPlayer" => 4, "teleportTile" => 15, "moveType" => "other", "moveShift" => -4),
		31 => array("id" => 31, "nbPlayer" => 4, "teleportTile" => 16, "moveType" => "own", "moveShift" => -7),
		32 => array("id" => 32, "nbPlayer" => 4, "teleportTile" => 13, "moveType" => "own", "moveShift" => -7,  "moveShift2" => -1),
		33 => array("id" => 33, "nbPlayer" => 5, "teleportTile" => 17, "moveType" => "own", "moveShift" => 5),
		34 => array("id" => 34, "nbPlayer" => 5, "teleportTile" => 18, "moveType" => "other", "moveShift" => 5),
		35 => array("id" => 35, "nbPlayer" => 5, "teleportTile" => 18, "moveType" => "own", "moveShift" => 8,  "moveShift2" => 1),
		36 => array("id" => 36, "nbPlayer" => 5, "teleportTile" => 20, "moveType" => "own", "moveShift" => 10),
		37 => array("id" => 37, "nbPlayer" => 5, "teleportTile" => 17, "moveType" => "own", "moveShift" => -5),
		38 => array("id" => 38, "nbPlayer" => 5, "teleportTile" => 19, "moveType" => "other", "moveShift" => -5),
		39 => array("id" => 39, "nbPlayer" => 5, "teleportTile" => 20, "moveType" => "own", "moveShift" => -9),
		40 => array("id" => 40, "nbPlayer" => 5, "teleportTile" => 19, "moveType" => "own", "moveShift" => -9, "moveShift2" => -1)
	);
 
		
	$nbPlayer = count($players);
 	
	
	//get the players IDs
	$playerIds = array();
	foreach( $players as $playerId => $player ){
		$playerIds[] = $playerId;
	}
	
	//random sort on the cards in order to assign each card to a random player
	shuffle($cardsDef);
	
	$i=0;
	
	//initial number of cards for each player
	$nbCardByPlayer = 2;
	
	$sql ="INSERT INTO card VALUES ";
	$values = array();
	foreach ($cardsDef as $card) {

		//if the card is available with the number of current players, we add it			
		if($nbPlayer >= $card["nbPlayer"]){
			
			//by default the card is in the deck
			$location = "DECK";
			
			//if we haven't yet give all the cards to the player, we determine the player that take the card
			if($i < $nbPlayer * $nbCardByPlayer){
				//get the playerId for this card
				$playerId = $playerIds[floor($i/$nbCardByPlayer)];
				$location = $playerId;
			}
			
			$moveShift2 = 0;
			if(array_key_exists("moveShift2", $card)){
				$moveShift2 = $card["moveShift2"];
			}
			
			
			$values[] = "('".$card["id"]."','".$card["moveType"]."','".$card["moveShift"]."','".$moveShift2."','".$card["teleportTile"]."','".$location."',0)";
			$i++;
		}
		
	}
    
	$sql .= implode( $values, ',' );
	return $sql;
 }



/**
 * Get the SQL requests to insert the power cards
 */
function getRequestInitPowerCards(){
	//array representing all the available power cards
 	$cardsDef = array(
		1 => array("name"=>"bandit", "location" => "DECK"),
		2 => array("name"=>"luck", "location" => "DECK"),
		3 => array("name"=>"defense", "location" => "DECK"),
		4 => array("name"=>"strength", "location" => "DECK"),
		5 => array("name"=>"blackMagic", "location" => "DECK"),
		6 => array("name"=>"mace", "location" => "DECK"),
		7 => array("name"=>"switch", "location" => "DECK"),
		8 => array("name"=>"heal", "location" => "DECK"),
		9 => array("name"=>"curse", "location" => "DECK"),
		10 => array("name"=>"barter", "location" => "DECK"),
		11 => array("name"=>"speed", "location" => "DECK"),
		12 => array("name"=>"thief", "location" => "DECK"),
	);
	
	$sql ="INSERT INTO powerCard(name,location) VALUES ";
	$values = array();
	foreach ($cardsDef as $card) {
		$values[] = "('".$card["name"]."','".$card["location"]."')";		
	}
    
	$sql .= implode( $values, ',' );
	return $sql;
}

 

?>
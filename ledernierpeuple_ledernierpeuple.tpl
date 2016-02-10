{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- LeDernierPeuple implementation : © <Your name here> <Your email address here>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    ledernierpeuple_ledernierpeuple.tpl
    
    This is the HTML template of your game.
    
    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.
    
    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format
    
    See your "view" PHP file to check how to set variables and control blocks
    
    Please REMOVE this comment before publishing your game on BGA
-->

<table id="gameTable">
	<tr>
		<td>
			<div id="board">
				<div id="tiles"></div>
				<div id="pawns"></div>
			</div>
			<div id="chosenCard"></div>
			<div id="chosenPowerCard"></div>
		</td>
		<td style="width:100%">
			<div id="deck" class="whiteblock">
				<h3>Deck</h3>
				<div id="cards"></div>
			</div>
		</td>
	</tr>
</table>


<script type="text/javascript">

// Javascript HTML templates

/*
// Example:
var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${id}"></div>';


style="background-image:url(\'img/tiles/tile${id}.png\');"

*/

var jstpl_tile='<div class="tile" id="tile_${id}" style="background-position:${bgPosition}px;"></div>';
var jstpl_pawn='<div class="pawn pawn_${color}" id="pawn_${id}"></div>';
var jstpl_moveCard='<div class="card moveCard" id="card_${id}" style="background-position:${bgPosition}px;"></div>';
var jstpl_powerCard='<div class="card powerCard" id="powerCard_${id}" style="background-position:${bgPosition}px;"></div>';

</script>  




{OVERALL_GAME_FOOTER}

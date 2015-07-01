/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * LeDernierPeuple implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * ledernierpeuple.js
 *
 * LeDernierPeuple user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter"
],
function (dojo, declare) {
    return declare("bgagame.ledernierpeuple", ebg.core.gamegui, {
        constructor: function(){
            console.log('ledernierpeuple constructor');
              
            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;
            
            this.powerCardDesc = [];
            this.powerCardDesc[1] = "Steal 1 card from another player";
            this.powerCardDesc[2] = "Draw 1 move card and 1 power card";
            this.powerCardDesc[3] = "Protect from an attack or a power card";
            this.powerCardDesc[4] = "Earn 2 points";
            this.powerCardDesc[5] = "Remove 1 card from another player";
            this.powerCardDesc[6] = "Skip the turn of another player";
            this.powerCardDesc[7] = "Switch 2 pawns";
            this.powerCardDesc[8] = "Earn 1 point";
            this.powerCardDesc[9] = "Remove 1 point from another player";
            this.powerCardDesc[10] = "Swap all your cards with those af another player";
            this.powerCardDesc[11] = "Play 2 turns";
            this.powerCardDesc[12] = "Steal 1 point from another player";

        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
        setup: function( gamedatas )
        {
            console.log( "Starting game setup" );
            
            // Setting up player boards
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];
                         
                // TODO: Setting up players boards if needed
            }
            
            // TODO: Set up your game interface here, according to "gamedatas"
            
            //put the tiles on the board
            this.putTilesOnBoard(gamedatas.tiles);
            
            //put the pawns on the board
        	this.putPawnsOnBoard(gamedatas.pawns, gamedatas.players, gamedatas.tiles);	
            
            //put the cards
        	this.putCards(gamedatas.cards);	
        	
        	//set the nb cards of each player in the player box
        	this.putNbCards(gamedatas.nbCards);
 
            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },
        
        /**
         * Utility method used to count the number of element in an object
         * Useful for the gamedatas object
         */
        countElements : function(obj){
		    var size = 0, key;
		    for (key in obj) {
		        if (obj.hasOwnProperty(key)) size++;
		    }
		    return size;
        },
        
        /**
         * Put the tiles in circle on the board
         */
        putTilesOnBoard: function(tiles){
        	//number of tiles
        	var nbTiles = this.countElements(tiles);
        	
        	//space between tiles
        	var tileWidth = 74 + 6;
        	var tileHeight = 100;
        	
        	//coordinates of the first tile
        	var top = 0;
        	if(nbTiles == 20){
        		left = 313;
        	}
        	else if (nbTiles == 16){
        		left = 260;
        	}
        	else if (nbTiles == 12){
        		left = 210;
        	}
        	else{
        		console.error("TODO");
        	}
            
            var angle = 0;
            
            //vars use to compute the total height of the container
            var panelHeight = 2 * tileHeight;
			var panelWidth = 2 * tileHeight;//tileHeight because the tiles are horizontals

        	for(var idx in tiles){
        		
        		var bgPosition = (idx - 1) * - 74;
        		
	        	//create the html node from the jstpl expression
	        	dojo.place( this.format_block( 'jstpl_tile', {
	                id: tiles[idx].id,
	                bgPosition : bgPosition
	            } ) , 'tiles' );
	            
	            //keep the coordinates to use it during the pawns placement 
	            tiles[idx].top = top;
	            tiles[idx].left = left;
	            
	            //set the rotattion style
	            $('tile_'+tiles[idx].id).style.transform="rotate("+angle+"deg)";
				$('tile_'+tiles[idx].id).style.transformOrigin='0px '+tileHeight+'px';    
	            
	            dojo.fx.slideTo({node:'tile_'+tiles[idx].id,top : top, left : left, unit: 'px', duration:1000}).play();
	            
	            //Create the div which will contains the pawns
	            dojo.place('<div id="pawnTile_'+tiles[idx].id+'" class="pawnTile"></div>', "tile_"+tiles[idx].id,"last");
	            
	            
				var deltaX =  tileWidth * Math.cos((Math.PI * angle) / 180);
				var deltaY = tileWidth * Math.sin((Math.PI * angle) / 180);
				left += deltaX; 
            	top += deltaY;
			
				//add to container size
				if(deltaX > 0){
					panelWidth+=deltaX;
				}
				if(deltaY > 0){
					panelHeight+=deltaY;
				}
				
	            angle += 360 / nbTiles;
	            
           }
           
           //then, we determine the size of the board
           $("board").style.width = panelWidth + "px";
           $("board").style.height = panelHeight + "px";
           
           //set the location of the card selected div
           $("chosenCard").style.left = (panelWidth / 2) - (100 / 2) + 10 + "px";
           $("chosenCard").style.top = (panelHeight / 2) - (139 / 2) + "px";
        },
        
        /**
         * Put the pawns on the tiles
         */
        putPawnsOnBoard : function (pawns, players, tiles){
        	
        	for(var idx in pawns){
        		
        		var player = players[pawns[idx].playerId];
        		
        		
        		//create the html node from the jstpl expression
	        	dojo.place( this.format_block( 'jstpl_pawn', {
	                color: player.color,
	                id: pawns[idx].id
	            } ) , 'pawnTile_'+ pawns[idx].tileId );
	            
	            
        		var tile = tiles[pawns[idx].tileId];
        		
	            //coordinates of the pawn
	            var top = parseInt(tile.top) + 32;
	            var left = parseInt(tile.left);
	            if((idx - 1) % 2 == 0){
	            	left += 5;
	            }
	            else{
	            	left += 30;
	            }
	            
	            
	            //dojo.fx.slideTo({node:'pawn_'+pawns[idx].id,top : top, left : left, unit: 'px', duration:1000, delay:1000}).play();
        	}
        },
        
        
        /**
         * Put the cards of the deck
         */
        putCards : function(cards){
        	
        	for(var idx in cards){
        		
        		var bgPosition = (cards[idx].id - 1) * -100;
        		
        		//create the html node from the jstpl expression
	        	dojo.place( this.format_block( 'jstpl_'+cards[idx].cardType, {
	                id: cards[idx].id,
	                bgPosition : bgPosition
	            } ) , 'cards' );
	            
	            if(cards[idx].cardType == "powerCard"){
	            	var desc = this.powerCardDesc[cards[idx].id];
	            	if(desc){
	            		var idElem = "powerCard_"+cards[idx].id;
	            		this.addTooltip(idElem, _(desc), '');	
	            	}
	            }
	            //dojo.fx.slideTo({node:'card_'+cards[idx].id,top : top, left : left, unit: 'px', duration:1000, delay:2000}).play();
	            //left+=120;
        	}
        	
        },
        
        
        /**
         * Remove a card from the deck
         */
        removeCards: function(cards){
        	for(var idx in cards){
        		
        		var cardEltId = cards[idx].cardType+"_"+cards[idx].id;
        		
        		if($(cardEltId) != null){
	    		    //remove the card chosen    
		            dojo.fadeOut({node: cardEltId,
		            			onEnd : dojo.destroy
		            	}).play();	
        		}
        	}
        },

		/**
		 * Put the number of cards for each player in the player box information
		 */        
        putNbCards: function(nbCardsByPlayer){
			for(var playerId in nbCardsByPlayer){
				
				//create html nodes
				var nbCardHtml = '<div class="boardblock"><span id="player_nbcard_'+playerId+'">'+nbCardsByPlayer[playerId]+'&nbsp;</span>';
				nbCardHtml += '<div class="icon16 icon16_hand"></div></div>';
				
				dojo.place(nbCardHtml, dojo.query("#player_board_"+playerId+" .player_score")[0]);
			}
        },
        
        updateNbCards: function(playerId, nbCard){
        	dojo.byId("player_nbcard_"+playerId).innerHTML = nbCard+'&nbsp;';
        },
       
       
       updatePossibleMoves: function(possibleMoves){
       		this.possibleMoves = possibleMoves;
       		for(var pawnId in possibleMoves){
       			var pawnElt = dojo.byId('pawn_'+pawnId);
       			dojo.setStyle(pawnElt, {cursor : 'pointer'});
   				pawnElt.onclickListener = dojo.connect(pawnElt, 'onclick', this, 'onPawnClick');
       		}
       	
       },
       
       updateCombination: function(possibleCombination){
       		this.possibleCombination = possibleCombination;
       		for(var pawnId in possibleCombination){
       			var pawnElt = dojo.byId('pawn_'+pawnId);
       			dojo.setStyle(pawnElt, {cursor : 'pointer'});
   				pawnElt.onclickCombinationListener = dojo.connect(pawnElt, 'onclick', this, 'onChooseCombinationClick');
       		}
       },
       
       
       updatePawnTarget: function(possiblePawns){
       		this.possiblePawns = possiblePawns;
       		for(var pawnId in possiblePawns){
       			var pawnElt = dojo.byId('pawn_'+pawnId);
       			dojo.setStyle(pawnElt, {cursor : 'pointer'});
   				pawnElt.onclickTargetListener = dojo.connect(pawnElt, 'onclick', this, 'onChooseTargetClick');
       		}
       },
       
       
       updateSwitchedPawnsTarget: function(){
			
			var me = this;
	
			dojo.query(".pawn").forEach(function(item, index, array) {
				dojo.setStyle(item, {cursor : 'pointer'});
				item.clickListener = dojo.connect(item, 'onclick', me, 'onChoosePawnToSwitchClick');
			});
	
		},
       
       
       showChosenCard : function(cardId){
       		var bgPosition = (cardId - 1) * -100;
       		dojo.setStyle("chosenCard", {backgroundPosition : bgPosition+"px", display:"block"});
       },
       
       /*hideDeck : function(){
       		$("deck").style.display = "none";
       },
       
       showDeck : function(){
       		$("deck").style.display = "block";
       },*/
       
       
       movePawn : function(pawnId, tileId){
       		
            dojo.fadeOut({
            	node:"pawn_"+pawnId,
            	onEnd : function(){
		        	dojo.place( "pawn_"+ pawnId, 'pawnTile_'+ tileId, "last" );
            		dojo.fadeIn({node:'pawn_'+pawnId}).play();
            	}
            }).play();
       },

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );
                
                break;
           */
           
           	case 'useCard':
           		//this.hideDeck();
           		if(args.active_player == this.player_id){
           			this.updatePossibleMoves( args.args.possibleMoves );
           			this.showChosenCard(args.args.cardId);	
           		}
           		break;
           	case 'chooseCard':
           	
           		if(args.active_player == this.player_id){
           			var me = this;
           			dojo.query(".card.moveCard").forEach(
				          function(item, index, array){
				               item.clickListener = dojo.connect(item, 'onclick', me, 'onCardClick');
				          }
				     );
           			dojo.query('.card.moveCard').addClass("canChoose");
           		}
           		break;
           		
           	case 'chooseCombination':
           	
           		if(args.active_player == this.player_id){
           			this.updateCombination(args.args.pawnsCombination);
           		}
           		
           		break;
           		
           	case 'choosePowerCard':
           	
           		if(args.active_player == this.player_id){
           			var me = this;
           			dojo.query(".card.powerCard").forEach(
				          function(item, index, array){
				               item.clickListener = dojo.connect(item, 'onclick', me, 'onPowerCardClick');
				          }
				     );
           			dojo.query('.card.powerCard').addClass("canChoose");
           		}
           		break;
           		
           	case 'chooseTargetPlayer':
           	
           		if(args.active_player == this.player_id){
					this.updatePawnTarget(args.args.possiblePawns);	
           		}
           	
           		break;
           		
           	case 'chooseSwitchedPawns':
           	
           		if(args.active_player == this.player_id){
           			this.updateSwitchedPawnsTarget();
           		}
           	
           		break;
           
            case 'dummmy':
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                
                break;
           */
          	case 'chooseCard':
      			dojo.query(".card.moveCard").forEach(
			          function(item, index, array){
			          	if(item.clickListener){
			          		dojo.disconnect(item.clickListener);
			          	}	
			          }
			    );
		     	dojo.query('.card.moveCard').removeClass("canChoose");
          		break;
           
           
           	case 'chooseCombination':
           		
           		//disconnect listeners
	       		for(var pawnId in this.possibleCombination){
	       			var pawnElt = dojo.byId('pawn_'+pawnId);
	       			dojo.setStyle(pawnElt, {cursor : 'auto'});
	       			if(pawnElt.onclickCombinationListener){
	       				dojo.disconnect(pawnElt.onclickCombinationListener);	
	       			}
	       		}
           		
           		break;
           		
           	case 'choosePowerCard':
           	
           		dojo.query(".card.powerCard").forEach(
			          function(item, index, array){
			          	if(item.clickListener){
			          		dojo.disconnect(item.clickListener);
			          	}	
			          }
			    );
		     	dojo.query('.card.powerCard').removeClass("canChoose");
           		break;
           		
       		case 'chooseTargetPlayer':
       		
       			//disconnect listeners
	       		for(var pawnId in this.possiblePawns){
	       			var pawnElt = dojo.byId('pawn_'+pawnId);
	       			dojo.setStyle(pawnElt, {cursor : 'auto'});
	       			if(pawnElt.onclickCombinationListener){
	       				dojo.disconnect(pawnElt.onclickTargetListener);	
	       			}
	       		}
       		
       			break;
           
           case 'chooseSwitchedPawns':
           		
           		dojo.query(".pawn").forEach(function(item,index,array){
	       			dojo.setStyle(item, {cursor : 'auto'});
	       			if(item.clickListener){
		          		dojo.disconnect(item.clickListener);
		          	}
           		});
           		dojo.query('.pawn.selectedPawn').removeClass("selectedPawn");
           		
           		break;
           
           
            case 'dummmy':
                break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );
                      
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
/*               
                 Example:
 
                 case 'myGameState':
                    
                    // Add 3 action buttons in the action status bar:
                    
                    this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' ); 
                    this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' ); 
                    this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' ); 
                    break;
*/

				case "chooseCard":
					this.addActionButton( 'skipCardButton', _('Skip'), 'onSkipClick' ); 
				
					break;
					
					
				case "choosePowerCard":
					this.addActionButton( 'skipPowerCardButton', _('Skip'), 'onSkipPowerCardClick' );
				
					break;

                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */


        ///////////////////////////////////////////////////
        //// Player's action
        
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */
       
       /**
        * Method called when the player click on a card
        */
       onCardClick: function(event){
       		// Stop this event propagation
			dojo.stopEvent( event );

			//get the card id
            var split = event.currentTarget.id.split('_');
            var cardId = split[1];
            

            var playerId = this.player_id;
            // Check that this action is possible at this moment
            if( this.checkAction( 'chooseCard' )) {            
                this.ajaxcall( "/ledernierpeuple/ledernierpeuple/chooseCard.html", {
                    playerId:playerId,
                    cardId:cardId
                }, this, function( result ) {} );
            
	            //remove the card chosen    
	            dojo.fadeOut({node: event.currentTarget.id,
	            			onEnd : function(){
	            				dojo.destroy("card_"+cardId);
	            			}
	            	}).play();
	            
            }            
       },
       
       /**
        * Method called when the use click on the Skip link during the choseCard state
        */
       onSkipClick: function(event){
       		// Stop this event propagation
			dojo.stopEvent( event );

            // Check that this action is possible at this moment
            if( this.checkAction( 'skipTurn' )) {
                this.ajaxcall( "/ledernierpeuple/ledernierpeuple/skipTurn.html", {}, this, function( result ) {} );
            }   
       },
       
              
       /**
        * Method called when the player click on one of his pawn to move it
        */
       onPawnClick: function(event){
       		// Stop this event propagation
			dojo.stopEvent( event );
			
			dojo.query(".possibleMove").removeClass("possibleMove");

			//get the pawn id
            var split = event.currentTarget.id.split('_');
            var pawnId = split[1];
            
            for(var idx in this.possibleMoves[pawnId]){
            	var possibleMove = this.possibleMoves[pawnId][idx];
            	
            	var moveTileId = possibleMove.tileId;
            	
            	dojo.query("#tile_"+moveTileId).addClass("possibleMove");
            	
            	var tileElt = $("tile_"+moveTileId);
            	if(!tileElt.onclickListener){
            		tileElt.onclickListener = new Array();
            	}
   				tileElt.onclickListener[tileElt.onclickListener.length] = dojo.connect(tileElt, 'onclick', this, 'onChooseTileClick');
   				
            }
            
            this.selectedPawnId = pawnId;
       },
       
       onChooseTileClick : function(event){
       		// Stop this event propagation
			dojo.stopEvent( event );

			//no pawn selected, do nothing
			if(!this.selectedPawnId){
				return;
			}
			if( this.checkAction( 'useCard' ) )    // Check that this action is possible at this moment
            {
	
				//get the tile id
	            var split = event.currentTarget.id.split('_');
	            var tileId = split[1];
	            
	            var partial = false;
	            //check if it's a partial move
	            for(var idx in this.possibleMoves[this.selectedPawnId]){
	            	var possibleMove = this.possibleMoves[this.selectedPawnId][idx];
            	
            		var moveTileId = possibleMove.tileId;
            		
            		if(moveTileId == tileId && possibleMove.partial){
            			partial = true;
            		}
	            }
	            
            	
            	this.ajaxcall( "/ledernierpeuple/ledernierpeuple/useCard.html", {
                    pawnId:this.selectedPawnId,
                    tileId:tileId,
                    partial:partial
                }, this, function( result ) {} );
                
                dojo.query(".pawn").forEach(function(node, index, nodelist){
					dojo.style(node, "cursor", "auto");
					//disconnect listener on the element
					if(node.onclickListener){
						dojo.disconnect(node.onclickListener);	
					}
				});
				
				dojo.query(".tile.possibleMove").forEach(function(node, index, nodelist){
					dojo.removeClass(node, "possibleMove");
					//disconnect listeners on the element
					if(node.onclickListener){
						for(var i = 0; i < node.onclickListener.length; i++){
							dojo.disconnect(node.onclickListener[i]);		
						}
					}
				});
				
				dojo.setStyle("chosenCard", {display:"none"});
            }
            
       },
       
       
       onChooseCombinationClick: function(event){
       		// Stop this event propagation
			dojo.stopEvent( event );
			
			if( this.checkAction( 'combinationChosen' ) )    // Check that this action is possible at this moment
            {

				//get the pawn id
	            var split = event.currentTarget.id.split('_');
	            var pawnId = split[1];
	            
	            this.ajaxcall( "/ledernierpeuple/ledernierpeuple/combinationChosen.html", {
	                    pawnId:pawnId
	                }, this, function( result ) {} );
           	}
       },
       
       /**
        * Called when the user click the Skip link in order to not use a power card
        */
       onSkipPowerCardClick: function(event){
       		// Stop this event propagation
			dojo.stopEvent( event );

            // Check that this action is possible at this moment
            if( this.checkAction( 'skipPowerCard' )) {
                this.ajaxcall( "/ledernierpeuple/ledernierpeuple/skipPowerCard.html", {}, this, function( result ) {} );
            }
       	
       },
       
       /**
        * Called when the user choose a power card to use
        */
       onPowerCardClick: function(event){
       		// Stop this event propagation
			dojo.stopEvent( event );

			//get the card id
            var split = event.currentTarget.id.split('_');
            var cardId = split[1];
            

            var playerId = this.player_id;
            // Check that this action is possible at this moment
            if( this.checkAction( 'choosePowerCard' )) {            
                this.ajaxcall( "/ledernierpeuple/ledernierpeuple/choosePowerCard.html", {
                    playerId:playerId,
                    cardId:cardId
                }, this, function( result ) {} );
            
            	var me = this;
	            //remove the card chosen    
	            dojo.fadeOut({node: event.currentTarget.id,
	            			onEnd : function(){
	            				dojo.destroy("powerCard_"+cardId);
	            			}
	            	}).play();
	            
            }            
       },
       
       /**
        * Called during the use of a power card when the player choose the targeted pawn
        */
       onChooseTargetClick:function(event){
       		// Stop this event propagation
			dojo.stopEvent( event );
			
			if( this.checkAction( 'chooseTarget' ) )    // Check that this action is possible at this moment
            {

				//get the pawn id
	            var split = event.currentTarget.id.split('_');
	            var pawnId = split[1];
	            
	            this.ajaxcall( "/ledernierpeuple/ledernierpeuple/chooseTarget.html", {
	                    pawnId:pawnId
	                }, this, function( result ) {} );
           	}
       },
       
       /**
        * Called when the player click on a pawn he want to switch with another
        */
       onChoosePawnToSwitchClick: function(event){
       		// Stop this event propagation
			dojo.stopEvent( event );
			
			var clickedPawnId = event.currentTarget.id;
			
			if(clickedPawnId == this.firstPawnId){
				dojo.query("#"+clickedPawnId).removeClass("selectedPawn");
				this.firstPawnId = null;
				return;
			}
			
			//first pawn the player click on
			if(this.firstPawnId == null){
				this.firstPawnId = clickedPawnId;
				dojo.query("#"+clickedPawnId).addClass("selectedPawn");
				return;
			}
			else{
				if( this.checkAction( 'chooseSwitchedPawns' ) )    // Check that this action is possible at this moment
	            {
	            	//get the first pawn id
	            	var firstPawnId = this.firstPawnId.split("_")[1];
	
					//get the second pawn id
		            var secondPawnId = event.currentTarget.id.split('_')[1];
		            
		            this.ajaxcall( "/ledernierpeuple/ledernierpeuple/chooseSwitchedPawns.html", {
		                    firstPawnId:firstPawnId,
		                    secondPawnId:secondPawnId
		                }, this, function( result ) {} );
	           	}
			}
			
       },
        
        /* Example:
        
        onMyMethodToCall1: function( evt )
        {
            console.log( 'onMyMethodToCall1' );
            
            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'myAction' ) )
            {   return; }

            this.ajaxcall( "/ledernierpeuple/ledernierpeuple/myAction.html", { 
                                                                    lock: true, 
                                                                    myArgument1: arg1, 
                                                                    myArgument2: arg2,
                                                                    ...
                                                                 }, 
                         this, function( result ) {
                            
                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)
                            
                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );        
        },        
        
        */

        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your ledernierpeuple.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
            
            dojo.subscribe('movePawn', this, "notif_movePawn");
            // Wait 2 sec after executing the movePawn handler
            this.notifqueue.setSynchronous( 'movePawn', 2000 );
            
            dojo.subscribe('newScores', this, "notif_newScores");
            
            dojo.subscribe('newNbCards', this, "notif_newNbCards");
            
            dojo.subscribe('newCards', this, "notif_newCards");
            
            dojo.subscribe('loseCards', this, "notif_loseCards");
            
            dojo.subscribe('teleportAfterMove', this, "notif_teleportAfterMove");
            // Wait 1 sec after executing the teleportAfterMove handler
            //this.notifqueue.setSynchronous( 'teleportAfterMove', 1000 );
               
            
            // TODO: here, associate your game notifications with local methods
            
            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            
            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            // 
        },  
        
        // TODO: from this point and below, you can write your game notifications handling methods
        
        notif_movePawn : function(notif){
        	console.log(notif);
        	
    		this.movePawn(notif.args.pawnId, notif.args.tileId);
        },
        
        notif_newScores: function( notif )
        {
            for( var player_id in notif.args.scores )
            {
                var newScore = notif.args.scores[ player_id ];
                this.scoreCtrl[ player_id ].toValue( newScore );
            }
        },
        
        notif_newNbCards: function( notif )
        {
            for( var player_id in notif.args.nbCards )
            {
                var nbCards = notif.args.nbCards[ player_id ];
                this.updateNbCards(player_id, nbCards);
            }
        },
        
        notif_newCards: function (notif){
        	if(notif.args.newCards){
        		this.putCards(notif.args.newCards);	
        	}
        },
        
        notif_loseCards: function(notif){
        	if(notif.args.losedCards){
        		this.removeCards(notif.args.losedCards);
        	}
        },
        
        notif_teleportAfterMove: function(notif){
        	var i =0;
		    for(; i < notif.args.teleport.length;i++ ) {
		    	var pawnId = notif.args.teleport[i].pawnId;
		    	var tileId = notif.args.teleport[i].tileId;
                this.movePawn(pawnId, tileId);
            }    	
        }
        
        /*
        Example:
        
        notif_cardPlayed: function( notif )
        {
            console.log( 'notif_cardPlayed' );
            console.log( notif );
            
            // Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call
            
            // TODO: play the card in the user interface.
        },    
        
        */
   });             
});

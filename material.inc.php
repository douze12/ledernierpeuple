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
 * material.inc.php
 *
 * LeDernierPeuple game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */


/*

Example:

$this->card_types = array(
    1 => array( "card_name" => ...,
                ...
              )
);

*/
/*
$this->speciesNames = array(
	1 => clienttranslate("Peuple 1"),
	2 => clienttranslate("Peuple 2"),
	3 => clienttranslate("Peuple 3"),
	4 => clienttranslate("Peuple 4"),
	5 => clienttranslate("Peuple 5"),
	6 => clienttranslate("Peuple 6")
);*/

// For translation purposes
$this->card_types = array(
	clienttranslate("bandit"),
	clienttranslate("blackMagic"),
	clienttranslate("mace"),
	clienttranslate("curse"),
	clienttranslate("barter"),
	clienttranslate("thief"),
	clienttranslate("defense"),
	clienttranslate("switch"),
	clienttranslate("luck"),
	clienttranslate("strength"),
	clienttranslate("heal"),
	clienttranslate("speed"),	
);





















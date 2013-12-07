Diceware
========

A password, passphrase, and pin generator, using the diceware method

## Description

Arnold Reinhold proposed the [Diceware](http://world.std.com/~reinhold/diceware.html) method of generating passphrases: start with a dictionary of 7776 common words and use dice rolls to pick words from that dictionary, to form the phrase.

Using actual dice is preferred, for true security and true randomness. However, I find that tedious and am not quite paranoid enough to go through the effort of doing so. I'm creating these programs, to use the method while taking the grunt work out of the method.

I'm using the [Diceware 8k list](http://world.std.com/%7Ereinhold/dicewarefaq.html#computer), which is optimized for computer selection of words. I'm using [RANDOM.ORG](http://www.random.org) to generate random numbers and simulate dice rolls.

## Fork

This repository has been forked from http://github.com/jmartindf/diceware .

List of modifications:

* Removed all unused code.
* Include support for dictionaries in languages other than english.
* Added namespaces and PSR-0 compatibility.
* Include composer support.

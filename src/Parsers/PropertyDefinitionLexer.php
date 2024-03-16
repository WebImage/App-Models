<?php

namespace WebImage\Models\Parsers;

use Doctrine\Common\Lexer\AbstractLexer;

class PropertyDefinitionLexer extends AbstractLexer
{
	const T_STRING               = 'T_STRING';
	const T_COMMENT              = 'T_COMMENT';
	const T_HASH                 = 'T_HASH';
	const T_AT                   = 'T_AT';
	const T_OPEN_PAREN           = 'T_OPEN_PAREN';
	const T_CLOSE_PAREN          = 'T_CLOSE_PAREN';
	const T_PERIOD               = 'T_PERIOD';
	const T_OPEN_BRACKET         = 'T_OPEN_BRACKET';
	const T_CLOSE_BRACKET        = 'T_CLOSE_BRACKET';
	const T_OPEN_SQUARE_BRACKET  = 'T_OPEN_SQUARE_BRACKET';
	const T_CLOSE_SQUARE_BRACKET = 'T_CLOSE_SQUARE_BRACKET';
	const T_FORWARD_ARROW        = 'T_FORWARD_ARROW';
	const T_QUESTION             = 'T_QUESTION';
	const T_ASTERISK             = 'T_ASTERISK';
	const T_EXCLAMATION          = 'T_EXCLAMATION';
	const T_PLUS                 = 'T_PLUS';
	const T_COMMA                = 'T_COMMA';

	protected function getCatchablePatterns()
	{
		return [
			'->',
			'[#@\(\)\.\[\]{}\*\?\*\!\+,]',
			'\/\/.*$'
		];
	}

	protected function getNonCatchablePatterns()
	{
		return [
			'\s+'
		];
	}

	protected function getType(&$value)
	{
		switch($value) {
			case '#':
				return self::T_HASH;
			case '@':
				return self::T_AT;
			case '(':
				return self::T_OPEN_PAREN;
			case ')':
				return self::T_CLOSE_PAREN;
			case '.':
				return self::T_PERIOD;
			case '{':
				return self::T_OPEN_BRACKET;
			case '}':
				return self::T_CLOSE_BRACKET;
			case '[':
				return self::T_OPEN_SQUARE_BRACKET;
			case ']':
				return self::T_CLOSE_SQUARE_BRACKET;
			case '->':
				return self::T_FORWARD_ARROW;
			case '?':
				return self::T_QUESTION;
			case '*':
				return self::T_ASTERISK;
			case '!':
				return self::T_EXCLAMATION;
			case '+':
				return self::T_PLUS;
			case ',':
				return self::T_COMMA;
			default:
				if (substr($value, 0, 2) == '//') {
					$value = trim(substr($value, 2));
					return self::T_COMMENT;
				}
				return self::T_STRING;
		}
	}
}

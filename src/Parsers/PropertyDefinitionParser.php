<?php

namespace WebImage\Models\Parsers;

class PropertyDefinitionParser
{
	/**
	 * PropertyDefinitionParser constructor.
	 */
	public function __construct()
	{
		$this->lexer = new PropertyDefinitionLexer();
	}

	public function parse(string $str): array
	{
		$this->lexer->setInput($str);

		$propertyInfo = [
			'type' => '',
			'primaryKey' => null,
			'generationStrategy' => null,
			'multiple' => false,
			'comment' => '',
			'required' => null,
			'size' => null,
			'size2' => null,
			'reference' => [
				'targetType' => null,
				'path' => [],
				'selectProperty' => null
			]
		];

		if ($this->lexer->glimpse()['type'] == PropertyDefinitionLexer::T_EXCLAMATION) {
			$this->lexer->moveNext();
			$propertyInfo['required'] = true;
		}

		if ($this->lexer->glimpse()['type'] == PropertyDefinitionLexer::T_HASH) {
			$this->lexer->moveNext();
			$propertyInfo['type'] = 'virtual';
			$this->processProperty($propertyInfo);
		} else {
			$this->processProperty($propertyInfo);
		}

		return $propertyInfo;
	}

	private function processProperty(&$propertyInfo)
	{
		$this->lexer->moveNext();

		$lookahead = $this->lexer->lookahead;

		if ($lookahead['type'] != PropertyDefinitionLexer::T_STRING) {
			throw new \RuntimeException('Unexpected ' . $lookahead['type'] . ' at ' . $lookahead['position'] . '. Expecting ' . PropertyDefinitionLexer::T_STRING);
		}

		// Type name
		if ($propertyInfo['type'] == 'virtual') {
			$propertyInfo['reference']['targetType'] = $lookahead['value'];
		} else {
			$propertyInfo['type'] = $lookahead['value'];
		}

		while ($this->lexer->moveNext()) {

			$lookahead = $this->lexer->lookahead;
//			echo $lookahead['type'] . ' - ' . $lookahead['value'] . PHP_EOL;

			switch ($lookahead['type']) {
				case PropertyDefinitionLexer::T_COMMENT:
					$propertyInfo['comment'] = $lookahead['value'];
					break;
				case PropertyDefinitionLexer::T_PERIOD:
					$this->processDotNotation($propertyInfo);
					break;
//				case PropertyDefinitionLexer::T_OPEN_BRACKET:
//					$this->processOpenBracket($propertyInfo);
//					break;
				case PropertyDefinitionLexer::T_OPEN_SQUARE_BRACKET:
					$this->processOpenSquareBracket($propertyInfo);
					break;
				case PropertyDefinitionLexer::T_OPEN_PAREN:
					if ($propertyInfo['type'] == 'virtual') {
						$this->processVirtualParenthesis($propertyInfo);
					} else {
						$this->processTypeParenthesis($propertyInfo);
					}
					break;
				case PropertyDefinitionLexer::T_FORWARD_ARROW:
					$this->lexer->moveNext();
					if (!$this->lexer->isNextToken(PropertyDefinitionLexer::T_STRING)) {
						throw new \RuntimeException('Unexpected ' . $this->lexer->lookahead['type'] . ' at ' . $this->lexer->lookahead['position']);
					}
					$propertyInfo['reference']['selectProperty'] = $this->lexer->lookahead['value'];
					break;
				case PropertyDefinitionLexer::T_PLUS:
					$propertyInfo['generationStrategy'] = 'AUTO';
					break;
				default:
					print_r($lookahead);
					throw new \RuntimeException('Unexpected ' . $lookahead['type'] . ' at ' . $lookahead['position']);
			}
		}
	}

	private function processDotNotation(&$propertyInfo)
	{
		$this->lexer->moveNext();

		if (!$this->lexer->isNextToken(PropertyDefinitionLexer::T_STRING)) {
			throw new \RuntimeException('Missing dot notation property');
		}

		$lookahead = $this->lexer->lookahead;

		$propertyInfo['reference']['reverseProperty'] = $lookahead['value'];
	}

	private function processOpenBracket(&$propertyInfo)
	{
		$this->lexer->moveNext();
		print_r($this->lexer->lookahead);
		die(__FILE__ . ':' . __LINE__ . PHP_EOL);
	}

	private function processOpenSquareBracket(&$propertyInfo)
	{
		$this->lexer->moveNext();

		if (!$this->lexer->isNextToken(PropertyDefinitionLexer::T_CLOSE_SQUARE_BRACKET)) {
			throw new \RuntimeException('Unexpected ' . $this->lexer['type'] . ' found at ' . $this->lexer->lookahead['position'] . '. Square brackets must be empty');
		}

		$propertyInfo['multiple'] = true;
	}

	private function processVirtualParenthesis(&$propertyInfo)
	{
		$this->lexer->moveNext();

		if (!$this->lexer->isNextToken(PropertyDefinitionLexer::T_STRING)) {
			throw new \RuntimeException('Expacting Type after open parenthesis');
		}

		$position = $this->lexer->lookahead['position'];
		$ix = 0;
		$key = 'type';
		$path = [];
		/**
		 * path = [
		 * 	type
		 *  property
		 * ]
		 */
		while (!$this->lexer->isNextToken(PropertyDefinitionLexer::T_CLOSE_PAREN)) {
			switch($this->lexer->lookahead['type']) {
				case PropertyDefinitionLexer::T_STRING:
					if ($key == 'type') {
						$path[$ix] = [
							'type' => $this->lexer->lookahead['value'],
							'property' => null,
							'forwardProperty' => null
						];
					} else {
						echo 'Path: ' . $key . ' ' . $this->lexer->lookahead['value'] . PHP_EOL;
						$path[$ix][$key] = $this->lexer->lookahead['value'];
					}
					break;
				case PropertyDefinitionLexer::T_PERIOD:
					if ($key == 'property') {
						throw new \RuntimeException('Multiple values in dot notation, e.g. type.property.property are not supported at ' . $this->lexer->lookahead['position']);
					}
					$key = 'property';
					break;
				case PropertyDefinitionLexer::T_FORWARD_ARROW:
					$key = 'forwardProperty';
					break;
				default:
					throw new \RuntimeException('Unexpected ' . $this->lexer->lookahead['type'] . ' at ' . $this->lexer->lookahead['position']);
			}

			if (!$this->lexer->moveNext()) {
				throw new \RuntimeException('Parenthesis at ' . $position . ' were never closed');
			}
		}

		$propertyInfo['reference']['path']   = $path;
	}

	private function processTypeParenthesis(&$propertyInfo)
	{
		$this->lexer->moveNext();
		$valueKey = 'size';
		$position = $this->lexer->lookahead['position'];

		while (!$this->lexer->isNextToken(PropertyDefinitionLexer::T_CLOSE_PAREN)) {
			switch($this->lexer->lookahead['type']) {
				case PropertyDefinitionLexer::T_STRING:
					$value = $this->lexer->lookahead['value'];
					if (is_numeric($value)) {
						$value = (int) $value;
						if ($propertyInfo[$valueKey] !== null) throw new \RuntimeException('Expecting ' . PropertyDefinitionLexer::T_COMMA . ' or ' . PropertyDefinitionLexer::T_CLOSE_PAREN . ' but found ' . $this->lexer->lookahead['type'] . ' at ' . $this->lexer->lookahead['position']);
						$propertyInfo[$valueKey] = $value;
						if ($valueKey == 'size') $valueKey = 'size2';
					} else {
						throw new \RuntimeException('Unexpected ' . $this->lexer->lookahead['type'] . ' at ' . $this->lexer->lookahead['position'] . '.  Expecting numeric value.');
					}
					break;
				case PropertyDefinitionLexer::T_COMMA:
					break;
				default:
					throw new \RuntimeException('Unexpected ' . $this->lexer->lookahead['type'] . ' at ' . $this->lexer->lookahead['position']);
			}

			if (!$this->lexer->moveNext()) {
				throw new \RuntimeException('Parenthesis at ' . $position . ' were never closed');
			}
		}
	}
}

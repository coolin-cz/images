<?php

namespace WebChemistry\Images\Template;

use Latte;
use Latte\CompileException;
use Latte\Compiler\Nodes\Php\Scalar\StringNode;
use Latte\Engine;

class Macros extends Latte\Extension {

	public function getTags():array{
		return ['img' => [$this, 'processImg']];
	}

	// {img path/to/img.jpg[, alias]}
	public function processImg(Latte\Compiler\Tag $tag, Latte\Compiler\TemplateParser $parser):Latte\Compiler\Node{
		if($tag->isNAttribute()){
			throw new CompileException('Attribute n:img is not supported.', $tag->position);
		}

		$tag->outputMode = $tag::OutputRemoveIndentation;
		$tag->expectArguments('asset');

		$asset = $tag->parser->parseUnquotedStringOrExpression();
		$tag->parser->stream->tryConsume(',');
		$args = $tag->parser->parseArguments();

		return new ImgNode($asset, $args);
	}

	/**
	 * @param Latte\MacroTokens $tokenizer
	 * @return array
	 */
	protected function parseArguments(Latte\MacroTokens $tokenizer) {
		$args = [];
		$tokenizer->nextToken();

		while (($tokens = $tokenizer->currentToken()) && $tokens[0] !== ')') {
			$this->skipWhitespaces($tokenizer);
			[$value,,$type] = $tokenizer->currentToken();
			if ($value === ',') {
				throw new \LogicException("Expected value, given '$value'.");
			}

			$args[] = trim($value, "'\"");
			$tokenizer->nextToken();
			$this->skipWhitespaces($tokenizer);
			[$value,,$type] = $tokenizer->currentToken();
			if ($value === ',') {
				$tokenizer->nextToken();
			}
		}

		return $args;
	}

	/**
	 * @param Latte\MacroTokens $tokenizer
	 * @return array
	 */
	protected function parseAliases(Latte\MacroTokens $tokenizer) {
		$aliases = [];

		while ($token = $tokenizer->nextToken()) {
			$this->skipWhitespaces($tokenizer);

			[$key,,$type] = $tokenizer->currentToken();

			$aliases[$key] = [];
			if ($type !== $tokenizer::T_SYMBOL) {
				throw new \LogicException("Alias must starts with identifier, given '$key'");
			}

			$this->skipWhitespaces($tokenizer);

			if (!$tokenizer->isNext()) {
				break;
			}
			$this->skipWhitespaces($tokenizer);
			[$value,,$type] = $tokenizer->nextToken();
			if ($value === '(') {
				$aliases[$key] = $this->parseArguments($tokenizer);
				if (!$tokenizer->isNext()) {
					break;
				}
				[$value,,$type] = $tokenizer->nextToken();
			}
			if ($value !== ',') {
				throw new \LogicException("Next alias must continue with dash, given '$value'.");
			}
		}

		return $aliases;
	}


	/**
	 * @param Latte\MacroNode $node
	 * @param Latte\PhpWriter $writer
	 * @return string
	 */
	public function beginImg(Latte\MacroNode $node, Latte\PhpWriter $writer) {
		$tokenizer = $node->tokenizer;
		$imageName = $tokenizer->fetchWord();
		$aliases = $this->parseAliases($tokenizer);

		return $writer->write('
			$_res = $this->global->imageStorageFacade->create(%word, %var);' .
			'echo $this->global->imageStorageFacade->link($_res);',
		$imageName,
		$aliases
		);
	}

	/**
	 * @param Latte\MacroNode $node
	 * @param Latte\PhpWriter $writer
	 * @return string
	 */
	public function attrImg(Latte\MacroNode $node, Latte\PhpWriter $writer) {
		return $writer->write(
			'echo " " . %word . "\""; %raw echo "\"";',
			$node->htmlNode->name === 'a' ? 'href=' : 'src=',
			$this->beginImg($node, $writer)
		);
	}

}

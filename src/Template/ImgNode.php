<?php
declare(strict_types=1);

namespace WebChemistry\Images\Template;

use Latte\Compiler\Nodes\Php\Expression\ArrayNode;
use Latte\Compiler\Nodes\Php\ExpressionNode;
use Latte\Compiler\PrintContext;

class ImgNode extends \Latte\Compiler\Nodes\StatementNode{

	public function __construct(
		protected ExpressionNode $asset,
		protected ArrayNode $args
	){}

	public function print(PrintContext $context):string{
		return $context->format(
			<<<'PHP'
			$l_args = array_flip(%node);
			foreach($l_args as $key => $val){
				$l_args[$key] = [];
			}
			$l_res = $this->global->imageStorageFacade->create(%node, $l_args);
			echo $this->global->imageStorageFacade->link($l_res);
			PHP,
			$this->args,
			$this->asset
		);
	}

	public function &getIterator(): \Generator{
		//WTF???
		if(false){
			yield;
		}
	}
}

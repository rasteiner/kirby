<?php

namespace Kirby\Query\AST;

use Kirby\Query\Visitors\Visitor;

/**
 * @package   Kirby Query
 * @author    Roman Steiner <>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://opensource.org/licenses/MIT
 * @since     6.0.0
 */
class GlobalFunctionNode extends IdentifierNode
{
	public function __construct(
		public string $name,
		public ArgumentListNode $arguments,
	) {
	}

	/**
	 * Replace escaped dots with real dots
	 */
	public function name(): string
	{
		return str_replace('\.', '.', $this->name);
	}

	public function resolve(Visitor $visitor): mixed
	{
		$name      = $this->name();
		$arguments = $this->arguments->resolve($visitor);
		return $visitor->function($name, $arguments);
	}
}

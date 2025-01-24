<?php

namespace Kirby\Query\Runners;

use Closure;
use Kirby\Query\Parser\Parser;
use Kirby\Query\Query;
use Kirby\Query\Visitors\Interpreter;

/**
 * Runner that caches the AST in memory
 *
 * @package   Kirby Query
 * @author    Roman Steiner <roman@toastlab.ch>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://opensource.org/licenses/MIT
 * @since     6.0.0
 */
class Interpreted extends Runner
{
	/**
	 * Creates a runner for the Query
	 */
	public static function for(Query $query): static
	{
		return new static(
			functions: $query::$entries,
			interceptor: $query->intercept(...),
			cache: $query::$cache
		);
	}

	protected function resolver(string $query): Closure
	{
		// load closure from cache
		if (isset($this->cache[$query]) === true) {
			return $this->cache[$query];
		}

		// parse query and generate closure
		$parser = new Parser($query);
		$ast    = $parser->parse();
		$self   = $this;

		return $this->cache[$query] = function (array $binding) use ($self, $ast) {
			$visitor = new Interpreter(
				functions: $self->functions,
				context: $binding,
				interceptor: $self->interceptor
			);

			return $ast->resolve($visitor);
		};
	}

	/**
	 * Executes a query within a given data context
	 *
	 * @param array $context Optional variables to be passed to the query
	 *
	 * @throws \Exception when query is invalid or executor not callable
	 */
public function run(string $query, array $context = []): mixed
{
	// try resolving query directly from data context or functions
	$entry = Runtime::get($query, $context, $this->functions, false);

	if ($entry !== false) {
		return $entry;
	}

	return $this->resolver($query)($context);
}
}

<?php

namespace Kirby\Panel\Ui\Buttons;

use Closure;
use Kirby\Cms\App;
use Kirby\Cms\ModelWithContent;
use Kirby\Panel\Panel;
use Kirby\Panel\Ui\Button;
use Kirby\Toolkit\Controller;

/**
 * A view button is a UI button, by default small in size and filles,
 * that optionally defines options for a dropdown
 *
 * @package   Kirby Panel
 * @author    Nico Hoffmann <nico@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://getkirby.com/license
 * @since     5.0.0
 * @internal
 */
class ViewButton extends Button
{
	public function __construct(
		public string $component = 'k-view-button',
		public readonly ModelWithContent|null $model = null,
		public array|null $badge = null,
		public string|null $class = null,
		public string|bool|null $current = null,
		public string|null $dialog = null,
		public bool $disabled = false,
		public string|null $drawer = null,
		public bool|null $dropdown = null,
		public string|null $icon = null,
		public string|null $link = null,
		public array|string|null $options = null,
		public bool|string $responsive = true,
		public string|null $size = 'sm',
		public string|null $style = null,
		public string|null $target = null,
		public string|array|null $text = null,
		public string|null $theme = null,
		public string|array|null $title = null,
		public string $type = 'button',
		public string|null $variant = 'filled',
	) {
	}

	/**
	 * Creates new view button by looking up
	 * the button in all areas, if referenced by name
	 * and resolving to proper instance
	 */
	public static function factory(
		string|array|Closure $button,
		string|int|null $name = null,
		string|null $view = null,
		ModelWithContent|null $model = null,
		array $data = []
	): static|null {
		// referenced by name
		if (is_string($button) === true) {
			$button = static::find($button, $view);
		}

		// turn closure into actual button (object or array)
		$button = static::resolve($button, $model, $data);

		if (
			$button === null ||
			$button instanceof ViewButton
		) {
			return $button;
		}

		// flatten definition array into list of arguments for this class
		$button = static::normalize($button);

		// if button definition has a name, use it for the component name
		if (is_string($name) === true) {
			// If this specific component does not exist,
			// `k-view-buttons` will fall back to `k-view-button` again
			$button['component'] ??= 'k-' . $name . '-view-button';
		}

		return new static(...$button, model: $model);
	}

	/**
	 * Finds a view button by name
	 * among the defined buttons from all areas
	 */
	public static function find(
		string $name,
		string|null $view = null
	): array|Closure {
		// collect all buttons from areas
		$buttons = Panel::buttons();

		// try to find by full name (view-prefixed)
		if ($view && $button = $buttons[$view . '.' . $name] ?? null) {
			return $button;
		}

		// try to find by just name
		if ($button = $buttons[$name] ?? null) {
			return $button;
		}

		// assume it must be a custom view button component
		return ['component' => 'k-' . $name . '-view-button'];
	}

	/**
	 * Transforms an array to be used as
	 * named arguments in the constructor
	 * @internal
	 */
	public static function normalize(array $button): array
	{
		// if component and props are both not set, assume shortcut
		// where props were directly passed on top-level
		if (
			isset($button['component']) === false &&
			isset($button['props']) === false
		) {
			return $button;
		}

		// flatten array
		if ($props = $button['props'] ?? null) {
			$button = [...$props, ...$button];
			unset($button['props']);
		}

		return $button;
	}

	public function props(): array
	{
		// helper for props that support Kirby queries
		$resolve = fn ($value) =>
			$value ?
			$this->model?->toSafeString($value) ?? $value :
			null;

		return [
			...parent::props(),
			'dialog'  => $resolve($this->dialog),
			'drawer'  => $resolve($this->drawer),
			'link'    => $resolve($this->link),
			'options' => $this->options
		];
	}

	/**
	 * Transforms a closure to the actual view button
	 * by calling it with the provided arguments
	 * @internal
	 */
	public static function resolve(
		Closure|array $button,
		ModelWithContent|null $model = null,
		array $data = []
	): static|array|null {
		if ($button instanceof Closure) {
			$kirby      = App::instance();
			$controller = new Controller($button);

			if ($model instanceof ModelWithContent) {
				$data = [
					'model'             => $model,
					$model::CLASS_ALIAS => $model,
					...$data
				];
			}

			$button = $controller->call(data: [
				'kirby' => $kirby,
				'site'  => $kirby->site(),
				'user'  => $kirby->user(),
				...$data
			]);
		}

		return $button;
	}
}

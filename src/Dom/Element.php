<?php

namespace Migrate\Dom;


use DOMDocument;
use DOMElement;

class Element implements ElementInterface
{
	protected $tag;
	protected $element;

	public function factory(DOMElement $element): DOMElement
    {
        $this->tag = $element->tagName;
        $this->element = $element;
        return $this->get();
    }

	/**
	 * Returns element with applied business rules
	 *
	 * @return DOMElement
	 */
	protected function get(): DOMElement
	{
		switch ($this->tag) {
			case 'a':
				return $this->link();
			case 'img':
				return $this->image();
			case 'iframe':
				return $this->iframe();

			// Block
			case 'p':
			case 'div':
			case 'section':
			case 'header':
			case 'footer':
			case 'main':
			case 'aside':
			case 'article':
				return $this->basic('p');

			// Inline-block
			case 'font':
				return $this->basic('span');

			// Headings
			case 'h1':
				return $this->basic('h2');

			// Obsolete elements
			case 'center':
				return $this->basic('p', null, 'text-align: center;');
			case 'strike':
				return $this->basic('s');
			case 'nobr':
				return $this->basic('span', null, 'white-space: nowrap;');

			default:
				return $this->basic();
		}
	}

	/**
	 * Creates a new element based on supplied tag,
	 * if none given then uses old element tag
	 *
	 * @param string|null $tag
	 * @param string|null $class
	 * @param string|null $style
	 * @return DOMElement
	 */
	protected function basic(?string $tag = null, ?string $class = null, ?string $style = null): DOMElement
	{
        /** @var \DOMDocument $document */
		$document = $this->element->ownerDocument;

		if (is_null($tag)) {
			$tag = $this->tag;
		}

		$element = $document->createElement($tag, null);

		if (!is_null($class)) {
			$element->setAttribute('class', $class);
		}

		if (!is_null($style)) {
			$element->setAttribute('style', $style);
		}

		return $element;
	}

	/**
	 * Creates a new image element
	 *
	 * @return DOMElement
	 */
	protected function image(): DOMElement
	{
	    /** @var DOMDocument $document */
		$document = $this->element->ownerDocument;
		/** @var DOMElement $image */
		$image = $this->element;

		$src = (string)$image->getAttribute('src');
		$alt = (string)$image->getAttribute('alt');
		$title = (string)$image->getAttribute('title');
		$align = (string)$image->getAttribute('align');

		$element = $document->createElement('img', null);
		$element->setAttribute('src', $src);

		if (!empty($alt)) {
			$element->setAttribute('alt', $alt);
		}

		if (!empty($title)) {
			$element->setAttribute('title', $title);
		}

		if (!empty($align) && in_array($align, ['left', 'right'])) {
			$class = ($align === 'right') ? 'mceAlignRight' : 'mceAlignLeft';
			$element->setAttribute('class', $class);
		}

		return $element;
	}

	/**
	 * Creates a new link (tag: a) element
	 *
	 * @return DOMElement
	 */
	protected function link(): DOMElement
	{
        /** @var \DOMDocument $document */
        $document = $this->element->ownerDocument;
        /** @var DOMElement $link */
		$link = $this->element;

		$href = (string)$link->getAttribute('href');
		$target = (string)$link->getAttribute('target');
		$title = (string)$link->getAttribute('title');

		$element = $document->createElement('a', null);

		// Remove all JavaScript from href attribute
		$href = (strpos(strtolower($href), 'javascript') === false) ? $href : "#";
		$element->setAttribute('href', $href);

		if (!empty($target) && in_array($target, ['_blank', '_self', '_parent', '_top'])) {
			$element->setAttribute('target', $target);
		}

		if (!empty($title)) {
			$element->setAttribute('title', $title);
		}

		return $element;
	}

	/**
	 * Creates a new iframe element
	 *
	 * @return DOMElement
	 */
	protected function iframe(): DOMElement
	{
        /** @var \DOMDocument $document */
        $document = $this->element->ownerDocument;
        /** @var DOMElement $iframe */
		$iframe = $this->element;

		$src = (string)$iframe->getAttribute('src');
		$width = (string)$iframe->getAttribute('width');
		$height = (string)$iframe->getAttribute('height');
		$style = '';

		$element = $document->createElement('iframe', null);
		$element->setAttribute('src', $src);

		if (!empty($width)) {
			$style .= "width: {$width}px;";
		}

		if (!empty($height)) {
			$style .= "height: {$height}px;";
		}

		if (!empty($style)) {
			$element->setAttribute('style', $style);
		}

		return $element;
	}
}

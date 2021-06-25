<?php

namespace Migrate\Dom;


use DOMElement;

interface ElementInterface
{
    public function factory(DOMElement $element): DOMElement;
}

<?php

namespace Migrate\Dom;


use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMNode;
use DOMProcessingInstruction;
use Exception;

class Dom
{
    protected $document;
    protected $removeTags = ['script', 'style'];

    public function __construct(string $html)
    {
        $html = $this->utf8Encode($html);

        if (empty($html)) {
            throw new Exception("HTML given is empty");
        }

        $this->document = new DOMDocument();
        $this->document->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
    }

    public function getElementsByTagName(string $tag): DOMNodeList
    {
        return $this->document->getElementsByTagName($tag);
    }

    /**
     * Returns the first element of given tag
     *
     * @param string $tag
     * @return DOMElement|null
     */
    public function getElementByTagName(string $tag): ?DOMElement
    {
        $nodeList = $this->getElementsByTagName($tag);
        if ($nodeList->length > 0) {
            /** @var DOMElement $element */
            $element = $nodeList->item(0);
            return $element;
        }
        return null;
    }

    /**
     *
     */
    protected function parse(): void
    {
        $total = $this->count();
        $index = 0;
        while($index < $total) {
            $node = $this->getNode($index);
            if ($node instanceof DOMElement) {
                $newElement = (new Element())->factory($node);
                $this->insertValue($newElement, $node);
                $node->parentNode->replaceChild($newElement, $node);
            }
            $index++;
        }
    }

    /**
     * Returns the node at specified index
     *
     * @param int $index
     * @return DOMNode
     */
    public function getNode(int $index = 0): DOMNode
    {
        $body = $this->getBody();
        return $this->getNodeFromNodeList($body->childNodes, $index);
    }

    protected function getNodeFromNodeList(DOMNodeList $nodeList, int $index, int $offset = 0)
    {
        $selectedNode = null;
        /** @var DOMNode $node */
        foreach ($nodeList as $node) {
            $subCount = $offset + $this->countNodes($node->childNodes);
            // Our node is here!
            if ($subCount > $index) {
                return $this->getNodeFromNodeList($node->childNodes, $index, $offset);
            } else {
                $offset = $subCount;
            }

            if ($offset === $index) {
                $selectedNode = $node;
                break;
            }
            $offset++;
        }
        return $selectedNode;
    }

    /**
     * Returns total count of nodes in document.
     *
     * @return int
     */
    protected function count(): int
    {
        return $this->countNodes($this->getBody()->childNodes);
    }

    /**
     * Counts nodes in node list recursively
     *
     * @param DOMNodeList|null $nodeList
     * @param int $count
     * @return int
     */
    protected function countNodes(?DOMNodeList $nodeList, int $count = 0): int
    {
        if (is_null($nodeList)) {
            return $count;
        }
        /** @var DOMNode $node */
        foreach ($nodeList as $node) {
            $count = $this->countNodes($node->childNodes, $count);
            $count++;
        }
        return $count;
    }

    /**
     * Return the body node of the HTML
     *
     * @return DOMNode|null
     */
    protected function getBody(): ?DOMNode
    {
        return $this->document->getElementsByTagName('body')->item(0);
    }

    /**
     * Goes through every node in the document recursively and removes unwanted
     * elements.
     *
     * Each time an element is removed the node list is rebuilt and run again.
     * This behavior is necessary, because the previously given node list can
     * change or break when an element has been removed.
     */
    protected function clean(): void
    {
        $keepGoing = true;
        $nodeList = $this->getBody()->childNodes;
        while($keepGoing) {
            $keepGoing = $this->cleanElements($nodeList);
            // Rebuild the node list
            $nodeList = $this->getBody()->childNodes;
        }
    }

    /**
     * Helper function
     * Looks for unwanted elements and removes a single element.
     * Returns true if element was removed, false otherwise.
     *
     * @param DOMNodeList $nodeList
     * @return bool
     */
    protected function cleanElements(DOMNodeList $nodeList): bool
    {
        /** @var DOMNode $node */
        foreach ($nodeList as $node) {

            // If node has children then we need to go deeper
            if ($node->hasChildNodes()) {
                $this->cleanElements($node->childNodes);
            }

            // Removes all processing instructions like PHP og XML syntax for example
            if ($node instanceof DOMProcessingInstruction) {
                $this->remove($node);
                return true;
            }

            // Checks if node is and regular HTML element and removes unwanted tags
            if ($node instanceof DOMElement && in_array($node->tagName, $this->removeTags)) {
                $this->remove($node);
                return true;
            }
        }
        return false;
    }

    /**
     * Returns parsed HTML as string
     *
     * @return string
     */
    public function getHTML(): string
    {
        $this->clean();
        $this->parse();
        /** @var DOMElement $body */
        $body = $this->getBody();
        // Nicely formats output with indentation and extra space.
        $this->document->formatOutput = true;
        return $this->innerHTML($body);
    }

    /**
     * Clones all child nodes from one node to another
     *
     * @param DOMNode $new
     * @param DOMNode $old
     */
    protected function insertValue(DOMNode $new, DOMNode $old): void
    {
        /** @var DOMNode $node */
        foreach ($old->childNodes as $node) {
            $clone = $node->cloneNode(true);
            $new->appendChild($clone);
        }
    }

    /**
     * Returns innerHTML of an element
     *
     * @param DOMElement $element
     * @return string
     */
    protected function innerHTML(DOMElement $element): string
    {
        $html = '';
        if ($element->hasChildNodes()) {
            foreach ($element->childNodes as $node) {
                if ($node instanceof \DOMText && empty(trim($node->data))) {
                    continue;
                }
                $html .= $this->document->saveHTML($node);
            }
        }
        return $this->utf8Encode($html);
    }

    /**
     * Removes the given node from it's parent.
     * E.g. removes the node from the document.
     *
     * @param DOMNode $node
     */
    public function remove(DOMNode $node): void
    {
        $node->parentNode->removeChild($node);
    }

    /**
     * Converts all UTF-8 characters/symbols to their HTML entities counter part
     * to ensure safe manipulation of nodes.
     *
     * @param string $html
     * @return string
     */
    protected function utf8Encode(string $html): string
    {
        return mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
    }
}


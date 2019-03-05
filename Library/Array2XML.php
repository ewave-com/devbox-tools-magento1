<?php
/**
 * @author    Ewave <https://ewave.com/>
 * @copyright 2018-2019 NASKO TRADING PTY LTD
 * @license   https://ewave.com/wp-content/uploads/2018/07/eWave-End-User-License-Agreement.pdf BSD Licence
 */

namespace MagentoOneDevBox\Library;

class Array2XML extends \DomDocument
{
    /**
     * Root node
     *
     * @var \DOMElement
     */
    private $root;

    /**
     * Array2XML constructor.
     * @param string $root
     * @param string|null $file
     * @param string $encoding
     * @param string $version
     * @param bool $formatOutput
     * @param bool $preserveWhiteSpace
     */
    public function __construct(
        $root = 'config',
        $file = null,
        $encoding = 'UTF-8',
        $version = '1.0',
        $formatOutput = true,
        $preserveWhiteSpace = false
    ) {
        parent::__construct($version, $encoding);

        /** set the encoding */
        $this->encoding = $encoding;

        /** format the output */
        $this->formatOutput = $formatOutput;

        /** set the WhiteSpace */
        $this->preserveWhiteSpace = $preserveWhiteSpace;

        if ($file && is_file($file)) {
            $this->load($file);
            $elements = $this->getElementsByTagName($root);
            $element = $elements->length ? $elements->item(0) : null;
        }

        if (empty($element)) {
            $element = $this->appendChild($this->createElement($root));
        }

        /*** create the root element ***/
        $this->root = $element;
    }

    /**
     * @param array $arr
     * @param null $node
     * @throws \Exception
     */
    public function createNode($arr, $node = null)
    {
        if (!$this->root) {
            throw new \Exception('Root node not specified');
        }

        if (!$node) {
            $node = $this->root;
        }

        foreach ($arr as $element => $value) {
            if (!is_array($value) || empty($value) || empty($value['name'])) {
                continue;
            }
            if (!empty($value['remove'])) {
                $child = $node->getElementsByTagName($value['name'])->item(0);
                if ($child) {
                    $node->removeChild($child);
                }
                continue;
            } elseif ($node->getElementsByTagName($value['name'])->length) {
                $child = $node->getElementsByTagName($value['name'])->item(0);
                $child->textContent = $value['value'];
            } else {
                $child = $this->createElement($value['name'], $value['value']);
            }

            $node->appendChild($child);

            if (!empty($value['children'])) {
                self::createNode($value['children'], $child);
            }
        }
    }
}

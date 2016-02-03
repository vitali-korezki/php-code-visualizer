<?php

// namespace PhpParser\Serializer;

// use XMLWriter;
use PhpParser\Node;
use PhpParser\Comment;
use PhpParser\Serializer;

use PhpParser\PrettyPrinter\Standard as PrettyPrinterStandard;
class PrettyWriter extends PrettyPrinterStandard
{
    public function getAssignValue($type, $node) {
        // var_dump( $this->prettywriter->getAssignValue($type, $node->expr) );
        
        list($precedence, $associativity) = $this->precedenceMap[$type];
        return $this->pPrec($node, $precedence, $associativity, 1);
    }
    
    public function getValue(Node $node) {
        return $this->p($node);
    }
}

class Visualizer implements PhpParser\Serializer
{
    protected $writer;
    
    protected $prettywriter;

    /**
     * Constructs a XML serializer.
     */
    public function __construct() {
        $this->writer = new XMLWriter;
        $this->writer->openMemory();
        $this->writer->setIndent(true);
        
        $this->prettywriter = new PrettyWriter();
    }

    public function serialize(array $nodes) {
        $this->writer->flush();
        $this->writer->startDocument('1.0', 'UTF-8');

        $this->writer->startElement('AST');
        $this->writer->writeAttribute('xmlns:node',      'http://nikic.github.com/PHPParser/XML/node');
        $this->writer->writeAttribute('xmlns:subNode',   'http://nikic.github.com/PHPParser/XML/subNode');
        $this->writer->writeAttribute('xmlns:attribute', 'http://nikic.github.com/PHPParser/XML/attribute');
        $this->writer->writeAttribute('xmlns:scalar',    'http://nikic.github.com/PHPParser/XML/scalar');

        $this->_serialize($nodes);

        $this->writer->endElement();

        return $this->writer->outputMemory();
    }

    protected function _serialize($node, $nodename='value') {
        if ($node instanceof Node) {
            // type of the node
            // $this->writer->startElement('node:' . $node->getType());
            $type = preg_replace('/Stmt_|Scalar_|Expr_/', '', $node->getType());
            
            // if simple assign expression
            if ( preg_match('/Stmt_Use|Stmt_Property|Scalar_|Expr_/', $node->getType()) && method_exists($this->prettywriter, 'p'.$node->getType()) ) {
                $function = 'p'.$node->getType();
                
                if (strpos($type, 'Assign') !== false) {
                    $this->writer->startElement('Assign');
                    
                    $value = $this->prettywriter->$function($node);
                    $value_left = $this->prettywriter->getValue($node->var);
                    $value_right = $this->prettywriter->getValue($node->expr);
                    
                    $value_operator = str_replace($value_left, '', $value);
                    $value_operator = str_replace($value_right, '', $value_operator);
                    $value_operator = trim($value_operator);
                    
                    $this->writer->writeAttribute('type', $type );
                    $this->writer->writeAttribute('left', $value_left );
                    $this->writer->writeAttribute('right', $value_right );
                    $this->writer->writeAttribute('operator', $value_operator );
                    
                    $this->writer->writeElement('value', $value );
                    $this->writer->endElement();
                }
                else {
                    $this->writer->writeElement($type, $this->prettywriter->$function($node) );
                }
            }
            elseif ($type == 'Name') {
                $this->writer->writeElement('name', $node->toString() );
                // $this->writer->writeAttribute('value', $node->toString());
            }
            else {
                // other elements
                $this->writer->startElement( $type );
                $this->writer->writeAttribute('type', 'block');
    
                foreach ($node->getAttributes() as $name => $value) {
                    // check if array -> then may be a comment
                    if (is_array($value)) {
                        // $this->writer->startElement('attribute:' . $name);
                        $this->writer->startElement('' . $name);
                        $this->_serialize($value);
                        $this->writer->endElement();
                    }
                    else {
                        $this->writer->writeAttribute($name, $value);
                    }
                }
    
                foreach ($node as $name => $subNode) {
                    // $this->writer->startElement('subNode:' . $name);
                    // $this->writer->startElement('' . $name);
                    $this->_serialize($subNode, $name);
                    // $this->writer->endElement();
                }
    
                $this->writer->endElement();
            }
        } elseif ($node instanceof Comment) {
            $this->writer->startElement('comment');
            $this->writer->writeAttribute('isDocComment', $node instanceof Comment\Doc ? 'true' : 'false');
            $this->writer->writeAttribute('line', (string) $node->getLine());
            $this->writer->text($node->getText());
            $this->writer->endElement();
        } elseif (is_array($node)) {
            $countNodes = count($node);
            if ($countNodes == 1) {
                // just one element write directly to the xml
                foreach ($node as $key => $subNode) {
                    $this->_serialize($subNode, $key);
                }
            }
            elseif ($countNodes > 1) {
                // $this->writer->startElement('scalar:array');
                $this->writer->startElement( ($nodename && $nodename != 'stmts') ? $nodename : 'children' );
                foreach ($node as $key => $subNode) {
                    $this->_serialize($subNode, $key);
                }
                $this->writer->endElement();
            }
        } elseif (is_string($node)) {
            // $this->writer->writeElement('scalar:string', $node);
            // $this->writer->writeElement('string', $node);
            $this->writer->writeElement($nodename, $node);
        } elseif (is_int($node)) {
            // $this->writer->writeElement('scalar:int', (string) $node);
            // $this->writer->writeElement('int', (string) $node);
            $this->writer->writeElement($nodename, (string) $node);
        } elseif (is_float($node)) {
            // TODO Higher precision conversion?
            // $this->writer->writeElement('scalar:float', (string) $node);
            // $this->writer->writeElement('float', (string) $node);
            $this->writer->writeElement($nodename, (string) $node);
        } elseif (true === $node) {
            // $this->writer->writeElement('scalar:true');
            // $this->writer->writeElement('true');
            // $this->writer->writeElement('true');
        } elseif (false === $node) {
            // $this->writer->writeElement('scalar:false');
            // $this->writer->writeElement('false');
            // $this->writer->writeElement('false');
        } elseif (null === $node) {
            // $this->writer->writeElement('scalar:null');
            // $this->writer->writeElement('null');
            // $this->writer->writeElement('null');
        } else {
            throw new \InvalidArgumentException('Unexpected node type');
        }
    }
}

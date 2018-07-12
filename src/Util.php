<?php

namespace TDW\LegacyBundle;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;

class Util
{
    /**
     * @param string $filename
     *
     * @return string[]
     */
    public static function findClasses(string $filename): array
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new \RuntimeException('File does not exist or is not readable: '.$filename);
        }

        // Prepare a backwards compatible parser
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

        // Parse the file
        $statements = $parser->parse(file_get_contents($filename));

        // Resolve names to FQCNs
        $namespacer = new NodeTraverser();
        $namespacer->addVisitor(new NameResolver());
        $statements = $namespacer->traverse($statements);

        // And find our classes
        $finder = new NodeFinder();

        return array_map(function (Node\Stmt\Class_ $node) { return $node->namespacedName->toString(); }, $finder->findInstanceOf($statements, Node\Stmt\Class_::class));
    }
}

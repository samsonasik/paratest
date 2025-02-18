<?php

declare(strict_types=1);

namespace ParaTest\JUnit;

use SimpleXMLElement;

use function assert;
use function count;
use function current;
use function iterator_to_array;
use function sprintf;

/**
 * @internal
 *
 * @immutable
 */
class TestCase
{
    public function __construct(
        public readonly string $name,
        public readonly string $class,
        public readonly string $file,
        public readonly int $line,
        public readonly int $assertions,
        public readonly float $time
    ) {
    }

    final public static function caseFromNode(SimpleXMLElement $node): self
    {
        $systemOutput  = null;
        $systemOutputs = $node->xpath('system-out');
        if ($systemOutputs !== null && $systemOutputs !== []) {
            assert(count($systemOutputs) === 1);
            $systemOutput = (string) current($systemOutputs);
        }

        $getFirstNode = static function (array $nodes): SimpleXMLElement {
            assert(count($nodes) === 1);
            $node = current($nodes);
            assert($node instanceof SimpleXMLElement);

            return $node;
        };
        $getType      = static function (SimpleXMLElement $node): string {
            $element = $node->attributes();
            assert($element !== null);
            $attributes = iterator_to_array($element);
            assert($attributes !== []);

            return (string) $attributes['type'];
        };

        if (($errors = $node->xpath('error')) !== null && $errors !== []) {
            $error = $getFirstNode($errors);
            $type  = $getType($error);
            $text  = (string) $error;

            if ($type === 'PHPUnit\\Framework\\RiskyTest') {
                return new TestCaseWithMessage(
                    (string) $node['name'],
                    (string) $node['class'],
                    (string) $node['file'],
                    (int) $node['line'],
                    (int) $node['assertions'],
                    (float) $node['time'],
                    $type,
                    $text,
                    $systemOutput,
                    MessageType::risky,
                );
            }

            return new TestCaseWithMessage(
                (string) $node['name'],
                (string) $node['class'],
                (string) $node['file'],
                (int) $node['line'],
                (int) $node['assertions'],
                (float) $node['time'],
                $type,
                $text,
                $systemOutput,
                MessageType::error,
            );
        }

        if (($failures = $node->xpath('failure')) !== null && $failures !== []) {
            $failure = $getFirstNode($failures);
            $type    = $getType($failure);
            $text    = (string) $failure;

            return new TestCaseWithMessage(
                (string) $node['name'],
                (string) $node['class'],
                (string) $node['file'],
                (int) $node['line'],
                (int) $node['assertions'],
                (float) $node['time'],
                $type,
                $text,
                $systemOutput,
                MessageType::failure,
            );
        }

        if ($node->xpath('skipped') !== []) {
            $text = (string) $node['name'];
            if ((string) $node['class'] !== '') {
                $text = sprintf(
                    "%s::%s\n\n%s:%s",
                    $node['class'],
                    $node['name'],
                    $node['file'],
                    $node['line'],
                );
            }

            return new TestCaseWithMessage(
                (string) $node['name'],
                (string) $node['class'],
                (string) $node['file'],
                (int) $node['line'],
                (int) $node['assertions'],
                (float) $node['time'],
                null,
                $text,
                $systemOutput,
                MessageType::skipped,
            );
        }

        return new self(
            (string) $node['name'],
            (string) $node['class'],
            (string) $node['file'],
            (int) $node['line'],
            (int) $node['assertions'],
            (float) $node['time'],
        );
    }
}

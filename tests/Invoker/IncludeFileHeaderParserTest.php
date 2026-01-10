<?php

namespace Joby\Smol\Context\Invoker;

use PHPUnit\Framework\TestCase;

class IncludeFileHeaderParserTest extends TestCase
{
    public function testParsesDocblockAfterDeclareAndNamespaceAndUse(): void
    {
        $source = <<<PHP
<!doctype html>
<?php

declare(strict_types=1);

namespace Foo\\Bar;

use Acme\\Thing;
use Acme\\OtherThing as OT;

/**
 * Header docblock
 */

return 1;
PHP;

        $header = IncludeFileHeaderParser::parse($source);

        $this->assertSame('Foo\\Bar', $header->namespace);
        $this->assertArrayHasKey('Thing', $header->uses);
        $this->assertSame('Acme\\Thing', $header->uses['Thing']);
        $this->assertArrayHasKey('OT', $header->uses);
        $this->assertSame('Acme\\OtherThing', $header->uses['OT']);
        $this->assertNotNull($header->docblock);
        $this->assertStringContainsString('Header docblock', $header->docblock);
    }

    public function testStopsBeforeExecutableCode(): void
    {
        $source = <<<PHP
<?php
return 1;
/** should not be treated as header docblock */
PHP;

        $header = IncludeFileHeaderParser::parse($source);
        $this->assertNull($header->docblock);
        $this->assertNull($header->namespace);
        $this->assertSame([], $header->uses);
    }

    public function testIgnoresGroupUseStatements(): void
    {
        $source = <<<PHP
<?php
use Foo\\{Bar,Baz};
/** doc */
return 1;
PHP;

        $header = IncludeFileHeaderParser::parse($source);
        $this->assertSame([], $header->uses);
        $this->assertNotNull($header->docblock);
    }
}

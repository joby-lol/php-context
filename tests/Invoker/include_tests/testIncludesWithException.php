<?php
/**
 * This file sends some output then throws an exception. The exception ultimately thrown by the Invoker should include
 * this exception, as well as the output buffer content.
 */

echo 'output buffer value';

throw new RuntimeException('test exception');
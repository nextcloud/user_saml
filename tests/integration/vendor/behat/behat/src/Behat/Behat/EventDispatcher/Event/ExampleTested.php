<?php

/*
 * This file is part of the Behat.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Behat\EventDispatcher\Event;

/**
 * Represents an example event.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
interface ExampleTested
{
    public const BEFORE = 'tester.example_tested.before';
    public const AFTER_SETUP = 'tester.example_tested.after_setup';
    public const BEFORE_TEARDOWN = 'tester.example_tested.before_teardown';
    public const AFTER = 'tester.example_tested.after';
}

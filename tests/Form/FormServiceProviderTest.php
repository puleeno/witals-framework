<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Form;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Container\Container;
use Witals\Framework\Form\FormBuilder;
use Witals\Framework\Form\FormServiceProvider;

class FormServiceProviderTest extends TestCase
{
    public function testRegisterBindsFormBuilder(): void
    {
        $container = new Container();
        $provider = new FormServiceProvider($container);
        $provider->register();

        $this->assertTrue($container->has(FormBuilder::class));
    }

    public function testRegisterBindsFormAlias(): void
    {
        $container = new Container();
        $provider = new FormServiceProvider($container);
        $provider->register();

        $this->assertTrue($container->has('form'));
    }

    public function testMakeReturnsFormBuilder(): void
    {
        $container = new Container();
        $provider = new FormServiceProvider($container);
        $provider->register();

        $form = $container->make(FormBuilder::class);

        $this->assertInstanceOf(FormBuilder::class, $form);
    }

    public function testMakeViaAliasReturnsFormBuilder(): void
    {
        $container = new Container();
        $provider = new FormServiceProvider($container);
        $provider->register();

        $form = $container->make('form');

        $this->assertInstanceOf(FormBuilder::class, $form);
    }
}

<?php

declare(strict_types=1);

namespace Witals\Framework\Container\Exceptions;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

class BindingResolutionException extends RuntimeException implements ContainerExceptionInterface {}

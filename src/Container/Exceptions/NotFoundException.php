<?php

declare(strict_types=1);

namespace Witals\Framework\Container\Exceptions;

use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

class NotFoundException extends RuntimeException implements NotFoundExceptionInterface {}

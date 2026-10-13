<?php

namespace App\Support\HeartImport;

use RuntimeException;

/**
 * A row (or run) that cannot be imported, with a reason safe to print and log.
 */
final class ImportException extends RuntimeException {}

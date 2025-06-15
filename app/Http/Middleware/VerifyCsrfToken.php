<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
  /**
   * The URIs that should be excluded from CSRF verification.
   *
   * @var array<int, string>
   */
  protected $except = [
    // Leave and WFH API routes that use web session authentication
    'api/v1/employees/*/leave-records',
    'api/v1/employees/*/leave-records/*',
    'api/v1/employees/*/wfh-records',
    'api/v1/employees/*/wfh-records/*',
  ];
}

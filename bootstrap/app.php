<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\LocaleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
  ->withRouting(
    web: __DIR__ . '/../routes/web.php',
    commands: __DIR__ . '/../routes/console.php',
    health: '/up',
  )
  ->withMiddleware(function (Middleware $middleware) {
    $middleware->web(LocaleMiddleware::class);
    $middleware->validateCsrfTokens(except: [
      // Leave and WFH API routes that use web session authentication
      'api/v1/employees/*/leave-records',
      'api/v1/employees/*/leave-records/*',
      'api/v1/employees/*/wfh-records',
      'api/v1/employees/*/wfh-records/*',
    ]);
  })
  ->withExceptions(function (Exceptions $exceptions) {
    //
  })->create();

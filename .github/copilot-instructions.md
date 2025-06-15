# Planning

When asked to enter "Planner Mode" or using the /plan command, deeply reflect upon the changes being asked and analyze existing code to map the full scope of changes needed. Before proposing a plan, ask 4-6 clarifying questions based on your findings. Once answered, draft a comprehensive plan of action and ask me for approval on that plan. Once approved, implement all steps in that plan. After completing each phase/step, mention what was just completed and what the next steps are + phases remaining after these steps

## Documentation

review documentation at _/docs/_ before working on requests

Whenever there is a change to the database structure or any of its fields you must update the documentation at `docs/database/database_tables.md`
Whenever there is a change to the routes structure or any of its fields you must update the documentation at `docs/routes.md`
Whenever there is a change to the API structure or any of its fields you must update the documentation at `docs/API/api.md`

## UI/UX Template Compliance

All views created within the QFlow system must strictly adhere to the Vuexy HTML Admin Template guidelines. This includes layout structure, form design, table formats, and modal implementations. The resources/views folder includes sample blade templates that demonstrate best practices based on Vuexy components.

Vuexy Documentation Reference:
https://demos.pixinvent.com/vuexy-html-admin-template/documentation/laravel-introduction.html

Ensure consistency across all screens by referring to these templates and the documentation for styling and UI behavior.

## Code Quality & Testing Standards

All code contributions to the **QFlow** platform must comply with the following documentation and test coverage standards. These rules ensure quality, maintainability, and long-term scalability of the project.

### 📘 1. Code Documentation

#### 1.1 PHP Classes, Methods, and Functions

- Every controller, service, repository, model, and helper must be documented using [PHPDoc](https://docs.phpdoc.org/latest/index.html).
- Required tags:
  - `@param` and `@return` for all public methods
  - `@throws` for methods that raise exceptions
  - `@author`

#### 1.2 JavaScript & Blade Templates

- Use JS-style comments (`//`, `/** ... */`) to explain logic.
- Blade views must contain documentation blocks at the top specifying:
  - Purpose of the view
  - Route or controller action
  - Expected variables or data

#### 1.3 API Endpoints

- Document API routes in `routes/api.php` using DocBlocks.
- Controller logic must be reflected in Swagger/OpenAPI annotations for integration with `/docs`.

---

### ✅ 2. Mandatory Tests

> **No pull request is accepted unless all new functionality is tested.**

#### 2.1 Unit Tests

- Each class must have a test under `/tests/Unit`.
- Cover all methods and edge cases.

#### 2.2 Feature Tests

- Cover full workflows and endpoints in `/tests/Feature`.
- Simulate real user interactions using Laravel's `TestCase`.

#### 2.3 API Tests

- Validate request/response structures, status codes, and auth layers.
- Use `actingAs`, `withHeaders`, and `assertJson`.

#### 2.4 UI Tests (Optional - Phase 2+)

- Use Laravel Dusk or Cypress for key workflows.
- Validate form behaviors and frontend logic.

---

### 📊 3. Coverage & CI Enforcement

- All new code must have **≥85%** test coverage.
- Run `php artisan test --coverage` locally.
- CI/CD pipeline will reject PRs that:
  - Drop coverage
  - Fail unit or feature tests
  - Lack test files for new logic

---

### 📦 4. Commit & Pull Request Rules

- Every PR must:
  - Reference feature or issue
  - Include test files or justification if not applicable
  - Pass code review from senior team member

---

### 🧩 Vuexy & Views Compliance

- **All Blade views must use Vuexy components**.
- Sample templates are available under `/resources/views`.
- Vuexy documentation reference:
  [https://demos.pixinvent.com/vuexy-html-admin-template/documentation/laravel-introduction.html](https://demos.pixinvent.com/vuexy-html-admin-template/documentation/laravel-introduction.html)

---

### 📁 Documentation Location

- All technical documents must be placed under `/docs`

## Avoid queries in Blade templates

### Do not execute queries in Blade templates and use eager loading (N + 1 problem)

Bad (for 100 users, 101 DB queries will be executed):

```php
@foreach (User::all() as $user)
{% raw %}   {{ $user->profile->name }}{% endraw %}
@endforeach
```

Good (for 100 users, 2 DB queries will be executed):

```php
$users = User::with('profile')->get();

...

@foreach ($users as $user)
{% raw %}   {{ $user->profile->name }}{% endraw %}
@endforeach
```

## Do not get data from the .env file directly

Pass the data to config files instead and then use the `config()` helper function to use the data in an application.

Bad:

```php
$apiKey = env('API_KEY');
```

Good:

```php
// config/api.php
'key' => env('API_KEY'),

// Use the data
$apiKey = config('api.key');
```

## Avoid fat controllers and write frequent queries in model.

Put all DB related logic into Eloquent models or into Repository classes if you're using Query Builder or raw SQL queries.

Bad:

```php
public function index(): View
{
    $clients = Client::verified()
        ->with(['orders' => function ($q) {
            $q->where('created_at', '>', Carbon::today()->subWeek());
        }])
        ->get();

    return view('index', ['clients' => $clients]);
}
```

Good:

```php
public function index(): View
{
    return view('index', ['clients' => $this->client->getWithNewOrders()]);
}

class Client extends Model
{
    public function getWithNewOrders(): Collection
    {
        return $this->verified()
            ->with(['orders' => function ($q) {
                $q->where('created_at', '>', Carbon::today()->subWeek());
            }])
            ->get();
    }
}
```

## Business logic should be in repository class

A controller must have only one responsibility, so move business logic from controllers to repository classes.

Bad:

```php
public function store(Request $request): Redirect
{
    if ($request->hasFile('image')) {
        $request->file('image')->move(public_path('images') . 'temp');
    }

    ....
}
```

Good:

```php
public function store(Request $request): Redirect
{
    $this->articleRepository->handleUploadedImage($request->file('image'));

    ....
}

class ArticleRepository
{
    public function handleUploadedImage($image): void
    {
        if (!is_null($image)) {
            $image->move(public_path('images') . 'temp');
        }
    }
}
```

## Use config helper

### Do not get data from the .env file directly

Pass the data to config files instead and then use the config() helper function to use the data in an application.

Bad:

```php
$apiKey = env('API_KEY');
```

Good:

```php
// config/api.php
'key' => env('API_KEY'),

// Use the data
$apiKey = config('api.key');
```

## Don't repeat yourself (DRY)

Reuse code when you can. SRP is helping you to avoid duplication. Also, reuse Blade templates, use Eloquent scopes etc.

Bad:

```php
public function getActive(): Collection
{
    return $this->where('verified', 1)->whereNull('deleted_at')->get();
}

public function getArticles(): Collection
{
    return $this->whereHas('user', function ($query) {
            $query->where('verified', 1)->whereNull('deleted_at');
        })->get();
}

```

Good:

```php
public function scopeActive(Builder $query): Builder
{
    return $query->where('verified', 1)->whereNull('deleted_at');
}

public function getActive(): Collection
{
    return $this->active()->get();
}

public function getArticles(): Collection
{
    return $this->whereHas('user', function ($query) {
            $query->active();
        })->get();
}
```

## Use IoC container for long term projects

new Class syntax creates tight coupling between classes and complicates testing. Use IoC container or facades instead.

Bad:

```php
$user = new User;
$user->create($request->validated());
```

Good:

```php
public function __construct(User $user)
{
    $this->user = $user;
}

....

$this->user->create($request->validated());
```

## Follow Laravel naming conventions

Follow [PSR standards](http://www.php-fig.org/psr/psr-2/).

Also, follow naming conventions accepted by Laravel community:

| What                             | How                                                                       | Good                                    | Bad                                                 |
| -------------------------------- | ------------------------------------------------------------------------- | --------------------------------------- | --------------------------------------------------- |
| Controller                       | singular                                                                  | ArticleController                       | ~~ArticlesController~~                              |
| Route                            | plural                                                                    | articles/1                              | ~~article/1~~                                       |
| Named route                      | snake_case with dot notation                                              | users.show_active                       | ~~users.show-active, show-active-users~~            |
| Model                            | singular                                                                  | User                                    | ~~Users~~                                           |
| hasOne or belongsTo relationship | singular                                                                  | articleComment                          | ~~articleComments, article_comment~~                |
| All other relationships          | plural                                                                    | articleComments                         | ~~articleComment, article_comments~~                |
| Table                            | plural                                                                    | article_comments                        | ~~article_comment, articleComments~~                |
| Pivot table                      | singular model names in alphabetical order                                | article_user                            | ~~user_article, articles_users~~                    |
| Table column                     | snake_case without model name                                             | meta_title                              | ~~MetaTitle; article_meta_title~~                   |
| Model property                   | snake_case                                                                | $model->created_at                      | ~~$model->createdAt~~                               |
| Foreign key                      | singular model name with \_id suffix                                      | article_id                              | ~~ArticleId, id_article, articles_id~~              |
| Primary key                      | -                                                                         | id                                      | ~~custom_id~~                                       |
| Migration                        | -                                                                         | 2017_01_01_000000_create_articles_table | ~~2017_01_01_000000_articles~~                      |
| Method                           | camelCase                                                                 | getAll                                  | ~~get_all~~                                         |
| Method in resource controller    | [table](https://laravel.com/docs/master/controllers#resource-controllers) | store                                   | ~~saveArticle~~                                     |
| Method in test class             | camelCase                                                                 | testGuestCannotSeeArticle               | ~~test_guest_cannot_see_article~~                   |
| Variable                         | camelCase                                                                 | $articlesWithAuthor                     | ~~$articles_with_author~~                           |
| Collection                       | descriptive, plural                                                       | $activeUsers = User::active()->get()    | ~~$active, $data~~                                  |
| Object                           | descriptive, singular                                                     | $activeUser = User::active()->first()   | ~~$users, $obj~~                                    |
| Config and language files index  | snake_case                                                                | articles_enabled                        | ~~ArticlesEnabled; articles-enabled~~               |
| View                             | snake_case                                                                | show_filtered.blade.php                 | ~~showFiltered.blade.php, show-filtered.blade.php~~ |
| Config                           | snake_case                                                                | google_calendar.php                     | ~~googleCalendar.php, google-calendar.php~~         |
| Contract (interface)             | adjective or noun                                                         | Authenticatable                         | ~~AuthenticationInterface, IAuthentication~~        |
| Trait                            | adjective                                                                 | Notifiable                              | ~~NotificationTrait~~                               |

## Use PHP Type declaration

PHP supports type declarations since PHP 7, and it's a good practice to utilize them when defining types in Laravel.

Bad:

```php
public function calculateTotal($quantity, $price)
{
    return $quantity * $price;
}
```

Good:

```php
public function calculateTotal(int $quantity, float $price): float
{
    return $quantity * $price;
}
```

**Class type declaration**

```php
public function saveUser(User $user): void
{
    ...
}
```

**DocBlocks as type declaration**

```php
/**
 * Calculate the total.
 *
 * @param int $quantity
 * @param float $price
 * @return float
 */
public function calculateTotal(int $quantity, float $price): float
{
    return $quantity * $price;
}

```

## Prefer Eloquent and Laravel Collections

## Prefer to use Eloquent over using Query Builder and raw SQL queries.

Eloquent allows you to write readable and maintainable code. Also, Eloquent has great built-in tools like soft deletes, events, scopes etc.

Bad:

```sql
SELECT *
FROM `articles`
WHERE EXISTS (SELECT *
              FROM `users`
              WHERE `articles`.`user_id` = `users`.`id`
              AND EXISTS (SELECT *
                          FROM `profiles`
                          WHERE `profiles`.`user_id` = `users`.`id`)
              AND `users`.`deleted_at` IS NULL)
AND `verified` = '1'
AND `active` = '1'
ORDER BY `created_at` DESC
```

Good:

```php
Article::has('user.profile')->verified()->latest()->get();
```

## Prefer collections to arrays

Using collection methods, we abstract away the iteration and filtering logic, making the code more expressive and easier to understand. Laravel collections offer numerous methods, such as map, pluck, groupBy, sum, etc., which makes it easy to perform various data manipulations without having to write custom code.

Bad:

```php
$products = ['pin', 'pen', 'pencil', 'paper'];

$isProductEmpty = count($products) === 0
```

Good:

```php
$products = collect(['pin', 'pen', 'pencil', 'paper']);

$isProductEmpty = $products->isEmpty();
```

## Readable and descriptive variable names

When declaring variable names in Laravel (or any programming language), it's essential to prioritize readability and clarity to make your code easier to understand. Meaningful and descriptive variable names enhance the maintainability and readability of your codebase.

## Use descriptive variable names

Choose names that clearly convey the purpose or content of the variable. Avoid using single-letter or abbreviated names, unless they are widely accepted and universally understood, like $i for a loop counter.

Bad:

```php
$un = 'john_doe'; // unclear abbreviation
$tic = 10;        // unclear abbreviation
```

Good:

```php
$username = 'john_doe';
$totalItemCount = 10;
```

## Be consistent

Maintain consistency in naming conventions throughout your codebase. If you use camelCase for variables, stick with it consistently.

Bad:

```php
$first_name = 'John'; // Mixing camelCase and snake_case
$LASTNAME = 'Doe';    // Inconsistent casing
```

Good:

```php
$firstName = 'John';
$lastName = 'Doe';
```

## Use meaningful variable names

If a variable's purpose might not be immediately apparent from its name, provide additional context or comments to clarify its role.

Bad:

```php
$val = 123; // What does this represent?
$flag = true; // What is this flag for?
```

Good:

```php
$currentPostId = 123; // ID of the currently displayed blog post
$isActive = true;     // Flag indicating whether the user is active
```

## Avoid Overly Abbreviated Names

While brevity is good, avoid overly cryptic or abbreviated names that may be difficult to understand.

Bad:

```php
$maxAtt = 3; // Unclear abbreviation
$usrCt = 1000; // Unclear abbreviation
```

Good:

```php
$maxAttempts = 3;
$userCount = 1000;
```

## Use Plural for Collections

When dealing with collections or arrays, use plural names to indicate that the variable holds multiple items.

Bad:

```php
$user = ['john', 'jane', 'joe']; // Singular name but holds multiple users
```

Good:

```php
$users = ['john', 'jane', 'joe'];
```

## Avoid Using Reserved Words

Be cautious not to use PHP reserved words or names of built-in functions as variable names.

```php
// Bad - 'unset' is a reserved word in PHP
$unset = 'Some value';
```

## Use shorter and more readable syntax where possible

Bad:

```php
$request->session()->get('cart');
$request->input('name');
```

Good:

```php
session('cart');
$request->name;
```

### More examples:

| Common syntax                                                          | Shorter and more readable syntax                   |
| ---------------------------------------------------------------------- | -------------------------------------------------- |
| `Session::get('cart')`                                                 | `session('cart')`                                  |
| `$request->session()->get('cart')`                                     | `session('cart')`                                  |
| `Session::put('cart', $data)`                                          | `session(['cart' => $data])`                       |
| `$request->input('name'), Request::get('name')`                        | `$request->name, request('name')`                  |
| `return Redirect::back()`                                              | `return back()`                                    |
| `is_null($object->relation) ? null : $object->relation->id`            | `optional($object->relation)->id`                  |
| `return view('index')->with('title', $title)->with('client', $client)` | `return view('index', compact('title', 'client'))` |
| `$request->has('value') ? $request->value : 'default';`                | `$request->get('value', 'default')`                |
| `Carbon::now(), Carbon::today()`                                       | `now(), today()`                                   |
| `App::make('Class')`                                                   | `app('Class')`                                     |
| `->where('column', '=', 1)`                                            | `->where('column', 1)`                             |
| `->orderBy('created_at', 'desc')`                                      | `->latest()`                                       |
| `->orderBy('age', 'desc')`                                             | `->latest('age')`                                  |
| `->orderBy('created_at', 'asc')`                                       | `->oldest()`                                       |
| `->select('id', 'name')->get()`                                        | `->get(['id', 'name'])`                            |
| `->first()->name`                                                      | `->value('name')`                                  |

## Single responsibility principle

A class and a method should have only one responsibility.

Bad:

```php
public function getFullNameAttribute(): string
{
    if (auth()->user() && auth()->user()->hasRole('client') && auth()->user()->isVerified()) {
        return 'Mr. ' . $this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name;
    } else {
        return $this->first_name[0] . '. ' . $this->last_name;
    }
}
```

Good:

```php
public function getFullNameAttribute(): string
{
    return $this->isVerifiedClient() ? $this->getFullNameLong() : $this->getFullNameShort();
}

public function isVerifiedClient(): bool
{
    return auth()->user() && auth()->user()->hasRole('client') && auth()->user()->isVerified();
}

public function getFullNameLong(): string
{
    return 'Mr. ' . $this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name;
}

public function getFullNameShort(): string
{
    return $this->first_name[0] . '. ' . $this->last_name;
}
```

## Use Accessors and Mutators

Use Accessors and Mutators instead of mutating in controllers and blade

Bad:

```php
{% raw %}{{ Carbon::createFromFormat('Y-d-m H-i', $object->ordered_at)->toDateString() }}{% endraw %}
{% raw %}{{ Carbon::createFromFormat('Y-d-m H-i', $object->ordered_at)->format('m-d') }}{% endraw %}
```

Good:

```php
// Model
protected $dates = ['ordered_at', 'created_at', 'updated_at'];

public function getSomeDateAttribute(DateTime $date): string
{
    return $date->format('m-d');
}

// View
{{ $object->ordered_at->toDateString() }}
{{ $object->ordered_at->some_date }}
```

## Use constants and language helper

Use config and language files, constants instead of text in the code

Bad:

```php
public function isNormal(): bool
{
    return $article->type === 'normal';
}

return back()->with('message', 'Your article has been added!');
```

Good:

```php
public function isNormal(): bool
{
    return $article->type === Article::TYPE_NORMAL;
}

return back()->with('message', __('app.article_added'));
```

## Use Request class for validations

### Move validation from controllers to Request classes.

Bad:

```php
public function store(Request $request): Redirect
{
    $request->validate([
        'title' => 'required|unique:posts|max:255',
        'body' => 'required',
        'publish_at' => 'nullable|date',
    ]);

    ...
}
```

Good:

```php
public function store(PostRequest $request): Redirect
{
    ...
}

class PostRequest extends Request
{
    public function rules(): array
    {
        return [
            'title' => 'required|unique:posts|max:255',
            'body' => 'required',
            'publish_at' => 'nullable|date',
        ];
    }
}
```

## Other good practices

Never put any logic in routes files.

Minimize usage of vanilla PHP in Blade templates.

Use [laravel pint](https://laravel.com/docs/pint) for `PHP Code Style Fixer`

Use in-memory DB for testing.

Do not override standard framework features to avoid problems related to updating the framework version and many other issues.

Use modern PHP syntax where possible, but don't forget about readability.

## Memory of mistakes

Update copilot-instructions.md with notes of previous mistakes and how to avoid them in the future. make sure to check these notes whenever you work on a new request so it is mistake free! This is very important. Do not forget to update this file with new mistakes and how to avoid them in the future.

## Mistakes and how to avoid them

### Visual Request 403 Authorization Errors (Fixed June 2025)

**Problem:** Organization admin users receiving 403 "unauthorized" errors when submitting Visual Request forms, despite having organization admin privileges.

**Root Cause:** Missing Laravel model policy for VisualRequest model. When controllers use `$this->authorize('view', $visualRequest)` calls, Laravel falls back to denying access if no policy is defined.

**Solution:**

1. **Created VisualRequestPolicy** (`app/Policies/VisualRequestPolicy.php`) with proper authorization logic for organization admins
2. **Registered policy** in `AuthServiceProvider.php` with model binding
3. **Updated StoreVisualRequestRequest** authorization to allow organization admins regardless of specific permissions

**Key Learnings:**

- Always check for missing model policies when encountering 403 errors with `$this->authorize()` calls
- Laravel denies access by default if no policy is found
- Organization admins should have access to resources within their organization without requiring specific permissions
- Form request authorization AND model policies both need to be configured properly
- Use `php artisan make:policy PolicyName --model=ModelName` to create policies with proper structure

**Prevention:** When creating new models that use `$this->authorize()` in controllers, always create corresponding policies.

### Visual Request Route Parameter Errors (Fixed June 2025)

**Problem:** Visual Request show page using non-existent `access_token` field in route generation, causing "Property [access_token] does not exist on this collection instance" errors.

**Root Cause:** Blade template referencing `$visualRequest->access_token` field that doesn't exist in the VisualRequest model. The model uses `slug` field for public URL identification.

**Solution:**

1. **Updated Blade template** in `show.blade.php` to use `$visualRequest->slug` instead of `$visualRequest->access_token`
2. **Route generation fix** changed from `route('vr.index', $visualRequest->access_token)` to `route('vr.index', $visualRequest->slug)`

**Key Learnings:**

- Always verify that model fields referenced in Blade templates actually exist in the database schema
- Check database migrations and model fillable arrays to confirm available fields
- The VisualRequest model uses `slug` field for public URL tokens, not an `access_token` field
- Public Visual Request routes expect the slug as the token parameter

**Prevention:** When referencing model properties in Blade templates, verify they exist in the model's database schema and fillable array.

### Visual Request Route Not Defined Errors (Fixed June 2025)

**Problem:** Blade templates using route name `vr.index` causing "Route [vr.index] not defined" errors.

**Root Cause:** The Visual Request public routes were defined with the prefix `visual-request.public.*` but code was referencing the non-existent route name `vr.index`.

**Solution:**

1. **Updated Blade templates** in `show.blade.php` to use correct route name `visual-request.public.show` instead of `vr.index`
2. **Added backward compatibility alias** by creating a new route `vr.index` that points to the same controller action for easier access
3. **Fixed all route references** in QR code generation and URL display fields

**Key Learnings:**

- Always check that route names referenced in Blade templates actually exist using `php artisan route:list`
- Visual Request public routes use the naming convention `visual-request.public.*` not `vr.*`
- When creating user-friendly aliases, add them as separate route definitions for backward compatibility
- Route names must match exactly - Laravel is case-sensitive with route names

**Prevention:** Before using route names in Blade templates, verify they exist by checking route files or running `php artisan route:list --name=route_name`.

### JavaScript Errors and Modern Practices (Fixed June 2025)

**Problem:** Visual Request index page showing JavaScript errors:

1. "Avoid using document.write()" violation warnings
2. "Uncaught ReferenceError: $ is not defined" errors

**Root Cause:**

1. **document.write() usage:** Footer templates and analytics cards using deprecated `document.write()` to display current year
2. **Missing jQuery:** Visual Request index page attempting to use jQuery (`$`) before it was loaded in the browser

**Solution:**

1. **Replaced document.write() usage** in all template files:

   - Updated `footer.blade.php` and `footer-front.blade.php` to use `{{ date('Y') }}` with modern JavaScript for dynamic updates
   - Fixed `cards-analytics.blade.php`, `app-ecommerce-dashboard.blade.php`, and `ui-footer.blade.php` to use Blade templating instead of document.write
   - Added modern JavaScript with `document.addEventListener('DOMContentLoaded')` to update years dynamically

2. **Fixed jQuery loading issues**:
   - Added `jquery.js` to vendor-script section in visual requests index
   - Wrapped jQuery code with proper DOM ready checks and error handling
   - Added defensive coding to check if jQuery is loaded before using it

**Key Learnings:**

- **document.write() is deprecated** and violates modern web standards - use Blade templating with `{{ date('Y') }}` for static content
- For dynamic year updates, use modern JavaScript: `document.addEventListener('DOMContentLoaded', ...)`
- **Always ensure jQuery is loaded** before using `$` - include `jquery.js` in vendor-script section
- Add defensive checks: `if (typeof $ === 'undefined')` to prevent errors
- Use proper nesting of DOM ready events when combining vanilla JS and jQuery
- Browser console violations should be addressed as they impact performance and user experience

**Prevention:**

- Avoid document.write() - use Blade templating for server-side content and modern JavaScript for client-side updates
- Always include required JavaScript libraries in the correct order in vendor-script sections
- Test JavaScript functionality in browser console during development

### Visual Request Preview Method Missing (Fixed June 2025)

**Problem:** Visual Request preview functionality showing "Method App\Http\Controllers\Client\VisualRequestController::preview does not exist" error when trying to access `/client/visual-requests/{id}/preview`.

**Root Cause:** The `preview` method was documented in routes but not implemented in the `VisualRequestController`.

**Solution:**

1. **Added preview method** to `App\Http\Controllers\Client\VisualRequestController` with proper authorization and data preparation
2. **Created preview blade template** at `resources/views/client/visual-requests/preview.blade.php` with:
   - Preview mode styling and visual indicators
   - Simulated Visual Request interface showing how it appears to end users
   - Configuration summary displaying current settings
   - Disabled form elements to prevent actual submission
   - Links to edit configuration and view live version

**Key Learnings:**

- Always implement controller methods that are referenced in route definitions
- Preview functionality should closely mirror the public interface but in a controlled environment
- Include visual indicators when users are in preview mode to avoid confusion
- Preview templates should show both the user experience and administrative configuration details
- Use authorization checks (`$this->authorize('view', $visualRequest)`) in preview methods

**Prevention:** Verify that all route definitions have corresponding controller methods implemented, especially when documenting new features.

### Visual Request Permission Assignment Errors (Fixed June 2025)

**Problem:** Organization admin users receiving 403 "unauthorized" errors when submitting Visual Request forms, even after Visual Request permissions were defined in the seeder.

**Root Cause:**

1. **Missing Permissions in Database:** Visual Request permissions were defined in `OrganizationAdminPermissionsSeeder` but never actually created in the database
2. **Incomplete Permission Assignment Logic:** The seeder was only assigning permissions matching patterns like `client.%`, `view_%`, `manage_%` - but Visual Request permissions like `create_visual_requests`, `edit_visual_requests`, `delete_visual_requests` didn't match these patterns
3. **Missing Role Assignment:** No users had the `organization_admin` role assigned

**Solution:**

1. **Updated Seeder Permission Query** in `OrganizationAdminPermissionsSeeder.php` to include all Visual Request permission patterns:

   ```php
   // Added comprehensive patterns to capture all permission types
   $allClientPermissions = Permission::where(function ($query) {
     $query->where('name', 'like', 'client.%')
       ->orWhere('name', 'like', 'view_%')
       ->orWhere('name', 'like', 'manage_%')
       ->orWhere('name', 'like', '%visual_requests%')  // New
       ->orWhere('name', 'like', 'create_%')           // New
       ->orWhere('name', 'like', 'edit_%')             // New
       ->orWhere('name', 'like', 'delete_%');          // New
   })
   ```

2. **Ran Permission Seeder** to create all permissions and assign them to organization_admin role:

   ```bash
   php artisan db:seed --class=OrganizationAdminPermissionsSeeder
   ```

3. **Assigned Organization Admin Role** to the intended user:

   ```bash
   php artisan tinker --execute="App\Models\User::find(12)->assignRole('organization_admin');"
   ```

4. **Cleared Permission Cache**:
   ```bash
   php artisan permission:cache-reset
   ```

**Key Learnings:**

- **Always verify permissions exist in database** - use `php artisan tinker --execute="echo Spatie\Permission\Models\Permission::where('name', 'like', '%visual%')->get();"` to check
- **Permission seeder patterns must be comprehensive** - don't assume permission names follow standard patterns like `client.%`
- **Check permission assignment after seeder runs** - verify the role actually has the expected permissions
- **Test authorization end-to-end** - check both that permissions exist AND that users have proper roles assigned
- **Permission cache must be cleared** after role/permission changes to take effect immediately

**Prevention:**

- When adding new permissions to seeders, always verify they match the assignment query patterns
- Test permission assignment by checking actual database records, not just seeder definitions
- Always run seeder after defining new permissions and verify the results
- Check that intended users have the correct roles assigned

### Visual Request Authorization Fix for Fresh Installations (Fixed June 2025)

**Problem:** Visual Request 403 authorization errors returned after fresh migrations despite being fixed in v0.1.52. Organization admin users could not create Visual Requests in fresh installations.

**Root Cause:** The `OrganizationAdminPermissionsSeeder` was only assigning the organization_admin role to a hard-coded test user email `sdvsvd@svdv.vom` that doesn't exist in fresh installations. While the seeder created permissions correctly, the standard organization admin user `org-admin@qflow.test` created by `UserSeeder` wasn't getting the updated role with Visual Request permissions due to seeder execution order issues.

**Solution:**

1. **Updated OrganizationAdminPermissionsSeeder** to check for both the hard-coded test user AND the standard organization admin user:

   ```php
   // Also look for the standard organization admin user created in fresh installations
   $standardOrgAdmin = User::where('email', 'org-admin@qflow.test')->first();
   ```

2. **Modified role assignment logic** to handle multiple organization admin users:

   ```php
   // Direct DB update for users to avoid guard issues (for any organization admin users found)
   $usersToUpdate = array_filter([$specificUser, $standardOrgAdmin]);
   ```

3. **Verified seeder execution order** ensures Visual Request permissions are created and assigned properly in fresh installations

**Key Learnings:**

- **Hard-coded email dependencies** in seeders can break fresh installations - always check for standard users too
- **Seeder execution order matters** - permissions must be created before role assignments, but roles can be reassigned after permission updates
- **Fresh migration testing** is critical to ensure fixes work in new installations, not just existing systems
- **The standard organization admin user** in fresh installations is `org-admin@qflow.test`, not the test email used during development
- **Database seeder patterns** should be flexible enough to handle both development and production user scenarios

**Prevention:**

- When creating seeders that assign roles to specific users, always include logic to handle standard users created in fresh installations
- Test all authorization fixes with `php artisan migrate:fresh --seed` to ensure they work from scratch
- Avoid hard-coding specific user emails without fallback logic for standard installation users

### Modular Architecture User Model Fix (Fixed June 2025)

**Problem:** Seeders using `App\Models\User` failing in fresh installations because the application uses modular architecture with `Modules\User\Models\User`.

**Root Cause:** Both `OrganizationAdminPermissionsSeeder` and `UserSeeder` were importing and using `App\Models\User` instead of the correct `Modules\User\Models\User` model that the application actually uses.

**Solution:**

1. **Updated OrganizationAdminPermissionsSeeder** import statement:

   ```php
   // Before
   use App\Models\User;

   // After
   use Modules\User\Models\User;
   ```

2. **Updated UserSeeder** import statement with the same change

3. **Verified seeder functionality** by running `php artisan db:seed --class=OrganizationAdminPermissionsSeeder` to ensure it works correctly

**Key Learnings:**

- **Always use the correct User model** for the application's architecture - check if it's `App\Models\User` or `Modules\User\Models\User`
- **Test seeders after User model changes** to ensure they work correctly
- **Modular architecture impacts** - when using Laravel modules, model paths may be different from standard Laravel structure
- **Guard compatibility** - using the wrong User model can cause guard mismatch errors in role/permission assignments

**Prevention:**

- When creating new seeders that work with User models, verify which User model class the application actually uses
- Check existing working seeders or controllers to see which User model import they use
- Test seeder functionality after any User model path changes
- Always use the correct namespace for models in modular applications to avoid class not found errors
